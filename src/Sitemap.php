<?php

namespace Sitemap;

use Sunra\PhpSimple\HtmlDomParser;
use GuzzleHttp\Client;

class Sitemap{
    protected static $guzzle;
    
    public $url;
    public $host;
    public $domain;
    public $links;
    public $images;
    public $videos;
    
    public $markup = '';
    public $contentID = 'content';
    
    protected $priority = array(0 => '1', 1 => '0.8', 2 => '0.6', 3 => '0.4', 4 => '0.2', 5 => '0.1');
    protected $frequency = array(0 => 'weekly', 1 => 'weekly', 2 => 'monthly', 3 => 'monthly', 4 => 'monthly', 5 => 'yearly');
    
    /**
     * Crawl the homepage and get all of the links for that page
     * @param string $uri This should be the website homepage that you wish to crawl for the sitemap
     */
    public function __construct($uri){
        self::$guzzle = new Client();
        $this->getMarkup($uri);
        $this->getLinks(1);
        $this->domain = $uri;
    }
    
    /**
     * Parses each page of the website up to the given number of levels 
     * @param int $maxlevels The maximum number of levels from the homepage that should be crawled fro the website
     * @return array And array is return with all of the site pages and information
     */
    public function parseSite($maxlevels = 3){
        $level = 2;
        for($i = 1; $i <= $maxlevels; $i++){
            foreach($this->links as $link => $info){
                if($info['visited'] == 0){
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
    private function getMarkup($uri){
        $this->url = $uri;
        $this->host = parse_url($this->url);
        $this->links[$uri]['visited'] = 1;
        
        $responce = self::$guzzle->request('GET', $uri);
        $this->markup = $responce->getBody();
        if($responce->getStatusCode() === 200){
            $html = HtmlDomParser::str_get_html($this->markup);
            $this->links[$uri]['markup'] = $html;
            $this->links[$uri]['images'] = $this->getImages($html);
        }
        else{$this->links[$uri]['error'] = $responce->getStatusCode();}
    }
    
    /**
     * Get all of the images within the HTML
     * @param string $htmlInfo This should be the HTML you wish to get the images from
     * @return array|boolean If the page has images which are not previously included in the sitemap an array will be return else returns false
     */
    protected function getImages($htmlInfo){
        return $this->getAssets($htmlInfo);
    }
    
    /**
     * Get all of the videos which are in the HTML
     * @param string $htmlInfo This should be the HTML you wish to get the videos from
     * @return array|boolean If the page has videos which are not previously included in the sitemap an array will be return else returns false
     */
    protected function getVideos($htmlInfo){
        return $this->getAssets($htmlInfo, 'video', 'videos');
    }
    
    /**
     * Get all of the assets based on the given variables from within the HTML
     * @param string $htmlInfo This should be the HTML you wish to get the assets from
     * @param string $tag This should be the tag you wish to search for in the HTML
     * @param string $global This should be the name of the variable where the assets are stores to see if the assets already exists 
     * @return array|boolean If the page has assets which are not previously included in the sitemap an array will be return else returns false
     */
    protected function getAssets($htmlInfo, $tag = 'img', $global = 'images'){
        $item = array();
        $html = HtmlDomParser::str_get_html($htmlInfo);
        foreach($html->find($tag) as $i => $assets){
            $linkInfo = parse_url($assets->src);
            $fullLink = $this->buildLink($linkInfo, $assets->src);
            if(!empty($fullLink) && !$this->$global[$fullLink]){
                $this->$global[$fullLink] = $fullLink;
                $item[$i]['src'] = $fullLink;
                $item[$i]['alt'] = $assets->alt;
                $i++;
            }
        }
        return $item[0]['src'] ? $item : false;
    }
    
    /**
     * Build the full link for use in the sitemap
     * @param array $linkInfo This should be the information retrieved about the asset
     * @param string $src This should be the source of the asset
     * @return string This should be the full link URL for use in the sitemap
     */
    protected function buildLink($linkInfo, $src){
        $fullLink = ''; 
        if(!$linkInfo['scheme'] || $this->host['host'] == $linkInfo['host']){
            if(!$linkInfo['scheme']){$fullLink.= $this->host['scheme'].'://';}
            if(!$linkInfo['host']){$fullLink.= $this->host['host'];}
            $fullLink.= $src;
        }
        return $fullLink;
    }

    /**
     * This get all of the links for the current page and checks is they have already been added to the link list or not before adding and crawling
     * @param int $level This should be the maximum number of levels to crawl for the website
     * @return void
     */
    private function getLinks($level = 1){
        if(!empty($this->markup)){
            $html = HtmlDomParser::str_get_html($this->markup);
            foreach(array_unique($html->find('a')) as $link){
                if($link->rel !== 'nofollow'){
                    $link = $link->href;
                    $linkInfo = parse_url($link);
                    if((!$linkInfo['scheme'] || $this->host['host'] == $linkInfo['host']) && !$linkInfo['username'] && !$linkInfo['password']){
                        $linkExt = explode('.', $linkInfo['path']);
                        if(!in_array(strtolower($linkExt[1]), array('jpg', 'jpeg', 'gif', 'png'))){
                            $fullLink = '';
                            if(!$linkInfo['path'] && $linkInfo['query']){$link = $this->host['path'].$link;}
                            elseif($linkInfo['path'][0] != '/' && !$linkInfo['query']){$link = '/'.$link;}

                            if(!$linkInfo['scheme']){$fullLink.= $this->host['scheme'].'://';}
                            if(!$linkInfo['host']){$fullLink.= $this->host['host'];}
                            if(str_replace('#'.$linkInfo['fragment'], '', $link) !== '/'){
                                $fullLink.= $link;
                                $EndLink = str_replace('#'.$linkInfo['fragment'], '', $fullLink);
                                if(!$this->links[$EndLink] || ($this->links[$EndLink]['visited'] == 0 && $this->url == $EndLink)){
                                    if($this->url == $EndLink || $this->links[$EndLink]['visited'] == 1){$num = 1;}else{$num = 0;}
                                    $this->links[$EndLink]['level'] = ($level > 5 ? 5 : $level);
                                    $this->links[$EndLink]['visited'] = $num;
                                }
                            }
                        }
                    }
                }
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
    private function urlXML($url, $priority = '0.8', $freq = 'monthly', $modified = '', $additional = ''){
        if(empty($modified)){$modified = date('c');}
        return '<url>
<loc>'.$url.'</loc>
<lastmod>'.date('c').'</lastmod>
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
    private function imageXML($src, $caption){
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
    private function videoXML($location, $title, $description, $thumbnailLoc, $duration = '', $friendly = 'yes', $live = 'no'){
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
     * @param int $maxLevels The maximum number of levels to crawl from the homepage
     * @return string Returns the XML sitemap string
     */
    public function createSitemap($maxLevels = 3, $styleURL = 'style.xsl'){
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="'.$styleURL.'"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach($this->parseSite($maxLevels) as $url => $info){            
            $images = '';
            if(!empty($info['images'])){
                foreach($info['images'] as $imgInfo){
                    $images.= $this->imageXML($imgInfo['src'], $imgInfo['alt']);
                }
            }
            
            $videos = '';
            if(!empty($info['videos'])){
                foreach($info['videos'] as $vidInfo){
                    $videos.= $this->videoXML($vidInfo['src'], $vidInfo['title'], $vidInfo['description'], $vidInfo['thumbnail']);
                }
            }
            $sitemap.= $this->urlXML($url, $this->priority[$info['level']], $this->frequency[$info['level']], date('c'), $images.$videos);
        }
        $sitemap.= '</urlset>';
        return $sitemap;
    }
}
