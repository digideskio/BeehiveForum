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

/* $Id: admin_forum_settings.php,v 1.56 2005-02-09 21:45:33 decoyduck Exp $ */

// Compress the output
include_once("./include/gzipenc.inc.php");

// Enable the error handler
include_once("./include/errorhandler.inc.php");

// Installation checking functions
include_once("./include/install.inc.php");

// Check that Beehive is installed correctly
check_install();

// Multiple forum support
include_once("./include/forum.inc.php");

// Fetch the forum settings
$forum_settings = get_forum_settings();

include_once("./include/admin.inc.php");
include_once("./include/emoticons.inc.php");
include_once("./include/form.inc.php");
include_once("./include/header.inc.php");
include_once("./include/html.inc.php");
include_once("./include/lang.inc.php");
include_once("./include/logon.inc.php");
include_once("./include/post.inc.php");
include_once("./include/session.inc.php");
include_once("./include/styles.inc.php");
include_once("./include/user.inc.php");

// Check we're logged in correctly

if (!$user_sess = bh_session_check()) {
    $request_uri = rawurlencode(get_request_uri(true));
    $webtag = get_webtag($webtag_search);
    header_redirect("./logon.php?webtag=$webtag&final_uri=$request_uri");
}

// Check we have a webtag

if (!$webtag = get_webtag($webtag_search)) {
    $request_uri = rawurlencode(get_request_uri(true));
    header_redirect("./forums.php?webtag_search=$webtag_search&final_uri=admin.php%3Fpage%3D$request_uri");
}

// Load language file

$lang = load_language_file();

if (!perm_has_forumtools_access()) {
    html_draw_top();
    echo "<h1>{$lang['accessdenied']}</h1>\n";
    echo "<p>{$lang['accessdeniedexp']}</p>";
    html_draw_bottom();
    exit;
}

$error_html = "";
// Languages

$available_langs = lang_get_available(); // get list of available languages

