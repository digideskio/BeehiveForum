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

// Frameset for thread list and messages

// Enable the error handler
require_once("./include/errorhandler.inc.php");

// Compress the output
require_once("./include/gzipenc.inc.php");

//Check logged in status
require_once("./include/session.inc.php");
require_once("./include/header.inc.php");

if(!bh_session_check()){

    $uri = "./logon.php?final_uri=". urlencode(get_request_uri());
    header_redirect($uri);

}

require_once("./include/perm.inc.php");
require_once("./include/html.inc.php");
require_once("./include/forum.inc.php");
require_once("./include/db.inc.php");
require_once("./include/format.inc.php");
require_once("./include/constants.inc.php");

html_draw_top();

if(!($HTTP_COOKIE_VARS['bh_sess_ustatus'] & USER_PERM_SOLDIER)){
    echo "<h1>Access Denied</h1>\n";
    echo "<p>You do not have permission to use this section.</p>";
    html_draw_bottom();
    exit;
}

// Column sorting stuff

if (isset($HTTP_GET_VARS['sort_by'])) {
    if ($HTTP_GET_VARS['sort_by'] == "LOG_ID") {
        $sort_by = "ADMIN_LOG.LOG_ID";
    } elseif ($HTTP_GET_VARS['sort_by'] == "ADMIN_UID") {
        $sort_by = "ADMIN_LOG.ADMIN_UID";
    } elseif ($HTTP_GET_VARS['sort_by'] == "ACTION") {
        $sort_by = "ADMIN_LOG.ACTION";
    }
} else {
    $sort_by = "ADMIN_LOG.LOG_TIME";
}

if (isset($HTTP_GET_VARS['sort_dir'])) {
    if ($HTTP_GET_VARS['sort_dir'] == "DESC") {
        $sort_dir = "DESC";
    } else {
        $sort_dir = "ASC";
    }
} else {
    $sort_dir = "DESC";
}

if (isset($HTTP_GET_VARS['offset'])) {
    $start = $HTTP_GET_VARS['offset'];
}else {
    $start = 0;
}

$db = db_connect();

if (isset($HTTP_POST_VARS['clear'])) {

    $sql = "DELETE FROM ". forum_table("ADMIN_LOG");
    $result = db_query($sql, $db);

}

// Draw the form
echo "<h1>Admin Access Log</h1>\n";
echo "<p>This list shows the last 20 actions sanctioned by users with Admin privileges.</p>\n";
echo "<div align=\"center\">\n";
echo "<table width=\"96%\" class=\"box\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "  <tr>\n";
echo "    <td class=\"posthead\">\n";
echo "      <table width=\"100%\">\n";
echo "        <tr>\n";

if ($sort_by == 'UID' && $sort_dir == 'ASC') {
  echo "          <td class=\"subhead\" width=\"100\" align=\"left\"><a href=\"", $HTTP_SERVER_VARS['PHP_SELF'], "?sort_by=LOG_TIME&amp;sort_dir=DESC\">Date/Time</a></td>\n";
}else {
  echo "          <td class=\"subhead\" width=\"100\" align=\"left\"><a href=\"", $HTTP_SERVER_VARS['PHP_SELF'], "?sort_by=LOG_TIME&amp;sort_dir=ASC\">Date/Time</a></td>\n";
}

if ($sort_by == 'LOGON' && $sort_dir == 'ASC') {
  echo "          <td class=\"subhead\" width=\"200\" align=\"left\"><a href=\"", $HTTP_SERVER_VARS['PHP_SELF'], "?sort_by=ADMIN_UID&amp;sort_dir=DESC\">Logon</a></td>\n";
}else {
  echo "          <td class=\"subhead\" width=\"200\" align=\"left\"><a href=\"", $HTTP_SERVER_VARS['PHP_SELF'], "?sort_by=ADMIN_UID&amp;sort_dir=ASC\">Logon</a></td>\n";
}

if ($sort_by == 'STATUS' && $sort_dir == 'ASC') {
  echo "          <td class=\"subhead\" align=\"left\"><a href=\"", $HTTP_SERVER_VARS['PHP_SELF'], "?sort_by=ACTION&amp;sort_dir=DESC\">Action</a></td>\n";
}else {
  echo "          <td class=\"subhead\" align=\"left\"><a href=\"", $HTTP_SERVER_VARS['PHP_SELF'], "?sort_by=ACTION&amp;sort_dir=ASC\">Action</a></td>\n";
}

