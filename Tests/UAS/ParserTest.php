<?php
/**
 * UASParser PHPUnit tests
 * @author Marcus Bointon https://github.com/Synchro
 */

class ParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * An instance of the UAS parser to test.
     * @type UAS\Parser
     */
    protected static $uasparser;
    protected static $cache_path;
    protected static $default_ini_url;
    protected static $default_ver_url;
    protected static $default_md5_url;

    public function setUp() {
        self::$cache_path = sys_get_temp_dir().'/uascache/';
        self::$uasparser = new UAS\Parser(self::$cache_path, 86400, true); //Create a UASParser instance with debug output enabled
        self::$uasparser->timeout = 600; //Be pessimistic, site can be very slow
    }

    public static function tearDownAfterClass() {
        //self::$uasparser->clearCache();
        //@unlink(self::$cache_path);
    }

    public function resetURLs() {
        self::$uasparser->setIniUrl('http://user-agent-string.info/rpc/get_data.php?key=free&format=ini');
        self::$uasparser->setVerUrl('http://user-agent-string.info/rpc/get_data.php?key=free&format=ini&ver=y');
        self::$uasparser->setMd5Url('http://user-agent-string.info/rpc/get_data.php?format=ini&md5=y');
    }

    /**
     * Check setting the download dir to various values.
     */
    public function testSetPath() {
        $this->assertTrue(self::$uasparser->setCacheDir(self::$cache_path));
        //Test non-writable path
        $this->assertFalse(self::$uasparser->setCacheDir('/var'));
        //Test non-existent path
        $this->assertFalse(self::$uasparser->setCacheDir('/jksdhfkjhsldkfhklkh/'.md5(microtime(true))));
    }

    /**
     * Check that the download dir is the same as we set it to
     */
    public function testPath() {
        $this->assertEquals(self::$uasparser->getCacheDir(), realpath(self::$cache_path));
    }

    public function testExpires() {
        self::$uasparser->updateInterval = 99999;
        $this->assertEquals(self::$uasparser->updateInterval, 99999);
    }

    public function testUpdateDatabase() {
        $this->assertTrue(self::$uasparser->downloadData()); //Should cause a download
        $this->assertTrue(self::$uasparser->downloadData(true)); //Should also cause a download
        $this->assertTrue(self::$uasparser->downloadData()); //Should NOT cause a download
    }

    public function testDownloadErrors() {
        self::$uasparser->setIniUrl('https://github.com/Synchro/UASparser/raw/master/Tests/empty.ini');
        $this->assertFalse(self::$uasparser->downloadData(true), 'Empty file considered good');
        self::$uasparser->setIniUrl('https://github.com/Synchro/UASparser/raw/master/Tests/bad.ini');
        self::$uasparser->setVerUrl('https://github.com/Synchro/UASparser/raw/master/Tests/bad.ver');
        $this->assertFalse(self::$uasparser->downloadData(true), 'Bad file considered good');
        $this->resetURLs();
    }

    public function testChecksums() {
        self::$uasparser->setMd5Url('https://github.com/Synchro/UASparser/raw/master/Tests/empty.ini');
        $this->assertFalse(self::$uasparser->downloadData(true), 'Empty checksum considered good');
        self::$uasparser->setMd5Url('https://github.com/Synchro/UASparser/raw/master/Tests/bad.ini');
        $this->assertFalse(self::$uasparser->downloadData(true), 'Bad checksum considered good');
        $this->resetURLs();
    }

    public function testPermissions() {
        $path = self::$uasparser->getCacheDir().DIRECTORY_SEPARATOR.'uasdata.ini';
        $perms = fileperms($path);
        chmod($path, 0444); //Set read-only
        self::$uasparser->setIniUrl('https://github.com/Synchro/UASparser/raw/master/Tests/bad.ini');
        self::$uasparser->setMd5Url('https://github.com/Synchro/UASparser/raw/master/Tests/bad.md5');
        $ok = self::$uasparser->downloadData(true);
        chmod($path, $perms); //Reset perms
        $this->assertFalse($ok, 'Failed file write not detected');
        $this->resetURLs();
    }

    /**
     * Test control over downloads.
     */
    public function testDownloadControl() {
        self::$uasparser->setDoDownloads(false);
        $this->assertFalse(self::$uasparser->getDoDownloads());
        self::$uasparser->setUseZipDownloads(true);
        //Inject an old timestamp into the cache file
        $cache = file_get_contents(self::$uasparser->getCacheDir().'/cache.ini');
        $cache = preg_replace('/localversion = .*/', 'localversion = "20130529-01"', $cache, 1);
        $cache = preg_replace('/lastupdate = .*/', 'lastupdate = "1346146206"', $cache, 1);
        file_put_contents(self::$uasparser->getCacheDir().'/cache.ini', $cache);
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
    public function testCoverage() {
        self::$uasparser->getIniUrl();
        self::$uasparser->getVerUrl();
        self::$uasparser->getMd5Url();
    }

    /**
     * Test getting the current user agent.
     * @depends testUpdateDatabase
     */
    public function testCurrent() {
        $u = self::$uasparser->parse();
        $this->assertTrue(is_array($u));
        $this->assertArrayHasKey('typ', $u);
        $this->assertArrayHasKey('ua_family', $u);
        $this->assertArrayHasKey('ua_name', $u);
        $this->assertArrayHasKey('ua_version', $u);
        $this->assertArrayHasKey('ua_url', $u);
        $this->assertArrayHasKey('ua_company', $u);
        $this->assertArrayHasKey('ua_company_url', $u);
        $this->assertArrayHasKey('ua_icon', $u);
        $this->assertArrayHasKey('ua_info_url', $u);
        $this->assertArrayHasKey('os_family', $u);
        $this->assertArrayHasKey('os_name', $u);
        $this->assertArrayHasKey('os_url', $u);
        $this->assertArrayHasKey('os_company', $u);
        $this->assertArrayHasKey('os_company_url', $u);
        $this->assertArrayHasKey('os_icon', $u);
    }

    /**
     * Test a specific, known user agent string.
     * @depends testUpdateDatabase
     */
    public function testSafari() {
        $u = self::$uasparser->parse('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/536.26.17 (KHTML, like Gecko) Version/6.0.2 Safari/536.26.17');
        $this->assertTrue(is_array($u));
        $this->assertEquals($u['typ'], 'Browser');
        $this->assertEquals($u['ua_family'], 'Safari');
        $this->assertEquals($u['ua_name'], 'Safari 6.0.2');
        $this->assertEquals($u['ua_version'], '6.0.2');
        $this->assertEquals($u['ua_url'], 'http://en.wikipedia.org/wiki/Safari_%28web_browser%29');
        $this->assertEquals($u['ua_company'], 'Apple Inc.');
        $this->assertEquals($u['ua_company_url'], 'http://www.apple.com/');
        $this->assertEquals($u['ua_icon'], 'safari.png');
        $this->assertEquals($u['ua_info_url'], 'http://user-agent-string.info/list-of-ua/browser-detail?browser=Safari');
        $this->assertEquals($u['os_family'], 'OS X');
        $this->assertEquals($u['os_name'], 'OS X 10.8 Mountain Lion');
        $this->assertEquals($u['os_url'], 'http://www.apple.com/osx/');
        $this->assertEquals($u['os_company'], 'Apple Computer, Inc.');
        $this->assertEquals($u['os_company_url'], 'http://www.apple.com/');
        $this->assertEquals($u['os_icon'], 'macosx.png');
    }

    /**
     * Test getting the user agent from the global env.
     * @depends testUpdateDatabase
     */
    public function testEnv() {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/536.26.17 (KHTML, like Gecko) Version/6.0.2 Safari/536.26.17';
        $u = self::$uasparser->parse();
        $this->assertTrue(is_array($u));
        $this->assertEquals($u['typ'], 'Browser');
        $this->assertEquals($u['ua_family'], 'Safari');
        $this->assertEquals($u['ua_name'], 'Safari 6.0.2');
        $this->assertEquals($u['ua_version'], '6.0.2');
        $this->assertEquals($u['ua_url'], 'http://en.wikipedia.org/wiki/Safari_%28web_browser%29');
        $this->assertEquals($u['ua_company'], 'Apple Inc.');
        $this->assertEquals($u['ua_company_url'], 'http://www.apple.com/');
        $this->assertEquals($u['ua_icon'], 'safari.png');
        $this->assertEquals($u['ua_info_url'], 'http://user-agent-string.info/list-of-ua/browser-detail?browser=Safari');
        $this->assertEquals($u['os_family'], 'OS X');
        $this->assertEquals($u['os_name'], 'OS X 10.8 Mountain Lion');
        $this->assertEquals($u['os_url'], 'http://www.apple.com/osx/');
        $this->assertEquals($u['os_company'], 'Apple Computer, Inc.');
        $this->assertEquals($u['os_company_url'], 'http://www.apple.com/');
        $this->assertEquals($u['os_icon'], 'macosx.png');
    }

    /**
     * Test a robot user agent.
     * @depends testUpdateDatabase
     */
    public function testRobot() {
        $u = self::$uasparser->parse('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
        $this->assertTrue(is_array($u));
        $this->assertEquals($u['typ'], 'Robot');
        $this->assertEquals($u['ua_family'], 'Googlebot');
        $this->assertEquals($u['ua_name'], 'Googlebot/2.1');
        $this->assertEquals($u['ua_version'], 'unknown');
        $this->assertEquals($u['ua_url'], 'https://support.google.com/webmasters/answer/1061943?hl=en');
        $this->assertEquals($u['ua_company'], 'Google Inc.');
        $this->assertEquals($u['ua_company_url'], 'http://www.google.com/');
        $this->assertEquals($u['ua_icon'], 'bot_googlebot.png');
        $this->assertEquals($u['ua_info_url'], 'http://user-agent-string.info/list-of-ua/bot-detail?bot=Googlebot');
        $this->assertEquals($u['os_family'], 'unknown');
        $this->assertEquals($u['os_name'], 'unknown');
        $this->assertEquals($u['os_url'], 'unknown');
        $this->assertEquals($u['os_company'], 'unknown');
        $this->assertEquals($u['os_company_url'], 'unknown');
        $this->assertEquals($u['os_icon'], 'unknown.png');
    }

    /**
     * Test an user agent whose OS needs to be looked up.
     * @depends testUpdateDatabase
     */
    public function testUnknownOS() {
        $u = self::$uasparser->parse('OmniWeb/2.7-beta-3 OWF/1.0');
        $this->assertTrue(is_array($u));
        $this->assertEquals($u['typ'], 'Browser');
        $this->assertEquals($u['ua_family'], 'OmniWeb');
        $this->assertEquals($u['ua_name'], 'OmniWeb 2.7-beta-3');
        $this->assertEquals($u['ua_version'], '2.7-beta-3');
        $this->assertEquals($u['ua_url'], 'http://www.omnigroup.com/applications/omniweb/');
        $this->assertEquals($u['ua_company'], 'Omni Development, Inc.');
        $this->assertEquals($u['ua_company_url'], 'http://www.omnigroup.com/');
        $this->assertEquals($u['ua_icon'], 'omniweb.png');
        $this->assertEquals($u['ua_info_url'], 'http://user-agent-string.info/list-of-ua/browser-detail?browser=OmniWeb');
        $this->assertEquals($u['os_family'], 'Mac OS');
        $this->assertEquals($u['os_name'], 'Mac OS');
        $this->assertEquals($u['os_url'], 'http://en.wikipedia.org/wiki/Mac_OS');
        $this->assertEquals($u['os_company'], 'Apple Computer, Inc.');
        $this->assertEquals($u['os_company_url'], 'http://www.apple.com/');
        $this->assertEquals($u['os_icon'], 'macos.png');
    }
}
