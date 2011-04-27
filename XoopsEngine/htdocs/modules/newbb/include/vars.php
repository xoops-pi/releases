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
 * @version         $Id: vars.php 2170 2008-09-23 13:40:23Z phppp $
 */
 
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}
require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.session.php";

// NewBB cookie structure
/* NewBB cookie storage
    Long term cookie: (configurable, generally one month)
        LV - Last Visit
        M - Menu mode
        V - View mode
        G - Toggle
    Short term cookie: (same as session life time)
        ST - Stored Topic IDs for mark
        LP - Last Post
        LF - Forum Last view
        LT - Topic Last read
        LVT - Last Visit Temp
*/

/* -- Cookie settings -- */
$forumCookie['domain'] = "";
$forumCookie['path'] = "/";
$forumCookie['secure'] = false;
$forumCookie['expire'] = time() + 3600 * 24 * 30; // one month
$forumCookie['prefix'] = 'newbb_' . ((is_object($xoopsUser)) ? $xoopsUser->getVar('uid') : 0);

// set LastVisitTemp cookie, which only gets the time from the LastVisit cookie if it does not exist yet
// otherwise, it gets the time from the LastVisitTemp cookie
$last_visit = newbb_getsession("LV");
$last_visit = ($last_visit)? $last_visit : newbb_getcookie("LV");
$last_visit = ($last_visit) ? $last_visit : time();


// update LastVisit cookie.
newbb_setcookie("LV", time(), $forumCookie['expire']); // set cookie life time to one month
newbb_setsession("LV", $last_visit);

// include customized variables
if ( is_object($GLOBALS["xoopsModule"]) && "newbb" == $GLOBALS["xoopsModule"]->getVar("dirname", "n") ) {
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.config.php";
    $GLOBALS["xoopsModuleConfig"] = newbb_loadConfig();
}

?>