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
 * @version         $Id: functions.read.php 2284 2008-10-12 03:45:46Z phppp $
 */

function newbb_setRead($type, $item_id, $post_id, $uid = null)
{
    $read_handler =& xoops_getmodulehandler("read" . $type, "newbb");
    return $read_handler->setRead($item_id, $post_id, $uid);
}

function newbb_getRead($type, $item_id, $uid = null)
{
    $read_handler =& xoops_getmodulehandler("read" . $type, "newbb");
    return $read_handler->getRead($item_id, $uid);
}

function newbb_setRead_forum($status = 0, $uid = null)
{
    $read_handler =& xoops_getmodulehandler("readforum", "newbb");
    return $read_handler->setRead_items($status, $uid);
}

function newbb_setRead_topic($status = 0, $forum_id = 0, $uid = null)
{
    $read_handler =& xoops_getmodulehandler("readtopic", "newbb");
    return $read_handler->setRead_items($status, $forum_id, $uid);
}

function newbb_isRead($type, &$items, $uid = null)
{
    $read_handler =& xoops_getmodulehandler("read" . $type, "newbb");
    return $read_handler->isRead_items($items, $uid);
}
?>