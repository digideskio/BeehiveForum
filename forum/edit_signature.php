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

/* $Id: edit_signature.php,v 1.3 2004-01-26 20:11:11 decoyduck Exp $ */

// Compress the output
require_once("./include/gzipenc.inc.php");

// Enable the error handler
require_once("./include/errorhandler.inc.php");

//Check logged in status
require_once("./include/session.inc.php");
require_once("./include/header.inc.php");

if(!bh_session_check()){

    $uri = "./logon.php?final_uri=". urlencode(get_request_uri());
    header_redirect($uri);
}

if (bh_session_get_value('UID') == 0) {
    html_guest_error();
    exit;
}

require_once("./include/html.inc.php");
require_once("./include/user.inc.php");
require_once("./include/post.inc.php");
require_once("./include/fixhtml.inc.php");
require_once("./include/form.inc.php");
require_once("./include/header.inc.php");
require_once("./include/lang.inc.php");

if (isset($HTTP_POST_VARS['submit'])) {

    $valid = true;

    if (isset($HTTP_POST_VARS['sig_content']) && trim($HTTP_POST_VARS['sig_content']) != "") {
        $t_sig_content = _stripslashes(trim($HTTP_POST_VARS['sig_content']));
    }else {
        $t_sig_content = "";
    }

    if (isset($HTTP_POST_VARS['sig_html']) && $HTTP_POST_VARS['sig_html'] == "Y") {
        $t_sig_html = "Y";
    }else {
        $t_sig_html = "N";
    }
    
    $t_sig_content_check = preg_replace('/\&\#([0-9]+)\;/me', "chr('\\1')", rawurldecode($t_sig_content));

    if (preg_match("/<.+(src|background|codebase|background-image)(=|s?:s?).+getattachment.php.+>/ ", $t_sig_content_check) && $t_sig_html != "N") {
        $error_html.= "<h2>{$lang['notallowedembedattachmentsignature']}</h2>\n";
        $valid = false;
    }
    
    if ($valid) {

        // User's UID for updating with.

        $uid = bh_session_get_value('UID');

        // Check the signature code to see if it needs running through fix_html

        if ($t_sig_html == "Y") {
            $t_sig_content = fix_html($t_sig_content);
        }else {
            $t_sig_content = _stripslashes($t_sig_content);
        }

        // Update USER_SIG

        user_update_sig($uid, $t_sig_content, $t_sig_html);    
        
        // Reinitialize the User's Session to save them having to logout and back in

        bh_session_init($uid);

        // IIS bug prevents redirect at same time as setting cookies.

        if (isset($HTTP_SERVER_VARS['SERVER_SOFTWARE']) && !strstr($HTTP_SERVER_VARS['SERVER_SOFTWARE'], 'Microsoft-IIS')) {

            header_redirect("./edit_signature.php?updated=true");

        }else {

            html_draw_top();

            // Try a Javascript redirect
            echo "<script language=\"javascript\" type=\"text/javascript\">\n";
            echo "<!--\n";
            echo "document.location.href = './edit_signature.php?updated=true';\n";
            echo "//-->\n";
            echo "</script>";

            // If they're still here, Javascript's not working. Give up, give a link.
            echo "<div align=\"center\"><p>&nbsp;</p><p>&nbsp;</p>";
            echo "<p>{$lang['preferencesupdated']}</p>";

            form_quick_button("./edit_signature.php", $lang['continue'], "", "", "_top");

            html_draw_bottom();
            exit;
        }
    }
}

// Get the User's Signature

user_get_sig(bh_session_get_value('UID'), $user_sig['SIG_CONTENT'], $user_sig['SIG_HTML']);

// Start Output Here

html_draw_top();

echo "<h1>{$lang['editsignature']}</h1>\n";

// Any error messages to display?

if (!empty($error_html)) {
    echo $error_html;
}else if (isset($HTTP_GET_VARS['updated'])) {
    echo "<h2>{$lang['preferencesupdated']}</h2>\n";
}

echo "<br />\n";
echo "<div class=\"postbody\">\n";
echo "  <form name=\"prefs\" action=\"edit_signature.php\" method=\"post\" target=\"_self\">\n";
echo "    <table cellpadding=\"0\" cellspacing=\"0\">\n";
echo "      <tr>\n";
echo "        <td>\n";
echo "          <table class=\"box\">\n";
echo "            <tr>\n";
echo "              <td class=\"posthead\">\n";
echo "                <table class=\"posthead\" width=\"100%\">\n";
echo "                  <tr>\n";
echo "                    <td class=\"subhead\">{$lang['signature']}</td>\n";
echo "                  </tr>\n";
echo "                  <tr>\n";
echo "                    <td>", form_textarea("sig_content", (isset($t_sig_content) ? _htmlentities(_stripslashes($t_sig_content)) : _htmlentities(_stripslashes($user_sig['SIG_CONTENT']))), 4, 60), "</td>\n";
echo "                  </tr>\n";
echo "                  <tr>\n";
echo "                    <td align=\"right\">", form_checkbox("sig_html", "Y", $lang['containsHTML'], (isset($t_sig_html) && $t_sig_html == "Y") ? true : ($user_sig['SIG_HTML'] == "Y")), "</td>\n";
echo "                  </tr>\n";
echo "                </table>\n";
echo "              </td>\n";
echo "            </tr>\n";
echo "          </table>\n";
echo "        </td>\n";
echo "      </tr>\n";
echo "      <tr>\n";
echo "        <td align=\"center\"><p>", form_submit("submit", $lang['save']), "</p></td>\n";
echo "      </tr>\n";
echo "    </table>\n";
echo "  </form>\n";
echo "</div>\n";

html_draw_bottom();

?>