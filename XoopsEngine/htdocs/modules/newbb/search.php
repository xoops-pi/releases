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
 * @version         $Id: search.php 2175 2008-09-23 14:07:03Z phppp $
 */
include 'header.php';
xoops_loadLanguage("search");
$config_handler =& xoops_gethandler('config');
$xoopsConfigSearch = $config_handler->getConfigsByCat(XOOPS_CONF_SEARCH);
if ($xoopsConfigSearch['enable_search'] != 1) {
    header('Location: ' . XOOPS_URL . '/modules/newbb/index.php');
    exit();
}

$xoopsConfig['module_cache'][$xoopsModule->getVar('mid')] = 0;
$xoopsOption['template_main']= 'newbb_search.html';
include XOOPS_ROOT_PATH . '/header.php';

require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.render.php";
require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.forum.php";
require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";

include_once XOOPS_ROOT_PATH . '/modules/newbb/include/search.inc.php';
$limit = $xoopsModuleConfig['topics_per_page'];

$queries = array();
$andor = "";
$start = 0;
$uid = 0;
$forum = 0;
$sortby = 'p.post_time DESC';
$subquery = "";
$searchin = "both";
$sort = "";
$since = isset($_POST['since']) ? $_POST['since'] : (isset($_GET['since']) ? $_GET['since'] : null);
$next_search['since'] = $since;
$term = isset($_POST['term']) ? $_POST['term'] : (isset($_GET['term']) ? $_GET['term'] : null);
$uname = isset($_POST['uname']) ? $_POST['uname'] : (isset($_GET['uname']) ? $_GET['uname'] : null);

if ($xoopsModuleConfig['wol_enabled']) {
    $online_handler =& xoops_getmodulehandler('online', 'newbb');
    $online_handler->init(0);
}

$xoopsTpl->assign("forumindex", sprintf(_MD_FORUMINDEX, htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES)));

