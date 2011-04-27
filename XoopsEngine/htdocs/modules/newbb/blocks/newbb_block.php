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
 * @version         $Id: newbb_block.php 2168 2008-09-23 13:34:39Z phppp $
 */
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.config.php";

function b_newbb_array_filter($var)
{
    return $var > 0;
}

// options[0] - Citeria valid: time(by default)
// options[1] - NumberToDisplay: any positive integer
// options[2] - TimeDuration: negative for hours, positive for days, for instance, -5 for 5 hours and 5 for 5 days
// options[3] - DisplayMode: 0-full view;1-compact view;2-lite view
// options[4] - Display Navigator: 1 (by default), 0 (No)
// options[5] - Title Length : 0 - no limit
// options[6] - SelectedForumIDs: null for all

function b_newbb_show($options)
{
    global $xoopsConfig;
    global $access_forums;
    global $xoopsLogger;
    $xoopsLogger->startTime( 'XOOPS output block - bb' );

    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";
    $myts =& MyTextSanitizer::getInstance();
    $block = array();
    $i = 0;
    $order = "";
    $extra_criteria = "";
    if (!empty($options[2])) {
        $extra_criteria .= " AND t.topic_time>". ( time() - newbb_getSinceTime($options[2]) );
    }
    switch ($options[0]) {
        case 'time':
        default:
            $order = 't.topic_last_post_id';
            break;
    }
$xoopsLogger->startTime( 'XOOPS output block - bb - config' );
    $newbbConfig = newbb_loadConfig();
$xoopsLogger->stopTime( 'XOOPS output block - bb - config' );

                
    if (!isset($access_forums)) {
        $perm_handler =& xoops_getmodulehandler('permission', 'newbb');
        if ( !$access_forums = $perm_handler->getForums()  ) {
            return $block;
        }
    }
    if (!empty($options[6])) {
        $allowedforums = array_filter(array_slice($options, 6), "b_newbb_array_filter"); // get allowed forums
        $allowed_forums = array_intersect($allowedforums, $access_forums);
    } else {
        $allowed_forums = $access_forums;
    }
    if (empty($allowed_forums)) return $block;

    $forum_criteria = ' AND t.forum_id IN (' . implode(',', $allowed_forums) . ')';
    $approve_criteria = ' AND t.approved = 1';
    
    $query = 'SELECT'.
            '    t.topic_id, t.topic_replies, t.forum_id, t.topic_title, t.topic_views, t.type_id,'.
            '    f.forum_name,'.
            '    p.post_id, p.post_time, p.icon, p.uid, p.poster_name'.
            '    FROM ' . $GLOBALS["xoopsDB"]->prefix('bb_topics') . ' AS t '.
            '    LEFT JOIN ' . $GLOBALS["xoopsDB"]->prefix('bb_posts') . ' AS p ON t.topic_last_post_id=p.post_id'.
            '    LEFT JOIN ' . $GLOBALS["xoopsDB"]->prefix('bb_forums') . ' AS f ON f.forum_id=t.forum_id'.
            '    WHERE 1=1 ' .
                $forum_criteria .
                $approve_criteria .
                $extra_criteria .
                ' ORDER BY ' . $order . ' DESC';
    
$xoopsLogger->startTime( 'XOOPS output block - bb - query' );
    $result = $GLOBALS["xoopsDB"]->query($query, $options[1], 0);
$xoopsLogger->stopTime( 'XOOPS output block - bb - query' );

    if (!$result) {
        //xoops_error($GLOBALS["xoopsDB"]->error());
        return false;
    }
    $block['disp_mode'] = $options[3]; // 0 - full view; 1 - compact view; 2 - lite view;
    $rows = array();
    $author = array();
    $types = array();
    
    while ($row = $GLOBALS["xoopsDB"]->fetchArray($result)) {
        $rows[] = $row;
        $author[$row["uid"]] = 1;
        if ($row['type_id'] >0) {
            $types[$row['type_id']] = 1;
        }
    }

    if (count($rows) < 1) return $block;
    
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.user.php";
    $author_name = newbb_getUnameFromIds(array_keys($author), $newbbConfig['show_realname'], true);

$xoopsLogger->startTime( 'XOOPS output block - bb - type' );
    if (count($types) > 0) {
        $type_handler =& xoops_getmodulehandler('type', 'newbb');
        $type_list = $type_handler->getList(new Criteria("type_id", "(".implode(", ", array_keys($types)).")", "IN"));
    }
$xoopsLogger->stopTime( 'XOOPS output block - bb - type' );

$xoopsLogger->startTime( 'XOOPS output block - bb - assign' );
    foreach ($rows as $arr) {
        $topic_page_jump = '';
        $topic['topic_subject'] = empty($type_list[$arr["type_id"]])?"":"[".$type_list[$arr["type_id"]]."] ";
        
        $topic['post_id'] = $arr['post_id'];
        $topic['forum_id'] = $arr['forum_id'];
        $topic['forum_name'] = $myts->htmlSpecialChars($arr['forum_name']);
        $topic['id'] = $arr['topic_id'];

        $title = $myts->htmlSpecialChars($arr['topic_title']);
        if (!empty($options[5])) {
            $title = xoops_substr($title, 0, $options[5]);
        }
        $topic['title'] = $title;
        $topic['replies'] = $arr['topic_replies'];
        $topic['views'] = $arr['topic_views'];
        $topic['time'] = newbb_formatTimestamp($arr['post_time']);
        if (!empty($author_name[$arr['uid']])) {
            $topic_poster = $author_name[$arr['uid']];
        } else {
            $topic_poster = $myts->htmlSpecialChars( ($arr['poster_name'])?$arr['poster_name']:$GLOBALS["xoopsConfig"]["anonymous"] );
        }
        $topic['topic_poster'] = $topic_poster;
        $topic['topic_page_jump'] = $topic_page_jump;
        $block['topics'][] = $topic;
        unset($topic);
    }
$xoopsLogger->stopTime( 'XOOPS output block - bb - assign' );
    $block['indexNav'] = intval($options[4]);
$xoopsLogger->stopTime( 'XOOPS output block - bb' );

    return $block;
}

