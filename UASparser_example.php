<?php
/**
 * PHP version 5
 *
 * @package UASparser
 * @author Jaroslav Mallat (http://mallat.cz/)
 * @copyright Copyright (c) 2008 Jaroslav Mallat
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link http://user-agent-string.info/download/UASparser
 */

namespace UAS;

// Loads the class
require 'UAS/Parser.php';

// header page
scriptheader();

// Creates a new UASparser object and set cache dir (this php scrimt must right write to cache dir)
$parser = new Parser();
$parser->SetCacheDir(sys_get_temp_dir() . "/uascache/");

// Gets information about the current browser's user agent
$ret = $parser->Parse();
// print response data - array view
$useragent = 'unknown';
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $useragent = $_SERVER['HTTP_USER_AGENT'];
}
echo "<h2>Array view</h2>";
echo "<b>Researched current useragent: </b><pre>".htmlspecialchars($useragent)."</pre>";
echo "<pre>";
print_r($ret);
echo "</pre>";
echo "<h2>Formatted view</h2>";

// All icons are available on http://user-agent-string.info/download/ (all icons is 16x16px)
$ico_ua_url = "http://user-agent-string.info/pub/img/ua/";
$ico_os_url = "http://user-agent-string.info/pub/img/os/";

// print response data - formatted view
echo "<b>Researched current useragent: </b><br />";
echo $ret['typ'] . " - ";
if ($ret['ua_url'] == "unknown") {
    $ua = $ret['ua_name'];
} else {
    $ua = "<a href=\"" . $ret['ua_url'] . "\">" . $ret['ua_name'] . "</a>";
}
echo "<img src=\"" . $ico_ua_url . $ret['ua_icon'] . "\" width=\"16\" height=\"16\" border=\"0\"> " . $ua;
if ($ret['os_name'] != "unknown") {
    echo " <b>run on</b> ";
    if ($ret['os_url'] == "unknown") {
        $os = $ret['os_name'];
    } else {
        $os = "<a href=\"" . $ret['os_url'] . "\">" . $ret['os_name'] . "</a>";
    }
    echo "<img src=\"" . $ico_os_url . $ret['os_icon'] . "\" width=\"16\" height=\"16\" border=\"0\"> " . $os;
}
echo " --> <a href=\"" . $ret['ua_info_url'] . "\">UA info</a><br />";

// Gets information about user agent - example 2
$ret = $parser->Parse("libwww-perl/5.812");
// print response data - formatted view
echo "<br /><b>Researched useragent:</b> libwww-perl/5.812<br />";
echo $ret['typ'] . " - ";
if ($ret['ua_url'] == "unknown") {
    $ua = $ret['ua_name'];
} else {
    $ua = "<a href=\"" . $ret['ua_url'] . "\">" . $ret['ua_name'] . "</a>";
}
echo "<img src=\"" . $ico_ua_url . $ret['ua_icon'] . "\" width=\"16\" height=\"16\" border=\"0\"> " . $ua;
if ($ret['os_name'] != "unknown") {
    echo " <b>run on</b> ";
    if ($ret['os_url'] == "unknown") {
        $os = $ret['os_name'];
    } else {
        $os = "<a href=\"" . $ret['os_url'] . "\">" . $ret['os_name'] . "</a>";
    }
    echo "<img src=\"" . $ico_os_url . $ret['os_icon'] . "\" width=\"16\" height=\"16\" border=\"0\"> " . $os;
}
echo " --> <a href=\"" . $ret['ua_info_url'] . "\">UA info</a><br />";

// Gets information about user agent - example 3
$ret = $parser->Parse("Klondike/1.50 (HTTP Win32)");
// print response data - formatted view
echo "<br /><b>Researched useragent:</b> Klondike/1.50 (HTTP Win32)<br />";
echo $ret['typ'] . " - ";
if ($ret['ua_url'] == "unknown") {
    $ua = $ret['ua_name'];
} else {
    $ua = "<a href=\"" . $ret['ua_url'] . "\">" . $ret['ua_name'] . "</a>";
}
echo "<img src=\"" . $ico_ua_url . $ret['ua_icon'] . "\" width=\"16\" height=\"16\" border=\"0\"> " . $ua;
if ($ret['os_name'] != "unknown") {
    echo " <b>run on</b> ";
    if ($ret['os_url'] == "unknown") {
        $os = $ret['os_name'];
    } else {
        $os = "<a href=\"" . $ret['os_url'] . "\">" . $ret['os_name'] . "</a>";
    }
    echo "<img src=\"" . $ico_os_url . $ret['os_icon'] . "\" width=\"16\" height=\"16\" border=\"0\"> " . $os;
}
echo " --> <a href=\"" . $ret['ua_info_url'] . "\">UA info</a><br />";

// Gets information about user agent - example 4
$ret = $parser->Parse("Googlebot-Image/1.0");
// print response data - formatted view
echo "<br /><b>Researched useragent: </b>Googlebot-Image/1.0<br />";
echo $ret['typ'] . " - ";
if ($ret['ua_url'] == "unknown") {
    $ua = $ret['ua_name'];
} else {
    $ua = "<a href=\"" . $ret['ua_url'] . "\">" . $ret['ua_name'] . "</a>";
}
echo "<img src=\"" . $ico_ua_url . $ret['ua_icon'] . "\" width=\"16\" height=\"16\" border=\"0\"> " . $ua;
if ($ret['os_name'] != "unknown") {
    echo " <b>run on</b> ";
    if ($ret['os_url'] == "unknown") {
        $os = $ret['os_name'];
    } else {
        $os = "<a href=\"" . $ret['os_url'] . "\">" . $ret['os_name'] . "</a>";
    }
    echo "<img src=\"" . $ico_os_url . $ret['os_icon'] . "\" width=\"16\" height=\"16\" border=\"0\"> " . $os;
}
echo " --> <a href=\"" . $ret['ua_info_url'] . "\">UA info</a><br />";

// Gets information about user agent - example 6
$ret = $parser->Parse("W3C_Validator/1.654");
// print response data - formatted view
echo "<br /><b>Researched useragent: </b>W3C_Validator/1.654<br />";
echo $ret['typ'] . " - ";
if ($ret['ua_url'] == "unknown") {
    $ua = $ret['ua_name'];
} else {
    $ua = "<a href=\"" . $ret['ua_url'] . "\">" . $ret['ua_name'] . "</a>";
}
echo "<img src=\"" . $ico_ua_url . $ret['ua_icon'] . "\" width=\"16\" height=\"16\" border=\"0\"> " . $ua;
if ($ret['os_name'] != "unknown") {
    echo " <b>run on</b> ";
    if ($ret['os_url'] == "unknown") {
        $os = $ret['os_name'];
    } else {
        $os = "<a href=\"" . $ret['os_url'] . "\">" . $ret['os_name'] . "</a>";
    }
    echo "<img src=\"" . $ico_os_url . $ret['os_icon'] . "\" width=\"16\" height=\"16\" border=\"0\"> " . $os;
}
echo " --> <a href=\"" . $ret['ua_info_url'] . "\">UA info</a><br />";


// end page
foot();


function scriptheader()
{
    ?>
<html>
<head>
    <title>class UASparser.php example</title>
</head>
<body>
<h1>UASparser example</h1>
<hr/>
    <?php
}

function foot()
{
    ?>
<hr/>
<p><a href="http://user-agent-string.info/">user-agent-string.info</a></p>
<p>This script uses the UASparser library from <a href="http://user-agent-string.info/download/UASparser">http://user-agent-string.info/download/UASparser</a>
</p>
</body></html>
<?php
}