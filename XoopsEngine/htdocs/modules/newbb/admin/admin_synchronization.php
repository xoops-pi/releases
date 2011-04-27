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
 * @version         $Id: admin_synchronization.php 2167 2008-09-23 13:33:57Z phppp $
 */

include 'admin_header.php';
xoops_cp_header();

loadModuleAdminMenu(5, _AM_NEWBB_SYNCFORUM);

//if (!empty($_GET['type'])) {
    $start = intval( @$_GET['start'] );
    
    switch( @$_GET['type'] ) {
    case "forum":
        $forum_handler =& xoops_getmodulehandler('forum', 'newbb');
        if ($start >= ($count = $forum_handler->getCount()) ) {
            break;
        }
        if (empty($start)) {
            $mysql_version = version_compare( mysql_get_server_info(), "4.1.0", "ge" );
            /* for MySQL 4.1+ */
            if ($mysql_version):
            $sql =  "    UPDATE " . $xoopsDB->prefix("bb_forums") .
                    "    SET parent_forum = 0" .
                    "    WHERE (parent_forum NOT IN ( SELECT DISTINCT forum_id FROM " . $xoopsDB->prefix("bb_forums") . "))" .
                    "        OR parent_forum = forum_id";
            else:
            // for 4.0+
            /* */
            $sql =  "    UPDATE " . $xoopsDB->prefix("bb_forums") .
                    "    SET parent_forum = 0" .
                    "    LEFT JOIN " . $xoopsDB->prefix("bb_forums") . " AS aa ON " . $xoopsDB->prefix("bb_forums") . ".parent_forum = aa.forum_id " .
                    "    WHERE (aa.forum_id IS NULL)" .
                    "        OR parent_forum = forum_id";
            endif;
            $xoopsDB->queryF($sql);
            
            //$forum_handler->cleanOrphan();
        }
        
        $limit = empty($_GET['limit']) ? 20 : intval($_GET['limit']);
        $criteria = new Criteria("1", 1);
        $criteria->setStart($start);
        $criteria->setLimit($limit);
        $forums_obj = $forum_handler->getAll($criteria);
        $category_handler =& xoops_getmodulehandler('category', 'newbb');
        $cat_ids = $category_handler->getIds();
        foreach (array_keys($forums_obj) as $key) {
            if (!in_array($forums_obj[$key]->getVar("cat_id"), $cat_ids)) {
                $forums_obj[$key]->setVar("cat_id", $cat_ids[0]);
            }
            $sql = "SELECT MAX(post_id) AS last_post, COUNT(*) AS total FROM " . $xoopsDB->prefix("bb_posts") . " AS p LEFT JOIN  " . $xoopsDB->prefix("bb_topics") . " AS t ON p.topic_id=t.topic_id WHERE p.approved=1 AND t.approved=1 AND p.forum_id = " . $key;
            if ( $result = $xoopsDB->query($sql)) {
                $last_post = 0;
                $posts = 0;
                if ( $row = $xoopsDB->fetchArray($result) ) {
                    $last_post = intval($row['last_post']);
                    $posts = intval($row['total']);
                }
                if ($forums_obj[$key]->getVar("forum_last_post_id") != $last_post) {
                    $forums_obj[$key]->setVar("forum_last_post_id", $last_post);
                }
                if ($forums_obj[$key]->getVar("forum_posts") != $posts) {
                    $forums_obj[$key]->setVar("forum_posts", $posts);
                }
            }
            $sql = "SELECT COUNT(*) AS total FROM " . $xoopsDB->prefix("bb_topics") . " WHERE approved=1 AND forum_id = " . $key;
            $result = $xoopsDB->query($sql);
            if ( $row = $xoopsDB->fetchArray($result) ) {
                if ($forums_obj[$key]->getVar("forum_topics") != $row['total']) {
                    $forums_obj[$key]->setVar("forum_topics", $row['total']);
                }
            }
            $forum_handler->insert($forums_obj[$key], true);
        }
        
        redirect_header("admin_synchronization.php?type=forum&amp;start=" . ($start + $limit) . "&amp;limit={$limit}", 2, _AM_NEWBB_SYNCHING . " {$count}: {$start} - " . ($start + $limit));
        exit();
        break;
        
    case "topic":
        $limit = empty($_GET['limit']) ? 1000 : intval($_GET['limit']);
        $topic_handler =& xoops_getmodulehandler('topic', 'newbb');
        if ($start >= ($count = $topic_handler->getCount(new Criteria("approved", 1))) ) {
            break;
        }
        $sql =  "    SELECT topic_id, topic_last_post_id, topic_replies" .
                "    FROM " . $xoopsDB->prefix("bb_topics") . 
                "    WHERE approved=1";
        $result = $xoopsDB->query($sql, $limit, $start);
        while ( list($topic_id, $last_post, $replies) = $xoopsDB->fetchRow($result) ) {
            $sql =  "    SELECT MAX(post_id) AS last_post, COUNT(*) - 1 AS replies " .
                    "    FROM " . $xoopsDB->prefix("bb_posts") . 
                    "    WHERE approved=1 AND topic_id = {$topic_id}";
            $ret = $xoopsDB->query($sql);
            list($_last_post, $_replies) = $xoopsDB->fetchRow($ret);
            if ($_last_post != $last_post || $_replies != $replies) {
                $xoopsDB->queryF(
                        "    UPDATE " . $xoopsDB->prefix("bb_topics") . " SET topic_last_post_id = {$_last_post}, topic_replies = {$_replies}" .
                        "    WHERE topic_id = {$topic_id} "
                        ); 
            }
        }
        
        redirect_header("admin_synchronization.php?type=topic&amp;start=" . ($start + $limit) . "&amp;limit={$limit}", 2, _AM_NEWBB_SYNCHING." {$count}: {$start} - " . ($start + $limit));
        exit();
        break;
        
    case "post":
        $limit = empty($_GET['limit']) ? 1000 : intval($_GET['limit']);
        $post_handler =& xoops_getmodulehandler('post', 'newbb');
        if ($start >= ($count = $post_handler->getCount(new Criteria("approved", 1))) ) {
            break;
        }
        $sql =  "    SELECT topic_id" .
                "    FROM " . $xoopsDB->prefix("bb_topics") . 
                "    WHERE approved=1";
        $result = $xoopsDB->query($sql, $limit, $start);
        while ( list($topic_id) = $xoopsDB->fetchRow($result) ) {
            $sql =  "    SELECT MIN(post_id) AS top_post" .
                    "    FROM " . $xoopsDB->prefix("bb_posts") . 
                    "    WHERE approved=1 AND topic_id = {$topic_id}";
            $ret = $xoopsDB->query($sql);
            list($top_post) = $xoopsDB->fetchRow($ret);
            $sql =  "    UPDATE " . $xoopsDB->prefix("bb_posts") .
                    "    SET pid = 0 " .
                    "    WHERE post_id = " . $top_post;
            $xoopsDB->queryF($sql);
            
            $criteria = new CriteriaCompo(new criteria("topic_id", $topic_id));
            $criteria->add(new criteria("approved", 1));
            $post_ids = $post_handler->getIds($criteria);
            $sql =  "    UPDATE " . $xoopsDB->prefix("bb_posts") .
                    "    SET pid = " . $top_post .
                    "    WHERE" .
                    "         topic_id = {$topic_id}" .
                    "         AND post_id <> " . $top_post .
                    "         AND pid NOT IN (" . implode(", ", $post_ids) . ")";
            $xoopsDB->queryF($sql);
        }
        
        redirect_header("admin_synchronization.php?type=post&amp;start=" . ($start + $limit) . "&amp;limit={$limit}", 2, _AM_NEWBB_SYNCHING . " {$count}: {$start} - " . ($start + $limit));
        exit();
        break;
        
    case "user":
        $limit = empty($_GET['limit']) ? 1000 : intval($_GET['limit']);
        $user_handler =& xoops_gethandler('user');
        if ($start >= ($count = $user_handler->getCount()) ) {
            break;
        }
        $sql =  "    SELECT uid" .
                "    FROM " . $xoopsDB->prefix("users");
        $result = $xoopsDB->query($sql, $limit, $start);
        while ( list($uid) = $xoopsDB->fetchRow($result) ) {
            $sql =  "    SELECT count(*)" .
                    "    FROM " . $xoopsDB->prefix("bb_topics") . 
                    "    WHERE approved=1 AND topic_poster = {$uid}";
            $ret = $xoopsDB->query($sql);
            list($topics) = $xoopsDB->fetchRow($ret);
            
            $sql =  "    SELECT count(*)" .
                    "    FROM " . $xoopsDB->prefix("bb_topics") . 
                    "    WHERE approved=1 AND topic_digest > 0 AND topic_poster = {$uid}";
            $ret = $xoopsDB->query($sql);
            list($digests) = $xoopsDB->fetchRow($ret);
            
            $sql =  "    SELECT count(*), MAX(post_time)" .
                    "    FROM " . $xoopsDB->prefix("bb_posts") . 
                    "    WHERE approved=1 AND uid = {$uid}";
            $ret = $xoopsDB->query($sql);
            list($posts, $lastpost) = $xoopsDB->fetchRow($ret);
            
            $xoopsDB->queryF(
                    "    REPLACE INTO " . $xoopsDB->prefix("bb_user_stats") .
                    "     SET uid = '{$uid}', user_topics = '{$topics}', user_posts = '{$posts}', user_digests = '{$digests}', user_lastpost = '{$lastpost}'"
                    ); 
        }
        
        redirect_header("admin_synchronization.php?type=user&amp;start=" . ($start + $limit) . "&amp;limit={$limit}", 2, _AM_NEWBB_SYNCHING . " {$count}: {$start} - " . ($start + $limit));
        exit();
        break;
        
    case "stats":
        $stats_handler =& xoops_getmodulehandler('stats', 'newbb');
        if (empty($start)) {
            $xoopsDB->queryF("TRUNCATE TABLE " . $stats_handler->table);
        }
        
        $now = time();
        $time_start = array(
                            "day"    => "%Y%j",
                            "week"    => "%Y%u",
                            "month"    => "%Y%m",
                            );
        $counts = array();
                            
        $sql =  "    SELECT forum_id".
                "    FROM " . $xoopsDB->prefix("bb_forums");
        $ret = $xoopsDB->query($sql);
        while ( list($forum_id) = $xoopsDB->fetchRow($ret) ) {
            $sql =  "    SELECT COUNT(*), SUM(topic_views)" .
                    "    FROM " . $xoopsDB->prefix("bb_topics") . 
                    "    WHERE approved=1 AND forum_id = {$forum_id}";
            $result = $xoopsDB->query($sql);
            list($topics, $views) = $xoopsDB->fetchRow($result);
            $stats_handler->update($forum_id, "topic", $topics);
            $stats_handler->update($forum_id, "view", $views);
             
            $sql =  "    SELECT COUNT(*)" .
                    "    FROM " . $xoopsDB->prefix("bb_topics") . 
                    "    WHERE approved=1 AND topic_digest >0 AND forum_id = {$forum_id}";
            $result = $xoopsDB->query($sql);
            list($digests) = $xoopsDB->fetchRow($result);
            $stats_handler->update($forum_id, "digest", $digests);
            
            $sql =  "    SELECT COUNT(*)" .
                    "    FROM " . $xoopsDB->prefix("bb_posts") . 
                    "    WHERE approved=1 AND forum_id = {$forum_id}";
            $result = $xoopsDB->query($sql);
            list($posts) = $xoopsDB->fetchRow($result);
            $stats_handler->update($forum_id, "post", $posts);
            
            
            foreach ($time_start as $period => $format) {                
                $sql =  "    SELECT COUNT(*), SUM(topic_views)" .
                        "    FROM " . $xoopsDB->prefix("bb_topics") . 
                        "    WHERE approved=1 AND forum_id = {$forum_id}" .
                        "        AND FROM_UNIXTIME(topic_time, '{$format}') >= FROM_UNIXTIME({$now}, '{$format}')";
                $result = $xoopsDB->query($sql);
                list($topics, $views) = $xoopsDB->fetchRow($result);
                $xoopsDB->queryF(
                    "    INSERT INTO {$stats_handler->table}" .
                    "        (`stats_id`, `stats_value`, `stats_type`, `stats_period`, `time_update`, `time_format`) " .
                    "    VALUES ".
                    "        ('{$forum_id}', '{$topics}', '" . array_search("topic", $stats_handler->param["type"]) . "', '" . array_search($period, $stats_handler->param["period"]) . "', NOW(), '{$format}')"
                    );
                $xoopsDB->queryF(
                    "    INSERT INTO {$stats_handler->table}" .
                    "        (`stats_id`, `stats_value`, `stats_type`, `stats_period`, `time_update`, `time_format`) " .
                    "    VALUES ".
                    "        ('{$forum_id}', '{$views}', '" . array_search("view", $stats_handler->param["type"]) . "', '" . array_search($period, $stats_handler->param["period"]) . "', NOW(), '{$format}')"
                    );
                @$counts["topic"][$period] += $topics;
                @$counts["view"][$period] += $views;
                
                $sql =  "    SELECT COUNT(*)" .
                        "    FROM " . $xoopsDB->prefix("bb_topics") . 
                        "    WHERE approved=1 AND topic_digest >0 AND forum_id = {$forum_id}" .
                        "        AND FROM_UNIXTIME(digest_time, '{$format}') >= FROM_UNIXTIME({$now}, '{$format}')";
                $result = $xoopsDB->query($sql);
                list($digests) = $xoopsDB->fetchRow($result);
                $xoopsDB->queryF(
                    "    INSERT INTO {$stats_handler->table}" .
                    "        (`stats_id`, `stats_value`, `stats_type`, `stats_period`, `time_update`, `time_format`) " .
                    "    VALUES ".
                    "        ('{$forum_id}', '{$digests}', '" . array_search("digest", $stats_handler->param["type"]) . "', '" . array_search($period, $stats_handler->param["period"]) . "', NOW(), '{$format}')"
                    );
                @$counts["digest"][$period] += $digests;
                    
                $sql =  "    SELECT COUNT(*)".
                        "    FROM " . $xoopsDB->prefix("bb_posts") . 
                        "    WHERE approved=1 AND forum_id = {$forum_id}" .
                        "        AND FROM_UNIXTIME(post_time, '{$format}') >= FROM_UNIXTIME({$now}, '{$format}')";
                $result = $xoopsDB->query($sql);
                list($posts) = $xoopsDB->fetchRow($result);
                $xoopsDB->queryF(
                    "    INSERT INTO {$stats_handler->table}" .
                    "        (`stats_id`, `stats_value`, `stats_type`, `stats_period`, `time_update`, `time_format`) " .
                    "    VALUES " .
                    "        ('{$forum_id}', '{$posts}', '" . array_search("post", $stats_handler->param["type"]) . "', '" . array_search($period, $stats_handler->param["period"]) . "', NOW(), '{$format}')"
                    );
                @$counts["post"][$period] += $posts;
            }
            
        }
       
        $xoopsDB->queryF(
            "    DELETE FROM {$stats_handler->table}" .
            "    WHERE stats_id = '0' AND stats_period <> " . array_search("total", $stats_handler->param["period"])
            );
        foreach ($time_start as $period => $format) {
            foreach (array_keys($counts) as $type) {
                $xoopsDB->queryF(
                    "    INSERT INTO {$stats_handler->table}" .
                    "        (`stats_id`, `stats_value`, `stats_type`, `stats_period`, `time_update`, `time_format`) " .
                    "    VALUES " .
                    "        ('0', '{$counts[$type][$period]}', '" . array_search($type, $stats_handler->param["type"]) . "', '" . array_search($period, $stats_handler->param["period"]) . "', NOW(), '{$format}')"
                    );
            }
        }
        break;
        
    case "misc":
    default:
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.recon.php";
        newbb_synchronization();
        break;
    }
    

