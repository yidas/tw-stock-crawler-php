<?php

namespace yidas\twStockCrawler\crawlers;

/**
 * Crawler - Source of Yahoo 
 * 
 * @author  Nick Tsai
 * @since   1.0.0
 */
class YahooCrawler extends BaseCrawler implements CrawlerInterface
{
    const HOST = "https://tw.stock.yahoo.com";

    /**
     * getPrice() crawled from TWSE
     */
    const HOST_PRICE = "https://www.twse.com.tw";

    /**
     * Get Company data
     *
     * @param string|integer $stockId
     * @return array
     */
    public static function getCompanyData($stockId=false)
    {
        // Yahoo Moblie
        $html = self::request(self::HOST . '/quote/%s/profile', [self::getStockId($stockId)], 'GET', true);
        
        # Check for HTML
        if (!self::checkResponseBody($html))
            return false;
        // echo $html;exit;

        /**
         * Parse
         */
        // Get title
        $pattern = '/,"name":"(\W+)",/';
        preg_match($pattern, $html, $matches);
        // var_dump($matches);exit;
        $title = isset($matches[1]) ? $matches[1] : null;

        // Get industry
        $pattern = '/,"sectorName":"(\W+)",/';
        preg_match($pattern, $html, $matches);
        // var_dump($matches);exit;
        $industry = isset($matches[1]) ? $matches[1] : null;

        // Get OTC
        $pattern = '/,"exchangeName":"(\W+)",/';
        preg_match($pattern, $html, $matches);
        // var_dump($matches);exit;
        $type = isset($matches[1]) ? $matches[1] : null;
        $isOTC = ($type=="上櫃") ? true : false;

        /* PC mode pattern
        // Get title
        $pattern = "/<TITLE>([\x{4e00}-\x{9fa5}]+)/u";
        $title = preg_match($pattern, $html, $match) ? $match[1] : '';
        // Get Industry
        $pattern = "/<td width=\"84\" align=\"left\">([\x{4e00}-\x{9fa5}]+)/u";
        $industry = preg_match($pattern, $html, $match) ? $match[1] : ''; 
        */

        $response = self::response('company', [
            (string) $stockId,
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
        // Yahoo Moblie
        $html = self::request(self::HOST . '/quote/%s/time-sales', [self::getStockId($stockId)], 'GET', true);
        
        # Check for HTML
        if (!self::checkResponseBody($html))
            return false;
        // echo $html;exit;
        
        /**
         * Parse
         */
        // Get last price
        $pattern = '/,"price":"(\S+)","regularMarketDayHigh"/';
        preg_match($pattern, $html, $matches);
        // var_dump($matches);exit;
        $amount = isset($matches[1]) ? (float) $matches[1] : null;

        // Get Date
        $pattern = '/{"closeTime":"(\S+)","status":"\S+"},"nextMarketTime"/';
        preg_match($pattern, $html, $matches);
        // var_dump($matches);exit;
        $unixTime = isset($matches[1]) ? strtotime($matches[1]) + (8*3600) : null;
        $date = date("Ymd", $unixTime);
        $time = date("H:i:s", $unixTime);

        $response = self::response('price', [
            $date,
            $time,
            $amount,
        ]);
        // var_dump($response);exit;
        return $response;
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
        $json = self::request(self::HOST_PRICE . "/exchangeReport/STOCK_DAY?response=json&date=%s&stockNo=%s", [
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
        // Yahoo Moblie
        $json = self::request(self::HOST . '/_td-stock/api/resource/StockServices.revenues;includedFields=priceAssessment;period=quarter;symbol=%s.TW?bkt=&device=smartphone&ecma=modern&feature=&intl=tw&lang=zh-Hant-TW&partner=none&prid=2unfidpga2509&region=TW&site=finance&tz=Asia%%2FTaipei&ver=1.2.978&returnMeta=true', [self::getStockId($stockId)], 'GET', true);
        
        # Check for HTML
        if (!self::checkResponseBody($json))
            return false;
        
        $data = json_decode($json, true);
        // var_dump($data);exit;

        /**
         * Parse
         */

        if (!isset($data["data"]["data"]["result"]["revenues"]))
            return false;
        $nodeList = & $data["data"]["data"]["result"]["revenues"];

        // Year
        $year = ($year) ? (int) sprintf("%04d", $year) : ((int) date("Y"));
        // var_dump($year);exit;

        // Map of month to quarter
        $belongMap = ["03" => 'Q1', "06" => 'Q2', "09" => 'Q3', "12" => 'Q4',];
        $details = [];
        foreach ($nodeList as $key => $row) {

            $rowUnixTime = strtotime($row["date"]) + (8*3600);
            if ($year == date("Y", $rowUnixTime)) {

                $belongMonth = date("m", $rowUnixTime);
                $belong = isset($belongMap[$belongMonth]) ? $belongMap[$belongMonth] : null;
                $eps = isset($row['eps']) ? (float) $row['eps'] : null;

                $details[] = [
                    'year' => $year,
                    'belong' => $belong,
                    'amount' => $eps,
                ];
            }
        }
        // var_dump($details);exit;

        $total = 0;
        foreach ($details as $key => $detail) {
            $total += $detail["amount"];
        }

        $responses[] = self::response('eps', [
            $year,
            $total,
            $details,
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
        // Yahoo Moblie
        $json = self::request(self::HOST . '/_td-stock/api/resource/StockServices.dividends;date=;limit=100;showUpcoming=true;sortBy=-date;symbol=%s.TW', [self::getStockId($stockId)], 'GET', true);
        
        # Check for HTML
        if (!self::checkResponseBody($json))
            return false;

        $data = json_decode($json, true);
        // var_dump($data);exit;

        /**
         * Parse
         */

        if (!isset($data["dividends"]))
            return false;

        // Year
        $year = ($year) ? (int) sprintf("%04d", $year) : ((int) date("Y")) -1;
        // var_dump($year);exit;

        $details = [];
        foreach ($data["dividends"] as $key => $row) {
            if ($year == $row["year"]) {

                // Yahoo mobile period pattern
                $belongMap = ["H1" => "H1", "H2" => "H2", "Q1" => "Q1", "Q2" => "Q2", "Q3" => "Q3", "Q4" => "Q4"];
                $belong = ($row["period"]=="FY") ? $year : (isset($belongMap[$row["period"]]) ? $belongMap[$row["period"]] : null);

                $cash = isset($row["exDividend"]["cash"]) ? (float) $row["exDividend"]["cash"] : null;
                $cashReleaseYear = isset($row["exDividend"]["cashPayYear"]) ? (int) $row["exDividend"]["cashPayYear"] : null;
                $cashReleaseDate = isset($row["exDividend"]["cashPayDate"]) ? date("Y/m/d", strtotime($row["exDividend"]["cashPayDate"])) : null;

                $stock = isset($row["exRight"]["stock"]) ? (float) $row["exRight"]["stock"] : null;
                $stockReleaseYear = isset($row["exRight"]["cashPayYear"]) ? (int) $row["exRight"]["cashPayYear"] : null;
                $stockReleaseDate = isset($row["exRight"]["stockPayDate"]) ? date("Y/m/d", strtotime($row["exRight"]["stockPayDate"])) : null;

                $details[] = [
                    'year' => $year,
                    'belong' => $belong,
                    'cash' => $cash,
                    'cashReleaseYear' => $cashReleaseYear,
                    'cashReleaseDate' => $cashReleaseDate,
                    'stock' => $stock,
                    'stockReleaseYear' => $stockReleaseYear,
                    'stockReleaseDate' => $stockReleaseDate,
                    'total' => (float) $cash + $stock,
                ];
            }
        }
        // var_dump($details);exit;

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
            $details,
        ]);
        // var_dump($response);exit;
        return $response;
    }
}



