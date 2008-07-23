<?php

/*======================================================================
Copyright Project Beehive Forum 2002

This file is part of Beehive Forum.

Beehive Forum is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
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

/* $Id: user.inc.php,v 1.353 2008-07-23 19:57:12 decoyduck Exp $ */

// We shouldn't be accessing this file directly.

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Request-URI: ../index.php");
    header("Content-Location: ../index.php");
    header("Location: ../index.php");
    exit;
}

include_once(BH_INCLUDE_PATH. "constants.inc.php");
include_once(BH_INCLUDE_PATH. "forum.inc.php");
include_once(BH_INCLUDE_PATH. "ip.inc.php");
include_once(BH_INCLUDE_PATH. "lang.inc.php");
include_once(BH_INCLUDE_PATH. "session.inc.php");

function user_count()
{
   if (!$db_user_count = db_connect()) return false;

   if (!$table_data = get_table_prefix()) return false;

   $sql = "SELECT COUNT(UID) AS COUNT FROM USER";

   if (!$result = db_query($sql, $db_user_count)) return false;

   list($user_count) = db_fetch_array($result, DB_RESULT_NUM);

   return $user_count;
}

function user_exists($logon, $check_uid = false)
{
    if (!$db_user_exists = db_connect()) return false;

    if (!$table_data = get_table_prefix()) return false;

    $logon = db_escape_string($logon);

    if (is_numeric($check_uid) && $check_uid !== false) {

        $sql = "SELECT COUNT(UID) AS USER_COUNT FROM USER ";
        $sql.= "WHERE LOGON = '$logon' AND UID <> '$check_uid'";

    }else {

        $sql = "SELECT COUNT(UID) AS USER_COUNT FROM USER ";
        $sql.= "WHERE LOGON = '$logon'";
    }

    if (!$result = db_query($sql, $db_user_exists)) return false;

    list($user_count) = db_fetch_array($result, DB_RESULT_NUM);

    return ($user_count > 0);
}

function user_create($logon, $password, $nickname, $email)
{
    if (!$db_user_create = db_connect()) return false;

    $logon     = db_escape_string($logon);
    $nickname  = db_escape_string($nickname);
    $email     = db_escape_string($email);
    $md5pass   = md5($password);

    if ($http_referer = bh_session_get_value('REFERER')) {
        $http_referer = db_escape_string($http_referer);
    }else {
        $http_referer = "";
    }

    if (!$ipaddress = get_ip_address()) return false;

    $sql = "INSERT INTO USER (LOGON, PASSWD, NICKNAME, EMAIL, ";
    $sql.= "REGISTERED, REFERER, IPADDRESS) VALUES ('$logon', ";
    $sql.= "'$md5pass', '$nickname', '$email', NOW(), ";
    $sql.= "'$http_referer', '$ipaddress')";

    if ($result = db_query($sql, $db_user_create)) {

        $new_uid = db_insert_id($db_user_create);
        return $new_uid;
    }

    return false;
}

