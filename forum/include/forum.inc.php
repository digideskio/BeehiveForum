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

/* $Id: forum.inc.php,v 1.68 2004-06-13 11:49:07 decoyduck Exp $ */

include_once("./include/constants.inc.php");
include_once("./include/db.inc.php");
include_once("./include/form.inc.php");
include_once("./include/header.inc.php");
include_once("./include/html.inc.php");
include_once("./include/lang.inc.php");
include_once("./include/session.inc.php");

function get_table_prefix()
{
    static $forum_data = false;

    if (!$forum_data) {

        $db_get_table_prefix = db_connect();

        if (!$uid = bh_session_get_value('UID')) $uid = 0;

        if (isset($_GET['webtag'])) {
            $webtag = trim($_GET['webtag']);
        }else if (isset($_POST['webtag'])) {
            $webtag = trim($_POST['webtag']);
        }else {
            $webtag = false;
        }

        if (!is_bool($webtag)) {

            // Check #1: See if the webtag specified in GET/POST
            // actually exists.

            $sql = "SELECT F.FID, CONCAT(F.WEBTAG, '', '_') AS PREFIX  FROM FORUMS F ";
            $sql.= "LEFT JOIN USER_FORUM UF ON (UF.FID = F.FID AND UF.UID = '$uid') ";
            $sql.= "WHERE (F.ACCESS_LEVEL = 0 OR (F.ACCESS_LEVEL = 1 AND UF.ALLOWED <=> 1))";
            $sql.= "AND F.WEBTAG = '$webtag'";

            $result = db_query($sql, $db_get_table_prefix);

            if (db_num_rows($result) > 0) {
                $forum_data = db_fetch_array($result);
                return $forum_data;
            }
        }

        if (is_bool($webtag)) {

            // Check #2: Try and select a default webtag from
            // the databse

            $sql = "SELECT F.FID, CONCAT(F.WEBTAG, '', '_') AS PREFIX  FROM FORUMS F ";
	    $sql.= "LEFT JOIN USER_FORUM UF ON (UF.FID = F.FID AND UF.UID = '$uid') ";
            $sql.= "WHERE (F.ACCESS_LEVEL = 0 OR (F.ACCESS_LEVEL = 1 AND UF.ALLOWED <=> 1))";
	    $sql.= "AND F.DEFAULT_FORUM = 1";

            $result = db_query($sql, $db_get_table_prefix);

            if (db_num_rows($result) > 0) {
                $forum_data = db_fetch_array($result);
	        return $forum_data;
            }
        }

        return false;
    }

    return $forum_data;
}

function get_webtag(&$webtag_search)
{
    static $webtag_data = false;

    if (!$webtag_data) {

        $db_get_webtag = db_connect();

        if (!$uid = bh_session_get_value('UID')) $uid = 0;

        if (isset($_GET['webtag'])) {
            $webtag = trim($_GET['webtag']);
        }else if (isset($_POST['webtag'])) {
            $webtag = trim($_POST['webtag']);
        }else {
            $webtag = false;
        }

        if (!is_bool($webtag)) {

            // Check #1: See if the webtag specified in GET/POST
            // actually exists.

            $sql = "SELECT F.WEBTAG FROM FORUMS F ";
            $sql.= "LEFT JOIN USER_FORUM UF ON (UF.FID = F.FID AND UF.UID = '$uid') ";
            $sql.= "WHERE (F.ACCESS_LEVEL = 0 OR (F.ACCESS_LEVEL = 1 AND UF.ALLOWED <=> 1))";
            $sql.= "AND F.WEBTAG = '$webtag'";

            $result = db_query($sql, $db_get_webtag);

            if (db_num_rows($result) > 0) {

                $webtag_data = db_fetch_array($result);
	        return $webtag_data['WEBTAG'];
            }
        }

        if (is_bool($webtag)) {

            // Check #2: Try and select a default webtag from
            // the databse

 	    $sql = "SELECT F.WEBTAG FROM FORUMS F ";
	    $sql.= "LEFT JOIN USER_FORUM UF ON (UF.FID = F.FID AND UF.UID = '$uid') ";
            $sql.= "WHERE (F.ACCESS_LEVEL = 0 OR (F.ACCESS_LEVEL = 1 AND UF.ALLOWED <=> 1))";
	    $sql.= "AND F.DEFAULT_FORUM = 1";

            $result = db_query($sql, $db_get_webtag);

            if (db_num_rows($result) > 0) {

                $webtag_data = db_fetch_array($result);
                return $webtag_data['WEBTAG'];
            }
        }

        $webtag_search = $webtag;
        return false;
    }

    return $webtag_data['WEBTAG'];
}

