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
 * @version         $Id: module.v400.php 2170 2008-09-23 13:40:23Z phppp $
 */

function xoops_module_update_newbb_v400(&$module) 
{
    $stats_handler =& xoops_getmodulehandler('stats', 'newbb');
    
    $result = $GLOBALS['xoopsDB']->query( "SELECT `forum_id`, `forum_topics`, `forum_posts` FROM ".$GLOBALS['xoopsDB']->prefix("bb_forums") );
    while($row = $GLOBALS['xoopsDB']->fetchArray($result)) {
        $stats_handler->update($row["forum_id"], "topic", $row["forum_topics"]);
        $stats_handler->update($row["forum_id"], "post", $row["forum_posts"]);
    }
    $result = $GLOBALS['xoopsDB']->query( "SELECT `forum_id`, SUM(topic_views) AS views FROM ".$GLOBALS['xoopsDB']->prefix("bb_topics") . " GROUP BY `forum_id`" );
    while($row = $GLOBALS['xoopsDB']->fetchArray($result)) {
        $stats_handler->update($row["forum_id"], "view", $row["views"]);
    }
    $result = $GLOBALS['xoopsDB']->query( "SELECT `forum_id`, COUNT(*) AS digests FROM ".$GLOBALS['xoopsDB']->prefix("bb_topics") . " WHERE topic_digest = 1 GROUP BY `forum_id`" );
    while($row = $GLOBALS['xoopsDB']->fetchArray($result)) {
        $stats_handler->update($row["forum_id"], "digest", $row["digests"]);
    }
    $result = $GLOBALS['xoopsDB']->query( "SELECT SUM(forum_topics) AS topics, SUM(forum_posts) AS posts FROM ".$GLOBALS['xoopsDB']->prefix("bb_forums") );
    while($row = $GLOBALS['xoopsDB']->fetchArray($result)) {
        $stats_handler->update(-1, "topic", $row["topics"]);
        $stats_handler->update(-1, "post", $row["posts"]);
    }
    
    /*
    $GLOBALS['xoopsDB']->queryF(
            "    INSERT INTO ".$GLOBALS['xoopsDB']->prefix("bb_stats").
            "        (`id`, `value`, `type`, `period`, `time_update`, `time_format`)".
            "    SELECT `forum_id`, `forum_topics`, '".NEWBB_STATS_TYPE_TOPIC."', '".NEWBB_STATS_PERIOD_TOTAL."', NOW() + 0, ''".
            "         FROM ".$GLOBALS['xoopsDB']->prefix("bb_forums")
            );
    $GLOBALS['xoopsDB']->queryF(
            "    INSERT INTO ".$GLOBALS['xoopsDB']->prefix("bb_stats").
            "        (`id`, `value`, `type`, `period`, `time_update`, `time_format`)".
            "    SELECT `forum_id`, `forum_posts`, '".NEWBB_STATS_TYPE_POST."', '".NEWBB_STATS_PERIOD_TOTAL."', NOW() + 0, ''".
            "         FROM ".$GLOBALS['xoopsDB']->prefix("bb_forums")
            );
    $GLOBALS['xoopsDB']->queryF(
            "    INSERT INTO ".$GLOBALS['xoopsDB']->prefix("bb_stats").
            "        (`id`, `value`, `type`, `period`, `time_update`, `time_format`)".
            "    SELECT `forum_id`, count(*), '".NEWBB_STATS_TYPE_DIGEST."', '".NEWBB_STATS_PERIOD_TOTAL."', NOW() + 0, ''".
            "         FROM ".$GLOBALS['xoopsDB']->prefix("bb_topics").
            "        WHERE topic_digest = 1".
            "        GROUP BY `forum_id`".
            );
    $GLOBALS['xoopsDB']->queryF(
            "    INSERT INTO ".$GLOBALS['xoopsDB']->prefix("bb_stats").
            "        (`id`, `value`, `type`, `period`, `time_update`, `time_format`)".
            "    SELECT `forum_id`, SUM(topic_views), '".NEWBB_STATS_TYPE_VIEW."', '".NEWBB_STATS_PERIOD_TOTAL."', NOW() + 0, ''".
            "         FROM ".$GLOBALS['xoopsDB']->prefix("bb_topics").
            "        WHERE topic_digest = 1".
            "        GROUP BY `forum_id`".
            );
    */
    
    $sql =        "    UPDATE " . $GLOBALS['xoopsDB']->prefix("bb_posts_text") . " AS t, " . $GLOBALS['xoopsDB']->prefix("bb_posts") . " AS p" .
                "    SET t.dohtml = p.dohtml, " .
                "        t.dosmiley = p.dosmiley, " .
                "        t.doxcode = p.doxcode, " .
                "        t.doimage = p.doimage, " .
                "        t.dobr = p.dobr" .
                "    WHERE p.post_id =t.post_id ";
    if ( $GLOBALS['xoopsDB']->queryF($sql) ) {
        $sql =    "    ALTER TABLE " . $GLOBALS['xoopsDB']->prefix("bb_posts") .
                "        DROP `dohtml`," .
                "        DROP `dosmiley`," .
                "        DROP `doxcode`," .
                "        DROP `doimage`," .
                "        DROP `dobr`";
        $GLOBALS['xoopsDB']->queryF( $sql );
    } else {
        xoops_error($GLOBALS['xoopsDB']->error() . "<br />" . $sql);
    }
    
    @include_once XOOPS_ROOT_PATH . "/modules/tag/include/functions.php";
    if ( function_exists("tag_getTagHandler") && $tag_handler =& tag_getTagHandler() ) {
        $table_topic = $GLOBALS['xoopsDB']->prefix("bb_topics");
        
        $sql =    "    SELECT topic_id, topic_tags" .
                "    FROM {$table_topic}";
        if ( ($result = $GLOBALS['xoopsDB']->query($sql)) == false) {
            xoops_error($GLOBALS['xoopsDB']->error());
        }
        while($myrow = $GLOBALS['xoopsDB']->fetchArray($result)) {
            if (empty($myrow["topic_tags"])) continue;
            $tag_handler->updateByItem($myrow["topic_tags"], $myrow["topic_id"], $module->getVar("mid"));
        }
    }

    
    if (!$GLOBALS['xoopsDB']->query("
            SELECT COUNT(*)    
            FROM " . $GLOBALS['xoopsDB']->prefix("bb_type_tmp") . " AS a, " . $GLOBALS['xoopsDB']->prefix("bb_type_forum_tmp") . " AS b
            WHERE a.type_id = b.type_id AND a.type_id >0;
        ")
    ) {
        xoops_error($GLOBALS['xoopsDB']->error());
        $GLOBALS['xoopsDB']->queryF("DROP TABLE " . $GLOBALS['xoopsDB']->prefix("bb_type_tmp"));
        $GLOBALS['xoopsDB']->queryF("DROP TABLE " . $GLOBALS['xoopsDB']->prefix("bb_type_forum_tmp"));
        return true;
    }
    
    $GLOBALS['xoopsDB']->queryF(
            "    INSERT INTO ".$GLOBALS['xoopsDB']->prefix("bb_type") .
            "        (`type_id`, `type_name`, `type_color`)" .
            "    SELECT `type_id`, `type_name`, `type_color`" .
            "         FROM ".$GLOBALS['xoopsDB']->prefix("bb_type_tmp")
            );
    $GLOBALS['xoopsDB']->queryF(
            "    INSERT INTO ".$GLOBALS['xoopsDB']->prefix("bb_type_forum") .
            "        (`type_id`, `forum_id`, `type_order`)" .
            "    SELECT `type_id`, `forum_id`, `type_order`" .
            "         FROM ".$GLOBALS['xoopsDB']->prefix("bb_type_forum_tmp")
            );
    
    $GLOBALS['xoopsDB']->queryF("DROP TABLE " . $GLOBALS['xoopsDB']->prefix("bb_type_tmp"));
    $GLOBALS['xoopsDB']->queryF("DROP TABLE " . $GLOBALS['xoopsDB']->prefix("bb_type_forum_tmp"));
    
    // TODO: convert IP from numeric format to string format
    
    return true;
}
?>