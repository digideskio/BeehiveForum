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

// Enable the error handler
require_once("./include/errorhandler.inc.php");

// Compress the output
require_once("./include/gzipenc.inc.php");

// Frameset for thread list and messages

//Check logged in status
require_once("./include/session.inc.php");
require_once("./include/header.inc.php");
require_once("./include/form.inc.php");

if(!bh_session_check()){

    $uri = "./index.php?final_uri=". urlencode(get_request_uri());
    header_redirect($uri);

}

$uid = bh_session_get_value('UID');

require_once("./include/perm.inc.php");
require_once("./include/html.inc.php");
require_once("./include/constants.inc.php");
require_once("./include/db.inc.php");
require_once("./include/format.inc.php");
require_once("./include/thread.inc.php");
require_once("./include/folder.inc.php");
require_once("./include/lang.inc.php");

html_draw_top_script();

echo "<table class=\"posthead\" border=\"0\" width=\"200\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "  <tr>\n";
echo "    <td class=\"subhead\">{$lang['recentthreads']}</td>\n";
echo "  </tr>\n";

// Get available folders
$fidlist = folder_get_available();

$db = db_connect();

// Get most recent threads
$sql  = "SELECT T.TID, T.TITLE, T.LENGTH, UT.LAST_READ, UT.INTEREST, U.NICKNAME, U.LOGON ";
$sql .= "FROM ".forum_table("THREAD")." T ";
$sql .= "LEFT JOIN ".forum_table("USER_THREAD")." UT ";
$sql .= "ON (T.TID = UT.TID and UT.UID = $uid) ";
$sql .= "JOIN " . forum_table("USER") . " U ";
$sql .= "JOIN " . forum_table("POST") . " P ";
$sql .= "LEFT JOIN " . forum_table("USER_FOLDER") . " UF ON ";
$sql .= "(UF.FID = T.FID AND UF.UID = $uid) ";
$sql .= "WHERE T.FID IN ($fidlist) ";
$sql .= "AND U.UID = P.FROM_UID ";
$sql .= "AND P.TID = T.TID ";
$sql .= "AND P.PID = 1 ";
$sql .= "AND NOT (UT.INTEREST <=> -1) ";
$sql .= "AND NOT (UF.INTEREST <=> -1) ";
$sql .= "ORDER BY T.MODIFIED desc ";
$sql .= "LIMIT 0, 10";

$result = db_query($sql, $db);

echo "  <tr>\n";
echo "    <td>\n";
echo "      <table class=\"posthead\" border=\"0\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">\n";

while($row = db_fetch_array($result)){

    $tid = $row['TID'];

    if (isset($row['LAST_READ']) && $row['LAST_READ'] && $row['LENGTH'] > $row['LAST_READ']){
        $pid = $row['LAST_READ'] + 1;
    } else {
        $pid = 1;
    }

    echo "        <tr>\n";
    echo "          <td valign=\"top\" align=\"center\" nowrap=\"nowrap\">";

    if (!isset($row['LAST_READ'])) {
        echo "<img src=\"".style_image('unread_thread.png')."\" name=\"t".$row['TID']."\" align=\"middle\" alt=\"{$lang['unreadthread']}\" />";
    }else if ($row['LAST_READ'] == 0 || $row['LAST_READ'] < $row['LENGTH']) {
        echo "<img src=\"".style_image('unread_thread.png')."\" name=\"t".$row['TID']."\" align=\"middle\" alt=\"{$lang['unreadmessages']}\" />";
    }else if ($row['LAST_READ'] == $row['LENGTH']) {
        echo "<img src=\"".style_image('bullet.png')."\" name=\"t".$row['TID']."\" align=\"middle\" alt=\"{$lang['readthread']}\" />";
    }

    echo "<bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
    echo "          <td><a href=\"discussion.php?msg=$tid.$pid\" target=\"main\" title=\"#$tid Started by " . format_user_name($row['LOGON'], $row['NICKNAME']) . "\">";
    echo _stripslashes($row['TITLE'])."</a><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo>";

    if (isset($row['INTEREST']) && $row['INTEREST'] == 1) echo "<img src=\"".style_image('high_interest.png')."\" alt=\"{$lang['highinterest']}\" align=\"middle\" />";
    if (isset($row['INTEREST']) && $row['INTEREST'] == 2) echo "<img src=\"".style_image('subscribe.png')."\" alt=\"{$lang['subscribed']}\" align=\"middle\" />";

    echo "          </td>\n";
    echo "        </tr>\n";

}

echo "      </table>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
echo "  </tr>\n";

