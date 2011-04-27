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
 * @version         $Id: functions.session.php 2284 2008-10-12 03:45:46Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

/*
 * Currently the newbb session/cookie handlers are limited to:
 * -- one dimension
 * -- "," and "|" are preserved
 *
 */
function newbb_setsession($name, $string = '')
{
    if (is_array($string)) {
        $value = array();
        foreach ($string as $key => $val) {
            $value[]=$key . "|" . $val;
        }
        $string = implode(",", $value);
    }
    $_SESSION['newbb_' . $name] = $string;
}

function newbb_getsession($name, $isArray = false)
{
    $value = !empty($_SESSION['newbb_' . $name]) ? $_SESSION['newbb_' . $name] : false;
    if ($isArray) {
        $_value = ($value) ? explode(",", $value) : array();
        $value = array();
        foreach ($_value as $string) {
            $key = substr($string, 0, strpos($string, "|"));
            $val = substr($string, (strpos($string, "|") + 1));
            $value[$key] = $val;
        }
        unset($_value);
    }
    return $value;
}

function newbb_setcookie($name, $string = '', $expire = 0)
{
    global $forumCookie;
    if (is_array($string)) {
        $value = array();
        foreach ($string as $key => $val) {
            $value[] = $key ."|" . $val;
        }
        $string = implode(",", $value);
    }
    setcookie($forumCookie['prefix'] . $name, $string, intval($expire), $forumCookie['path'], $forumCookie['domain'], $forumCookie['secure']);
}

function newbb_getcookie($name, $isArray = false)
{
    global $forumCookie;
    $value = !empty($_COOKIE[$forumCookie['prefix'] . $name]) ? $_COOKIE[$forumCookie['prefix'] . $name] : null;
    if ($isArray) {
        $_value = ($value) ? explode(",", $value) : array();
        $value = array();
        foreach ($_value as $string) {
            $sep = strpos($string, "|");
            if ($sep === false) {
                $value[] = $string;
            } else {
                $key = substr($string, 0, $sep);
                $val = substr($string, ($sep + 1));
                $value[$key] = $val;
            }
        }
        unset($_value);
    }
    return $value;
}
?>