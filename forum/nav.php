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

// Navigation strip

require_once("./include/constants.inc.php");
require_once("./include/header.inc.php");
require_once("./include/html.inc.php");

header_no_cache();

html_draw_top('Nav', 'navpage');

?>
        <a href="start.php" target="main">Start</a>&nbsp|&nbsp;
        <a href="discussion.php" target="main">Messages</a>&nbsp|&nbsp;
<?php

if ($HTTP_COOKIE_VARS['bh_sess_uid'] > 0) {

?>
        <a href="preferences.php" target="main">Preferences</a>&nbsp|&nbsp;
        <a href="profile.php" target="main">Profile</a>&nbsp|&nbsp;
<?php

}

if(isset($HTTP_COOKIE_VARS['bh_sess_ustatus']) && ($HTTP_COOKIE_VARS['bh_sess_ustatus'] & USER_PERM_SOLDIER)) {

?>
        <a href="admin.php" target="main">Admin</a>&nbsp|&nbsp;
<?php

}
?>

        <a href="logout.php" target="main">Logout</a>
    </body>
</html>
