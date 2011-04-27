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
 * @version         $Id: ratethread.php 2175 2008-09-23 14:07:03Z phppp $
 */

include 'header.php';

$ratinguser = is_object($xoopsUser) ? $xoopsUser -> getVar('uid') : 0;
$anonwaitdays = 1;
$ip = newbb_getIP(true);
foreach (array("topic_id", "rate", "forum") as $var) {
    ${$var} = isset($_POST[$var]) ? intval($_POST[$var]) : (isset($_GET[$var]) ? intval($_GET[$var]) : 0);
}

$topic_handler =& xoops_getmodulehandler('topic', 'newbb');
$topic_obj =& $topic_handler->get($topic_id);
if (!$topic_handler->getPermission($topic_obj->getVar("forum_id"), $topic_obj->getVar('topic_status'), "post")
    &&
    !$topic_handler->getPermission($topic_obj->getVar("forum_id"), $topic_obj->getVar('topic_status'), "reply")
) {
    redirect_header("javascript:history.go(-1);", 2, _NOPERM);
}

if (empty($rate)) {
    redirect_header("viewtopic.php?topic_id=" . $topic_id."&amp;forum=" . $forum, 4, _MD_NOVOTERATE);
    exit();
}
$rate_handler =& xoops_getmodulehandler("rate", $xoopsModule->getVar("dirname"));
if ($ratinguser != 0) {
    // Check if Topic POSTER is voting (UNLESS Anonymous users allowed to post)
    $crit_post =& New CriteriaCompo(new Criteria("topic_id", $topic_id));
    $crit_post->add(new Criteria("post_uid", $ratinguser));
    $post_handler =& xoops_getmodulehandler("post", $xoopsModule->getVar("dirname"));
    if ($post_handler->getCount($crit_post)) {
        redirect_header("viewtopic.php?topic_id=" . $topic_id . "&amp;forum=" . $forum, 4, _MD_CANTVOTEOWN);
        exit();
    }
    // Check if REG user is trying to vote twice.
    $crit_rate =& New CriteriaCompo(new Criteria("topic_id", $topic_id));
    $crit_rate->add(new Criteria("ratinguser", $ratinguser));
    if ($rate_handler->getCount($crit_rate)) {
        redirect_header("viewtopic.php?topic_id=" . $topic_id . "&amp;forum=" . $forum, 4, _MD_VOTEONCE);
        exit();
    }
} else {
    // Check if ANONYMOUS user is trying to vote more than once per day.
    $crit_rate =& New CriteriaCompo(new Criteria("topic_id", $topic_id));
    $crit_rate->add(new Criteria("ratinguser", $ratinguser));
    $crit_rate->add(new Criteria("ratinghostname", $ip));
    $crit_rate->add(new Criteria("ratingtimestamp", time() - (86400 * $anonwaitdays), ">"));
    if ($rate_handler->getCount($crit_rate)) {
        redirect_header("viewtopic.php?topic_id=" . $topic_id . "&amp;forum=" . $forum, 4, _MD_VOTEONCE);
        exit();
    }
}
$rate_obj =& $rate_handler->create();
$rate_obj->setVar("rating", $rate * 2);
$rate_obj->setVar("topic_id", $topic_id);
$rate_obj->setVar("ratinguser", $ratinguser);
$rate_obj->setVar("ratinghostname", $ip);
$rate_obj->setVar("ratingtimestamp", time());

$ratingid = $rate_handler->insert($rate_obj);;
        
$query = "select rating FROM " . $xoopsDB -> prefix('bb_votedata') . " WHERE topic_id = " . $topic_id . "";
$voteresult = $xoopsDB->query($query);
$votesDB = $xoopsDB->getRowsNum($voteresult);
$totalrating = 0;
while (list($rating) = $xoopsDB -> fetchRow($voteresult)) {
    $totalrating += $rating;
}
$finalrating = $totalrating / $votesDB;
$finalrating = number_format($finalrating, 4);
$sql = sprintf("UPDATE %s SET rating = %u, votes = %u WHERE topic_id = %u", $xoopsDB -> prefix('bb_topics'), $finalrating, $votesDB, $topic_id);
$xoopsDB->queryF($sql);

$ratemessage = _MD_VOTEAPPRE . "<br />" . sprintf(_MD_THANKYOU, $xoopsConfig['sitename']);
redirect_header("viewtopic.php?topic_id=" . $topic_id . "&amp;forum=" . $forum, 2, $ratemessage);
exit();

include XOOPS_ROOT_PATH . '/footer.php';
?>