// options[0] - Citeria valid: time(by default), views, replies, digest, sticky
// options[1] - NumberToDisplay: any positive integer
// options[2] - TimeDuration: negative for hours, positive for days, for instance, -5 for 5 hours and 5 for 5 days
// options[3] - DisplayMode: 0-full view;1-compact view;2-lite view
// options[4] - Display Navigator: 1 (by default), 0 (No)
// options[5] - Title Length : 0 - no limit
// options[6] - SelectedForumIDs: null for all

function b_newbb_topic_show($options)
{
    global $xoopsConfig;
    global $access_forums;

global $xoopsLogger;
$xoopsLogger->startTime( 'XOOPS output block - topic' );
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";
    $myts =& MyTextSanitizer::getInstance();
    $block = array();
    $i = 0;
    $order = "";
    $extra_criteria = "";
    $time_criteria = null;
    if (!empty($options[2])) {
        $time_criteria = time() - newbb_getSinceTime($options[2]);
        $extra_criteria = " AND t.topic_time>".$time_criteria;
    }
    switch ($options[0]) {
        case 'views':
            $order = 't.topic_views';
            break;
        case 'replies':
            $order = 't.topic_replies';
            break;
        case 'digest':
            $order = 't.digest_time';
            $extra_criteria = " AND t.topic_digest=1";
            if ($time_criteria) {
                $extra_criteria .= " AND t.digest_time>".$time_criteria;
            }
            break;
        case 'sticky':
            $order = 't.topic_id';
            $extra_criteria .= " AND t.topic_sticky=1";
            break;
        case 'time':
        default:
            $order = 't.topic_id';
            break;
    }
    $newbbConfig = newbb_loadConfig();

$xoopsLogger->startTime( 'XOOPS output block - topic - permission' );
    if (!isset($access_forums)) {
        $perm_handler =& xoops_getmodulehandler('permission', 'newbb');
        if ( !$access_forums = $perm_handler->getForums()  ) {
            return $block;
        }
    }

    if (!empty($options[6])) {
        $allowedforums = array_filter(array_slice($options, 6), "b_newbb_array_filter"); // get allowed forums
        $allowed_forums = array_intersect($allowedforums, $access_forums);
    } else {
        $allowed_forums = $access_forums;
    }
$xoopsLogger->stopTime( 'XOOPS output block - topic - permission' );
    if (empty($allowed_forums)) return false;

$xoopsLogger->startTime( 'XOOPS output block - topic - query' );
    $forum_criteria = ' AND t.forum_id IN (' . implode(',', $allowed_forums) . ')';
    $approve_criteria = ' AND t.approved = 1';

    $query = 'SELECT'.
            '    t.topic_id, t.topic_replies, t.forum_id, t.topic_title, t.topic_views, t.type_id, t.topic_time, t.topic_poster, t.poster_name,'.
            '    f.forum_name'.
            '    FROM ' . $GLOBALS["xoopsDB"]->prefix('bb_topics') . ' AS t '.
            '    LEFT JOIN ' . $GLOBALS["xoopsDB"]->prefix('bb_forums') . ' AS f ON f.forum_id=t.forum_id'.
            '    WHERE 1=1 ' .
                $forum_criteria .
                $approve_criteria .
                $extra_criteria .
                ' ORDER BY ' . $order . ' DESC';

    $result = $GLOBALS["xoopsDB"]->query($query, $options[1], 0);
$xoopsLogger->stopTime( 'XOOPS output block - topic - query' );
$xoopsLogger->startTime( 'XOOPS output block - topic - assign' );

    if (!$result) {
        //xoops_error($GLOBALS["xoopsDB"]->error());
        return $block;
    }
    $block['disp_mode'] = $options[3]; // 0 - full view; 1 - compact view; 2 - lite view;
    $rows = array();
    $author = array();
    $types = array();
    while ($row = $GLOBALS["xoopsDB"]->fetchArray($result)) {
        $rows[] = $row;
        $author[$row["topic_poster"]] = 1;
        if ($row['type_id'] >0) {
            $types[$row['type_id']] = 1;
        }
    }
    if (count($rows) < 1) return $block;
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.user.php";
    $author_name = newbb_getUnameFromIds(array_keys($author), $newbbConfig['show_realname'], true);
    if (count($types) > 0) {
        $type_handler =& xoops_getmodulehandler('type', 'newbb');
        $type_list = $type_handler->getList(new Criteria("type_id", "(".implode(", ", array_keys($types)).")", "IN"));
    }

    foreach ($rows as $arr) {
        $topic_page_jump = '';
        $topic['topic_subject'] = empty($type_list[$arr["type_id"]])?"":"[".$type_list[$arr["type_id"]]."] ";
        $topic['forum_id'] = $arr['forum_id'];
        $topic['forum_name'] = $myts->htmlSpecialChars($arr['forum_name']);
        $topic['id'] = $arr['topic_id'];

        $title = $myts->htmlSpecialChars($arr['topic_title']);
        if (!empty($options[5])) {
            $title = xoops_substr($title, 0, $options[5]);
        }
        $topic['title'] = $title;
        $topic['replies'] = $arr['topic_replies'];
        $topic['views'] = $arr['topic_views'];
        $topic['time'] = newbb_formatTimestamp($arr['topic_time']);
        if (!empty($author_name[$arr['topic_poster']])) {
            $topic_poster = $author_name[$arr['topic_poster']];
        } else {
            $topic_poster = $myts->htmlSpecialChars( ($arr['poster_name'])?$arr['poster_name']:$GLOBALS["xoopsConfig"]["anonymous"] );
        }
        $topic['topic_poster'] = $topic_poster;
        $topic['topic_page_jump'] = $topic_page_jump;
        $block['topics'][] = $topic;
        unset($topic);
    }
$xoopsLogger->stopTime( 'XOOPS output block - topic - assign' );

    $block['indexNav'] = intval($options[4]);
$xoopsLogger->stopTime( 'XOOPS output block - topic' );

    return $block;
}

