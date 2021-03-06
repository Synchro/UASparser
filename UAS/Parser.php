<?php

/**
 * User Agent String Parser
 *
 * PHP version 5
 *
 * @package    UASparser
 * @author     Jaroslav Mallat (http://mallat.cz/)
 * @copyright  Copyright (c) 2008 Jaroslav Mallat
 * @copyright  Copyright (c) 2010 Alex Stanev (http://stanev.org)
 * @copyright  Copyright (c) 2012 Martin van Wingerden (http://www.copernica.com)
 * @author     Marcus Bointon (https://github.com/Synchro)
 * @version    0.53
 * @license    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link       http://user-agent-string.info/download/UASparser
 */

namespace UAS;

/**
 * User Agent String Parser Class.
 * @package UASparser
 */
class Parser
{
    /**
     * How often to update the UAS database.
     * @type integer
     */
    public $updateInterval = 86400; // 1 day

    /**
     * Whether debug output is enabled.
     * @type boolean
     */
    protected $debug = false;

    /**
     * Default timeout for network requests.
     * @type integer
     */
    public $timeout = 60;

    /**
     * Should this instance attempt data downloads?
     * Useful if some other instance (e.g. from cron) is responsible for downloads.
     * @type bool
     */
    protected $doDownloads = true;
    /**
     * Should this instance use zip compression while downloads
     * Useful if use mbstring.func_overload
     * @type bool
     */
    protected $useZipDownloads = true;
    /**
     * URL to fetch the full data file from.
     * @type string
     */
    protected static $ini_url = 'http://user-agent-string.info/rpc/get_data.php?key=free&format=ini';

    /**
     * URL to fetch the data file version from.
     * @type string
     */
    protected static $ver_url = 'http://user-agent-string.info/rpc/get_data.php?key=free&format=ini&ver=y';

    /**
     * URL to fetch the data file hash from.
     * @type string
     */
    protected static $md5_url = 'http://user-agent-string.info/rpc/get_data.php?format=ini&md5=y';

    /**
     * URL for info about the UAS project.
     * @type string
     */
    protected static $info_url = 'http://user-agent-string.info';

    /**
     * Path to store data file downloads to.
     * @type string|null
     */
    protected $cache_dir = null;

    /**
     * Array of parsed UAS data.
     * @type array|null
     */
    protected $data = null;

    /**
     * Constructor.
     * @param string $cacheDirectory Cache directory for data downloads
     * @param integer $updateInterval Allowed age of the cache file.
     * @param bool $debug Whether to emit debug info.
     * @param bool $doDownloads Whether to allow data downloads.
     */
    public function __construct($cacheDirectory = null, $updateInterval = null, $debug = false, $doDownloads = true)
    {
        if ($cacheDirectory) {
            $this->SetCacheDir($cacheDirectory);
        }
        if ($updateInterval) {
            $this->updateInterval = $updateInterval;
        }
        $this->debug = (bool) $debug;
        $this->doDownloads = (bool) $doDownloads;
    }

    /**
     * Output a time-stamped debug message if debugging is enabled
     * @param string $msg
     */
    protected function debug($msg)
    {
        if ($this->debug) {
            echo gmdate('Y-m-d H:i:s') . "\t$msg\n";
        }
    }

