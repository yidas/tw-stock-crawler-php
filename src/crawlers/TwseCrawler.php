<?php

namespace yidas\twStockCrawler\crawlers;

/**
 * Crawler - Source of Yahoo 
 * 
 * @author  Nick Tsai
 * @since   1.0.0
 */
class TwseCrawler extends BaseCrawler implements CrawlerInterface
{
    const HOST = "https://mops.twse.com.tw";
    const HOST_TRX = "https://www.twse.com.tw";

    /**
     * Get Company data
     *
     * @param string|integer $stockId
     * @return array
     */
    public static function getCompanyData($stockId=false)
    {
        $html = self::request(self::HOST . "/mops/web/ajax_t05st03", [
            "co_id" => self::getStockId($stockId),
            // Imitated params
            "encodeURIComponent" => "1", "step" => "1", "firstin" => "1", "off" => "1", "queryName" => "co_id", "inpuType" => "co_id", "TYPEK" => "all",
        ], 'POST');
        // echo $html;exit;
        
        # Check for HTML
        if (!self::checkResponseBody($html))
            return false;

        /**
         * Parse
         */

        /* Get Title */
        $pattern = "/\n(\W+)<\/span>　公司提供/";
        // preg_match($pattern, $html, $matches);var_dump($matches);exit;
        $title = preg_match($pattern, $html, $match) ? $match[1] : '';

        /* Get OTC */
        $pattern = '/>\((\W+)公司\)/';
        // preg_match($pattern, $html, $matches);var_dump($matches);exit;
        $type = preg_match($pattern, $html, $match) ? $match[1] : '';
        $isOTC = ($type=="上櫃") ? true : false;

        /* Get Industry */
        $pattern = "/<td nowrap class='lColor'>(\W+)\s/";
        // preg_match($pattern, $html, $matches);var_dump($matches);exit;
        $industry = preg_match($pattern, $html, $match) ? $match[1] : '';

        $response = self::response('company', [
            (string) self::getStockId($stockId),
            (boolean) $isOTC,
            (string) $title,
            (string) $industry,
        ]);
        // var_dump($response);exit;
        return $response;
    }

    /**
     * Get Last Price
     *
     * @param string|integer $stockId
     * @return array
     */
    public static function getLastPrice($stockId=false)
    {
        return self::getPrice($stockId);
    }

    /**
     * Get Price
     *
     * @param string|integer $stockId
     * @param string $date
     * @return array
     */
    public static function getPrice($stockId=false, $date=null)
    {
        // Date
        $dateFormat = "Ymd";
        $date = ($date) ? date($dateFormat, strtotime($date)) : date($dateFormat);
        
        // API returning JSON
        $json = self::request(self::HOST_TRX . "/exchangeReport/STOCK_DAY?response=json&date=%s&stockNo=%s", [
            $date,
            self::getStockId($stockId),
            ]);
        
        # Check for JSON
        if (!self::checkResponseBody($json))
            return false;

        $data = json_decode($json, true);
        // var_dump($data);exit;

        // Data parsing
        if (!isset($data["data"])) {
            return false;
        }
        // Changing date to ROC date for matching data
        $unixTime = strtotime($date);
        $targetDate = date("Y", $unixTime) - 1911 . date("/m/d", $unixTime);
        foreach ($data["data"] as $key => $row) {
            if ($targetDate == $row[0]) {
                break;
            }
        }
        // var_dump($row);exit;

        // 0 => Date
        $date = $row[0];
        $time = null;
        // 6 => Closing price
        $amount = (float) $row[6];

        $response = self::response('price', [
            $date,
            $time,
            $amount,
        ]);
        // var_dump($response);exit;
        return $response;
    }

    /**
     * Get Dividend
     *
     * @param string|integer $stockId
     * @param integer $year
     * @return array
     */
    public static function getEPS($stockId=false, $year=null)
    {
        // Date
        $year = ($year) ? $year : date("Y");
        $yearROC = $year - 1911;

        $html = self::request(self::HOST . "/mops/web/ajax_t163sb15", [
            "co_id" => self::getStockId($stockId),
            "isnew" => (!$year) ? "true" : "false",
            "year" => $yearROC,
            // Imitated params
            "encodeURIComponent" => "1", "step" => "1", "firstin" => "1", "off" => "1", "queryName" => "co_id", "inpuType" => "co_id", "TYPEK" => "all",
        ], 'POST');
        // echo $html;exit;
        
        # Check for HTML
        if (!self::checkResponseBody($html))
            return false;

        /**
         * Parse
         */

        /* Get Year */
        $pattern = '/>(\d+)年度資料<\/td>/';
        // preg_match($pattern, $html, $matches);var_dump($matches);exit;
        $yearROC = preg_match($pattern, $html, $match) ? $match[1] : '';
        $year = $yearROC + 1911;

        /* Get EPSs by season */
        $pattern = "/<th class='odd' style='text-align:left'>基本每股盈餘（元）<\/th><td class='odd' style='text-align:right'>(\S+)<\/td>\n<td class='odd' style='text-align:right'>(\S+)<\/td>\n<td class='odd' style='text-align:right'>(\S+)<\/td>\n<td class='odd' style='text-align:right'>(\S+)<\/td>/";
        preg_match($pattern, $html, $matches);
        // var_dump($matches);exit;
        if (!$matches)
            return false;

        $details = [];
        $lastCumulativeAmount = 0;
        for ($i=1; $i <= 4; $i++) { 
            
            if (!isset($matches[$i]) || !is_numeric($matches[$i]))
                continue;

            // Separating cumulative amount
            $cumulativeAmount = (float) ($matches[$i]);
            $amount = $cumulativeAmount - $lastCumulativeAmount;
            $lastCumulativeAmount += $amount;

            $details[] = [
                'year' => $year,
                'belong' => "Q{$i}",
                'amount' => $amount,
            ];
        }

        $responses[] = self::response('eps', [
            $year,
            $lastCumulativeAmount,
            array_reverse($details),
        ]);

        // var_dump($response);exit;
        return $responses;
    }