// options[0] - Citeria valid: title(by default), text
// options[1] - NumberToDisplay: any positive integer
// options[2] - TimeDuration: negative for hours, positive for days, for instance, -5 for 5 hours and 5 for 5 days
// options[3] - DisplayMode: 0-full view;1-compact view;2-lite view; Only valid for "time"
// options[4] - Display Navigator: 1 (by default), 0 (No)
// options[5] - Title/Text Length : 0 - no limit
// options[6] - SelectedForumIDs: null for all

function b_newbb_post_show($options)
{
    global $xoopsConfig;
    global $access_forums;

    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";
    $myts =& MyTextSanitizer::getInstance();
    $block = array();
    $i = 0;
    $order = "";
    $extra_criteria = "";
    $time_criteria = null;
    if (!empty($options[2])) {
        $time_criteria = time() - newbb_getSinceTime($options[2]);
        $extra_criteria = " AND p.post_time>".$time_criteria;
    }
    
    switch ($options[0]) {
        case "text":
            if (!empty($newbbConfig['enable_karma']))
                $extra_criteria .= " AND p.post_karma = 0";
            if (!empty($newbbConfig['allow_require_reply']))
                $extra_criteria .= " AND p.require_reply = 0";        
        default:
            $order = 'p.post_id';
            break;
    }
    $newbbConfig = newbb_loadConfig();

    if (!isset($access_forums)) {
        $perm_handler =& xoops_getmodulehandler('permission', 'newbb');
        if ( !$access_forums = $perm_handler->getForums()  ) {
            return $block;
        }
    }

    if (!empty($options[6])) {
        $allowedforums = array_filter(array_slice($options, 6), "b_newbb_array_filter"); // get allowed forums
        $allowed_forums = array_intersect($allowedforums, $access_forums);
    } else {
        $allowed_forums = $access_forums;
    }
    if (empty($allowed_forums)) return $block;

    $forum_criteria = ' AND p.forum_id IN (' . implode(',', $allowed_forums) . ')';
    $approve_criteria = ' AND p.approved = 1';

    $query = 'SELECT';
    $query .= '    p.post_id, p.subject, p.post_time, p.icon, p.uid, p.poster_name,';
    if ($options[0]=="text") {
        $query .= '    pt.dohtml, pt.dosmiley, pt.doxcode, pt.dobr, pt.post_text,';    
    }
    $query .= '    f.forum_id, f.forum_name'.
            '    FROM ' . $GLOBALS["xoopsDB"]->prefix('bb_posts') . ' AS p '.
            '    LEFT JOIN ' . $GLOBALS["xoopsDB"]->prefix('bb_forums') . ' AS f ON f.forum_id=p.forum_id';
    if ($options[0]=="text") {
        $query .= '    LEFT JOIN ' . $GLOBALS["xoopsDB"]->prefix('bb_posts_text') . ' AS pt ON pt.post_id=p.post_id';
    }
    $query .= '    WHERE 1=1 ' .
                $forum_criteria .
                $approve_criteria .
                $extra_criteria .
                ' ORDER BY ' . $order . ' DESC';

    $result = $GLOBALS["xoopsDB"]->query($query, $options[1], 0);
    if (!$result) {
        //xoops_error($GLOBALS["xoopsDB"]->error());
        return $block;
    }
    $block['disp_mode'] = ($options[0] == "text") ? 3 : $options[3]; // 0 - full view; 1 - compact view; 2 - lite view;
    $rows = array();
    $author = array();
    while ($row = $GLOBALS["xoopsDB"]->fetchArray($result)) {
        $rows[] = $row;
        $author[$row["uid"]] = 1;
    }
    if (count($rows) < 1) return $block;
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.user.php";
    $author_name = newbb_getUnameFromIds(array_keys($author), $newbbConfig['show_realname'], true);

    foreach ($rows as $arr) {
        //if ($arr['icon'] && is_file(XOOPS_ROOT_PATH . "/images/subject/" . $arr['icon'])) {
        if (!empty($arr['icon'])) {
            $last_post_icon = '<img src="' . XOOPS_URL . '/images/subject/' . htmlspecialchars($arr['icon']) . '" alt="" />';
        } else {
            $last_post_icon = '<img src="' . XOOPS_URL . '/images/subject/icon1.gif" alt="" />';
        }
        //$topic['jump_post'] = "<a href='" . XOOPS_URL . "/modules/newbb/viewtopic.php?post_id=" . $arr['post_id'] ."#forumpost" . $arr['post_id'] . "'>" . $last_post_icon . "</a>";
        $topic['forum_id'] = $arr['forum_id'];
        $topic['forum_name'] = $myts->htmlSpecialChars($arr['forum_name']);
        //$topic['id'] = $arr['topic_id'];

        $title = $myts->htmlSpecialChars($arr['subject']);
        if ($options[0]!="text" && !empty($options[5])) {
            $title = xoops_substr($title, 0, $options[5]);
        }
        $topic['title'] = $title;
        $topic['post_id'] = $arr['post_id'];
        $topic['time'] = newbb_formatTimestamp($arr['post_time']);
        if (!empty($author_name[$arr['uid']])) {
            $topic_poster = $author_name[$arr['uid']];
        } else {
            $topic_poster = $myts->htmlSpecialChars( ($arr['poster_name'])?$arr['poster_name']:$GLOBALS["xoopsConfig"]["anonymous"] );
        }
        $topic['topic_poster'] = $topic_poster;
        
        if ($options[0]=="text") {
            $post_text = $myts->displayTarea($arr['post_text'], $arr['dohtml'], $arr['dosmiley'], $arr['doxcode'], 1, $arr['dobr']);
            if (!empty($options[5])) {
                $post_text = xoops_substr(newbb_html2text($post_text), 0, $options[5]);
            }
            $topic['post_text'] = $post_text;
        }        
        
        $block['topics'][] = $topic;
        unset($topic);
    }
    $block['indexNav'] = intval($options[4]);
    return $block;
}

