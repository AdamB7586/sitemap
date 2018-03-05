<?php

namespace Sitemap\Tests;

use PHPUnit\Framework\TestCase;
use Sitemap\Sitemap;

class SitemapTest extends TestCase{
    public $sitemap;
    
    protected function setUp() {
        $this->sitemap = new Sitemap();
    }
    
    protected function tearDown() {
        $this->sitemap = null;
    }
    
    /**
     * @covers Sitemap\Sitemap::__construct
     * @covers Sitemap\Sitemap::setDomain
     * @covers Sitemap\Sitemap::getDomain
     * @covers Sitemap\Sitemap::getMarkup
     * @covers Sitemap\Sitemap::getImages
     * @covers Sitemap\Sitemap::getLinks
     * @covers Sitemap\Sitemap::addLinktoArray
     * @covers Sitemap\Sitemap::getAssets
     * @covers Sitemap\Sitemap::setFilePath
     * @covers Sitemap\Sitemap::buildLink 
     */
    public function testSetDomain() {
        $this->assertObjectHasAttribute('url', $this->sitemap->setDomain('https://www.google.co.uk/'));
        $this->assertEquals('https://www.google.co.uk/', $this->sitemap->getDomain());
        $this->assertObjectHasAttribute('url', $this->sitemap->setDomain('http://www.example.com/'));
        $this->assertEquals('http://www.example.com/', $this->sitemap->getDomain());
    }
    
    /**
     * @covers Sitemap\Sitemap::__construct
     * @covers Sitemap\Sitemap::setFilePath
     * @covers Sitemap\Sitemap::getFilePath
     */
    public function testSetFilePath() {
        $this->assertObjectHasAttribute('url', $this->sitemap->setFilePath(dirname(__FILE__)));
        $this->assertEquals(dirname(__FILE__), $this->sitemap->getFilePath());
        $this->assertObjectHasAttribute('url', $this->sitemap->setFilePath(158774));
        $this->assertEquals(dirname(__FILE__), $this->sitemap->getFilePath());
    }
    
    /**
     * @covers Sitemap\Sitemap::__construct
     * @covers Sitemap\Sitemap::createSitemap
     * @covers Sitemap\Sitemap::setDomain
     * @covers Sitemap\Sitemap::getMarkup
     * @covers Sitemap\Sitemap::getImages
     * @covers Sitemap\Sitemap::getLinks
     * @covers Sitemap\Sitemap::addLinktoArray
     * @covers Sitemap\Sitemap::getAssets
     * @covers Sitemap\Sitemap::setFilePath
     * @covers Sitemap\Sitemap::buildLink 
     * @covers Sitemap\Sitemap::parseSite
     * @covers Sitemap\Sitemap::imageXML
     * @covers Sitemap\Sitemap::videoXML
     * @covers Sitemap\Sitemap::urlXML
     * @covers Sitemap\Sitemap::copyXMLStyle
     * @covers Sitemap\Sitemap::getFilePath
     */
    public function testCreateSitemap() {
        $this->sitemap->setDomain('http://www.example.com/')->setFilePath(dirname(__FILE__).'/');
        $this->assertTrue($this->sitemap->createSitemap(true, 1));
        $this->assertContains('<loc>http://www.example.com/</loc>', file_get_contents(dirname(__FILE__).'/sitemap.xml'));
    }
}
