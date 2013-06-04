<?php
/**
 * PHP version 5
 *
 * @package    UASparser
 * @author     Jaroslav Mallat (http://mallat.cz/)
 * @copyright  Copyright (c) 2008 Jaroslav Mallat
 * @copyright  Copyright (c) 2010 Alex Stanev (http://stanev.org)
 * @copyright  Copyright (c) 2012 Martin van Wingerden (http://www.copernica.com)
 * @author     Marcus Bointon (https://github.com/Synchro)
 * @version    0.51
 * @license    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link       http://user-agent-string.info/download/UASparser
 */

namespace UAS;

class Parser
{
    /**
     * @var integer How often to update UAS database
     */
    public $updateInterval = 86400; // 1 day

    /**
     * @var boolean Whether debug output is enabled
     */
    protected $debug = false;

    /**
     * @var integer $timeout Default timeout for network requests
     */
    public $timeout = 60;

    private static $_ini_url = 'http://user-agent-string.info/rpc/get_data.php?key=free&format=ini';
    private static $_ver_url = 'http://user-agent-string.info/rpc/get_data.php?key=free&format=ini&ver=y';
    private static $_md5_url = 'http://user-agent-string.info/rpc/get_data.php?format=ini&md5=y';
    private static $_info_url = 'http://user-agent-string.info';
    private $_cache_dir = null;
    private $_data = null;

    /**
     * Constructor with an optional cache directory
     * @param string $cacheDirectory
     * @param integer $updateInterval
     * @param bool $debug
     * @internal param \cache $string directory to be used by this instance
     */
    public function __construct($cacheDirectory = null, $updateInterval = null, $debug = false)
    {
        if ($cacheDirectory) {
            $this->SetCacheDir($cacheDirectory);
        }
        if ($updateInterval) {
            $this->updateInterval = $updateInterval;
        }
        $this->debug = (boolean)$debug;
    }

    /**
     * Output a time-stamped debug message if debugging is enabled
     * @param string $msg
     */
    protected function debug($msg) {
        if ($this->debug) {
            echo gmdate('Y-m-d H:i:s') . "\t$msg\n";
        }
    }

