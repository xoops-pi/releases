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
 * @version         $Id: module.v220.php 2170 2008-09-23 13:40:23Z phppp $
 */

function xoops_module_update_newbb_v220(&$module) 
{
    $perms=array('post','view','reply','edit','delete','addpoll','vote','attach','noapprove');
    foreach ($perms as $perm) {
        $sql = "UPDATE ".$GLOBALS['xoopsDB']->prefix('group_permission')." SET gperm_name='forum_".$perm."' WHERE gperm_name='forum_can_".$perm."'";
        $result = $GLOBALS['xoopsDB']->queryF($sql);
        if (!$result) {
            /* Shouldn't setErrors from here, otherwise the update will be failed in cleanVars check */
            $module->setErrors("Could not change ".$perm.": ".$sql);
        }
    }
    $sql = "UPDATE ".$GLOBALS['xoopsDB']->prefix('group_permission')." SET gperm_name='forum_access' WHERE gperm_name='global_forum_access'";
    $result = $GLOBALS['xoopsDB']->queryF($sql);
    if (!$result) {
        /* Shouldn't setErrors from here, otherwise the update will be failed in cleanVars check */
        $module->setErrors("Could not change forum_access");
    }
    $sql = "UPDATE ".$GLOBALS['xoopsDB']->prefix('group_permission')." SET gperm_name='category_access' WHERE gperm_name='forum_cat_access'";
    $result = $GLOBALS['xoopsDB']->queryF($sql);
    if (!$result) {
        $module->setErrors("Could not change category_access");
    }
    
    $sql = "SELECT forum_id, forum_moderator FROM ".$GLOBALS['xoopsDB']->prefix('bb_forums');
    $result = $GLOBALS['xoopsDB']->query($sql);
    while ($row = $GLOBALS['xoopsDB']->fetchArray($result)) {
        $mods = explode(" ", $row["forum_moderator"]);
        $mods = is_array($mods)?serialize($mods):serialize(array());
        $sql_sub = "UPDATE ".$GLOBALS['xoopsDB']->prefix('bb_forums')." SET forum_moderator='".$mods."' WHERE forum_id=".$row["forum_id"];
        $result_sub = $GLOBALS['xoopsDB']->queryF($sql_sub);
        if (!$result) {
            $module->setErrors("Could not forum_moderator for forum ".$row["forum_id"]);
        }
    }
    return true;
}
?>