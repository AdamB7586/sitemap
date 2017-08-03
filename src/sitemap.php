<?php
use Sunra\PhpSimple\HtmlDomParser;
use GuzzleHttp\Client;

class Sitemap{
    protected static $guzzle;
    
    public $url;
    public $host;
    public $domain;
    public $links;
    public $images;
    
    public $markup = '';
    
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
        $pageInfo = curl_getinfo($ch);
        
        if($responce->getStatusCode() !== 200){$this->links[$uri]['error'] = $pageInfo;}
        else{
            $html = HtmlDomParser::str_get_html($this->markup);
            if($html){
                $this->content = $html->find('div[id=content]', 0)->innertext;
		if(!$this->content){$this->content = $html->find('div[id=main]', 0)->innertext;}
                if($this->content){
                    $this->links[$uri]['markup'] = $this->content;
                    $this->links[$uri]['images'] = $this->getImages($this->content);
                }
            }
        }
    }
    
    /**
     * Get all of the images within the main content section of the website
     * @param string $html This should be the HTML you wish to get the images
     * @return array|boolean If the page has images which are not previously included in the sitemap an array will be return else returns false
     */
    private function getImages($html){
        if(!empty($html)){
            $i = 0;
            $html = HtmlDomParser::str_get_html($html);
            foreach($html->find('img') as $images){
                $linkInfo = parse_url($images->src);
                if(!$linkInfo['scheme'] || $this->host['host'] == $linkInfo['host']){
                    $fullLink = '';
                    if(!$linkInfo['path'] && $linkInfo['query']){$link = $this->host['path'].$images->src;}
                    elseif($linkInfo['path'][0] != '/' && !$linkInfo['query']){$link = '/'.$images->src;}
                    
                    if(!$linkInfo['scheme']){$fullLink.= $this->host['scheme'].'://';}
                    if(!$linkInfo['host']){$fullLink.= $this->host['host'];}
                    $fullLink.= $images->src;
                    if(!$this->images[$fullLink]){
                        $this->images[$fullLink] = $fullLink;
                        $img[$i]['src'] = $fullLink;
                        $img[$i]['alt'] = $images->alt;
                        $i++;
                    }
                }
            }
            return $img[0] ? $img : false;
        }
        return false;
    }
    
    /**
     * Get all of the video which are in the main content section of the website
     * @param string $html This should be the HTML you wish to get the images
     * @return boolean False is returned currently
     */
    private function getVideos($html){
        if(!empty($html)){
            /*$i = 0;
            $html = HtmlDomParser::str_get_html($html);
            foreach($html->find('img') as $images){
                $linkInfo = parse_url($images->src);
                if(!$linkInfo['scheme'] || $this->host['host'] == $linkInfo['host']){
                    $fullLink = '';
                    if(!$linkInfo['path'] && $linkInfo['query']){$link = $this->host['path'].$images->src;}
                    elseif($linkInfo['path'][0] != '/' && !$linkInfo['query']){$link = '/'.$images->src;}
                    
                    if(!$linkInfo['scheme']){$fullLink.= $this->host['scheme'].'://';}
                    if(!$linkInfo['host']){$fullLink.= $this->host['host'];}
                    $fullLink.= $images->src;
                    if(!$this->images[$fullLink]){
                        $this->images[$fullLink] = $fullLink;
                        $img[$i]['src'] = $fullLink;
                        $img[$i]['alt'] = $images->alt;
                        $i++;
                    }
                }
            }
            return $img[0] ? $img : false;*/      
        }
        return false;
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
                                    $this->links[$EndLink]['level'] = $level;
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
     * @return string Return the formatted string for the imgae section of the sitemap
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
     * @param string $thumbnailLoc The image thumbnail yo want to use for the video
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
            if($info['level'] == 0 || !$info['level']){$priority = '1'; $freq = 'weekly';}
            elseif($info['level'] == 1){$priority = '0.8'; $freq = 'weekly';}
            elseif($info['level'] == 2){$priority = '0.6'; $freq = 'monthly';}
            elseif($info['level'] == 3){$priority = '0.4'; $freq = 'monthly';}
            elseif($info['level'] == 4){$priority = '0.2'; $freq = 'monthly';}
            elseif($info['level'] == 5){$priority = '0.1'; $freq = 'monthly';}
            else{$priority = '0.1'; $freq = 'yearly';}
            
            $images = '';
            if(!empty($info['images'])){
                foreach($info['images'] as $imgID => $imgInfo){
                    $images.= $this->imageXML($imgInfo['src'], $imgInfo['alt']);
                }
            }
            
            $videos = '';
            if(!empty($info['videos'])){
                foreach($info['videos'] as $vidID => $vidInfo){
                    $videos.= $this->videoXML($vidInfo['src'], $vidInfo['title'], $vidInfo['description'], $vidInfo['thumbnail']);
                }
            }
            $sitemap.= $this->urlXML($url, $priority, $freq, date('c'), $images.$videos);
        }
        $sitemap.= '</urlset>';
        return $sitemap;
    }
}