    /**
     * Parse the useragent string if given, otherwise parse the current user agent.
     * @param string $useragent user agent string
     * @return array
     */
    public function parse($useragent = null)
    {
        // Intialize some variables
        $browserId = $osId = null;
        $result = array();

        // Initialize the return value
        $result['typ'] = 'unknown';
        $result['ua_family'] = 'unknown';
        $result['ua_name'] = 'unknown';
        $result['ua_version'] = 'unknown';
        $result['ua_url'] = 'unknown';
        $result['ua_company'] = 'unknown';
        $result['ua_company_url'] = 'unknown';
        $result['ua_icon'] = 'unknown.png';
        $result['ua_info_url'] = 'unknown';
        $result['os_family'] = 'unknown';
        $result['os_name'] = 'unknown';
        $result['os_url'] = 'unknown';
        $result['os_company'] = 'unknown';
        $result['os_company_url'] = 'unknown';
        $result['os_icon'] = 'unknown.png';

        // If no user agent is supplied process the one from the server vars
        if (!isset($useragent) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $useragent = $_SERVER['HTTP_USER_AGENT'];
        }

        // If we haven't loaded the data yet, do it now
        if (!$this->data) {
            $this->data = $this->loadData();
        }

        // We have no data or no valid user agent, just return the default data
        if (!$this->data || !isset($useragent)) {
            return $result;
        }

        // Crawler
        foreach ($this->data['robots'] as $test) {
            if ($test[0] == $useragent) {
                $result['typ'] = 'Robot';
                if ($test[1]) {
                    $result['ua_family'] = $test[1];
                }
                if ($test[2]) {
                    $result['ua_name'] = $test[2];
                }
                if ($test[3]) {
                    $result['ua_url'] = $test[3];
                }
                if ($test[4]) {
                    $result['ua_company'] = $test[4];
                }
                if ($test[5]) {
                    $result['ua_company_url'] = $test[5];
                }
                if ($test[6]) {
                    $result['ua_icon'] = $test[6];
                }
                if ($test[7]) { // OS set
                    $osData = $this->data['os'][$test[7]];
                    if ($osData[0]) {
                        $result['os_family'] = $osData[0];
                    }
                    if ($osData[1]) {
                        $result['os_name'] = $osData[1];
                    }
                    if ($osData[2]) {
                        $result['os_url'] = $osData[2];
                    }
                    if ($osData[3]) {
                        $result['os_company'] = $osData[3];
                    }
                    if ($osData[4]) {
                        $result['os_company_url'] = $osData[4];
                    }
                    if ($osData[5]) {
                        $result['os_icon'] = $osData[5];
                    }
                }
                if ($test[8]) {
                    $result['ua_info_url'] = self::$info_url . $test[8];
                }
                return $result;
            }
        }

        // Find a browser based on the regex
        foreach ($this->data['browser_reg'] as $test) {
            if (@preg_match($test[0], $useragent, $info)) { // $info may contain version
                $browserId = $test[1];
                break;
            }
        }

        // A valid browser was found
        if ($browserId) { // Browser detail
            $browserData = $this->data['browser'][$browserId];
            if ($this->data['browser_type'][$browserData[0]][0]) {
                $result['typ'] = $this->data['browser_type'][$browserData[0]][0];
            }
            if (isset($info[1])) {
                $result['ua_version'] = $info[1];
            }
            if ($browserData[1]) {
                $result['ua_family'] = $browserData[1];
            }
            if ($browserData[1]) {
                $result['ua_name'] = $browserData[1] . (isset($info[1]) ? ' ' . $info[1] : '');
            }
            if ($browserData[2]) {
                $result['ua_url'] = $browserData[2];
            }
            if ($browserData[3]) {
                $result['ua_company'] = $browserData[3];
            }
            if ($browserData[4]) {
                $result['ua_company_url'] = $browserData[4];
            }
            if ($browserData[5]) {
                $result['ua_icon'] = $browserData[5];
            }
            if ($browserData[6]) {
                $result['ua_info_url'] = self::$info_url . $browserData[6];
            }
        }

        // Browser OS, does this browser match contain a reference to an os?
        if (isset($this->data['browser_os'][$browserId])) { // OS detail
            $osId = $this->data['browser_os'][$browserId][0]; // Get the OS id
            $osData = $this->data['os'][$osId];
            if ($osData[0]) {
                $result['os_family'] = $osData[0];
            }
            if ($osData[1]) {
                $result['os_name'] = $osData[1];
            }
            if ($osData[2]) {
                $result['os_url'] = $osData[2];
            }
            if ($osData[3]) {
                $result['os_company'] = $osData[3];
            }
            if ($osData[4]) {
                $result['os_company_url'] = $osData[4];
            }
            if ($osData[5]) {
                $result['os_icon'] = $osData[5];
            }
            return $result;
        }

        // Search for the OS
        foreach ($this->data['os_reg'] as $test) {
            if (@preg_match($test[0], $useragent)) {
                $osId = $test[1];
                break;
            }
        }

        // A valid OS was found
        if ($osId) { // OS detail
            $osData = $this->data['os'][$osId];
            if ($osData[0]) {
                $result['os_family'] = $osData[0];
            }
            if ($osData[1]) {
                $result['os_name'] = $osData[1];
            }
            if ($osData[2]) {
                $result['os_url'] = $osData[2];
            }
            if ($osData[3]) {
                $result['os_company'] = $osData[3];
            }
            if ($osData[4]) {
                $result['os_company_url'] = $osData[4];
            }
            if ($osData[5]) {
                $result['os_icon'] = $osData[5];
            }
        }
        return $result;
    }

    /**
     * Load agent data from the files.
     * Will download data if we don't have any.
     * @return boolean
     */
    protected function loadData()
    {
        if (!file_exists($this->cache_dir)) {
            $this->debug('Cache file not found');
            return false;
        }

        if (file_exists($this->cache_dir . '/cache.ini')) {
            $cacheIni = parse_ini_file($this->cache_dir . '/cache.ini');

            // Should we fetch new data because it is too old?
            if ($cacheIni['lastupdatestatus'] != '1' || $cacheIni['lastupdate'] < time() - $this->updateInterval) {
                if ($this->doDownloads) {
                    $this->downloadData();
                } else {
                    $this->debug('Downloads suppressed, using old data');
                }
            }
        } else {
            // Do a download even if downloads are disabled as otherwise we can't work at all
            if (!$this->doDownloads) {
                $this->debug('Data missing - Doing download even though downloads are suppressed');
            }
            $this->downloadData();
        }

        // We have file with data, parse and return it
        if (file_exists($this->cache_dir . '/uasdata.ini')) {
            return @parse_ini_file($this->cache_dir . '/uasdata.ini', true);
        } else {
            $this->debug('Data file not found');
        }
        return false;
    }

