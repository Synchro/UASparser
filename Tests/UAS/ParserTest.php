<?php

/**
 * UASParser PHPUnit tests
 * @author Marcus Bointon https://github.com/Synchro
 */

namespace UAS;

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * An instance of the UAS parser to test.
     * @type Parser
     */
    protected static $uasparser;
    protected static $cachePath;

    public function setUp(): void
    {
        self::$cachePath = sys_get_temp_dir() . '/uascache/';
        self::$uasparser = new Parser(
            self::$cachePath,
            86400,
            true
        ); //Create a UASParser instance with debug output enabled
        self::$uasparser->timeout = 600; //Be pessimistic, site can be very slow
    }

    public static function tearDownAfterClass(): void
    {
        self::$uasparser->clearCache();
        @unlink(self::$cachePath);
    }

    public function resetURLs(): void
    {
        self::$uasparser->setIniUrl('http://user-agent-string.info/rpc/get_data.php?key=free&format=ini');
        self::$uasparser->setVerUrl('http://user-agent-string.info/rpc/get_data.php?key=free&format=ini&ver=y');
        self::$uasparser->setMd5Url('http://user-agent-string.info/rpc/get_data.php?format=ini&md5=y');
    }

    /**
     * Check setting the download dir to various values.
     */
    public function testSetPath(): void
    {
        self::assertTrue(self::$uasparser->setCacheDir(self::$cachePath));
        //Test non-writable path
        self::assertFalse(self::$uasparser->setCacheDir('/var'));
        //Test non-existent path
        self::assertFalse(self::$uasparser->setCacheDir('/jksdhfkjhsldkfhklkh/' . md5(microtime(true))));
    }

    /**
     * Check that the download dir is the same as we set it to
     */
    public function testPath(): void
    {
        self::assertEquals(self::$uasparser->getCacheDir(), realpath(self::$cachePath));
    }

    public function testExpires(): void
    {
        self::$uasparser->updateInterval = 99999;
        self::assertEquals(99999, self::$uasparser->updateInterval);
    }

    public function testUpdateDatabase(): void
    {
        self::markTestIncomplete(
            'The free UAS database download is no longer available.'
        );
        self::assertTrue(self::$uasparser->downloadData()); //Should cause a download
        self::assertTrue(self::$uasparser->downloadData(true)); //Should also cause a download
        self::assertTrue(self::$uasparser->downloadData()); //Should NOT cause a download
    }

    public function testDownloadErrors(): void
    {
        self::$uasparser->setIniUrl('https://github.com/Synchro/UASparser/raw/master/Tests/empty.ini');
        self::assertFalse(self::$uasparser->downloadData(true), 'Empty file considered good');
        self::$uasparser->setIniUrl('https://github.com/Synchro/UASparser/raw/master/Tests/bad.ini');
        self::$uasparser->setVerUrl('https://github.com/Synchro/UASparser/raw/master/Tests/bad.ver');
        self::assertFalse(self::$uasparser->downloadData(true), 'Bad file considered good');
        $this->resetURLs();
    }

    public function testChecksums(): void
    {
        self::$uasparser->setMd5Url('https://github.com/Synchro/UASparser/raw/master/Tests/empty.ini');
        self::assertFalse(self::$uasparser->downloadData(true), 'Empty checksum considered good');
        self::$uasparser->setMd5Url('https://github.com/Synchro/UASparser/raw/master/Tests/bad.ini');
        self::assertFalse(self::$uasparser->downloadData(true), 'Bad checksum considered good');
        $this->resetURLs();
    }

    /**
     * @depends testUpdateDatabase
     */
    public function testPermissions(): void
    {
        $path = self::$uasparser->getCacheDir() . DIRECTORY_SEPARATOR . 'uasdata.ini';
        $perms = fileperms($path);
        chmod($path, 0444); //Set read-only
        self::$uasparser->setIniUrl('https://github.com/Synchro/UASparser/raw/master/Tests/bad.ini');
        self::$uasparser->setMd5Url('https://github.com/Synchro/UASparser/raw/master/Tests/bad.md5');
        $result = self::$uasparser->downloadData(true);
        chmod($path, $perms); //Reset perms
        self::assertFalse($result, 'Failed file write not detected');
        $this->resetURLs();
    }

    /**
     * Test control over downloads.
     */
    public function testDownloadControl(): void
    {
        self::$uasparser->setDoDownloads(false);
        self::assertFalse(self::$uasparser->getDoDownloads());
        self::$uasparser->setUseZipDownloads(true);
        //Inject an old timestamp into the cache file
        $cache = file_get_contents(self::$uasparser->getCacheDir() . '/cache.ini');
        $cache = preg_replace('/localversion = .*/', 'localversion = "20130529-01"', $cache, 1);
        $cache = preg_replace('/lastupdate = .*/', 'lastupdate = "1346146206"', $cache, 1);
        file_put_contents(self::$uasparser->getCacheDir() . '/cache.ini', $cache);
        unset($cache);
        //Should use old data
        self::$uasparser->parse();
        self::$uasparser->clearCache();
        self::$uasparser->clearData();
        //Should do a download even though downloads are disabled
        self::$uasparser->parse();
    }

    /**
     * Misc calls for coverage.
     */
    public function testCoverage(): void
    {
        self::assertNotEmpty(self::$uasparser->getIniUrl());
        self::assertNotEmpty(self::$uasparser->getVerUrl());
        self::assertNotEmpty(self::$uasparser->getMd5Url());
    }

    /**
     * Test getting the current user agent.
     * @depends testUpdateDatabase
     */
    public function testCurrent(): void
    {
        $uas = self::$uasparser->parse();
        self::assertIsArray($uas);
        self::assertArrayHasKey('typ', $uas);
        self::assertArrayHasKey('ua_family', $uas);
        self::assertArrayHasKey('ua_name', $uas);
        self::assertArrayHasKey('ua_version', $uas);
        self::assertArrayHasKey('ua_url', $uas);
        self::assertArrayHasKey('ua_company', $uas);
        self::assertArrayHasKey('ua_company_url', $uas);
        self::assertArrayHasKey('ua_icon', $uas);
        self::assertArrayHasKey('ua_info_url', $uas);
        self::assertArrayHasKey('os_family', $uas);
        self::assertArrayHasKey('os_name', $uas);
        self::assertArrayHasKey('os_url', $uas);
        self::assertArrayHasKey('os_company', $uas);
        self::assertArrayHasKey('os_company_url', $uas);
        self::assertArrayHasKey('os_icon', $uas);
    }

    /**
     * Test a specific, known user agent string.
     * @depends testUpdateDatabase
     */
    public function testSafari(): void
    {
        $uas = self::$uasparser->parse(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/536.26.17 (KHTML, like Gecko) ' .
            'Version/6.0.2 Safari/536.26.17'
        );
        self::assertIsArray($uas);
        self::assertEquals('Browser', $uas['typ']);
        self::assertEquals('Safari', $uas['ua_family']);
        self::assertEquals('Safari 6.0.2', $uas['ua_name']);
        self::assertEquals('6.0.2', $uas['ua_version']);
        self::assertEquals('http://en.wikipedia.org/wiki/Safari_%28web_browser%29', $uas['ua_url']);
        self::assertEquals('Apple Inc.', $uas['ua_company']);
        self::assertEquals('http://www.apple.com/', $uas['ua_company_url']);
        self::assertEquals('safari.png', $uas['ua_icon']);
        self::assertEquals(
            'http://user-agent-string.info/list-of-ua/browser-detail?browser=Safari',
            $uas['ua_info_url']
        );
        self::assertEquals('OS X', $uas['os_family']);
        self::assertEquals('OS X 10.8 Mountain Lion', $uas['os_name']);
        self::assertEquals('http://en.wikipedia.org/wiki/OS_X_Mountain_Lion', $uas['os_url']);
        self::assertEquals('Apple Computer, Inc.', $uas['os_company']);
        self::assertEquals('http://www.apple.com/', $uas['os_company_url']);
        self::assertEquals('macosx.png', $uas['os_icon']);
    }

    /**
     * Test getting the user agent from the global env.
     * @depends testUpdateDatabase
     */
    public function testEnv(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/536.26.17 ' .
            '(KHTML, like Gecko) Version/6.0.2 Safari/536.26.17';
        $uas = self::$uasparser->parse();
        self::assertIsArray($uas);
        self::assertEquals('Browser', $uas['typ']);
        self::assertEquals('Safari', $uas['ua_family']);
        self::assertEquals('Safari 6.0.2', $uas['ua_name']);
        self::assertEquals('6.0.2', $uas['ua_version']);
        self::assertEquals('http://en.wikipedia.org/wiki/Safari_%28web_browser%29', $uas['ua_url']);
        self::assertEquals('Apple Inc.', $uas['ua_company']);
        self::assertEquals('http://www.apple.com/', $uas['ua_company_url']);
        self::assertEquals('safari.png', $uas['ua_icon']);
        self::assertEquals(
            'http://user-agent-string.info/list-of-ua/browser-detail?browser=Safari',
            $uas['ua_info_url']
        );
        self::assertEquals('OS X', $uas['os_family']);
        self::assertEquals('OS X 10.8 Mountain Lion', $uas['os_name']);
        self::assertEquals('http://en.wikipedia.org/wiki/OS_X_Mountain_Lion', $uas['os_url']);
        self::assertEquals('Apple Computer, Inc.', $uas['os_company']);
        self::assertEquals('http://www.apple.com/', $uas['os_company_url']);
        self::assertEquals('macosx.png', $uas['os_icon']);
    }

    /**
     * Test a robot user agent.
     * @depends testUpdateDatabase
     */
    public function testRobot(): void
    {
        $uas = self::$uasparser->parse('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
        self::assertIsArray($uas);
        self::assertEquals('Robot', $uas['typ']);
        self::assertEquals('Googlebot', $uas['ua_family']);
        self::assertEquals('Googlebot/2.1', $uas['ua_name']);
        self::assertEquals('unknown', $uas['ua_version']);
        self::assertEquals('https://support.google.com/webmasters/answer/1061943?hl=en', $uas['ua_url']);
        self::assertEquals('Google Inc.', $uas['ua_company']);
        self::assertEquals('http://www.google.com/', $uas['ua_company_url']);
        self::assertEquals('bot_googlebot.png', $uas['ua_icon']);
        self::assertEquals('http://user-agent-string.info/list-of-ua/bot-detail?bot=Googlebot', $uas['ua_info_url']);
        self::assertEquals('unknown', $uas['os_family']);
        self::assertEquals('unknown', $uas['os_name']);
        self::assertEquals('unknown', $uas['os_url']);
        self::assertEquals('unknown', $uas['os_company']);
        self::assertEquals('unknown', $uas['os_company_url']);
        self::assertEquals('unknown.png', $uas['os_icon']);
    }

    /**
     * Test an user agent whose OS needs to be looked up.
     * @depends testUpdateDatabase
     */
    public function testUnknownOS(): void
    {
        $uas = self::$uasparser->parse('OmniWeb/2.7-beta-3 OWF/1.0');
        self::assertIsArray($uas);
        self::assertEquals('Browser', $uas['typ']);
        self::assertEquals('OmniWeb', $uas['ua_family']);
        self::assertEquals('OmniWeb 2.7-beta-3', $uas['ua_name']);
        self::assertEquals('2.7-beta-3', $uas['ua_version']);
        self::assertEquals('http://www.omnigroup.com/applications/omniweb/', $uas['ua_url']);
        self::assertEquals('Omni Development, Inc.', $uas['ua_company']);
        self::assertEquals('http://www.omnigroup.com/', $uas['ua_company_url']);
        self::assertEquals('omniweb.png', $uas['ua_icon']);
        self::assertEquals(
            'http://user-agent-string.info/list-of-ua/browser-detail?browser=OmniWeb',
            $uas['ua_info_url']
        );
        self::assertEquals('Mac OS', $uas['os_family']);
        self::assertEquals('Mac OS', $uas['os_name']);
        self::assertEquals('http://en.wikipedia.org/wiki/Mac_OS', $uas['os_url']);
        self::assertEquals('Apple Computer, Inc.', $uas['os_company']);
        self::assertEquals('http://www.apple.com/', $uas['os_company_url']);
        self::assertEquals('macos.png', $uas['os_icon']);
    }
}