function get_forum_settings()
{
    global $default_settings;

    static $get_forum_settings = false;

    $forum_settings = array();

    if (!$get_forum_settings) {

        $db_get_forum_settings = db_connect();

        if (!$table_data = get_table_prefix()) $table_data['FID'] = 0;

        $sql = "SELECT SNAME, SVALUE FROM FORUM_SETTINGS WHERE FID = '{$table_data['FID']}'";
        $result = db_query($sql, $db_get_forum_settings);

        while ($row = db_fetch_array($result)) {
            $forum_settings[$row['SNAME']] = $row['SVALUE'];
            $get_forum_settings = true;
        }
    }

    return array_merge($default_settings, $forum_settings);
}

function save_forum_settings($forum_settings_array)
{
    if (!is_array($forum_settings_array)) return false;

    $db_save_forum_settings = db_connect();

    if (!$table_data = get_table_prefix()) $table_data['FID'] = 0;

    foreach ($forum_settings_array as $sname => $svalue) {

        $sname = addslashes($sname);
        $svalue = addslashes($svalue);

	$sql = "SELECT FID FROM FORUM_SETTINGS WHERE ";
	$sql.= "SNAME = '$sname' AND FID = '{$table_data['FID']}'";

	$result = db_query($sql, $db_save_forum_settings);

	if (db_num_rows($result) > 0) {

            $sql = "UPDATE FORUM_SETTINGS SET SVALUE = '$svalue' ";
	    $sql.= "WHERE SNAME = '$sname' AND FID = '{$table_data['FID']}'";

	}else {

            $sql = "INSERT INTO FORUM_SETTINGS (FID, SNAME, SVALUE) ";
            $sql.= "VALUES ('{$table_data['FID']}', '$sname', '$svalue')";
	}

	$result = db_query($sql, $db_save_forum_settings);
    }
}

function forum_get_setting($setting_name, $value = false, $default = false)
{
    global $forum_settings;

    if (isset($forum_settings[$setting_name])) {
        if ($value) {
            if (strtoupper($forum_settings[$setting_name]) == strtoupper($value)) {
                return true;
            }
        }else {
            return _stripslashes($forum_settings[$setting_name]);
        }
    }

    return $default;
}

function load_start_page()
{
    $webtag = get_webtag($webtag_search);

    if (@file_exists("forums/$webtag/start_main.php")) {

        $content = implode("\n", file("forums/$webtag/start_main.php"));
        return $content;
    }

    return false;
}

function save_start_page($content)
{
    $webtag = get_webtag($webtag_search);

    if (!is_dir("forums")) mkdir("forums", 0755);
    if (!is_dir("forums/$webtag")) mkdir("forums/$webtag", 0755);

    if (@$fp = fopen("./forums/$webtag/start_main.php", "w")) {

        fwrite($fp, $content);
        fclose($fp);

        return true;
    }

    return false;
}

