<?php

namespace Sitemap;

use KubAT\PhpSimple\HtmlDomParser;
use GuzzleHttp\Client;

class Sitemap {
    protected $guzzle;
    
    public $filepath;
    public $url;
    public $host;
    public $domain;
    public $links;
    public $images;
    public $videos;
    
    public $markup = '';
    public $contentID = 'content';
    
    protected $ignoreURLContaining = [];

    protected $priority = [0 => '1', 1 => '0.8', 2 => '0.6', 3 => '0.4', 4 => '0.2', 5 => '0.1'];
    protected $frequency = [0 => 'weekly', 1 => 'weekly', 2 => 'monthly', 3 => 'monthly', 4 => 'monthly', 5 => 'yearly'];
    
    /**
     * Crawl the homepage and get all of the links for that page
     * @param string $uri This should be the website homepage that you wish to crawl for the sitemap
     */
    public function __construct($uri = NULL) {
        $this->guzzle = new Client();
        if($uri !== NULL) {
            $this->setDomain($uri);
        }
        $this->setFilePath($_SERVER['DOCUMENT_ROOT']);
    }
    
    /**
     * Sets the domain that the sitemap should be created for
     * @param string $uri This should be the URL That you wish to create the sitemap for
     * @return $this Returns $this for method chaining 
     */
    public function setDomain($uri) {
        $this->getMarkup($uri);
        $this->getLinks(1);
        $this->domain = $uri;
        return $this;
    }
    
    /**
     * Returns the current URL that the sitemap is being created for
     * @return string This will be the URL that the sitemap is being created for
     */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * Set where the files will be created
     * @param string $path Set the absolute path where you want the sitemap files to be created
     * @return $this
     */
    public function setFilePath($path) {
        if(is_string($path) && is_dir($path)){
            $this->filepath = $path;
        }
        return $this;
    }
    
    /**
     * Gets the absolute path where files will be created
     * @return string This will be the absolute path where files are created
     */
    public function getFilePath() {
        return $this->filepath;
    }
    
    /**
     * Add a string or array of strings to ignore any URL containing the added item(s)
     * @param straing|array $ignore The item or array of items that you want to ignore any URL containing
     * @return $this
     */
    public function addURLItemstoIgnore($ignore) {
        if(is_array($ignore)) {
            $this->ignoreURLContaining = array_unique(array_push($this->ignoreURLContaining, $ignore));
        }
        elseif(is_string($ignore)){
            $this->ignoreURLContaining = array_unique(array_push($this->ignoreURLContaining, [$ignore]));
        }
        return $this;
    }
    
    /**
     * Returns an array of the strings to ignore in the links
     * @return array Returns an array of items to ignore link containing the values
     */
    public function getURLItemsToIgnore(){
        return $this->ignoreURLContaining;
    }
    
    /**
     * Parses each page of the website up to the given number of levels 
     * @param int $maxlevels The maximum number of levels from the homepage that should be crawled fro the website
     * @return array And array is return with all of the site pages and information
     */
    protected function parseSite($maxlevels = 5) {
        $level = 2;
        for ($i = 1; $i <= $maxlevels; $i++) {
            foreach ($this->links as $link => $info) {
                if ($info['visited'] == 0) {
                    $this->getMarkup($link);
                    $this->getLinks(($info['level'] + 1));
                }
                $level++;
            }
        }
        return $this->links;
    }
    
    /**
     * Gets the markup and headers for the given URL
     * @param string $uri This should be the page URL you wish to crawl and get the headers and page information
     * @return void
     */
    private function getMarkup($uri) {
        $this->url = $uri;
        $this->host = parse_url($this->url);
        $this->links[$uri]['visited'] = 1;
        
        $responce = $this->guzzle->request('GET', $uri, ['http_errors' => false, 'track_redirects' => true]);
        $this->markup = $responce->getBody();
        if ($responce->getStatusCode() === 200) {
            $html = HtmlDomParser::str_get_html($this->markup);
            $this->links[$uri]['markup'] = $html;
            $this->links[$uri]['images'] = $this->getImages($html);
        }
        else {$this->links[$uri]['error'] = $responce->getStatusCode(); }
    }
    
    /**
     * Get all of the images within the HTML
     * @param string $htmlInfo This should be the HTML you wish to get the images from
     * @return array|boolean If the page has images which are not previously included in the sitemap an array will be return else returns false
     */
    protected function getImages($htmlInfo) {
        return $this->getAssets($htmlInfo);
    }
    
    /**
     * Get all of the videos which are in the HTML
     * @param string $htmlInfo This should be the HTML you wish to get the videos from
     * @return array|boolean If the page has videos which are not previously included in the sitemap an array will be return else returns false
     */
    protected function getVideos($htmlInfo) {
        return $this->getAssets($htmlInfo, 'video', 'videos');
    }
    
