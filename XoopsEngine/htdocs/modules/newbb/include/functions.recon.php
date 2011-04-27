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
 * @version         $Id: functions.recon.php 2284 2008-10-12 03:45:46Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

function newbb_synchronization($type = "")
{
    switch($type) {
    case "rate":
    case "report":
    case "post":
    case "topic":
    case "forum":
    case "category":
    case "moderate":
    case "read":
        $type = array($type);
        $clean = $type;
        break;
    default:
        $type = null;
        $clean = array("category", "forum", "topic", "post", "report", "rate", "moderate", "readtopic", "readforum");
        break;
    }
    foreach ($clean as $item) {
        $handler =& xoops_getmodulehandler($item, "newbb");
        $handler->cleanOrphan();
        unset($handler);
    }
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.config.php";
    $newbbConfig = newbb_loadConfig();
    if (empty($type) || in_array("post", $type)):
        $post_handler =& xoops_getmodulehandler("post", "newbb");
        $expires = isset($newbbConfig["pending_expire"]) ? intval($newbbConfig["pending_expire"]) : 7;
        $post_handler->cleanExpires($expires * 24 * 3600);
    endif;
    if (empty($type) || in_array("topic", $type)):
        $topic_handler =& xoops_getmodulehandler("topic", "newbb");
        $expires = isset($newbbConfig["pending_expire"]) ? intval($newbbConfig["pending_expire"]) : 7;
        $topic_handler->cleanExpires($expires * 24 * 3600);
        //$topic_handler->synchronization();
    endif;
    /*
    if (empty($type) || in_array("forum", $type)):
        $forum_handler =& xoops_getmodulehandler("forum", "newbb");
        $forum_handler->synchronization();
    endif;
    */
    if (empty($type) || in_array("moderate", $type)) {
        $moderate_handler =& xoops_getmodulehandler("moderate", "newbb");
        $moderate_handler->clearGarbage();
    }
    if (empty($type) || in_array("read", $type)) {
        $read_handler =& xoops_getmodulehandler("readforum", "newbb");
        $read_handler->clearGarbage();
        //$read_handler->synchronization();
        $read_handler =& xoops_getmodulehandler("readtopic", "newbb");
        $read_handler->clearGarbage();
        //$read_handler->synchronization();
    }
    return true;
}

?>