    /**
     * Get Dividend
     *
     * @param string|integer $stockId
     * @param integer $year
     * @return array
     */
    public static function getDividend($stockId=false, $year=null)
    {
        // Date
        $date = ($year) ? $year : date("Y");
        $date = $date - 1911;

        $html = self::request(self::HOST . "/mops/web/ajax_t05st09_2", [
            "co_id" => self::getStockId($stockId),
            "isnew" => (!$year) ? "true" : "false",
            "date1" => $date,
            "date2" => $date,
            // Imitated params
            "encodeURIComponent" => "1", "step" => "1", "firstin" => "1", "off" => "1", "queryName" => "co_id", "inpuType" => "co_id", "TYPEK" => "all", "qryType" => 1,
        ], 'POST');
        // echo $html;exit;
        
        # Check for HTML
        if (!self::checkResponseBody($html))
            return false;

        /**
         * Parse
         */

        /* Get Year */
        $pattern = "/nowrap>(\d+)年<br>/";
        // preg_match($pattern, $html, $matches);var_dump($matches);exit;
        $yearROC = preg_match($pattern, $html, $match) ? $match[1] : '';
        $year = $yearROC + 1911;

        // Distinguish the way dividends are distributed
        $details = [];
        // By half year
        if (strpos($html, '下半年') !== false) {

            $wordMap = [1 => '上', 2 => '下'];
            foreach ($wordMap as $key => $value) {
                /* Get Dividend */
                $pattern = "/>(\d+)年<br>{$value}半年&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>(\d+)\/(\d+\/\d+)&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\S*&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>\S+&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>/";
                preg_match($pattern, $html, $matches);
                // var_dump($matches);exit;
                if (!$matches)
                    continue;

                $detail = self::processDividendDetail($matches);
                $detail["belong"] = "H{$key}";
                $details[] = $detail;
            }
        } 
        // By season
        if (strpos($html, '<br>第1季&nbsp;') !== false || strpos($html, '<br>第4季&nbsp;') !== false) {

            for ($i=1; $i <= 4; $i++) { 
                /* Get Dividend */
                $pattern = "/>(\d+)年<br>第{$i}季&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>(\d+)\/(\d+\/\d+)&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\S*&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>\S+&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>/";
                preg_match($pattern, $html, $matches);
                // var_dump($matches);exit;
                if (!$matches)
                    continue;

                $detail = self::processDividendDetail($matches);
                $detail["belong"] = "Q{$i}";
                $details[] = $detail;
            }
        }
        // By year
        if (strpos($html, '<br>年度&nbsp;')) {
            /* Get Dividend */
            $pattern = "/>(\d+)年<br>年度&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>(\d+)\/(\d+\/\d+)&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\S*&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='center' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>\S+&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>\s+\S+&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>\s\n\s<TD align='right' nowrap>(\S+)&nbsp;<\/TD>/";
            preg_match($pattern, $html, $matches);
            // var_dump($matches);exit;
            if ($matches)
                $details[] = self::processDividendDetail($matches);
        }
        // var_dump($details);exit;

        // Aggregation
        $cash = 0;
        $stock = 0;
        $total = 0;
        foreach ($details as $key => $detail) {
            $cash += $detail["cash"];
            $stock += $detail["stock"];
            $total += $detail["total"];
        }

        $response = self::response('dividend', [
            $year,
            $cash,
            $stock,
            $total,
            array_reverse($details),
        ]);
        // var_dump($response);exit;
        return $response;
    }

    /**
     * Process Dividend Detail
     *
     * @param array $matches Regex matches
     * @return array
     */
    protected static function processDividendDetail($matches) {

        $belongYear = isset($matches[1]) ? $matches[1] + 1911 : null;
        $releaseYear = isset($matches[2]) ? $matches[2] + 1911 : null;
        $releaseDate = isset($matches[3]) ? "{$releaseYear}/{$matches[3]}" : null;
        $cash = isset($matches[4]) ? (float) ($matches[4]) : 0;
        $cashReserve = isset($matches[5]) ? (float) ($matches[5]) : 0;
        $cash += $cashReserve;
        $stock = isset($matches[6]) ? (float) $matches[6] : 0;
        $stockReserve = isset($matches[7]) ? (float) $matches[7] : 0;
        $stock += $stockReserve;
        $total = (float) $cash + $stock;
        
        return [
            'year' => $belongYear,
            'belong' => "$belongYear",
            'cash' => $cash,
            'cashReleaseYear' => ($cash) ? $releaseYear : null,
            'cashReleaseDate' => ($cash) ? $releaseDate : null,
            'stock' => $stock,
            'stockReleaseYear' => ($stock) ? $releaseYear : null,
            'stockReleaseDate' => ($stock) ? $releaseDate : null,
            'total' => $total,
        ];
    }
}