$form = '<fieldset><legend style="font-weight: bold; color: #900;">' . _AM_NEWBB_SYNCFORUM . '</legend>';

$form .= '<form action="admin_synchronization.php" method="get">';
$form .= '<div style="padding: 10px 2px;">';
$form .= '<h2>' . _AM_NEWBB_SYNC_TYPE_FORUM . '</h2>';
$form .= '<input type="hidden" name="type" value="forum">';
$form .= _AM_NEWBB_SYNC_ITEMS . '<input type="text" name="limit" value="20">';
$form .= '<input type="submit" name="submit" value=' . _SUBMIT . ' />';
$form .= '</div>';
$form .= '</form>';

$form .= '<form action="admin_synchronization.php" method="get">';
$form .= '<div style="padding: 10px 2px;">';
$form .= '<h2>' . _AM_NEWBB_SYNC_TYPE_TOPIC . '</h2>';
$form .= '<input type="hidden" name="type" value="topic">';
$form .= _AM_NEWBB_SYNC_ITEMS . '<input type="text" name="limit" value="1000">';
$form .= '<input type="submit" name="submit" value=' . _SUBMIT . ' />';
$form .= '</div>';
$form .= '</form>';

$form .= '<form action="admin_synchronization.php" method="get">';
$form .= '<div style="padding: 10px 2px;">';
$form .= '<h2>' . _AM_NEWBB_SYNC_TYPE_POST . '</h2>';
$form .= '<input type="hidden" name="type" value="post">';
$form .= _AM_NEWBB_SYNC_ITEMS . '<input type="text" name="limit" value="1000">';
$form .= '<input type="submit" name="submit" value=' . _SUBMIT . ' />';
$form .= '</div>';
$form .= '</form>';

