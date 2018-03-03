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
     * @covers Sitemap\Sitemap::getMarkup
     * @covers Sitemap\Sitemap::getImages
     * @covers Sitemap\Sitemap::getLinks
     * @covers Sitemap\Sitemap::addLinktoArray
     * @covers Sitemap\Sitemap::getAssets
     * @covers Sitemap\Sitemap::setFilePath
     */
    public function testSetDomain() {
        $this->assertObjectHasAttribute('url', $this->sitemap->setDomain('http://www.example.com/'));
    }
    
    public function testGetDomain() {
        $this->markTestIncomplete();
    }
    
    public function testSetFilePath() {
        $this->markTestIncomplete();
    }
    
    public function testGetFilePath() {
        $this->markTestIncomplete();
    }
    
    public function testCreateSitemap() {
        $this->markTestIncomplete();
    }
}
