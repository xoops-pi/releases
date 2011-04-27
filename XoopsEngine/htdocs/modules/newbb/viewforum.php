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
 * @version         $Id: viewforum.php 2175 2008-09-23 14:07:03Z phppp $
 */

include "header.php";

if ( empty($_GET['forum']) ) {
    redirect_header("index.php", 2, _MD_ERRORFORUM);
    exit();
}
require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.read.php";

/*
 * Build the page query
 */
$query_vars = array("forum", "type", "status", "mode", "sort", "order", "start", "since");
$query_array = array();
foreach ($query_vars as $var) {
    if (empty($_GET[$var])) continue;
    $query_array[$var] = "{$var}={$_GET[$var]}";
}
$page_query = implode("&amp;", array_values($query_array));

if (isset($_GET['mark'])) {
    if (1 == intval($_GET['mark'])) { // marked as read
        $markvalue = 1;
        $markresult = _MD_MARK_READ;
    } else { // marked as unread
        $markvalue = 0;
        $markresult = _MD_MARK_UNREAD;
    }
    newbb_setRead_topic($markvalue, $_GET['forum']);
    $url = "viewforum.php?" . $page_query;
    redirect_header($url, 2, $markresult);
}

    
$forum_id = intval($_GET['forum']);
$type = @intval($_GET['type']);
$status = (!empty($_GET['status']) && in_array($_GET['status'], array("active", "pending", "deleted", "digest", "unreplied", "unread"))) ? $_GET['status'] : "";
$mode = (!empty($status) && in_array($status, array("active", "pending", "deleted"))) ? 2 : (!empty($_GET['mode']) ? intval($_GET['mode']) : 0);


$forum_handler =& xoops_getmodulehandler('forum', 'newbb');
$forum_obj =& $forum_handler->get($forum_id);
if (!$forum_handler->getPermission($forum_obj)) {
    redirect_header("index.php", 2, _NOPERM);
    exit();
}
newbb_setRead("forum", $forum_id, $forum_obj->getVar("forum_last_post_id"));


$xoops_pagetitle = $forum_obj->getVar('forum_name') . " [" . $xoopsModule->getVar('name') . "]";
if (!empty($xoopsModuleConfig['rss_enable'])) {
    $xoops_module_header .= '<link rel="alternate" type="application/xml+rss" title="' . $xoopsModule->getVar('name') . '-' . $forum_obj->getVar('forum_name') . '" href="' . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . '/rss.php?f=' . $forum_id . '" />';
}

$xoopsOption['template_main'] = 'newbb_viewforum.html';
$xoopsOption['xoops_pagetitle']= $xoops_pagetitle;
$xoopsOption['xoops_module_header']= $xoops_module_header;

include XOOPS_ROOT_PATH . "/header.php";
require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.render.php";

$xoopsLogger->startTime( 'XOOPS output module - forum' );

$xoopsLogger->startTime( 'XOOPS output module - forum - init' );

$xoopsTpl->assign('xoops_module_header', $xoops_module_header);
$xoopsTpl->assign('xoops_pagetitle', $xoops_pagetitle);
$xoopsTpl->assign("forum_id", $forum_id);

$isadmin = newbb_isAdmin($forum_obj);
$xoopsTpl->assign('viewer_level', ($isadmin) ? 2 : is_object($xoopsUser) );
/* Only admin has access to admin mode */
if (!$isadmin) {
    $status = (!empty($status) && in_array($status, array("active", "pending", "deleted"))) ? "" : $status;
    $mode = 0;
}
$xoopsTpl->assign('mode', $mode);
$xoopsTpl->assign('status', $status);

if ($xoopsModuleConfig['wol_enabled']) {
    $online_handler =& xoops_getmodulehandler('online', 'newbb');
    $online_handler->init($forum_obj);
    $xoopsTpl->assign('online', $online_handler->show_online());
}

