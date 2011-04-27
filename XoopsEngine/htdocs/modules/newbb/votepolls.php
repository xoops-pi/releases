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
 * @version         $Id: votepolls.php 2175 2008-09-23 14:07:03Z phppp $
 */

include "header.php";

include_once XOOPS_ROOT_PATH . "/modules/xoopspoll/include/constants.php";
include_once XOOPS_ROOT_PATH . "/modules/xoopspoll/class/xoopspoll.php";
include_once XOOPS_ROOT_PATH . "/modules/xoopspoll/class/xoopspolloption.php";
include_once XOOPS_ROOT_PATH . "/modules/xoopspoll/class/xoopspolllog.php";
include_once XOOPS_ROOT_PATH . "/modules/xoopspoll/class/xoopspollrenderer.php";

if ( !empty($_POST['poll_id']) ) {
    $poll_id = intval($_POST['poll_id']);
} elseif (!empty($_GET['poll_id'])) {
    $poll_id = intval($_GET['poll_id']);
}
if ( !empty($_POST['topic_id']) ) {
    $topic_id = intval($_POST['topic_id']);
} elseif (!empty($_GET['topic_id'])) {
    $topic_id = intval($_GET['topic_id']);
}
if ( !empty($_POST['forum']) ) {
    $forum = intval($_POST['forum']);
} elseif (!empty($_GET['forum'])) {
    $forum = intval($_GET['forum']);
}

$topic_handler =& xoops_getmodulehandler('topic', 'newbb');
$topic_obj =& $topic_handler->get($topic_id);
if (!$topic_handler->getPermission($topic_obj->getVar("forum_id"), $topic_obj->getVar('topic_status'), "vote")) {
    redirect_header("javascript:history.go(-1);", 2, _NOPERM);
}

if ( !empty($_POST['option_id']) ) {
    $mail_author = false;
    $poll = new XoopsPoll($poll_id);

    if ( is_object($xoopsUser) ) {
        if ( XoopsPollLog::hasVoted($poll_id, $_SERVER['REMOTE_ADDR'], $xoopsUser->getVar("uid")) ) {
            $msg = _PL_ALREADYVOTED;
            setcookie("bb_polls[$poll_id]", 1);
        } else {
            $poll->vote($_POST['option_id'], '', $xoopsUser->getVar("uid"));
            $poll->updateCount();
            $msg = _PL_THANKSFORVOTE;
            setcookie("bb_polls[$poll_id]", 1);
        }
    } else {
        if ( XoopsPollLog::hasVoted($poll_id, $_SERVER['REMOTE_ADDR']) ) {
            $msg = _PL_ALREADYVOTED;
            setcookie("bb_polls[$poll_id]", 1);
        } else {
            $poll->vote($_POST['option_id'], $_SERVER['REMOTE_ADDR']);
            $poll->updateCount();
            $msg = _PL_THANKSFORVOTE;
            setcookie("bb_polls[$poll_id]", 1);
        }
    }

    redirect_header("viewtopic.php?topic_id={$topic_id}&amp;forum={$forum}&amp;poll_id={$poll_id}&amp;pollresult=1", 1, $msg);
    exit();
}
redirect_header("viewtopic.php?topic_id={$topic_id}&amp;forum={$forum}", 1, "You must choose an option !!");
?>