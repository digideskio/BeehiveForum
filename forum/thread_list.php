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

/* $Id: thread_list.php,v 1.224 2004-11-30 22:25:26 tribalonline Exp $ */

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

include_once("./include/constants.inc.php");
include_once("./include/folder.inc.php");
include_once("./include/form.inc.php");
include_once("./include/format.inc.php");
include_once("./include/header.inc.php");
include_once("./include/html.inc.php");
include_once("./include/lang.inc.php");
include_once("./include/logon.inc.php");
include_once("./include/messages.inc.php");
include_once("./include/pm.inc.php");
include_once("./include/session.inc.php");
include_once("./include/thread.inc.php");
include_once("./include/threads.inc.php");
include_once("./include/word_filter.inc.php");

if (!$user_sess = bh_session_check()) {

    html_draw_top();

    if (isset($_POST['user_logon']) && isset($_POST['user_password']) && isset($_POST['user_passhash'])) {

        if (perform_logon(false)) {

            $lang = load_language_file();
            $webtag = get_webtag($webtag_search);

            echo "<h1>{$lang['loggedinsuccessfully']}</h1>";
            echo "<div align=\"center\">\n";
            echo "<p><b>{$lang['presscontinuetoresend']}</b></p>\n";

            $request_uri = get_request_uri();

            echo "<form method=\"post\" action=\"$request_uri\" target=\"_self\">\n";
            echo form_input_hidden('webtag', $webtag);

            foreach($_POST as $key => $value) {
                echo form_input_hidden($key, _htmlentities(_stripslashes($value)));
            }

            echo form_submit(md5(uniqid(rand())), $lang['continue']), "&nbsp;";
            echo form_button(md5(uniqid(rand())), $lang['cancel'], "onclick=\"self.location.href='$request_uri'\""), "\n";
            echo "</form>\n";

            html_draw_bottom();
            exit;
        }
    }

    draw_logon_form(false);
    html_draw_bottom();
    exit;
}

// Load language file

$lang = load_language_file();

// Check we have a webtag

if (!$webtag = get_webtag($webtag_search)) {
    $request_uri = rawurlencode(get_request_uri(true));
    header_redirect("./forums.php?webtag_search=$webtag_search&final_uri=$request_uri");
}

// Check that we have access to this forum

if (!forum_check_access_level()) {
    $request_uri = rawurlencode(get_request_uri(true));
    header_redirect("./forums.php?webtag_search=$webtag_search&final_uri=$request_uri");
}

// Check that required variables are set
if (bh_session_get_value('UID') == 0) {

    $uid = 0; // default to UID 0 if no other UID specified

    if (isset($_GET['mode']) && is_numeric($_GET['mode'])) {
        // non-logged in users can only display "All" threads or those in the past x days, since the other options would be impossible
        if ($_GET['mode'] == 0 || $_GET['mode'] == 3 || $_GET['mode'] == 4 || $_GET['mode'] == 5) {
            $mode = $_GET['mode'];
        }else {
            $mode = 0;
        }
    }else {
        if (isset($_COOKIE['bh_thread_mode']) && is_numeric($_COOKIE['bh_thread_mode'])) {
            $mode = $_COOKIE['bh_thread_mode'];
        }else{
            $mode = 0;
        }
    }

}else {

    $uid = bh_session_get_value('UID');

    if (isset($_GET['markread'])) {

        if ($_GET['markread'] == 2 && isset($_GET['tid_array']) && is_array($_GET['tid_array'])) {
            threads_mark_read($_GET['tid_array']);
        }elseif ($_GET['markread'] == 0) {
            threads_mark_all_read();
        }elseif ($_GET['markread'] == 1) {
            threads_mark_50_read();
        }
    }

    if (isset($_GET['mode']) && is_numeric($_GET['mode'])) {
        $mode = $_GET['mode'];
    }else {
        if (isset($_COOKIE['bh_thread_mode']) && is_numeric($_COOKIE['bh_thread_mode'])) {
            $mode = $_COOKIE['bh_thread_mode'];
        }else{
            if (threads_any_unread()) { // default to "Unread" messages for a logged-in user, unless there aren't any
                $mode = 1;
            }else {
                $mode = 0;
            }
        }
    }
}

