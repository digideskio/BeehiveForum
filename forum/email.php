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

/* $Id: email.php,v 1.82 2007-08-09 22:55:43 decoyduck Exp $ */

// Constant to define where the include files are
define("BH_INCLUDE_PATH", "./include/");

// Server checking functions
include_once(BH_INCLUDE_PATH. "server.inc.php");

// Compress the output
include_once(BH_INCLUDE_PATH. "gzipenc.inc.php");

// Enable the error handler
include_once(BH_INCLUDE_PATH. "errorhandler.inc.php");

// Installation checking functions
include_once(BH_INCLUDE_PATH. "install.inc.php");

// Check that Beehive is installed correctly
check_install();

// Multiple forum support
include_once(BH_INCLUDE_PATH. "forum.inc.php");

// Fetch Forum Settings

$forum_settings = forum_get_settings();

// Fetch Global Forum Settings

$forum_global_settings = forum_get_global_settings();

include_once(BH_INCLUDE_PATH. "email.inc.php");
include_once(BH_INCLUDE_PATH. "form.inc.php");
include_once(BH_INCLUDE_PATH. "format.inc.php");
include_once(BH_INCLUDE_PATH. "header.inc.php");
include_once(BH_INCLUDE_PATH. "html.inc.php");
include_once(BH_INCLUDE_PATH. "lang.inc.php");
include_once(BH_INCLUDE_PATH. "logon.inc.php");
include_once(BH_INCLUDE_PATH. "session.inc.php");
include_once(BH_INCLUDE_PATH. "user.inc.php");

// Check we're logged in correctly

if (!$user_sess = bh_session_check()) {
    $request_uri = rawurlencode(get_request_uri());
    $webtag = get_webtag($webtag_search);
    header_redirect("./logon.php?webtag=$webtag&final_uri=$request_uri");
}

// Check to see if the user is banned.

if (bh_session_user_banned()) {

    html_user_banned();
    exit;
}

// Check to see if the user has been approved.

if (!bh_session_user_approved()) {

    html_user_require_approval();
    exit;
}

// Check we have a webtag

if (!$webtag = get_webtag($webtag_search)) {
    $request_uri = rawurlencode(get_request_uri(false));
    header_redirect("./forums.php?webtag_search=$webtag_search&final_uri=$request_uri");
}

// Load language file

$lang = load_language_file();

// Check that we have access to this forum

if (!forum_check_access_level()) {

    $request_uri = rawurlencode(get_request_uri());
    header_redirect("./forums.php?webtag_search=$webtag_search&final_uri=$request_uri");
}

if (user_is_guest()) {

    html_guest_error();
    exit;
}

if (isset($_GET['uid']) && is_numeric($_GET['uid'])) {

    $to_uid = $_GET['uid'];

}else if (isset($_POST['to_uid']) && is_numeric($_POST['to_uid'])) {

    $to_uid = $_POST['to_uid'];

}else {

    html_draw_top('pm_popup_disabled');
    html_error_msg($lang['nouserspecifiedforemail']);
    html_draw_bottom();
    exit;
}

if (isset($_POST['close'])) {

    html_draw_top('pm_popup_disabled');
    echo "<script language=\"Javascript\" type=\"text/javascript\">\n";
    echo "  window.close();\n";
    echo "</script>\n";

    html_draw_bottom();
    exit;
}

$uid = bh_session_get_value('UID');

$to_user = user_get($to_uid);
$from_user = user_get($uid);

if (isset($_POST['submit'])) {

    $valid = true;

    if (isset($_POST['t_subject']) && strlen(trim(_stripslashes($_POST['t_subject']))) > 0) {
        $subject = trim(_stripslashes($_POST['t_subject']));
    }else {
        $error = "<h2>{$lang['entersubjectformessage']}</h2>";
        $valid = false;
    }

    if (isset($_POST['t_message']) && strlen(trim(_stripslashes($_POST['t_message']))) > 0) {
        $message = trim(_stripslashes($_POST['t_message']));
    }else {
        $error = "<h2>{$lang['entercontentformessage']}</h2>";
        $valid = false;
    }

    if (!user_allow_email($to_user['UID'])) {
        $error = "<h2>{$lang['user']} {$to_user['LOGON']} {$lang['hasoptedoutofemail']}</h2>\n";
        $valid = false;
    }

    if (!ereg("^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*$", $to_user['EMAIL'])) {
        $error = "<h2>{$lang['user']} {$to_user['LOGON']} {$lang['hasinvalidemailaddress']}</h2>\n";
        $valid = false;
    }

    if ($valid) {

        if (email_send_message_to_user($to_uid, $uid, $subject, $message)) {

            html_draw_top("title={$lang['emailresult']}", 'pm_popup_disabled');
            html_display_msg($lang['msgsent'], $lang['msgsentsuccessfully'], 'email.php', 'post', array('close' => $lang['close']), array('to_uid' => $to_uid), false, 'center');
            html_draw_bottom();
            exit;

        }else {

            html_draw_top("title={$lang['emailresult']}", 'pm_popup_disabled');
            html_error_msg($lang['mailsystemfailure'], 'email.php', 'post', array('close' => $lang['close']), array('to_uid' => $to_uid), false, 'center');
            html_draw_bottom();
            exit;
        }
    }
}

html_draw_top("{$lang['email']} {$to_user['LOGON']}", 'pm_popup_disabled');

if (!isset($subject)) $subject = "";
if (!isset($message)) $message = "";

echo "<div align=\"center\">\n";
echo "<form name=\"f_email\" action=\"email.php\" method=\"post\">\n";
echo "  ", form_input_hidden('webtag', _htmlentities($webtag)), "\n";
echo "  ", form_input_hidden("to_uid", _htmlentities($to_uid)), "\n";

if (isset($error) && strlen(trim($error)) > 0) {

    echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"480\">\n";
    echo "    <tr>\n";
    echo "      <td align=\"left\">$error</td>\n";
    echo "    </tr>\n";
    echo "  </table>\n";
}

echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"480\">\n";
echo "    <tr>\n";
echo "      <td align=\"left\">\n";
echo "        <table class=\"box\">\n";
echo "          <tr>\n";
echo "            <td align=\"left\" class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"480\">\n";
echo "                <tr>\n";
echo "                  <td align=\"left\" class=\"subhead\" colspan=\"2\">{$lang['email']}&nbsp;{$to_user['NICKNAME']}</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" width=\"25%\">{$lang['from']}:</td>\n";
echo "                        <td align=\"left\">{$from_user['NICKNAME']} ({$from_user['EMAIL']})</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\">{$lang['subject']}:</td>\n";
echo "                        <td align=\"left\">", form_input_text("t_subject", _htmlentities($subject), 54, 128), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" valign=\"top\">{$lang['message']}:</td>\n";
echo "                        <td align=\"left\">", form_textarea("t_message", _htmlentities($message), 12, 51), "</td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\" colspan=\"2\">&nbsp;</td>\n";
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
echo "      <td align=\"center\">", form_submit("submit", $lang['send']), "&nbsp;", form_submit("close", $lang['cancel']), "</td>\n";
echo "    </tr>\n";
echo "  </table>\n";
echo "</form>\n";
echo "</div>\n";

html_draw_bottom();

?>