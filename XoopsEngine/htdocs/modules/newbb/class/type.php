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
 * @version         $Id: type.php 2169 2008-09-23 13:37:10Z phppp $
 */

 
if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}

class NewbbType extends XoopsObject 
{
    function NewbbType()
    {
        //$this->ArtObject("bb_type");
        $this->initVar('type_id',             XOBJ_DTYPE_INT);
        $this->initVar('type_name',         XOBJ_DTYPE_TXTBOX,    "");
        $this->initVar('type_color',         XOBJ_DTYPE_SOURCE,    "");
        $this->initVar('type_description',     XOBJ_DTYPE_TXTBOX,    "");
        //$this->initVar('type_order',         XOBJ_DTYPE_INT,        99);
    }
}

class NewbbTypeHandler extends XoopsPersistableObjectHandler
{
    function NewbbTypeHandler(&$db)
    {
        $this->XoopsPersistableObjectHandler($db, 'bb_type', 'NewbbType', 'type_id', 'type_name');
    }
    
    /**
     * Get types linked to a forum
     * 
     * @param    mixed    $forums        single forum ID or an array of forum IDs
     * @return     array    associative array of types (name, color, order)
     */
    function getByForum($forums = null)
    {
        $ret = array();
        
        $forums = ( is_array($forums) 
                    ? array_filter(array_map("intval", array_map("trim", $forums))) 
                    : ( empty($forums)
                        ? 0
                        : array(intval($forums))
                    )
                );
        
        $sql = "    SELECT o.type_id, o.type_name, o.type_color, l.type_order" .
                "     FROM " . $this->db->prefix("bb_type_forum") . " AS l " .
                "         LEFT JOIN {$this->table} AS o ON o.{$this->keyName} = l.{$this->keyName} " .
                "     WHERE " .
                "        l.forum_id " . ( empty($forums) ? "IS NOT NULL" : "IN (" . implode(", ", $forums) . ")") .
                "         ORDER BY l.type_order ASC"
                ;
        if ( ($result = $this->db->query($sql)) == false) {
            return $ret;
        }
        
        while ($myrow = $this->db->fetchArray($result)) {
            $ret[$myrow[$this->keyName]] = array(
                "type_id"       => $myrow[$this->keyName],
                "type_order"    => $myrow["type_order"],
                "type_name"     => htmlspecialchars($myrow["type_name"]),
                "type_color"    => htmlspecialchars($myrow["type_color"]),
                );
        }
        return $ret;
    }
    
    /**
     * Update types linked to a forum
     * 
     * @param    integer    $forum_id
     * @param    array    $types
     * @return     boolean
     */
    function updateByForum($forum_id, $types)
    {
        $forum_id = intval($forum_id);
        if (empty($forum_id)) return false;

        $types_existing = $this->getByForum($forum_id);
        $types_valid    = array();
        $types_add      = array();
        $types_update   = array();
        foreach (array_keys($types_existing) as $key) {
            if (empty($types[$key])) {
                continue;
            }
            $types_valid[] = $key;
            if ($types[$key] != $types_existing[$key]["type_order"]) {
                $types_update[] = $key;
            }
        }
        foreach (array_keys($types) as $key) {
            if (!empty($types[$key]) && !isset($types_existing[$key])) {
                $types_add[] = $key;
            }
        }
        $types_valid    = array_filter( $types_valid );
        $types_add      = array_filter( $types_add );
        $types_update   = array_filter( $types_update );
      
        if (!empty($types_valid)) {
            $sql = "DELETE FROM " . $this->db->prefix("bb_type_forum") .
                    " WHERE " .
                    "     {$this->keyName} NOT IN (" . implode(", ", $types_valid) . ")"; 
            if ( ($result = $this->db->queryF($sql)) == false) {
            }
        }
        
        if (!empty($types_update)) {
            $type_query = array();
            foreach ($types_update as $key) {
                $order = $types[$key];
                if ($types_existing[$key]["type_order"] == $order) continue;
                $sql = "UPDATE " . $this->db->prefix("bb_type_forum") .
                        " SET type_order = {$order}" .
                        " WHERE  {$this->keyName} = {$key} AND forum_id = {$forum_id}"; 
                if ( ($result = $this->db->queryF($sql)) == false) {
                }
            }
        }
        
        if (!empty($types_add)) {
            $type_query = array();
            foreach ($types_add as $key) {
                $order = $types[$key];
                //if (!in_array($key, $types_add)) continue;
                $type_query[] = "({$key}, {$forum_id}, {$order})";
            }
            $sql = "INSERT INTO " . $this->db->prefix("bb_type_forum") .
                    " (type_id, forum_id, type_order) " .
                    " VALUES ". implode(", ", $type_query);
            if ( ($result = $this->db->queryF($sql)) == false) {
                //xoops_error($this->db->error());
            }
        }
        
        return true;
    }
    