if (isset($_GET['folder']) && is_numeric($_GET['folder'])) {
    $folder = $_GET['folder'];
    $mode = 0;
}

if (bh_session_get_value('UID') != 0) {
    bh_setcookie('bh_thread_mode', $mode);
}

if (isset($_GET['start_from']) && is_numeric($_GET['start_from'])) {
    $start_from = $_GET['start_from'];
}else {
    $start_from = 0;
}

// Output XHTML header
html_draw_top();

echo "<script language=\"javascript\" type=\"text/javascript\">\n";
echo "<!--\n\n";
echo "function change_current_thread (thread_id) {\n";
echo "    if (current_thread > 0) {\n";
echo "        document[\"t\" + current_thread].src = \"", style_image('bullet.png'), "\";\n";
echo "    }\n";
echo "    document[\"t\" + thread_id].src = \"", style_image('current_thread.png'), "\";\n";
echo "    current_thread = thread_id;\n";
echo "    return true;\n";
echo "}\n\n";
echo "// -->\n";
echo "</script>\n\n";
echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "  <tr>\n";
echo "    <td class=\"postbody\" colspan=\"2\"><img src=\"", style_image('post.png'), "\" height=\"15\" alt=\"{$lang['newdiscussion']}\" title=\"{$lang['newdiscussion']}\" />&nbsp;<a href=\"post.php?webtag=$webtag\" target=\"main\">{$lang['newdiscussion']}</a></td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td class=\"postbody\" colspan=\"2\"><img src=\"", style_image('poll.png'), "\" height=\"15\" alt=\"{$lang['createpoll']}\" title=\"{$lang['createpoll']}\" />&nbsp;<a href=\"create_poll.php?webtag=$webtag\" target=\"main\">{$lang['createpoll']}</a></td>\n";
echo "  </tr>\n";

if ($pm_new_count = pm_get_unread_count() && bh_session_get_value('UID') != 0) {
    echo "  <tr>\n";
    echo "    <td class=\"postbody\" colspan=\"2\"><img src=\"", style_image('pmunread.png'), "\" height=\"16\" alt=\"{$lang['pminbox']}\" title=\"{$lang['pminbox']}\" />&nbsp;<a href=\"pm.php?webtag=$webtag\" target=\"main\">{$lang['pminbox']}</a> <span class=\"pmnewcount\">[$pm_new_count {$lang['unread']}]</span></td>\n";
    echo "  </tr>\n";
}else {
    echo "  <tr>\n";
    echo "    <td class=\"postbody\" colspan=\"2\"><img src=\"", style_image('pmread.png'), "\" height=\"16\" alt=\"{$lang['pminbox']}\" title=\"{$lang['pminbox']}\" />&nbsp;<a href=\"pm.php?webtag=$webtag\" target=\"main\">{$lang['pminbox']}</a></td>\n";
    echo "  </tr>\n";
}

echo "  <tr>\n";
echo "    <td colspan=\"2\">&nbsp;</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td colspan=\"2\">\n";
echo "      <form name=\"f_mode\" method=\"get\" action=\"thread_list.php\">\n";
echo "        ", form_input_hidden("webtag", $webtag), "\n";

threads_draw_discussions_dropdown($mode);

echo form_submit("go",$lang['goexcmark']). "\n";

echo "      </form>\n";
echo "    </td>\n";
echo "  </tr>\n";

// The tricky bit - displaying the right threads for whatever mode is selected