$form .= '<form action="admin_synchronization.php" method="get">';
$form .= '<div style="padding: 10px 2px;">';
$form .= '<h2>' . _AM_NEWBB_SYNC_TYPE_USER . '</h2>';
$form .= '<input type="hidden" name="type" value="user">';
$form .= _AM_NEWBB_SYNC_ITEMS . '<input type="text" name="limit" value="1000">';
$form .= '<input type="submit" name="submit" value=' . _SUBMIT . ' />';
$form .= '</div>';
$form .= '</form>';

$form .= '<form action="admin_synchronization.php" method="get">';
$form .= '<div style="padding: 10px 2px;">';
$form .= '<h2>' . _AM_NEWBB_SYNC_TYPE_STATS . '</h2>';
$form .= '<input type="hidden" name="type" value="stats">';
$form .= '<input type="submit" name="submit" value=' . _SUBMIT . ' />';
$form .= '</div>';
$form .= '</form>';

$form .= '<form action="admin_synchronization.php" method="get">';
$form .= '<div style="padding: 10px 2px;">';
$form .= '<h2>' . _AM_NEWBB_SYNC_TYPE_MISC . '</h2>';
$form .= '<input type="hidden" name="type" value="misc">';
$form .= '<input type="submit" name="submit" value=' . _SUBMIT . ' />';
$form .= '</div>';
$form .= '</form>';

$form .= "</fieldset>";

echo $form;
xoops_cp_footer();
?>