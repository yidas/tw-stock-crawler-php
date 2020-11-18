<?php

namespace yidas\twStockCrawler\crawlers;

/**
 * Crawler Interface
 * 
 * @author  Nick Tsai
 * @since   1.0.0
 */
interface CrawlerInterface 
{
    public static function getCompanyData($stockId);
    public static function getPrice($stockId, $date);
    public static function getEPS($stockId, $year);
    public static function getDividend($stockId, $year);
}