function forum_create($webtag, $forum_name, $access)
{
    // Ensure the variables we've been given are valid

    $webtag = preg_replace("/[^A-Z0-9-_]/", "", strtoupper($webtag));
    $forum_name = addslashes($forum_name);

    if (!is_numeric($access)) $access = 0;

    // Only the queen can create forums!!

    if (perm_has_forumtools_access()) {

        $uid = bh_session_get_value('UID');

        $db_forum_create = db_connect();

        $sql = "SELECT FID FROM FORUMS WHERE WEBTAG = '$webtag'";
        $result = db_query($sql, $db_forum_create);

        if (db_num_rows($result) > 0) {
            return false;
        }

	// Beehive Table Names

	$table_array = array('ADMIN_LOG', 'BANNED_IP', 'DEDUPE',
	                     'FILTER_LIST', 'FOLDER', 'LINKS',
	                     'LINKS_COMMENT', 'LINKS_FOLDERS', 'LINKS_VOTE',
	                     'PM', 'PM_ATTACHMENT_IDS', 'PM_CONTENT',
	                     'POLL', 'POLL_VOTES', 'POST',
	                     'POST_ATTACHMENT_FILES', 'POST_ATTACHMENT_IDS',
	                     'POST_CONTENT', 'PROFILE_ITEM', 'PROFILE_SECTION',
                             'STATS', 'THREAD', 'USER_FOLDER',
                             'USER_PEER', 'USER_POLL_VOTES', 'USER_PREFS',
                             'USER_PROFILE', 'USER_SIG', 'USER_THREAD');

        // Check to see if any of the Beehive tables already exist.
        // If they do then something is wrong and we should error out.

        foreach ($table_array as $table_name) {

            $sql = "SHOW TABLES LIKE '{$webtag}_{$table_name}'";
            $result = db_query($sql, $db_forum_create);

            if (db_num_rows($result) > 0) return false;
        }

        // Create ADMIN_LOG table

        $sql = "CREATE TABLE {$webtag}_ADMIN_LOG (";
        $sql.= "  LOG_ID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  LOG_TIME DATETIME DEFAULT NULL,";
        $sql.= "  ADMIN_UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  FID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  TID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PSID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PIID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  ACTION MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (LOG_ID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create BANNED_IP table

        $sql = "CREATE TABLE {$webtag}_BANNED_IP (";
        $sql.= "  IP CHAR(15) NOT NULL DEFAULT '',";
        $sql.= "  PRIMARY KEY  (IP)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create DEDUPE table

        $sql = "CREATE TABLE {$webtag}_DEDUPE (";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  DDKEY CHAR(32) DEFAULT NULL,";
        $sql.= "  PRIMARY KEY  (UID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create FILTER_LIST table

        $sql = "CREATE TABLE {$webtag}_FILTER_LIST (";
        $sql.= "  ID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  MATCH_TEXT VARCHAR(255) NOT NULL DEFAULT '',";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  REPLACE_TEXT VARCHAR(255) NOT NULL DEFAULT '',";
        $sql.= "  FILTER_OPTION TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (ID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create FOLDER table

        $sql = "CREATE TABLE {$webtag}_FOLDER (";
        $sql.= "  FID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  TITLE VARCHAR(32) DEFAULT NULL,";
        $sql.= "  ACCESS_LEVEL TINYINT(4) DEFAULT '0',";
        $sql.= "  DESCRIPTION VARCHAR(255) DEFAULT NULL,";
        $sql.= "  ALLOWED_TYPES TINYINT(3) DEFAULT NULL,";
        $sql.= "  POSITION MEDIUMINT(3) UNSIGNED DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (FID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create LINKS table

        $sql = "CREATE TABLE {$webtag}_LINKS (";
        $sql.= "  LID SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  FID SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  URI VARCHAR(255) NOT NULL DEFAULT '',";
        $sql.= "  TITLE VARCHAR(64) NOT NULL DEFAULT '',";
        $sql.= "  DESCRIPTION TEXT NOT NULL,";
        $sql.= "  CREATED DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',";
        $sql.= "  VISIBLE CHAR(1) NOT NULL DEFAULT 'N',";
        $sql.= "  CLICKS MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (LID),";
        $sql.= "  KEY FID (FID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create LINKS_COMMENT table

        $sql = "CREATE TABLE {$webtag}_LINKS_COMMENT (";
        $sql.= "  CID SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  LID SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  CREATED DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',";
        $sql.= "  COMMENT TEXT NOT NULL,";
        $sql.= "  PRIMARY KEY  (CID),";
        $sql.= "  KEY LID (LID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create LINKS_FOLDERS table

        $sql = "CREATE TABLE {$webtag}_LINKS_FOLDERS (";
        $sql.= "  FID SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  PARENT_FID SMALLINT(5) UNSIGNED DEFAULT '1',";
        $sql.= "  NAME VARCHAR(32) NOT NULL DEFAULT '',";
        $sql.= "  VISIBLE CHAR(1) NOT NULL DEFAULT '',";
        $sql.= "  PRIMARY KEY  (FID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create LINKS_VOTE table

        $sql = "CREATE TABLE {$webtag}_LINKS_VOTE (";
        $sql.= "  LID SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  RATING SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  TSTAMP DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',";
        $sql.= "  PRIMARY KEY  (LID,UID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create PM table

        $sql = "CREATE TABLE {$webtag}_PM (";
        $sql.= "  MID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  TYPE TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  TO_UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  FROM_UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  SUBJECT VARCHAR(64) NOT NULL DEFAULT '',";
        $sql.= "  CREATED DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',";
        $sql.= "  NOTIFIED TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (MID),";
        $sql.= "  KEY TO_UID (TO_UID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create PM_ATTACHMENT_IDS table

        $sql = "CREATE TABLE {$webtag}_PM_ATTACHMENT_IDS (";
        $sql.= "  MID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  AID CHAR(32) NOT NULL DEFAULT '',";
        $sql.= "  PRIMARY KEY  (MID),";
        $sql.= "  KEY AID (AID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create PM_CONTENT table

        $sql = "CREATE TABLE {$webtag}_PM_CONTENT (";
        $sql.= "  MID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  CONTENT TEXT,";
        $sql.= "  PRIMARY KEY  (MID),";
        $sql.= "  FULLTEXT KEY CONTENT (CONTENT)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create POLL table

        $sql = "CREATE TABLE {$webtag}_POLL (";
        $sql.= "  TID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  CLOSES DATETIME DEFAULT NULL,";
        $sql.= "  CHANGEVOTE TINYINT(1) NOT NULL DEFAULT '1',";
        $sql.= "  POLLTYPE TINYINT(1) NOT NULL DEFAULT '0',";
        $sql.= "  SHOWRESULTS TINYINT(1) NOT NULL DEFAULT '1',";
        $sql.= "  VOTETYPE TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (TID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create POLL_VOTES table

        $sql = "CREATE TABLE {$webtag}_POLL_VOTES (";
        $sql.= "  TID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  OPTION_ID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  OPTION_NAME CHAR(255) NOT NULL DEFAULT '',";
        $sql.= "  GROUP_ID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (TID,OPTION_ID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create POST table

        $sql = "CREATE TABLE {$webtag}_POST (";
        $sql.= "  TID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  REPLY_TO_PID MEDIUMINT(8) UNSIGNED DEFAULT NULL,";
        $sql.= "  FROM_UID MEDIUMINT(8) UNSIGNED DEFAULT NULL,";
        $sql.= "  TO_UID MEDIUMINT(8) UNSIGNED DEFAULT NULL,";
        $sql.= "  VIEWED DATETIME DEFAULT NULL,";
        $sql.= "  CREATED DATETIME DEFAULT NULL,";
        $sql.= "  STATUS TINYINT(4) DEFAULT '0',";
        $sql.= "  EDITED DATETIME DEFAULT NULL,";
        $sql.= "  EDITED_BY MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  IPADDRESS VARCHAR(15) NOT NULL DEFAULT '',";
        $sql.= "  PRIMARY KEY  (TID,PID),";
        $sql.= "  KEY TO_UID (TO_UID),";
        $sql.= "  KEY IPADDRESS (IPADDRESS)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create POST_ATTACHMENT_FILES table

        $sql = "CREATE TABLE {$webtag}_POST_ATTACHMENT_FILES (";
        $sql.= "  ID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  AID VARCHAR(32) NOT NULL DEFAULT '',";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  FILENAME VARCHAR(255) NOT NULL DEFAULT '',";
        $sql.= "  MIMETYPE VARCHAR(255) NOT NULL DEFAULT '',";
        $sql.= "  HASH VARCHAR(32) NOT NULL DEFAULT '',";
        $sql.= "  DOWNLOADS MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (ID),";
        $sql.= "  KEY AID (AID),";
        $sql.= "  KEY HASH (HASH)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create POST_ATTACHMENT_IDS table

        $sql = "CREATE TABLE {$webtag}_POST_ATTACHMENT_IDS (";
        $sql.= "  TID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  AID CHAR(32) NOT NULL DEFAULT '',";
        $sql.= "  PRIMARY KEY  (TID,PID),";
        $sql.= "  KEY AID (AID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create POST_CONTENT table

        $sql = "CREATE TABLE {$webtag}_POST_CONTENT (";
        $sql.= "  TID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  CONTENT TEXT,";
        $sql.= "  PRIMARY KEY  (TID,PID),";
        $sql.= "  FULLTEXT KEY CONTENT (CONTENT)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create PROFILE_ITEM table

        $sql = "CREATE TABLE {$webtag}_PROFILE_ITEM (";
        $sql.= "  PIID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  PSID MEDIUMINT(8) UNSIGNED DEFAULT NULL,";
        $sql.= "  NAME VARCHAR(64) DEFAULT NULL,";
        $sql.= "  TYPE TINYINT(3) UNSIGNED DEFAULT '0',";
        $sql.= "  POSITION MEDIUMINT(3) UNSIGNED DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (PIID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create PROFILE_SECTION table

        $sql = "CREATE TABLE {$webtag}_PROFILE_SECTION (";
        $sql.= "  PSID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  NAME VARCHAR(64) DEFAULT NULL,";
        $sql.= "  POSITION MEDIUMINT(3) UNSIGNED DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (PSID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create STATS table

        $sql = "CREATE TABLE {$webtag}_STATS (";
        $sql.= "  ID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  MOST_USERS_DATE DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',";
        $sql.= "  MOST_USERS_COUNT MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  MOST_POSTS_DATE DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',";
        $sql.= "  MOST_POSTS_COUNT MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (ID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create THREAD table

        $sql = "CREATE TABLE {$webtag}_THREAD (";
        $sql.= "  TID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  FID MEDIUMINT(8) UNSIGNED DEFAULT NULL,";
        $sql.= "  TITLE VARCHAR(64) DEFAULT NULL,";
        $sql.= "  LENGTH MEDIUMINT(8) UNSIGNED DEFAULT NULL,";
        $sql.= "  POLL_FLAG CHAR(1) DEFAULT NULL,";
        $sql.= "  MODIFIED DATETIME DEFAULT NULL,";
        $sql.= "  CLOSED DATETIME DEFAULT NULL,";
        $sql.= "  STICKY CHAR(1) DEFAULT NULL,";
        $sql.= "  STICKY_UNTIL DATETIME DEFAULT NULL,";
        $sql.= "  ADMIN_LOCK DATETIME DEFAULT NULL,";
        $sql.= "  PRIMARY KEY  (TID),";
        $sql.= "  KEY FID (FID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create USER_FOLDER table

        $sql = "CREATE TABLE {$webtag}_USER_FOLDER (";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  FID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  INTEREST TINYINT(4) DEFAULT '0',";
        $sql.= "  ALLOWED TINYINT(4) DEFAULT '0',";
        $sql.= "  PRIMARY KEY  (UID,FID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create USER_PEER table

        $sql = "CREATE TABLE {$webtag}_USER_PEER (";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PEER_UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  RELATIONSHIP TINYINT(4) DEFAULT NULL,";
        $sql.= "  PRIMARY KEY  (UID,PEER_UID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create USER_POLL_VOTES table

        $sql = "CREATE TABLE {$webtag}_USER_POLL_VOTES (";
        $sql.= "  ID MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql.= "  TID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PTUID VARCHAR(32) NOT NULL DEFAULT '',";
        $sql.= "  OPTION_ID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  TSTAMP DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',";
        $sql.= "  PRIMARY KEY  (ID,TID,PTUID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create USER_PREFS table

        $sql = "CREATE TABLE {$webtag}_USER_PREFS (";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  FIRSTNAME VARCHAR(32) DEFAULT NULL,";
        $sql.= "  LASTNAME VARCHAR(32) DEFAULT NULL,";
        $sql.= "  DOB DATE DEFAULT '0000-00-00',";
        $sql.= "  HOMEPAGE_URL VARCHAR(255) DEFAULT NULL,";
        $sql.= "  PIC_URL VARCHAR(255) DEFAULT NULL,";
        $sql.= "  EMAIL_NOTIFY CHAR(1) DEFAULT NULL,";
        $sql.= "  TIMEZONE DECIMAL(2,1) DEFAULT NULL,";
        $sql.= "  DL_SAVING CHAR(1) DEFAULT NULL,";
        $sql.= "  MARK_AS_OF_INT CHAR(1) DEFAULT NULL,";
        $sql.= "  POSTS_PER_PAGE TINYINT(3) UNSIGNED DEFAULT NULL,";
        $sql.= "  FONT_SIZE TINYINT(3) UNSIGNED DEFAULT NULL,";
        $sql.= "  STYLE VARCHAR(255) DEFAULT NULL,";
        $sql.= "  EMOTICONS VARCHAR(255) DEFAULT NULL,";
        $sql.= "  VIEW_SIGS CHAR(1) DEFAULT NULL,";
        $sql.= "  START_PAGE TINYINT(3) UNSIGNED DEFAULT NULL,";
        $sql.= "  LANGUAGE VARCHAR(32) DEFAULT NULL,";
        $sql.= "  PM_NOTIFY CHAR(1) DEFAULT NULL,";
        $sql.= "  PM_NOTIFY_EMAIL CHAR(1) DEFAULT NULL,";
        $sql.= "  DOB_DISPLAY TINYINT(3) UNSIGNED DEFAULT NULL,";
        $sql.= "  ANON_LOGON TINYINT(3) UNSIGNED DEFAULT NULL,";
        $sql.= "  SHOW_STATS TINYINT(3) UNSIGNED DEFAULT NULL,";
        $sql.= "  IMAGES_TO_LINKS CHAR(1) DEFAULT NULL,";
        $sql.= "  USE_WORD_FILTER CHAR(1) DEFAULT NULL,";
        $sql.= "  USE_ADMIN_FILTER CHAR(1) DEFAULT NULL,";
        $sql.= "  ALLOW_EMAIL CHAR(1) DEFAULT NULL,";
        $sql.= "  ALLOW_PM CHAR(1) DEFAULT NULL,";
        $sql.= "  PRIMARY KEY  (UID,UID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create USER_PROFILE table

        $sql = "CREATE TABLE {$webtag}_USER_PROFILE (";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  PIID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  ENTRY VARCHAR(255) DEFAULT NULL,";
        $sql.= "  PRIMARY KEY  (UID,PIID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create USER_SIG table

        $sql = "CREATE TABLE {$webtag}_USER_SIG (";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  CONTENT TEXT,";
        $sql.= "  HTML CHAR(1) DEFAULT NULL,";
        $sql.= "  PRIMARY KEY  (UID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create USER_THREAD table

        $sql = "CREATE TABLE {$webtag}_USER_THREAD (";
        $sql.= "  UID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  TID MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',";
        $sql.= "  LAST_READ MEDIUMINT(8) UNSIGNED DEFAULT NULL,";
        $sql.= "  LAST_READ_AT DATETIME DEFAULT NULL,";
        $sql.= "  INTEREST TINYINT(4) DEFAULT NULL,";
        $sql.= "  PRIMARY KEY  (UID,TID)";
        $sql.= ") TYPE=MYISAM;";

        $result = db_query($sql, $db_forum_create);

        // Create General Folder

        $sql = "INSERT INTO {$webtag}_FOLDER (TITLE, ACCESS_LEVEL, DESCRIPTION, ALLOWED_TYPES, POSITION) ";
        $sql.= "VALUES ('General', 0, NULL, NULL, 0)";

        $result = db_query($sql, $db_forum_create);

        // Create Top Level Links Folder

        $sql = "INSERT INTO {$webtag}_LINKS_FOLDERS (PARENT_FID, NAME, VISIBLE) VALUES (NULL, 'Top Level', 'Y')";
        $result = db_query($sql, $db_forum_create);

        // Save Webtag

        $sql = "INSERT INTO FORUMS (WEBTAG) VALUES ('$webtag')";
        $result = db_query($sql, $db_forum_create);

        // Get the new FID so we can save the settings

        $new_fid = db_insert_id($db_forum_create);

        // Store Forum Name

        $sql = "INSERT INTO FORUM_SETTINGS (FID, SNAME, SVALUE) VALUES ('$new_fid', 'forum_name', '$forum_name')";
        $result = db_query($sql, $db_forum_create);

        return $new_fid;
    }

    return false;
}

