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

/* $Id: form.inc.php,v 1.40 2004-01-13 16:41:40 decoyduck Exp $ */

// form.inc.php : form item functions

require_once("./include/db.inc.php");
require_once("./include/lang.inc.php");

// Create a form field

function form_field($name, $value = false, $width = false, $maxchars = false, $type = "text", $custom_html = false)
{
    global $lang;

    $html = "<input type=\"$type\" name=\"$name\" class=\"bhinputtext\" autocomplete=\"off\" value=\"$value\" ";

    if ($custom_html) {
        $custom_html = trim($custom_html);
        $html.= "$custom_html ";
    }

    if ($width) {
        $width = (int)trim($width);
        $html.= "size=\"$width\" ";
    }

    if ($maxchars) {
        $maxchars = (int)trim($maxchars);
        $html.= "maxlength=\"$maxchars\" ";
    }

    $html.= "dir=\"{$lang['_textdir']}\" />";
    return $html;
}

// Creates a text input field

function form_input_text($name, $value = false, $width = false, $maxchars = false, $custom_html = false)
{
    return form_field($name, $value, $width, $maxchars, "text", $custom_html);
}

// Creates a password input field

function form_input_password($name, $value = false, $width = false, $maxchars = false, $custom_html = false)
{
    return form_field($name, $value, $width, $maxchars, "password", $custom_html);
}

// Creates a hidden form field

function form_input_hidden($name, $value = false, $custom_html = false)
{
    return form_field($name, $value, 0, 0, "hidden", $custom_html);
}

// Create a textarea input field

function form_textarea($name, $value = false, $rows = false, $cols = false, $wrap = "virtual", $custom_html = false)
{
    global $lang;

    $html = "<textarea name=\"$name\" class=\"bhtextarea\" autocomplete=\"off\" ";

    if ($custom_html) {
        $custom_html = trim($custom_html);
        $html.= "$custom_html ";
    }

    if ($rows) {
        $rows = (int)trim($rows);
        $html.= "rows=\"$rows\" ";
    }

    if ($cols) {
        $cols = (int)trim($cols);
        $html.= "cols=\"$cols\" ";
    }

    $html.= "dir=\"{$lang['_textdir']}\" autocomplete=\"off\">$value</textarea>";
    return $html;
}

// Creates a dropdown with values from database

function form_dropdown_sql($name, $sql, $default, $custom_html = false)
{
    global $lang;

    $html = "<select name=\"$name\" class=\"bhselect\" autocomplete=\"off\" ";
    $html.= "dir=\"{$lang['_textdir']}\" ";

    if ($custom_html) {
        $custom_html = trim($custom_html);
        $html.= "$custom_html ";
    }

    $html.= ">";

    $db_form_dropdown_sql = db_connect();
    $result = db_query($sql, $db_form_dropdown_sql);

    while ($row = db_fetch_array($result)) {
        $sel = ($row[0] == $default) ? " selected=\"selected\"" : "";
        if ($row[1]) {
            $html.= "<option value=\"". _stripslashes($row[0]). "\"$sel>". _stripslashes($row[1]). "</option>";
        }else {
            $html.= "<option$sel>". _stripslashes($row[0]). "</option>";
        }
    }

    $html.= "</select>";
    return $html;
}

// Creates a dropdown with values from array(s)

function form_dropdown_array($name, $value, $label, $default = false, $custom_html = false)
{
    global $lang;

    $html = "<select name=\"$name\" class=\"bhselect\" autocomplete=\"off\" ";
    $html.= "dir=\"{$lang['_textdir']}\" ";

    if ($custom_html) {
        $custom_html = trim($custom_html);
        $html.= "$custom_html ";
    }

    $html.= ">";

    for ($i = 0; $i < count($value); $i++) {
        $sel = ($value[$i] == $default) ? " selected=\"selected\"" : "";
        if (isset($label[$i])) {
            $html.= "<option value=\"". $value[$i]. "\"$sel>". $label[$i]. "</option>";
        }else {
            $html.= "<option$sel>". $value[$i]. "</option>";
        }
    }

    $html.= "</select>";
    return $html;
}

// Creates a checkbox field

function form_checkbox($name, $value, $text, $checked = false, $custom_html = false)
{
    $html = "<span class=\"bhinputcheckbox\">";
    $html.= "<input type=\"checkbox\" name=\"$name\" value=\"$value\" ";
    $html.= "autocomplete=\"off\"";
    
    if ($checked) $html.= " checked=\"checked\"";

    if ($custom_html) {
        $custom_html = trim($custom_html);
        $html.= " $custom_html ";
    }

    $html.= "/>$text</span>";
    return $html;
}

// Create a radio field

function form_radio($name, $value, $text, $checked = false, $custom_html = false)
{
    $html = "<span class=\"bhinputradio\">";
    $html.= "<input type=\"radio\" name=\"$name\" value=\"$value\" ";
    $html.= "autocomplete=\"off\"";

    if ($checked) $html.= " checked=\"checked\"";

    if ($custom_html) {
        $custom_html = trim($custom_html);
        $html.= " $custom_html ";
    }

    $html.= "/>$text</span>";
    return $html;
}

// Create an array of radio fields.

function form_radio_array($name, $value, $text, $checked = false, $custom_html = false)
{
    for ($i = 0; $i < count($value); $i++) {
        if (isset($html)) {
            $html.= form_radio($name, $value[$i], $text[$i], ($checked == $value[$i]), $custom_html);
        }else {
            $html = form_radio($name, $value[$i], $text[$i], ($checked == $value[$i]), $custom_html);
        }
    }

    return $html;
}

