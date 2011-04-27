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
 * @version         $Id: userstats.php 2169 2008-09-23 13:37:10Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

class NewbbUserstats extends XoopsObject 
{
    function NewbbUserstats()
    {
        //$this->ArtObject("bb_user_stats");
        $this->initVar('uid',                 XOBJ_DTYPE_INT);
        $this->initVar('user_topics',         XOBJ_DTYPE_INT);
        $this->initVar('user_digests',         XOBJ_DTYPE_INT);
        $this->initVar('user_posts',         XOBJ_DTYPE_INT);
        $this->initVar('user_lastpost',     XOBJ_DTYPE_INT);
    }
}



/**
 * user stats
 *
 */
class NewbbUserstatsHandler extends XoopsPersistableObjectHandler
{
    function NewbbUserstatsHandler(&$db)
    {
        $this->XoopsPersistableObjectHandler($db, 'bb_user_stats', 'NewbbUserstats', 'uid');
    }
    
    function &instance($db = null)
    {
        static $instance;
        if (!isset($instance)) {
            $instance =& new NewbbUserstatsHandler($db);
        }
        return $instance;
    }
    
    function getStats($id)
    {
        if (empty($id)) return null;
        $sql = "SELECT * FROM {$this->table} WHERE {$this->keyName} = " . intval($id);
        if (!$result = $this->db->query($sql)) {
            return null;
        }
        $row = $this->db->fetchArray($result);

        return $row;
    }
    
    function insert(&$object, $force = true)
    {
        if (!$object->isDirty()) {
            $object->setErrors("not isDirty");
            return $object->getVar($this->keyName);
        }
        $this->_loadHandler("write");
        if (!$changedVars = $this->_handler["write"]->cleanVars($object)) {
            $object->setErrors("cleanVars failed");
            return $object->getVar($this->keyName);
        }
        $queryFunc = empty($force) ? "query" : "queryF";
        
        $keys = array();
        foreach ($changedVars as $k => $v) {
            $keys[] = " {$k} = {$v}";
        }
        $sql = "REPLACE INTO " . $this->table . " SET " . implode(",", $keys);
        if (!$result = $this->db->{$queryFunc}($sql)) {
            $object->setErrors("update object error:" . $sql);
            return false;
        }
        unset($changedVars);
        return $object->getVar($this->keyName);
    }
}

?>