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
 * @version         $Id: rss.php 2175 2008-09-23 14:07:03Z phppp $
 */

include_once "header.php";
include_once XOOPS_ROOT_PATH . '/class/template.php';
$xoopsLogger->activated = false;

$forums = array();
$category = intval( @$_GET["c"] );
if (!empty($_GET["f"])) {
    $forums = array_map("intval", array_map("trim", explode("|", $_GET["f"])));
}
$forum_handler =& xoops_getmodulehandler('forum', 'newbb');
$topic_handler =& xoops_getmodulehandler('topic', 'newbb');
$valid_forums = $forum_handler->getIdsByPermission(); // get all accessible forums

if (is_array($forums) && count($forums) > 0 ) {
    $valid_forums = array_intersect($forums, $valid_forums);
} elseif ($category > 0) {
    $crit_top = new CriteriaCompo(new Criteria("cat_id", $category));
    $crit_top->add(new Criteria("forum_id", "(" . implode(", ", $valid_forums) . ")", "IN"));
    $forums_top = $forum_handler->getIds($crit_top);
    $valid_forums = array_intersect($forums_top, $valid_forums);
}
if (count($valid_forums)==0) {
    newbb_trackback_response(1, _NOPERM);
}

$charset = empty($xoopsModuleConfig['rss_utf8']) ? _CHARSET : 'UTF-8';
header ('Content-Type:text/xml; charset=' . $charset);

$tpl = new XoopsTpl();
$tpl->caching = 2;
$tpl->cache_lifetime = $xoopsModuleConfig['rss_cachetime'] * 60;

if (is_object( $xoopsUser )) {
    $groups = $xoopsUser->getGroups();
    sort($groups);
    $contentCacheId = substr( md5(implode(",", $groups) . XOOPS_DB_PASS . XOOPS_DB_NAME), 0, strlen(XOOPS_DB_USER) * 2 );
} else {
    $contentCacheId = XOOPS_GROUP_ANONYMOUS;
}