function forum_delete($fid)
{
    // Only the queen can create forums!!

    if (perm_has_forumtools_access()) {

        $db_forum_delete = db_connect();

        if (!is_numeric($fid)) return false;

        $sql = "SELECT WEBTAG FROM FORUMS WHERE FID = '$fid'";
        $result = db_query($sql, $db_forum_delete);

        if (db_num_rows($result) > 0) {

            $forum_data = db_fetch_array($result);

	    $sql = "DELETE FROM FORUM_SETTINGS WHERE FID = '$fid'";
            $result = db_query($sql, $db_forum_delete);

  	    $sql = "DELETE FROM FORUMS WHERE FID = '$fid'";
	    $result = db_query($sql, $db_forum_delete);

	    $table_array = array('ADMIN_LOG', 'BANNED_IP', 'DEDUPE',
	                         'FILTER_LIST', 'FOLDER', 'LINKS',
	                         'LINKS_COMMENT', 'LINKS_FOLDERS', 'LINKS_VOTE',
	                         'PM', 'PM_ATTACHMENT_IDS', 'PM_CONTENT',
	                         'POLL', 'POLL_VOTES', 'POST',
	                         'POST_ATTACHMENT_FILES', 'POST_ATTACHMENT_IDS',
	                         'POST_CONTENT', 'PROFILE_ITEM', 'PROFILE_SECTION',
                                 'STATS', 'THREAD', 'USER_FOLDER',
                                 'USER_PEER', 'USER_POLL_VOTES', 'USER_PREFS',
                                 'USER_PROFILE', 'USER_SIG', 'USER_THREAD');

            foreach ($table_array as $table_name) {

                $sql = "DROP TABLE IF EXISTS {$forum_data['WEBTAG']}_{$table_name}";
	        $result = db_query($sql, $db_forum_delete);
	    }
        }
    }

    return false;
}

