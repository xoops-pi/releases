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
 * @version         $Id: functions.topic.php 2170 2008-09-23 13:40:23Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

/**
 * Create full title of a topic
 *
 * the title is composed of [type_name] if type_id is greater than 0 plus topic_title
 *
 */
function newbb_getTopicTitle($topic_title, $prefix_name = null, $prefix_color = null)
{
    if (empty($prefix_name)) {
        return $topic_title;
    }
    $topic_prefix = $prefix_color ? "<em style=\"font-style: normal; color: {$prefix_color};\">[{$prefix_name}]</em> " : "[{$prefix_name}] ";
    
    return $topic_prefix . $topic_title;
}
?>