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
 * @version         $Id: permission.category.php 2169 2008-09-23 13:37:10Z phppp $
 */

if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}

if (!class_exists('NewbbPermissionHandler')) {
    require_once dirname(__FILE__) . '/permission.php';
}

class NewbbPermissionCategoryHandler extends NewbbPermissionHandler
{
    function NewbbPermissionCategoryHandler(&$db)
    {
        $this->NewbbPermissionHandler($db);
    }
    
    function getValidItems($mid, $id = 0)
    {
        $full_items = array();
        if (empty($mid)) return $full_items;
        
        $full_items[] = "'category_access'";
        return $full_items;
    }
    
    function deleteByCategory($cat_id)
    {
        $cat_id = intval($cat_id);
        if (empty($cat_id)) return false;
        $gperm_handler =& xoops_gethandler('groupperm');
        $criteria =& new CriteriaCompo(new Criteria('gperm_modid', $GLOBALS["xoopsModule"]->getVar('mid')));
        $criteria->add(new Criteria('gperm_name', 'category_access'));
        $criteria->add(new Criteria('gperm_itemid', $cat_id));
        return $gperm_handler->deleteAll($criteria);
    }

    function setCategoryPermission($category, $groups = array())
    {
        if (is_object($GLOBALS["xoopsModule"]) && $GLOBALS["xoopsModule"]->getVar("dirname") == "newbb") {
            $mid = $GLOBALS["xoopsModule"]->getVar("mid");
        } else {
            $module_handler =& xoops_gethandler('module');
            $newbb =& $module_handler->getByDirname('newbb');
            $mid = $newbb->getVar("mid");
        }
        if (empty($groups)) {
            $member_handler =& xoops_gethandler('member');
            $glist = $member_handler->getGroupList();
            $groups = array_keys($glist);
        }
        $ids = $this->getGroupIds("category_access", $category, $mid);
        $ids_add = array_diff($groups, $ids);
        $ids_rmv = array_diff($ids, $groups);
        foreach ($ids_add as $group) {
            $this->addRight("category_access", $category, $group, $mid);
        }
        foreach ($ids_rmv as $group) {
            $this->deleteRight("category_access", $category, $group, $mid);
        }
        
        return true;
    }
}

?>