if ($forum_handler->getPermission($forum_obj, "post")) {
    $xoopsTpl->assign('forum_post_or_register', "<a href=\"newtopic.php?forum={$forum_id}\">" . newbb_displayImage('t_new', _MD_POSTNEW) . "</a>");
    if ($forum_handler->getPermission($forum_obj, "addpoll")) {
        $t_poll = newbb_displayImage('t_poll', _MD_ADDPOLL);
        $xoopsTpl->assign('forum_addpoll', "<a href=\"newtopic.php?op=add&amp;forum={$forum_id}\">{$t_poll}</a>");
     }
} else {
    if ( !empty($GLOBALS["xoopsModuleConfig"]["show_reg"]) && !is_object($xoopsUser)) {
        $redirect = preg_replace("|(.*)\/modules\/newbb\/(.*)|", "\\1/modules/newbb/newtopic.php?forum=" . $forum_id, htmlspecialchars($xoopsRequestUri));
        $xoopsTpl->assign('forum_post_or_register', "<a href='" . XOOPS_URL . "/user.php?xoops_redirect={$redirect}'>" . _MD_REGTOPOST . "</a>");
        $xoopsTpl->assign('forum_addpoll', "");
    } else {
        $xoopsTpl->assign('forum_post_or_register', "");
        $xoopsTpl->assign('forum_addpoll', "");
    }
}
$xoopsLogger->stopTime( 'XOOPS output module - forum - init' );

$xoopsLogger->startTime( 'XOOPS output module - forum - parent' );
$parentforum = $forum_handler->getParents($forum_obj);
$xoopsTpl->assign_by_ref("parentforum", $parentforum);
$xoopsLogger->stopTime( 'XOOPS output module - forum - parent' );

$xoopsLogger->startTime( 'XOOPS output module - forum - sub' );

$criteria = new CriteriaCompo( new Criteria("parent_forum", $forum_id) );
$criteria->add( new Criteria("forum_id", "(" . implode(", ", $forum_handler->getIdsByPermission('access')) . ")", "IN") );
$criteria->setSort("forum_order");
if ($forums = $forum_handler->getAll($criteria, null, false)) {
    $subforum_array = $forum_handler->display($forums, $xoopsModuleConfig["length_title_index"], $xoopsModuleConfig["count_subforum"]);
    $subforum = array_values($subforum_array[$forum_id]);
    unset($subforum_array);
    $xoopsTpl->assign_by_ref("subforum", $subforum);
}
$xoopsLogger->stopTime( 'XOOPS output module - forum - sub' );

$xoopsLogger->startTime( 'XOOPS output module - forum - category' );

$category_handler =& xoops_getmodulehandler("category");
$category_obj =& $category_handler->get($forum_obj->getVar("cat_id"), array("cat_title"));
$xoopsTpl->assign('category', array("id" => $forum_obj->getVar("cat_id"), "title" => $category_obj->getVar('cat_title')));

$xoopsTpl->assign('forum_index_title', sprintf(_MD_FORUMINDEX, htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES)));
$xoopsTpl->assign('forum_name', $forum_obj->getVar('forum_name'));
$xoopsTpl->assign('forum_moderators', $forum_obj->disp_forumModerators());


$sel_sort_array = array("t.topic_title" => _MD_TOPICTITLE, "u.uname" => _MD_TOPICPOSTER, "t.topic_time" => _MD_TOPICTIME, "t.topic_replies" => _MD_NUMBERREPLIES, "t.topic_views" => _MD_VIEWS, "p.post_time" => _MD_LASTPOSTTIME);
if ( !isset($_GET['sort']) || !in_array($_GET['sort'], array_keys($sel_sort_array)) ) {
    $sort = "t.topic_last_post_id";
} else {
    $sort = $_GET['sort'];
}

$forum_selection_sort = '<select name="sort">';
foreach ( $sel_sort_array as $sort_k => $sort_v ) {
    $forum_selection_sort .= '<option value="' . $sort_k . '"' . (($sort == $sort_k) ? ' selected="selected"' : '') . '>' . $sort_v . '</option>';
}
$forum_selection_sort .= '</select>';

$xoopsTpl->assign_by_ref('forum_selection_sort', $forum_selection_sort);

$order = (!isset($_GET['order']) || $_GET['order'] != "ASC") ? "DESC" : "ASC";
$forum_selection_order = '<select name="order">';
$forum_selection_order .= '<option value="ASC"' . (($order == "ASC") ? ' selected' : '') . '>' . _MD_ASCENDING . '</option>';
$forum_selection_order .= '<option value="DESC"' . (($order == "DESC") ? ' selected' : '') . '>' . _MD_DESCENDING . '</option>';
$forum_selection_order .= '</select>';

$xoopsTpl->assign_by_ref('forum_selection_order', $forum_selection_order);

$since = isset($_GET['since']) ? intval($_GET['since']) : $xoopsModuleConfig["since_default"];
require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";
$forum_selection_since = newbb_sinceSelectBox($since);
$xoopsTpl->assign_by_ref('forum_selection_since', $forum_selection_since);

