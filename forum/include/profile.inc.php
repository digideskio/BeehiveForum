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

/* $Id: profile.inc.php,v 1.83 2007-07-10 15:50:38 decoyduck Exp $ */

/**
* Functions relating to profiles
*/

/**
*/

// We shouldn't be accessing this file directly.

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Request-URI: ../index.php");
    header("Content-Location: ../index.php");
    header("Location: ../index.php");
    exit;
}

include_once(BH_INCLUDE_PATH. "constants.inc.php");
include_once(BH_INCLUDE_PATH. "form.inc.php");
include_once(BH_INCLUDE_PATH. "format.inc.php");
include_once(BH_INCLUDE_PATH. "forum.inc.php");
include_once(BH_INCLUDE_PATH. "lang.inc.php");
include_once(BH_INCLUDE_PATH. "session.inc.php");

function profile_section_get_name($psid)
{
   $db_profile_section_get_name = db_connect();

   if (!is_numeric($psid)) return false;

   if (!$table_data = get_table_prefix()) return "The Unknown Section";

   $sql = "SELECT PS.NAME FROM {$table_data['PREFIX']}PROFILE_SECTION PS WHERE PS.PSID = '$psid'";

   if (!$result = db_query($sql, $db_profile_section_get_name)) return false;

   if (db_num_rows($result) > 0) {

       list($sectionname) = db_fetch_array($result, DB_RESULT_NUM);
       return $sectionname;
   }

   return "The Unknown Section";
}

function profile_section_create($name)
{
    $db_profile_section_create = db_connect();

    if (!$table_data = get_table_prefix()) return false;

    $name = db_escape_string($name);

    $sql = "SELECT COALESCE(MAX(POSITION), 0) + 1 FROM {$table_data['PREFIX']}PROFILE_SECTION ";

    if (!$result = db_query($sql, $db_profile_section_create)) return false;

    list($new_position) = db_fetch_array($result, DB_RESULT_NUM);

    $sql = "INSERT INTO {$table_data['PREFIX']}PROFILE_SECTION (NAME, POSITION) ";
    $sql.= "VALUES ('$name', '$new_position')";

    if ($result = db_query($sql, $db_profile_section_create)) {
        
        $new_psid = db_insert_id($db_profile_section_create);
        return $new_psid;
    }
    
    return false;
}

function profile_section_update($psid, $name)
{
    $db_profile_section_update = db_connect();

    if (!is_numeric($psid)) return false;

    $name = db_escape_string($name);

    if (!$table_data = get_table_prefix()) return false;

    $sql = "UPDATE {$table_data['PREFIX']}PROFILE_SECTION ";
    $sql.= "SET NAME = '$name' WHERE PSID = '$psid'";

    if (!$result = db_query($sql, $db_profile_section_update)) return false;

    return $result;
}

function profile_sections_get()
{
    $db_profile_section_get = db_connect();

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT PROFILE_SECTION.PSID, PROFILE_SECTION.NAME, ";
    $sql.= "PROFILE_SECTION.POSITION, COUNT(PROFILE_ITEM.PIID) AS ITEM_COUNT ";
    $sql.= "FROM {$table_data['PREFIX']}PROFILE_SECTION PROFILE_SECTION ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}PROFILE_ITEM PROFILE_ITEM ";
    $sql.= "ON (PROFILE_ITEM.PSID = PROFILE_SECTION.PSID) ";
    $sql.= "GROUP BY PROFILE_SECTION.PSID ";
    $sql.= "ORDER BY PROFILE_SECTION.POSITION, PROFILE_SECTION.PSID";

    if (!$result = db_query($sql, $db_profile_section_get)) return false;

    if (db_num_rows($result) > 0) {

        $profile_sections_get = array();

        while($row = db_fetch_array($result)) {

            $profile_sections_get[$row['PSID']] = $row;
        }

        return $profile_sections_get;
    }

    return false;
}

