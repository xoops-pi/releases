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
 * @version         $Id: header.php 2175 2008-09-23 14:07:03Z phppp $
 */

include_once '../../mainfile.php';
include_once XOOPS_ROOT_PATH . "/modules/" . $xoopsModule->getVar("dirname", "n") . "/include/vars.php";
require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.user.php";

//require_once XOOPS_ROOT_PATH."/Frameworks/textsanitizer/module.textsanitizer.php";
$myts =& MyTextSanitizer::getInstance();

// menumode cookie
if (isset($_GET['menumode'])) {
    $menumode = intval($_GET['menumode']);
    newbb_setcookie("M", $menumode, $forumCookie['expire']);
} else {
    $cookie_M = intval(newbb_getcookie("M"));
    $menumode = (!isset($valid_menumodes[$cookie_M])) ? $xoopsModuleConfig['menu_mode'] : $cookie_M;
}

$menumode_other = array();
$menu_url = htmlSpecialChars(preg_replace("/&menumode=[^&]/", "", $_SERVER[ 'REQUEST_URI' ]));
$menu_url .= ( false === strpos($menu_url, "?") ) ? "?menumode=" : "&amp;menumode=";
foreach ($xoopsModuleConfig["valid_menumodes"] as $key => $val) {
    if ($key != $menumode) $menumode_other[] = array("title" => $val, "link" => $menu_url . $key);
}

$newbb_module_header = '';
$newbb_module_header .= '<link rel="alternate" type="application/rss+xml" title="' . $xoopsModule->getVar("name") . '" href="' . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname', "n") . '/rss.php" />';
$newbb_module_header .= '
    <link rel="stylesheet" type="text/css" href="templates/style.css" />
    <script type="text/javascript">var toggle_cookie="' . $forumCookie['prefix'] . 'G'.'";</script>
    <script src="include/js/newbb_toggle.js" type="text/javascript"></script>
    ';
    
if ($menumode == 2) {
    $newbb_module_header .= '
    <link rel="stylesheet" type="text/css" href="templates/newbb_menu_hover.css" />
    <style type="text/css">body { behavior:url("include/newbb.htc"); }</style>
    ';
}

if ($menumode == 1) {
    $newbb_module_header .= '
    <link rel="stylesheet" type="text/css" href="templates/newbb_menu_click.css" />
    <script src="include/js/newbb_menu_click.js" type="text/javascript"></script>
    ';
}

$xoops_module_header = $newbb_module_header; // for cache hack

if (!empty($xoopsModuleConfig["welcome_forum"]) && is_object($xoopsUser) && !$xoopsUser->getVar('posts')) {
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.welcome.php";
}

//$GLOBALS['xoopsOption']['noblock'] = true;
?>