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
 * @version         $Id: notification.inc.php 2170 2008-09-23 13:40:23Z phppp $
 */
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}
require_once(XOOPS_ROOT_PATH.'/modules/newbb/include/functions.php');
if ( !defined('NEWBB_NOTIFY_ITEMINFO') ) {
define('NEWBB_NOTIFY_ITEMINFO', 1);

function newbb_notify_iteminfo($category, $item_id)
{
    $module_handler =& xoops_gethandler('module');
    $module =& $module_handler->getByDirname('newbb');

    if ($category=='global') {
        $item['name'] = '';
        $item['url'] = '';
        return $item;
    }
    $item_id = intval($item_id);

    global $xoopsDB;
    if ($category=='forum') {
        // Assume we have a valid forum id
        $sql = 'SELECT forum_name FROM ' . $xoopsDB->prefix('bb_forums') . ' WHERE forum_id = '.$item_id;
        if (!$result = $xoopsDB->query($sql)) {
              redirect_header("index.php", 2, _MD_ERRORFORUM);
            exit();
        }
        $result_array = $xoopsDB->fetchArray($result);
        $item['name'] = $result_array['forum_name'];
        $item['url'] = XOOPS_URL . '/modules/' . $module->getVar('dirname') . '/viewforum.php?forum=' . $item_id;
        return $item;
    }

    if ($category=='thread') {
        // Assume we have a valid topid id
        $sql = 'SELECT t.topic_title,f.forum_id,f.forum_name FROM '.$xoopsDB->prefix('bb_topics') . ' t, ' . $xoopsDB->prefix('bb_forums') . ' f WHERE t.forum_id = f.forum_id AND t.topic_id = '. $item_id . ' limit 1';
        if (!$result = $xoopsDB->query($sql)) {
              redirect_header("index.php", 2, _MD_ERROROCCURED);
            exit();
        }
        $result_array = $xoopsDB->fetchArray($result);
        $item['name'] = $result_array['topic_title'];
        $item['url'] = XOOPS_URL . '/modules/' . $module->getVar('dirname') . '/viewtopic.php?forum=' . $result_array['forum_id'] . '&topic_id=' . $item_id;
        return $item;
    }

    if ($category=='post') {
        // Assume we have a valid post id
        $sql = 'SELECT subject,topic_id,forum_id FROM ' . $xoopsDB->prefix('bb_posts') . ' WHERE post_id = ' . $item_id . ' LIMIT 1';
        if (!$result = $xoopsDB->query($sql)) {
              redirect_header("index.php", 2, _MD_ERROROCCURED);
            exit();
        }
        $result_array = $xoopsDB->fetchArray($result);
        $item['name'] = $result_array['subject'];
        $item['url'] = XOOPS_URL . '/modules/' . $module->getVar('dirname') . '/viewtopic.php?forum= ' . $result_array['forum_id'] . '&amp;topic_id=' . $result_array['topic_id'] . '#forumpost' . $item_id;
        return $item;
    }
}
}
?>