    /**
     * Download new data.
     * @param bool $force Whether to force a download even if we have a cached file
     * @return boolean
     */
    public function downloadData($force = false)
    {
        // by default status is failed
        $status = false;
        // support for one of curl or fopen wrappers is needed
        if (!ini_get('allow_url_fopen') && !function_exists('curl_init')) {
            $this->debug('Fopen wrappers and curl unavailable, cannot continue');
            trigger_error(
                'ERROR: function file_get_contents not allowed URL open. Update the datafile ',
                '(uasdata.ini in Cache Dir) manually.'
            );
            return $status;
        }

        $cacheIni = array();
        if (file_exists($this->cache_dir . '/cache.ini')) {
            $cacheIni = parse_ini_file($this->cache_dir . '/cache.ini');
        }

        // Check the version on the server
        // If we are current, don't download again
        $ver = $this->getContents(self::$ver_url, $this->timeout);
        //Should be a date and version string like '20130529-01'
        if (preg_match('/^[0-9]{8}-[0-9]{2}$/', $ver)) {
            if (array_key_exists('localversion', $cacheIni)) {
                //Version on server is same as or older than what we already have
                if ($ver <= $cacheIni['localversion']) {
                    if ($force) {
                        $this->debug('Existing file is current, but forcing a download anyway.');
                    } else {
                        $this->debug('Download skipped, existing file is current.');
                        $status = true;
                        $this->writeCacheIni($ver, $status);
                        return $status;
                    }
                }
            }
        } else {
            $this->debug('Version string format mismatch.');
            $ver = 'none'; //Server gave us something unexpected
        }

        // Download the ini file
        $ini = $this->getContents(self::$ini_url, $this->timeout);
        if (!empty($ini)) {
            // Download the hash file
            $md5hash = $this->getContents(self::$md5_url, $this->timeout);
            if (!empty($md5hash)) {
                // Validate the hash, if okay store the new ini file
                if (md5($ini) == $md5hash) {
                    $written = @file_put_contents($this->cache_dir . '/uasdata.ini', $ini, LOCK_EX);
                    if ($written === false) {
                        $this->debug('Failed to write data file to ' . $this->cache_dir . '/uasdata.ini');
                    } else {
                        $status = true;
                    }
                } else {
                    $this->debug('Data file hash mismatch.');
                }
            } else {
                $this->debug('Failed to fetch hash file.');
            }
        } else {
            $this->debug('Failed to fetch data file.');
        }
        $this->writeCacheIni($ver, $status);
        return $status; //Return true on success
    }

    /**
     * Generate and write the cache.ini file in the cache directory
     * @param string $ver
     * @param string $status
     * @return bool
     */
    protected function writeCacheIni($ver, $status)
    {
        // Build a new cache file and store it
        $cacheIni = "; cache info for class UASparser - http://user-agent-string.info/download/UASparser\n";
        $cacheIni .= "[main]\n";
        $cacheIni .= "localversion = \"$ver\"\n";
        $cacheIni .= 'lastupdate = "' . time() . "\"\n";
        $cacheIni .= "lastupdatestatus = \"$status\"\n";
        $written = @file_put_contents($this->cache_dir . '/cache.ini', $cacheIni, LOCK_EX);
        if ($written === false) {
            $this->debug('Failed to write cache file to ' . $this->cache_dir . '/cache.ini');
            return false;
        }
        return true;
    }

