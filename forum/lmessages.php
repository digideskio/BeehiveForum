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

/* $Id: lmessages.php,v 1.51 2005-03-14 13:27:20 decoyduck Exp $ */

// Constant to define where the include files are
define("BH_INCLUDE_PATH", "./include/");

// Light Mode Detection
define("BEEHIVEMODE_LIGHT", true);

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

// Fetch the forum settings
$forum_settings = forum_get_settings();

include_once(BH_INCLUDE_PATH. "beehive.inc.php");
include_once(BH_INCLUDE_PATH. "constants.inc.php");
include_once(BH_INCLUDE_PATH. "folder.inc.php");
include_once(BH_INCLUDE_PATH. "form.inc.php");
include_once(BH_INCLUDE_PATH. "header.inc.php");
include_once(BH_INCLUDE_PATH. "html.inc.php");
include_once(BH_INCLUDE_PATH. "lang.inc.php");
include_once(BH_INCLUDE_PATH. "light.inc.php");
include_once(BH_INCLUDE_PATH. "messages.inc.php");
include_once(BH_INCLUDE_PATH. "perm.inc.php");
include_once(BH_INCLUDE_PATH. "poll.inc.php");
include_once(BH_INCLUDE_PATH. "session.inc.php");
include_once(BH_INCLUDE_PATH. "thread.inc.php");
include_once(BH_INCLUDE_PATH. "user.inc.php");

if (!$user_sess = bh_session_check()) {
    $request_uri = rawurlencode(get_request_uri(true));
    $webtag = get_webtag($webtag_search);
    header_redirect("./llogon.php?webtag=$webtag&final_uri=$request_uri");
}

// Check we have a webtag

if (!$webtag = get_webtag($webtag_search)) {
    $request_uri = rawurlencode(get_request_uri(true));
    header_redirect("./lforums.php?final_uri=$request_uri");
}

// Load language file

$lang = load_language_file();

// Check that we have access to this forum

if (!forum_check_access_level()) {
    header_redirect("./lforums.php");
}

// Check that required variables are set
// default to display most recent discussion for user

if (isset($_GET['msg']) && validate_msg($_GET['msg'])) {
    $msg = $_GET['msg'];
}else {
    if (bh_session_get_value('UID')) {
        $msg = messages_get_most_recent(bh_session_get_value('UID'));
    } else {
        $msg = "1.1";
    }
}

list($tid, $pid) = explode('.', $msg);

if (!is_numeric($pid)) $pid = 1;
if (!is_numeric($tid)) $tid = 1;

if (!thread_can_view($tid, bh_session_get_value('UID'))) {
    light_html_draw_top();
    echo "<h2>{$lang['threadcouldnotbefound']}</h2>";
    light_html_draw_bottom();
    exit;
}

// Poll stuff

if (isset($_POST['pollsubmit'])) {

  if (isset($_POST['pollvote'])) {

    poll_vote($_POST['tid'], $_POST['pollvote']);
    header_redirect("lmessages.php?webtag=$webtag&msg=". $_POST['tid']. ".1");

  }else {

    light_html_draw_top();
    echo "<h2>{$lang['mustselectpolloption']}</h2>";
    light_html_draw_bottom();
    exit;

  }

}

// Output XHTML header
light_html_draw_top();

if (bh_session_get_value('POSTS_PER_PAGE')) {
    $ppp = bh_session_get_value('POSTS_PER_PAGE');
    if ($ppp < 10) $ppp = 10;
    if ($ppp > 30) $ppp = 30;
}else {
    $ppp = 20;
}

$messages = messages_get($tid,$pid,$ppp);
$threaddata = thread_get($tid);
$foldertitle = folder_get_title($threaddata['FID']);

$show_sigs = false; // explicitly set sigs not to show in light mode

$msg_count = count($messages);

light_messages_top($msg, apply_wordfilter(_stripslashes($threaddata['TITLE'])), $threaddata['INTEREST'], $threaddata['STICKY'], $threaddata['CLOSED'], $threaddata['ADMIN_LOCK']);

if ($msg_count > 0) {

    $first_msg = $messages[0]['PID'];

    foreach($messages as $message) {

        if (isset($message['RELATIONSHIP'])) {

            if ($message['RELATIONSHIP'] >= 0) { // if we're not ignoring this user
                $message['CONTENT'] = message_get_content($tid, $message['PID']);
            } else {
                $message['CONTENT'] = $lang['ignored']; // must be set to something or will show as deleted
            }

            } else {

                $message['CONTENT'] = message_get_content($tid, $message['PID']);

            }

        if ($threaddata['POLL_FLAG'] == 'Y') {

          if ($message['PID'] == 1) {

            light_poll_display($tid, $threaddata['LENGTH'], $first_msg, true, $threaddata['CLOSED'], true);
            $last_pid = $message['PID'];

          }else {

            light_message_display($tid, $message, $threaddata['LENGTH'], $first_msg, true, $threaddata['CLOSED'], true, true, $show_sigs, false);
            $last_pid = $message['PID'];

          }

        }else {

          light_message_display($tid, $message, $threaddata['LENGTH'], $first_msg, true, $threaddata['CLOSED'], true, false, $show_sigs, false);
          $last_pid = $message['PID'];

        }
    }
}

unset($messages, $message);

if ($last_pid < $threaddata['LENGTH']) {
    $npid = $last_pid + 1;
    form_quick_button("./lmessages.php", $lang['keepreading'], "msg", "$tid.$npid");
}

light_messages_nav_strip($tid, $pid, $threaddata['LENGTH'], $ppp);

echo "<p><a href=\"lpost.php?webtag=$webtag&amp;replyto=$tid.0\" target=\"_parent\">{$lang['replyall']}</a></p>\n";

if (bh_session_get_value('UID') == 0) {
    echo "<h4><a href=\"lthread_list.php?webtag=$webtag\">{$lang['backtothreadlist']}</a> | <a href=\"llogout.php?webtag=$webtag\">{$lang['login']}</a></h4>\n";
}else {
    echo "<h4><a href=\"lthread_list.php?webtag=$webtag\">{$lang['backtothreadlist']}</a> | <a href=\"llogout.php?webtag=$webtag\">{$lang['logout']}</a></h4>\n";
}

echo "<h6>&copy; ", date('Y'), " <a href=\"http://www.beehiveforum.net/\" target=\"_blank\">Project BeehiveForum</a></h6>\n";

light_html_draw_bottom();

if ($msg_count > 0 && bh_session_get_value('UID') && bh_session_get_value('UID') != 0) {
    messages_update_read($tid,$last_pid,bh_session_get_value('UID'));
}

?>