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
 * @version         $Id: functions.stats.php 2170 2008-09-23 13:40:23Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

function newbb_getStats()
{
    $stats_handler =& xoops_getmodulehandler('stats', 'newbb');
    $stats = $stats_handler->getStats();
    return $stats;
}

function newbb_updateStats($id, $type, $increment = 1)
{
    $stats_handler =& xoops_getmodulehandler('stats', 'newbb');
    return $stats_handler->update($id, $type, $increment);
}

/*
* Gets the total number of topics in a form
*/
function newbb_getTotalTopics($forum_id = "")
{
    $topic_handler =& xoops_getmodulehandler('topic', 'newbb');
    $criteria =& new CriteriaCompo(new Criteria("approved", 0, ">"));
    if ( $forum_id ) {
        $criteria->add(new Criteria("forum_id", intval($forum_id)));
    }
    return $topic_handler->getCount($criteria);
}

/*
* Returns the total number of posts in the whole system, a forum, or a topic
* Also can return the number of users on the system.
*/
function newbb_getTotalPosts($id = 0, $type = "all")
{
    $post_handler =& xoops_getmodulehandler('post', 'newbb');
    $criteria =& new CriteriaCompo(new Criteria("approved", 0, ">"));
    switch ( $type ) {
    case 'forum':
        if ($id > 0) $criteria->add(new Criteria("forum_id", intval($id)));
        break;
    case 'topic':
        if ($id > 0) $criteria->add(new Criteria("topic_id", intval($id)));
        break;
    case 'all':
    default:
        break;
    }
    return $post_handler->getCount($criteria);
}

function newbb_getTotalViews()
{
    global $xoopsDB;
    $sql = "SELECT sum(topic_views) FROM " . $xoopsDB->prefix("bb_topics");
    if ( !$result = $xoopsDB->query($sql) ) {
        return null;
    }
    list ($total) = $xoopsDB->fetchRow($result);
    return $total;
}

?>