// options[0] - Citeria valid: post(by default), topic, digest, sticky
// options[1] - NumberToDisplay: any positive integer
// options[2] - TimeDuration: negative for hours, positive for days, for instance, -5 for 5 hours and 5 for 5 days
// options[3] - DisplayMode: 0-full view;1-compact view;
// options[4] - Display Navigator: 1 (by default), 0 (No)
// options[5] - Title Length : 0 - no limit
// options[6] - SelectedForumIDs: null for all

function b_newbb_author_show($options)
{
    global $xoopsConfig;
    global $access_forums;

    $myts =& MyTextSanitizer::getInstance();
    $block = array();
    $i = 0;
    $type = "topic";
    $order = "count";
    $extra_criteria = "";
    $time_criteria = null;
    if (!empty($options[2])) {
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";
        $time_criteria = time() - newbb_getSinceTime($options[2]);
        $extra_criteria = " AND topic_time > " . $time_criteria;
    }
    switch ($options[0]) {
        case 'topic':
            break;
        case 'digest':
            $extra_criteria = " AND topic_digest = 1";
            if ($time_criteria) {
                $extra_criteria .= " AND digest_time > " . $time_criteria;
            }
            break;
        case 'sticky':
            $extra_criteria .= " AND topic_sticky = 1";
            break;
        case 'post':
        default:
            $type = "post";
            if ($time_criteria) {
                $extra_criteria = " AND post_time > ". $time_criteria;
            }
            break;
    }
    $newbbConfig = newbb_loadConfig();

    if (!isset($access_forums)) {
        $perm_handler =& xoops_getmodulehandler('permission', 'newbb');
        if ( !$access_forums = $perm_handler->getForums()  ) {
            return $block;
        }
    }

    if (!empty($options[5])) {
        $allowedforums = array_filter(array_slice($options, 5), "b_newbb_array_filter"); // get allowed forums
        $allowed_forums = array_intersect($allowedforums, $access_forums);
    } else {
        $allowed_forums = $access_forums;
    }
    if (empty($allowed_forums)) return false;

    if ($type=="topic") {
        $forum_criteria = ' AND forum_id IN (' . implode(',', $allowed_forums) . ')';
        $approve_criteria = ' AND approved = 1';
        $query = 'SELECT DISTINCT topic_poster AS author, COUNT(*) AS count
                    FROM ' . $GLOBALS["xoopsDB"]->prefix('bb_topics') . '
                    WHERE topic_poster>0 ' .
                    $forum_criteria .
                    $approve_criteria .
                    $extra_criteria .
                    ' GROUP BY topic_poster ORDER BY ' . $order . ' DESC';
    } else {
        $forum_criteria = ' AND forum_id IN (' . implode(',', $allowed_forums) . ')';
        $approve_criteria = ' AND approved = 1';
        $query = 'SELECT DISTINCT uid AS author, COUNT(*) AS count
                    FROM ' . $GLOBALS["xoopsDB"]->prefix('bb_posts') . '
                    WHERE uid > 0 ' .
                    $forum_criteria .
                    $approve_criteria .
                    $extra_criteria .
                    ' GROUP BY uid ORDER BY ' . $order . ' DESC';
    }

    $result = $GLOBALS["xoopsDB"]->query($query, $options[1], 0);
    if (!$result) {
        //xoops_error($GLOBALS["xoopsDB"]->error());
        return $block;
    }
    $author = array();
    while ($row = $GLOBALS["xoopsDB"]->fetchArray($result)) {
        $author[$row["author"]]["count"] = $row["count"];
    }
    if (count($author) < 1) return $block;
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.user.php";
    $author_name = newbb_getUnameFromIds(array_keys($author), $newbbConfig['show_realname']);
    foreach (array_keys($author) as $uid) {
        $author[$uid]["name"] = $myts->htmlSpecialChars($author_name[$uid]);
    }
    $block['authors'] =& $author;
    $block['disp_mode'] = $options[3]; // 0 - full view; 1 - lite view;
    $block['indexNav'] = intval($options[4]);
    return $block;
}

