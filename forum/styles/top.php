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

/* $Id$ */

// Set the default timezone
date_default_timezone_set('UTC');

// Constant to define where the include files are
define("BH_INCLUDE_PATH", "../include/");

// Path to the root Beehive install for CSS and JS
define("BH_FORUM_PATH", "../");

// Server checking functions
include_once(BH_INCLUDE_PATH. "server.inc.php");

// Disable PHP's register_globals
unregister_globals();

// Disable caching if on AOL
cache_disable_aol();

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

// Fetch Forum Settings

$forum_settings = forum_get_settings();

// Fetch Global Forum Settings

$forum_global_settings = forum_get_global_settings();

include_once(BH_INCLUDE_PATH. "html.inc.php");

// Get Webtag

$webtag = get_webtag();

// Check we're logged in correctly

$user_sess = bh_session_check(false);

// Check to see if the user is banned.

if (bh_session_user_banned()) {

    html_user_banned();
    exit;
}

// Check to see if the user has been approved.

if (!bh_session_user_approved()) {

    html_user_require_approval();
    exit;
}

// Check we have a webtag

if (!forum_check_webtag_available($webtag)) {
    $request_uri = rawurlencode(get_request_uri(false));
    header_redirect("forums.php?webtag_error&final_uri=$request_uri");
}

html_draw_top('class=top_banner');

$top_frame_name = html_get_top_frame_name();

$forum_name = forum_get_setting('forum_name', false, 'A Beehive Forum');

echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n";
echo "  <tr>\n";
echo "    <td align=\"left\" valign=\"middle\"><a href=\"http://www.beehiveforum.net/\" target=\"_blank\"><img src=\"../images/beehive_logo.png\" border=\"0\" alt=\"Beehive Logo\" /></a></td>\n";
echo "    <td align=\"right\" valign=\"middle\"><a href=\"http://sourceforge.net\" target=\"_top\"><img src=\"http://sourceforge.net/sflogo.php?group_id=50772&amp;type=2\" width=\"125\" height=\"37\" border=\"0\" alt=\"SourceForge.net Logo\" /></a></td>\n";
echo "  </tr>\n";
echo "</table>\n";

html_draw_bottom();

?>