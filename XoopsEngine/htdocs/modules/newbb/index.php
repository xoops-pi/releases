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
 * @version         $Id: index.php 2175 2008-09-23 14:07:03Z phppp $
 */

include "header.php";

/* deal with marks */
if (isset($_GET['mark_read'])) {
    if (1 == intval($_GET['mark_read'])) { // marked as read
        $markvalue = 1;
        $markresult = _MD_MARK_READ;
    } else { // marked as unread
        $markvalue = 0;
        $markresult = _MD_MARK_UNREAD;
    }
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.read.php";
    newbb_setRead_forum($markvalue);
    redirect_header("index.php", 2, _MD_ALL_FORUM_MARKED . ' ' . $markresult);
}

$viewcat = @intval($_GET['cat']);
$category_handler =& xoops_getmodulehandler('category', 'newbb');

$categories = array();
if (!$viewcat) {
    $categories = $category_handler->getByPermission('access', null, false);
    $forum_index_title = "";
    $xoops_pagetitle = $xoopsModule->getVar('name');
} else {
    $category_obj =& $category_handler->get($viewcat);
    if ($category_handler->getPermission($category_obj)) {
        $categories[$viewcat] = $category_obj->getValues();
    }
    $forum_index_title = sprintf(_MD_FORUMINDEX, htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES));
    $xoops_pagetitle = $category_obj->getVar('cat_title') . " [" . $xoopsModule->getVar('name') . "]";
}

if (count($categories) == 0) {
    redirect_header(XOOPS_URL, 2, _MD_NORIGHTTOACCESS);
    exit();
}

/* rss feed */
if (!empty($xoopsModuleConfig['rss_enable'])) {
    $xoops_module_header .= '<link rel="alternate" type="application/rss+xml" title="' . $xoopsModule->getVar('name') . '" href="' . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname', 'n') . '/rss.php" />';
}

$xoopsOption['template_main'] = 'newbb_index.html';
$xoopsOption['xoops_pagetitle'] = $xoops_pagetitle;
$xoopsOption['xoops_module_header'] = $xoops_module_header;
include XOOPS_ROOT_PATH . "/header.php";

$xoopsLogger->startTime( 'XOOPS output module - bb' );

require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.render.php";

$xoopsTpl->assign('xoops_pagetitle', $xoops_pagetitle);
$xoopsTpl->assign('xoops_module_header', $xoops_module_header);
$xoopsTpl->assign('forum_index_title', $forum_index_title);

$xoopsLogger->startTime( 'XOOPS output module - bb - online' );
if ($xoopsModuleConfig['wol_enabled']) {
    $online_handler =& xoops_getmodulehandler('online', 'newbb');
    $online_handler->init();
    $xoopsTpl->assign('online', $online_handler->show_online());
}
$xoopsLogger->stopTime( 'XOOPS output module - bb - online' );

$forum_handler =& xoops_getmodulehandler('forum', 'newbb');

$xoopsLogger->startTime( 'XOOPS output module - bb - forum' );

/* Allowed forums */
$forums_allowed = $forum_handler->getIdsByPermission();

/* fetch top forums */
$forums_top = array();
$xoopsLogger->startTime( 'XOOPS output module - bb - forum - top' );
if (!empty($forums_allowed)) {
    $crit_top = new CriteriaCompo(new Criteria("parent_forum", 0));
    $crit_top->add(new Criteria("cat_id", "(" . implode(", ", array_keys($categories)) . ")", "IN"));
    $crit_top->add(new Criteria("forum_id", "(" . implode(", ", $forums_allowed) . ")", "IN"));
    $forums_top = $forum_handler->getIds($crit_top);
}
$xoopsLogger->stopTime( 'XOOPS output module - bb - forum - top' );