    /**
     * Get all of the assets based on the given variables from within the HTML
     * @param string $htmlInfo This should be the HTML you wish to get the assets from
     * @param string $tag This should be the tag you wish to search for in the HTML
     * @param string $global This should be the name of the variable where the assets are stores to see if the assets already exists 
     * @return array|boolean If the page has assets which are not previously included in the sitemap an array will be return else returns false
     */
    protected function getAssets($htmlInfo, $tag = 'img', $global = 'images') {
        $item = array();
        $html = HtmlDomParser::str_get_html($htmlInfo);
        foreach ($html->find($tag) as $i => $assets) {
            $linkInfo = parse_url($assets->src);
            $fullLink = $this->buildLink($linkInfo, $assets->src);
            if (!empty($fullLink) && !$this->$global[$fullLink]) {
                $this->$global[$fullLink] = $fullLink;
                $item[$i]['src'] = $fullLink;
                $item[$i]['alt'] = $assets->alt;
                $i++;
            }
        }
        return isset($item[0]['src']) ? $item : false;
    }
    
    /**
     * Build the full link for use in the sitemap
     * @param array|false $linkInfo This should be the information retrieved about the asset
     * @param string $src This should be the source of the asset
     * @return string This should be the full link URL for use in the sitemap
     */
    protected function buildLink($linkInfo, $src) {
        $fullLink = ''; 
        if (!isset($linkInfo['scheme']) || $this->host['host'] == $linkInfo['host']) {
            if (!isset($linkInfo['scheme'])) {$fullLink .= $this->host['scheme'].'://'; }
            if (!isset($linkInfo['host'])) {$fullLink .= $this->host['host']; }
            $fullLink .= $src;
        }
        return $fullLink;
    }

    /**
     * This get all of the links for the current page and checks is they have already been added to the link list or not before adding and crawling
     * @param int $level This should be the maximum number of levels to crawl for the website
     */
    protected function getLinks($level = 1) {
        if (!empty($this->markup)) {
            $html = HtmlDomParser::str_get_html($this->markup);
            foreach (array_unique($html->find('a')) as $link) {
                $linkInfo = parse_url($link->href);
                if ($link->rel !== 'nofollow' && is_array($linkInfo)) {
                    $this->addLinktoArray($linkInfo, $link->href, $level);
                }
            }
        }
    }
    
    /**
     * Adds the link to the attribute array
     * @param array $linkInfo This should be the link information array
     */
    protected function addLinktoArray($linkInfo, $link, $level = 1){
        if ((!isset($linkInfo['scheme']) || $this->host['host'] == $linkInfo['host']) && !isset($linkInfo['username']) && !isset($linkInfo['password']) && !$this->checkForIgnoredStrings($link)) {
            $linkExt = explode('.', $linkInfo['path']);
            $pass = true;
            if(isset($linkExt[1])){
                $pass = (in_array(strtolower($linkExt[1]), array('jpg', 'jpeg', 'gif', 'png')) ? false : true);
            }
            if ($pass === true) {
                $this->addLink($linkInfo, $link, $level);
            }
        }
    }
    
    /**
     * Returns the full link path
     * @param array $linkInfo This should be all of the link information
     * @param string $path This should be the link path
     * @return string The full URI will be returned
     */
    protected function linkPath($linkInfo, $path){
        $fullLink = '';
        if (!isset($linkInfo['scheme'])) {$fullLink .= $this->host['scheme'].'://'; }
        if (!isset($linkInfo['host'])) {$fullLink .= $this->host['host']; }
        
        if (!$linkInfo['path'] && $linkInfo['query']) {return $fullLink.$this->host['path'].$path; }
        elseif ($linkInfo['path'][0] != '/' && !$linkInfo['query']) {return $fullLink.'/'.$path; }
        return $fullLink.$path;
    }
    
    /**
     * Add the link to the attribute array
     * @param array $linkInfo This should be all of the link information
     * @param string $link This should be the link path
     * @param int $level This should be the link level
     */
    protected function addLink($linkInfo, $link, $level = 1){
        $fragment = (isset($linkInfo['fragment']) ? '#'.$linkInfo['fragment'] : '');
        if (str_replace($fragment, '', $link) !== '/') {
            $EndLink = str_replace($fragment, '', $this->linkPath($linkInfo, $link));
            if (!isset($this->links[$EndLink]) || ($this->links[$EndLink]['visited'] == 0 && $this->url == $EndLink)) {
                $this->links[$EndLink] = array(
                    'level' => ($level > 5 ? 5 : $level),
                    'visited' => ($this->url == $EndLink ? 1 : isset($this->links[$EndLink]) ? ($this->links[$EndLink]['visited'] == 1 ? 1 : 0) : 0)
                );
            }
        }
    }

