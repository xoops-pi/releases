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
 * @version         $Id: delete.php 2175 2008-09-23 14:07:03Z phppp $
 */

include 'header.php';

$ok = isset($_POST['ok']) ? intval($_POST['ok']) : 0;
foreach (array('forum', 'topic_id', 'post_id', 'order', 'pid', 'act') as $getint) {
    ${$getint} = isset($_POST[$getint]) ? intval($_POST[$getint]) : 0;
}
foreach (array('forum', 'topic_id', 'post_id', 'order', 'pid', 'act') as $getint) {
    ${$getint} = (${$getint}) ? ${$getint} : (isset($_GET[$getint]) ? intval($_GET[$getint]) : 0);
}
$viewmode = (isset($_GET['viewmode']) && $_GET['viewmode'] != 'flat') ? 'thread' : 'flat';
$viewmode = $viewmode ? $viewmode: (isset($_POST['viewmode']) ? $_POST['viewmode'] : 'flat');

$forum_handler =& xoops_getmodulehandler('forum', 'newbb');
$topic_handler =& xoops_getmodulehandler('topic', 'newbb');
$post_handler =& xoops_getmodulehandler('post', 'newbb');

if ( !empty($post_id) ) {
    $topic =& $topic_handler->getByPost($post_id);
} else {
    $topic =& $topic_handler->get($topic_id);
}
$topic_id = $topic->getVar('topic_id');
if ( !$topic_id ) {
    $redirect = empty($forum) ? "index.php" : 'viewforum.php?forum=' . $forum;
    redirect_header($redirect, 2, _MD_ERRORTOPIC);
    exit();
}

$forum = $topic->getVar('forum_id');
$forum_obj =& $forum_handler->get($forum);
if (!$forum_handler->getPermission($forum_obj)) {
    redirect_header("index.php", 2, _MD_NORIGHTTOACCESS);
    exit();
}

$isadmin = newbb_isAdmin($forum_obj);
$uid = is_object($xoopsUser) ? $xoopsUser->getVar('uid') : 0;

$post_obj =& $post_handler->get($post_id);
$topic_status = $topic->getVar('topic_status');
if ( $topic_handler->getPermission($topic->getVar("forum_id"), $topic_status, 'delete') && ( $isadmin || $post_obj->checkIdentity() )) {
} else {
    redirect_header("viewtopic.php?topic_id={$topic_id}&amp;order={$order}&amp;viewmode={$viewmode}&amp;pid={$pid}&amp;forum={$forum}", 2, _MD_DELNOTALLOWED);
    exit();
}

if (!$isadmin && !$post_obj->checkTimelimit('delete_timelimit')) {
    redirect_header("viewtopic.php?forum={$forum}&amp;topic_id={$topic_id}&amp;post_id={$post_id}&amp;order={$order}&amp;viewmode={$viewmode}&amp;pid={$pid}", 2, _MD_TIMEISUPDEL);
    exit();
}

if ($xoopsModuleConfig['wol_enabled']) {
    $online_handler =& xoops_getmodulehandler('online', 'newbb');
    $online_handler->init($forum_obj);
}

if ( $ok ) {
    $isDeleteOne = (1 == $ok) ? true : false;
    $post_handler->delete($post_obj, $isDeleteOne);
    $forum_handler->synchronization($forum);
    $topic_handler->synchronization($topic_id);

    $post_obj->loadFilters("delete");
    if ( $isDeleteOne ) {
        redirect_header("viewtopic.php?topic_id={$topic_id}&amp;order={$order}&amp;viewmode={$viewmode}&amp;pid={$pid}&amp;forum={$forum}", 2, _MD_POSTDELETED);
    } else {
        redirect_header("viewforum.php?forum={$forum}", 2, _MD_POSTSDELETED);
    }
    exit();

} else {
    include XOOPS_ROOT_PATH . "/header.php";
    xoops_confirm(array('post_id' => $post_id, 'viewmode' => $viewmode, 'order' => $order, 'forum' => $forum, 'topic_id' => $topic_id, 'ok' => 1), 'delete.php', _MD_DEL_ONE);
    if ($isadmin) {
        xoops_confirm(array('post_id' => $post_id, 'viewmode' => $viewmode, 'order' => $order, 'forum' => $forum, 'topic_id' => $topic_id, 'ok' => 99), 'delete.php', _MD_DEL_RELATED);
    }
    include XOOPS_ROOT_PATH . '/footer.php';
}
?>