$xoopsLogger->startTime( 'XOOPS output module - bb - forum - sub' );
/* fetch subforums if required to display */
if (empty($forums_top) || $xoopsModuleConfig['subforum_display'] == "hidden") {
    $forums_sub = array();
} else {
    $crit_sub = new CriteriaCompo(new Criteria("parent_forum", "(" . implode(", ", $forums_top) . ")", "IN"));
    $crit_sub->add(new Criteria("forum_id", "(" . implode(", ", $forums_allowed) . ")", "IN"));
    $forums_sub = $forum_handler->getIds($crit_sub);
}
$xoopsLogger->stopTime( 'XOOPS output module - bb - forum - sub' );

/* Fetch forum data */
$forums_available = array_merge($forums_top, $forums_sub);
$forums_array = array();
if (!empty($forums_available)) {
    $crit_forum = new Criteria("forum_id", "(" . implode(", ", $forums_available) . ")", "IN");
    $crit_forum->setSort("cat_id ASC, parent_forum ASC, forum_order");
    $crit_forum->setOrder("ASC");
$xoopsLogger->startTime( 'XOOPS output module - bb - forum - prepare' );
    $forums = $forum_handler->getAll($crit_forum, null, false);
$xoopsLogger->stopTime( 'XOOPS output module - bb - forum - prepare' );

$xoopsLogger->startTime( 'XOOPS output module - bb - forum - display' );
    $forums_array = $forum_handler->display($forums, $xoopsModuleConfig["length_title_index"], $xoopsModuleConfig["count_subforum"]);
$xoopsLogger->stopTime( 'XOOPS output module - bb - forum - display' );
}

if (count($forums_array)>0) {
    foreach ($forums_array[0] as $parent => $forum) {
        if (isset($forums_array[$forum['forum_id']])) {
            $forum['subforum'] =& $forums_array[$forum['forum_id']];
        }
        $forumsByCat[$forum['forum_cid']][] = $forum;
    }
}
$xoopsLogger->stopTime( 'XOOPS output module - bb - forum' );

$category_array = array();
$toggles = newbb_getcookie('G', true);
$icon_handler = newbb_getIconHandler();
$category_icon = array(
    "expand"    => $icon_handler->getImageSource("minus"),
    "collapse"  => $icon_handler->getImageSource("plus")
    );

foreach (array_keys($categories) as $id) {
    $forums = array();
    $onecat =& $categories[$id];

    $cat_element_id = "cat_" . $onecat['cat_id'];
    $expand = (count($toggles) > 0) ? ( (in_array($cat_element_id, $toggles)) ? false : true ) : true;
    $cat_display = ($expand) ? 'block;' : 'none;';
    $cat_icon_display  = ($expand) ? $category_icon["expand"] : $category_icon["collapse"];

    if (isset($forumsByCat[$onecat['cat_id']])) {
        $forums =& $forumsByCat[$onecat['cat_id']];
    }

    $cat_sponsor = array();
    @list($url, $title) = array_map("trim", preg_split("/ /", $onecat['cat_url'], 2));
    if (empty($title)) $title = $url;
    $title = $myts->htmlSpecialChars($title);
    if (!empty($url)) {
        $cat_sponsor = array("title" => $title, "link" => formatURL($url));
    }
    $cat_image = $onecat['cat_image'];
    if ( !empty($cat_image) && $cat_image != "blank.gif") {
        $cat_image = XOOPS_URL."/modules/" . $xoopsModule->getVar("dirname", "n") . "/images/category/" . $cat_image;
    } else {
        $cat_image = "";
    }
    $category_array[] = array(
        'cat_id'            => $onecat['cat_id'],
        'cat_title'         => $onecat['cat_title'],
        'cat_image'         => $cat_image,
        'cat_sponsor'       => $cat_sponsor,
        'cat_description'   => $onecat['cat_description'],
        'cat_element_id'    => $cat_element_id,
        'cat_display'       => $cat_display,
        'cat_icon_display'  => $cat_icon_display,
        'forums'            => $forums
        );
}
unset($categories, $forums_array, $forumsByCat);
$xoopsTpl->assign_by_ref("category_icon", $category_icon);
$xoopsTpl->assign_by_ref("categories", $category_array);

