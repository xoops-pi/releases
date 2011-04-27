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
 * @version         $Id: stats.php 2169 2008-09-23 13:37:10Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

define("NEWBB_STATS_TYPE_TOPIC",    1);
define("NEWBB_STATS_TYPE_POST",     2);
define("NEWBB_STATS_TYPE_DIGEST",   3);
define("NEWBB_STATS_TYPE_VIEW",     4);

define("NEWBB_STATS_PERIOD_TOTAL",  1);
define("NEWBB_STATS_PERIOD_DAY",    2);
define("NEWBB_STATS_PERIOD_WEEK",   3);
define("NEWBB_STATS_PERIOD_MONTH",  4);


/**
 * Stats for forum
 *
 */
class NewbbStatsHandler
{
    var $db;
    var $table;
    var $param = array (
            "type"      => array("topic", "post", "digest", "view"),
            "period"    => array("total", "day", "week", "month"),
        );
    
    function NewbbStatsHandler($db = null)
    {
        if (!$db) {
            $this->db = $GLOBALS["xoopsDB"];
        } else {
            $this->db = $db;
        }
        $this->table = $this->db->prefix("bb_stats");
    }
    
    function &instance($db = null)
    {
        static $instance;
        if (!isset($instance)) {
            $instance =& new NewbbStatsHandler($db);
        }
        return $instance;
    }
    
    function update($id, $type, $increment = 1)
    {
        $id = intval($id);
        $increment = intval($increment);
        
        if (empty($increment) || false === ( $type = array_search($type, $this->param["type"]) )) {
            return false;
        }
        
        $sql =  "    UPDATE {$this->table}" .
                "    SET stats_value = CASE " .
                "                    WHEN time_format = '' OR DATE_FORMAT(time_update, time_format) = DATE_FORMAT(NOW(), time_format)  THEN stats_value + '{$increment}' " .
                "                    ELSE '{$increment}' " .
                "                END, " .
                "        time_update = NOW()" .
                "    WHERE " .
                "        (stats_id = '0' OR stats_id = '{$id}') " .
                "        AND stats_type='{$type}' "
                ;
        $result = $this->db->queryF($sql);
        $rows = $this->db->getAffectedRows();
        if ($rows == 0) {
            $sql =  "    INSERT INTO {$this->table}" .
                    "        (`stats_id`, `stats_value`, `stats_type`, `stats_period`, `time_update`, `time_format`) " .
                    "    VALUES " .
                    "        ('0', '{$increment}', '{$type}', '" . array_search("total", $this->param["period"]) . "', NOW(), ''), " .
                    "        ('0', '{$increment}', '{$type}', '" . array_search("day", $this->param["period"]) . "', NOW(), '%Y%j'), " .
                    "        ('0', '{$increment}', '{$type}', '" . array_search("week", $this->param["period"]) . "', NOW(), '%Y%u'), " .
                    "        ('0', '{$increment}', '{$type}', '" . array_search("month", $this->param["period"]) . "', NOW(), '%Y%m')"
                    ;
            $result = $this->db->queryF($sql);
        }
        if ($rows < 2 * count($this->param["period"]) && !empty($id)) {
            $sql =    "    INSERT INTO {$this->table}" .
                    "        (`stats_id`, `stats_value`, `stats_type`, `stats_period`, `time_update`, `time_format`) " .
                    "    VALUES " .
                    "        ('{$id}', '{$increment}', '{$type}', '" . array_search("total", $this->param["period"]) . "', NOW(), ''), " .
                    "        ('{$id}', '{$increment}', '{$type}', '" . array_search("day", $this->param["period"]) . "', NOW(), '%Y%j'), " .
                    "        ('{$id}', '{$increment}', '{$type}', '" . array_search("week", $this->param["period"]) . "', NOW(), '%Y%u'), " .
                    "        ('{$id}', '{$increment}', '{$type}', '" . array_search("month", $this->param["period"]) . "', NOW(), '%Y%m')"
                    ;
            $result = $this->db->queryF($sql);
        }
    }
    
    /**
     * Get stats of "Today"
     *
     * @param array    $ids        ID of forum: > 0, forum; 0 - global; empty - all
     * @param array    $types        type of stats items: 1 - topic; 2 - post; 3 - digest; 4 - click; empty - all
     * @param array    $periods    time period: 1 - all time; 2 - today; 3 - this week; 4 - this month; empty - all
     */
    function getStats($ids = array(), $types = array(), $periods = array())
    {
        $ret = array();
        
        $_types = array();
        foreach ($types as $type) {
            $_types[] = array_search($type, $this->param["type"]);
        }
        $_periods = array();
        foreach ($periods as $period) {
            $_periods[] = array_search($period, $this->param["period"]);
        }
        $sql =  "    SELECT stats_id, stats_value, stats_type, stats_period " .
                "    FROM {$this->table} " .
                "    WHERE " .
                "        ( time_format = '' OR DATE_FORMAT(time_update, time_format) = DATE_FORMAT(NOW(), time_format) ) " .
                "        " . (empty($ids) ? "" : "AND stats_id IN (" . implode(", ", array_map("intval", $ids)) . ")") .
                "        " . (empty($_types) ? "" : "AND stats_type IN (" . implode(", ", $_types) . ")") .
                "        " . (empty($_periods) ? "" : "AND stats_period IN (" . implode(", ", $_periods) . ")")
                ;
        $result = $this->db->query($sql);
        
        while($row = $this->db->fetchArray($result)) {
            $ret[ strval($row["stats_id"]) ][ $this->param["type"][$row["stats_type"]] ][ $this->param["period"][$row["stats_period"]] ] = $row["stats_value"]; 
        }
        return $ret;
    }
}

?>