if (isset($folder)) {
    list($thread_info, $folder_order) = threads_get_folder($uid, $folder, $start_from);
} else {
    switch ($mode) {
        case 0: // All discussions
            list($thread_info, $folder_order) = threads_get_all($uid, $start_from);
            break;
        case 1; // Unread discussions
            list($thread_info, $folder_order) = threads_get_unread($uid);
            break;
        case 2; // Unread discussions To: Me
            list($thread_info, $folder_order) = threads_get_unread_to_me($uid);
            break;
        case 3; // Today's discussions
            list($thread_info, $folder_order) = threads_get_by_days($uid, 1);
            break;
        case 4: // Unread today
            list($thread_info, $folder_order) = threads_get_unread_by_days($uid);
            break;
        case 5; // 2 days back
            list($thread_info, $folder_order) = threads_get_by_days($uid, 2);
            break;
        case 6; // 7 days back
            list($thread_info, $folder_order) = threads_get_by_days($uid, 7);
            break;
        case 7; // High interest
            list($thread_info, $folder_order) = threads_get_by_interest($uid, 1);
            break;
        case 8; // Unread high interest
            list($thread_info, $folder_order) = threads_get_unread_by_interest($uid, 1);
            break;
        case 9; // Recently seen
            list($thread_info, $folder_order) = threads_get_recently_viewed($uid);
            break;
        case 10; // Ignored
            list($thread_info, $folder_order) = threads_get_by_interest($uid, -1);
            break;
        case 11; // By Ignored Users
            list($thread_info, $folder_order) = threads_get_by_relationship($uid, USER_IGNORED_COMPLETELY);
            break;
        case 12; // Subscribed to
            list($thread_info, $folder_order) = threads_get_by_interest($uid, 2);
            break;
        case 13: // Started by friend
            list($thread_info, $folder_order) = threads_get_by_relationship($uid, USER_FRIEND);
            break;
        case 14: // Unread started by friend
            list($thread_info, $folder_order) = threads_get_unread_by_relationship($uid, USER_FRIEND);
            break;
        case 15: // Polls
            list($thread_info, $folder_order) = threads_get_polls($uid);
            break;
        case 16: // Sticky threads
            list($thread_info, $folder_order) = threads_get_sticky($uid);
            break;
        case 17: // Most unread posts
            list($thread_info, $folder_order) = threads_get_longest_unread($uid);
            break;
        default: // Default to all threads
            list($thread_info, $folder_order) = threads_get_all($uid, $start_from);
            break;
    }
}

// Now, the actual bit that displays the threads...

// Get folder FIDs and titles
$folder_info = threads_get_folders();

if (!$folder_info) {

    echo "</table>\n";
    echo "<h1>{$lang['error']}</h1>\n";
    echo "<h2>{$lang['couldnotretrievefolderinformation']}</h2>\n";

    html_draw_bottom();
    exit;
}

// Get total number of messages for each folder
$folder_msgs = threads_get_folder_msgs();

// Check to see if $folder_order is an array, and define it as one if not
if (!is_array($folder_order)) $folder_order = array();

// Sort the folders and threads correctly as per the URL query for the TID

if (isset($_GET['msg']) && validate_msg($_GET['msg'])) {

    $threadvisible = false;

    list($tid, $pid) = explode('.', $_GET['msg']);

    if (thread_can_view($tid, bh_session_get_value('UID'))) {

        if ($thread = thread_get($tid)) {

            foreach ($thread as $key => $value) {
                $thread[strtolower($key)] = $value;
                unset($thread[$key]);
            }

            if (!isset($thread['relationship'])) $thread['relationship'] = 0;

            if ($thread['tid'] == $tid) {

                if (in_array($thread['fid'], $folder_order)) {
                    array_splice($folder_order, array_search($thread['fid'], $folder_order), 1);
                }

                array_unshift($folder_order, $thread['fid']);

                if (!is_array($thread_info)) $thread_info = array();

                foreach ($thread_info as $key => $thread_data) {
                    if ($thread_data['tid'] == $tid) {
                        unset($thread_info[$key]);
                        break;
                    }
                }

                array_unshift($thread_info, $thread);
            }
        }
    }
}

// Work out if any folders have no messages and add them.
// Seperate them by INTEREST level

