<?php

namespace yidas\twStockCrawler\taifexCrawlers;

/**
 * Crawler - Source of Yahoo 
 * 
 * @author  Nick Tsai
 * @since   1.0.0
 */
class TaifexCrawler extends BaseCrawler
{
    const HOST = "https://www.taifex.com.tw";

    /**
     * Get Future Contracts
     *
     * @param string $date
     * @return array
     */
    public static function getFutureContracts($date=null)
    {
        // Date
        $dateFormat = "Y/m/d";
        $date = ($date) ? date($dateFormat, strtotime($date)) : date($dateFormat);

        $html = self::request(self::HOST . "/cht/3/futContractsDate", [
            "queryDate" => $date,
            // Imitated params
            "queryType" => "1", "goDay" => "", "doQuery" => "1", "dateaddcnt" => "-1", "commodityId" => "",
        ], 'POST');
        // echo $html;exit;
        
        # Check for HTML
        if (!self::checkResponseBody($html))
            return false;

        /**
         * Parse
         */
        $data = [];

        /* Get Title */
        $pattern = '/<span class="right">日期(\S+)<\/span>/';
        // preg_match($pattern, $html, $matches);var_dump($matches);exit;
        $data['date'] = preg_match($pattern, $html, $match) ? $match[1] : '';

        /* Get All lots */
        $pattern = '/<font color="blue">\s*\n*\s*(\S+)<\/font>/';
        // preg_match_all($pattern, $html, $matches);var_dump($matches);exit;
        $matches = preg_match_all($pattern, $html, $match) ? $match[1] : '';
        # Check for Data (329 is the last row if data has updated successfully)
        if (!isset($matches[329]) || !isset($matches[71]) || !is_string($matches[71]))
            return false;
        $data['tx']['oi']['long']['volume']['dealers'] = self::numeric($matches[3]);
        $data['tx']['oi']['short']['volume']['dealers'] = self::numeric($matches[4]);
        $data['tx']['oi']['net']['volume']['dealers'] = self::numeric($matches[5]);
        $data['tx']['oi']['long']['volume']['fini'] = self::numeric($matches[15]);
        $data['tx']['oi']['short']['volume']['fini'] = self::numeric($matches[16]);
        $data['tx']['oi']['net']['volume']['fini'] = self::numeric($matches[17]);
        $data['mtx']['oi']['long']['volume']['dealers'] = self::numeric($matches[57]);
        $data['mtx']['oi']['short']['volume']['dealers'] = self::numeric($matches[58]);
        $data['mtx']['oi']['net']['volume']['dealers'] = self::numeric($matches[59]);
        $data['mtx']['oi']['long']['volume']['fini'] = self::numeric($matches[69]);
        $data['mtx']['oi']['short']['volume']['fini'] = self::numeric($matches[70]);
        $data['mtx']['oi']['net']['volume']['fini'] = self::numeric($matches[71]);
        // print_r($data);exit;

        /* Get All amount */
        $pattern = '/<div align="right">\s*\n*\s*(\S+)<\/div>/';
        // preg_match_all($pattern, $html, $matches);var_dump($matches);exit;
        $matches = preg_match_all($pattern, $html, $match) ? $match[1] : '';
        # Check for Data
        if (!isset($matches[71]) || !is_string($matches[71]))
            return false;
        $data['tx']['oi']['long']['value']['dealers'] = self::numeric($matches[3]);
        $data['tx']['oi']['short']['value']['dealers'] = self::numeric($matches[4]);
        $data['tx']['oi']['net']['value']['dealers'] = self::numeric($matches[5]);
        $data['tx']['oi']['long']['value']['fini'] = self::numeric($matches[15]);
        $data['tx']['oi']['short']['value']['fini'] = self::numeric($matches[16]);
        $data['tx']['oi']['net']['value']['fini'] = self::numeric($matches[17]);
        $data['mtx']['oi']['long']['value']['dealers'] = self::numeric($matches[57]);
        $data['mtx']['oi']['short']['value']['dealers'] = self::numeric($matches[58]);
        $data['mtx']['oi']['net']['value']['dealers'] = self::numeric($matches[59]);
        $data['mtx']['oi']['long']['value']['fini'] = self::numeric($matches[69]);
        $data['mtx']['oi']['short']['value']['fini'] = self::numeric($matches[70]);
        $data['mtx']['oi']['net']['value']['fini'] = self::numeric($matches[71]);
        // print_r($data);exit;

        return $data;

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
     * Get Future Contracts
     *
     * @param string $date
     * @return array
     */
    public static function getOptions($date=null)
    {
        // Date
        $dateFormat = "Y/m/d";
        $date = ($date) ? date($dateFormat, strtotime($date)) : date($dateFormat);

        $html = self::request(self::HOST . "/cht/3/callsAndPutsDate", [
            "queryDate" => $date,
            // Imitated params
            "queryType" => "1", "goDay" => "", "doQuery" => "1", "dateaddcnt" => "-1", "commodityId" => "",
        ], 'POST');
        // echo $html;exit;
        
        # Check for HTML
        if (!self::checkResponseBody($html))
            return false;

        /**
         * Parse
         */
        $data = [];

        /* Get Title */
        $pattern = '/<span class="right">日期(\S+)<\/span>/';
        // preg_match($pattern, $html, $matches);var_dump($matches);exit;
        $data['date'] = preg_match($pattern, $html, $match) ? $match[1] : '';

        /* Get All lots */
        $pattern = '/<font color="blue">\s*\n*\s*(\S+)<\/font>/';
        // preg_match_all($pattern, $html, $matches);var_dump($matches);exit;
        $matches = preg_match_all($pattern, $html, $match) ? $match[1] : '';
        # Check for Data
        if (!isset($matches[35]) || !is_string($matches[35]))
            return false;
        $data['txo']['call']['oi']['long']['volume']['dealers'] = self::numeric($matches[3]);
        $data['txo']['call']['oi']['short']['volume']['dealers'] = self::numeric($matches[4]);
        $data['txo']['call']['oi']['net']['volume']['dealers'] = self::numeric($matches[5]);
        $data['txo']['call']['oi']['long']['volume']['fini'] = self::numeric($matches[15]);
        $data['txo']['call']['oi']['short']['volume']['fini'] = self::numeric($matches[16]);
        $data['txo']['call']['oi']['net']['volume']['fini'] = self::numeric($matches[17]);
        $data['txo']['put']['oi']['long']['volume']['dealers'] = self::numeric($matches[21]);
        $data['txo']['put']['oi']['short']['volume']['dealers'] = self::numeric($matches[22]);
        $data['txo']['put']['oi']['net']['volume']['dealers'] = self::numeric($matches[23]);
        $data['txo']['put']['oi']['long']['volume']['fini'] = self::numeric($matches[33]);
        $data['txo']['put']['oi']['short']['volume']['fini'] = self::numeric($matches[34]);
        $data['txo']['put']['oi']['net']['volume']['fini'] = self::numeric($matches[35]);
        // print_r($data);exit;

        /* Get All amount (The column format of the first amount is different) */
        $pattern = '/\'#FFFFF0\'>\s*\n*\s*(\S+)<\/TD>/';
        // preg_match_all($pattern, $html, $matches);var_dump($matches);exit;
        $matches = preg_match_all($pattern, $html, $match) ? $match[1] : '';
        # Check for Data
        if (!isset($matches[29]) || !is_string($matches[29]))
            return false;
        $data['txo']['call']['oi']['long']['value']['dealers'] = self::numeric($matches[2]);
        $data['txo']['call']['oi']['short']['value']['dealers'] = self::numeric($matches[3]);
        $data['txo']['call']['oi']['net']['value']['dealers'] = self::numeric($matches[4]);
        $data['txo']['call']['oi']['long']['value']['fini'] = self::numeric($matches[12]);
        $data['txo']['call']['oi']['short']['value']['fini'] = self::numeric($matches[13]);
        $data['txo']['call']['oi']['net']['value']['fini'] = self::numeric($matches[14]);
        $data['txo']['put']['oi']['long']['value']['dealers'] = self::numeric($matches[17]);
        $data['txo']['put']['oi']['short']['value']['dealers'] = self::numeric($matches[18]);
        $data['txo']['put']['oi']['net']['value']['dealers'] = self::numeric($matches[19]);
        $data['txo']['put']['oi']['long']['value']['fini'] = self::numeric($matches[27]);
        $data['txo']['put']['oi']['short']['value']['fini'] = self::numeric($matches[28]);
        $data['txo']['put']['oi']['net']['value']['fini'] = self::numeric($matches[29]);
        // print_r($data);exit;

        return $data;
    }
    
    /**
     * Numeric
     *
     * @param string $string
     * @return integer
     */
    private static function numeric($string)
    {
        return (int) str_replace(',', '', $string);
    }
}