function forum_update_access($fid, $access)
{
    if (!is_numeric($fid)) return false;
    if (!is_numeric($access)) return false;

    // Only the queen can change a forums status!!

    if (perm_has_forumtools_access()) {

        $db_forum_update_access = db_connect();

        $sql = "SELECT COUNT(*) FROM FORUMS WHERE FID = '$fid'";
        $result = db_query($sql, $db_forum_update_access);

	if (db_num_rows($result) > 0) {

	    $sql = "UPDATE FORUMS SET ACCESS_LEVEL = '$access' WHERE FID = '$fid'";
	    $result = db_query($sql, $db_forum_update_access);

	}

	return $result;
    }

    return false;
}

function forum_get($fid)
{
    if (!is_numeric($fid)) return false;

    if (perm_has_forumtools_access()) {

        $db_forum_get = db_connect();

	$sql = "SELECT * FROM FORUMS WHERE FID = '$fid'";
	$result = db_query($sql, $db_forum_get);

	if (db_num_rows($result) > 0) {

	    $forum_get_array = db_fetch_array($result);
	    $forum_get_array['FORUM_SETTINGS'] = array();

	    $sql = "SELECT SNAME, SVALUE FROM FORUM_SETTINGS WHERE FID = '$fid'";
	    $result = db_query($sql, $db_forum_get);

	    while ($row = db_fetch_array($result)) {
	        $forum_get_array['FORUM_SETTINGS'][$row['SNAME']] = $row['SVALUE'];
	    }

	    return $forum_get_array;
	}
    }

    return false;
}