$query_sort = $query_array;
unset($query_sort["sort"], $query_sort["order"]);
$page_query_sort = implode("&amp;", array_values($query_sort));
unset($query_sort);
$xoopsTpl->assign('h_topic_link', "viewforum.php?{$page_query_sort}&amp;sort=t.topic_title&amp;order=" . (($sort == "t.topic_title" && $order == "DESC") ? "ASC" : "DESC"));
$xoopsTpl->assign('h_reply_link', "viewforum.php?{$page_query_sort}&amp;sort=t.topic_replies&amp;order=" . (($sort == "t.topic_replies" && $order == "DESC") ? "ASC" : "DESC"));
$xoopsTpl->assign('h_poster_link', "viewforum.php?{$page_query_sort}&amp;sort=u.uname&amp;order=" . (($sort == "u.uname" && $order == "DESC") ? "ASC" : "DESC"));
$xoopsTpl->assign('h_views_link', "viewforum.php?{$page_query_sort}&amp;sort=t.topic_views&amp;order=" . (($sort == "t.topic_views" && $order == "DESC") ? "ASC" : "DESC"));
$xoopsTpl->assign('h_rating_link', "viewforum.php?{$page_query_sort}&amp;sort=t.topic_ratings&amp;order=" . (($sort == "t.topic_ratings" && $order == "DESC") ? "ASC" : "DESC"));
$xoopsTpl->assign('h_date_link', "viewforum.php?{$page_query_sort}&amp;sort=p.post_time&amp;order=" . (($sort == "p.post_time" && $order == "DESC") ? "ASC" : "DESC"));
$xoopsTpl->assign('h_publish_link', "viewforum.php?{$page_query_sort}&amp;sort=t.topic_time&amp;order=" . (($sort == "t.topic_time" && $order == "DESC") ? "ASC" : "DESC"));
$xoopsTpl->assign('forum_since', $since); // For $since in search.php


$startdate = empty($since) ? 0 : (time() - newbb_getSinceTime($since));
$start = !empty($_GET['start']) ? intval($_GET['start']) : 0;

$xoopsLogger->stopTime( 'XOOPS output module - forum - category' );

$criteria_vars = array("startdate", "start", "sort", "order", "type", "status", "excerpt");
foreach ($criteria_vars as $var) {
    $criteria_topic[$var] = @${$var};
}
$criteria_topic["excerpt"] = $xoopsModuleConfig['post_excerpt'];
$xoopsLogger->startTime( 'XOOPS output module - forum - topic' );
list($allTopics, $sticky) = $forum_handler->getAllTopics($forum_obj, $criteria_topic);
$xoopsLogger->stopTime( 'XOOPS output module - forum - topic' );

$xoopsTpl->assign_by_ref('topics', $allTopics);
$xoopsTpl->assign('sticky', $sticky);
$xoopsTpl->assign('rating_enable', $xoopsModuleConfig['rating_enabled']);
$xoopsTpl->assign('img_newposts', newbb_displayImage('topic_new'));
$xoopsTpl->assign('img_hotnewposts', newbb_displayImage('topic_hot_new'));
$xoopsTpl->assign('img_folder', newbb_displayImage('topic'));
$xoopsTpl->assign('img_hotfolder', newbb_displayImage('topic_hot'));
$xoopsTpl->assign('img_locked', newbb_displayImage('topic_locked'));

$xoopsTpl->assign('img_sticky', newbb_displayImage('topic_sticky', _MD_TOPICSTICKY));
$xoopsTpl->assign('img_digest', newbb_displayImage('topic_digest', _MD_TOPICDIGEST));
$xoopsTpl->assign('img_poll', newbb_displayImage('poll', _MD_TOPICHASPOLL));

$mark_read_link = "viewforum.php?mark=1&amp;{$page_query}";
$mark_unread_link = "viewforum.php?mark=2&amp;{$page_query}";
$xoopsTpl->assign('mark_read', $mark_read_link);
$xoopsTpl->assign('mark_unread', $mark_unread_link);

$xoopsTpl->assign('post_link', "viewpost.php?forum=" . $forum_id);
$xoopsTpl->assign('newpost_link', "viewpost.php?status=new&amp;forum=" . $forum_id);

$xoopsLogger->startTime( 'XOOPS output module - forum - type' );

