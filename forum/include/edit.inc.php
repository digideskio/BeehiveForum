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

/* $Id: edit.inc.php,v 1.55 2005-03-28 19:43:34 decoyduck Exp $ */

include_once(BH_INCLUDE_PATH. "forum.inc.php");
include_once(BH_INCLUDE_PATH. "lang.inc.php");

function post_update($fid, $tid, $pid, $content)
{
    if (!is_numeric($tid)) return false;
    if (!is_numeric($pid)) return false;

    $db_post_update = db_connect();

    $content = addslashes($content);

    if (!$table_data = get_table_prefix()) return false;

    $sql = "UPDATE {$table_data['PREFIX']}POST_CONTENT SET CONTENT = '$content' ";
    $sql.= "WHERE TID = '$tid' AND PID = '$pid' LIMIT 1";

    $result = db_query($sql, $db_post_update);

    if (perm_check_folder_permissions($fid, USER_PERM_POST_APPROVAL) && !perm_is_moderator($fid)) {

        $sql = "UPDATE {$table_data['PREFIX']}POST SET APPROVED = 0, APPROVED_BY = 0 ";
        $sql.= "WHERE TID = '$tid' AND PID = '$pid' LIMIT 1";

        $result = db_query($sql, $db_post_update);
    }

    return $result;
}

function post_add_edit_text($tid, $pid)
{
    if (!is_numeric($tid)) return false;
    if (!is_numeric($pid)) return false;

    $db_post_add_edit_text = db_connect();
    $edit_uid = bh_session_get_value('UID');

    if (!$table_data = get_table_prefix()) return false;

    $sql = "UPDATE {$table_data['PREFIX']}POST SET EDITED = NOW(), EDITED_BY = '$edit_uid' ";
    $sql.= "WHERE TID = '$tid' AND PID = '$pid'";

    return db_query($sql, $db_post_add_edit_text);
}

function post_delete($tid, $pid)
{
    if (!is_numeric($tid)) return false;
    if (!is_numeric($pid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $db_post_delete = db_connect();

    if (thread_is_poll($tid) && $pid == 1) {

        $sql = "UPDATE {$table_data['PREFIX']}THREAD ";
        $sql.= "SET POLL_FLAG = 'N' WHERE TID = '$tid'";

        $result = db_query($sql, $db_post_delete);
    }

    $sql = "DELETE FROM {$table_data['PREFIX']}THREAD ";
    $sql.= "WHERE TID = '$tid' AND LENGTH = 1";

    $result = db_query($sql, $db_post_delete);

    $sql = "DELETE FROM SEARCH_POSTS WHERE TID = $tid ";
    $sql.= "AND PID = $pid";

    $result = db_query($sql, $db_post_delete);

    $sql = "UPDATE {$table_data['PREFIX']}POST_CONTENT SET CONTENT = NULL ";
    $sql.= "WHERE TID = '$tid' AND PID = '$pid'";

    $result = db_query($sql, $db_post_delete);

    return post_add_edit_text($tid, $pid);
}

function edit_refuse($tid, $pid)
{
    $lang = load_language_file();

    echo "<div align=\"center\">";
    echo "<h1>{$lang['error']}</h1>";
    echo "<p>{$lang['nopermissiontoedit']}</p>";
    echo form_quick_button("./discussion.php", $lang['back'], "msg", "$tid.$pid");
    echo "</div>";
}

?>