// Creates a form submit button

function form_submit($name = "submit", $value = "Submit", $custom_html = false, $class = "button")
{
    $html = "<input type=\"submit\" name=\"$name\" value=\"$value\" ";
    $html.= "autocomplete=\"off\" class=\"$class\" ";

    if ($custom_html) {
        $custom_html = trim($custom_html);
        $html.= "$custom_html ";
    }

    $html.= "/>";
    return $html;
}

// Creates a form reset button

function form_reset($name = "reset", $value = "Reset", $custom_html = false, $class = "button")
{
    $html = "<input type=\"reset\" name=\"$name\" value=\"$value\" ";
    $html.= "autocomplete=\"off\" class=\"$class\" ";

    if ($custom_html) {
        $custom_html = trim($custom_html);
        $html.= "$custom_html ";
    }

    $html.= "/>";
    return $html;
}

// Creates a button with custom HTML, for onclick methods, etc.

function form_button($name, $value, $custom_html, $class="button")
{
    $html = "<input type=\"button\" name=\"$name\" value=\"$value\" ";
    $html.= "autocomplete=\"off\" class=\"$class\" ";

    if ($custom_html) {
        $custom_html = trim($custom_html);
        $html.= "$custom_html ";
    }

    $html.= "/>";
    return $html;
}

// create a form just to be a link button
// $var and $value can optionally be single-dimensional arrays
// containing names and values to be used for hidden form
// fields. Multi-dimensional arrays will be ignored.

function form_quick_button($href, $label, $var = false, $value = false, $target = "_self")
{
    echo "<form name=\"f_quickbutton\" method=\"get\" action=\"$href\" ";
    echo "target=\"$target\" autocomplete=\"off\">";

    if ($var) {
        if (is_array($var)) {
            for ($i = 0; $i < count($var); $i++) {
                if (!is_array($var[$i])) {
                    echo form_input_hidden($var[$i], $value[$i]);
                }
            }
        }else {
            echo form_input_hidden($var, $value);
        }
    }

    echo form_submit("submit", $label);
    echo "</form>";
}

// create the date of birth dropdowns for prefs. $show_blank controls whether to show
// a blank option in each box for backwards compatibility with 0.3 and below,
// where the DOB was not required information

function form_dob_dropdowns($dob_year, $dob_month, $dob_day, $show_blank = true)
{
    global $lang;

    $birthday_days = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10',
                           '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
                           '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
                           '31');

    $birthday_months = array($lang['jan'], $lang['feb'], $lang['mar'], $lang['apr'],
                             $lang['may'], $lang['jun'], $lang['jul'], $lang['aug'],
                             $lang['sep'], $lang['oct'], $lang['nov'], $lang['dec']);

    $birthday_years = range(1900, date('Y', mktime()));

    if ($show_blank) {
        $birthday_days_values = range(0, 31);
        $birthday_days = array_merge(' ', $birthday_days);
        $birthday_months_values = range(0, 12);
        $birthday_months = array_merge(' ', $birthday_months);
        $birthday_years_values = array_merge(0, $birthday_years);
        $birthday_years = array_merge(' ', $birthday_years);
    }else {
        $birthday_days_values = range(1, 31);
        $birthday_months_values = range(1, 12);
        $birthday_years_values = $birthday_years;
    }

    $output = form_dropdown_array("dob_day", $birthday_days_values, $birthday_days, $dob_day);
    $output.= "&nbsp;";
    $output.= form_dropdown_array("dob_month", $birthday_months_values, $birthday_months, $dob_month);
    $output.= "&nbsp;";
    $output.= form_dropdown_array("dob_year", $birthday_years_values, $birthday_years, $dob_year);

    return $output;
}

// Creates an array of hidden form fields.
// Is multi-dimensional array safe.

function form_input_hidden_array($name, $value)
{
    if (is_array($value)) {
        foreach ($value as $array_key => $array_value) {
            if (isset($return)) {
                $return.= form_input_hidden_array("{$name}[{$array_key}]", $array_value);
            }else {
                $return = form_input_hidden_array("{$name}[{$array_key}]", $array_value);
            }
        }
    }else {
        if (isset($return)) {
            $return.= form_input_hidden($name, _stripslashes($value));
        }else {
            $return = form_input_hidden($name, _stripslashes($value));
        }
    }

    return $return;
}

// Creates a dropdown selectors for dates
// including seperate fields for day, month and year.

function form_date_dropdowns($year = 0, $month = 0, $day = 0, $prefix = false)
{
    global $lang;

    $days   = array_merge(" ", range(1,31));
    $months = array(" ", $lang['jan'], $lang['feb'], $lang['mar'], $lang['apr'],
                    $lang['may'], $lang['jun'], $lang['jul'], $lang['aug'],
                    $lang['sep'], $lang['oct'], $lang['nov'], $lang['dec']);

    // the end of 2037 is more or less the maximum time that
    // can be represented as a UNIX timestamp currently

    $years  = array_merge(" ", range(date('Y'), 2037));
    $years_values = array_merge(0, range(date('Y'), 2037));

    $output = form_dropdown_array("{$prefix}day", range(0,31), $days, $day);
    $output.= "&nbsp;";
    $output.= form_dropdown_array("{$prefix}month", range(0, 12), $months, $month);
    $output.= "&nbsp;";
    $output.= form_dropdown_array("{$prefix}year", $years_values, $years, $year);

    return $output;
}

?>