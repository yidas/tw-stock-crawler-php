<?php

namespace yidas\twStockCrawler\crawlers;

/**
 * Base Crawler
 * 
 * @author  Nick Tsai
 * @since   1.0.0
 */
Abstract class BaseCrawler implements CrawlerInterface
{
    const HOST = "";

    protected static $stockId = null;

    protected static $returnFormats = [
        'company' => [
            0 => 'id',
            1 => 'isOTC',
            2 => 'title',
            3 => 'industry',
        ],
        'price' => [
            0 => 'date',
            1 => 'timestamp',
            2 => 'amount',
        ],
        'dividend' => [
            0 => 'year',
            1 => 'cash',
            2 => 'stock',
            3 => 'total',
            4 => 'details',
        ],
        'eps' => [
            0 => 'year',
            1 => 'amount',
            2 => 'details',
        ],
    ];

    /**
     * URI list
     * 
     * The symbol "%s" means $stockId for replacement
     *
     * @var array
     */
    protected static $uriList = [];

    /**
     * Set Stock ID
     *
     * @param string|integer $stockId
     * @return boolean
     */
    public static function setStockId($stockId)
    {
        self::$stockId = self::parseStockId($stockId);
        return true;
    }

    /**
     * Get Stock ID
     *
     * @return integer
     */
    public static function getStockId($stockId=false)
    {
        return ($stockId) ? $stockId : self::$stockId;
    }

    /**
     * Parse Stock ID
     *
     * @param string|integer $stockId
     * @return string
     */
    protected static function parseStockId($stockId)
    {
        return sprintf("%04d", $stockId);
    }

    /**
     * Request
     *
     * @param string $url
     * @param array $params
     * @param string $method
     * @return boolean|string Response body when successful
     */
    protected static function request($url, $params=[], $method='GET', $isMobile=false)
    {
        $opts = [
            'http' => [
                'protocol_version' => '1.1',
                'method' => $method,
                'header' => '',
            ]
        ];
        
        // Mobile client
        if ($isMobile) {
            $opts["http"]["header"] .= "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Mobile Safari/537.36;";
        }

        // Method process
        if ($method == 'GET') {

            $url = vsprintf($url, $params);

        } else {

            $opts["http"]["header"] .= "Content-Type: application/x-www-form-urlencoded";
            $opts["http"]["content"] = http_build_query($params);
        }

        return @file_get_contents($url, false, stream_context_create($opts));
    }

    /**
     * Request
     *
     * @param string $uriKey
     * @param string $stockId
     * @param string $method
     * @return boolean|string Response body when successful
     */
    // protected static function request($uriKey, $stockId, $method='GET')
    // {
    //     $url = sprintf(static::HOST . static::$uriList[$uriKey], static::parseStockId($stockId));
    //     return @file_get_contents($url);
    // }

    /**
     * Check response body
     *
     * @param mixed $responseBody from file_get_contents
     * @return boolean
     */
    protected static function checkResponseBody($responseBody)
    {
        return ($responseBody || strlen($responseBody) > 100) ? true : false;
    }

    /**
     * Formatted response 
     *
     * @param string $uriKey
     * @param array $data
     * @return array
     */
    protected static function response($uriKey, $data)
    {
        if (!isset(self::$returnFormats[$uriKey])) {
            return false;
        }

        $response = [];
        foreach ((array) self::$returnFormats[$uriKey] as $key => $value) {
            $response[$value] = $data[$key];
        }

        return $response;
    }

    /**
     * Numeric
     *
     * @param string $string
     * @return integer
     */
    protected static function numeric($string)
    {
        return (int) str_replace(',', '', $string);
    }
}