if (bh_session_get_value('UID') > 0) {

    if (isset($_GET['msg']) && validate_msg($_GET['msg'])) {

        list($tid, $pid) = explode('.', $_GET['msg']);

        if (thread_can_view($tid, bh_session_get_value('UID'))) {
            list(,$selectedfolder) = thread_get($tid);
        }

    }elseif (isset($_GET['folder'])) {

        $selectedfolder = $_GET['folder'];

    }else {

        $selectedfolder = 0;
    }

    $ignored_folders = array();

    while (list($fid, $folder_data) = each($folder_info)) {
        if ($folder_data['INTEREST'] == 0 || (isset($selectedfolder) && $selectedfolder == $fid)) {
            if ((!in_array($fid, $folder_order)) && (!in_array($fid, $ignored_folders))) $folder_order[] = $fid;
        }else {
            if ((!in_array($fid, $folder_order)) && (!in_array($fid, $ignored_folders))) $ignored_folders[] = $fid;
        }
    }

    // Append ignored folders onto the end of the folder list.
    // This will make them appear at the bottom of the thread list.

    $folder_order = array_merge($folder_order, $ignored_folders);

}else {

    while (list($fid, $folder_data) = each($folder_info)) {
        if (!in_array($fid, $folder_order)) $folder_order[] = $fid;
    }
}

// If no threads are returned, say something to that effect

if (!$thread_info) {
    echo "  <tr>\n";
    echo "    <td class=\"smalltext\" colspan=\"2\">{$lang['nomessagesinthiscategory']} <a href=\"thread_list.php?webtag=$webtag&amp;mode=0\">{$lang['clickhere']}</a> {$lang['forallthreads']}.</td>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <td>&nbsp;</td>\n";
    echo "  </tr>\n";
}

if ($start_from != 0 && $mode == 0 && !isset($folder)) echo "<tr><td class=\"smalltext\" colspan=\"2\"><img src=\"".style_image('current_thread.png')."\" height=\"15\" alt=\"{$lang['prev50threads']}\" title=\"{$lang['prev50threads']}\" />&nbsp;<a href=\"thread_list.php?webtag=$webtag&amp;mode=0&amp;start_from=".($start_from - 50)."\">{$lang['prev50threads']}</a></td></tr><tr><td>&nbsp;</td></tr>\n";

// Iterate through the information we've just got and display it in the right order

