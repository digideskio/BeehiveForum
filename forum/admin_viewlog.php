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

/* $Id: admin_viewlog.php,v 1.76 2005-03-14 13:27:17 decoyduck Exp $ */

// Constant to define where the include files are
define("BH_INCLUDE_PATH", "./include/");

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

include_once(BH_INCLUDE_PATH. "admin.inc.php");
include_once(BH_INCLUDE_PATH. "constants.inc.php");
include_once(BH_INCLUDE_PATH. "db.inc.php");
include_once(BH_INCLUDE_PATH. "format.inc.php");
include_once(BH_INCLUDE_PATH. "header.inc.php");
include_once(BH_INCLUDE_PATH. "html.inc.php");
include_once(BH_INCLUDE_PATH. "lang.inc.php");
include_once(BH_INCLUDE_PATH. "logon.inc.php");
include_once(BH_INCLUDE_PATH. "perm.inc.php");
include_once(BH_INCLUDE_PATH. "session.inc.php");

// Check we're logged in correctly

if (!$user_sess = bh_session_check()) {
    $request_uri = rawurlencode(get_request_uri(true));
    $webtag = get_webtag($webtag_search);
    header_redirect("./logon.php?webtag=$webtag&final_uri=$request_uri");
}

// Check we have a webtag

if (!$webtag = get_webtag($webtag_search)) {
    $request_uri = rawurlencode(get_request_uri(true));
    header_redirect("./forums.php?webtag_search=$webtag_search&final_uri=admin.php%3Fpage%3D$request_uri");
}

// Load language file

$lang = load_language_file();

// Check that we have access to this forum

if (!forum_check_access_level()) {
    $request_uri = rawurlencode(get_request_uri(true));
    header_redirect("./forums.php?webtag_search=$webtag_search&final_uri=$request_uri");
}

html_draw_top();

if (!(perm_has_admin_access())) {
    echo "<h1>{$lang['accessdenied']}</h1>\n";
    echo "<p>{$lang['accessdeniedexp']}</p>";
    html_draw_bottom();
    exit;
}

// Column sorting stuff

if (isset($_GET['sort_by'])) {
    if ($_GET['sort_by'] == "CREATED") {
        $sort_by = "CREATED";
    } elseif ($_GET['sort_by'] == "UID") {
        $sort_by = "ADMIN_LOG.UID";
    } elseif ($_GET['sort_by'] == "ACTION") {
        $sort_by = "ADMIN_LOG.ACTION";
    } else {
        $sort_by = "CREATED";
    }
} else {
    $sort_by = "CREATED";
}

if (isset($_GET['sort_dir'])) {
    if ($_GET['sort_dir'] == "DESC") {
        $sort_dir = "DESC";
    } else {
        $sort_dir = "ASC";
    }
} else {
    $sort_dir = "DESC";
}

if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $start = floor($_GET['page'] - 1) * 20;
}else {
    $start = 0;
}

// Clear the admin log.

if (isset($_POST['clear'])) {
    admin_clearlog();
}

// Draw the form
echo "<h1>{$lang['admin']} : {$lang['adminaccesslog']}</h1>\n";
echo "<p>{$lang['adminlogexp']}</p>\n";
echo "<div align=\"center\">\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"96%\">\n";
echo "    <tr>\n";
echo "      <td>\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";

if ($sort_by == 'CREATED' && $sort_dir == 'ASC') {
    echo "                    <td class=\"subhead\" width=\"100\" align=\"left\"><a href=\"admin_viewlog.php?webtag=$webtag&amp;sort_by=CREATED&amp;sort_dir=DESC\">{$lang['datetime']}</a></td>\n";
}else {
    echo "                    <td class=\"subhead\" width=\"100\" align=\"left\"><a href=\"admin_viewlog.php?webtag=$webtag&amp;sort_by=CREATED&amp;sort_dir=ASC\">{$lang['datetime']}</a></td>\n";
}

if ($sort_by == 'ADMIN_LOG.UID' && $sort_dir == 'ASC') {
    echo "                    <td class=\"subhead\" width=\"200\" align=\"left\"><a href=\"admin_viewlog.php?webtag=$webtag&amp;sort_by=UID&amp;sort_dir=DESC\">{$lang['logon']}</a></td>\n";
}else {
    echo "                    <td class=\"subhead\" width=\"200\" align=\"left\"><a href=\"admin_viewlog.php?webtag=$webtag&amp;sort_by=UID&amp;sort_dir=ASC\">{$lang['logon']}</a></td>\n";
}