if (isset($_POST['submit'])) {

    $valid = true;

    if (isset($_POST['forum_name']) && strlen(trim(_stripslashes($_POST['forum_name']))) > 0) {
        $new_forum_settings['forum_name'] = trim(_stripslashes($_POST['forum_name']));
    }else {
        $error_html = "<h2>{$lang['mustsupplyforumname']}</h2>\n";
        $valid = false;
    }

    if (isset($_POST['forum_email']) && strlen(trim(_stripslashes($_POST['forum_email']))) > 0) {
        $new_forum_settings['forum_email'] = trim(_stripslashes($_POST['forum_email']));
    }else {
        $error_html = "<h2>{$lang['mustsupplyforumemail']}</h2>\n";
        $valid = false;
    }

    if (isset($_POST['forum_desc']) && strlen(trim(_stripslashes($_POST['forum_desc']))) > 0) {
        $new_forum_settings['forum_desc'] = trim(_stripslashes($_POST['forum_desc']));
    }else {
        $new_forum_settings['forum_desc'] = "";
    }

    if (isset($_POST['forum_keywords']) && strlen(trim(_stripslashes($_POST['forum_keywords']))) > 0) {
        $new_forum_settings['forum_keywords'] = trim(_stripslashes($_POST['forum_keywords']));
    }else {
        $new_forum_settings['forum_keywords'] = "";
    }

    if (isset($_POST['default_style']) && strlen(trim(_stripslashes($_POST['default_style']))) > 0) {

        $new_forum_settings['default_style'] = trim(_stripslashes($_POST['default_style']));

        if (!style_exists($new_forum_settings['default_style'])) {

            $error_html = "<h2>{$lang['unknownstylename']}</h2>\n";
            $valid = false;
        }

    }else {

        $error_html = "<h2>{$lang['mustchoosedefaultstyle']}</h2>\n";
        $valid = false;
    }

    if (isset($_POST['default_emoticons']) && strlen(trim(_stripslashes($_POST['default_emoticons']))) > 0) {

        $new_forum_settings['default_emoticons'] = trim(_stripslashes($_POST['default_emoticons']));

        if (!emoticons_set_exists($new_forum_settings['default_emoticons'])) {
            $error_html = "<h2>{$lang['unknownemoticonsname']}</h2>\n";
            $valid = false;
        }

    }else {

        $error_html = "<h2>{$lang['mustchoosedefaultemoticons']}</h2>\n";
        $valid = false;
    }

    if (isset($_POST['default_language']) && strlen(trim(_stripslashes($_POST['default_language']))) > 0) {

        $new_forum_settings['default_language'] = trim(_stripslashes($_POST['default_language']));

        if (!_in_array($new_forum_settings['default_language'], $available_langs)) {

            $error_html = "<h2>{$lang['unknownlanguage']}</h2>\n";
            $valid = false;
        }

    }else {

        $error_html = "<h2>{$lang['mustchoosedefaultlang']}</h2>\n";
        $valid = false;
    }

    if (isset($_POST['require_post_approval']) && $_POST['require_post_approval'] == "Y") {
        $new_forum_settings['require_post_approval'] = "Y";
    }else {
        $new_forum_settings['require_post_approval'] = "N";
    }

    if (isset($_POST['allow_post_editing']) && $_POST['allow_post_editing'] == "Y") {
        $new_forum_settings['allow_post_editing'] = "Y";
    }else {
        $new_forum_settings['allow_post_editing'] = "N";
    }

    if (isset($_POST['post_edit_time']) && is_numeric($_POST['post_edit_time'])) {
        $new_forum_settings['post_edit_time'] = $_POST['post_edit_time'];
    }else {
        $new_forum_settings['post_edit_time'] = 0;
    }

    if (isset($_POST['maximum_post_length']) && is_numeric($_POST['maximum_post_length'])) {
        $new_forum_settings['maximum_post_length'] = $_POST['maximum_post_length'];
    }else {
        $new_forum_settings['maximum_post_length'] = 6226;
    }

    if (isset($_POST['enable_wiki_integration']) && $_POST['enable_wiki_integration'] == "Y") {
        $new_forum_settings['enable_wiki_integration'] = "Y";
    }else {
        $new_forum_settings['enable_wiki_integration'] = "N";
    }

    if (isset($_POST['wiki_integration_uri']) && strlen(trim(_stripslashes($_POST['wiki_integration_uri']))) > 0) {
        $new_forum_settings['wiki_integration_uri'] = trim(_stripslashes($_POST['wiki_integration_uri']));
    }else {
        $new_forum_settings['wiki_integration_uri'] = "";
    }

    if (isset($_POST['show_links']) && $_POST['show_links'] == "Y") {
        $new_forum_settings['show_links'] = "Y";
    }else {
        $new_forum_settings['show_links'] = "N";
    }

    if (isset($_POST['allow_polls']) && $_POST['allow_polls'] == "Y") {
        $new_forum_settings['allow_polls'] = "Y";
    }else {
        $new_forum_settings['allow_polls'] = "N";
    }

    if (isset($_POST['show_stats']) && $_POST['show_stats'] == "Y") {
        $new_forum_settings['show_stats'] = "Y";
    }else {
        $new_forum_settings['show_stats'] = "N";
    }

    if (isset($_POST['allow_search_spidering']) && $_POST['allow_search_spidering'] == "Y") {
        $new_forum_settings['allow_search_spidering'] = "Y";
    }else {
        $new_forum_settings['allow_search_spidering'] = "N";
    }

    if (isset($_POST['guest_account_enabled']) && $_POST['guest_account_enabled'] == "Y") {
        $new_forum_settings['guest_account_enabled'] = "Y";
    }else {
        $new_forum_settings['guest_account_enabled'] = "N";
    }

    if (isset($_POST['auto_logon']) && $_POST['auto_logon'] == "Y") {
        $new_forum_settings['auto_logon'] = "Y";
    }else {
        $new_forum_settings['auto_logon'] = "N";
    }

    if ($valid) {

        save_forum_settings($new_forum_settings);

        $uid = bh_session_get_value('UID');
        admin_addlog($uid, 0, 0, 0, 0, 0, 29);

        if (isset($_SERVER['SERVER_SOFTWARE']) && !strstr($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS')) {
            header_redirect("./admin_forum_settings.php?webtag=$webtag&updated=true");

        }else {

            html_draw_top();

            // Try a Javascript redirect
            echo "<script language=\"javascript\" type=\"text/javascript\">\n";
            echo "<!--\n";
            echo "document.location.href = './admin_forum_settings.php?webtag=$webtag&amp;updated=true';\n";
            echo "//-->\n";
            echo "</script>";
            // If they're still here, Javascript's not working. Give up, give a link.
            echo "<div align=\"center\"><p>&nbsp;</p><p>&nbsp;</p>";
            echo "<p>{$lang['forumsettingsupdated']}</p>";
            form_quick_button("./admin_forum_settings.php", $lang['continue'], false, false, "_top");

            html_draw_bottom();
            exit;
        }
    }
}

// Get the available forum styles and emoticons

$available_styles = styles_get_available();
$available_emoticons = emoticons_get_available();

// Start Output Here

html_draw_top("emoticons.js");

if ($webtag) {
    echo "<h1>{$lang['forumsettings']} : ", forum_get_setting('forum_name', false, 'Unknown Forum'), "</h1>\n";
}else {
    html_draw_top();
    echo "<h1>{$lang['error']}</h1>\n";
    html_draw_bottom();
    exit;
}

// Any error messages to display?

if (!empty($error_html)) {
    echo $error_html;
}else if (isset($_GET['updated'])) {
    echo "<h2>{$lang['forumsettingsupdated']}</h2>\n";
}

echo "<br />\n";
echo "<form name=\"prefs\" action=\"admin_forum_settings.php\" method=\"post\" target=\"_self\">\n";
echo "  ", form_input_hidden('webtag', $webtag), "\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"550\">\n";
echo "    <tr>\n";
echo "      <td>\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td class=\"subhead\" colspan=\"2\">{$lang['mainsettings']}</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['forumname']}:</td>\n";
echo "                        <td>", form_input_text("forum_name", forum_get_setting('forum_name', false, 'A Beehive Forum'), 42, 32), "&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['forumemail']}:</td>\n";
echo "                        <td>", form_input_text("forum_email", forum_get_setting('forum_email', false, 'admin@abeehiveforum.net'), 42, 80), "&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['forumdesc']}:</td>\n";
echo "                        <td>", form_input_text("forum_desc", forum_get_setting('forum_desc', false, ''), 42, 80), "&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['forumkeywords']}:</td>\n";
echo "                        <td>", form_input_text("forum_keywords", forum_get_setting('forum_keywords', false, ''), 42, 80), "&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['defaultstyle']}:</td>\n";
echo "                        <td>", form_dropdown_array("default_style", array_keys($available_styles), array_values($available_styles), forum_get_setting('default_style', false, 'Beehive')), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['defaultemoticons']} [<a href=\"javascript:void(0);\" onclick=\"openEmoticons('','$webtag')\" target=\"_self\">{$lang['preview']}</a>]:</td>\n";
echo "                        <td>", form_dropdown_array("default_emoticons", array_keys($available_emoticons), array_values($available_emoticons), forum_get_setting('default_emoticons')), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['defaultlanguage']}:</td>\n";
echo "                        <td>", form_dropdown_array("default_language", $available_langs, $available_langs, forum_get_setting('default_language', false, 'en')), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">&nbsp;</td>\n";
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
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"550\">\n";
echo "    <tr>\n";
echo "      <td>\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['postoptions']}</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['requirepostapproval']}</td>\n";
echo "                        <td>", form_radio("require_post_approval", "Y", $lang['yes'], (isset($forum_settings['require_post_approval']) && $forum_settings['require_post_approval'] == "Y")), "&nbsp;", form_radio("require_post_approval", "N", $lang['no'], (isset($forum_settings['require_post_approval']) && $forum_settings['require_post_approval'] == "N") || !isset($forum_settings['require_post_approval'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['allowpostoptions']}</td>\n";
echo "                        <td>", form_radio("allow_post_editing", "Y", $lang['yes'], (isset($forum_settings['allow_post_editing']) && $forum_settings['allow_post_editing'] == "Y")), "&nbsp;", form_radio("allow_post_editing", "N", $lang['no'], (isset($forum_settings['allow_post_editing']) && $forum_settings['allow_post_editing'] == "N") || !isset($forum_settings['allow_post_editing'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['postedittimeout']}:</td>\n";
echo "                        <td>", form_input_text("post_edit_time", forum_get_setting('post_edit_time', false, '0'), 20, 32), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['maximumpostlength']}:</td>\n";
echo "                        <td>", form_input_text("maximum_post_length", forum_get_setting('maximum_post_length', false, '6226'), 20, 32), "&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_10']}</p>\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_11']}</p>\n";
echo "                        </td>\n";
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
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"550\">\n";
echo "    <tr>\n";
echo "      <td>\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['wikiintegration']}</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['enablewikiintegration']}</td>\n";
echo "                        <td>", form_radio("enable_wiki_integration", "Y", $lang['yes'], (isset($forum_settings['enable_wiki_integration']) && $forum_settings['enable_wiki_integration'] == "Y")), "&nbsp;", form_radio("enable_wiki_integration", "N", $lang['no'], (isset($forum_settings['enable_wiki_integration']) && $forum_settings['enable_wiki_integration'] == "N") || !isset($forum_settings['enable_wiki_integration'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['wikiintegrationuri']}:</td>\n";
echo "                        <td>", form_input_text("wiki_integration_uri", forum_get_setting('wiki_integration_uri', false, 'http://en.wikipedia.org/wiki/[WikiWord]'), 42, 255), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_30']}</p>\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_31']}</p>\n";
echo "                        </td>\n";
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
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"550\">\n";
echo "    <tr>\n";
echo "      <td>\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['links']}</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['enablelinkssection']}</td>\n";
echo "                        <td>", form_radio("show_links", "Y", $lang['yes'], (isset($forum_settings['show_links']) && $forum_settings['show_links'] == "Y")), "&nbsp;", form_radio("show_links", "N", $lang['no'], (isset($forum_settings['show_links']) && $forum_settings['show_links'] == "N") || !isset($forum_settings['show_links'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_13']}</p>\n";
echo "                        </td>\n";
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
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"550\">\n";
echo "    <tr>\n";
echo "      <td>\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['polls']}</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['allowcreationofpolls']}</td>\n";
echo "                        <td>", form_radio("allow_polls", "Y", $lang['yes'], (isset($forum_settings['allow_polls']) && $forum_settings['allow_polls'] == "Y")), "&nbsp;", form_radio("allow_polls", "N", $lang['no'], (isset($forum_settings['allow_polls']) && $forum_settings['allow_polls'] == "N") || !isset($forum_settings['allow_polls'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_12']}</p>\n";
echo "                        </td>\n";
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
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"550\">\n";
echo "    <tr>\n";
echo "      <td>\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['stats']}</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['enablestatsdisplay']}</td>\n";
echo "                        <td>", form_radio("show_stats", "Y", $lang['yes'], (isset($forum_settings['show_stats']) && $forum_settings['show_stats'] == "Y")), "&nbsp;", form_radio("show_stats", "N", $lang['no'], (isset($forum_settings['show_stats']) && $forum_settings['show_stats'] == "N") || !isset($forum_settings['show_stats'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_17']}</p>\n";
echo "                        </td>\n";
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
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"550\">\n";
echo "    <tr>\n";
echo "      <td>\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['searchenginespidering']}</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['allowsearchenginespidering']}</td>\n";
echo "                        <td>", form_radio("allow_search_spidering", "Y", $lang['yes'], (isset($forum_settings['allow_search_spidering']) && $forum_settings['allow_search_spidering'] == "Y")), "&nbsp;", form_radio("allow_search_spidering", "N", $lang['no'], (isset($forum_settings['allow_search_spidering']) && $forum_settings['allow_search_spidering'] == "N") || !isset($forum_settings['allow_search_spidering'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_28']}</p>\n";
echo "                        </td>\n";
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
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"550\">\n";
echo "    <tr>\n";
echo "      <td>\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['guestaccount']}</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['enableguestaccount']}</td>\n";
echo "                        <td>", form_radio("guest_account_enabled", "Y", $lang['yes'], (isset($forum_settings['guest_account_enabled']) && $forum_settings['guest_account_enabled'] == "Y")), "&nbsp;", form_radio("guest_account_enabled", "N", $lang['no'], (isset($forum_settings['guest_account_enabled']) && $forum_settings['guest_account_enabled'] == "N") || !isset($forum_settings['guest_account_enabled'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"220\">{$lang['autologinguests']}</td>\n";
echo "                        <td>", form_radio("auto_logon", "Y", $lang['yes'], (isset($forum_settings['auto_logon']) && $forum_settings['auto_logon'] == "Y")), "&nbsp;", form_radio("auto_logon", "N", $lang['no'], (isset($forum_settings['auto_logon']) && $forum_settings['auto_logon'] == "N") || !isset($forum_settings['auto_logon'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_21']}</p>\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_22']}</p>\n";
echo "                        </td>\n";
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
echo "      <td>&nbsp;</td>\n";
echo "    </tr>\n";
echo "    <tr>\n";
echo "      <td align=\"center\">", form_submit("submit", $lang['save']), "</td>\n";
echo "    </tr>\n";
echo "  </table>\n";
echo "</form>\n";
html_draw_bottom();

?>