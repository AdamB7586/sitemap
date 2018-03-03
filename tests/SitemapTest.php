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
     */
    public function testSetDomain() {
        $this->assertObjectHasAttribute('url', $this->sitemap->setDomain('https://www.google.co.uk'));
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