if ($sort_by == 'ADMIN_LOG.ACTION' && $sort_dir == 'ASC') {
    echo "                    <td class=\"subhead\" align=\"left\"><a href=\"admin_viewlog.php?webtag=$webtag&amp;sort_by=ACTION&amp;sort_dir=DESC\">{$lang['action']}</a></td>\n";
}else {
    echo "                    <td class=\"subhead\" align=\"left\"><a href=\"admin_viewlog.php?webtag=$webtag&amp;sort_by=ACTION&amp;sort_dir=ASC\">{$lang['action']}</a></td>\n";
}

echo "                  </tr>\n";

$admin_log_array = admin_get_log_entries($start, $sort_by, $sort_dir);

if (sizeof($admin_log_array['admin_log_array']) > 0) {

    foreach ($admin_log_array['admin_log_array'] as $admin_log_entry) {

        echo "                  <tr>\n";
        echo "                    <td class=\"posthead\" align=\"left\">", format_time($admin_log_entry['CREATED']), "</td>\n";
        echo "                    <td class=\"posthead\" align=\"left\"><a href=\"admin_user.php?webtag=$webtag&amp;uid=", $admin_log_entry['UID'], "\">", format_user_name($admin_log_entry['LOGON'], $admin_log_entry['NICKNAME']), "</a></td>\n";

        switch ($admin_log_entry['ACTION']) {

            case CHANGE_USER_STATUS:

                $action_text = "{$lang['changeduserstatus']}: {$admin_log_entry['ENTRY']}";
                break;

            case CHANGE_FORUM_ACCESS:

                $action_text = "{$lang['changedfolderaccess']}: {$admin_log_entry['ENTRY']}";
                break;

            case DELETE_ALL_USER_POSTS:

                $action_text = "{$lang['deletedallusersposts']}: {$admin_log_entry['ENTRY']}";
                break;

            case BANNED_IPADDRESS:

                $action_text = "{$lang['bannedipaddress']} {$admin_log_entry['ENTRY']}";
                break;

            case UNBANNED_IPADDRESS:

                $action_text = "{$lang['unbannedipaddress']} {$admin_log_entry['ENTRY']}";
                break;

            case DELETE_ALL_ATTACHMENTS:

                $action_text = "{$lang['deletedusersattachments']} {$admin_log_entry['ENTRY']}";
                break;

            case EDIT_THREAD_OPTIONS:

                $action_text = "{$lang['changedtitleaccessfolder']}: {$admin_log_entry['ENTRY']}";
                break;

            case MOVED_THREADS:

                $action_text = "{$lang['movedthreads']}: {$admin_log_entry['ENTRY']}";
                break;

            case CREATE_NEW_FOLDER:

                $action_text = "{$lang['creatednewfolder']}: {$admin_log_entry['ENTRY']}";
                break;

            case CHANGE_PROFILE_SECT:

                $action_text = "{$lang['changedprofilesectiontitle']}: {$admin_log_entry['ENTRY']}";
                break;

            case ADDED_PROFILE_SECT:

                $action_text = "{$lang['addednewprofilesection']}: {$admin_log_entry['ENTRY']}";
                break;

            case DELETE_PROFILE_SECT:

                $action_text = "{$lang['deletedprofilesection']}: {$admin_log_entry['ENTRY']}";
                break;

            case CHANGE_PROFILE_ITEM:

                $action_text = "{$lang['changedprofileitemtitle']}: {$admin_log_entry['ENTRY']}";
                break;

            case ADDED_PROFILE_ITEM:

                $action_text = "{$lang['addednewprofileitem']}: {$admin_log_entry['ENTRY']}";
                break;

            case DELETE_PROFILE_ITEM:

                $action_text = "{$lang['deletedprofileitem']}: {$admin_log_entry['ENTRY']}";
                break;

            case EDITED_START_PAGE:

                $action_text = "{$lang['editedstartpage']}";
                break;

            case CREATED_NEW_STYLE:

                $action_text = "{$lang['savednewstyle']}";
                break;

            case MOVED_THREAD:

                $action_text = "{$lang['movedthread']}: {$admin_log_entry['ENTRY']}";
                break;

            case CLOSED_THREAD:

                $action_text = "{$lang['closedthread']}: {$admin_log_entry['ENTRY']}";
                break;

            case OPENED_THREAD:

                $action_text = "{$lang['openedthread']}: {$admin_log_entry['ENTRY']}";
                break;

            case RENAME_THREAD:

                $action_text = "{$lang['renamedthread']}: {$admin_log_entry['ENTRY']}";
                break;

            case DELETE_POST:

                $action_text = "{$lang['deletedpost']}: {$admin_log_entry['ENTRY']}";
                break;

            case EDIT_POST:

                $action_text = "{$lang['editedpost']}: {$admin_log_entry['ENTRY']}";
                break;

            case EDIT_WORD_FILTER:

                $action_text = "{$lang['editedwordfilter']}";
                break;

            case CREATE_THREAD_STICKY:

                $action_text = "{$lang['madethreadsticky']}: {$admin_log_entry['ENTRY']}";
                break;

            case REMOVE_THREAD_STICKY:

                $action_text = "{$lang['madethreadnonsticky']}: {$admin_log_entry['ENTRY']}";
                break;

            case END_USED_SESSION:

                $action_text = "{$lang['endedsessionforuser']}: {$admin_log_entry['ENTRY']}";
                break;

            case EDIT_FORUM_SETTINGS:

                $action_text = "{$lang['editedforumsettings']}";
                break;

            case LOCKED_THREAD:

                $action_text = "{$lang['lockedthreadtitlefolder']}: {$admin_log_entry['ENTRY']}";
                break;

            case UNLOCKED_THREAD:

                $action_text = "{$lang['unlockedthreadtitlefolder']}: {$admin_log_entry['ENTRY']}";
                break;

            case DELETE_USER_THREAD_POSTS:

                $action_text = "{$lang['userspostsdeletedinthread']}: {$admin_log_entry['ENTRY']}";
                break;

            case DELETE_THREAD:

                $action_text = "{$lang['threaddeleted']}: {$admin_log_entry['ENTRY']}";
                break;

            case DELETE_ATTACHMENT:

                $action_text = "{$lang['deleteduserattachmentfrompost']}: {$admin_log_entry['ENTRY']}";
                break;

            case EDIT_FORUM_LINKS:

                $action_text = "{$lang['editedforumlinks']}";
                break;

            case APPROVED_POST:

                $action_text = "{$lang['approvedpost']}: {$admin_log_entry['ENTRY']}";
                break;

            case CREATE_USER_GROUP:

                $action_text = "{$lang['createdusergroup']}: {$admin_log_entry['ENTRY']}";
                break;

            case DELETE_USER_GROUP:

                $action_text = "{$lang['createdusergroup']}: {$admin_log_entry['ENTRY']}";
                break;

            case ADD_USER_TO_GROUP:

                $action_text = "{$lang['createdusergroup']}: {$admin_log_entry['ENTRY']}";
                break;

            default:

                $action_text = "{$lang['unknown']}";
                break;
        }

        echo "                    <td class=\"posthead\" align=\"left\">", $action_text, "</td>\n";
        echo "                  </tr>\n";

    }

}else {

    echo "                  <tr>\n";
    echo "                    <td class=\"posthead\" colspan=\"3\" align=\"left\">{$lang['adminlogempty']}</td>\n";
    echo "                  </tr>\n";
}

echo "              </table>\n";
echo "            </td>\n";
echo "          </tr>\n";
echo "        </table>\n";
echo "      </td>\n";
echo "    </tr>\n";
echo "    <tr>\n";
echo "      <td>&nbsp;</td>\n";
echo "    </tr>\n";
echo "    <tr>\n";
echo "      <td class=\"postbody\" align=\"center\">", page_links(get_request_uri(), $start, $admin_log_array['admin_log_count'], 20), "</td>\n";
echo "    </tr>\n";
echo "    <tr>\n";
echo "      <td>&nbsp;</td>\n";
echo "    </tr>\n";
echo "    <tr>\n";
echo "      <td align=\"center\">\n";
echo "        <form name=\"f_post\" action=\"admin_viewlog.php?webtag=$webtag\" method=\"post\" target=\"_self\">\n";
echo "          ", form_submit('clear',$lang['clearlog']), "\n";
echo "        </form>\n";
echo "      </td>";
echo "    </tr>\n";
echo "  </table>\n";
echo "</div>\n";

html_draw_bottom();

?>