$xoopsTpl->assign(array(
    "index_title"   => sprintf(_MD_WELCOME, htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES)),
    "index_desc"    => _MD_TOSTART,
    ));

/* display user stats */
$userstats = array();
if (is_object($xoopsUser)) {
    $userstats_handler =& xoops_getmodulehandler('userstats');
    $userstats_row = $userstats_handler->getStats($xoopsUser->getVar("uid"));
    $userstats["topics"] = sprintf(_MD_USER_TOPICS, intval( @$userstats_row["user_topics"] ));
    $userstats["posts"] = sprintf(_MD_USER_POSTS, intval( @$userstats_row["user_posts"] ));
    $userstats["digests"] = sprintf(_MD_USER_DIGESTS, intval( @$userstats_row["user_digests"] ));
    $userstats["currenttime"] = sprintf(_MD_TIMENOW, formatTimestamp(time(), "s"));
    $userstats["lastvisit"] = sprintf(_MD_USER_LASTVISIT, formatTimestamp($last_visit, "s"));
    $userstats["lastpost"] = empty($userstats_row["user_lastpost"]) ? _MD_USER_NOLASTPOST : sprintf(_MD_USER_LASTPOST, formatTimestamp($userstats_row["user_lastpost"], "s"));
}
$xoopsTpl->assign_by_ref("userstats", $userstats);

/* display forum stats */
$stats_handler =& xoops_getmodulehandler('stats');
$stats = $stats_handler->getStats(array_merge(array(0), $forums_available));
$xoopsTpl->assign_by_ref("stats", $stats);
$xoopsTpl->assign("subforum_display", $xoopsModuleConfig['subforum_display']);
$xoopsTpl->assign('mark_read', "index.php?mark_read=1");
$xoopsTpl->assign('mark_unread', "index.php?mark_read=2");

$xoopsTpl->assign('all_link', "viewall.php");
$xoopsTpl->assign('post_link', "viewpost.php");
$xoopsTpl->assign('newpost_link', "viewpost.php?status=new");
$xoopsTpl->assign('digest_link', "viewall.php?status=digest");
$xoopsTpl->assign('unreplied_link', "viewall.php?status=unreplied");
$xoopsTpl->assign('unread_link', "viewall.php?status=unread");
$xoopsTpl->assign('menumode', $menumode);
$xoopsTpl->assign('menumode_other', $menumode_other);

$isadmin = $GLOBALS["xoopsUserIsAdmin"];
$xoopsTpl->assign('viewer_level',  ($isadmin) ? 2 : is_object($xoopsUser));
$mode = (!empty($_GET['mode'])) ? intval($_GET['mode']) : 0;
$xoopsTpl->assign('mode', $mode );

$xoopsTpl->assign('viewcat', $viewcat);
$xoopsTpl->assign('version', $xoopsModule->getVar("version"));

/* To be removed */
if ( $isadmin ) {
    $xoopsTpl->assign('forum_index_cpanel',array("link" => "admin/index.php", "name" => _MD_ADMINCP));
}

if ($xoopsModuleConfig['rss_enable'] == 1) {
    $xoopsTpl->assign("rss_enable", 1);
    $xoopsTpl->assign("rss_button", newbb_displayImage('rss', 'RSS feed'));
}
$xoopsTpl->assign(array(
    "img_forum_new" => newbb_displayImage('forum_new', _MD_NEWPOSTS),
    "img_forum" => newbb_displayImage('forum', _MD_NONEWPOSTS),
    'img_subforum' => newbb_displayImage('subforum')));

$xoopsLogger->stopTime( 'XOOPS output module - bb' );

include_once XOOPS_ROOT_PATH . '/footer.php';
?>