function b_newbb_edit($options)
{
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.forum.php";
    
    $form  = _MB_NEWBB_CRITERIA."<select name='options[0]'>";
    $form .= "<option value='time'";
    if ($options[0]=="time") $form .= " selected='selected' ";
    $form .= ">"._MB_NEWBB_CRITERIA_TIME."</option>";
    $form .= "</select>";
    $form .= "<br />" . _MB_NEWBB_DISPLAY."<input type='text' name='options[1]' value='" . $options[1] . "' />";
    $form .= "<br />" . _MB_NEWBB_TIME."<input type='text' name='options[2]' value='" . $options[2] . "' />";
    $form .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;<small>" . _MB_NEWBB_TIME_DESC. "</small>";
    $form .= "<br />" . _MB_NEWBB_DISPLAYMODE. "<input type='radio' name='options[3]' value='0'";
    if ($options[3] == 0) {
        $form .= " checked='checked'";
    }
    $form .= " />&nbsp;" . _MB_NEWBB_DISPLAYMODE_FULL . "<input type='radio' name='options[3]' value='1'";
    if ($options[3] == 1) {
        $form .= " checked='checked'";
    }
    $form .= " />&nbsp;" . _MB_NEWBB_DISPLAYMODE_COMPACT . "<input type='radio' name='options[3]' value='2'";
    if ($options[3] == 2) {
        $form .= " checked='checked'";
    }
    $form .= " />&nbsp;" . _MB_NEWBB_DISPLAYMODE_LITE;

    $form .= "<br />" . _MB_NEWBB_INDEXNAV."<input type=\"radio\" name=\"options[4]\" value=\"1\"";
    if ($options[4] == 1) $form .= " checked=\"checked\"";
    $form .= " />"._YES."<input type=\"radio\" name=\"options[4]\" value=\"0\"";
    if ($options[4] == 0) $form .= " checked=\"checked\"";
    $form .= " />"._NO;

    $form .= "<br />" . _MB_NEWBB_TITLE_LENGTH."<input type='text' name='options[5]' value='" . $options[5] . "' />";

    $form .= "<br /><br />" . _MB_NEWBB_FORUMLIST;

    $options_forum = array_filter(array_slice($options, 6), "b_newbb_array_filter"); // get allowed forums
    $isAll = (count($options_forum) == 0 || empty($options_forum[0]));
    $form .= "<br />&nbsp;&nbsp;<select name=\"options[]\" multiple=\"multiple\">";
    $form .= "<option value=\"0\" ";
    if ($isAll) $form .= " selected=\"selected\"";
    $form .= ">"._ALL."</option>";
    $form .= newbb_forumSelectBox($options_forum);
    $form .= "</select><br />";

    return $form;
}