    /**
     * Parse the useragent string if given otherwise parse the current user agent
     * @param string $useragent user agent string
     * @return array
     */
    public function Parse($useragent = null)
    {
        // intialize some variables
        $browser_id = $os_id = null;
        $result = array();

        // initialize the return value
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

        // if no user agent is supplied process the one from the server vars
        if (!isset($useragent) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $useragent = $_SERVER['HTTP_USER_AGENT'];
        }

        // if we haven't loaded the data yet, do it now
        if (!$this->_data) {
            $this->_data = $this->_loadData();
        }

        // we have no data or no valid user agent, just return the default data
        if (!$this->_data || !isset($useragent)) {
            return $result;
        }

        // crawler
        foreach ($this->_data['robots'] as $test) {
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
                    $os_data = $this->_data['os'][$test[7]];
                    if ($os_data[0]) {
                        $result['os_family'] = $os_data[0];
                    }
                    if ($os_data[1]) {
                        $result['os_name'] = $os_data[1];
                    }
                    if ($os_data[2]) {
                        $result['os_url'] = $os_data[2];
                    }
                    if ($os_data[3]) {
                        $result['os_company'] = $os_data[3];
                    }
                    if ($os_data[4]) {
                        $result['os_company_url'] = $os_data[4];
                    }
                    if ($os_data[5]) {
                        $result['os_icon'] = $os_data[5];
                    }
                }
                if ($test[8]) {
                    $result['ua_info_url'] = self::$_info_url . $test[8];
                }
                return $result;
            }
        }

        // find a browser based on the regex
        foreach ($this->_data['browser_reg'] as $test) {
            if (@preg_match($test[0], $useragent, $info)) { // $info may contain version
                $browser_id = $test[1];
                break;
            }
        }

        // a valid browser was found
        if ($browser_id) { // browser detail
            $browser_data = $this->_data['browser'][$browser_id];
            if ($this->_data['browser_type'][$browser_data[0]][0]) {
                $result['typ'] = $this->_data['browser_type'][$browser_data[0]][0];
            }
            if (isset($info[1])) {
                $result['ua_version'] = $info[1];
            }
            if ($browser_data[1]) {
                $result['ua_family'] = $browser_data[1];
            }
            if ($browser_data[1]) {
                $result['ua_name'] = $browser_data[1] . (isset($info[1]) ? ' ' . $info[1] : '');
            }
            if ($browser_data[2]) {
                $result['ua_url'] = $browser_data[2];
            }
            if ($browser_data[3]) {
                $result['ua_company'] = $browser_data[3];
            }
            if ($browser_data[4]) {
                $result['ua_company_url'] = $browser_data[4];
            }
            if ($browser_data[5]) {
                $result['ua_icon'] = $browser_data[5];
            }
            if ($browser_data[6]) {
                $result['ua_info_url'] = self::$_info_url . $browser_data[6];
            }
        }

        // browser OS, does this browser match contain a reference to an os?
        if (isset($this->_data['browser_os'][$browser_id])) { // os detail
            $os_id = $this->_data['browser_os'][$browser_id][0]; // Get the os id
            $os_data = $this->_data['os'][$os_id];
            if ($os_data[0]) {
                $result['os_family'] = $os_data[0];
            }
            if ($os_data[1]) {
                $result['os_name'] = $os_data[1];
            }
            if ($os_data[2]) {
                $result['os_url'] = $os_data[2];
            }
            if ($os_data[3]) {
                $result['os_company'] = $os_data[3];
            }
            if ($os_data[4]) {
                $result['os_company_url'] = $os_data[4];
            }
            if ($os_data[5]) {
                $result['os_icon'] = $os_data[5];
            }
            return $result;
        }

        // search for the os
        foreach ($this->_data['os_reg'] as $test) {
            if (@preg_match($test[0], $useragent)) {
                $os_id = $test[1];
                break;
            }
        }

        // a valid os was found
        if ($os_id) { // os detail
            $os_data = $this->_data['os'][$os_id];
            if ($os_data[0]) {
                $result['os_family'] = $os_data[0];
            }
            if ($os_data[1]) {
                $result['os_name'] = $os_data[1];
            }
            if ($os_data[2]) {
                $result['os_url'] = $os_data[2];
            }
            if ($os_data[3]) {
                $result['os_company'] = $os_data[3];
            }
            if ($os_data[4]) {
                $result['os_company_url'] = $os_data[4];
            }
            if ($os_data[5]) {
                $result['os_icon'] = $os_data[5];
            }
        }
        return $result;
    }

    /**
     * Load the data from the files
     * @return boolean
     */
    private function _loadData()
    {
        if (!file_exists($this->_cache_dir)) {
            $this->debug('Cache file not found');
            return false;
        }

        if (file_exists($this->_cache_dir . '/cache.ini')) {
            $cacheIni = parse_ini_file($this->_cache_dir . '/cache.ini');

            // should we reload the data because it is already old?
            if ($cacheIni['lastupdate'] < time() - $this->updateInterval || $cacheIni['lastupdatestatus'] != '0') {
                $this->downloadData();
            }
        } else {
            $this->downloadData();
        }

        // we have file with data, parse and return it
        if (file_exists($this->_cache_dir . '/uasdata.ini')) {
            return @parse_ini_file($this->_cache_dir . '/uasdata.ini', true);
        } else {
            $this->debug('Data file not found');
        }
        return false;
    }

    /**
     * Download the data
     * @param bool $force Whether to force a download even if we have a cached file
     * @return boolean
     */
    public function DownloadData($force = false)
    {
        // by default status is failed
        $status = false;
        // support for one of curl or fopen wrappers is needed
        if (!ini_get('allow_url_fopen') && !function_exists('curl_init')) {
            $this->debug('Fopen wrappers and curl unavailable, cannot continue');
            trigger_error(
                'ERROR: function file_get_contents not allowed URL open. Update the datafile (uasdata.ini in Cache Dir) manually.'
            );
            return $status;
        }

        $cacheIni = array();
        if (file_exists($this->_cache_dir . '/cache.ini')) {
            $cacheIni = parse_ini_file($this->_cache_dir . '/cache.ini');
        }

        // Check the version on the server
        // If we are current, don't download again
        $ver = $this->get_contents(self::$_ver_url, $this->timeout);
        if (preg_match('/^[0-9]{8}-[0-9]{2}$/', $ver)) { //Should be a date and version string like '20130529-01'
            if (array_key_exists('localversion', $cacheIni)) {
                if ($ver <= $cacheIni['localversion']) { //Version on server is same as or older than what we already have
                    if ($force) {
                        $this->debug('Existing file is current, but forcing a download anyway.');
                    } else {
                        $this->debug('Download skipped, existing file is current.');
                        return true;
                    }
                }
            }
        } else {
            $this->debug('Version string format mismatch.');
            $ver = 'none'; //Server gave us something unexpected
        }

        // Download the ini file
        $ini = $this->get_contents(self::$_ini_url, $this->timeout);
        if (!empty($ini)) {
            // download the hash file
            $md5hash = $this->get_contents(self::$_md5_url, $this->timeout);
            if (!empty($md5hash)) {
                // validate the hash, if okay store the new ini file
                if (md5($ini) == $md5hash) {
                    $written = @file_put_contents($this->_cache_dir . '/uasdata.ini', $ini, LOCK_EX);
                    if ($written === false) {
                        $this->debug('Failed to write data file to ' . $this->_cache_dir . '/uasdata.ini');
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

        // build a new cache file and store it
        $cacheIni = "; cache info for class UASparser - http://user-agent-string.info/download/UASparser\n";
        $cacheIni .= "[main]\n";
        $cacheIni .= "localversion = \"$ver\"\n";
        $cacheIni .= 'lastupdate = "' . time() . "\"\n";
        $cacheIni .= "lastupdatestatus = \"$status\"\n";
        $written = @file_put_contents($this->_cache_dir . '/cache.ini', $cacheIni, LOCK_EX);
        if ($written === false) {
            $this->debug('Failed to write cache file to ' . $this->_cache_dir . '/cache.ini');
        }

        return $status; //Return true on success
    }

    /**
     * Get the content of a certain url with a defined timeout
     * The timeout is set high (5 minutes) as the site can be slow to respond
     * You shouldn't be doing this request interactively anyway!
     * @param string $url
     * @param int $timeout
     * @return string
     */
    private function get_contents($url, $timeout = 300)
    {
        $data = '';
        $starttime = microtime(true);
        // use fopen
        if (ini_get('allow_url_fopen')) {
            $fp = @fopen($url, 'rb', false, stream_context_create(array(
                  'http' => array(
                    'timeout' => $timeout,
                    'header'  => "Accept-Encoding: gzip\r\n"
                  ))));
            if (is_resource($fp)) {
                $data = stream_get_contents($fp);
                $res = stream_get_meta_data($fp);
                if (array_key_exists('wrapper_data', $res)) {
                  foreach($res['wrapper_data'] as $d) {
                    if ($d == 'Content-Encoding: gzip') { //Data was compressed
                      $data = gzinflate(substr($data, 10, -8)); //Uncompress data
                      $this->debug('Successfully uncompressed data');
                      break;
                    }
                  }
                }
                fclose($fp);
                if (empty($data)) {
                    if ($this->debug) {
                        if ($res['timed_out']) {
                            $this->debug('Fetching URL failed due to timeout: '.$url);
                        } else {
                            $this->debug('Fetching URL failed: '.$url);
                        }
                    }
                    $data = '';
                } else {
                  $this->debug('Fetching URL with fopen succeeded: '.$url.'. '.strlen($data).' bytes in '.(microtime(true) - $starttime).' sec.');
                }
            } else {
                $this->debug('Opening URL failed: '.$url);
            }
        } // fall back to curl
        elseif (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => 'gzip'
            ));
            $data = curl_exec($ch);
            if ($data !== false and curl_errno($ch) == 0) {
                $this->debug('Fetching URL with curl succeeded: '.$url.'. '.strlen($data).' bytes in '.(microtime(true) - $starttime).' sec.');
            } else {
                $this->debug('Opening URL with curl failed: '.$url.' '.curl_error($ch));
                $data = '';
            }
            curl_close($ch);
        } else {
            trigger_error('Could not fetch UAS data; neither fopen nor curl are available.', E_USER_ERROR);
        }
        return $data;
    }

    /**
     * Set the cache directory
     * @param string
     * @return bool
     */
    public function SetCacheDir($cache_dir)
    {
        $this->debug('Setting cache dir to '.$cache_dir);
        // The directory does not exist at this moment, try to make it
        if (!file_exists($cache_dir)) {
            @mkdir($cache_dir, 0777, true);
        }

        // perform some extra checks
        if (!is_writable($cache_dir) || !is_dir($cache_dir)) {
            $this->debug('Cache dir(' . $cache_dir . ') is not a directory or not writable');
            return false;
        }

        // store the cache dir
        $cache_dir = realpath($cache_dir);
        $this->_cache_dir = $cache_dir;
        return true;
    }

    /**
     * Get the cache directory
     * @return string
     */
    public function GetCacheDir()
    {
        return $this->_cache_dir;
    }

    /**
     * Clear the cache files
     */
    public function ClearCache()
    {
        @unlink($this->_cache_dir . '/cache.ini');
        @unlink($this->_cache_dir . '/uasdata.ini');
        $this->debug('Cleared cache.');
    }
}
