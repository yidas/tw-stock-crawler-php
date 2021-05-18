<?php

namespace yidas\twStockCrawler\taifexCrawlers;

/**
 * Crawler Interface
 * 
 * @author  Nick Tsai
 * @since   1.0.0
 */
interface CrawlerInterface 
{
    public static function getFutureContracts($date);
    public static function getOptions($date);
}