$xoopsCachedTemplateId = md5( $contentCacheId . str_replace( XOOPS_URL, '', $_SERVER['REQUEST_URI'] ) );
if (!$tpl->is_cached('db:newbb_rss.html', $xoopsCachedTemplateId)) {
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";

    $xmlrss_handler =& xoops_getmodulehandler('xmlrss', 'newbb');
    $rss = $xmlrss_handler->create();

    $rss->setVarRss('channel_title', $xoopsConfig['sitename'] . ' :: ' . _MD_FORUM);
    $rss->channel_link = XOOPS_URL.'/';
    $rss->setVarRss('channel_desc', $xoopsConfig['slogan'] . ' :: ' . $xoopsModule->getInfo('description'));
    // There is a "bug" with xoops function formatTimestamp(time(), 'rss')
    // We have to make a customized function
    //$rss->channel_lastbuild = formatTimestamp(time(), 'rss');
    $rss->setVarRss('channel_lastbuild', newbb_formatTimestamp(time(), 'rss'));
    $rss->channel_webmaster = $xoopsConfig['adminmail'];
    $rss->channel_editor = $xoopsConfig['adminmail'];
    $rss->setVarRss('channel_category', $xoopsModule->getVar('name'));
    $rss->channel_generator = "CBB " . $xoopsModule->getInfo('version');
    $rss->channel_language = _LANGCODE;
    $rss->xml_encoding = $charset;
    $rss->image_url = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . '/' . $xoopsModule->getInfo('image');

    $dimention = @getimagesize(XOOPS_ROOT_PATH . '/modules/' . $xoopsModule->getVar('dirname') . '/' . $xoopsModule->getInfo('image'));
    if (empty($dimention[0])) {
        $width = 88;
    } else {
        $width = ($dimention[0] > 144) ? 144 : $dimention[0];
    }
    if (empty($dimention[1])) {
        $height = 31;
    } else {
        $height = ($dimention[1] > 400) ? 400 : $dimention[1];
    }
    $rss->image_width = $width;
    $rss->image_height = $height;

    $rss->max_items            = $xoopsModuleConfig['rss_maxitems'];
    $rss->max_item_description = $xoopsModuleConfig['rss_maxdescription'];


    $forum_criteria = ' AND t.forum_id IN ('.implode(',',$valid_forums).')';
    unset($valid_forums);
    $approve_criteria = ' AND t.approved = 1 AND p.approved = 1';

    $query = 'SELECT' .
            '    f.forum_id, f.forum_name,' .
            '    t.topic_id, t.topic_title, t.type_id,' .
            '    p.post_id, p.post_time, p.subject, p.uid, p.poster_name, p.post_karma, p.require_reply, ' .
            '    pt.dohtml, pt.dosmiley, pt.doxcode, pt.dobr,' .
            '    pt.post_text'.
            '    FROM ' . $xoopsDB->prefix('bb_posts') . ' AS p' .
            '    LEFT JOIN ' . $xoopsDB->prefix('bb_topics') . ' AS t ON t.topic_last_post_id=p.post_id' .
            '    LEFT JOIN ' . $xoopsDB->prefix('bb_posts_text') . ' AS pt ON pt.post_id=p.post_id' .
            '    LEFT JOIN ' . $xoopsDB->prefix('bb_forums') . ' AS f ON f.forum_id=p.forum_id' .
            '    WHERE 1=1 ' .
                $forum_criteria .
                $approve_criteria .
                ' ORDER BY p.post_id DESC';

    $limit = intval($xoopsModuleConfig['rss_maxitems'] * 1.5);

    if (!$result = $xoopsDB->query($query, $limit)) {
        newbb_trackback_response(1, _MD_ERROR);
        //xoops_error($xoopsDB->error());
        //return $xmlrss_handler->get($rss);
    }
    $rows = array();
    $types = array();
    while ($row = $xoopsDB->fetchArray($result)) {
        $users[$row['uid']] = 1;
        if ($row['type_id'] >0) {
            $types[$row['type_id']] = 1;
        }
        $rows[] = $row;
    }
    if (count($rows) < 1) {
        newbb_trackback_response(1, _MD_ERROR);
        //return $xmlrss_handler->get($rss);
    }
    $users = newbb_getUnameFromIds(array_keys($users), $xoopsModuleConfig['show_realname']);
    if (count($types) > 0) {
        $type_handler =& xoops_getmodulehandler('type', 'newbb');
        $type_list = $type_handler->getList(new Criteria("type_id", "(".implode(", ", array_keys($types)).")", "IN"));
    }
    
    foreach ($rows as $topic) {
        if ( $xoopsModuleConfig['enable_karma'] && $topic['post_karma'] > 0 ) continue;
        if ( $xoopsModuleConfig['allow_require_reply'] && $topic['require_reply']) continue;
        if (!empty($users[$topic['uid']])) {
            $topic['uname'] = $users[$topic['uid']];
        } else {
            $topic['uname'] = ($topic['poster_name']) ? $myts->htmlSpecialChars($topic['poster_name']) : $myts->htmlSpecialChars($GLOBALS["xoopsConfig"]["anonymous"]);
        }
        $description  = $topic["forum_name"]."::";
        $topic['topic_subject'] = empty($type_list[$topic["type_id"]]) ? "" : "[" . $type_list[$topic["type_id"]] . "] ";
        $description  .= $topic['topic_subject'] . $topic['topic_title'] . "<br />\n";
        $description  .= $myts->displayTarea($topic['post_text'], $topic['dohtml'], $topic['dosmiley'], $topic['doxcode'], $topic['dobr']);
        $label = _MD_BY . " " . $topic['uname'];
        $time = newbb_formatTimestamp($topic['post_time'], "rss");
        $link = XOOPS_URL . "/modules/" . $xoopsModule->getVar('dirname') . '/viewtopic.php?topic_id=' . $topic['topic_id'] . '&amp;forum=' . $topic['forum_id'];
        $title = $topic['subject'];
        if (!$rss->addItem($title, $link, $description, $label, $time)) break;
    }
    $rss_feed = $xmlrss_handler->get($rss);

    $tpl->assign('rss', $rss_feed);
    unset($rss);
}
$tpl->display('db:newbb_rss.html', $xoopsCachedTemplateId);
?>