    /**
     * Creates the formatted string for the sitemap with the correct information in
     * @param string $url The full URL of the page
     * @param double $priority The priority to give the page on the website
     * @param string $freq The frequency the page changes on the website
     * @param string $modified The last modified time of the page
     * @param string $additional Any additional information to add to the sitemap on that page of the website such as images or videos
     * @return string Returns the sitemap information as a formatted string
     */
    private function urlXML($url, $priority = '0.8', $freq = 'monthly', $modified = '', $additional = '') {
        return '<url>
<loc>'.$url.'</loc>
<lastmod>'.(empty($modified) ? date('c') : $modified).'</lastmod>
<changefreq>'.$freq.'</changefreq>
<priority>'.$priority.'</priority>'.$additional.'
</url>
';
    }
    
    /**
     * Creates the image XML string information to add to the sitemap for the website
     * @param string $src The full source of the image including the domain
     * @param string $caption The caption to give the image in the sitemap
     * @return string Return the formatted string for the image section of the sitemap
     */
    private function imageXML($src, $caption) {
        return '<image:image>
<image:loc>'.$src.'</image:loc>
<image:caption>'.htmlentities($caption).'</image:caption>
</image:image>';
    }
    
    /**
     * Return the XML sitemap video section formatted string
     * @param string $location The location of the video
     * @param string $title The title of the video
     * @param string $description A short description of the video
     * @param string $thumbnailLoc The image thumbnail you want to use for the video
     * @param int $duration The duration of the video (seconds I think)
     * @param string $friendly Is it a family friendly video yes/no
     * @param string $live Is it a live stream yes/no
     * @return string Returns the video sitemap formatted string
     */
    private function videoXML($location, $title, $description, $thumbnailLoc, $duration = '', $friendly = 'yes', $live = 'no') {
        return '<video:video>
<video:thumbnail_loc>'.$thumbnailLoc.'</video:thumbnail_loc>
<video:title>'.$title.'</video:title>
<video:description>'.$description.'</video:description>
<video:content_loc>'.$location.'</video:content_loc>
<video:duration>'.$duration.'</video:duration>
<video:family_friendly>'.$friendly.'</video:family_friendly>
<video:live>'.$live.'</video:live>
</video:video>';
    }
    
    /**
     * Create a XML sitemap using the URL given during construct and crawls the rest of the websites
     * @param boolean $includeStyle If you want the XML Style to also be created set this as true else set as false
     * @param int $maxLevels The maximum number of levels to crawl from the homepage
     * @param string $filename If you want to set the filename to be something other than sitemap set this value here
     * @return boolean Returns true if successful else returns false on failure
     */
    public function createSitemap($includeStyle = true, $maxLevels = 5, $filename = 'sitemap') {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>'.($includeStyle === true ? '<?xml-stylesheet type="text/xsl" href="style.xsl"?>' : '').'<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($this->parseSite($maxLevels) as $url => $info) {            
            $images = '';
            if (!empty($info['images'])) {
                foreach ($info['images'] as $imgInfo) {
                    $images .= $this->imageXML($imgInfo['src'], $imgInfo['alt']);
                }
            }
            
            $videos = '';
            if (!empty($info['videos'])) {
                foreach ($info['videos'] as $vidInfo) {
                    $videos .= $this->videoXML($vidInfo['src'], $vidInfo['title'], $vidInfo['description'], $vidInfo['thumbnail']);
                }
            }
            $sitemap .= $this->urlXML($url, (isset($info['level']) ? $this->priority[$info['level']] : 1), (isset($info['level']) ? $this->frequency[$info['level']] : 'weekly'), date('c'), $images.$videos);
        }
        $sitemap .= '</urlset>';
        if($includeStyle === true) {$this->copyXMLStyle();}
        return file_put_contents($this->getFilePath().strtolower($filename).'.xml', $sitemap) !== false ? true : false;
    }
    
    /**
     * Copy the XSL stylesheet so that it is local to the sitemap 
     * @return boolean If the style is successfully created will return true else returns false
     */
    protected function copyXMLStyle() {
        $style = file_get_contents(realpath(dirname(__FILE__)).'/style.xsl');
        return file_put_contents($this->getFilePath().'/style.xsl', $style) !== false ? true : false;
    }
    
    /**
     * Checks to see if the link contains any of the values set to be ignored
     * @param string $link This should be the link you are checking for ignored strings
     * @return boolean If contains blocked elements returns true else returns false
     */
    protected function checkForIgnoredStrings($link){
        if(is_array($this->getURLItemsToIgnore()) && !empty($this->getURLItemsToIgnore())) {
            foreach($this->getURLItemsToIgnore() as $string){
                 if(strpos($link, $string) !== false){return true;}
            }
        }
        return true;
    }
}