echo "        </tr>\n";

$sql = "SELECT ADMIN_LOG.LOG_ID, UNIX_TIMESTAMP(ADMIN_LOG.LOG_TIME) AS LOG_TIME, ADMIN_LOG.ADMIN_UID, ";
$sql.= "ADMIN_LOG.UID, AUSER.LOGON AS ALOGON, AUSER.NICKNAME AS ANICKNAME, USER.LOGON, USER.NICKNAME, ";
$sql.= "ADMIN_LOG.FID, ADMIN_LOG.TID, ADMIN_LOG.PID, FOLDER.TITLE AS FOLDER_TITLE, THREAD.TITLE AS THREAD_TITLE, ";
$sql.= "ADMIN_LOG.PSID, ADMIN_LOG.PIID, PS.NAME AS PS_NAME, PI.NAME AS PI_NAME, ADMIN_LOG.ACTION ";
$sql.= "FROM ". forum_table("ADMIN_LOG"). " ADMIN_LOG ";
$sql.= "LEFT JOIN ". forum_table("USER"). " AUSER ON (AUSER.UID = ADMIN_LOG.ADMIN_UID) ";
$sql.= "LEFT JOIN ". forum_table("USER"). " USER ON (USER.UID = ADMIN_LOG.UID) ";
$sql.= "LEFT JOIN ". forum_table("PROFILE_SECTION"). " PS ON (PS.PSID = ADMIN_LOG.PSID) ";
$sql.= "LEFT JOIN ". forum_table("PROFILE_ITEM"). " PI ON (PI.PIID = ADMIN_LOG.PIID) ";
$sql.= "LEFT JOIN ". forum_table("FOLDER"). " FOLDER ON (FOLDER.FID = ADMIN_LOG.FID) ";
$sql.= "LEFT JOIN ". forum_table("THREAD"). " THREAD ON (THREAD.TID = ADMIN_LOG.TID) ";
$sql.= "ORDER BY $sort_by $sort_dir LIMIT $start, 20";

$result = db_query($sql, $db);