// Display "Start Reading" button
echo "  <tr>\n";
echo "    <td align=\"center\">\n";
echo "      <table class=\"posthead\" border=\"0\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "        <tr>\n";
echo "          <td valign=\"top\" align=\"center\" nowrap=\"nowrap\">", form_quick_button("discussion.php","{$lang['startreading']} >>", 0, 0, "main"), "</td>\n";
echo "        </tr>\n";
echo "      </table>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td class=\"subhead\">{$lang['threadoptions']}</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td class=\"postbody\" colspan=\"2\">\n";
echo "      <table class=\"posthead\" border=\"0\" width=\"80%\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\">\n";
echo "        <tr>\n";
echo "          <td valign=\"top\" nowrap=\"nowrap\"><img src=\"", style_image('post.png'), "\" height=\"15\" alt=\"\" /><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo><a href=\"post.php\" target=\"main\">{$lang['newdiscussion']}</a></td>\n";
echo "        </tr>\n";
echo "        <tr>\n";
echo "          <td valign=\"top\" nowrap=\"nowrap\"><img src=\"", style_image('poll.png'), "\" height=\"15\" alt=\"\" /><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo><a href=\"create_poll.php\" target=\"main\">{$lang['createpoll']}</a></td>\n";
echo "        </tr>\n";
echo "      </table>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td class=\"subhead\">{$lang['recentvisitors']}</td>\n";
echo "  </tr>\n";

// Get recent visitors
$sql = "select U.UID, U.LOGON, U.NICKNAME, UNIX_TIMESTAMP(U.LAST_LOGON) as LAST_LOGON ";
$sql.= "from ".forum_table("USER")." U ";
$sql.= "order by U.LAST_LOGON desc ";
$sql.= "limit 0, 10";

$result = db_query($sql, $db);

echo "  <tr>\n";
echo "    <td>\n";
echo "      <table class=\"posthead\" border=\"0\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\">\n";

while($row = db_fetch_array($result)){

    echo "        <tr>\n";
    echo "          <td valign=\"top\" align=\"center\" nowrap=\"nowrap\"><img src=\"".style_image('bullet.png')."\" width=\"12\" height=\"16\" alt=\"bullet\" /></td>\n";
    echo "          <td><a href=\"#\" target=\"_self\" onclick=\"openProfile(".$row['UID'].")\">". $row['NICKNAME']. "</a></td>\n";
    echo "          <td align=\"right\" nowrap=\"nowrap\">". format_time($row['LAST_LOGON']). "<bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
    echo "        </tr>\n";

}

echo "      </table>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td align=\"center\"><img src=\"", style_image('post.png'), "\" height=\"15\" alt=\"\" /><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo><a href=\"visitor_log.php\" target=\"right\">{$lang['showmorevisitors']}</a><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
echo "  </tr>\n";

if ($birthdays = user_get_forthcoming_birthdays()) {
    echo "  <tr>\n";
    echo "    <td class=\"subhead\" colspan=\"2\">{$lang['forthcomingbirthdays']}</td>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <td>\n";
    echo "      <table class=\"posthead\" border=\"0\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\">\n";
    foreach ($birthdays as $row) {
        echo "        <tr>\n";
        echo "          <td valign=\"top\" align=\"center\" nowrap=\"nowrap\"><img src=\"".style_image('bullet.png')."\" width=\"12\" height=\"16\" alt=\"bullet\" /></td>\n";
        echo "          <td><a href=\"#\" target=\"_self\" onclick=\"openProfile(".$row['UID'].")\">". $row['NICKNAME']. "</a></td>\n";
        echo "          <td align=\"right\" nowrap=\"nowrap\">". format_birthday($row['DOB']). "<bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
        echo "        </tr>\n";
    }
    echo "      </table>\n";
    echo "    </td>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <td><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
    echo "  </tr>\n";
}

echo "  <tr>\n";
echo "    <td class=\"subhead\" colspan=\"2\">{$lang['navigate']}</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td>\n";
echo "      <table class=\"posthead\" border=\"0\" width=\"80%\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\">\n";
echo "        <tr>\n";
echo "          <td>\n";
echo "            <form name=\"f_nav\" method=\"get\" action=\"discussion.php\" target=\"main\">\n";
echo "              ", form_input_text('msg', '1.1', 10). "\n";
echo "              ", form_submit("go",$lang['goexcmark']). "\n";
echo "            </form>\n";
echo "          </td>\n";
echo "        </tr>\n";
echo "      </table>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td><bdo dir=\"{$lang['_textdir']}\">&nbsp;</bdo></td>\n";
echo "  </tr>\n";
echo "</table>\n";

html_draw_bottom();

?>
