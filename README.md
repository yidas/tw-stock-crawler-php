Taiwan Stock Crawler by PHP
===========================

PHP Crawler for Taiwan Stock Data (台股資料爬蟲)

[![Latest Stable Version](https://poser.pugx.org/yidas/tw-stock-crawler/v/stable?format=flat-square)](https://packagist.org/packages/yidas/tw-stock-crawler)
[![License](https://poser.pugx.org/yidas/tw-stock-crawler/license?format=flat-square)](https://packagist.org/packages/yidas/tw-stock-crawler)

OUTLINE
-------

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
    - [Company Data](#company-data)
    - [Price](#price)
    - [EPS](#eps)
    - [Dividend](#dividend)
- [References](#references)

---

REQUIREMENTS
------------
This library requires the following:

- PHP 5.4.0+\|7.0+

---

INSTALLATION
------------

Run Composer in your project:

    composer require yidas/tw-stock-crawler
    
Then you could call it after Composer is loaded depended on your PHP framework:

```php
require __DIR__ . '/vendor/autoload.php';

use yidas\twStockCrawler\Crawler;
```

---

USAGE
-----

You could first configure the crawler with source or stockId, and then call the function you need.

```php
$parser = \yidas\twStockCrawler\Crawler::config(["source"=>"yahoo"]);
$parser::setStockId("2330");
$priceData2330 = $parser::getLastPrice();
$priceData2454 = $parser::getLastPrice("2454");
$companyData2330 = $parser::getCompanyData();
```

### Company Data

`Crawler::getCompanyData("2330");`:

```php
Array
(
    [id] => 2330
    [isOTC] =>
    [title] => 台積電
    [industry] => 半導體
)
```

### Price

- Last Price

`Crawler::getLastPrice("2330");`:

```php
Array
(
    [date] => 20210517
    [timestamp] => 13:30:00
    [amount] => 549
)
```

### EPS

`Crawler::getEPS("2330", 2020);`

```php
Array
(
    [0] => Array
        (
            [year] => 2020
            [amount] => 19.98
            [details] => Array
                (
                    [0] => Array
                        (
                            [year] => 2020
                            [belong] => Q4
                            [amount] => 5.51
                        )

                    [1] => Array
                        (
                            [year] => 2020
                            [belong] => Q3
                            [amount] => 5.3
                        )

                    [2] => Array
                        (
                            [year] => 2020
                            [belong] => Q2
                            [amount] => 4.66
                        )

                    [3] => Array
                        (
                            [year] => 2020
                            [belong] => Q1
                            [amount] => 4.51
                        )
                )
        )
)
```

### Dividend

- Dividend by year

`Crawler::getDividend("2454", 2009);`:


```php
Array
(
    [year] => 2009
    [cash] => 26
    [stock] => 0.02
    [total] => 26.02
    [details] => Array
        (
            [0] => Array
                (
                    [year] => 2009
                    [belong] => 2009
                    [cash] => 26
                    [cashReleaseYear] => 2010
                    [cashReleaseDate] => 2010/08/26
                    [stock] => 0.02
                    [stockReleaseYear] =>
                    [stockReleaseDate] => 2010/08/26
                    [total] => 26.02
                )
        )
)
```

- Dividend by half year

`Crawler::getDividend("5283", 2019);`:

```php
Array
(
    [year] => 2019
    [cash] => 8.16
    [stock] => 0
    [total] => 8.16
    [details] => Array
        (
            [0] => Array
                (
                    [year] => 2019
                    [belong] => H2
                    [cash] => 4.16
                    [cashReleaseYear] => 2020
                    [cashReleaseDate] => 2020/09/29
                    [stock] =>
                    [stockReleaseYear] =>
                    [stockReleaseDate] =>
                    [total] => 4.16
                )

            [1] => Array
                (
                    [year] => 2019
                    [belong] => H1
                    [cash] => 4
                    [cashReleaseYear] => 2020
                    [cashReleaseDate] => 2020/04/20
                    [stock] =>
                    [stockReleaseYear] =>
                    [stockReleaseDate] =>
                    [total] => 4
                )
        )
)
```

- Dividend by season

`Crawler::getDividend("2330", 2020);`:

```php
Array
(
    [year] => 2020
    [cash] => 10
    [stock] => 0
    [total] => 10
    [details] => Array
        (
            [0] => Array
                (
                    [year] => 2020
                    [belong] => Q4
                    [cash] => 2.5
                    [cashReleaseYear] => 2021
                    [cashReleaseDate] => 2021/07/14
                    [stock] =>
                    [stockReleaseYear] =>
                    [stockReleaseDate] =>
                    [total] => 2.5
                )

            [1] => Array
                (
                    [year] => 2020
                    [belong] => Q3
                    [cash] => 2.5
                    [cashReleaseYear] => 2021
                    [cashReleaseDate] => 2021/04/14
                    [stock] =>
                    [stockReleaseYear] =>
                    [stockReleaseDate] =>
                    [total] => 2.5
                )

            [2] => Array
                (
                    [year] => 2020
                    [belong] => Q2
                    [cash] => 2.5
                    [cashReleaseYear] => 2021
                    [cashReleaseDate] => 2021/01/13
                    [stock] =>
                    [stockReleaseYear] =>
                    [stockReleaseDate] =>
                    [total] => 2.5
                )

            [3] => Array
                (
                    [year] => 2020
                    [belong] => Q1
                    [cash] => 2.5
                    [cashReleaseYear] => 2020
                    [cashReleaseDate] => 2020/10/14
                    [stock] =>
                    [stockReleaseYear] =>
                    [stockReleaseDate] =>
                    [total] => 2.5
                )
        )
)
```

---

REFERENCES
----------

- [Yahoo奇摩股市](https://tw.stock.yahoo.com/)

- [TWSE 臺灣證券交易所](https://www.twse.com.tw/zh/)

- [TWSE 公開資訊觀測站](https://mops.twse.com.tw/mops/web/index)