if (db_num_rows($result)) {

    while ($row = db_fetch_array($result)) {

        echo "        <tr>\n";
        echo "          <td class=\"posthead\" align=\"left\">", format_time($row['LOG_TIME']), "</td>\n";
        echo "          <td class=\"posthead\" align=\"left\"><a href=\"./admin_user.php?uid=", $row['ADMIN_UID'], "\">", format_user_name($row['ALOGON'], $row['ANICKNAME']), "</a></td>\n";

        if (!empty($row['LOGON']) && !empty($row['NICKNAME'])) {
            $user = "<a href=\"./admin_user.php?uid=". $row['UID']. "\">";
            $user.= format_user_name($row['LOGON'], $row['NICKNAME']). "</a>";
        }else {
            $user = "Unknown User (UID: ". $row['UID']. ")";
        }

        if (isset($row['FID']) && $row['FID'] > 0) {
            $title = $row['FID'];
        }else {
            $title = "Unknown Folder";
        }

        if (isset($row['TID']) && $row['TID'] > 0) {
            $tid = $row['TID'];
        }

        if (isset($row['PID']) && $row['PID'] > 0) {
            $pid = $row['PID'];
        }

        if (isset($row['FOLDER_TITLE']) && !empty($row['FOLDER_TITLE'])) {
            $folder_title = $row['FOLDER_TITLE'];
        }else {
            $folder_title = "Unknown (FID: ". $row['FID']. ")";
        }

        if (isset($row['THREAD_TITLE']) && !empty($row['THREAD_TITLE'])) {
            $thread_title = $row['THREAD_TITLE'];
        }else {
            $thread_title = "Unknown (TID: ". $row['TID']. ")";
        }

        if (isset($row['PS_NAME']) && !empty($row['PS_NAME'])) {
            $ps_name = $row['PS_NAME'];
        }else {
            $ps_name = "Unknown (PSID: ". $row['PSID']. ")";
        }

        if (isset($row['PI_NAME']) && !empty($row['PI_NAME'])) {
            $pi_name = $row['PI_NAME'];
        }else {
            $pi_name = "Unknown (PIID: ". $row['PIID']. ")";
        }

        switch ($row['ACTION']) {
            case 1:
                $action_text = "Changed User Status for User: $user";
                break;
            case 2:
                $action_text = "Changed User Folder Access Privs for User: $user";
                break;
            case 3:
                $action_text = "Deleted All Posts for User: $user";
                break;
            case 4:
                $action_text = "Banned User $user's IP Address";
                break;
            case 5:
                $action_text = "Unbanned User $user's IP Address";
                break;
            case 6:
                $action_text = "Deleted User $user's attachment";
                break;
            case 7:
                $action_text = "Changed Folder Title / Access Privs for folder: '$folder_title'";
                break;
            case 8:
                $action_text = "Moved threads to folder: '$folder_title'";
                break;
            case 9:
                $action_text = "Created new folder: '$folder_title'";
                break;
            case 10:
                $action_text = "Changed Profile Section title for section: $ps_name";
                break;
            case 11:
                $action_text = "Added New Profile Section: $ps_name";
                break;
            case 12:
                $action_text = "Deleted Profile Section: $ps_name";
                break;
            case 13:
                $action_text = "Changed Profile Item title for item: $pi_name";
                break;
            case 14:
                $action_text = "Added New Profile Item: $pi_name";
                break;
            case 15:
                $action_text = "Deleted Profile Item: $pi_name";
                break;
            case 16:
                $action_text = "Edited Start Page";
                break;
            case 17:
                $action_text = "Saved New Style";
                break;
            case 18:
                $action_text = "Moved Thread: '$thread_title'";
                break;
            case 19:
                $action_text = "Closed Thread: '$thread_title'";
                break;
            case 20:
                $action_text = "Opened Thread: '$thread_title'";
                break;
            case 21:
                $action_text = "Renamed Thread: '$thread_title'";
                break;
            case 22:
                $action_text = "Deleted Post: $tid.$pid";
                break;
            case 23:
                $action_text = "Edited Post: $tid.$pid";
                break;
            case 24:
                $action_text = "Edited Word Filter";
                break;
            default:
                $action_text = "Unknown";
                break;

        }

        unset($user, $title, $tid, $pid, $title, $ps_name, $pi_name);

        echo "          <td class=\"posthead\" align=\"left\">", $action_text, "</td>\n";
        echo "        </tr>\n";

    }

}else {

    echo "        <tr>\n";
    echo "          <td class=\"posthead\" colspan=\"3\" align=\"left\">Admin Log is empty</td>\n";
    echo "        </tr>\n";

}

echo "      </table>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "</table>\n";

if (db_num_rows($result) == 20) {
  if ($start < 20) {
    echo "<p><img src=\"", style_image('post.png'), "\" height=\"15\" alt=\"\" />&nbsp;<a href=\"admin_viewlog.php?offset=", $start + 20, "\" target=\"_self\">More</a></p>\n";
  }elseif ($start >= 20) {
    echo "<p><img src=\"", style_image('post.png'), "\" height=\"15\" alt=\"\" />&nbsp;<a href=\"admin_viewlog.php\" target=\"_self\">Recent Entries</a>&nbsp;&nbsp;";
    echo "<img src=\"", style_image('post.png'), "\" height=\"15\" alt=\"\" />&nbsp;<a href=\"admin_viewlog.php?offset=", $start + 20, "\" target=\"_self\">More</a></p>\n";
  }
}else {
  if ($start >= 20) {
    echo "<p><img src=\"", style_image('post.png'), "\" height=\"15\" alt=\"\" />&nbsp;<a href=\"admin_viewlog.php\" target=\"_self\">Recent Visitors</a>&nbsp;&nbsp;";
  }
}

echo "</div>\n";
echo "<p>&nbsp;</p>\n";

if ($HTTP_COOKIE_VARS['bh_sess_ustatus'] & USER_PERM_QUEEN && db_num_rows($result)) {
    echo "<form name=\"f_post\" action=\"" . get_request_uri() . "\" method=\"post\" target=\"_self\">\n";
    echo form_submit('clear', 'Clear Log');
    echo "</form>\n";
}

html_draw_bottom();

?>