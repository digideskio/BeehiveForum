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

/* $Id: admin_default_forum_settings.php,v 1.18 2005-03-14 13:11:19 decoyduck Exp $ */

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
$forum_settings = forum_get_settings();

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

$webtag = get_webtag($webtag_search);

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

// Default Forum Settings

$default_forum_settings = forum_get_default_settings();

if (isset($_POST['submit'])) {

    $valid = true;

    if (isset($_POST['search_min_word_length']) && is_numeric($_POST['search_min_word_length'])) {
        $new_forum_settings['search_min_word_length'] = $_POST['search_min_word_length'];
    }else {
        $new_forum_settings['search_min_word_length'] = 3;
    }

    if (isset($_POST['session_cutoff']) && is_numeric($_POST['session_cutoff'])) {
        $new_forum_settings['session_cutoff'] = $_POST['session_cutoff'];
    }else {
        $new_forum_settings['session_cutoff'] = 86400;
    }

    if (isset($_POST['active_sess_cutoff']) && is_numeric($_POST['active_sess_cutoff'])) {

        if ($_POST['active_sess_cutoff'] < $_POST['session_cutoff']) {

            $new_forum_settings['active_sess_cutoff'] = $_POST['active_sess_cutoff'];

        }else {

            $error_html = "<h2>{$lang['activesessiongreaterthansession']}</h2>\n";
            $valid = false;
        }

    }else {

        $new_forum_settings['active_sess_cutoff'] = 900;
    }

    if (isset($_POST['allow_new_registrations']) && $_POST['allow_new_registrations'] == "Y") {
        $new_forum_settings['allow_new_registrations'] = "Y";
    }else {
        $new_forum_settings['allow_new_registrations'] = "N";
    }

    if (isset($_POST['show_pms']) && $_POST['show_pms'] == "Y") {
        $new_forum_settings['show_pms'] = "Y";
    }else {
        $new_forum_settings['show_pms'] = "N";
    }

    if (isset($_POST['pm_max_user_messages']) && is_numeric($_POST['pm_max_user_messages'])) {
        $new_forum_settings['pm_max_user_messages'] = $_POST['pm_max_user_messages'];
    }else {
        $new_forum_settings['pm_max_user_messages'] = 100;
    }

    if (isset($_POST['pm_auto_prune_enabled']) && $_POST['pm_auto_prune_enabled'] == "Y") {

        if (isset($_POST['pm_auto_prune']) && is_numeric($_POST['pm_auto_prune'])) {

            $new_forum_settings['pm_auto_prune'] = $_POST['pm_auto_prune'];

        }else {

            $new_forum_settings['pm_auto_prune'] = "-60";
        }

    }else {

        if (isset($_POST['pm_auto_prune']) && is_numeric($_POST['pm_auto_prune'])) {

            $new_forum_settings['pm_auto_prune'] = $_POST['pm_auto_prune'] * -1;

        }else {

            $new_forum_settings['pm_auto_prune'] = "-60";
        }
    }

    if (isset($_POST['pm_allow_attachments']) && $_POST['pm_allow_attachments'] == "Y") {
        $new_forum_settings['pm_allow_attachments'] = "Y";
    }else {
        $new_forum_settings['pm_allow_attachments'] = "N";
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

    if (isset($_POST['attachments_enabled']) && $_POST['attachments_enabled'] == "Y") {
        $new_forum_settings['attachments_enabled'] = "Y";
    }else {
        $new_forum_settings['attachments_enabled'] = "N";
    }

    if (isset($_POST['attachment_dir']) && strlen(trim(_stripslashes($_POST['attachment_dir']))) > 0) {

        $new_forum_settings['attachment_dir'] = trim(_stripslashes($_POST['attachment_dir']));

        if (!(@is_dir($new_forum_settings['attachment_dir']))) {

            @mkdir($new_forum_settings['attachment_dir'], 0755);
            @chmod($new_forum_settings['attachment_dir'], 0777);
        }

        if (@$fp = fopen("{$new_forum_settings['attachment_dir']}/bh_attach_test", "w")) {

           fclose($fp);
           unlink("{$new_forum_settings['attachment_dir']}/bh_attach_test");

        }else {

           $error_html.= "<h2>{$lang['attachmentdirnotwritable']}</h2>\n";
           $valid = false;
        }

    }elseif (strtoupper($new_forum_settings['attachments_enabled']) == "Y") {

        $error_html = "<h2>{$lang['attachmentdirblank']}</h2>\n";
        $valid = false;
    }

    if (isset($_POST['attachments_max_user_space']) && is_numeric($_POST['attachments_max_user_space'])) {
        $new_forum_settings['attachments_max_user_space'] = ($_POST['attachments_max_user_space'] * 1024) * 1024;
    }else {
        $new_forum_settings['attachments_max_user_space'] = 1048576; // 1MB in bytes
    }

    if (isset($_POST['attachments_allow_embed']) && $_POST['attachments_allow_embed'] == "Y") {
        $new_forum_settings['attachments_allow_embed'] = "Y";
    }else {
        $new_forum_settings['attachments_allow_embed'] = "N";
    }

    if (isset($_POST['attachment_use_old_method']) && $_POST['attachment_use_old_method'] == "Y") {
        $new_forum_settings['attachment_use_old_method'] = "Y";
    }else {
        $new_forum_settings['attachment_use_old_method'] = "N";
    }

    if ($valid) {

        forum_save_default_settings($new_forum_settings);

        $uid = bh_session_get_value('UID');
        admin_add_log_entry(EDIT_FORUM_SETTINGS);

        if (isset($_SERVER['SERVER_SOFTWARE']) && !strstr($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS')) {
            header_redirect("./admin_default_forum_settings.php?webtag=$webtag&updated=true");

        }else {

            html_draw_top();

            // Try a Javascript redirect
            echo "<script language=\"javascript\" type=\"text/javascript\">\n";
            echo "<!--\n";
            echo "document.location.href = './admin_default_forum_settings.php?webtag=$webtag&amp;updated=true';\n";
            echo "//-->\n";
            echo "</script>";

            // If they're still here, Javascript's not working. Give up, give a link.
            echo "<div align=\"center\"><p>&nbsp;</p><p>&nbsp;</p>";
            echo "<p>{$lang['forumsettingsupdated']}</p>";

            form_quick_button("./admin_default_forum_settings.php", $lang['continue'], false, false, "_top");

            html_draw_bottom();
            exit;
        }
    }
}

// Start Output Here

html_draw_top("emoticons.js");

echo "<h1>{$lang['globalforumsettings']}</h1>\n";

// Any error messages to display?

if (!empty($error_html)) {
    echo $error_html;
}else if (isset($_GET['updated'])) {
    echo "<h2>{$lang['forumsettingsupdated']}</h2>\n";
}

echo "<br />\n";
echo "<form name=\"prefs\" action=\"admin_default_forum_settings.php\" method=\"post\" target=\"_self\">\n";
echo "  ", form_input_hidden('webtag', $webtag), "\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"550\">\n";
echo "    <tr>\n";
echo "      <td>\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['searchoptions']}:</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['minsearchwordlength']}:</td>\n";
echo "                        <td>", form_input_text("search_min_word_length", (isset($default_forum_settings['search_min_word_length'])) ? $default_forum_settings['search_min_word_length'] : "", 10, 2), "&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_14']}</p>\n";
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
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['sessions']}:</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['sessioncutoffseconds']}:</td>\n";
echo "                        <td>", form_input_text("session_cutoff", (isset($default_forum_settings['session_cutoff'])) ? $default_forum_settings['session_cutoff'] : "86400", 20, 6), "&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['activesessioncutoffseconds']}:</td>\n";
echo "                        <td>", form_input_text("active_sess_cutoff", (isset($default_forum_settings['active_sess_cutoff'])) ? $default_forum_settings['active_sess_cutoff'] : "900", 20, 6), "&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_15']}</p>\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_16']}</p>\n";
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
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['newuserregistrations']}:</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['allownewuserregistrations']}:</td>\n";
echo "                        <td>", form_radio("allow_new_registrations", "Y", $lang['yes'], (isset($default_forum_settings['allow_new_registrations']) && $default_forum_settings['allow_new_registrations'] == 'Y') || !isset($default_forum_settings['allow_new_registrations'])), "&nbsp;", form_radio("allow_new_registrations", "N", $lang['no'], (isset($default_forum_settings['allow_new_registrations']) && $default_forum_settings['allow_new_registrations'] == 'N')), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                       <td colspan=\"2\" >\n";
echo "                         <p class=\"smalltext\">{$lang['forum_settings_help_29']}</p>\n";
echo "                       </td>\n";
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
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['personalmessages']}:</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['enablepersonalmessages']}:</td>\n";
echo "                        <td>", form_radio("show_pms", "Y", $lang['yes'] , (isset($default_forum_settings['show_pms']) && $default_forum_settings['show_pms'] == 'Y')), "&nbsp;", form_radio("show_pms", "N", $lang['no'] , (isset($default_forum_settings['show_pms']) && $default_forum_settings['show_pms'] == 'N')), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"350\">{$lang['autopruneuserspmfoldersevery']} ", form_dropdown_array('pm_auto_prune', array(1 => 10, 2 => 15, 3 => 30, 4 => 60), array(1 => 10, 2 => 15, 3 => 30, 4 => 60), (isset($default_forum_settings['pm_auto_prune']) ? ($default_forum_settings['pm_auto_prune'] > 0 ? $default_forum_settings['pm_auto_prune'] : $default_forum_settings['pm_auto_prune'] * -1) : 4)), " {$lang['days']}:</td>\n";
echo "                        <td>", form_radio("pm_auto_prune_enabled", "Y", $lang['yes'], (isset($default_forum_settings['pm_auto_prune']) && $default_forum_settings['pm_auto_prune'] > 0)), "&nbsp;", form_radio("pm_auto_prune_enabled", "N", $lang['no'] , (isset($default_forum_settings['pm_auto_prune']) && $default_forum_settings['pm_auto_prune'] < 0)), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['allowpmstohaveattachments']}:</td>\n";
echo "                        <td>", form_radio("pm_allow_attachments", "Y", $lang['yes'] , (isset($default_forum_settings['pm_allow_attachments']) && $default_forum_settings['pm_allow_attachments'] == 'Y')), "&nbsp;", form_radio("pm_allow_attachments", "N", $lang['no'] , (isset($default_forum_settings['pm_allow_attachments']) && $default_forum_settings['pm_allow_attachments'] == 'N')), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['pmusermessages']}:</td>\n";
echo "                        <td>", form_input_text("pm_max_user_messages", (isset($default_forum_settings['pm_max_user_messages'])) ? $default_forum_settings['pm_max_user_messages'] : "", 10, 32), "&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"3\">\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_18']}</p>\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_19']}</p>\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_20']}</p>\n";
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
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['searchenginespidering']}:</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['allowsearchenginespidering']}:</td>\n";
echo "                        <td>", form_radio("allow_search_spidering", "Y", $lang['yes'], (isset($default_forum_settings['allow_search_spidering']) && $default_forum_settings['allow_search_spidering'] == 'Y')), "&nbsp;", form_radio("allow_search_spidering", "N", $lang['no'], (isset($default_forum_settings['allow_search_spidering']) && $default_forum_settings['allow_search_spidering'] == 'N') || !isset($default_forum_settings['allow_search_spidering'])), "</td>\n";
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
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['guestaccount']}:</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['enableguestaccount']}:</td>\n";
echo "                        <td>", form_radio("guest_account_enabled", "Y", $lang['yes'], (isset($default_forum_settings['guest_account_enabled']) && $default_forum_settings['guest_account_enabled'] == 'Y')), "&nbsp;", form_radio("guest_account_enabled", "N", $lang['no'], (isset($default_forum_settings['guest_account_enabled']) && $default_forum_settings['guest_account_enabled'] == 'N') || !isset($default_forum_settings['guest_account_enabled'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['autologinguests']}:</td>\n";
echo "                        <td>", form_radio("auto_logon", "Y", $lang['yes'], (isset($default_forum_settings['auto_logon']) && $default_forum_settings['auto_logon'] == 'Y')), "&nbsp;", form_radio("auto_logon", "N", $lang['no'], (isset($default_forum_settings['auto_logon']) && $default_forum_settings['auto_logon'] == 'N') || !isset($default_forum_settings['auto_logon'])), "</td>\n";
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
echo "                  <td class=\"subhead\" colspan=\"3\">{$lang['attachments']}:</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['enableattachments']}:</td>\n";
echo "                        <td>", form_radio("attachments_enabled", "Y", $lang['yes'], (isset($default_forum_settings['attachments_enabled']) && $default_forum_settings['attachments_enabled'] == 'Y')), "&nbsp;", form_radio("attachments_enabled", "N", $lang['no'], (isset($default_forum_settings['attachments_enabled']) && $default_forum_settings['attachments_enabled'] == 'N') || !isset($default_forum_settings['attachments_enabled'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['allowembeddingofattachments']}:</td>\n";
echo "                        <td>", form_radio("attachments_allow_embed", "Y", $lang['yes'], (isset($default_forum_settings['attachments_allow_embed']) && $default_forum_settings['attachments_allow_embed'] == 'Y')), "&nbsp;", form_radio("attachments_allow_embed", "N", $lang['no'], (isset($default_forum_settings['attachments_allow_embed']) && $default_forum_settings['attachments_allow_embed'] == 'N') || !isset($default_forum_settings['attachments_allow_embed'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['usealtattachmentmethod']}:</td>\n";
echo "                        <td>", form_radio("attachment_use_old_method", "Y", $lang['yes'], (isset($default_forum_settings['attachment_use_old_method']) && $default_forum_settings['attachment_use_old_method'] == 'Y')), "&nbsp;", form_radio("attachment_use_old_method", "N", $lang['no'], (isset($default_forum_settings['attachment_use_old_method']) && $default_forum_settings['attachment_use_old_method'] == 'N') || !isset($default_forum_settings['attachment_use_old_method'])), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['attachmentdir']}:</td>\n";
echo "                        <td>", form_input_text("attachment_dir", (isset($default_forum_settings['attachment_dir'])) ? $default_forum_settings['attachment_dir'] : "", 35, 255), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td width=\"270\">{$lang['userattachmentspace']}:</td>\n";
echo "                        <td>", form_input_text("attachments_max_user_space", (isset($default_forum_settings['attachments_max_user_space'])) ? ($default_forum_settings['attachments_max_user_space'] / 1024) / 1024 : "", 10, 32), "&nbsp;(MB)</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td colspan=\"2\">\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_23']}</p>\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_24']}</p>\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_25']}</p>\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_26']}</p>\n";
echo "                          <p class=\"smalltext\">{$lang['forum_settings_help_27']}</p>\n";
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
echo "      <td><p>{$lang['settingsaffectallforumswarning']}</p></td>\n";
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