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
 * @version         $Id: functions.welcome.php 2284 2008-10-12 03:45:46Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

function newbb_welcome()
{
    global $xoopsModule, $xoopsModuleConfig, $myts, $xoopsUser, $forum_obj;
    //$xoopsModuleConfig["welcome_forum"] = 1;
    $forum_handler =& xoops_getmodulehandler('forum', 'newbb');
    $forum_obj = $forum_handler->get($xoopsModuleConfig["welcome_forum"]);
    if (!$forum_handler->getPermission($forum_obj)) {
        unset($forum_obj);
        return false;
    }
    
    include dirname(__FILE__) . "/functions.welcome.inc.php";
    unset($forum_obj);
    return $ret;
}
newbb_welcome();
?>