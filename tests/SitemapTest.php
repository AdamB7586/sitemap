<?php

namespace Sitemap\Tests;

use PHPUnit\Framework\TestCase;
use Sitemap\Sitemap;

class SitemapTest extends TestCase
{
    public $sitemap;
    
    protected function setUp(): void
    {
        $this->sitemap = new Sitemap();
    }
    
    protected function tearDown(): void
    {
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
     * @covers Sitemap\Sitemap::addLink
     * @covers Sitemap\Sitemap::linkPath
     * @covers Sitemap\Sitemap::setXMLLayoutPath
     */
    public function testSetDomain()
    {
        $this->assertObjectHasAttribute('url', $this->sitemap->setDomain('https://www.google.co.uk/'));
        $this->assertEquals('https://www.google.co.uk/', $this->sitemap->getDomain());
        $this->assertObjectHasAttribute('url', $this->sitemap->setDomain('http://www.example.com/'));
        $this->assertEquals('http://www.example.com/', $this->sitemap->getDomain());
    }
    
    /**
     * @covers Sitemap\Sitemap::__construct
     * @covers Sitemap\Sitemap::setFilePath
     * @covers Sitemap\Sitemap::getFilePath
     * @covers Sitemap\Sitemap::setXMLLayoutPath
     */
    public function testSetFilePath()
    {
        $this->assertObjectHasAttribute('url', $this->sitemap->setFilePath(dirname(__FILE__)));
        $this->assertEquals(dirname(__FILE__), $this->sitemap->getFilePath());
        $this->assertObjectHasAttribute('url', $this->sitemap->setFilePath(158774));
        $this->assertEquals(dirname(__FILE__), $this->sitemap->getFilePath());
    }
    
    /**
     * @covers Sitemap\Sitemap::__construct
     * @covers Sitemap\Sitemap::createSitemap
     * @covers Sitemap\Sitemap::getDomain
     * @covers Sitemap\Sitemap::setDomain
     * @covers Sitemap\Sitemap::getMarkup
     * @covers Sitemap\Sitemap::getImages
     * @covers Sitemap\Sitemap::getLinks
     * @covers Sitemap\Sitemap::addLinktoArray
     * @covers Sitemap\Sitemap::getAssets
     * @covers Sitemap\Sitemap::setFilePath
     * @covers Sitemap\Sitemap::buildLink
     * @covers Sitemap\Sitemap::addLink
     * @covers Sitemap\Sitemap::linkPath
     * @covers Sitemap\Sitemap::parseSite
     * @covers Sitemap\Sitemap::imageXML
     * @covers Sitemap\Sitemap::videoXML
     * @covers Sitemap\Sitemap::urlXML
     * @covers Sitemap\Sitemap::copyXMLStyle
     * @covers Sitemap\Sitemap::getFilePath
     * @covers Sitemap\Sitemap::getLayoutFile
     * @covers Sitemap\Sitemap::getXMLLayoutPath
     * @covers Sitemap\Sitemap::setXMLLayoutPath
     * @covers Sitemap\Sitemap::checkForIgnoredStrings
     * @covers Sitemap\Sitemap::getURLItemsToIgnore
     * @covers Sitemap\Sitemap::addURLItemstoIgnore
     */
    public function testCreateSitemap()
    {
        $this->sitemap->setDomain('https://www.netflix.com/')->setFilePath(dirname(__FILE__).'/');
        $this->assertTrue($this->sitemap->createSitemap(true, 1));
        $this->assertStringContainsString('<loc>https://www.netflix.com/</loc>', file_get_contents(dirname(__FILE__).'/sitemap.xml'));
        
        //Test with setting domain in contructor
        $newSitemap = new Sitemap('https://www.adambinnersley.co.uk/');
        $newSitemap->setFilePath(dirname(__FILE__).'/')->addURLItemstoIgnore('about-me');
        $this->assertTrue($newSitemap->createSitemap(false));
        $this->assertStringContainsString('<loc>https://www.adambinnersley.co.uk/</loc>', file_get_contents(dirname(__FILE__).'/sitemap.xml'));
        $this->assertStringNotContainsString('about-me', file_get_contents(dirname(__FILE__).'/sitemap.xml'));
    }
}