    /**
     * Get the contents of a URL with a defined timeout.
     * The timeout is set high (5 minutes) as the site can be slow to respond
     * You shouldn't be doing this request interactively anyway!
     * @param string $url
     * @param int $timeout
     * @return string
     */
    protected function getContents($url, $timeout = 300)
    {
        $data = '';
        $starttime = microtime(true);
        // use fopen
        if (ini_get('allow_url_fopen')) {
            $streamOptions = array(
                'http' => array(
                    'timeout' => $timeout
                )
            );
            if ($this->useZipDownloads) {
                $streamOptions['http']['header'] = "Accept-Encoding: gzip\r\n";
            }
            $filep = @fopen(
                $url,
                'rb',
                false,
                stream_context_create($streamOptions)
            );
            if (is_resource($filep)) {
                $data = stream_get_contents($filep);
                $res = stream_get_meta_data($filep);
                $headers = array();
                if (array_key_exists('wrapper_data', $res) && array_key_exists('headers', $res['wrapper_data'])) {
                    $headers = $res['wrapper_data']['headers'];
                } elseif (array_key_exists('wrapper_data', $res)) {
                    $headers = $res['wrapper_data'];
                }
                if (!empty($headers)) {
                    foreach ($headers as $d) {
                        if ($d == 'Content-Encoding: gzip') { //Data was compressed
                            $data = gzinflate(substr($data, 10, -8)); //Uncompress data
                            $this->debug('Successfully uncompressed data');
                            break;
                        }
                    }
                }
                fclose($filep);
                if (empty($data)) {
                    if ($this->debug) {
                        if ($res['timed_out']) {
                            $this->debug('Fetching URL failed due to timeout: ' . $url);
                        } else {
                            $this->debug('Fetching URL failed: ' . $url);
                        }
                    }
                    $data = '';
                } else {
                    $this->debug(
                        'Fetching URL with fopen succeeded: ' . $url . '. ' .
                        strlen($data) . ' bytes in ' . (microtime(true) - $starttime) . ' sec.'
                    );
                }
            } else {
                $this->debug('Opening URL failed: ' . $url);
            }
        } elseif (function_exists('curl_init')) {
            // Fall back to curl
            $curl = curl_init($url);
            curl_setopt_array(
                $curl,
                array(
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => $timeout,
                    CURLOPT_RETURNTRANSFER => true,
                )
            );
            if ($this->useZipDownloads) {
                curl_setopt(
                    $curl,
                    CURLOPT_ENCODING,
                    'gzip'
                );
            }
            $data = curl_exec($curl);
            if ($data !== false and curl_errno($curl) == 0) {
                $this->debug(
                    'Fetching URL with curl succeeded: ' . $url . '. ' .
                    strlen($data) . ' bytes in ' . (microtime(true) - $starttime) . ' sec.'
                );
            } else {
                $this->debug('Opening URL with curl failed: ' . $url . ' ' . curl_error($curl));
                $data = '';
            }
            curl_close($curl);
        } else {
            trigger_error('Could not fetch UAS data; neither fopen nor curl are available.', E_USER_ERROR);
        }
        return $data;
    }

    /**
     * Set the cache directory.
     * @param string
     * @return bool
     */
    public function setCacheDir($cacheDir)
    {
        $this->debug('Setting cache dir to ' . $cacheDir);
        // The directory does not exist at this moment, try to make it
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        // perform some extra checks
        if (!is_writable($cacheDir) || !is_dir($cacheDir)) {
            $this->debug('Cache dir(' . $cacheDir . ') is not a directory or not writable');
            return false;
        }

        // store the cache dir
        $cacheDir = realpath($cacheDir);
        $this->cache_dir = $cacheDir;
        return true;
    }

    /**
     * Set use zip compression while downloading updates.
     * @param bool $use
     */
    public function setUseZipDownloads($use)
    {
        $this->useZipDownloads = (bool) $use;
    }

    /**
     * Get the cache directory
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cache_dir;
    }

    /**
     * Clear the cache files
     */
    public function clearCache()
    {
        @unlink($this->cache_dir . '/cache.ini');
        @unlink($this->cache_dir . '/uasdata.ini');
        $this->debug('Cleared cache.');
    }

    /**
     * Clear internal data store
     */
    public function clearData()
    {
        $this->data = null;
        $this->debug('Cleared data.');
    }

    /**
     * Get whether downloads are allowed.
     * @return bool
     */
    public function getDoDownloads()
    {
        return $this->doDownloads;
    }

    /**
     * Set whether downloads are allowed.
     * @param $doDownloads
     */
    public function setDoDownloads($doDownloads)
    {
        $this->doDownloads = (bool) $doDownloads;
    }

    /**
     * Get the download URL for the ini file
     * @return string
     */
    public function getIniUrl()
    {
        return self::$ini_url;
    }

    /**
     * Set the download URL for the ini file
     * @param string $url
     */
    public function setIniUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            self::$ini_url = $url;
        }
    }

    /**
     * Get the download URL for the version file
     * @return string
     */
    public function getVerUrl()
    {
        return self::$ver_url;
    }

    /**
     * Set the download URL for the version file
     * @param string $url
     */
    public function setVerUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            self::$ver_url = $url;
        }
    }

    /**
     * Get the download URL for the checksum file
     * @return string
     */
    public function getMd5Url()
    {
        return self::$md5_url;
    }

    /**
     * Set the download URL for the checksum file
     * @param string $url
     */
    public function setMd5Url($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            self::$md5_url = $url;
        }
    }
}