$query_type = $query_array;
unset($query_type["type"]);
$page_query_type = implode("&amp;", array_values($query_type));
unset($query_type);
$type_handler =& xoops_getmodulehandler('type', 'newbb');
$type_options = null;
if ($types = $type_handler->getByForum($forum_id)) {
    $type_options[] = array("title" => _ALL, "link" => "viewforum.php?{$page_query_type}");
    foreach ($types as $key => $item) {
        $type_options[] = array("title" => $item["type_name"], "link" => "viewforum.php?{$page_query_type}&amp;type={$key}");
    }
}
if ($type >0) {
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.topic.php";
    $xoopsTpl->assign('forum_topictype', newbb_getTopicTitle("", $types[$type]["type_name"], $types[$type]["type_color"]));
}
$xoopsTpl->assign_by_ref('type_options', $type_options);
$xoopsLogger->stopTime( 'XOOPS output module - forum - type' );

$query_status = $query_array;
unset($query_status["status"]);
$page_query_status = implode("&amp;", array_values($query_status));
unset($query_status);
$xoopsTpl->assign('newpost_link', "viewpost.php?status=new&amp;forum=".$forum_obj->getVar('forum_id'));
$xoopsTpl->assign('all_link', "viewforum.php?{$page_query_status}");
$xoopsTpl->assign('digest_link', "viewforum.php?{$page_query_status}&amp;status=digest");
$xoopsTpl->assign('unreplied_link', "viewforum.php?{$page_query_status}&amp;status=unreplied");
$xoopsTpl->assign('unread_link', "viewforum.php?{$page_query_status}&amp;status=unread");
switch($status) {
case 'digest':
    $current_status = _MD_DIGEST;
    break;
case 'unreplied':
    $current_status = _MD_UNREPLIED;
    break;
case 'unread':
    $current_status = _MD_UNREAD;
    break;
case 'active':
    $current_status = _MD_TYPE_ADMIN;
    break;
case 'pending':
    $current_status = _MD_TYPE_PENDING;
    break;
case 'deleted':
    $current_status = _MD_TYPE_DELETED;
    break;
default:
    $current_status = '';
    break;
}
$xoopsTpl->assign('forum_topicstatus', $current_status);

$xoopsLogger->startTime( 'XOOPS output module - forum - count' );

$all_topics = $forum_handler->getTopicCount($forum_obj, $startdate, $status);
if ( $all_topics > $xoopsModuleConfig['topics_per_page'] ) {
    include_once XOOPS_ROOT_PATH . '/class/pagenav.php';
    $query_nav = $query_array;
    unset($query_nav["start"]);
    $page_query_nav = implode("&amp;", array_values($query_nav));
    unset($query_nav);
    $nav = new XoopsPageNav($all_topics, $xoopsModuleConfig['topics_per_page'], $start, "start", $page_query_nav);
    $xoopsTpl->assign('forum_pagenav', $nav->renderNav(4));
} else {
    $xoopsTpl->assign('forum_pagenav', '');
}
$xoopsLogger->stopTime( 'XOOPS output module - forum - count' );


if (!empty($xoopsModuleConfig['show_jump'])) {
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.forum.php";
    $xoopsTpl->assign('forum_jumpbox', newbb_make_jumpbox($forum_id));
}
$xoopsTpl->assign('menumode',$menumode);
$xoopsTpl->assign('menumode_other',$menumode_other);

$xoopsLogger->startTime( 'XOOPS output module - forum - permtable' );
if ($xoopsModuleConfig['show_permissiontable']) {
    $perm_handler = xoops_getmodulehandler('permission', 'newbb');
    $permission_table = $perm_handler->permission_table($forum_id, false, $isadmin);
    $xoopsTpl->assign_by_ref('permission_table', $permission_table);
    unset($permission_table);
}
$xoopsLogger->stopTime( 'XOOPS output module - forum - permtable' );

if ($xoopsModuleConfig['rss_enable'] == 1) {
    $xoopsTpl->assign("rss_button","<div align='right'><a href='" . XOOPS_URL . "/modules/" . $xoopsModule->getVar("dirname", "n") . "/rss.php?f={$forum_id}' title='RSS feed' rel='external'>" . newbb_displayImage('rss', 'RSS feed') . "</a></div>");
}
$xoopsLogger->stopTime( 'XOOPS output module - forum' );

include XOOPS_ROOT_PATH . "/footer.php";
?>