function profile_sections_get_by_page($offset)
{
    $db_profile_sections_get_by_page = db_connect();

    if (!is_numeric($offset)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $profile_sections_array = array();

    $sql = "SELECT COUNT(PSID) FROM {$table_data['PREFIX']}PROFILE_SECTION";

    if (!$result = db_query($sql, $db_profile_sections_get_by_page)) return false;

    list($profile_sections_count) = db_fetch_array($result, DB_RESULT_NUM);

    $sql = "SELECT PROFILE_SECTION.PSID, PROFILE_SECTION.NAME, ";
    $sql.= "PROFILE_SECTION.POSITION, COUNT(PROFILE_ITEM.PIID) AS ITEM_COUNT ";
    $sql.= "FROM {$table_data['PREFIX']}PROFILE_SECTION PROFILE_SECTION ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}PROFILE_ITEM PROFILE_ITEM ";
    $sql.= "ON (PROFILE_ITEM.PSID = PROFILE_SECTION.PSID) ";
    $sql.= "GROUP BY PROFILE_SECTION.PSID ";
    $sql.= "ORDER BY PROFILE_SECTION.POSITION, PROFILE_SECTION.PSID ";
    $sql.= "LIMIT $offset, 10";

    if (!$result = db_query($sql, $db_profile_sections_get_by_page)) return false;

    if (db_num_rows($result) > 0) {

        while($row = db_fetch_array($result)) {

            $profile_sections_array[] = $row;
        }

    }else if ($profile_sections_count > 0) {

        $offset = calculate_max_offset($profile_sections_count, 10);
        return profile_sections_get_by_page($offset);
    }

    return array('profile_sections_array' => $profile_sections_array,
                 'profile_sections_count' => $profile_sections_count);
}

function profile_items_get($psid)
{
    $db_profile_items_get = db_connect();

    if (!is_numeric($psid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT PIID, NAME, TYPE, POSITION ";
    $sql.= "FROM {$table_data['PREFIX']}PROFILE_ITEM ";
    $sql.= "WHERE PSID = '$psid' ORDER BY POSITION, PIID";

    if (!$result = db_query($sql, $db_profile_items_get)) return false;

    if (db_num_rows($result) > 0) {

        $profile_items_get = array();

        while($row = db_fetch_array($result)) {

            $profile_items_get[] = $row;
        }

        return $profile_items_get;

    }else {

        return false;
    }
}

function profile_items_get_by_page($psid, $offset)
{
    $db_profile_items_get_by_page = db_connect();

    if (!is_numeric($psid)) return false;
    if (!is_numeric($offset)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $profile_items_array = array();

    $sql = "SELECT COUNT(PIID) FROM {$table_data['PREFIX']}PROFILE_ITEM ";
    $sql.= "WHERE PSID = '$psid'";

    if (!$result = db_query($sql, $db_profile_items_get_by_page)) return false;

    list($profile_items_count) = db_fetch_array($result, DB_RESULT_NUM);

    $sql = "SELECT PIID, NAME, TYPE, POSITION ";
    $sql.= "FROM {$table_data['PREFIX']}PROFILE_ITEM ";
    $sql.= "WHERE PSID = '$psid' ORDER BY POSITION, PIID ";
    $sql.= "LIMIT $offset, 10";

    if (!$result = db_query($sql, $db_profile_items_get_by_page)) return false;

    if (db_num_rows($result) > 0) {

        while($profile_item = db_fetch_array($result)) {

            if (($profile_item['TYPE'] == PROFILE_ITEM_RADIO) || ($profile_item['TYPE'] == PROFILE_ITEM_DROPDOWN)) {
                @list($profile_item['NAME']) = explode(':', $profile_item['NAME']);
            }

            $profile_items_array[] = $profile_item;
        }

    }else if ($profile_items_count > 0) {

        $offset = calculate_max_offset($profile_items_count, 10);
        return profile_items_get_by_page($psid, $offset);
    }

    return array('profile_items_array' => $profile_items_array,
                 'profile_items_count' => $profile_items_count);
}

function profile_item_get_name($piid)
{
    $db_profile_item_get = db_connect();

    if (!is_numeric($piid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT NAME FROM {$table_data['PREFIX']}PROFILE_ITEM ";
    $sql.= "WHERE PIID = '$piid'";

    if (!$result = db_query($sql, $db_profile_item_get)) return false;

    if (db_num_rows($result) > 0) {

        $profile_data = db_fetch_array($result);
        return $profile_data['NAME'];
    }

    return false;
}

function profile_item_create($psid, $name, $type)
{
    $db_profile_item_create = db_connect();

    if (!is_numeric($psid)) return false;
    if (!is_numeric($type)) return false;

    $name = db_escape_string($name);

    if (!$table_data = get_table_prefix()) return false;

    $new_position = 1;

    $sql = "SELECT COALESCE(MAX(POSITION), 0) + 1 FROM {$table_data['PREFIX']}PROFILE_ITEM ";
    $sql.= "WHERE PSID = '$psid'";

    if (!$result = db_query($sql, $db_profile_item_create)) return false;

    list($new_position) = db_fetch_array($result, DB_RESULT_NUM);

    $sql = "INSERT INTO {$table_data['PREFIX']}PROFILE_ITEM (PSID, NAME, TYPE, POSITION) ";
    $sql.= "VALUES ('$psid', '$name', '$type', '$new_position')";

    if ($result = db_query($sql, $db_profile_item_create)) {
        
        $new_piid = db_insert_id($db_profile_item_create);
        return $new_piid;
    }

    return false;
}

function profile_item_update($piid, $psid, $type, $name)
{
    $db_profile_item_update = db_connect();

    if (!is_numeric($piid)) return false;
    if (!is_numeric($psid)) return false;
    if (!is_numeric($type)) return false;

    $name = db_escape_string($name);

    if (!$table_data = get_table_prefix()) return false;

    $sql = "UPDATE {$table_data['PREFIX']}PROFILE_ITEM SET PSID = '$psid', ";
    $sql.= "TYPE = '$type', NAME = '$name' WHERE PIID = '$piid'";

    if (!$result = db_query($sql, $db_profile_item_update)) return false;

    return $result;
}

function profile_section_delete($psid)
{
    $db_profile_section_delete = db_connect();

    if (!is_numeric($psid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT PIID FROM {$table_data['PREFIX']}PROFILE_ITEM WHERE PSID = '$psid'";

    if (!$result = db_query($sql, $db_profile_section_delete)) return false;

    if (db_num_rows($result) > 0) {

        while ($profile_item = db_fetch_array($result)) {

            if (isset($profile_item['PIID']) && is_numeric($profile_item['PIID'])) {

                profile_item_delete($profile_item['PIID']);
            }
        }
    }

    $sql = "DELETE FROM {$table_data['PREFIX']}PROFILE_SECTION WHERE PSID = '$psid'";
    
    if (!$result = db_query($sql, $db_profile_section_delete)) return false;

    return true;
}

function profile_item_delete($piid)
{
    $db_profile_item_delete = db_connect();

    if (!is_numeric($piid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "DELETE FROM {$table_data['PREFIX']}USER_PROFILE WHERE PIID = '$piid'";
    
    if (!$result = db_query($sql, $db_profile_item_delete)) return false;

    $sql = "DELETE FROM {$table_data['PREFIX']}PROFILE_ITEM WHERE PIID = '$piid'";
    
    if (!$result = db_query($sql, $db_profile_item_delete)) return false;

    return true;
}

function profile_section_dropdown($default_psid, $field_name = 't_psid')
{
    $db_profile_section_dropdown = db_connect();

    if (!$table_data = get_table_prefix()) return "";

    $sql = "SELECT PSID, NAME FROM {$table_data['PREFIX']}PROFILE_SECTION";

    if (!$result = db_query($sql, $db_profile_section_dropdown)) return false;

    if (db_num_rows($result) > 0) {

        while ($profile_section_data = db_fetch_array($result)) {
            $profile_sections_array[$profile_section_data['PSID']] = $profile_section_data['NAME'];
        }

        return form_dropdown_array($field_name, $profile_sections_array, $default_psid);
    }

    return "";
}

/**
* Gets profile values stored for a user
*
* Returns an array of the following information:
* - <b>PSID</b>         : <i>[PROFILE_SECTION.PSID]</i> Profile section ID
* - <b>SECTION_NAME</b> : <i>[PROFILE_SECTION.NAME]</i> Name of profile section
* - <b>PIID</b>         : <i>[PROFILE_ITEM.PIID]</i>    Profile item ID
* - <b>ITEM_NAME</b>    : <i>[PROFILE_ITEM.NAME]</i>    Name of the profile item
* - <b>TYPE</b>         : <i>[PROFILE_ITEM.TYPE]</i>    Type of profile item (Eg radio-button, checkbox, text field, multi-line text field)
* - <b>CHECK_PIID</b>   : <i>[USER_PROFILE.PIID]</i>
* - <b>ENTRY</b>        : <i>[USER_PROFILE.ENTRY]</i>   User entered value for profile item
* - <b>PRIVACY</b>      : <i>[USER_PROFILE.PRIVACY]</i> Level of privacy of profile item (Eg 0 for viewable by all, 1 for viewable only by friends)
*
* @param integer $uid Returns the profile of this UID
*/
function profile_get_user_values($uid)
{
    $db_profile_get_user_values = db_connect();

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT PROFILE_SECTION.PSID, PROFILE_SECTION.NAME AS SECTION_NAME, ";
    $sql.= "PROFILE_ITEM.PIID, PROFILE_ITEM.NAME AS ITEM_NAME, PROFILE_ITEM.TYPE, ";
    $sql.= "USER_PROFILE.PIID AS CHECK_PIID, USER_PROFILE.ENTRY, USER_PROFILE.PRIVACY ";
    $sql.= "FROM {$table_data['PREFIX']}PROFILE_SECTION PROFILE_SECTION ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}PROFILE_ITEM PROFILE_ITEM ";
    $sql.= "ON (PROFILE_ITEM.PSID = PROFILE_SECTION.PSID) ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}USER_PROFILE USER_PROFILE ";
    $sql.= "ON (USER_PROFILE.PIID = PROFILE_ITEM.PIID AND USER_PROFILE.UID = '$uid') ";
    $sql.= "ORDER BY PROFILE_SECTION.POSITION, PROFILE_SECTION.PSID, ";
    $sql.= "PROFILE_ITEM.POSITION, PROFILE_ITEM.PIID";

    if (!$result = db_query($sql, $db_profile_get_user_values)) return false;

    if (db_num_rows($result) > 0) {

        $profile_values_array = array();

        while($row = db_fetch_array($result)) {

            $profile_values_array[] = $row;
        }

        return $profile_values_array;

    }else {

        return false;
    }
}

function profile_section_move_up($psid)
{
    $db_profile_section_move_up = db_connect();

    if (!is_numeric($psid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    profile_sections_positions_update();

    $sql = "SELECT PSID, POSITION FROM {$table_data['PREFIX']}PROFILE_SECTION ";
    $sql.= "ORDER BY POSITION";

    if (!$result = db_query($sql, $db_profile_section_move_up)) return false;

    $profile_section_order = array();

    while ($row = db_fetch_array($result)) {

        $profile_section_order[] = $row['PSID'];
        $profile_section_position[$row['PSID']] = $row['POSITION'];
    }

    if (($profile_section_order_key = array_search($psid, $profile_section_order)) !== false) {

        $profile_section_order_key--;

        if ($profile_section_order_key < 0) {
            $profile_section_order_key = 0;
        }

        $new_position = $profile_section_position[$psid];

        $sql = "UPDATE {$table_data['PREFIX']}PROFILE_SECTION SET POSITION = '$new_position' ";
        $sql.= "WHERE PSID = '{$profile_section_order[$profile_section_order_key]}'";

        if (!$result = db_query($sql, $db_profile_section_move_up)) return false;

        $new_position = $profile_section_position[$profile_section_order[$profile_section_order_key]];

        $sql = "UPDATE {$table_data['PREFIX']}PROFILE_SECTION SET POSITION = '$new_position' ";
        $sql.= "WHERE PSID = '$psid'";

        if (!$result = db_query($sql, $db_profile_section_move_up)) return false;

        return true;
    }

    return false;
}

function profile_section_move_down($psid)
{
    $db_profile_section_move_down = db_connect();

    if (!is_numeric($psid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    profile_sections_positions_update();

    $sql = "SELECT PSID, POSITION FROM {$table_data['PREFIX']}PROFILE_SECTION ";
    $sql.= "ORDER BY POSITION";

    if (!$result = db_query($sql, $db_profile_section_move_down)) return false;

    $profile_section_order = array();

    while ($row = db_fetch_array($result)) {

        $profile_section_order[] = $row['PSID'];
        $profile_section_position[$row['PSID']] = $row['POSITION'];
    }

    if (($profile_section_order_key = array_search($psid, $profile_section_order)) !== false) {

        $profile_section_order_key++;

        if ($profile_section_order_key > sizeof($profile_section_order)) {
            $profile_section_order = sizeof($profile_section_order);
        }

        $new_position = $profile_section_position[$psid];

        if (isset($profile_section_order[$profile_section_order_key])) {

            $sql = "UPDATE {$table_data['PREFIX']}PROFILE_SECTION SET POSITION = '$new_position' ";
            $sql.= "WHERE PSID = '{$profile_section_order[$profile_section_order_key]}'";

            if (!$result = db_query($sql, $db_profile_section_move_down)) return false;

            $new_position = $profile_section_position[$profile_section_order[$profile_section_order_key]];

            $sql = "UPDATE {$table_data['PREFIX']}PROFILE_SECTION SET POSITION = '$new_position' ";
            $sql.= "WHERE PSID = '$psid'";

            if (!$result = db_query($sql, $db_profile_section_move_down)) return false;

            return true;
        }
    }

    return false;
}

function profile_item_move_up($psid, $piid)
{
    $db_profile_item_move_down = db_connect();

    if (!is_numeric($piid)) return false;
    if (!is_numeric($psid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    profile_items_positions_update();

    $sql = "SELECT PIID, POSITION FROM {$table_data['PREFIX']}PROFILE_ITEM ";
    $sql.= "WHERE PSID = '$psid' ORDER BY POSITION";

    if (!$result = db_query($sql, $db_profile_item_move_down)) return false;

    $profile_item_order = array();

    while ($row = db_fetch_array($result)) {
        
        $profile_item_order[] = $row['PIID'];
        $profile_item_position[$row['PIID']] = $row['POSITION'];
    }

    if (($profile_item_order_key = array_search($piid, $profile_item_order)) !== false) {

        $profile_item_order_key--;

        if ($profile_item_order_key < 0) {
            $profile_item_order_key = 0;
        }

        $new_position = $profile_item_position[$piid];

        $sql = "UPDATE {$table_data['PREFIX']}PROFILE_ITEM SET POSITION = '$new_position' ";
        $sql.= "WHERE PIID = '{$profile_item_order[$profile_item_order_key]}' ";
        $sql.= "AND PSID = '$psid'";

        if (!$result = db_query($sql, $db_profile_item_move_down)) return false;

        $new_position = $profile_item_position[$profile_item_order[$profile_item_order_key]];

        $sql = "UPDATE {$table_data['PREFIX']}PROFILE_ITEM SET POSITION = '$new_position' ";
        $sql.= "WHERE PIID = '$piid' AND PSID = '$psid'";

        if (!$result = db_query($sql, $db_profile_item_move_down)) return false;

        return true;
    }

    return false;
}

function profile_item_move_down($psid, $piid)
{
    $db_profile_item_move_down = db_connect();

    if (!is_numeric($piid)) return false;
    if (!is_numeric($psid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    profile_items_positions_update();

    $sql = "SELECT PIID, POSITION FROM {$table_data['PREFIX']}PROFILE_ITEM ";
    $sql.= "WHERE PSID = '$psid' ORDER BY POSITION";

    if (!$result = db_query($sql, $db_profile_item_move_down)) return false;

    $profile_item_order = array();

    while ($row = db_fetch_array($result)) {
        
        $profile_item_order[] = $row['PIID'];
        $profile_item_position[$row['PIID']] = $row['POSITION'];
    }

    if (($profile_item_order_key = array_search($piid, $profile_item_order)) !== false) {

        $profile_item_order_key++;

        if ($profile_item_order_key > sizeof($profile_item_order)) {
            $profile_item_order_key = sizeof($profile_item_order);
        }

        $new_position = $profile_item_position[$piid];

        $sql = "UPDATE {$table_data['PREFIX']}PROFILE_ITEM SET POSITION = '$new_position' ";
        $sql.= "WHERE PIID = '{$profile_item_order[$profile_item_order_key]}' ";
        $sql.= "AND PSID = '$psid'";

        if (!$result = db_query($sql, $db_profile_item_move_down)) return false;

        $new_position = $profile_item_position[$profile_item_order[$profile_item_order_key]];

        $sql = "UPDATE {$table_data['PREFIX']}PROFILE_ITEM SET POSITION = '$new_position' ";
        $sql.= "WHERE PIID = '$piid' AND PSID = '$psid'";

        if (!$result = db_query($sql, $db_profile_item_move_down)) return false;

        return true;
    }

    return false;
}

function profile_sections_positions_update()
{
    $new_position = 0;

    $db_profile_sections_positions_update = db_connect();

    if (!$table_data = get_table_prefix()) return;

    $sql = "SELECT PSID FROM {$table_data['PREFIX']}PROFILE_SECTION ";
    $sql.= "ORDER BY POSITION";

    if (!$result = db_query($sql, $db_profile_sections_positions_update)) return false;

    while (list($psid) = db_fetch_array($result, DB_RESULT_NUM)) {

        if (isset($psid) && is_numeric($psid)) {

            $new_position++;
        
            $sql = "UPDATE {$table_data['PREFIX']}PROFILE_SECTION ";
            $sql.= "SET POSITION = '$new_position' WHERE PSID = '$psid'";

            if (!$result_update = db_query($sql, $db_profile_sections_positions_update)) return false;
        }
    }
}

function profile_items_positions_update()
{
    $new_position = 0;
    $current_section = false;

    $db_profile_items_positions_update = db_connect();

    if (!$table_data = get_table_prefix()) return;

    $sql = "SELECT PIID, PSID FROM {$table_data['PREFIX']}PROFILE_ITEM ";
    $sql.= "ORDER BY PSID, POSITION";

    if (!$result = db_query($sql, $db_profile_items_positions_update)) return false;

    while (list($piid, $psid) = db_fetch_array($result, DB_RESULT_NUM)) {
        
        if (($current_section == false) || ($current_section <> $psid)) {
            
            $new_position = 0;
            $current_section = $psid;
        }
        
        if (isset($piid) && is_numeric($piid)) {

            $new_position++;
        
            $sql = "UPDATE {$table_data['PREFIX']}PROFILE_ITEM ";
            $sql.= "SET POSITION = '$new_position' WHERE PIID = '$piid'";

            if (!$result_update = db_query($sql, $db_profile_items_positions_update)) return false;
        }
    }
}

function profile_get_section($psid)
{
    $db_profile_get_section = db_connect();

    if (!is_numeric($psid)) return false;

    if (!$table_data = get_table_prefix()) return;

    $sql = "SELECT NAME FROM {$table_data['PREFIX']}PROFILE_SECTION ";
    $sql.= "WHERE PSID = '$psid'";

    if (!$result = db_query($sql, $db_profile_get_section)) return false;

    if (db_num_rows($result)) {

        $profile_section_data = db_fetch_array($result);
        return $profile_section_data;
    }

    return false;
}

function profile_get_item($piid)
{
    $db_profile_get_item = db_connect();

    if (!is_numeric($piid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT NAME, TYPE FROM {$table_data['PREFIX']}PROFILE_ITEM ";
    $sql.= "WHERE PIID = '$piid'";

    if (!$result = db_query($sql, $db_profile_get_item)) return false;

    if (db_num_rows($result)) {

        $profile_item_data = db_fetch_array($result);
        return $profile_item_data;
    }

    return false;
}

?>