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
 * @version         $Id: viewpost.php 2175 2008-09-23 14:07:03Z phppp $
 */
include 'header.php';
// To enable image auto-resize by js
//$xoops_module_header .= '<script src="'.XOOPS_URL.'/Frameworks/textsanitizer/xoops.js" type="text/javascript"></script>';

$start = !empty($_GET['start']) ? intval($_GET['start']) : 0;
$forum_id = !empty($_GET['forum']) ? intval($_GET['forum']) : 0;
$order = isset($_GET['order']) ? $_GET['order'] : "DESC";

$uid = !empty($_GET['uid']) ? intval($_GET['uid']) : 0;
$status = (!empty($_GET['status']) && in_array($_GET['status'], array("active", "pending", "deleted", "new"))) ? $_GET['status'] : "";
$mode = !empty($_GET['mode']) ? intval($_GET['mode']) : 0;
$mode = (!empty($status) && in_array($status, array("active", "pending", "deleted")) ) ? 2 : $mode;

$forum_handler =& xoops_getmodulehandler('forum', 'newbb');
$post_handler =& xoops_getmodulehandler('post', 'newbb');

if (empty($forum_id)) {
    $forums = $forum_handler->getByPermission(0, "view");
    $access_forums = array_keys($forums);
    $isadmin = $GLOBALS["xoopsUserIsAdmin"];
} else {
    $forum_obj =& $forum_handler->get($forum_id);
    $forums[$forum_id] =& $forum_obj;
    $access_forums = array($forum_id);
    $isadmin = newbb_isAdmin($forum_obj);
}

/* Only admin has access to admin mode */
if (!$isadmin) {
    $status = in_array($status, array("active", "pending", "deleted")) ? "" : $status;
    $mode = 0;
}
if ($mode) {
    $_GET['viewmode'] = "flat";
}

$post_perpage = $xoopsModuleConfig['posts_per_page'];

$criteria_count = new CriteriaCompo(new Criteria("forum_id", "(" . implode(",", $access_forums) . ")", "IN"));
$criteria_post = new CriteriaCompo(new Criteria("p.forum_id", "(" . implode(",", $access_forums) . ")", "IN"));
$criteria_post->setSort("p.post_id");
$criteria_post->setOrder($order);

if (!empty($uid)) {
    $criteria_count->add(new Criteria("uid", $uid));
    $criteria_post->add(new Criteria("p.uid", $uid));
}

$join = null;
switch($status) {
case "pending":
    $criteria_status_count = new Criteria("approved", 0);
    $criteria_status_post = new Criteria("p.approved", 0);
    break;
    
case "deleted":
    $criteria_status_count = new Criteria("approved", -1);
    $criteria_status_post = new Criteria("p.approved", -1);
    break;
    
case "new":
    $criteria_status_count = new CriteriaCompo(new Criteria("post_time", intval($last_visit), ">"));
    $criteria_status_post = new CriteriaCompo(new Criteria("p.post_time", intval($last_visit), ">"));
    $criteria_status_count->add(new Criteria("approved", 1));
    $criteria_status_post->add(new Criteria("p.approved", 1));
    // following is for "unread" -- not finished
    /*
    if (empty($xoopsModuleConfig["read_mode"])) {
    } elseif ($xoopsModuleConfig["read_mode"] ==2) {
        $join = ' LEFT JOIN ' . $this->db->prefix('bb_reads_topic') . ' r ON r.read_item = p.topic_id';
        $criteria_status_post = new CriteriaCompo(new Criteria("p.post_id", "r.post_id", ">"));
        $criteria_status_post->add(new Criteria("r.read_id", "NULL", "IS"), "OR");
        $criteria_status_post->add(new Criteria("p.approved", 1));
        $criteria_status_count =& $criteria_status_post;
    } elseif ($xoopsModuleConfig["read_mode"] == 1) {
        $criteria_status_count = new CriteriaCompo(new Criteria("post_time", intval($last_visit), ">"));
        $criteria_status_post = new CriteriaCompo(new Criteria("p.post_time", intval($last_visit), ">"));
        $criteria_status_count->add(new Criteria("approved", 1));
        $criteria_status_post->add(new Criteria("p.approved", 1));
    }
    */
    break;
    
default:
    $criteria_status_count = new Criteria("approved", 1);
    $criteria_status_post = new Criteria("p.approved", 1);
    break;
}
$criteria_count->add($criteria_status_count);
$criteria_post->add($criteria_status_post);