function b_newbb_topic_edit($options)
{
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.forum.php";
    $form  = _MB_NEWBB_CRITERIA."<select name='options[0]'>";
    $form .= "<option value='time'";
        if ($options[0]=="time") $form .= " selected='selected' ";
        $form .= ">"._MB_NEWBB_CRITERIA_TIME."</option>";
    $form .= "<option value='views'";
        if ($options[0]=="views") $form .= " selected='selected' ";
        $form .= ">"._MB_NEWBB_CRITERIA_VIEWS."</option>";
    $form .= "<option value='replies'";
        if ($options[0]=="replies") $form .= " selected='selected' ";
        $form .= ">"._MB_NEWBB_CRITERIA_REPLIES."</option>";
    $form .= "<option value='digest'";
        if ($options[0]=="digest") $form .= " selected='selected' ";
        $form .= ">"._MB_NEWBB_CRITERIA_DIGEST."</option>";
    $form .= "<option value='sticky'";
        if ($options[0]=="sticky") $form .= " selected='selected' ";
        $form .= ">"._MB_NEWBB_CRITERIA_STICKY."</option>";
    $form .= "</select>";
    $form .= "<br />" . _MB_NEWBB_DISPLAY."<input type='text' name='options[1]' value='" . $options[1] . "' />";
    $form .= "<br />" . _MB_NEWBB_TIME."<input type='text' name='options[2]' value='" . $options[2] . "' />";
    $form .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;<small>" . _MB_NEWBB_TIME_DESC. "</small>";
    $form .= "<br />" . _MB_NEWBB_DISPLAYMODE. "<input type='radio' name='options[3]' value='0'";
    if ($options[3] == 0) {
        $form .= " checked='checked'";
    }
    $form .= " />&nbsp;" . _MB_NEWBB_DISPLAYMODE_FULL . "<input type='radio' name='options[3]' value='1'";
    if ($options[3] == 1) {
        $form .= " checked='checked'";
    }
    $form .= " />&nbsp;" . _MB_NEWBB_DISPLAYMODE_COMPACT . "<input type='radio' name='options[3]' value='2'";
    if ($options[3] == 2) {
        $form .= " checked='checked'";
    }
    $form .= " />&nbsp;" . _MB_NEWBB_DISPLAYMODE_LITE;

    $form .= "<br />" . _MB_NEWBB_INDEXNAV."<input type=\"radio\" name=\"options[4]\" value=\"1\"";
    if ($options[4] == 1) $form .= " checked=\"checked\"";
    $form .= " />"._YES."<input type=\"radio\" name=\"options[4]\" value=\"0\"";
    if ($options[4] == 0) $form .= " checked=\"checked\"";
    $form .= " />"._NO;

    $form .= "<br />" . _MB_NEWBB_TITLE_LENGTH."<input type='text' name='options[5]' value='" . $options[5] . "' />";

    $form .= "<br /><br />" . _MB_NEWBB_FORUMLIST;

    $options_forum = array_filter(array_slice($options, 6), "b_newbb_array_filter"); // get allowed forums
    
    $isAll = (count($options_forum)==0 || empty($options_forum[0]))? true:false;
    $form .= "<br />&nbsp;&nbsp;<select name=\"options[]\" multiple=\"multiple\">";
    $form .= "<option value=\"0\" ";
    if ($isAll) $form .= " selected=\"selected\"";
    $form .= ">"._ALL."</option>";
    $form .= newbb_forumSelectBox($options_forum);
    $form .= "</select><br />";

    return $form;
}

