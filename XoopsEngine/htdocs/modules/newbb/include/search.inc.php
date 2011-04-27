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
 * @version         $Id: search.inc.php 2170 2008-09-23 13:40:23Z phppp $
 */
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}
require_once(XOOPS_ROOT_PATH.'/modules/newbb/include/functions.php');

function &newbb_search($queryarray, $andor, $limit, $offset, $userid, $forums = 0, $sortby = 0, $searchin = "both", $subquery = "")
{
    global $xoopsDB, $xoopsConfig, $myts, $xoopsUser;

    $uid = ( is_object($xoopsUser) && $xoopsUser->isactive() ) ? $xoopsUser->getVar('uid') : 0;

    $forum_handler =& xoops_getmodulehandler('forum', 'newbb');
    $valid_forums = $forum_handler->getIdsByPermission();
    
    if (is_array($forums) && count($forums) > 0) {
        $valid_forums = array_intersect($valid_forums, $forums);
    } elseif (is_numeric($forums) && $forums > 0) {
        $valid_forums = array_intersect($valid_forums, array($forums));
    }

     $sql = 'SELECT p.uid,f.forum_id, p.topic_id, p.poster_name, p.post_time,';
    $sql .= ' f.forum_name, p.post_id, p.subject
            FROM '.$xoopsDB->prefix('bb_posts').' p,
            '.$xoopsDB->prefix('bb_posts_text').' pt,
            '.$xoopsDB->prefix('bb_forums').' f';
    $sql .= ' WHERE p.post_id = pt.post_id';
    $sql .= ' AND p.approved = 1';
    $sql .= ' AND p.forum_id = f.forum_id';
//                AND p.uid = u.uid'; // In case exists a userid with which the associated user is removed, this line will block search results.  Can do nothing unless xoops changes its way dealing with user removal
    if (!empty($forum)) {
        $sql .= ' AND f.forum_id IN ('.implode(',', $valid_forums).')';
    }

    $isUser=false;
    if ( is_numeric($userid) && $userid != 0 ) {
        $sql .= " AND p.uid=".$userid." ";
        $isUser=true;
    } else
    // TODO
    if (is_array($userid) && count($userid) > 0) {
        $userid = array_map('intval', $userid);
        $sql .= " AND p.uid IN (".implode(',', $userid).") ";
        $isUser=true;
    }

    $count = count($queryarray);
    if ( is_array($queryarray) && $count > 0) {
        switch ($searchin) {
           case 'title':
               $sql .= " AND ((p.subject LIKE '%$queryarray[0]%')";
               for ($i = 1; $i < $count; $i++) {
                   $sql .= " $andor ";
                   $sql .= "(p.subject LIKE '%$queryarray[$i]%')";
               }
               $sql .= ") ";
               break;

           case 'text':
               $sql .= " AND ((pt.post_text LIKE '%$queryarray[0]%')";
               for ($i = 1; $i < $count; $i++) {
                   $sql .= " $andor ";
                   $sql .= "(pt.post_text LIKE '%$queryarray[$i]%')";
               }
               $sql .= ") ";
               break;
            case 'both' :
            default;
               $sql .= " AND ((p.subject LIKE '%$queryarray[0]%' OR pt.post_text LIKE '%$queryarray[0]%')";
               for ($i = 1; $i < $count; $i++) {
                   $sql .= " $andor ";
                   $sql .= "(p.subject LIKE '%$queryarray[$i]%' OR pt.post_text LIKE '%$queryarray[$i]%')";
               }
               $sql .= ") ";
               break;
        }
    }

    if (!$sortby) {
        $sortby = "p.post_time DESC";
    }
    $sql .= $subquery." ORDER BY ".$sortby;
    $result = $xoopsDB->query($sql,$limit,$offset);
    $ret = array();
    $users = array();
    $i = 0;
     while($myrow = $xoopsDB->fetchArray($result)) {
        $ret[$i]['link'] = "viewtopic.php?topic_id=".$myrow['topic_id']."&amp;forum=".$myrow['forum_id']."&amp;post_id=".$myrow['post_id']."#forumpost".$myrow['post_id'];
        $ret[$i]['title'] = $myrow['subject'];
        $ret[$i]['time'] = $myrow['post_time'];
        $ret[$i]['uid'] = $myrow['uid'];
        $ret[$i]['forum_name'] = $myts->htmlSpecialChars($myrow['forum_name']);
        $ret[$i]['forum_link'] = "viewforum.php?forum=".$myrow['forum_id'];
        $users[$myrow['uid']] = 1;
        $ret[$i]['poster_name'] = $myrow['poster_name'];
        $i++;
    }

    if (count($users)>0) {
        $member_handler =& xoops_gethandler('member');
        $userid_array = array_keys($users);
        $user_criteria = "(".implode(",",$userid_array).")";
        $users = $member_handler->getUsers( new Criteria('uid', $user_criteria, 'IN'), true);
    } else {
        $users = null;
    }

    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.config.php";
    $newbbConfig = newbb_loadConfig();

    $count = count($ret);
    for($i =0; $i < $count; $i ++ ) {
        if ($ret[$i]['uid'] > 0) {
            if ( isset($users[$ret[$i]['uid']]) && (is_object($users[$ret[$i]['uid']])) && ($users[$ret[$i]['uid']]->isActive()) ) {
                $poster = ($newbbConfig['show_realname'] && $users[$ret[$i]['uid']]->getVar('name'))? $users[$ret[$i]['uid']]->getVar('name') : $users[$ret[$i]['uid']]->getVar('uname');
                $poster = $myts->htmlSpecialChars($poster);
                $ret[$i]['poster'] = '<a href="'.XOOPS_URL.'/userinfo.php?uid='.$ret[$i]['uid'].'">'.$poster.'</a>';
                $title = $myts->htmlSpecialChars($ret[$i]['title']);
            } else {
                $ret[$i]['poster'] = $xoopsConfig['anonymous'];
                $ret[$i]['uid'] = 0; // Have to force this to fit xoops search.php
            }
        } else {
            $ret[$i]['poster'] = (empty($ret[$i]['poster_name']))?$xoopsConfig['anonymous']:$myts->htmlSpecialChars($ret[$i]['poster_name']);
            $ret[$i]['uid'] = 0;
        }
    }
    unset($users);

    return $ret;
}
?>