if ( !empty($_POST['submit']) || !empty($_GET['submit']) || !empty($uname) || !empty($term)) {
    $start = isset($_GET['start']) ? $_GET['start'] : 0;
    $forum = isset($_POST['forum']) ? $_POST['forum'] : (isset($_GET['forum']) ? $_GET['forum'] : null);
    if (empty($forum) || $forum == 'all' || (is_array($forum) && in_array('all', $forum))) {
       $forum = array();
    } elseif (!is_array($forum)) {
       $forum = array_map("intval", explode("|", $forum));
    }
    $next_search['forum'] = implode("|", $forum);

    $addterms = isset($_POST['andor']) ? $_POST['andor'] : (isset($_GET['andor']) ? $_GET['andor'] : "");
    $next_search['andor'] = $addterms;

    if ( !in_array(strtolower($addterms), array("or", "and", "exact"))) {
        $andor = "AND";
    } else {
        $andor = strtoupper($addterms);
    }

    $uname_required = false;
    $search_username = $uname;
    $search_username = trim($search_username);
    $next_search['uname'] = $search_username;
    if ( !empty($search_username) ) {
        $uname_required = true;
        $search_username = $myts->addSlashes($search_username);
        if ( !$result = $xoopsDB->query("SELECT uid FROM " . $xoopsDB->prefix("users") . " WHERE uname LIKE '%{$search_username}%'") ) {
            redirect_header('search.php',1,_MD_ERROROCCURED);
            exit();
        }
        $uid = array();
        while ($row = $xoopsDB->fetchArray($result)) {
            $uid[] = $row['uid'];
        }
    } else {
        $uid = 0;
    }

    $next_search['term'] = $term;
    $query = trim($term);

    if ( $andor != "EXACT" ) {
        $ignored_queries = array(); // holds kewords that are shorter than allowed minmum length
        $temp_queries = preg_split('/[\s,]+/', $query);
        foreach ($temp_queries as $q) {
            $q = trim($q);
            if (strlen($q) >= $xoopsConfigSearch['keyword_min']) {
                $queries[] = $myts->addSlashes($q);
            } else {
                $ignored_queries[] = $myts->addSlashes($q);
            }
        }
        if (!$uname_required && count($queries) == 0) {
            redirect_header('search.php', 2, sprintf(_SR_KEYTOOSHORT, $xoopsConfigSearch['keyword_min']));
            exit();
        }
    } else {
        //$query = trim($query);
        if (!$uname_required && (strlen($query) < $xoopsConfigSearch['keyword_min'])) {
            redirect_header('search.php', 2, sprintf(_SR_KEYTOOSHORT, $xoopsConfigSearch['keyword_min']));
            exit();
        }
        $queries = array($myts->addSlashes($query));
    }

    // entries must be lowercase
    $allowed = array('t.topic_last_post_id desc', 'p.post_time desc', 't.topic_title', 't.topic_views', 't.topic_replies', 'f.forum_name', 'u.uname');

    $sortby = isset($_POST['sortby']) ? $_POST['sortby'] : (isset($_GET['sortby']) ? $_GET['sortby'] : null);
    $next_search['sortby'] = $sortby;
    $sortby = (in_array(strtolower($sortby), $allowed)) ? $sortby :  't.topic_last_post_id desc';
    $searchin = isset($_POST['searchin']) ? $_POST['searchin'] : (isset($_GET['searchin']) ? $_GET['searchin'] : 'both');
    $next_search['searchin'] = $searchin;
    if (!empty($since)) {
        $subquery = ' AND p.post_time >= ' . (time() - newbb_getSinceTime($since));
    }

    if ($uname_required && (!$uid || count($uid) < 1)) {
        $result = false;
    } else {
        $results = newbb_search($queries, $andor, $limit, $start, $uid, $forum, $sortby, $searchin, $subquery);
    }

    if ( count($results) < 1 ) {
        $xoopsTpl->assign("lang_nomatch", _SR_NOMATCH);
    } else {
        foreach ($results as $row) {
            $xoopsTpl->append('results', array('forum_name' => $myts->htmlSpecialChars($row['forum_name']), 'forum_link' => $row['forum_link'], 'link' => $row['link'], 'title' => $row['title'], 'poster' => $row['poster'], 'post_time' => formatTimestamp($row['time'], "m")));
        }
        unset($results);

        if (count($next_search)>0) {
            $items = array();
            foreach ($next_search as $para => $val) {
                if (!empty($val)) $items[] = "{$para}={$val}";
            }
            if (count($items) > 0) $paras = implode("&",$items);
            unset($next_search);
            unset($items);
        }
        $search_url = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname', 'N') . "/search.php?" . $paras;

        $next_results = newbb_search($queries, $andor, 1, $start + $limit, $uid, $forum, $sortby, $searchin, $subquery);
        $next_count = count($next_results);
        $has_next = false;
        if (is_array($next_results) && $next_count >0) {
            $has_next = true;
        }
        if (false != $has_next) {
            $next = $start + $limit;
            $queries = implode(',', $queries);
            $search_url_next = $search_url . "&start={$next}";
            $search_next = '<a href="' . htmlspecialchars($search_url_next) . '">' . _SR_NEXT . '</a>';
            $xoopsTpl->assign("search_next", $search_next);
        }
        if ( $start > 0 ) {
            $prev = $start - $limit;
            $search_url_prev = $search_url . "&start={$prev}";
            $search_prev = '<a href="' . htmlspecialchars($search_url_prev) . '">' . _SR_PREVIOUS . '</a>';
            $xoopsTpl->assign("search_prev", $search_prev);
        }
    }

    $search_info = _SR_KEYWORDS . ": " . $myts->htmlSpecialChars($term);
    if ($uname_required) {
        $search_info .= "<br />" . _MD_USERNAME . ": " . $myts->htmlSpecialChars($search_username);
    }
    $xoopsTpl->assign("search_info", $search_info);
}

$select_forum = '<select name="forum[]" size="5" multiple="multiple">';
$select_forum .= '<option value="all">' . _MD_SEARCHALLFORUMS . '</option>';
$select_forum .= newbb_forumSelectBox();
$select_forum .= '</select>';
$xoopsTpl->assign_by_ref("forum_selection_box", $select_forum);
$select_since = newbb_sinceSelectBox($xoopsModuleConfig['since_default']);
$xoopsTpl->assign_by_ref("since_selection_box", $select_since);

if ($xoopsConfigSearch['keyword_min'] > 0) {
    $xoopsTpl->assign("search_rule", sprintf(_SR_KEYIGNORE, $xoopsConfigSearch['keyword_min']));
}

include XOOPS_ROOT_PATH . '/footer.php';
?>