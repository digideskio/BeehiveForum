<?php

/*======================================================================
Copyright Project BeehiveForum 2002

This file is part of BeehiveForum.

BeehiveForum is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

BeehiveForum is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Beehive; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
USA
======================================================================*/

/* $Id: errorhandler.inc.php,v 1.46 2004-04-19 02:02:11 decoyduck Exp $ */

include_once("./include/constants.inc.php");
include_once("./include/lang.inc.php");

define("FATAL", E_USER_ERROR);
define("ERROR", E_USER_WARNING);
define("WARNING", E_USER_NOTICE);

error_reporting(E_ALL);

// Beehive Error Handler Function

function bh_error_handler($errno, $errstr, $errfile, $errline)
{
    if (error_reporting()) {

        global $lang;

        srand((double)microtime()*1000000);

        @ob_end_clean();
        ob_start("bh_gzhandler");

        if (defined("BEEHIVEMODE_LIGHT")) {

            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
            echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" dir=\"ltr\">\n";
            echo "<head>\n";
            echo "<title>", forum_get_setting('forum_name', false, 'A Beehive Forum'), " - Error Handler</title>\n";
            echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/>\n";
            echo "</head>\n";
            echo "<body>\n";
            echo "<p>{$lang['errorpleasewaitandretry']}</p>\n";
            echo "<form name=\"f_error\" method=\"post\" action=\"", get_request_uri(), "\" target=\"_self\">\n";

            foreach ($_POST as $key => $value) {
                echo "<input type=\"hidden\" name=\"$key}\" value=\"", _htmlentities($value), "\">\n";
            }

            echo "<input class=\"button\" type=\"submit\" name=\"", md5(uniqid(rand())), "\" value=\"{$lang['retry']}\" />\n";

            if (isset($_GET['retryerror']) && basename($_SERVER['PHP_SELF']) == 'post.php') {

                echo "<p>{$lang['multipleerroronpost']}</p>\n";
                echo "<textarea class=\"bhtextarea\" rows=\"15\" name=\"t_content\" cols=\"85\">", _htmlentities(_stripslashes($_POST['t_content'])), "</textarea>\n";

                if (isset($_GET['replyto']) && validate_msg($_GET['replyto'])) {

                    echo "<p>{$lang['replymsgnumber']}:</p>\n";
                    echo "<input class=\"bhinputtext\" type=\"text\" name=\"t_request_url\" value=\"{$_GET['replyto']}\">\n";

                }

            }

            echo "<h2>{$lang['errormsgfordevs']}:</h2>\n";

            switch ($errno) {

                case FATAL:
                    echo "<p><b>FATAL</b> [$errno] $errstr</p>\n";
                    echo "<p>Fatal error in line $errline of file ", basename($errfile), "</p>\n";
                    break;
                case ERROR:
                    echo "<p><b>ERROR</b> [$errno] $errstr</p>\n";
                    echo "<p>Error in line $errline of file ", basename($errfile), "</p>\n";
                    break;
                case WARNING:
                    echo "<p><b>WARNING</b> [$errno] $errstr</p>\n";
                    echo "<p>Warning in line $errline of file ", basename($errfile), "</p>\n";
                    break;
                default:
                    echo "<p><b>Unknown error</b> [$errno] $errstr</p>\n";
                    echo "<p>Unknown error in line $errline of file ", basename($errfile), "</p>\n";
                    break;
            }

            echo "<p>PHP/", PHP_VERSION, " (", PHP_OS, ")</p>\n";
            echo "</form>\n";
            echo "</body>\n";
            echo "</html>\n";

            die;

        }else {

            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
            echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"utf-8\" lang=\"en\" dir=\"ltr\">\n";
            echo "<head>\n";
            echo "<title>", forum_get_setting('forum_name', false, 'A Beehive Forum'), " - Error Handler</title>\n";
            echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
            echo "<link rel=\"icon\" href=\"images/favicon.ico\" type=\"image/ico\" />\n";
            echo "<link rel=\"stylesheet\" href=\"styles/default/style.css\" type=\"text/css\" />\n";
            echo "</head>\n";
            echo "<body>\n";
            echo "<div align=\"center\">\n";
            echo "<form name=\"f_error\" method=\"post\" action=\"", get_request_uri(), "\" target=\"_self\">\n";
            echo "<table cellpadding=\"0\" cellspacing=\"0\" width=\"550\">\n";
            echo "  <tr>\n";
            echo "    <td>\n";
            echo "      <table border=\"0\" width=\"100%\">\n";
            echo "        <tr>\n";
            echo "          <td class=\"postbody\">{$lang['errorpleasewaitandretry']}</td>\n";
            echo "        </tr>\n";
            echo "        <tr>\n";
            echo "          <td>\n";

            foreach ($_POST as $key => $value) {
                echo "<input type=\"hidden\" name=\"{$key}\" value=\"", _htmlentities($value), "\">\n";
            }

            echo "          </td>\n";
            echo "        </tr>\n";
            echo "        <tr>\n";
            echo "          <td align=\"center\"><input class=\"button\" type=\"submit\" name=\"", md5(uniqid(rand())), "\" value=\"{$lang['retry']}\" /></td>\n";
            echo "        </tr>\n";

            if (isset($_GET['retryerror']) && basename($_SERVER['PHP_SELF']) == 'post.php') {

                echo "        <tr>\n";
                echo "          <td>&nbsp;</td>\n";
                echo "        </tr>\n";
                echo "        <tr>\n";
                echo "          <td><hr /></td>\n";
                echo "        </tr>\n";
                echo "        <tr>\n";
                echo "          <td class=\"postbody\">{$lang['multipleerroronpost']}</td>\n";
                echo "        </tr>\n";
                echo "        <tr>\n";
                echo "          <td>&nbsp;</td>\n";
                echo "        </tr>\n";
                echo "        <tr>\n";
                echo "          <td><textarea class=\"bhtextarea\" rows=\"15\" name=\"t_content\" cols=\"85\">", _htmlentities(_stripslashes($_POST['t_content'])), "</textarea></td>\n";
                echo "        </tr>\n";

                if (isset($_GET['replyto']) && validate_msg($_GET['replyto'])) {

                    echo "        <tr>\n";
                    echo "          <td>&nbsp;</td>\n";
                    echo "        </tr>\n";
                    echo "        <tr>\n";
                    echo "          <td class=\"postbody\">{$lang['replymsgnumber']}:</td>\n";
                    echo "        </tr>\n";
                    echo "        <tr>\n";
                    echo "          <td><input class=\"bhinputtext\" type=\"text\" name=\"t_request_url\" value=\"{$_GET['replyto']}\"></td>\n";
                    echo "        </tr>\n";

                }

            }

            echo "        <tr>\n";
            echo "          <td>&nbsp;</td>\n";
            echo "        </tr>\n";
            echo "        <tr>\n";
            echo "          <td><hr /></td>\n";
            echo "        </tr>\n";
            echo "        <tr>\n";
            echo "          <td><h2>{$lang['errormsgfordevs']}:</h2></td>\n";
            echo "        </tr>\n";
            echo "        <tr>\n";
            echo "          <td class=\"postbody\">\n";

	    if ($errstr == BH_DB_CONNECT_ERROR) {

                echo "            <p><b>FATAL</b> [$errno]</p>\n";
                echo "            <p>{$lang['db_connect_error_1']}</p>\n";
                echo "            <p>{$lang['db_connect_error_2']}</p>\n";
		echo "            <pre>\$db_server<br />\$db_username<br />\$db_password<br />\$db_database</pre>\n";
                echo "            <p>{$lang['db_connect_error_3']}</p>\n";

	    }else {

                switch ($errno) {

                    case FATAL:
                        echo "            <p><b>FATAL</b> [$errno] $errstr</p>\n";
                        echo "            <p>Fatal error in line $errline of file $errfile</p>\n";
                        break;
                    case ERROR:
                        echo "            <p><b>ERROR</b> [$errno] $errstr</p>\n";
                        echo "            <p>Error in line $errline of file $errfile</p>\n";
                        break;
                    case WARNING:
                        echo "            <p><b>WARNING</b> [$errno] $errstr</p>\n";
                        echo "            <p>Warning in line $errline of file $errfile</p>\n";
                        break;
                    default:
                        echo "            <p><b>Unknown error</b> [$errno] $errstr</p>\n";
                        echo "            <p>Unknown error in line $errline of file $errfile</p>\n";
                        break;
                }
	    }

	    echo "            <p>Beehive Forum ", BEEHIVE_VERSION, " on PHP/", phpversion(), " ", PHP_OS, " ", strtoupper(php_sapi_name()), "</p>\n";
            echo "          </td>\n";
            echo "        </tr>\n";
            echo "      </table>\n";
            echo "    </td>\n";
            echo "  </tr>\n";
            echo "</table>\n";
            echo "</form>\n";
            echo "</div>\n";
            echo "</body>\n";
            echo "</html>\n";

            die;
        }
    }
}

// Should we enable our error handler?

if (isset($show_friendly_errors) && $show_friendly_errors) {
    set_error_handler("bh_error_handler");
}

?>