function forum_get_permissions($fid)
{
    if (!is_numeric($fid)) return false;

    if (perm_has_forumtools_access()) {

        $db_forum_get_permissions = db_connect();

        $sql = "SELECT USER.UID, USER.LOGON, USER.NICKNAME FROM USER USER ";
        $sql.= "LEFT JOIN USER_FORUM USER_FORUM ON (USER_FORUM.UID = USER.UID) ";
        $sql.= "WHERE USER_FORUM.FID = '$fid' AND USER_FORUM.ALLOWED = 1";

	$result = db_query($sql, $db_forum_get_permissions);

        if (db_num_rows($result)) {

            $forum_get_permissions_array = array();

            while($row = db_fetch_array($result)) {
	        $forum_get_permissions_array[] = $row;
            }

            return $forum_get_permissions_array;
        }
    }

    return false;
}

function forum_update_default($fid)
{
    if (!is_numeric($fid)) return false;

    if (perm_has_forumtools_access()) {

        $db_forum_get_permissions = db_connect();

        $sql = "UPDATE FORUMS SET DEFAULT_FORUM = 0 WHERE DEFAULT_FORUM = 1";
	$result = db_query($sql, $db_forum_get_permissions);

	if ($fid > 0) {

            $sql = "UPDATE FORUMS SET DEFAULT_FORUM = 1 WHERE FID = '$fid'";
	    $result = db_query($sql, $db_forum_get_permissions);
	}

        return $result;
    }

    return false;
}

