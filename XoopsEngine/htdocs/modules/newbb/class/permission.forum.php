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
 * @version         $Id: permission.forum.php 2169 2008-09-23 13:37:10Z phppp $
 */

if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}

if (!class_exists('NewbbPermissionHandler')) {
    require_once dirname(__FILE__) . '/permission.php';
}

if ( defined('FORUM_PERM_ITEMS') && class_exists("NewbbForumPermissionHandler") ) {
    die("access denied");
}
define('FORUM_PERM_ITEMS', 'access,view,post,reply,edit,delete,addpoll,vote,attach,noapprove,type,html,signature');

class NewbbPermissionForumHandler extends NewbbPermissionHandler
{
    function NewbbPermissionForumHandler(&$db)
    {
        $this->NewbbPermissionHandler($db);
    }
    
    function getValidPerms($fullname = false)
    {
        static $validPerms = array();
        if (isset($validPerms[intval($fullname)])) return $validPerms[intval($fullname)];
        $items = array_filter(array_map("trim", explode(",", FORUM_PERM_ITEMS)));
        if (!empty($fullname)) {
            foreach (array_keys($items) as $key) {
                $items[$key] = "forum_".$items[$key];
            }
        }
        $validPerms[intval($fullname)] = $items; 
        return $items;
    }

    function getValidItems($mid, $id = 0)
    {
        static $suspension = array();
        $full_items = array();
        if (empty($mid)) return $full_items;
        
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.user.php";
        $uid = is_object($GLOBALS["xoopsUser"]) ? $GLOBALS["xoopsUser"]->getVar("uid") : 0;
        $ip = newbb_getIP(true);
        if (!empty($GLOBALS["xoopsModuleConfig"]['enable_usermoderate']) && !isset($suspension[$uid][$id]) && !newbb_isAdmin($id)) {
            $moderate_handler =& xoops_getmodulehandler('moderate', 'newbb');
            if ($moderate_handler->verifyUser($uid, "", $id)) {
                $suspension[$uid][$ip][$id] = 1;
            } else {
                $suspension[$uid][$ip][$id] = 0;
            }
        }
    
        $items = $this->getValidPerms();
        foreach ($items as $item) {
            /* skip access for suspended users */
            //if ( !empty($suspension[$uid][$ip][$id]) && in_array($item, array("post", "reply", "edit", "delete", "addpoll", "vote", "attach", "noapprove", "type")) ) continue;
            if ( !empty($suspension[$uid][$ip][$id])  ) continue;
            $full_items[] = "'forum_{$item}'";
        }
        return $full_items;
    }
    
    /*
    * Returns permissions for a certain type
    *
    * @param int $id id of the item (forum, topic or possibly post) to get permissions for
    *
    * @return array
    */
    function getPermissions($id = 0)
    {
        global $xoopsUser;
        
        if (is_object($GLOBALS["xoopsModule"]) && $GLOBALS["xoopsModule"]->getVar("dirname") == "newbb") {
            $modid = $GLOBALS["xoopsModule"]->getVar("mid");
        } else {
            $module_handler =& xoops_gethandler('module');
            $xoopsNewBB =& $module_handler->getByDirname('newbb');
            $modid = $xoopsNewBB->getVar("mid");
            unset($xoopsNewBB);
        }
        
        // Get user's groups
        $groups = is_object($xoopsUser) ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
        // Create string of groupid's separated by commas, inserted in a set of brackets
        if (count($groups) < 1) return false;
        // Create criteria for getting only the permissions regarding this module and this user's groups
        $criteria = new CriteriaCompo(new Criteria('gperm_modid', $modid));
        $criteria->add(new Criteria('gperm_groupid', "(" . implode(',', $groups) . ")", 'IN'));
        if ($id) {
            if (is_array($id)) {
                $criteria->add(new Criteria('gperm_itemid', "(" . implode(',', $id) . ")", 'IN'));
            } else {
                $criteria->add(new Criteria('gperm_itemid', intval($id)));
            }
        }
        $gperm_names = implode( ", ", $this->getValidItems($modid, $id) );
        
        // Add criteria for gpermnames
        $criteria->add(new Criteria('gperm_name', "(" . $gperm_names . ")", 'IN'));
        // Get all permission objects in this module and for this user's groups
        $userpermissions = $this->getObjects($criteria, true);
                    
        // Set the granted permissions to 1
        foreach ($userpermissions as $gperm_id => $gperm) {
            $permissions[$gperm->getVar('gperm_itemid')][$gperm->getVar('gperm_name')] = 1;
        }
        $userpermissions = null;
        unset($userpermissions);
        
        // Return the permission array
        return $permissions;
    }

    function &permission_table($forum = 0, $topic_locked = false, $isadmin = false)
    {
        global $xoopsUser;
        $perm = array();

        if (is_object($forum)) $forum_id = $forum->getVar('forum_id');
        else $forum_id = $forum;

        $permission_set = $this->getPermissions($forum_id);
        
        $perm_items = $this->getValidPerms();
        foreach ($perm_items as $item) {
            if ($item=="access") continue;
            if ($isadmin ||
                (isset($permission_set[$forum_id]['forum_' . $item]) && (!$topic_locked || $item == "view"))
            ) {
                $perm[] = constant('_MD_CAN_' . strtoupper($item));
            } else {
                $perm[] = constant('_MD_CANNOT_' . strtoupper($item));
            }
        }

        return $perm;
    }
    
    function deleteByForum($forum_id)
    {
        $forum_id = intval($forum_id);
        if (empty($forum_id)) return false;
        $gperm_handler =& xoops_gethandler('groupperm');
        $criteria =& new CriteriaCompo(new Criteria('gperm_modid', $GLOBALS["xoopsModule"]->getVar('mid')));
        $items = $this->getValidPerms(true);
        $criteria->add(new Criteria('gperm_name', "('" . implode("', '", $items) . "')", 'IN'));
        $criteria->add(new Criteria('gperm_itemid', $forum_id));
        return $gperm_handler->deleteAll($criteria);
    }
   
    function applyTemplate($forum, $mid = 0)
    {
        if (!$perm_template = $this->getTemplate()) return false;
        
        if (empty($mid)) {
            if (is_object($GLOBALS["xoopsModule"]) && $GLOBALS["xoopsModule"]->getVar("dirname") == "newbb") {
                $mid = $GLOBALS["xoopsModule"]->getVar("mid");
            } else {
                $module_handler =& xoops_gethandler('module');
                $newbb =& $module_handler->getByDirname('newbb');
                $mid = $newbb->getVar("mid");
                unset($newbb);
            }
        }
        
        $member_handler =& xoops_gethandler('member');
        $glist = $member_handler->getGroupList();
        $perms = $this->getValidPerms(true);
        foreach (array_keys($glist) as $group) {
            foreach ($perms as $perm) {
                if (!empty($perm_template[$group][$perm])) {
                    $this->validateRight($perm, $forum, $group, $mid);
                } else {
                    $this->deleteRight($perm, $forum, $group, $mid);
                }
            }
        }
        return true;
    }
    
    function &getTemplate()
    {
        $perms = mod_loadFile("perm_template", "newbb", XOOPS_UPLOAD_PATH . "/newbb");
        return $perms;
    }
    
    function setTemplate($perms)
    {
        return mod_createFile($perms, "perm_template", "newbb", XOOPS_UPLOAD_PATH . "/newbb");
    }
}

?>