$karma_handler =& xoops_getmodulehandler('karma', 'newbb');
$user_karma = $karma_handler->getUserKarma();

$valid_modes = array("flat", "compact");
$viewmode_cookie = newbb_getcookie("V");
if (isset($_GET['viewmode']) && $_GET['viewmode'] == "compact") {
    newbb_setcookie("V", "compact", $forumCookie['expire']);
}
$viewmode = isset($_GET['viewmode'])
            ? $_GET['viewmode']
            : ( !empty($viewmode_cookie)
                ? $viewmode_cookie
                : @$valid_modes[$xoopsModuleConfig['view_mode'] - 1]
                );
$viewmode = in_array($viewmode, $valid_modes) ? $viewmode : $valid_modes[0];

$postCount = $post_handler->getPostCount($criteria_count);
$posts = $post_handler->getPostsByLimit($criteria_post, $post_perpage, $start/*, $join*/);

$poster_array = array();
if (count($posts) > 0) {
    foreach (array_keys($posts) as $id) {
        $poster_array[$posts[$id]->getVar('uid')] = 1;
    }
}

$xoops_pagetitle = $xoopsModule->getVar('name') . ' - ' . _MD_VIEWALLPOSTS;
$xoopsOption['xoops_pagetitle']= $xoops_pagetitle;
$xoopsOption['xoops_module_header']= $xoops_module_header;
$xoopsOption['template_main'] = 'newbb_viewpost.html';

include XOOPS_ROOT_PATH . "/header.php";
require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";
require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.render.php";

if (!empty($forum_id)) {
    if (!$forum_handler->getPermission($forum_obj, "view")) {
        redirect_header("index.php", 2, _MD_NORIGHTTOACCESS);
        exit();
    }
    if ($forum_obj->getVar('parent_forum')) {
        $parent_forum_obj =& $forum_handler->get($forum_obj->getVar('parent_forum'), array("forum_name"));
        $parentforum = array("id" => $forum_obj->getVar('parent_forum'), "name" => $parent_forum_obj->getVar("forum_name"));
        unset($parent_forum_obj);
        $xoopsTpl->assign_by_ref("parentforum", $parentforum);
    }
    $xoopsTpl->assign('forum_name', $forum_obj->getVar('forum_name'));
    $xoopsTpl->assign('forum_moderators', $forum_obj->disp_forumModerators());

    $xoops_pagetitle = $forum_obj->getVar('forum_name') . ' - ' . _MD_VIEWALLPOSTS . ' [' . $xoopsModule->getVar('name') . ']';
    $xoopsTpl->assign("forum_id", $forum_obj->getVar('forum_id'));

    if (!empty($xoopsModuleConfig['rss_enable'])) {
        $xoops_module_header .= '<link rel="alternate" type="application/xml+rss" title="' . $xoopsModule->getVar('name') . '-' . $forum_obj->getVar('forum_name') . '" href="' . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname', 'n') . '/rss.php?f=' . $forum_id . '" />';
    }
} elseif (!empty($xoopsModuleConfig['rss_enable'])) {
    $xoops_module_header .= '<link rel="alternate" type="application/xml+rss" title="' . $xoopsModule->getVar('name') . '" href="' . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . '/rss.php" />';
}
$xoopsTpl->assign('xoops_module_header', $xoops_module_header);
$xoopsTpl->assign('xoops_pagetitle', $xoops_pagetitle);

$userid_array=array();
if (count($poster_array) > 0) {
    $member_handler =& xoops_gethandler('member');
    $userid_array = array_keys($poster_array);
    $user_criteria = "(" . implode(",", $userid_array) . ")";
    $users = $member_handler->getUsers( new Criteria('uid', $user_criteria, 'IN'), true);
} else {
    $user_criteria = '';
    $users = null;
}