    /**
     * delete an object as well as links relying on it
     * 
     * @param    object    $object        {@link NewbbType}
     * @param     bool     $force         flag to force the query execution despite security settings
     * @return     bool
     */
    function delete(&$object, $force = true)
    {
        if (!is_object($object) || !$object->getVar($this->keyName)) return false;
        $queryFunc = empty($force)?"query":"queryF";
        
        /*
         * Remove forum-type links
         */
        $sql = "DELETE" .
                " FROM " . $this->db->prefix("bb_type_forum") . 
                " WHERE  " . $this->keyName . " = " . $object->getVar($this->keyName);
        if ( ($result = $this->db->{$queryFunc}($sql)) == false) {
           // xoops_error($this->db->error());
        }
        
        /*
         * Reset topic type linked to this type
         */
        $sql = "UPATE" .
                " " . $this->db->prefix("bb_topics") . 
                " SET {$this->keyName}=0" .
                " WHERE {$this->keyName} = " . $object->getVar($this->keyName);
        if ( ($result = $this->db->{$queryFunc}($sql)) == false) {
            //xoops_error($this->db->error());
        }
        
        return parent::delete($object, $force);
    }

    /**
     * clean orphan links from database
     * 
     * @return     bool    true on success
     */
    function cleanOrphan()
    {
        /* clear forum-type links */
        if (version_compare( mysql_get_server_info(), "4.1.0", "ge" )):
        $sql = "DELETE FROM " . $this->db->prefix("bb_type_forum") .
                " WHERE ({$this->keyName} NOT IN ( SELECT DISTINCT {$this->keyName} FROM {$this->table}) )";
        else:
        $sql =  "DELETE " . $this->db->prefix("bb_type_forum") . " FROM " . $this->db->prefix("bb_type_forum") .
                " LEFT JOIN {$this->table} AS aa ON " . $this->db->prefix("bb_type_forum") . ".{$this->keyName} = aa.{$this->keyName} " .
                " WHERE (aa.{$this->keyName} IS NULL)";
        endif;
        if (!$result = $this->db->queryF($sql)) {
            //xoops_error($this->db->error());
        }
        
        /* reconcile topic-type link */
        if (version_compare( mysql_get_server_info(), "4.1.0", "ge" )):
        $sql = "UPATE " . $this->db->prefix("bb_topics") . 
                " SET {$this->keyName} = 0" .
                " WHERE ({$this->keyName} NOT IN ( SELECT DISTINCT {$this->keyName} FROM {$this->table}) )";
        else:
        $sql =  "UPATE " . $this->db->prefix("bb_topics") . " FROM " . $this->db->prefix("bb_type_forum") .
                " SET " . $this->db->prefix("bb_topics") . ".{$this->keyName} = 0" .
                " LEFT JOIN {$this->table} AS aa ON " . $this->db->prefix("bb_topics") . ".{$this->keyName} = aa.{$this->keyName} " .
                " WHERE (aa.{$this->keyName} IS NULL)";
        endif;
        if (!$result = $this->db->queryF($sql)) {
            //xoops_error($this->db->error());
        }
        
        return true;
    }
}

?>