while (list($key1, $folder_number) = each($folder_order)) {

    echo "  <tr>\n";
    echo "    <td colspan=\"2\">\n";
    echo "      <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
    echo "        <tr>\n";
    echo "          <td class=\"foldername\">\n";

    if ($folder_info[$folder_number]['INTEREST'] == 0) {
        echo "            <img src=\"".style_image('folder.png')."\" height=\"15\" alt=\"{$lang['folder']}\" title=\"{$lang['folder']}\" />\n";
    }else {
        echo "            <img src=\"".style_image('folder_ignored.png')."\" height=\"15\" alt=\"{$lang['ignoredfolder']}\" title=\"{$lang['ignoredfolder']}\" />\n";
    }

    echo "            <a href=\"thread_list.php?webtag=$webtag&amp;mode=0&amp;folder=$folder_number\" title=\"", apply_wordfilter(_htmlentities($folder_info[$folder_number]['DESCRIPTION'])), "\">", apply_wordfilter(_htmlentities($folder_info[$folder_number]['TITLE'])), "</a>\n";
    echo "          </td>\n";

    if (bh_session_get_value('UID') > 0) {
        if ($folder_info[$folder_number]['INTEREST'] == 0) {
            echo "          <td class=\"folderpostnew\"><a href=\"user_folder.php?webtag=$webtag&amp;fid=$folder_number&amp;interest=-1\" onclick=\"return confirmFolderIgnore();\"><img src=\"". style_image('folder_hide.png'). "\" border=\"0\" height=\"15\" alt=\"{$lang['ignorethisfolder']}\" title=\"{$lang['ignorethisfolder']}\" /></a></td>\n";
        }else {
            echo "          <td class=\"folderpostnew\"><a href=\"user_folder.php?webtag=$webtag&amp;fid=$folder_number&amp;interest=0\" onclick=\"return confirmFolderUnignore();\"><img src=\"". style_image('folder_show.png'). "\" border=\"0\" height=\"15\" alt=\"{$lang['stopignoringthisfolder']}\" title=\"{$lang['stopignoringthisfolder']}\" /></a></td>\n";
        }
    }

    echo "        </tr>\n";
    echo "      </table>\n";
    echo "    </td>\n";
    echo "  </tr>\n";

    if ((bh_session_get_value('UID') == 0) || ($folder_info[$folder_number]['INTEREST'] != -1) || ($mode == 2) || (isset($selectedfolder) && $selectedfolder == $folder_number)) {

        if (is_array($thread_info)) {

            $visible_threads = false;

            foreach (array_keys($thread_info) as $thread_info_key) {
                if ($thread_info[$thread_info_key]['fid'] == $folder_number) $visible_threads = true;
            }

            echo "  <tr>\n";
            echo "    <td class=\"threads\" style=\"", ($visible_threads ? "border-bottom: 0px; " : ""), ($lang['_textdir'] == "ltr") ? "border-right: 0px" : "border-left: 0px", "\" valign=\"top\" width=\"50%\" nowrap=\"nowrap\"><a href=\"thread_list.php?webtag=$webtag&amp;mode=0&amp;folder=".$folder_number."\" class=\"folderinfo\" style=\"", ($lang['_textdir'] == "ltr") ? "text-align: left; float: left" : "text-align: right; float: right", "\">";

            if (isset($folder_msgs[$folder_number]) && $folder_msgs[$folder_number] > 0) {
                echo $folder_msgs[$folder_number];
            }else {
                echo "0";
            }

            echo "&nbsp;{$lang['threads']}</a></td>\n";
            echo "    <td class=\"threads\" style=\"", ($visible_threads ? "border-bottom: 0px; " : ""), ($lang['_textdir'] == "ltr") ? "border-left: 0px" : "border-right: 0px", "\" valign=\"top\" width=\"50%\" nowrap=\"nowrap\">";

            if (is_null($folder_info[$folder_number]['STATUS']) || $folder_info[$folder_number]['STATUS'] & USER_PERM_THREAD_CREATE) {

                echo "<a href=\"";
                echo $folder_info[$folder_number]['ALLOWED_TYPES']&FOLDER_ALLOW_NORMAL_THREAD ? "./post.php?webtag=$webtag" : "./create_poll.php?webtag=$webtag";
                echo "&amp;fid=".$folder_number."\" target=\"main\" class=\"folderpostnew\" style=\"", ($lang['_textdir'] == "ltr") ? "text-align: right; float: right" : "text-align: left; float: left", "\">{$lang['postnew']}</a>";

            }else {

                echo "&nbsp;";
            }

            echo "</td>\n";
            echo "  </tr>\n";

            if ($start_from != 0 && isset($folder) && $folder_number == $folder) {
                echo "  <tr>\n";
                echo "    <td class=\"threads\" style=\"border-top: 0px; border-bottom: 0px;\" colspan=\"2\"><a href=\"thread_list.php?webtag=$webtag&amp;mode=0&amp;folder=$folder&amp;start_from=".($start_from - 50)."\" class=\"folderinfo\">{$lang['prev50threads']}</a></td>\n";
                echo "  </tr>\n";
            }

            if ($visible_threads) {

                echo "  <tr>\n";
                echo "    <td class=\"threads\" style=\"border-top: 0px;\" colspan=\"2\">\n";
                echo "      <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";

                while (list($key2, $thread) = each($thread_info)) {

                    if (!isset($visiblethreads) || !is_array($visiblethreads)) $visiblethreads = array();
                    if (!in_array($thread['tid'], $visiblethreads)) $visiblethreads[] = $thread['tid'];

                    if ($thread['fid'] == $folder_number) {

                        echo "        <tr>\n";
                        echo "          <td valign=\"top\" align=\"center\" nowrap=\"nowrap\" width=\"20\">";
                        echo "<a href=\"thread_options.php?webtag=$webtag&amp;tid={$thread['tid']}\" target=\"right\">";

                        if ($thread['last_read'] == 0) {

                            if ($thread['length'] > 0) {
                                $number = "[{$thread['length']}&nbsp;{$lang['new']}]";
                            }else {
                                $number = "[1&nbsp;{$lang['new']}]";
                            }

                            $latest_post = 1;

                            if (!isset($first_thread) && isset($_GET['msg']) && validate_msg($_GET['msg'])) {
                                $first_thread = $thread['tid'];
                                echo "<img src=\"", style_image('current_thread.png'), "\" name=\"t{$thread['tid']}\" align=\"middle\" height=\"15\" alt=\"{$lang['threadoptions']}\" title=\"{$lang['threadoptions']}\" border=\"0\" />";
                            }else {
                                echo "<img src=\"", style_image('unread_thread.png'), "\" name=\"t{$thread['tid']}\" align=\"middle\" height=\"15\" alt=\"{$lang['threadoptions']}\" title=\"{$lang['threadoptions']}\" border=\"0\" />";
                            }

                        }elseif ($thread['last_read'] < $thread['length']) {

                            $new_posts = $thread['length'] - $thread['last_read'];
                            $number = "[{$new_posts}&nbsp;{$lang['new']}&nbsp;{$lang['of']}&nbsp;{$thread['length']}]";
                            $latest_post = $thread['last_read'] + 1;

                            if (!isset($first_thread) && isset($_GET['msg']) && validate_msg($_GET['msg'])) {
                                $first_thread = $thread['tid'];
                                echo "<img src=\"", style_image('current_thread.png'), "\" name=\"t{$thread['tid']}\" align=\"middle\" height=\"15\" alt=\"{$lang['threadoptions']}\" title=\"{$lang['threadoptions']}\" border=\"0\" />";
                            }else {
                                echo "<img src=\"", style_image('unread_thread.png'), "\" name=\"t{$thread['tid']}\" align=\"middle\" height=\"15\" alt=\"{$lang['threadoptions']}\" title=\"{$lang['threadoptions']}\" border=\"0\" />";
                            }

                        }else {

                            if ($thread['length'] > 0) {
                                $number = "[{$thread['length']}]";
                            }else {
                                $number = "[1]";
                            }

                            $latest_post = 1;

                            if (!isset($first_thread) && isset($_GET['msg']) && validate_msg($_GET['msg'])) {
                                $first_thread = $thread['tid'];
                                echo "<img src=\"", style_image('current_thread.png'), "\" name=\"t{$thread['tid']}\" align=\"middle\" height=\"15\" alt=\"{$lang['threadoptions']}\" title=\"{$lang['threadoptions']}\" border=\"0\" />";
                            } else {
                                echo "<img src=\"", style_image('bullet.png'), "\" name=\"t{$thread['tid']}\" align=\"middle\" height=\"15\" alt=\"{$lang['threadoptions']}\" title=\"{$lang['threadoptions']}\" border=\"0\" />";
                            }

                        }

                        echo "</a>";

                        // work out how long ago the thread was posted and format the time to display
                        $thread_time = format_time($thread['modified']);
                        // $thread_author = thread_get_author($thread['tid']);

                        echo "&nbsp;</td>\n";
                        echo "          <td valign=\"top\">";
                        echo "<a href=\"messages.php?webtag=$webtag&amp;msg={$thread['tid']}.{$latest_post}\" target=\"right\" class=\"threadname\" onclick=\"change_current_thread('{$thread['tid']}');\" title=\"#{$thread['tid']} {$lang['startedby']} ", format_user_name($thread['logon'], $thread['nickname']), "\">", apply_wordfilter($thread['title']), "</a> ";

                        if ($thread['interest'] == 1) echo "<img src=\"".style_image('high_interest.png')."\" height=\"15\" alt=\"{$lang['highinterest']}\" title=\"{$lang['highinterest']}\" align=\"middle\" /> ";
                        if ($thread['interest'] == 2) echo "<img src=\"".style_image('subscribe.png')."\" height=\"15\" alt=\"{$lang['subscribed']}\" title=\"{$lang['subscribed']}\" align=\"middle\" /> ";
                        if ($thread['poll_flag'] == 'Y') echo "<img src=\"".style_image('poll.png')."\" height=\"15\" alt=\"{$lang['poll']}\" title=\"{$lang['poll']}\" align=\"middle\" /> ";
                        if ($thread['sticky'] == 'Y') echo "<img src=\"".style_image('sticky.png')."\" height=\"15\" alt=\"{$lang['sticky']}\" title=\"{$lang['sticky']}\" align=\"middle\" /> ";
                        if ($thread['relationship'] & USER_FRIEND) echo "<img src=\"" . style_image('friend.png') . "\" height=\"15\" alt=\"{$lang['friend']}\" title=\"{$lang['friend']}\" align=\"middle\" /> ";

                        if (thread_has_attachments($thread['tid'])) echo "<img src=\"" . style_image('attach.png') . "\" height=\"15\" alt=\"{$lang['attachment']}\" title=\"{$lang['attachment']}\" align=\"middle\" /> ";

                        echo "<bdo dir=\"{$lang['_textdir']}\"><span class=\"threadxnewofy\">{$number}</span></bdo></td>\n";
                        echo "          <td valign=\"top\" nowrap=\"nowrap\" align=\"right\"><span class=\"threadtime\">{$thread_time}&nbsp;</span></td>\n";
                        echo "        </tr>\n";

                    }
                }

                if (isset($folder) && $folder_number == $folder) {

                    $more_threads = $folder_msgs[$folder] - $start_from - 50;

                    if ($more_threads > 0 && $more_threads <= 50) {
                        echo "        <tr>\n";
                        echo "          <td colspan=\"3\"><a href=\"thread_list.php?webtag=$webtag&amp;mode=0&amp;folder=$folder&amp;start_from=".($start_from + 50)."\" class=\"folderinfo\">{$lang['next']} $more_threads {$lang['threads']}</a></td>\n";
                        echo "        </tr>\n";
                    }

                    if ($more_threads > 50) {
                        echo "        <tr>\n";
                        echo "          <td colspan=\"3\"><a href=\"thread_list.php?webtag=$webtag&amp;mode=0&amp;folder=$folder&amp;start_from=".($start_from + 50)."\" class=\"folderinfo\">{$lang['next50threads']}</a></td>\n";
                        echo "        </tr>\n";
                    }

                }

                echo "      </table>\n";
                echo "    </td>\n";
                echo "  </tr>\n";

            }

        }elseif ($folder_info[$folder_number]['INTEREST'] != -1) {

            // Only display the additional folder info if the user _DOESN'T_ have the folder on ignore

            echo "  <tr>\n";
            echo "    <td class=\"threads\" style=\"", ($lang['_textdir'] == 'ltr') ? "border-right: 1px" : "border-left: 1px", "\" align=\"left\" valign=\"top\" width=\"50%\" nowrap=\"nowrap\"><a href=\"thread_list.php?webtag=$webtag&amp;mode=0&amp;folder=".$folder_number."\" class=\"folderinfo\">";

            if (isset($folder_msgs[$folder_number])) {
                echo $folder_msgs[$folder_number];
            }else {
                echo "0";
            }

            echo "&nbsp;{$lang['threads']}</a></td>\n";
            echo "    <td class=\"threads\" style=\"", ($lang['_textdir'] == "ltr") ? "border-left: 0px" : "border-right: 0px", "\" valign=\"top\" width=\"50%\" nowrap=\"nowrap\">";

            if (perm_check_folder_permissions($folder_number, USER_PERM_THREAD_CREATE)) {

                echo "<a href=\"";
                echo $folder_info[$folder_number]['ALLOWED_TYPES']&FOLDER_ALLOW_NORMAL_THREAD ? "./post.php?webtag=$webtag" : "./create_poll.php?webtag=$webtag";
                echo "&amp;fid=".$folder_number."\" target=\"main\" class=\"folderpostnew\" style=\"", ($lang['_textdir'] == "ltr") ? "text-align: right; float: right" : "text-align: left; float: left", "\">{$lang['postnew']}</a>";

            }else {

                echo "&nbsp;";
            }

            echo "</td>\n";
            echo "  </tr>\n";

        }

    }

    if (is_array($thread_info)) reset($thread_info);
}

