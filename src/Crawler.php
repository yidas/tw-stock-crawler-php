<?php

namespace yidas\twStockCrawler;

use Exception;

/**
 * Crawler Container
 * 
 * @author  Nick Tsai
 * @version 1.0.0
 */
class Crawler
{
    const CRAWLER_NAMESPACE = "\\" . __NAMESPACE__ . "\\crawlers";
    
    const DEFAULT_SOURCE = "Yahoo";

    private static $crawlerClass = null;

    /**
     * Configuration
     *
     * @param string|array $options option parameters
     * @return self
     */
    public static function config($options=[])
    {
        // Quick setting for Crawler source
        if (is_string($options)) {
            // Params as a string key
            $source = $options;
            $options = [];
            $options['source'] = $source;
        }

        // Assignment
        $source = isset($options['source']) ? $options['source'] : self::DEFAULT_SOURCE;

        self::setSource($source);

        return new self;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return self
     */
    public static function setSource($source)
    {
        $source = ucfirst(strtolower($source));
        $class = self::CRAWLER_NAMESPACE . "\\{$source}Crawler";
        if (!class_exists($class)) {
            throw new Exception("Crawler \"{$source}\" not found with namespace: {$class}", 404);
        }
        self::$crawlerClass = $class;

        return new self;
    }

    /**
     * Magic call 
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (!self::$crawlerClass) {
            self::config();
        }

        // Debug
        // echo "Calling object method '$name' " . implode(', ', $arguments). "\n";

        return forward_static_call_array([self::$crawlerClass, $name], $arguments);
    }
}



