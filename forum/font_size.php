<?php

/*======================================================================
Copyright Project Beehive Forum 2002

This file is part of Beehive Forum.

Beehive Forum is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
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

// Bootstrap
require_once 'boot.php';

// Includes required by this page.
require_once BH_INCLUDE_PATH. 'cache.inc.php';
require_once BH_INCLUDE_PATH. 'constants.inc.php';
require_once BH_INCLUDE_PATH. 'header.inc.php';
require_once BH_INCLUDE_PATH. 'html.inc.php';
require_once BH_INCLUDE_PATH. 'lang.inc.php';
require_once BH_INCLUDE_PATH. 'logon.inc.php';
require_once BH_INCLUDE_PATH. 'session.inc.php';

// Check we're logged in correctly
if (!session::logged_in()) {
    html_guest_error();
}

// Guests can't do different font sizes.
if (!session::logged_in()) exit;

// User's UID
$uid = session::get_value('UID');

// User's font size.
if (($font_size = session::get_value('FONT_SIZE')) === false) {
    $font_size = 10;
}

// Make sure the font size is positive and an integer.
$font_size = floor(abs($font_size));

// Output in text/css.
header("Content-Type: text/css");

// Check the cache
cache_check_etag(md5(sprintf("%s-%s-%s", session_id(), $font_size, $uid)));

// Check the user's font size.
if ($font_size < 5) $font_size = 5;
if ($font_size > 15) $font_size = 15;

// Array of different font sizes
$css_selectors = array(
    'body' => 0.8, 
    '.navpage' => 0.65
);

// Output the CSS
foreach ($css_selectors as $css_selector => $css_font_ratio) {
    printf("%s {\n    font-size: %d%%;\n}\n\n", $css_selector, $font_size * $css_font_ratio * 10);
}

?>