function user_update($uid, $logon, $nickname, $email)
{
    if (!$db_user_update = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    // Encode HTML tags and db_escape_string for protection.

    $logon = db_escape_string($logon);
    $nickname = db_escape_string($nickname);
    $email = db_escape_string($email);

    // Check to see if we need to save the current
    // details to the USER_HISTORY table.

    $sql = "SELECT LOGON, NICKNAME, EMAIL FROM USER_HISTORY ";
    $sql.= "WHERE UID = '$uid' ORDER BY MODIFIED DESC ";
    $sql.= "LIMIT 0, 1";

    if (!$result_check = db_query($sql, $db_user_update)) return false;

    // If there is some existing data we need to retrieve the
    // data and compare it to the new details.

    if (db_num_rows($result_check) > 0) {

        // Get the old data from the database and escape it so the strcmp works.

        $user_history_array = array_map('db_escape_string', db_fetch_array($result_check));

        // Check the data against that passed to the function.

        if ((strcmp($user_history_array['LOGON'], $logon) <> 0) || (strcmp($user_history_array['NICKNAME'], $nickname) <> 0) || (strcmp($user_history_array['EMAIL'], $email) <> 0)) {

            // If there are any differences we need to save the changes.
            // We save everything so that future changes don't cause
            // additional matches (NULL != $logon, etc.)

            $sql = "INSERT INTO USER_HISTORY (UID, LOGON, NICKNAME, EMAIL, MODIFIED) ";
            $sql.= "VALUES ('$uid', '$logon', '$nickname', '$email', NOW())";

            if (!$result_update = db_query($sql, $db_user_update)) return false;
        }

    }else {

        // No previous data so we just save what we have.

        $sql = "INSERT INTO USER_HISTORY (UID, LOGON, NICKNAME, EMAIL, MODIFIED) ";
        $sql.= "VALUES ('$uid', '$logon', '$nickname', '$email', NOW())";

        if (!$result_update = db_query($sql, $db_user_update)) return false;
    }

    // Update the user details

    $sql = "UPDATE LOW_PRIORITY USER SET LOGON = '$logon', NICKNAME = '$nickname', ";
    $sql.= "EMAIL = '$email' WHERE UID = '$uid'";

    if (!$result_update = db_query($sql, $db_user_update)) return false;

    return true;
}

function user_update_nickname($uid, $nickname)
{
    if (!$db_user_update = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $nickname = db_escape_string($nickname);

    $sql = "UPDATE LOW_PRIORITY USER SET NICKNAME = '$nickname' ";
    $sql.= "WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_update)) return false;

    return true;
}

function user_change_logon($uid, $logon)
{
    if (!$db_user_change_logon = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $logon = db_escape_string($logon);

    $sql = "UPDATE LOW_PRIORITY USER SET LOGON = '$logon' ";
    $sql.= "WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_change_logon)) return false;

    return true;
}

function user_update_post_count($uid, $post_count)
{
    if (!$db_user_update_post_count = db_connect()) return false;

    if (!is_numeric($uid)) return false;
    if (!is_numeric($post_count)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "UPDATE LOW_PRIORITY {$table_data['PREFIX']}USER_TRACK ";
    $sql.= "SET POST_COUNT = '$post_count' ";
    $sql.= "WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_update_post_count)) return false;

    return true;
}

function user_reset_post_count($uid)
{
    if (!$db_user_reset_post_count = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "UPDATE LOW_PRIORITY {$table_data['PREFIX']}USER_TRACK ";
    $sql.= "SET POST_COUNT = NULL WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_reset_post_count)) return false;

    return true;
}

function user_change_password($user_uid, $password, $old_passhash = false)
{
    if (!$db_user_change_password = db_connect()) return false;

    if (!is_numeric($user_uid)) return false;

    $uid = bh_session_get_value('UID');

    $passhash = db_escape_string(md5($password));

    if (bh_session_check_perm(USER_PERM_FORUM_TOOLS, 0)) {

        $sql = "UPDATE LOW_PRIORITY USER SET PASSWD = '$passhash' ";
        $sql.= "WHERE UID = '$user_uid'";

        if (!$result = db_query($sql, $db_user_change_password)) return false;

        return true;

    }elseif (is_md5($old_passhash)) {

        $old_passhash = db_escape_string($old_passhash);

        $sql = "UPDATE LOW_PRIORITY USER SET PASSWD = '$passhash' ";
        $sql.= "WHERE UID = '$user_uid' AND PASSWD = '$old_passhash'";

        if (!$result = db_query($sql, $db_user_change_password)) return false;

        return (db_affected_rows($db_user_change_password) > 0);
    }

    return false;
}

function user_update_forums($uid, $forums_array)
{
    if (!$db_user_update_forums = db_connect()) return false;

    if (!is_numeric($uid)) return false;
    if (!is_array($forums_array)) return false;

    foreach ($forums_array as $forum_fid => $allowed) {

        if (is_numeric($forum_fid) && is_numeric($allowed)) {

            $sql = "SELECT UID FROM USER_FORUM ";
            $sql.= "WHERE UID = '$uid' AND FID = '$forum_fid'";

            if (!$result = db_query($sql, $db_user_update_forums)) return false;

            if (db_num_rows($result) > 0) {

                $sql = "UPDATE LOW_PRIORITY USER_FORUM SET ALLOWED = '$allowed' ";
                $sql.= "WHERE UID = '$uid' AND FID = '$forum_fid'";

                if (!$result = db_query($sql, $db_user_update_forums)) return false;

            }else {

                $sql = "INSERT INTO USER_FORUM (UID, FID, ALLOWED) ";
                $sql.= "VALUES ('$uid', '$forum_fid', '$allowed')";

                if (!$result = db_query($sql, $db_user_update_forums)) return false;
            }
        }
    }

    return true;
}

function user_logon($logon, $passhash)
{
    if (!$db_user_logon = db_connect()) return false;

    if (!is_md5($passhash)) return false;

    $logon = db_escape_string(strtoupper($logon));
    $passhash = db_escape_string($passhash);

    if (!$ipaddress = get_ip_address()) return false;

    if (!$table_data = get_table_prefix()) $table_data['FID'] = 0;

    $sql = "SELECT UID, IPADDRESS FROM USER WHERE LOGON = '$logon' AND PASSWD = '$passhash' ";

    if (!$result = db_query($sql, $db_user_logon)) return false;

    if (db_num_rows($result) > 0) {

        $user_data = db_fetch_array($result);

        if (isset($user_data['UID']) && is_numeric($user_data['UID'])) {

            if (strcmp($user_data['IPADDRESS'], $ipaddress) <> 0) {

                $sql = "UPDATE LOW_PRIORITY USER SET IPADDRESS = '$ipaddress' WHERE UID = '{$user_data['UID']}'";
                if (!$result = db_query($sql, $db_user_logon)) return false;
            }

            return $user_data['UID'];
        }
    }

    return false;
}

function user_get($uid)
{
    if (!$db_user_get = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    $sess_uid = bh_session_get_value('UID');

    if ((!$table_data = get_table_prefix()) || ($uid == $sess_uid)) {

        $sql = "SELECT UID, LOGON, PASSWD, NICKNAME, USER.EMAIL, ";
        $sql.= "IPADDRESS, REFERER FROM USER WHERE UID = '$uid'";

    }else {

        $sql = "SELECT USER.UID, USER.LOGON, USER.PASSWD, USER.NICKNAME, ";
        $sql.= "USER.EMAIL, USER.IPADDRESS, USER.REFERER, USER_PEER.PEER_NICKNAME FROM USER ";
        $sql.= "LEFT JOIN {$table_data['PREFIX']}USER_PEER USER_PEER ";
        $sql.= "ON (USER_PEER.PEER_UID = USER.UID AND USER_PEER.UID = '$sess_uid') ";
        $sql.= "WHERE USER.UID = '$uid'";
    }

    if (!$result = db_query($sql, $db_user_get)) return false;

    if (db_num_rows($result) > 0) {

        $user_get = db_fetch_array($result);

        if (isset($user_get['PEER_NICKNAME'])) {

            if (!is_null($user_get['PEER_NICKNAME']) && strlen($user_get['PEER_NICKNAME']) > 0) {

                $user_get['NICKNAME'] = $user_get['PEER_NICKNAME'];
            }
        }

        return $user_get;
    }

    return false;
}

function user_get_by_password($uid, $passwd_hash)
{
    if (!$db_user_get = db_connect()) return false;

    if (!is_numeric($uid)) return false;
    if (!is_md5($passwd_hash)) return false;

    $sql = "SELECT UID, LOGON, PASSWD, NICKNAME, EMAIL, REGISTERED, ";
    $sql.= "IPADDRESS, REFERER, APPROVED FROM USER WHERE UID = '$uid' ";
    $sql.= "AND PASSWD = '$passwd_hash'";

    if (!$result = db_query($sql, $db_user_get)) return false;

    if (db_num_rows($result) > 0) {

        $user_get = db_fetch_array($result);
        return $user_get;
    }

    return false;
}

function user_get_logon($uid)
{
    if (!$db_user_get_logon = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    $table_data = get_table_prefix();

    $sql = "SELECT LOGON FROM USER WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_get_logon)) return false;

    if (db_num_rows($result) > 0) {

        list($logon) = db_fetch_array($result, DB_RESULT_NUM);
        return $logon;
    }

    return false;
}

function user_get_nickname($uid)
{
    if (!$db_user_get_nickname = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT NICKNAME FROM USER WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_get_nickname)) return false;

    if (db_num_rows($result) > 0) {

        list($nickname) = db_fetch_array($result, DB_RESULT_NUM);
        return $nickname;
    }

    return false;
}

function user_get_email($uid)
{
    if (!$db_user_get_email = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT EMAIL FROM USER WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_get_email)) return false;

    if (db_num_rows($result) > 0) {

        list($email) = db_fetch_array($result, DB_RESULT_NUM);
        return $email;
    }

    return false;
}

function user_get_referer($uid)
{
    if (!$db_user_get_referer = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT REFERER FROM USER WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_get_referer)) return false;

    if (db_num_rows($result) > 0) {

        list($referer) = db_fetch_array($result, DB_RESULT_NUM);
        return $referer;
    }

    return false;
}

function user_get_passwd($uid)
{
    if (!$db_user_get_passwd = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT PASSWD FROM USER WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_get_passwd)) return false;

    if (db_num_rows($result) > 0) {

        list($passwd) = db_fetch_array($result, DB_RESULT_NUM);
        return $passwd;
    }

    return false;
}

function user_get_uid($logon)
{
    if (!$db_user_get_uid = db_connect()) return false;

    $logon = db_escape_string($logon);

    $sql = "SELECT UID, LOGON, PASSWD, NICKNAME, EMAIL, ";
    $sql.= "REGISTERED, IPADDRESS, REFERER, APPROVED ";
    $sql.= "FROM USER WHERE LOGON LIKE '$logon'";

    if (!$result = db_query($sql, $db_user_get_uid)) return false;

    if (db_num_rows($result) > 0) {

        $user_array = db_fetch_array($result);
        return $user_array;
    }

    return false;
}

function user_get_sig($uid, &$content, &$html)
{
    if (!$db_user_get_sig = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT CONTENT, HTML FROM {$table_data['PREFIX']}USER_SIG WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_get_sig)) return false;

    if (db_num_rows($result) > 0) {

        list($content, $html) = db_fetch_array($result, DB_RESULT_NUM);
        return true;
    }

    return false;
}

function user_get_last_ip_address($uid)
{
    if (!$db_user_get_last_ip_address = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT IPADDRESS FROM USER WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_get_last_ip_address)) return false;

    if (db_num_rows($result) > 0) {

        list($ipaddress) = db_fetch_array($result, DB_RESULT_NUM);
        return $ipaddress;
    }

    return false;
}

function user_get_prefs($uid)
{
    // See user_update_prefs() below for an explanation of the prefs system.

    if (!$db_user_get_prefs = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    $forum_prefs = array();

    // 2. The user's global prefs, in USER_PREFS:

    $sql = "SELECT USER_PREFS.FIRSTNAME, USER_PREFS.LASTNAME, USER_PREFS.DOB, ";
    $sql.= "USER_PREFS.HOMEPAGE_URL, USER_PREFS.PIC_URL, USER_PREFS.PIC_AID, ";
    $sql.= "USER_PREFS.AVATAR_URL, USER_PREFS.AVATAR_AID, USER_PREFS.EMAIL_NOTIFY, ";
    $sql.= "USER_PREFS.TIMEZONE, TIMEZONES.GMT_OFFSET, TIMEZONES.DST_OFFSET, ";
    $sql.= "USER_PREFS.DL_SAVING, USER_PREFS.MARK_AS_OF_INT, USER_PREFS.POSTS_PER_PAGE, ";
    $sql.= "USER_PREFS.FONT_SIZE, USER_PREFS.VIEW_SIGS, USER_PREFS.START_PAGE, ";
    $sql.= "USER_PREFS.LANGUAGE, USER_PREFS.PM_NOTIFY, USER_PREFS.PM_NOTIFY_EMAIL, ";
    $sql.= "USER_PREFS.PM_SAVE_SENT_ITEM, USER_PREFS.PM_INCLUDE_REPLY, ";
    $sql.= "USER_PREFS.PM_AUTO_PRUNE, USER_PREFS.PM_EXPORT_TYPE, ";
    $sql.= "USER_PREFS.PM_EXPORT_FILE, USER_PREFS.PM_EXPORT_ATTACHMENTS, ";
    $sql.= "USER_PREFS.PM_EXPORT_STYLE, USER_PREFS.PM_EXPORT_WORDFILTER, ";
    $sql.= "USER_PREFS.DOB_DISPLAY, USER_PREFS.ANON_LOGON, ";
    $sql.= "USER_PREFS.SHOW_STATS, USER_PREFS.IMAGES_TO_LINKS, ";
    $sql.= "USER_PREFS.USE_WORD_FILTER, USER_PREFS.USE_ADMIN_FILTER, ";
    $sql.= "USER_PREFS.ALLOW_EMAIL, USER_PREFS.ALLOW_PM, USER_PREFS.POST_PAGE, ";
    $sql.= "USER_PREFS.SHOW_THUMBS, USER_PREFS.USE_MOVER_SPOILER, ";
    $sql.= "USER_PREFS.USE_LIGHT_MODE_SPOILER, USER_PREFS.ENABLE_WIKI_WORDS, ";
    $sql.= "USER_PREFS.REPLY_QUICK, USER_PREFS.USE_OVERFLOW_RESIZE FROM USER_PREFS ";
    $sql.= "LEFT JOIN TIMEZONES ON (TIMEZONES.TZID = USER_PREFS.TIMEZONE) ";
    $sql.= "WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_get_prefs)) return false;

    $global_prefs = (db_num_rows($result) > 0) ? db_fetch_array($result, DB_RESULT_ASSOC) : array();

    // 3. The user's per-forum prefs, in {webtag}_USER_PREFS (not all prefs are set here e.g. name):

    if ($table_data = get_table_prefix()) {

        $sql = "SELECT HOMEPAGE_URL, PIC_URL, PIC_AID, AVATAR_URL, AVATAR_AID, EMAIL_NOTIFY, ";
        $sql.= "MARK_AS_OF_INT, POSTS_PER_PAGE, FONT_SIZE, STYLE, VIEW_SIGS, START_PAGE, ";
        $sql.= "LANGUAGE, DOB_DISPLAY, ANON_LOGON, SHOW_STATS, IMAGES_TO_LINKS, USE_WORD_FILTER, ";
        $sql.= "USE_ADMIN_FILTER, EMOTICONS, ALLOW_EMAIL, ALLOW_PM, SHOW_THUMBS, USE_MOVER_SPOILER, ";
        $sql.= "USE_LIGHT_MODE_SPOILER, ENABLE_WIKI_WORDS, USE_OVERFLOW_RESIZE, REPLY_QUICK ";
        $sql.= "FROM {$table_data['PREFIX']}USER_PREFS WHERE UID = '$uid'";

        if (!$result = db_query($sql, $db_user_get_prefs)) return false;

        $forum_prefs = (db_num_rows($result) > 0) ? db_fetch_array($result, DB_RESULT_ASSOC) : array();
    }

    // Prune empty values from the arrays (to stop them overwriting valid values)
    // using strlen() as a callback function.

    $global_prefs = array_filter($global_prefs, "strlen");
    $forum_prefs = array_filter($forum_prefs, "strlen");

    // Add keys to indicate whether the preference is set globally or not

    foreach ($forum_prefs as $key => $value) {
        $forum_prefs[$key.'_GLOBAL'] = false;
    }

    foreach ($global_prefs as $key => $value) {
        $global_prefs[$key.'_GLOBAL'] = true;
    }

    // Merge them all together, with forum prefs overriding global prefs

    $prefs_array = array_merge($global_prefs, $forum_prefs);

    return $prefs_array;
}

function user_update_prefs($uid, $prefs_array, $prefs_global_setting_array = false)
{
    /* Attempt at explaining the new prefs system:

    $prefs_array contains the preference settings to be altered. Its keys are the names of the preference
    settings (same as the names of the corresponding database fields). $prefs_global_setting_array
    also has keys which are the names of the preference settings to be changed but contain Boolean values
    that when true set the appropriate preference globally and when false only set it for the current forum.
    The default behaviour is to set a preference globally if it is not specified otherwise.

    e.g.  $prefs_array           $prefs_global_setting_array    Result
          'VIEW_SIGS' => 'N'     'VIEW_SIGS' => false           Sets VIEW_SIGS to 'N' for current forum only
          'FONT_SIZE' => 11      'FONT_SIZE' not set            Sets FONT_SIZE to 11 globally

    FIRSTNAME, LASTNAME, DOB, TIMEZONE, DL_SAVING and POST_PAGE can only be set globally - there's no sense
    in changing them on a per-forum basis.

    */

    if (!is_numeric($uid)) return false;
    if (!is_array($prefs_array)) return false;
    if (!is_array($prefs_global_setting_array)) $prefs_global_setting_array = array();

    // names of preferences that can be set globally

    $global_pref_names = array('FIRSTNAME', 'LASTNAME', 'DOB', 'HOMEPAGE_URL',
                               'PIC_URL', 'PIC_AID', 'AVATAR_URL', 'AVATAR_AID',
                               'EMAIL_NOTIFY', 'TIMEZONE', 'DL_SAVING',
                               'MARK_AS_OF_INT', 'POSTS_PER_PAGE', 'FONT_SIZE',
                               'VIEW_SIGS', 'START_PAGE', 'LANGUAGE', 'PM_NOTIFY',
                               'PM_NOTIFY_EMAIL', 'PM_SAVE_SENT_ITEM', 'PM_INCLUDE_REPLY',
                               'PM_AUTO_PRUNE', 'PM_EXPORT_FILE', 'PM_EXPORT_TYPE',
                               'PM_EXPORT_ATTACHMENTS', 'PM_EXPORT_STYLE',
                               'PM_EXPORT_WORDFILTER', 'DOB_DISPLAY', 'ANON_LOGON',
                               'SHOW_STATS', 'IMAGES_TO_LINKS', 'USE_WORD_FILTER',
                               'USE_ADMIN_FILTER',  'ALLOW_EMAIL', 'ALLOW_PM',
                               'POST_PAGE', 'SHOW_THUMBS', 'ENABLE_WIKI_WORDS',
                               'USE_MOVER_SPOILER', 'USE_LIGHT_MODE_SPOILER',
                               'USE_OVERFLOW_RESIZE', 'REPLY_QUICK');

    // names of preferences that can be set on a per-forum basis

    $forum_pref_names =  array('HOMEPAGE_URL', 'PIC_URL', 'PIC_AID', 'AVATAR_URL',
                               'AVATAR_AID', 'EMAIL_NOTIFY', 'MARK_AS_OF_INT',
                               'POSTS_PER_PAGE', 'FONT_SIZE', 'STYLE', 'VIEW_SIGS',
                               'START_PAGE', 'LANGUAGE', 'DOB_DISPLAY', 'ANON_LOGON',
                               'SHOW_STATS', 'IMAGES_TO_LINKS', 'USE_WORD_FILTER',
                               'USE_ADMIN_FILTER', 'EMOTICONS', 'ALLOW_EMAIL',
                               'ALLOW_PM', 'SHOW_THUMBS', 'ENABLE_WIKI_WORDS',
                               'USE_MOVER_SPOILER', 'USE_LIGHT_MODE_SPOILER',
                               'USE_OVERFLOW_RESIZE', 'REPLY_QUICK');

    foreach ($prefs_array as $pref_name => $pref_setting) {

        if (user_check_pref($pref_name, $pref_setting)) {

            if (!isset($prefs_global_setting_array[$pref_name]) || $prefs_global_setting_array[$pref_name] == true) {

                // preference is to be set globally.
                // check this pref name is allowed to be set globally

                if (in_array($pref_name, $global_pref_names)) {

                    if (!isset($global_prefs) || !is_array($global_prefs)) $global_prefs = array();
                    $global_prefs[$pref_name] = $pref_setting;
                }

            }else {

                // preference is to be set for current forum only
                // check this pref name is allowed to be set on a per-forum basis

                if (in_array($pref_name, $forum_pref_names)) {

                    if (!isset($forum_prefs) || !is_array($forum_prefs)) $forum_prefs = array();
                    $forum_prefs[$pref_name] = $pref_setting;
                }
            }
        }
    }

    if (!$db_user_update_prefs = db_connect()) return false;

    $result_global = true;
    $result_forum  = true;

    if (isset($global_prefs) && is_array($global_prefs)) {

        // Is there an entry in USER_PREFS already for this user?

        $sql = "SELECT UID FROM USER_PREFS WHERE UID = '$uid'";

        if (!$result_global = db_query($sql, $db_user_update_prefs)) return false;

        if (db_num_rows($result_global) > 0) {

            // previous entry which we will UPDATE

            $values  = array();
            $columns = array();

            $values_array = array();

            foreach($global_prefs as $pref_name => $pref_setting) {

                 $pref_setting = db_escape_string($pref_setting);
                 $values_array[] = "$pref_name = '$pref_setting'";
            }

            if (sizeof($values_array) > 0) {

                $values = implode(", ", $values_array);

                $sql = "UPDATE LOW_PRIORITY USER_PREFS SET $values  WHERE UID = '$uid'";

                if (!$result_global = db_query($sql, $db_user_update_prefs)) return false;
            }

        }else {

            // no previous entry, construct an INSERT query

            $values  = array();
            $columns = array();

            $values_array = array();

            foreach($global_prefs as $pref_name => $pref_setting) {

                 $pref_setting = db_escape_string($pref_setting);
                 $values_array[$pref_name] = "'$pref_setting'";
            }

            if (sizeof($values_array) > 0) {

                $columns = implode(", ", array_keys($values_array));
                $values  = implode(", ", array_values($values_array));

                $sql = "INSERT INTO USER_PREFS (UID, $columns) VALUES ('$uid', $values) ";

                if (!$result_global = db_query($sql, $db_user_update_prefs)) return false;
            }
        }

        // If a pref is set globally, we need to remove it from all the [webtag]_USER_PREFS tables too.
        // MySQL doesn't mind if a record for this user doesn't exist in a particular table.

        $values  = array();
        $columns = array();

        $values_array = array();

        foreach($global_prefs as $pref_name => $pref_setting) {
            if (in_array($pref_name, $forum_pref_names)) {
                $values_array[] = "$pref_name = ''";
            }
        }

        if (sizeof($values_array) > 0) {

            $values  = implode(", ", $values_array);

            if (!$forum_prefix_array = forum_get_all_prefixes()) return false;

            foreach($forum_prefix_array as $forum_prefix) {

                $sql = "UPDATE LOW_PRIORITY {$forum_prefix}USER_PREFS SET $values WHERE UID = '$uid'";

                if (!$result = db_query($sql, $db_user_update_prefs)) return false;
            }
        }
    }

    if (isset($forum_prefs) && is_array($forum_prefs) && $table_data = get_table_prefix()) {

        $sql = "SELECT UID FROM {$table_data['PREFIX']}USER_PREFS WHERE UID = '$uid'";

        if (!$result_forum = db_query($sql, $db_user_update_prefs)) return false;

        if (db_num_rows($result_forum) > 0) {

            // previous entry which we will UPDATE

            $values  = array();
            $columns = array();

            $values_array = array();

            foreach($forum_prefs as $pref_name => $pref_setting) {

                $pref_setting = db_escape_string($pref_setting);
                $values_array[] = "$pref_name = '$pref_setting'";
            }

            if (sizeof($values_array) > 0) {

                $values = implode(", ", $values_array);

                $sql = "UPDATE LOW_PRIORITY {$table_data['PREFIX']}USER_PREFS SET $values WHERE UID = '$uid'";

                if (!$result_forum = db_query($sql, $db_user_update_prefs)) return false;
            }

        }else {

            // no previous entry, construct an INSERT query

            $values  = array();
            $columns = array();

            $values_array = array();

            foreach($forum_prefs as $pref_name => $pref_setting) {

                 $pref_setting = db_escape_string($pref_setting);
                 $values_array[$pref_name] = "'$pref_setting'";
            }

            if (sizeof($values_array) > 0) {

                $columns = implode(", ", array_keys($values_array));
                $values  = implode(", ", array_values($values_array));

                $sql = "INSERT INTO {$table_data['PREFIX']}USER_PREFS (UID, $columns) VALUES ('$uid', $values) ";

                if (!$result_forum = db_query($sql, $db_user_update_prefs)) return false;
            }
        }
    }

    return ($result_global && $result_forum);
}

function user_check_pref($name, $value)
{
    // Checks to ensure that a preference setting contains valid data

    if (strlen(trim($value)) == 0) return true;

    if ($name == "FIRSTNAME" || $name == "LASTNAME") {
        return preg_match("/^[a-z0-9 ]*$/i", $value);
    } elseif ($name == "STYLE" || $name == "EMOTICONS" || $name == "LANGUAGE") {
        return preg_match("/^[a-z0-9_-]*$/i", $value);
    } elseif ($name ==  "DOB") {
        return preg_match("/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/", $value);
    } elseif ($name == "HOMEPAGE_URL" || $name == "PIC_URL" || $name == "AVATAR_URL") {
        return (preg_match("/^http:\/\/[_\.0-9a-z\-~]*/i", $value) || $value == "");
    } elseif ($name == "EMAIL_NOTIFY" || $name == "DL_SAVING" || $name == "MARK_AS_OF_INT" || $name == "VIEW_SIGS" || $name == "PM_NOTIFY" || $name == "PM_NOTIFY_EMAIL" || $name == "PM_INCLUDE_REPLY" || $name == "PM_SAVE_SENT_ITEM" || $name == "PM_EXPORT_ATTACHMENTS" || $name == "PM_EXPORT_STYLE" || $name == "PM_EXPORT_WORDFILTER" || $name == "IMAGES_TO_LINKS" || $name == "SHOW_STATS" || $name == "USE_WORD_FILTER" || $name == "USE_ADMIN_FILTER" || $name == "ALLOW_EMAIL" || $name == "ALLOW_PM" || $name == "ENABLE_WIKI_WORDS" || $name == "USE_MOVER_SPOILER" || $name == "USE_LIGHT_MODE_SPOILER" || $name == "USE_OVERFLOW_RESIZE" || $name == "REPLY_QUICK") {
        return ($value == "Y" || $value == "N") ? true : false;
    } elseif ($name == "PIC_AID" || $name == "AVATAR_AID") {
        return (is_md5($value) || $value == "");
    } elseif ($name == "ANON_LOGON" || $name == "TIMEZONE" || $name == "POSTS_PER_PAGE" || $name == "FONT_SIZE" || $name == "START_PAGE" || $name == "DOB_DISPLAY" || $name == "POST_PAGE" || $name == "SHOW_THUMBS" || $name == "PM_AUTO_PRUNE" || $name == "PM_EXPORT_FILE" || $name == "PM_EXPORT_TYPE") {
        return is_numeric($value);
    } else {
        return false;
    }
}

function user_update_sig($uid, $content, $html, $global_update = false)
{
    if (!$db_user_update_sig = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    $content = db_escape_string($content);
    $html = db_escape_string($html);

    if ($global_update === true) {

        if (!$forum_prefix_array = forum_get_all_prefixes()) return false;

        foreach($forum_prefix_array as $forum_prefix) {

            $sql = "SELECT UID FROM {$forum_prefix}USER_SIG ";
            $sql.= "WHERE UID = '$uid'";

            if (!$result = db_query($sql, $db_user_update_sig)) return false;

            if (db_num_rows($result) > 0) {

                $sql = "UPDATE LOW_PRIORITY {$forum_prefix}USER_SIG SET CONTENT = '$content', ";
                $sql.= "HTML = '$html' WHERE UID = '$uid'";

            }else {

                $sql = "INSERT INTO {$forum_prefix}USER_SIG (UID, CONTENT, HTML) ";
                $sql.= "VALUES ('$uid', '$content', '$html')";
            }

            if (!$result = db_query($sql, $db_user_update_sig)) return false;
        }

    }else {

        if (!$table_data = get_table_prefix()) return false;

        $sql = "SELECT UID FROM {$table_data['PREFIX']}USER_SIG ";
        $sql.= "WHERE UID = '$uid'";

        if (!$result = db_query($sql, $db_user_update_sig)) return false;

        if (db_num_rows($result) > 0) {

            $sql = "UPDATE LOW_PRIORITY {$table_data['PREFIX']}USER_SIG SET CONTENT = '$content', ";
            $sql.= "HTML = '$html' WHERE UID = '$uid'";

        }else {

            $sql = "INSERT INTO {$table_data['PREFIX']}USER_SIG (UID, CONTENT, HTML) ";
            $sql.= "VALUES ('$uid', '$content', '$html')";
        }

        if (!$result = db_query($sql, $db_user_update_sig)) return false;
    }

    return true;
}

function user_update_global_sig($uid, $value, $global = true)
{
    return user_update_prefs($uid, array('VIEW_SIGS' => ($value == 'N') ? 'N' : 'Y'), array('VIEW_SIGS' => $global));
}

function user_get_global_sig($uid)
{
    return bh_session_get_value('VIEW_SIGS');
}

function user_is_guest()
{
    return (bh_session_get_value('UID') == 0);
}

function user_guest_enabled()
{
    $forum_settings = forum_get_settings();

    if (forum_get_setting('guest_account_enabled', 'N')) {
        return false;
    }

    return true;
}

function user_cookies_set()
{
    if (isset($_COOKIE['bh_sess_hash'])) return false;

    if (defined('BEEHIVEMODE_LIGHT') || defined('BEEHIVE_LIGHT_INCLUDE')) {

        if (isset($_COOKIE['bh_light_remember_username'])) return true;
        return false;
    }

    if (isset($_COOKIE['bh_remember_username'])) return true;
    return false;
}

function user_get_forthcoming_birthdays()
{
    if (!$db_user_get_forthcoming_birthdays = db_connect()) return false;

    if (!$table_data = get_table_prefix()) return false;

    $uid = bh_session_get_value('UID');

    $sql = "SELECT USER.UID, USER.LOGON, USER.NICKNAME, USER_PEER.PEER_NICKNAME, USER_PREFS.DOB, ";
    $sql.= "DAYOFMONTH(USER_PREFS.DOB) AS BDAY, MONTH(USER_PREFS.DOB) AS BMONTH ";
    $sql.= "FROM USER USER LEFT JOIN USER_PREFS USER_PREFS ON (USER_PREFS.UID = USER.UID) ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}USER_PREFS USER_PREFS_GLOBAL ";
    $sql.= "ON (USER_PREFS_GLOBAL.UID = USER.UID) ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}USER_PEER USER_PEER ";
    $sql.= "ON (USER_PEER.PEER_UID = USER.UID AND USER_PEER.UID = '$uid') ";
    $sql.= "WHERE USER_PREFS.DOB > 0 AND (USER_PREFS.DOB_DISPLAY > 1 ";
    $sql.= "OR USER_PREFS_GLOBAL.DOB_DISPLAY > 1) ";
    $sql.= "AND ((MONTH(USER_PREFS.DOB) = MONTH(NOW()) ";
    $sql.= "AND DAYOFMONTH(USER_PREFS.DOB) >= DAYOFMONTH(NOW())) ";
    $sql.= "OR MONTH(USER_PREFS.DOB) > MONTH(NOW())) ";
    $sql.= "ORDER BY BMONTH ASC, BDAY ASC ";
    $sql.= "LIMIT 0, 5";

    if (!$result = db_query($sql, $db_user_get_forthcoming_birthdays)) return false;

    if (db_num_rows($result) > 0) {

        $user_birthdays_array = array();

        while ($user_birthday_data = db_fetch_array($result)) {

            if (isset($user_birthday_data['PEER_NICKNAME'])) {
                if (!is_null($user_birthday_data['PEER_NICKNAME']) && strlen($user_birthday_data['PEER_NICKNAME']) > 0) {
                    $user_birthday_data['NICKNAME'] = $user_birthday_data['PEER_NICKNAME'];
                }
            }

            $user_birthdays_array[] = $user_birthday_data;
        }

        return $user_birthdays_array;
    }

    return false;
}

function user_search_array_clean($user_search)
{
    return db_escape_string(trim(str_replace("%", "", $user_search)));
}

function user_search($user_search, $offset = 0, $exclude_uid = 0)
{
    if (!$db_user_search = db_connect()) return false;

    if (!is_numeric($offset)) return false;
    if (!is_numeric($exclude_uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $user_array = array();

    $uid = bh_session_get_value('UID');

    $user_search_array = preg_split("/[;|,]/", $user_search);
    $user_search_array = array_map('user_search_array_clean', $user_search_array);

    $user_search_logon = implode("%' OR LOGON LIKE '", $user_search_array);
    $user_search_nickname = implode("%' OR NICKNAME LIKE '", $user_search_array);

    // Main query.

    $sql = "SELECT SQL_CALC_FOUND_ROWS USER.UID, USER.LOGON, USER.NICKNAME, ";
    $sql.= "USER_PEER.PEER_NICKNAME, USER_PEER.RELATIONSHIP ";
    $sql.= "FROM USER USER LEFT JOIN {$table_data['PREFIX']}USER_PEER USER_PEER ";
    $sql.= "ON (USER_PEER.PEER_UID = USER.UID AND USER_PEER.UID = '$uid') ";
    $sql.= "WHERE (LOGON LIKE '$user_search_logon%' ";
    $sql.= "OR NICKNAME LIKE '$user_search_nickname%') ";
    $sql.= "AND USER.UID <> $exclude_uid LIMIT $offset, 10";

    if (!$result = db_query($sql, $db_user_search)) return false;

    // Fetch the number of total results

    $sql = "SELECT FOUND_ROWS() AS ROW_COUNT";

    if (!$result_count = db_query($sql, $db_user_search)) return false;

    list($user_count) = db_fetch_array($result_count, DB_RESULT_NUM);

    // Check if we have any results.

    if (db_num_rows($result) > 0) {

        while ($user_data = db_fetch_array($result)) {

            if (isset($user_data['LOGON']) && isset($user_data['PEER_NICKNAME'])) {
                if (!is_null($user_data['PEER_NICKNAME']) && strlen($user_data['PEER_NICKNAME']) > 0) {
                    $user_data['NICKNAME'] = $user_data['PEER_NICKNAME'];
                }
            }

            if (!isset($user_data['LOGON'])) $user_data['LOGON'] = $lang['unknownuser'];
            if (!isset($user_data['NICKNAME'])) $user_data['NICKNAME'] = "";

            $user_array[$user_data['UID']] = $user_data;
        }

    }else if ($user_count > 0) {

        $offset = floor(($user_count - 1) / 10) * 10;
        return user_search($user_search, $offset, $exclude_uid);
    }

    return array('results_count' => $user_count,
                 'results_array' => $user_array);
}

function user_get_ip_addresses($uid)
{
    if (!$db_user_get_ip_addresses = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $user_ip_addresses_array = array();

    // Fetch the last 20 IP addresses from the POST table

    $sql = "SELECT DISTINCT IPADDRESS FROM {$table_data['PREFIX']}POST ";
    $sql.= "WHERE FROM_UID = '$uid' ORDER BY TID DESC LIMIT 0, 10";

    if (!$result = db_query($sql, $db_user_get_ip_addresses)) return false;

    if (db_num_rows($result) > 0) {

        while($user_ip_addresses_row = db_fetch_array($result)) {

            if (strlen($user_ip_addresses_row['IPADDRESS']) > 0) {

                $user_ip_addresses_array[] = $user_ip_addresses_row['IPADDRESS'];
            }
        }
    }

    return $user_ip_addresses_array;
}

function user_get_friends($uid)
{
    if (!$db_user_get_peers = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $user_rel = USER_FRIEND;

    $sess_uid = bh_session_get_value('UID');

    $sql = "SELECT USER.UID, USER.LOGON, USER.NICKNAME, USER_PEER.PEER_NICKNAME, ";
    $sql.= "USER_PEER.RELATIONSHIP FROM {$table_data['PREFIX']}USER_PEER USER_PEER ";
    $sql.= "LEFT JOIN USER USER ON (USER.UID = USER_PEER.PEER_UID) ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}USER_PEER USER_PEER ";
    $sql.= "ON (USER_PEER.PEER_UID = USER.UID AND USER_PEER.UID = '$sess_uid') ";
    $sql.= "WHERE USER.UID IS NOT NULL AND USER_PEER.UID = '$uid' ";
    $sql.= "AND (USER_PEER.RELATIONSHIP & $user_rel > 0) ";
    $sql.= "LIMIT 0, 20";

    if (!$result = db_query($sql, $db_user_get_peers)) return false;

    if (db_num_rows($result) > 0) {

        $user_get_peers_array = array();

        while ($user_data = db_fetch_array($result)) {

            if (isset($user_data['LOGON']) && isset($user_data['PEER_NICKNAME'])) {
                if (!is_null($user_data['PEER_NICKNAME']) && strlen($user_data['PEER_NICKNAME']) > 0) {
                    $user_data['NICKNAME'] = $user_data['PEER_NICKNAME'];
                }
            }

            if (!isset($user_data['LOGON'])) $user_data['LOGON'] = $lang['unknownuser'];
            if (!isset($user_data['NICKNAME'])) $user_data['NICKNAME'] = "";

            $user_get_peers_array[] = $user_data;
        }

        return $user_get_peers_array;
    }

    return false;
}

function user_get_ignored($uid)
{
    if (!$db_user_get_peers = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $user_rel = USER_IGNORED;

    $sql = "SELECT USER.UID, USER.LOGON, USER.NICKNAME, USER_PEER.PEER_NICKNAME, ";
    $sql.= "USER_PEER.RELATIONSHIP FROM {$table_data['PREFIX']}USER_PEER USER_PEER ";
    $sql.= "LEFT JOIN USER USER ON (USER.UID = USER_PEER.PEER_UID) ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}USER_PEER USER_PEER ";
    $sql.= "ON (USER_PEER.PEER_UID = USER.UID AND USER_PEER.UID = '$sess_uid') ";
    $sql.= "WHERE USER.UID IS NOT NULL AND USER_PEER.UID = '$uid' ";
    $sql.= "AND (USER_PEER.RELATIONSHIP & $user_rel > 0) ";
    $sql.= "LIMIT 0, 20";

    if (!$result = db_query($sql, $db_user_get_peers)) return false;

    if (db_num_rows($result) > 0) {

        $user_get_peers_array = array();

        while ($user_data = db_fetch_array($result)) {

            if (isset($user_data['LOGON']) && isset($user_data['PEER_NICKNAME'])) {
                if (!is_null($user_data['PEER_NICKNAME']) && strlen($user_data['PEER_NICKNAME']) > 0) {
                    $user_data['NICKNAME'] = $user_data['PEER_NICKNAME'];
                }
            }

            if (!isset($user_data['LOGON'])) $user_data['LOGON'] = $lang['unknownuser'];
            if (!isset($user_data['NICKNAME'])) $user_data['NICKNAME'] = "";

            $user_get_peers_array[] = $user_data;
        }

        return $user_get_peers_array;
    }

    return false;
}

function user_get_ignored_signatures($uid)
{
    if (!$db_user_get_peers = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $user_rel = USER_IGNORED_SIG;

    $sql = "SELECT USER.UID, USER.LOGON, USER.NICKNAME, USER_PEER.PEER_NICKNAME, ";
    $sql.= "USER_PEER.RELATIONSHIP FROM {$table_data['PREFIX']}USER_PEER USER_PEER ";
    $sql.= "LEFT JOIN USER USER ON (USER.UID = USER_PEER.PEER_UID) ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}USER_PEER USER_PEER ";
    $sql.= "ON (USER_PEER.PEER_UID = USER.UID AND USER_PEER.UID = '$sess_uid') ";
    $sql.= "WHERE USER.UID IS NOT NULL AND USER_PEER.UID = '$uid' ";
    $sql.= "AND (USER_PEER.RELATIONSHIP & $user_rel > 0) ";
    $sql.= "LIMIT 0, 20";

    if (!$result = db_query($sql, $db_user_get_peers)) return false;

    if (db_num_rows($result) > 0) {

        $user_get_peers_array = array();

        while ($user_data = db_fetch_array($result)) {

            if (isset($user_data['LOGON']) && isset($user_data['PEER_NICKNAME'])) {
                if (!is_null($user_data['PEER_NICKNAME']) && strlen($user_data['PEER_NICKNAME']) > 0) {
                    $user_data['NICKNAME'] = $user_data['PEER_NICKNAME'];
                }
            }

            if (!isset($user_data['LOGON'])) $user_data['LOGON'] = $lang['unknownuser'];
            if (!isset($user_data['NICKNAME'])) $user_data['NICKNAME'] = "";

            $user_get_peers_array[] = $user_data;
        }

        return $user_get_peers_array;
    }

    return false;
}

function user_get_relationships($uid, $offset = 0)
{
    if (!$db_user_get_relationships = db_connect()) return false;

    $user_get_peers_array = array();

    if (!is_numeric($uid)) return false;
    if (!is_numeric($offset)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT SQL_CALC_FOUND_ROWS USER.UID, USER.LOGON, USER.NICKNAME, ";
    $sql.= "USER_PEER.PEER_NICKNAME, USER_PEER.RELATIONSHIP, USER_PEER.PEER_NICKNAME ";
    $sql.= "FROM {$table_data['PREFIX']}USER_PEER USER_PEER ";
    $sql.= "LEFT JOIN USER USER ON (USER.UID = USER_PEER.PEER_UID) ";
    $sql.= "WHERE USER_PEER.UID = '$uid' AND USER.UID IS NOT NULL ";
    $sql.= "LIMIT $offset, 10";

    if (!$result = db_query($sql, $db_user_get_relationships)) return false;

    // Fetch the number of total results

    $sql = "SELECT FOUND_ROWS() AS ROW_COUNT";

    if (!$result_count = db_query($sql, $db_user_get_relationships)) return false;

    list($user_get_peers_count) = db_fetch_array($result_count, DB_RESULT_NUM);

    // Check if we have any results.

    if (db_num_rows($result) > 0) {

        while ($user_data = db_fetch_array($result)) {

            if (isset($user_data['LOGON']) && isset($user_data['PEER_NICKNAME'])) {
                if (!is_null($user_data['PEER_NICKNAME']) && strlen($user_data['PEER_NICKNAME']) > 0) {
                    $user_data['NICKNAME'] = $user_data['PEER_NICKNAME'];
                }
            }

            if (!isset($user_data['LOGON'])) $user_data['LOGON'] = $lang['unknownuser'];
            if (!isset($user_data['NICKNAME'])) $user_data['NICKNAME'] = "";

            $user_get_peers_array[$user_data['UID']] = $user_data;
        }

    }else if ($user_get_peers_count > 0) {

        $offset = floor(($user_get_peers_count - 1) / 10) * 10;
        return user_get_relationships($uid, $offset);
    }

    return array('user_count' => $user_get_peers_count,
                 'user_array' => $user_get_peers_array);
}

function user_get_peer_relationship($uid, $peer_uid)
{
    if (!$db_user_get_peer_relationship = db_connect()) return false;

    if (!is_numeric($uid)) return false;
    if (!is_numeric($peer_uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT RELATIONSHIP FROM {$table_data['PREFIX']}USER_PEER ";
    $sql.= "WHERE UID = '$uid' AND PEER_UID = '$peer_uid'";

    if (!$result = db_query($sql, $db_user_get_peer_relationship)) return false;

    if (db_num_rows($result) > 0) {

        list($peer_relationship) = db_fetch_array($result, DB_RESULT_NUM);
        return $peer_relationship;
    }

    return 0;
}

function user_get_peer_nickname($uid, $peer_uid)
{
    if (!$db_user_get_peer_nickname = db_connect()) return false;

    if (!is_numeric($uid)) return false;
    if (!is_numeric($peer_uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT PEER_NICKNAME FROM {$table_data['PREFIX']}USER_PEER ";
    $sql.= "WHERE UID = '$uid' AND PEER_UID = '$peer_uid'";

    if (!$result = db_query($sql, $db_user_get_peer_nickname)) return false;

    if (db_num_rows($result) > 0) {

        list($peer_nickname) = db_fetch_array($result, DB_RESULT_NUM);
        return $peer_nickname;
    }

    return user_get_nickname($peer_uid);
}

function user_search_relationships($user_search, $offset = 0, $exclude_uid = 0)
{
    if (!$db_user_search = db_connect()) return false;

    if (!is_numeric($offset)) return false;
    if (!is_numeric($exclude_uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $user_search_peers_array = array();

    if (($uid = bh_session_get_value('UID')) === false) return false;

    $user_search_array = explode(";", $user_search);
    $user_search_array = array_map('user_search_array_clean', $user_search_array);

    $user_search_logon = implode("%' OR LOGON LIKE '", $user_search_array);
    $user_search_nickname = implode("%' OR NICKNAME LIKE '", $user_search_array);

    $sql = "SELECT SQL_CALC_FOUND_ROWS USER.UID, USER.LOGON, USER.NICKNAME, ";
    $sql.= "USER_PEER.PEER_NICKNAME, USER_PEER.RELATIONSHIP ";
    $sql.= "FROM USER USER LEFT JOIN {$table_data['PREFIX']}USER_PEER USER_PEER ";
    $sql.= "ON (USER_PEER.PEER_UID = USER.UID AND USER_PEER.UID = '$uid') ";
    $sql.= "WHERE (LOGON LIKE '$user_search_logon%' ";
    $sql.= "OR NICKNAME LIKE '$user_search_nickname%') ";
    $sql.= "AND USER.UID <> $exclude_uid LIMIT $offset, 10";

    if (!$result = db_query($sql, $db_user_search)) return false;

    // Fetch the number of total results

    $sql = "SELECT FOUND_ROWS() AS ROW_COUNT";

    if (!$result_count = db_query($sql, $db_user_search)) return false;

    list($user_search_peers_count) = db_fetch_array($result_count, DB_RESULT_NUM);

    // Check if we have any results.

    if (db_num_rows($result) > 0) {

        while ($user_data = db_fetch_array($result)) {

            if (isset($user_data['LOGON']) && isset($user_data['PEER_NICKNAME'])) {
                if (!is_null($user_data['PEER_NICKNAME']) && strlen($user_data['PEER_NICKNAME']) > 0) {
                    $user_data['NICKNAME'] = $user_data['PEER_NICKNAME'];
                }
            }

            if (!isset($user_data['LOGON'])) $user_data['LOGON'] = $lang['unknownuser'];
            if (!isset($user_data['NICKNAME'])) $user_data['NICKNAME'] = "";

            $user_search_peers_array[$user_data['UID']] = $user_data;
        }

    }else if ($user_search_peers_count > 0) {

        $offset = floor(($user_search_peers_count - 1) / 10) * 10;
        return user_search_relationships($user_search, $offset, $exclude_uid);
    }

    return array('user_count' => $user_search_peers_count,
                 'user_array' => $user_search_peers_array);
}

function user_get_word_filter_list($offset)
{
    if (!$db_user_get_word_filter_list = db_connect()) return false;

    if (!is_numeric($offset)) $offset = 0;

    $word_filter_array = array();

    if (!$table_data = get_table_prefix()) return false;

    if (($uid = bh_session_get_value('UID')) === false) return false;

    $sql = "SELECT SQL_CALC_FOUND_ROWS FID, FILTER_NAME, MATCH_TEXT, ";
    $sql.= "REPLACE_TEXT, FILTER_TYPE, FILTER_ENABLED ";
    $sql.= "FROM {$table_data['PREFIX']}WORD_FILTER ";
    $sql.= "WHERE UID = '$uid' ORDER BY FID ";
    $sql.= "LIMIT $offset, 10";

    if (!$result = db_query($sql, $db_user_get_word_filter_list)) return false;

    // Fetch the number of total results

    $sql = "SELECT FOUND_ROWS() AS ROW_COUNT";

    if (!$result_count = db_query($sql, $db_user_get_word_filter_list)) return false;

    list($word_filter_count) = db_fetch_array($result_count, DB_RESULT_NUM);

    // Check if we have any results.

    if (db_num_rows($result) > 0) {

        while ($word_filter_data = db_fetch_array($result)) {

            $word_filter_array[$word_filter_data['FID']] = $word_filter_data;
        }

    }else if ($word_filter_count > 0) {

        $offset = floor(($word_filter_count - 1) / 10) * 10;
        return user_get_word_filter_list($offset);
    }

    return array('word_filter_count' => $word_filter_count,
                 'word_filter_array' => $word_filter_array);
}

function user_get_word_filter($filter_id)
{
    if (!$db_user_get_word_filter = db_connect()) return false;

    if (!is_numeric($filter_id)) return false;

    if (($uid = bh_session_get_value('UID')) === false) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT FID, FILTER_NAME, MATCH_TEXT, REPLACE_TEXT, FILTER_TYPE, ";
    $sql.= "FILTER_ENABLED FROM {$table_data['PREFIX']}WORD_FILTER ";
    $sql.= "WHERE FID = '$filter_id' AND UID = '$uid' ";
    $sql.= "ORDER BY FID";

    if (!$result = db_query($sql, $db_user_get_word_filter)) return false;

    if (db_num_rows($result) > 0) {

        $word_filter_array = db_fetch_array($result);
        return $word_filter_array;
    }

    return false;
}

function user_get_word_filter_count()
{
    if (!$db_user_get_word_filter_count = db_connect()) return false;

    if (($uid = bh_session_get_value('UID')) === false) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT COUNT(FID) AS FILTER_COUNT ";
    $sql.= "FROM {$table_data['PREFIX']}WORD_FILTER ";
    $sql.= "WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_get_word_filter_count)) return false;

    list($word_filter_count) =  db_fetch_array($result, DB_RESULT_NUM);

    return $word_filter_count;
}

function user_clear_word_filter()
{
    if (!$db_user_clear_word_filter = db_connect()) return false;

    if (!$table_data = get_table_prefix()) return false;

    if (($uid = bh_session_get_value('UID')) === false) return false;

    $sql = "DELETE QUICK FROM {$table_data['PREFIX']}WORD_FILTER WHERE UID = '$uid'";

    if (!$result = db_query($sql, $db_user_clear_word_filter)) return false;

    return true;
}

function user_add_word_filter($filter_name, $match_text, $replace_text, $filter_option, $filter_enabled)
{
    if (!$db_user_add_word_filter = db_connect()) return false;

    $filter_name  = db_escape_string($filter_name);
    $match_text   = db_escape_string($match_text);
    $replace_text = db_escape_string($replace_text);

    if (!is_numeric($filter_option)) return false;
    if (!is_numeric($filter_enabled)) return false;

    if (!$table_data = get_table_prefix()) return false;

    if (($uid = bh_session_get_value('UID')) === false) return false;

    $sql = "INSERT INTO {$table_data['PREFIX']}WORD_FILTER ";
    $sql.= "(UID, FILTER_NAME, MATCH_TEXT, REPLACE_TEXT, FILTER_TYPE, FILTER_ENABLED) ";
    $sql.= "VALUES ('$uid', '$filter_name', '$match_text', '$replace_text', '$filter_option', '$filter_enabled')";

    if (!$result = db_query($sql, $db_user_add_word_filter)) return false;

    return true;
}

function user_update_word_filter($filter_id, $filter_name, $match_text, $replace_text, $filter_option, $filter_enabled)
{
    if (!$db_user_save_word_filter = db_connect()) return false;

    if (!is_numeric($filter_id)) return false;

    if (!is_numeric($filter_option)) return false;
    if (!is_numeric($filter_enabled)) return false;

    $filter_name  = db_escape_string($filter_name);
    $match_text   = db_escape_string($match_text);
    $replace_text = db_escape_string($replace_text);

    if (!$table_data = get_table_prefix()) return false;

    if (($uid = bh_session_get_value('UID')) === false) return false;

    $sql = "UPDATE LOW_PRIORITY {$table_data['PREFIX']}WORD_FILTER SET FILTER_NAME = '$filter_name', ";
    $sql.= "MATCH_TEXT = '$match_text', REPLACE_TEXT = '$replace_text', ";
    $sql.= "FILTER_TYPE = '$filter_option', FILTER_ENABLED = '$filter_enabled' ";
    $sql.= "WHERE UID = '$uid' AND FID = '$filter_id'";

    if (!$result = db_query($sql, $db_user_save_word_filter)) return false;

    return true;
}

function user_delete_word_filter($filter_id)
{
    if (!$db_user_delete_word_filter = db_connect()) return false;

    if (!is_numeric($filter_id)) return false;

    if (!$table_data = get_table_prefix()) return false;

    if (($uid = bh_session_get_value('UID')) === false) return false;

    $sql = "DELETE QUICK FROM {$table_data['PREFIX']}WORD_FILTER ";
    $sql.= "WHERE UID = '$uid' AND FID = '$filter_id'";

    if (!$result = db_query($sql, $db_user_delete_word_filter)) return false;

    return true;
}

function user_is_active($uid)
{
    if (!$db_user_is_active = db_connect()) return false;

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $forum_fid = $table_data['FID'];

    $sql = "SELECT UID FROM SESSIONS WHERE UID = '$uid' ";
    $sql.= "AND FID = '$forum_fid' LIMIT 0, 1";

    if (!$result = db_query($sql, $db_user_is_active)) return false;

    return (db_num_rows($result) > 0);
}

function user_allow_pm($uid)
{
    return (bh_session_get_value('ALLOW_PM') == 'Y');
}

function user_allow_email($uid)
{
    return (bh_session_get_value('ALLOW_EMAIL') == "Y");
}

function user_prefs_prep_attachments($image_attachments_array)
{
    $attachments_array_prepared = array('' => '&nbsp;');

    $lang = load_language_file();

    if (!$attachment_dir = forum_get_setting('attachment_dir')) return array();

    foreach ($image_attachments_array as $hash => $attachment_details) {

        if ($image_info = getimagesize("$attachment_dir/$hash")) {

            $dimensions_text = "{$lang['dimensions']}: {$image_info[0]}x{$image_info[1]}px";
            $attachments_array_prepared[$hash] = "{$attachment_details['filename']}, $dimensions_text";
        }
    }

    return $attachments_array_prepared;
}

?>