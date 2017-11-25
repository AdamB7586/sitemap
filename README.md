# PHP XML Sitemap Generator
Generate a XML sitemap for a given URL. This class crawls any given website to create an XML sitemap for the domain.

## Installation

Installation is available via [Composer/Packagist](https://packagist.org/packages/adamb/database), you can add the following line to your `composer.json` file:

```json
"adamb/sitemap": "^1.0"
```

or

```sh
composer require adamb/sitemap
```

## Usage

Example of usage can be found below:

```php

// Method 1
$sitemap = new Sitemap\Sitemap('http://www.yourwebsite.co.uk');
$sitemap->createSitemap(); // Returns true if sitemap created else will return false


// Method 2
$sitemap = new Sitemap\Sitemap();
$sitemap->setDomain('http://www.yourwebsite.co.uk');
$sitemap->createSitemap(); // Returns true if sitemap created else will return false

```

## Change file creation location

By default the sitemap.xml file is created in the document root but this can be altered using the following method.

```php

$sitemap = new Sitemap\Sitemap('http://www.yourwebsite.co.uk');

// This should be an absolute path
$sitemap->setFilePath($_SERVER['DOCUMENT_ROOT'].'sitemaps/');

// or

$sitemap->setFilePath('C:\Inetpub\mywebsite.co.uk\httpdocs\sitemaps\\');

$sitemap->createSitemap();

```

## Sitemap creation options

By default the sitemap creates a XSL stylesheet along with the sitemap. You can also change the level of the link to include in the sitemap (e.g. Only include links within 3 clicks of the homepage) and also change the filename of the sitemap on creation.

```php

// To not include the XSL stylesheet set the first value to false when calling createSitemap();
$sitemap->createSitemap(false);

// To only include links within 3 click set the second value to 3
$sitemap->createSitemap(true, 3);

// To change the filename set the third value to your filename (excluding extension)
$sitemap->createSitemap(true, 5, 'mysitemapfile');

```

