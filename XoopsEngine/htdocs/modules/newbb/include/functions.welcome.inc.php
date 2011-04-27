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
 * @version         $Id: functions.welcome.inc.php 2284 2008-10-12 03:45:46Z phppp $
 */
 
global $xoopsModule, $xoopsModuleConfig, $myts, $xoopsUser, $forum_obj;

if ( !defined('XOOPS_ROOT_PATH') || !is_object($forum_obj) || !is_object($xoopsUser) || !is_object($xoopsModule) ) { 
    return; 
}
    
    $forum_id = $forum_obj->getVar("forum_id");
    $post_handler =& xoops_getmodulehandler('post', 'newbb');
    $post_obj =& $post_handler->create();
    $post_obj->setVar('poster_ip', newbb_getIP());
    $post_obj->setVar('uid', $xoopsUser->getVar("uid"));
    $post_obj->setVar('approved', 1);
    $post_obj->setVar('forum_id', $forum_id);

    $subject = sprintf(_MD_WELCOME_SUBJECT, $xoopsUser->getVar('uname'));
    $post_obj->setVar('subject', $subject);
    $post_obj->setVar('dohtml', 1);
    $post_obj->setVar('dosmiley', 1);
    $post_obj->setVar('doxcode', 0);
    $post_obj->setVar('dobr', 1);
    $post_obj->setVar('icon', "");
    $post_obj->setVar('attachsig', 1);
    $post_obj->setVar('post_time', time());
    
    $message = sprintf(_MD_WELCOME_MESSAGE, $xoopsUser->getVar('uname')) . "\n\n";
    $message .= _PROFILE . ": <a href='" . XOOPS_URL . "/userinfo.php?uid=" . $xoopsUser->getVar('uid') . "' title='" . $xoopsUser->getVar('uname') . "'><strong>" . $xoopsUser->getVar('uname') . "</strong></a> ";
    $message .= " | <a href='" . XOOPS_URL . "/pmlite.php?send2=1&amp;to_userid=" . $xoopsUser->getVar('uid')."' title='" . _MD_PM . "'>" . _MD_PM . "</a>\n";
    $post_obj->setVar('post_text', $message);
    $post_id = $post_handler->insert($post_obj);

    if (!empty($xoopsModuleConfig['notification_enabled'])) {
        $tags = array();
        $tags['THREAD_NAME'] = $subject;
        $tags['THREAD_URL'] = XOOPS_URL . '/modules/' . $xoopsModule->getVar("dirname") . '/viewtopic.php?post_id=' . $post_id . '&amp;topic_id=' . $post_obj->getVar('topic_id').'&amp;forum=' . $forum_id;
        $tags['POST_URL'] = $tags['THREAD_URL'] . '#forumpost' . $post_id;
        include_once 'include/notification.inc.php';
        $forum_info = newbb_notify_iteminfo ('forum', $forum_id);
        $tags['FORUM_NAME'] = $forum_info['name'];
        $tags['FORUM_URL'] = $forum_info['url'];
        $notification_handler =& xoops_gethandler('notification');
        $notification_handler->triggerEvent('forum', $forum_id, 'new_thread', $tags);
        $notification_handler->triggerEvent('global', 0, 'new_post', $tags);
        $notification_handler->triggerEvent('forum', $forum_id, 'new_post', $tags);
        $tags['POST_CONTENT'] = $myts->stripSlashesGPC($message);
        $tags['POST_NAME'] = $myts->stripSlashesGPC($subject);
        $notification_handler->triggerEvent('global', 0, 'new_fullpost', $tags);
    }
?>