if ($xoopsModuleConfig['wol_enabled']) {
    $online = array();
    if (!empty($user_criteria)) {
        $online_handler =& xoops_getmodulehandler('online', 'newbb');
        $online_handler->init($forum_id);
    }
}

$viewtopic_users = array();

if (count($userid_array) > 0) {
    require_once XOOPS_ROOT_PATH . "/modules/" . $xoopsModule->getVar("dirname", "n") . "/class/user.php";
    $user_handler = new NewbbUserHandler($xoopsModuleConfig['groupbar_enabled'], $xoopsModuleConfig['wol_enabled']);
    $user_handler->users = $users;
    $user_handler->online = $online;
    $viewtopic_users = $user_handler->getUsers();
}

$pn =0;
$topic_handler = &xoops_getmodulehandler('topic', 'newbb');
$suspension = array();
foreach (array_keys($posts) as $id) {
    $pn++;

    $post =& $posts[$id];
    $post_title = $post->getVar('subject');

    if ( $posticon = $post->getVar('icon') ) {
        $post_image = '<a name="' . $post->getVar('post_id') . '"><img src="' . XOOPS_URL . '/images/subject/' . htmlspecialchars($posticon) . '" alt="" /></a>';
    } else {
        $post_image = '<a name="' . $post->getVar('post_id') . '"><img src="' . XOOPS_URL . '/images/icons/no_posticon.gif" alt="" /></a>';
    }
    if ($post->getVar('uid') > 0 && isset($viewtopic_users[$post->getVar('uid')])) {
        $poster = $viewtopic_users[$post->getVar('uid')];
    } else {
        $poster= array(
            'uid'   => 0,
            'name'  => $post->getVar('poster_name') ? $post->getVar('poster_name') : $myts->HtmlSpecialChars($xoopsConfig['anonymous']),
            'link'  => $post->getVar('poster_name') ? $post->getVar('poster_name') : $myts->HtmlSpecialChars($xoopsConfig['anonymous'])
            );
    }
    if ($isadmin || $post->checkIdentity()) {
        $post_text = $post->getVar('post_text');
        $post_attachment = $post->displayAttachment();
    } elseif ($xoopsModuleConfig['enable_karma'] && $post->getVar('post_karma') > $user_karma) {
        $post_text = "<div class='karma'>" . sprintf(_MD_KARMA_REQUIREMENT, $user_karma, $post->getVar('post_karma')) . "</div>";
        $post_attachment = '';
    } elseif ( $xoopsModuleConfig['allow_require_reply'] && $post->getVar('require_reply') ) {
        $post_text = "<div class='karma'>" . _MD_REPLY_REQUIREMENT . "</div>";
        $post_attachment = '';
    } else {
        $post_text = $post->getVar('post_text');
        $post_attachment = $post->displayAttachment();
    }

    $thread_buttons = array();
    
    if ($GLOBALS["xoopsModuleConfig"]['enable_permcheck']) {
    
        if (!isset($suspension[$post->getVar('forum_id')])) {
            $moderate_handler =& xoops_getmodulehandler('moderate', 'newbb');
            $suspension[$post->getVar('forum_id')] = $moderate_handler->verifyUser(-1, "", $post->getVar('forum_id'));
        }
        
        if (!$suspension[$post->getVar('forum_id')] && $post->checkIdentity() && $post->checkTimelimit('edit_timelimit') || $isadmin) {
            $thread_buttons['edit']['image'] = newbb_displayImage('p_edit', _EDIT);
            $thread_buttons['edit']['link'] = "edit.php?forum=" . $post->getVar('forum_id') . "&amp;topic_id=" . $post->getVar('topic_id');
            $thread_buttons['edit']['name'] = _EDIT;
        }
    
        if ( (!$suspension[$post->getVar('forum_id')] && $post->checkIdentity() && $post->checkTimelimit('delete_timelimit')) || $isadmin ) {
            $thread_buttons['delete']['image'] = newbb_displayImage('p_delete', _DELETE);
            $thread_buttons['delete']['link'] = "delete.php?forum=" . $post->getVar('forum_id') . "&amp;topic_id=" . $post->getVar('topic_id');
            $thread_buttons['delete']['name'] = _DELETE;
        }
        if (!$suspension[$post->getVar('forum_id')] && is_object($xoopsUser)) {
            $thread_buttons['reply']['image'] = newbb_displayImage('p_reply', _MD_REPLY);
            $thread_buttons['reply']['link'] = "reply.php?forum=" . $post->getVar('forum_id') . "&amp;topic_id=" . $post->getVar('topic_id');
            $thread_buttons['reply']['name'] = _MD_REPLY;
            
            $thread_buttons['quote']['image'] = newbb_displayImage('p_quote', _MD_QUOTE);
            $thread_buttons['quote']['link'] = "reply.php?forum=" . $post->getVar('forum_id') . "&amp;topic_id=" . $post->getVar('topic_id') . "&amp;quotedac=1";
            $thread_buttons['quote']['name'] = _MD_QUOTE;
        }
    
    } else {
        $thread_buttons['edit']['image'] = newbb_displayImage('p_edit', _EDIT);
        $thread_buttons['edit']['link'] = "edit.php?forum=" .$post->getVar('forum_id') . "&amp;topic_id=" . $post->getVar('topic_id');
        $thread_buttons['edit']['name'] = _EDIT;
        $thread_buttons['delete']['image'] = newbb_displayImage('p_delete', _DELETE);
        $thread_buttons['delete']['link'] = "delete.php?forum=" . $post->getVar('forum_id') . "&amp;topic_id=" . $post->getVar('topic_id');
        $thread_buttons['delete']['name'] = _DELETE;
        $thread_buttons['reply']['image'] = newbb_displayImage('p_reply', _MD_REPLY);
        $thread_buttons['reply']['link'] = "reply.php?forum=" . $post->getVar('forum_id') . "&amp;topic_id=" . $post->getVar('topic_id');
        $thread_buttons['reply']['name'] = _MD_REPLY;
    }

    if (!$isadmin && $xoopsModuleConfig['reportmod_enabled']) {
        $thread_buttons['report']['image'] = newbb_displayImage('p_report', _MD_REPORT);
        $thread_buttons['report']['link'] = "report.php?forum=" . $post->getVar('forum_id') . "&amp;topic_id=" . $post->getVar('topic_id');
        $thread_buttons['report']['name'] = _MD_REPORT;
    }
    $thread_action = array();

    $xoopsTpl->append('posts', array(
                    'post_id'           => $post->getVar('post_id'),
                    'topic_id'          => $post->getVar('topic_id'),
                    'forum_id'          => $post->getVar('forum_id'),
                    'post_date'         => newbb_formatTimestamp($post->getVar('post_time')),
                    'post_image'        => $post_image,
                    'post_title'        => $post_title,
                    'post_text'         => $post_text,
                    'post_attachment'   => $post_attachment,
                    'post_edit'         => $post->displayPostEdit(),
                    'post_no'           => $start + $pn,
                    'post_signature'    => $post->getVar('attachsig') ? @$poster["signature"] : "",
                    'poster_ip'         => ($isadmin && $xoopsModuleConfig['show_ip']) ? long2ip($post->getVar('poster_ip')) : "",
                    'thread_action'     => $thread_action,
                    'thread_buttons'    => $thread_buttons,
                    'poster'            => $poster
                   )
          );

    unset($thread_buttons);
    unset($poster);
}
unset($viewtopic_users);
unset($forums);