function b_newbb_post_edit($options)
{
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.forum.php";
    $form  = _MB_NEWBB_CRITERIA."<select name='options[0]'>";
    $form .= "<option value='title'";
        if ($options[0]=="title") $form .= " selected='selected' ";
        $form .= ">"._MB_NEWBB_CRITERIA_TITLE."</option>";
    $form .= "<option value='text'";
        if ($options[0]=="text") $form .= " selected='selected' ";
        $form .= ">"._MB_NEWBB_CRITERIA_TEXT."</option>";
    $form  .= "</select>";
    $form .= "<br />" . _MB_NEWBB_DISPLAY."<input type='text' name='options[1]' value='" . $options[1] . "' />";
    $form .= "<br />" . _MB_NEWBB_TIME."<input type='text' name='options[2]' value='" . $options[2] . "' />";
    $form .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;<small>" . _MB_NEWBB_TIME_DESC. "</small>";
    $form .= "<br />" . _MB_NEWBB_DISPLAYMODE. "<input type='radio' name='options[3]' value='0'";
    if ($options[3] == 0) {
        $form .= " checked='checked'";
    }
    $form .= " />&nbsp;" . _MB_NEWBB_DISPLAYMODE_FULL . "<input type='radio' name='options[3]' value='1'";
    if ($options[3] == 1) {
        $form .= " checked='checked'";
    }
    $form .= " />&nbsp;" . _MB_NEWBB_DISPLAYMODE_COMPACT . "<input type='radio' name='options[3]' value='2'";
    if ($options[3] == 2) {
        $form .= " checked='checked'";
    }
    $form .= " />&nbsp;" . _MB_NEWBB_DISPLAYMODE_LITE;

    $form .= "<br />" . _MB_NEWBB_INDEXNAV."<input type=\"radio\" name=\"options[4]\" value=\"1\"";
    if ($options[4] == 1) $form .= " checked=\"checked\"";
    $form .= " />"._YES."<input type=\"radio\" name=\"options[4]\" value=\"0\"";
    if ($options[4] == 0) $form .= " checked=\"checked\"";
    $form .= " />"._NO;

    $form .= "<br />" . _MB_NEWBB_TITLE_LENGTH."<input type='text' name='options[5]' value='" . $options[5] . "' />";

    $form .= "<br /><br />" . _MB_NEWBB_FORUMLIST;

    $options_forum = array_filter(array_slice($options, 6), "b_newbb_array_filter"); // get allowed forums
    $isAll = (count($options_forum)==0||empty($options_forum[0]))?true:false;
    $form .= "<br />&nbsp;&nbsp;<select name=\"options[]\" multiple=\"multiple\">";
    $form .= "<option value=\"0\" ";
    if ($isAll) $form .= " selected=\"selected\"";
    $form .= ">"._ALL."</option>";
    $form .= newbb_forumSelectBox($options_forum);
    $form .= "</select><br />";

    return $form;
}