if ($mode == 0 && !isset($folder)) {

    $total_threads = 0;

    if (is_array($folder_msgs)) {

      while (list($fid, $num_threads) = each($folder_msgs)) {
        $total_threads += $num_threads;
      }

      $more_threads = $total_threads - $start_from - 50;
      if ($more_threads > 0 && $more_threads <= 50) echo "<tr><td colspan=\"2\">&nbsp;</td></tr><tr><td class=\"smalltext\" colspan=\"2\"><img src=\"".style_image('current_thread.png')."\" height=\"15\" alt=\"{$lang['next']} $more_threads {$lang['threads']}\" title=\"{$lang['next']} $more_threads {$lang['threads']}\" />&nbsp;<a href=\"thread_list.php?webtag=$webtag&amp;mode=0&amp;start_from=".($start_from + 50)."\">{$lang['next']} $more_threads {$lang['threads']}</td></tr>\n";
      if ($more_threads > 50) echo "<tr><td colspan=\"2\">&nbsp;</td></tr><tr><td class=\"smalltext\" colspan=\"2\"><img src=\"".style_image('current_thread.png')."\" height=\"15\" alt=\"{$lang['next50threads']}\" title=\"{$lang['next50threads']}\" />&nbsp;<a href=\"thread_list.php?webtag=$webtag&amp;mode=0&amp;start_from=".($start_from + 50)."\">{$lang['next50threads']}</a></td></tr>\n";

    }
}