if (!empty($xoopsModuleConfig['show_jump'])) {
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.forum.php";
    $xoopsTpl->assign('forum_jumpbox', newbb_make_jumpbox($forum_id));
}

if ( $postCount > $post_perpage ) {
    include XOOPS_ROOT_PATH . '/class/pagenav.php';
    $nav = new XoopsPageNav($postCount, $post_perpage, $start, "start", 'forum=' . $forum_id . '&amp;viewmode=' . $viewmode . '&amp;status=' . $status . '&amp;uid=' . $uid . '&amp;order=' . $order . "&amp;mode=" . $mode);
    $xoopsTpl->assign('pagenav', $nav->renderNav(4));
} else {
    $xoopsTpl->assign('pagenav', '');
}

$xoopsTpl->assign('lang_forum_index', sprintf(_MD_FORUMINDEX, htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES)));

switch($status) {
case 'active':
    $lang_title = _MD_VIEWALLPOSTS. ' ['._MD_TYPE_ADMIN.']';
    break;
case 'pending':
    $lang_title = _MD_VIEWALLPOSTS. ' ['._MD_TYPE_PENDING.']';
    break;
case 'deleted':
    $lang_title = _MD_VIEWALLPOSTS. ' ['._MD_TYPE_DELETED.']';
    break;
case 'new':
    $lang_title = _MD_NEWPOSTS;
    break;
default:
    $lang_title = _MD_VIEWALLPOSTS;
    break;
}
if ($uid > 0) {
    $lang_title .= ' (' . XoopsUser::getUnameFromId($uid) . ')';
}    
$xoopsTpl->assign('lang_title', $lang_title);
$xoopsTpl->assign('up', newbb_displayImage('up',_MD_TOP));
$xoopsTpl->assign('groupbar_enable', $xoopsModuleConfig['groupbar_enabled']);
$xoopsTpl->assign('anonymous_prefix', $xoopsModuleConfig['anonymous_prefix']);
$xoopsTpl->assign('down',newbb_displayImage('down', _MD_BOTTOM));

