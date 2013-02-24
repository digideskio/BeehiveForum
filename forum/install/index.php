<?php

/*======================================================================
Copyright Project Beehive Forum 2002

This file is part of Beehive Forum.

Beehive Forum is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

Beehive Forum is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Beehive; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
USA
======================================================================*/

define("BH_INCLUDE_PATH", "../include/");

date_default_timezone_set('UTC');

require_once BH_INCLUDE_PATH. 'errorhandler.inc.php';

error_reporting(E_ALL | E_STRICT);

set_exception_handler('bh_exception_handler');

set_error_handler('bh_error_handler');

@ini_set('display_errors', '0');

require_once BH_INCLUDE_PATH. 'server.inc.php';

require_once BH_INCLUDE_PATH. 'cache.inc.php';

require_once BH_INCLUDE_PATH. 'install.inc.php';

unregister_globals();

disable_magic_quotes();

set_server_protocol();

cache_disable_aol();

cache_disable_proxy();

install_check_php_version();

install_check_php_extensions();

if (isset($_POST['install_method'])) {

    install_msie_buffer_fix();

    $config = array();

    $config_saved = false;

    $error_array = array();

    $valid = true;

    if (isset($_POST['install_method']) && is_numeric($_POST['install_method'])) {

        $install_method = $_POST['install_method'];

    } else {

        $error_array[] = "You must choose an installation method.\n";
        $valid = false;
    }

    if (isset($_POST['forum_webtag']) && strlen(trim($_POST['forum_webtag'])) > 0) {

        $forum_webtag = mb_strtoupper(trim($_POST['forum_webtag']));

        if (!preg_match("/^[A-Z]{1}[A-Z0-9_]+$/D", $forum_webtag)) {

            $error_array[] = "Forum webtag must start with at least one uppercase letter (A-Z) and only contain the characters A-Z, 0-9 and underscore.\n";
            $valid = false;
        }

        if (strlen(trim($forum_webtag)) > 32) {

            $error_array[] = "Forum webtag must between 1 and 32 characters in length.\n";
            $valid = false;
        }

    } else {

        if (isset($install_method) && ($install_method < 2)) {

            $error_array[] = "Forum webtag must between 1 and 32 characters in length.\n";
            $valid = false;
        }
    }

    if (isset($_POST['db_server']) && strlen(trim($_POST['db_server'])) > 0) {
        $config['db_server'] = trim($_POST['db_server']);
    } else {
        $config['db_server'] = '';
    }

    if (isset($_POST['db_port']) && is_numeric($_POST['db_port'])) {
        $config['db_port'] = $_POST['db_port'];
    } else {
        $config['db_port'] = '';
    }

    if (isset($_POST['db_database']) && strlen(trim($_POST['db_database'])) > 0) {

        $config['db_database'] = trim($_POST['db_database']);

    } else {

        $error_array[] = "You must supply a database name.\n";
        $valid = false;
    }

    if (isset($_POST['db_username']) && strlen(trim($_POST['db_username'])) > 0) {
        $config['db_username'] = trim($_POST['db_username']);
    } else {
        $config['db_username'] = '';
    }

    if (isset($_POST['db_password']) && strlen(trim($_POST['db_password'])) > 0) {
        $config['db_password'] = trim($_POST['db_password']);
    } else {
        $config['db_password'] = '';
    }

    if (isset($_POST['db_cpassword']) && strlen(trim($_POST['db_cpassword'])) > 0) {
        $config['db_cpassword'] = trim($_POST['db_cpassword']);
    } else {
        $config['db_cpassword'] = "";
    }

    if (isset($install_method) && ($install_method < 2)) {

        if (isset($_POST['admin_username']) && strlen(trim($_POST['admin_username'])) > 0) {
            $admin_username = trim($_POST['admin_username']);
        } else {
            $error_array[] = "You must supply a username for your administrator account.\n";
            $valid = false;
        }

        if (isset($_POST['admin_password']) && strlen(trim($_POST['admin_password'])) > 0) {
            $admin_password = trim($_POST['admin_password']);
        } else {
            $error_array[] = "You must supply a password for your administrator account.\n";
            $valid = false;
        }

        if (isset($_POST['admin_cpassword']) && strlen(trim($_POST['admin_cpassword'])) > 0) {
            $admin_cpassword = trim($_POST['admin_cpassword']);
        } else {
            $error_array[] = "You must confirm the password for your administrator account.\n";
            $valid = false;
        }

        if (isset($_POST['admin_email']) && strlen(trim($_POST['admin_email'])) > 0) {
            $admin_email = trim($_POST['admin_email']);
        } else {
            $error_array[] = "You must supply an email address for your administrator account.\n";
            $valid = false;
        }
    }

    if (isset($_POST['remove_conflicts']) && $_POST['remove_conflicts'] == 'Y') {
        $remove_conflicts = true;
    } else {
        $remove_conflicts = false;
    }

    if (isset($_POST['enable_error_reports']) && $_POST['enable_error_reports'] == 'Y') {
        $enable_error_reports = true;
    } else {
        $enable_error_reports = false;
    }

    if ($valid) {

        if (($install_method == 0) && ($admin_password != $admin_cpassword)) {

            $error_array[] = "Administrator account passwords do not match.\n";
            $valid = false;
        }

        if ($config['db_password'] != $config['db_cpassword']) {

            $error_array[] = "MySQL database passwords do not match.\n";
            $valid = false;
        }
    }

    if ($valid) {

        $sql = "";

        try {

            db::set_config($config);

            $db = db::get();

            install_check_mysql_version();

            try {

                if (($install_method == 3) && (@file_exists('upgrade.php'))) {

                    include_once("upgrade.php");

                } else if (@file_exists('new-install.php')) {

                    $remove_conflicts = ($install_method == 1);

                    include_once("new-install.php");
                }

                $config_file = "";

                if (($config_file = @file_get_contents('config.inc.php')) !== false) {

                    $config_file = str_replace('{db_server}',   $config['db_server'],   $config_file);
                    $config_file = str_replace('{db_port}',     $config['db_port'],     $config_file);
                    $config_file = str_replace('{db_username}', $config['db_username'], $config_file);
                    $config_file = str_replace('{db_password}', $config['db_password'], $config_file);
                    $config_file = str_replace('{db_database}', $config['db_database'], $config_file);

                    if (isset($enable_error_reports) && ($enable_error_reports == true)) {
                        $config_file = str_replace('{error_report_email_addr_to}', (isset($admin_email) ? $admin_email : ''), $config_file);
                    } else {
                        $config_file = str_replace('{error_report_email_addr_to}', 'false', $config_file);
                    }

                    if (!defined('BEEHIVE_DEVELOPER_MODE')) {

                        if (@file_put_contents('../include/config.inc.php', $config_file)) {
                            $config_saved = true;
                        }

                    } else {

                        $config_saved = true;
                    }
                }

                install_draw_top();

                if ($config_saved) {

                    echo "<table cellpadding=\"0\" cellspacing=\"0\" width=\"525\">\n";
                    echo "  <tr>\n";
                    echo "    <td align=\"left\" width=\"525\">\n";
                    echo "      <table class=\"box\" width=\"100%\">\n";
                    echo "        <tr>\n";
                    echo "          <td align=\"left\" class=\"posthead\">\n";
                    echo "            <table class=\"posthead\" width=\"100%\">\n";
                    echo "              <tr>\n";
                    echo "                <td align=\"left\" class=\"subhead\">Installation Complete</td>\n";
                    echo "              </tr>\n";
                    echo "              <tr>\n";
                    echo "                <td align=\"center\" colspan=\"2\">\n";
                    echo "                  <table cellpadding=\"2\" cellspacing=\"0\" width=\"95%\">\n";
                    echo "                    <tr>\n";
                    echo "                      <td align=\"left\" class=\"postbody\">Installation of your Beehive Forum has completed successfully, but before you can use it you must delete the install folder. Once this has been done you can click Continue below to start using your Beehive Forum.</td>\n";
                    echo "                    </tr>\n";
                    echo "                    <tr>\n";
                    echo "                      <td align=\"left\" class=\"postbody\">&nbsp;</td>\n";
                    echo "                    </tr>\n";
                    echo "                  </table>\n";
                    echo "                </td>\n";
                    echo "              </tr>\n";
                    echo "            </table>\n";
                    echo "          </td>\n";
                    echo "        </tr>\n";
                    echo "      </table>\n";
                    echo "    </td>\n";
                    echo "  </tr>\n";
                    echo "  <tr>\n";
                    echo "    <td align=\"left\">&nbsp;</td>\n";
                    echo "  </tr>\n";
                    echo "  <tr>\n";
                    echo "    <td align=\"center\"><a class=\"button\" href=\"../index.php\"><span>Continue</span</a></td>\n";
                    echo "  </tr>\n";
                    echo "</table>\n";

                } else {

                    echo "<table cellpadding=\"0\" cellspacing=\"0\" width=\"525\">\n";
                    echo "  <tr>\n";
                    echo "    <td align=\"left\" width=\"525\">\n";
                    echo "      <table class=\"box\" width=\"100%\">\n";
                    echo "        <tr>\n";
                    echo "          <td align=\"left\" class=\"posthead\">\n";
                    echo "            <table class=\"posthead\" width=\"100%\">\n";
                    echo "              <tr>\n";
                    echo "                <td align=\"left\" class=\"subhead\">Database Setup Complete</td>\n";
                    echo "              </tr>\n";
                    echo "              <tr>\n";
                    echo "                <td align=\"center\" colspan=\"2\">\n";
                    echo "                  <table cellpadding=\"2\" cellspacing=\"0\" width=\"95%\">\n";
                    echo "                    <tr>\n";
                    echo "                      <td align=\"left\" class=\"postbody\">Your database has been succesfully setup for use with Beehive. However we were unable to automatically apply the changes to your config.inc.php.</td>\n";
                    echo "                    </tr>\n";
                    echo "                    <tr>\n";
                    echo "                      <td align=\"left\" class=\"postbody\">&nbsp;</td>\n";
                    echo "                    </tr>\n";
                    echo "                    <tr>\n";
                    echo "                      <td align=\"left\" class=\"postbody\">In order to complete the installation you will need to save a copy of your config.inc.php to your hard disc drive by clicking the 'Download Config' button below and from there upload it to your server into Beehive's 'include' folder. After you have successfully uploaded your config.inc.php you must delete the install folder. Once this has been done you can click Continue below to start using your Beehive Forum.</td>\n";
                    echo "                    </tr>\n";
                    echo "                    <tr>\n";
                    echo "                      <td align=\"left\" class=\"postbody\">&nbsp;</td>\n";
                    echo "                    </tr>\n";
                    echo "                  </table>\n";
                    echo "                </td>\n";
                    echo "              </tr>\n";
                    echo "            </table>\n";
                    echo "          </td>\n";
                    echo "        </tr>\n";
                    echo "      </table>\n";
                    echo "    </td>\n";
                    echo "  </tr>\n";
                    echo "  <tr>\n";
                    echo "    <td align=\"left\">&nbsp;</td>\n";
                    echo "  </tr>\n";
                    echo "  <tr>\n";
                    echo "    <td align=\"center\">\n";
                    echo "      <table cellpadding=\"0\" cellspacing=\"0\" width=\"525\">\n";
                    echo "        <tr>\n";
                    echo "          <td width=\"55%\" align=\"right\">\n";
                    echo "            <form accept-charset=\"utf-8\" method=\"post\" action=\"index.php\">\n";
                    echo "              <input type=\"hidden\" name=\"db_server\" value=\"", htmlentities_array($config['db_server']), "\">\n";
                    echo "              <input type=\"hidden\" name=\"db_port\" value=\"", htmlentities_array($config['db_port']), "\">\n";
                    echo "              <input type=\"hidden\" name=\"db_username\" value=\"", htmlentities_array($config['db_username']), "\">\n";
                    echo "              <input type=\"hidden\" name=\"db_password\" value=\"", htmlentities_array($config['db_password']), "\">\n";
                    echo "              <input type=\"hidden\" name=\"db_database\" value=\"", htmlentities_array($config['db_database']), "\">\n";
                    echo "              <input type=\"hidden\" name=\"admin_email\" value=\"", isset($admin_email) ? htmlentities_array($admin_email) : '', "\">\n";
                    echo "              <input type=\"hidden\" name=\"enable_error_reports\" value=\"", $enable_error_reports ? 'Y' : 'N', "\">\n";
                    echo "              <input type=\"submit\" name=\"download_config\" value=\"Download Config\" class=\"button\" />&nbsp;\n";
                    echo "            </form>\n";
                    echo "          </td>\n";
                    echo "          <td align=\"left\" width=\"45%\">\n";
                    echo "            <a class=\"button\" href=\"../index.php\" /><span>Continue</span></a>\n";
                    echo "          </td>\n";
                    echo "        </tr>\n";
                    echo "      </table>\n";
                    echo "    </td>\n";
                    echo "  </tr>\n";
                    echo "</table>\n";
                }

                install_draw_bottom();
                exit;

            } catch (Exception $e) {

                $error_array[] = "<h2>Could not complete installation. Error was: ". $e->getMessage(). "</h2>\n";
            }

        } catch (Exception $e) {

            $error_array[] = "<p>Database connection to ". htmlentities_array($config['db_server']). ":". htmlentities_array($config['db_port']). " could not be established. Please check your MySQL Database Configuration settings are correct and that you have permisison to access the database you've entered.</p>\n<p><b>Note:</b> The database must be created manually prior to the installation of the Beehive Forum software!</p>\n";
        }
    }

} else if (isset($_POST['download_config'])) {

    $config_file = "";

    if (($config_file = @file_get_contents('config.inc.php')) !== false) {

        if (isset($_POST['db_server']) && strlen(trim($_POST['db_server'])) > 0) {
            $config['db_server'] = trim($_POST['db_server']);
        }

        if (isset($_POST['db_port']) && is_numeric($_POST['db_port'])) {
            $config['db_port'] = $_POST['db_port'];
        }

        if (isset($_POST['db_database']) && strlen(trim($_POST['db_database'])) > 0) {
            $config['db_database'] = trim($_POST['db_database']);
        }

        if (isset($_POST['db_username']) && strlen(trim($_POST['db_username'])) > 0) {
            $config['db_username'] = trim($_POST['db_username']);
        }

        if (isset($_POST['db_password']) && strlen(trim($_POST['db_password'])) > 0) {
            $config['db_password'] = trim($_POST['db_password']);
        }

        if (isset($_POST['admin_email']) && strlen(trim($_POST['admin_email'])) > 0) {
            $admin_email = trim($_POST['admin_email']);
        }

        if (isset($_POST['enable_error_reports']) && ($_POST['enable_error_reports'] == 'Y')) {
            $enable_error_reports = true;
        } else {
            $enable_error_reports = false;
        }

        if (isset($config['db_server'], $config['db_port'], $config['db_database'], $config['db_username'], $config['db_password'])) {

            $config_file = str_replace('{db_server}',   $config['db_server'],   $config_file);
            $config_file = str_replace('{db_port}',     $config['db_port'],     $config_file);
            $config_file = str_replace('{db_username}', $config['db_username'], $config_file);
            $config_file = str_replace('{db_password}', $config['db_password'], $config_file);
            $config_file = str_replace('{db_database}', $config['db_database'], $config_file);

            if (isset($enable_error_reports) && ($enable_error_reports == true)) {
                $config_file = str_replace('{error_report_email_addr_to}', (isset($admin_email) ? $admin_email : ''), $config_file);
            } else {
                $config_file = str_replace('{error_report_email_addr_to}', 'false', $config_file);
            }

            header("Content-Type: text/plain; name=\"config.inc.php\"");
            header("Content-disposition: attachment; filename=\"config.inc.php\"");

            echo $config_file;
            exit;

        } else {

            $config_file = str_replace('{db_server}',   "", $config_file);
            $config_file = str_replace('{db_port}',     "", $config_file);
            $config_file = str_replace('{db_database}', "", $config_file);
            $config_file = str_replace('{db_username}', "", $config_file);
            $config_file = str_replace('{db_password}', "", $config_file);

            install_msie_buffer_fix();

            install_draw_top();

            echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"525\">\n";
            echo "    <tr>\n";
            echo "      <td align=\"left\" width=\"525\">\n";
            echo "        <table class=\"box\" width=\"100%\">\n";
            echo "          <tr>\n";
            echo "            <td align=\"left\" class=\"posthead\">\n";
            echo "              <table class=\"posthead\" width=\"100%\">\n";
            echo "                <tr>\n";
            echo "                  <td align=\"left\" class=\"subhead\">Config Download Failed</td>\n";
            echo "                </tr>\n";
            echo "                <tr>\n";
            echo "                  <td align=\"center\" colspan=\"2\">\n";
            echo "                    <table cellpadding=\"2\" cellspacing=\"0\" width=\"95%\">\n";
            echo "                      <tr>\n";
            echo "                        <td align=\"left\" class=\"postbody\">Oops! It would appear that we don't have enough information to be able to send you your config.inc.php. This would only have happened if the previous page didn't send us the right information.</td>\n";
            echo "                      </tr>\n";
            echo "                      <tr>\n";
            echo "                        <td align=\"left\" class=\"postbody\">&nbsp;</td>\n";
            echo "                      </tr>\n";
            echo "                      <tr>\n";
            echo "                        <td align=\"left\" class=\"postbody\">Fortunately you can still get your Beehive Forum functional by following these simple instructions:</td>\n";
            echo "                      </tr>\n";
            echo "                      <tr>\n";
            echo "                        <td align=\"left\" class=\"postbody\">\n";
            echo "                          <ol>\n";
            echo "                            <li><p>Copy and paste the text in the box below into a text editor.</p></li>\n";
            echo "                            <li><p>Edit the \$config['db_server'], \$config['db_port'], \$config['db_database'], \$config['db_username'] and \$config['db_password'] entries near the top of the script to match those that you entered in the first step of this installation</p></li>\n";
            echo "                            <li><p>Save the file as config.inc.php (all in lowercase) and upload it to the 'include' folder of your Beehive installation.</p></li>\n";
            echo "                            <li><p>Delete the 'install' folder from the Beehive ditribution on your server.</p></li>\n";
            echo "                          </ol>\n";
            echo "                        </td>\n";
            echo "                      </tr>\n";
            echo "                      <tr>\n";
            echo "                        <td align=\"left\" class=\"postbody\">&nbsp;</td>\n";
            echo "                      </tr>\n";
            echo "                      <tr>\n";
            echo "                        <td align=\"left\" class=\"postbody\">Once you've done all of that you can click the Continue button below to start using your Beehive Forum.</td>\n";
            echo "                      </tr>\n";
            echo "                      <tr>\n";
            echo "                        <td align=\"left\" class=\"postbody\">&nbsp;</td>\n";
            echo "                      </tr>\n";
            echo "                      <tr>\n";
            echo "                        <td align=\"left\" class=\"postbody\"><b>config.inc.php:</b></td>\n";
            echo "                      </tr>\n";
            echo "                      <tr>\n";
            echo "                        <td align=\"center\"><textarea name=\"config_file\" rows=\"20\" cols=\"56\" wrap=\"off\">$config_file</textarea></td>\n";
            echo "                      </tr>\n";
            echo "                    </table>\n";
            echo "                  </td>\n";
            echo "                </tr>\n";
            echo "              </table>\n";
            echo "            </td>\n";
            echo "          </tr>\n";
            echo "        </table>\n";
            echo "      </td>\n";
            echo "    </tr>\n";
            echo "  </table>\n";
            echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"525\">\n";
            echo "    <tr>\n";
            echo "      <td align=\"left\" width=\"525\">&nbsp;</td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "      <td align=\"center\">\n";
            echo "        <a class=\"button\" href=\"../index.php\" /><span>Continue</span></a>\n";
            echo "      </td>\n";
            echo "    </tr>\n";
            echo "  </table>\n";

            install_draw_bottom();
            exit;
        }

    } else {

        $error_array[] = "Could not complete installation. Error was: failed to read config.inc.php\n";
    }
}