echo "  <tr>\n";
echo "    <td>&nbsp;</td>\n";
echo "  </tr>\n";
echo "</table>\n";

if (bh_session_get_value('UID') != 0) {

    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
    echo "  <tr>\n";
    echo "    <td class=\"smalltext\" colspan=\"2\">{$lang['markasread']}:</td>\n";
    echo "  </tr>\n";

    echo "  <tr>\n";
    echo "    <td>&nbsp;</td>\n";
    echo "    <td class=\"smalltext\">\n";
    echo "      <form name=\"f_mark\" method=\"get\" action=\"thread_list.php\">\n";
    echo "        ", form_input_hidden('webtag', $webtag), "\n";

    $labels = array($lang['alldiscussions'], $lang['next50discussions']);

    if (isset($visiblethreads) && is_array($visiblethreads)) {

        $labels[] = $lang['visiblediscussions'];

        for ($i = 0; $i < sizeof($visiblethreads); $i++) {
            echo "        ", form_input_hidden("tid_array[]", $visiblethreads[$i]), "\n";
        }
    }

    echo "        ", form_dropdown_array("markread", range(0, sizeof($labels) -1), $labels, 0). "\n";
    echo "        ", form_submit("go",$lang['goexcmark']). "\n";
    echo "      </form>\n";
    echo "    </td>\n";
    echo "  </tr>\n";
    echo "</table>\n";
}

echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
echo "  <tr>\n";
echo "    <td class=\"smalltext\" colspan=\"2\">{$lang['navigate']}:</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td>&nbsp;</td>\n";
echo "    <td class=\"smalltext\">\n";
echo "      <form name=\"f_nav\" method=\"get\" action=\"messages.php\" target=\"right\">\n";
echo "        ", form_input_hidden('webtag', $webtag), "\n";
echo "        ", form_input_text('msg', '1.1', 10), "\n";
echo "        ", form_submit("go",$lang['goexcmark']), "\n";
echo "      </form>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "</table>\n";
echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
echo "  <tr>\n";
echo "    <td class=\"smalltext\" colspan=\"2\">{$lang['search']} (<a href=\"search.php?webtag=$webtag\" target=\"right\">{$lang['advanced']}</a>):</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td>&nbsp;</td>\n";
echo "    <td class=\"smalltext\">\n";
echo "      <form method=\"post\" action=\"search.php\" target=\"_self\">\n";
echo "        ", form_input_hidden('webtag', $webtag), "\n";
echo "        ", form_input_text("search_string", "", 20). "\n";
echo "        ", form_submit("submit", $lang['find']). "\n";
echo "      </form>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "</table>\n";

echo "<script language=\"JavaScript\" type=\"text/javascript\">\n";
echo "<!--\n";

if (isset($first_thread)) {
    echo "current_thread = ".$first_thread.";\n";
}else {
    echo "current_thread = 0;\n";
}

echo "// -->";
echo "</script>\n";

html_draw_bottom();

?>