function b_newbb_author_edit($options)
{
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.forum.php";
    $form  = _MB_NEWBB_CRITERIA."<select name='options[0]'>";
    $form .= "<option value='post'";
        if ($options[0]=="post") $form .= " selected='selected' ";
        $form .= ">"._MB_NEWBB_CRITERIA_POST."</option>";
    $form .= "<option value='topic'";
        if ($options[0]=="topic") $form .= " selected='selected' ";
        $form .= ">"._MB_NEWBB_CRITERIA_TOPIC."</option>";
    $form .= "<option value='digest'";
        if ($options[0]=="digest") $form .= " selected='selected' ";
        $form .= ">"._MB_NEWBB_CRITERIA_DIGESTS."</option>";
    $form .= "<option value='sticky'";
        if ($options[0]=="sticky") $form .= " selected='selected' ";
        $form .= ">"._MB_NEWBB_CRITERIA_STICKYS."</option>";
    $form .= "</select>";
    $form .= "<br />" . _MB_NEWBB_DISPLAY."<input type='text' name='options[1]' value='" . $options[1] . "' />";
    $form .= "<br />" . _MB_NEWBB_TIME."<input type='text' name='options[2]' value='" . $options[2] . "' />";
    $form .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;<small>" . _MB_NEWBB_TIME_DESC. "</small>";
    $form .= "<br />" . _MB_NEWBB_DISPLAYMODE. "<input type='radio' name='options[3]' value='0'";
    if ($options[3] == 0) {
        $form .= " checked='checked'";
    }
    $form .= " />&nbsp;" . _MB_NEWBB_DISPLAYMODE_COMPACT . "<input type='radio' name='options[3]' value='1'";
    if ($options[3] == 1) {
        $form .= " checked='checked'";
    }
    $form .= " />&nbsp;" . _MB_NEWBB_DISPLAYMODE_LITE;

    $form .= "<br />" . _MB_NEWBB_INDEXNAV."<input type=\"radio\" name=\"options[4]\" value=\"1\"";
    if ($options[4] == 1) $form .= " checked=\"checked\"";
    $form .= " />"._YES."<input type=\"radio\" name=\"options[4]\" value=\"0\"";
    if ($options[4] == 0) $form .= " checked=\"checked\"";
    $form .= " />"._NO;

    $form .= "<br /><br />" . _MB_NEWBB_FORUMLIST;

    $options_forum = array_filter(array_slice($options, 5), "b_newbb_array_filter"); // get allowed forums
    $isAll = (count($options_forum)==0||empty($options_forum[0]))?true:false;
    $form .= "<br />&nbsp;&nbsp;<select name=\"options[]\" multiple=\"multiple\">";
    $form .= "<option value=\"0\" ";
    if ($isAll) $form .= " selected=\"selected\"";
    $form .= ">"._ALL."</option>";
    $form .= newbb_forumSelectBox($options_forum);
    $form .= "</select><br />";

    return $form;
}
?>