$all_link = "viewall.php?forum={$forum_id}&amp;start={$start}";
$post_link = "viewpost.php?forum={$forum_id}";
$newpost_link = "viewpost.php?forum={$forum_id}&amp;status=new";
$digest_link = "viewall.php?forum={$forum_id}&amp;start={$start}&amp;status=digest";
$unreplied_link = "viewall.php?forum={$forum_id}&amp;start={$start}&amp;status=unreplied";
$unread_link = "viewall.php?forum={$forum_id}&amp;start={$start}&amp;status=unread";

$xoopsTpl->assign('all_link', $all_link);
$xoopsTpl->assign('post_link', $post_link);
$xoopsTpl->assign('newpost_link', $newpost_link);
$xoopsTpl->assign('digest_link', $digest_link);
$xoopsTpl->assign('unreplied_link', $unreplied_link);
$xoopsTpl->assign('unread_link', $unread_link);

$viewmode_options = array();
if ($viewmode=="compact") {
    $viewmode_options[] = array("link" => "viewpost.php?viewmode=flat&amp;order={$order}&amp;forum=" . $forum_id, "title" => _FLAT);
    if ($order == 'DESC') {
        $viewmode_options[] = array("link" => "viewpost.php?viewmode=compact&amp;order=ASC&amp;forum=" . $forum_id, "title" => _OLDESTFIRST);
    } else {
        $viewmode_options[] = array("link" => "viewpost.php?viewmode=compact&amp;order=DESC&amp;forum=" . $forum_id, "title" => _NEWESTFIRST);
    }
} else {
    $viewmode_options[]= array("link" => "viewpost.php?viewmode=compact&amp;order={$order}&amp;forum=" . $forum_id, "title" => _MD_COMPACT);
    if ($order == 'DESC') {
        $viewmode_options[] = array("link" => "viewpost.php?viewmode=flat&amp;order=ASC&amp;forum=" . $forum_id, "title" => _OLDESTFIRST);
    } else {
        $viewmode_options[] = array("link" => "viewpost.php?viewmode=flat&amp;order=DESC&amp;forum=" . $forum_id, "title" => _NEWESTFIRST);
    }
}

$xoopsTpl->assign('viewmode_compact', ($viewmode == "compact") ? 1 : 0);
$xoopsTpl->assign_by_ref('viewmode_options', $viewmode_options);
$xoopsTpl->assign('menumode', $menumode);
$xoopsTpl->assign('menumode_other', $menumode_other);

$xoopsTpl->assign('viewer_level', ($isadmin) ? 2 : is_object($xoopsUser) );
$xoopsTpl->assign('uid', $uid);
$xoopsTpl->assign('mode', $mode);
$xoopsTpl->assign('status', $status);

if ($transferbar = @include XOOPS_ROOT_PATH . "/Frameworks/transfer/bar.transfer.php") {
    $xoopsTpl->assign('transfer', $transferbar);
}

include XOOPS_ROOT_PATH . '/footer.php';
?>