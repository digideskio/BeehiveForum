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

// Compress the output
require_once("./include/gzipenc.inc.php");

// Frameset for thread list and messages

//Check logged in status
require_once("./include/session.inc.php");
require_once("./include/header.inc.php");
require_once("./include/form.inc.php");

if(!bh_session_check()){

    $uri = "./logon.php?final_uri=". urlencode(get_request_uri());
    header_redirect($uri);

}

$uid = $HTTP_COOKIE_VARS['bh_sess_uid'];

require_once("./include/perm.inc.php");
require_once("./include/html.inc.php");
require_once("./include/constants.inc.php");
require_once("./include/db.inc.php");
require_once("./include/format.inc.php");
require_once("./include/thread.inc.php");
require_once("./include/folder.inc.php");

html_draw_top_script();

echo "<table class=\"posthead\" border=\"0\" width=\"200\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "  <tr>\n";
echo "    <td class=\"subhead\">Recent threads</td>\n";
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
$sql .= "WHERE T.FID IN ($fidlist) ";
$sql .= "AND U.UID = P.FROM_UID ";
$sql .= "AND P.TID = T.TID ";
$sql .= "AND P.PID = 1 ";
$sql .= "AND (UT.INTEREST >= 0 or UT.INTEREST is null) ";
$sql .= "ORDER BY T.MODIFIED desc ";
$sql .= "LIMIT 0, 10";

$result = db_query($sql, $db);

echo "  <tr>\n";
echo "    <td>\n";
echo "      <table class=\"posthead\" border=\"0\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">\n";

while($row = db_fetch_array($result)){

    $tid = $row['TID'];

    if($row['LAST_READ'] && $row['LENGTH'] > $row['LAST_READ']){
        $pid = $row['LAST_READ'] + 1;
    } else {
        $pid = 1;
    }

    echo "        <tr>\n";
    echo "          <td valign=\"top\" align=\"middle\" nowrap=\"nowrap\">";

    if (($row['LAST_READ'] == 0) || ($row['LAST_READ'] < $row['LENGTH'])) {
        echo "<img src=\"".style_image('unread_thread.png')."\" name=\"t".$row['TID']."\" align=\"absmiddle\" />";
    } elseif ($row['LAST_READ'] == $row['LENGTH']) {
        echo "<img src=\"".style_image('bullet.png')."\" name=\"t".$row['TID']."\" align=\"absmiddle\" />";
    }

    echo "&nbsp;</td>\n";
    echo "          <td><a href=\"discussion.php?msg=$tid.$pid\" target=\"main\" title=\"#$tid Started by " . format_user_name($row['LOGON'], $row['NICKNAME']) . "\">";
    echo _stripslashes($row['TITLE'])."</a>&nbsp;";

    if ($row['INTEREST'] == 1) echo "<img src=\"".style_image('high_interest.png')."\" alt=\"High Interest\" align=\"middle\">";
    if ($row['INTEREST'] == 2) echo "<img src=\"".style_image('subscribe.png')."\" alt=\"Subscribed\" align=\"middle\">";

    echo "          </td>\n";
    echo "        </tr>\n";

}

echo "      </table>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td>&nbsp;</td>\n";
echo "  </tr>\n";

// Display "Start Reading" button
echo "  <tr>\n";
echo "    <td align=\"center\">", form_quick_button("discussion.php","Start reading >>", 0, 0, "main"), "</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td>&nbsp;</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td class=\"subhead\">Recent visitors</td>\n";
echo "  </tr>\n";

// Get recent visitors
$sql = "select U.UID, U.LOGON, U.NICKNAME, UNIX_TIMESTAMP(U.LAST_LOGON) as LAST_LOGON ";
$sql.= "from ".forum_table("USER")." U ";
$sql.= "order by U.LAST_LOGON desc ";
$sql.= "limit 0, 10";

$result = db_query($sql, $db);

echo "  <tr>\n";
echo "    <td>\n";
echo "      <table class=\"posthead\" border=\"0\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">\n";

while($row = db_fetch_array($result)){

    echo "        <tr>\n";
    echo "          <td valign=\"top\" align=\"middle\" nowrap=\"nowrap\"><img src=\"".style_image('bullet.png')."\" width=\"12\" height=\"16\" /></td>\n";
    echo "          <td><a href=\"#\" target=\"_self\" onclick=\"openProfile(".$row['UID'].")\">". $row['NICKNAME']. "</a></td>\n";
    echo "          <td align=\"right\" nowrap=\"nowrap\">". format_time($row['LAST_LOGON']). "&nbsp;</td>\n";
    echo "        </tr>\n";

}

echo "      </table>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td>&nbsp;</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td class=\"subhead\" colspan=\"2\">Navigate:</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td>&nbsp;</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td>\n";
echo "      <form name=\"f_nav\" method=\"get\" action=\"discussion.php\" target=\"main\">\n";
echo form_input_text('msg', '1.1', 10). "\n        ";
echo form_submit("go","Go!"). "\n";
echo "      </form>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "</table>\n";

html_draw_bottom();

?>