install_draw_top();

echo "<form accept-charset=\"utf-8\" id=\"install_form\" method=\"post\" action=\"index.php\">\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"525\">\n";
echo "    <tr>\n";
echo "      <td align=\"left\" colspan=\"2\">\n";
echo "        <p>Welcome to the Beehive Forum installation script. To get everything kicking off to a great start please fill out the details below and click the Install button!</p>\n";
echo "        <p><b>WARNING</b>: Proceed only if you have performed a backup of your database! Failure to do so could result in loss of your forum. You have been warned!</p>\n";
echo "      </td>\n";
echo "    </tr>\n";

if (isset($error_array) && sizeof($error_array) > 0) {

    echo "    <tr>\n";
    echo "      <td align=\"left\" colspan=\"2\"><hr /></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td align=\"left\"><img src=\"../styles/default/images/warning.png\" alt=\"Warning\" title=\"Warning\" /></td>\n";
    echo "      <td align=\"left\"><h2>The following errors need correcting before you continue</h2></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td align=\"left\" colspan=\"2\">\n";
    echo "        <ul>\n";
    echo "          <li>", implode("</li><li>", $error_array), "</li>\n";
    echo "        </ul>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
}

echo "  </table>\n";
echo "  <br />\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"525\">\n";
echo "    <tr>\n";
echo "      <td align=\"left\" width=\"525\">\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td align=\"left\" class=\"posthead\">\n";
echo "              <table cellpadding=\"2\" cellspacing=\"0\" class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td align=\"left\" style=\"white-space: nowrap\" class=\"subhead\">Basic Configuration</td>\n";
echo "                  <td style=\"white-space: nowrap\" class=\"subhead\" align=\"right\"><img src=\"../styles/default/images/help.png\" border=\"0\" alt=\"Help!\" title=\"Help!\" class=\"install_help_icon\" id=\"help_basic\" /></td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\" colspan=\"2\">\n";
echo "                    <table cellpadding=\"2\" cellspacing=\"0\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"220\" class=\"postbody\">Installation Method:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\">\n";
echo "                          <select name=\"install_method\" id =\"install_method\" class=\"install_input\" tabindex=\"1\">\n";
echo "                            <option value=\"\">Please select...</option>\n";
echo "                            <option value=\"0\" ", (isset($install_method) && $install_method == 0) ? "selected=\"selected\"" : "", ">New Install</option>\n";
echo "                            <option value=\"1\" ", (isset($install_method) && $install_method == 1) ? "selected=\"selected\"" : "", ">Reinstall</option>\n";
echo "                            <option value=\"2\" ", (isset($install_method) && $install_method == 2) ? "selected=\"selected\"" : "", ">Reconnect</option>\n";
echo "                            <option value=\"3\" ", (isset($install_method) && $install_method == 3) ? "selected=\"selected\"" : "", ">Upgrade 1.3.0 to 1.3.1</option>\n";
echo "                          </select>\n";
echo "                        </td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"200\" valign=\"top\" class=\"postbody\">Default Forum Webtag:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\"><input type=\"text\" name=\"forum_webtag\" class=\"bhinputtext install_input\" value=\"", (isset($forum_webtag) && strlen($forum_webtag) > 0 ? htmlentities_array($forum_webtag) : ''), "\" size=\"28\" maxlength=\"32\" tabindex=\"2\" /></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" class=\"postbody\" colspan=\"2\">&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                    </table>\n";
echo "                  </td>\n";
echo "                </tr>\n";
echo "              </table>\n";
echo "            </td>\n";
echo "          </tr>\n";
echo "        </table>\n";
echo "      </td>\n";
echo "    </tr>\n";
echo "  </table>\n";
echo "  <br />\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"525\">\n";
echo "    <tr>\n";
echo "      <td align=\"left\" width=\"525\">\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td align=\"left\" class=\"posthead\">\n";
echo "              <table cellpadding=\"2\" cellspacing=\"0\" class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td align=\"left\" style=\"white-space: nowrap\" class=\"subhead\">MySQL Database Configuration</td>\n";
echo "                  <td style=\"white-space: nowrap\" class=\"subhead\" align=\"right\"><img src=\"../styles/default/images/help.png\" border=\"0\" alt=\"Help!\" title=\"Help!\" class=\"install_help_icon\" id=\"help_database\" /></td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\" colspan=\"2\">\n";
echo "                    <table cellpadding=\"2\" cellspacing=\"0\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"220\" class=\"postbody\">Hostname:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\"><input type=\"text\" name=\"db_server\" class=\"bhinputtext install_input\" value=\"", (isset($config['db_server']) && strlen($config['db_server']) > 0 ? htmlentities_array($config['db_server']) : ''), "\" size=\"28\" tabindex=\"3\" /></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"220\" class=\"postbody\">Port:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\"><input type=\"text\" name=\"db_port\" class=\"bhinputtext install_input\" value=\"", (isset($config['db_port']) && strlen($config['db_port']) > 0 ? htmlentities_array($config['db_port']) : '3306'), "\" size=\"28\" tabindex=\"3\" /></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"220\" class=\"postbody\">Database Name:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\"><input type=\"text\" name=\"db_database\" class=\"bhinputtext install_input\" value=\"", (isset($config['db_database']) && strlen($config['db_database']) > 0 ? htmlentities_array($config['db_database']) : ''), "\" size=\"28\" tabindex=\"4\" /></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"220\" class=\"postbody\">Username:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\"><input type=\"text\" name=\"db_username\" class=\"bhinputtext install_input\" value=\"", (isset($config['db_username']) && strlen($config['db_username']) > 0 ? htmlentities_array($config['db_username']) : ''), "\" size=\"28\" tabindex=\"5\" /></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"220\" class=\"postbody\">Password:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\"><input type=\"password\" name=\"db_password\" class=\"bhinputtext install_input\" value=\"\" size=\"28\" tabindex=\"6\" /></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"220\" class=\"postbody\">Confirm Password:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\"><input type=\"password\" name=\"db_cpassword\" class=\"bhinputtext install_input\" value=\"\" size=\"28\" tabindex=\"7\" /></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" class=\"postbody\" colspan=\"2\">&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                    </table>\n";
echo "                  </td>\n";
echo "                </tr>\n";
echo "              </table>\n";
echo "            </td>\n";
echo "          </tr>\n";
echo "        </table>\n";
echo "      </td>\n";
echo "    </tr>\n";
echo "  </table>\n";
echo "  <br />\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"525\">\n";
echo "    <tr>\n";
echo "      <td align=\"left\" width=\"525\">\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td align=\"left\" class=\"posthead\">\n";
echo "              <table cellpadding=\"2\" cellspacing=\"0\" class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td align=\"left\" style=\"white-space: nowrap\" class=\"subhead\">Admin Account (New installs only)</td>\n";
echo "                  <td style=\"white-space: nowrap\" class=\"subhead\" align=\"right\"><img src=\"../styles/default/images/help.png\" border=\"0\" alt=\"Help!\" title=\"Help!\" class=\"install_help_icon\" id=\"help_admin\" /></td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\" colspan=\"2\">\n";
echo "                    <table cellpadding=\"2\" cellspacing=\"0\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"220\" class=\"postbody\">Admin Username:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\"><input type=\"text\" name=\"admin_username\" class=\"bhinputtext install_input\" value=\"", (isset($admin_username) && strlen($admin_username) > 0 ? htmlentities_array($admin_username) : ''), "\" size=\"28\" maxlength=\"32\" tabindex=\"8\" /></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"220\" class=\"postbody\">Admin Email Address:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\"><input type=\"text\" name=\"admin_email\" class=\"bhinputtext install_input\" value=\"", (isset($admin_email) && strlen($admin_email) > 0 ? htmlentities_array($admin_email) : ''), "\" size=\"28\" maxlength=\"80\" tabindex=\"9\" /></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"220\" class=\"postbody\">Admin Password:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\"><input type=\"password\" name=\"admin_password\" class=\"bhinputtext install_input\" value=\"\" size=\"28\" maxlength=\"32\" tabindex=\"10\" /></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"220\" class=\"postbody\">Confirm Password:</td>\n";
echo "                        <td align=\"left\" class=\"postbody\"><input type=\"password\" name=\"admin_cpassword\" class=\"bhinputtext install_input\" value=\"\" size=\"28\" maxlength=\"32\" tabindex=\"11\" /></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" class=\"postbody\" colspan=\"2\">&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                    </table>\n";
echo "                  </td>\n";
echo "                </tr>\n";
echo "              </table>\n";
echo "            </td>\n";
echo "          </tr>\n";
echo "        </table>\n";
echo "      </td>\n";
echo "    </tr>\n";
echo "  </table>\n";
echo "  <br />\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"525\">\n";
echo "    <tr>\n";
echo "      <td align=\"left\" width=\"525\">\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td align=\"left\" class=\"posthead\">\n";
echo "              <table cellpadding=\"2\" cellspacing=\"0\" class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td align=\"left\" style=\"white-space: nowrap\" class=\"subhead\">Advanced Options</td>\n";
echo "                  <td style=\"white-space: nowrap\" class=\"subhead\" align=\"right\"><img src=\"../styles/default/images/help.png\" border=\"0\" alt=\"Help!\" title=\"Help!\" class=\"install_help_icon\" id=\"help_advanced\" /></td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\" colspan=\"2\">\n";
echo "                    <table cellpadding=\"2\" cellspacing=\"0\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" class=\"postbody\"><span class=\"bhinputcheckbox\"><input type=\"checkbox\" name=\"remove_conflicts\" id=\"remove_conflicts\" value=\"Y\" tabindex=\"12\"", (isset($remove_conflicts) && $remove_conflicts == 'Y' ? " checked=\"checked\"" : ""), " /><label for=\"remove_conflicts\">Automatically remove tables that conflict with Beehive Forum's own.</label></span></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" class=\"postbody\"><span class=\"bhinputcheckbox\"><input type=\"checkbox\" name=\"enable_error_reports\" id=\"enable_error_reports\" value=\"Y\" tabindex=\"14\"", (isset($enable_error_reports) && $enable_error_reports == 'Y' ? " checked=\"checked\"" : ""), " /><label for=\"enable_error_reports\">Send error reports to Admin email address.</label></span></td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" class=\"postbody\" colspan=\"2\">&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                    </table>\n";
echo "                  </td>\n";
echo "                </tr>\n";
echo "              </table>\n";
echo "            </td>\n";
echo "          </tr>\n";
echo "        </table>\n";
echo "      </td>\n";
echo "    </tr>\n";
echo "    <tr>\n";
echo "      <td align=\"left\">&nbsp;</td>\n";
echo "    </tr>\n";
echo "    <tr>\n";
echo "      <td align=\"left\"><p>The installation process may take several minutes to complete. Please click the Install button once and once only. Clicking it multiple times may cause your installation to become corrupted.</p></td>\n";
echo "    </tr>\n";
echo "    <tr>\n";
echo "      <td align=\"left\">&nbsp;</td>\n";
echo "    </tr>\n";
echo "    <tr>\n";
echo "      <td align=\"center\"><input type=\"submit\" name=\"install\" id=\"install_button\" value=\"Install\" class=\"button\" tabindex=\"15\" /></td>\n";
echo "    </tr>\n";
echo "  </table>\n";
echo "</form>\n";

install_draw_bottom();

?>