function forum_search($search_string)
{
    $search_string = addslashes(trim($search_string));
    $search_string = preg_replace("/[^\w]/", "", $search_string);

    $keywords_array = explode(" ", $search_string);

    $db_forum_search = db_connect();
    $forum_search_array = array();

    $forum_webtag_sql = "FORUMS.WEBTAG LIKE '%";
    $forum_webtag_sql.= implode("%' OR FORUMS.WEBTAG LIKE '%", $keywords_array);
    $forum_webtag_sql.= "%'";

    $forum_settings_sql = "FORUM_SETTINGS.SVALUE LIKE '%";
    $forum_settings_sql.= implode("%' OR FORUM_SETTINGS.SVALUE LIKE '%", $keywords_array);
    $forum_settings_sql.= "%'";

    $sql = "SELECT FORUMS.FID, FORUMS.WEBTAG FROM FORUM_SETTINGS ";
    $sql.= "LEFT JOIN FORUMS ON (FORUMS.FID = FORUM_SETTINGS.FID) ";
    $sql.= "WHERE $forum_webtag_sql OR (FORUM_SETTINGS.SNAME = 'forum_keywords' ";
    $sql.= "AND ($forum_settings_sql)) OR (FORUM_SETTINGS.SNAME = 'forum_desc' ";
    $sql.= "AND ($forum_settings_sql)) OR (FORUM_SETTINGS.SNAME = 'forum_name' ";
    $sql.= "AND ($forum_settings_sql))";

    $result = db_query($sql, $db_forum_search);

    if (db_num_rows($result) > 0) {

        while ($forum_data = db_fetch_array($result)) {

            $sql = "SELECT SVALUE AS FORUM_NAME FROM FORUM_SETTINGS ";
            $sql.= "WHERE SNAME = 'forum_name' AND FID = '{$forum_data['FID']}'";

	    $result_forum_name = db_query($sql, $db_forum_search);

	    if (db_num_rows($result_forum_name)) {

	        $row = db_fetch_array($result_forum_name);
	        $forum_data['FORUM_NAME'] = $row['FORUM_NAME'];

	    }else {

	        $forum_data['FORUM_NAME'] = $lang['unnamedforum'];
	    }

            $sql = "SELECT COUNT(*) AS POST_COUNT FROM {$forum_data['WEBTAG']}_POST POST ";
            $result_post_count = db_query($sql, $db_forum_search);

            if (db_num_rows($result_post_count)) {

                $row = db_fetch_array($result_post_count);
                $forum_data['MESSAGES'] = $row['POST_COUNT'];

            }else {

                $forum_data['MESSAGES'] = 0;
            }

            $sql = "SELECT SVALUE FROM FORUM_SETTINGS WHERE ";
            $sql.= "FORUM_SETTINGS.FID = {$forum_data['FID']} AND ";
            $sql.= "FORUM_SETTINGS.SNAME = 'forum_desc'";

            $result_description = db_query($sql, $db_forum_search);

            if (db_num_rows($result_description)) {

                $row = db_fetch_array($result_description);
                $forum_data['DESCRIPTION'] = $row['SVALUE'];

            }else{

                $forum_data['DESCRIPTION'] = "";
            }

            $forum_search_array[$forum_data['FID']] = $forum_data;
        }

        return $forum_search_array;
    }

    return false;
}

?>