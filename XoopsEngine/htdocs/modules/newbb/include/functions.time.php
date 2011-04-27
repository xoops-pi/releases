<?php
/**
 * Newbb module
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code 
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         newbb
 * @since           4.0
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @version         $Id: functions.time.php 2284 2008-10-12 03:45:46Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

/**
 * Function to convert UNIX time to formatted time string
 */
function newbb_formatTimestamp($time, $format = "c", $timeoffset = "")
{
    xoops_load("xoopslocal");
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.config.php";
    $newbbConfig = newbb_loadConfig();

    $format = strtolower($format);
    if ($format == "reg" || $format == "") {
        $format = "c";
    }
    if ( ($format == "custom" || $format == "c") && !empty($newbbConfig["formatTimestamp_custom"]) ) {
        $format = $newbbConfig["formatTimestamp_custom"];
    }
    
    return XoopsLocal::formatTimestamp($time, $format, $timeoffset);
}

function newbb_sinceSelectBox($selected = 100)
{
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.config.php";
    $newbbConfig = newbb_loadConfig();

    $select_array = explode(',', $newbbConfig['since_options']);
    $select_array = array_map('trim', $select_array);

    $forum_selection_since = '<select name="since">';
    foreach ($select_array as $since) {
        $forum_selection_since .= '<option value="' . $since . '"' . (($selected == $since) ? ' selected="selected"' : '') . '>';
        if ($since > 0) {
            $forum_selection_since .= sprintf(_MD_FROMLASTDAYS, $since);
        } else {
            $forum_selection_since .= sprintf(_MD_FROMLASTHOURS, abs($since));
        }
        $forum_selection_since .= '</option>';
    }
    $forum_selection_since .= '<option value="365"' . (($selected == 365) ? ' selected="selected"' : '') . '>' . _MD_THELASTYEAR . '</option>';
    $forum_selection_since .= '<option value="0"' . (($selected == 0) ? ' selected="selected"' : '') . '>' . _MD_BEGINNING . '</option>';
    $forum_selection_since .= '</select>';

    return $forum_selection_since;
}

function newbb_getSinceTime($since = 100)
{
    if ($since == 1000) return 0;
    if ($since > 0) return intval($since) * 24 * 3600;
    else return intval(abs($since)) * 3600;
}
?>