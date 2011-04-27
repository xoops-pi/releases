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
 * @version         $Id: readtopic.php 2169 2008-09-23 13:37:10Z phppp $
 */
include_once dirname(__FILE__) . '/read.php';

/**
 * A handler for read/unread handling
 * 
 * @package     newbb/cbb
 * 
 * @author        D.J. (phppp, http://xoopsforge.com)
 * @copyright    copyright (c) 2005 XOOPS.org
 */

class Readtopic extends Read 
{
    function Readtopic()
    {
        $this->Read("topic");
    }
}

class NewbbReadtopicHandler extends NewbbReadHandler
{
    /**
     * maximum records per forum for one user.
     * assigned from $xoopsModuleConfig["read_items"]
     *
     * @var integer
     */
    var $items_per_forum;
    
    function NewbbReadtopicHandler(&$db)
    {
        $this->NewbbReadHandler($db, "topic");
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.config.php";
        $newbbConfig = newbb_loadConfig();
        $this->items_per_forum = isset($newbbConfig["read_items"]) ? intval($newbbConfig["read_items"]) : 100;
    }
    
    /**
     * clean orphan items from database
     * 
     * @return     bool    true on success
     */
    function cleanOrphan()
    {
        parent::cleanOrphan($this->db->prefix("bb_posts"), "post_id");
        return parent::cleanOrphan($this->db->prefix("bb_topics"), "topic_id", "read_item");
    }    

    /**
     * Clear garbage
     * 
     * Delete all expired and duplicated records
     */
    function clearGarbage()
    {
        parent::clearGarbage();
        
        // TODO: clearItemsExceedMaximumItemsPerForum
        return true;
    }
    
    function setRead_items($status = 0, $forum_id = 0, $uid = null)
    {
        if (empty($this->mode)) return true;
        
        if ($this->mode == 1) return $this->setRead_items_cookie($status, $forum_id);
        else return $this->setRead_items_db($status, $forum_id, $uid);
    }
        
    function setRead_items_cookie($status, $forum_id)
    {
        $cookie_name = "LT";
        $cookie_vars = newbb_getcookie($cookie_name, true);
        
        $item_handler =& xoops_getmodulehandler('topic', 'newbb');
        $criteria =& new CriteriaCompo(new Criteria("forum_id", $forum_id));
        $criteria->setSort("topic_last_post_id");
        $criteria->setOrder("DESC");
        $criteria->setLimit($this->items_per_forum);
        $items = $item_handler->getIds($criteria);
        
        foreach ($items as $var) {
            if (empty($status)) {
                if (isset($cookie_vars[$var])) unset($cookie_vars[$var]);
            } else {
                $cookie_vars[$var] = time() /*$items[$var]*/;
            }
        }
        newbb_setcookie($cookie_name, $cookie_vars);
        return true;
    }
    
    function setRead_items_db($status, $forum_id, $uid)
    {
        if (empty($uid)) {
            if (is_object($GLOBALS["xoopsUser"])) {
                $uid = $GLOBALS["xoopsUser"]->getVar("uid");
            } else {
                return false;
            }
        }
        
        $item_handler =& xoops_getmodulehandler('topic', 'newbb');
        $criteria_topic =& new CriteriaCompo(new Criteria("forum_id", $forum_id));
        $criteria_topic->setSort("topic_last_post_id");
        $criteria_topic->setOrder("DESC");
        $criteria_topic->setLimit($this->items_per_forum);
        $criteria_sticky =& new CriteriaCompo(new Criteria("forum_id", $forum_id));
        $criteria_sticky->add(new Criteria("topic_sticky", 1));
    
        if (empty($status)) {            
            $items_id = $item_handler->getIds($criteria_topic);
            $sticky_id = $item_handler->getIds($criteria_sticky);
            $items =  $items_id+$sticky_id;
            $criteria =& new CriteriaCompo(new Criteria("uid", $uid));
            $criteria->add(new Criteria("read_item", "(" . implode(", ", $items) . ")", "IN"));
            $this->deleteAll($criteria, true);
            return true;
        }
        
        $items_obj =& $item_handler->getAll($criteria_topic, array("topic_last_post_id"));
        $sticky_obj =& $item_handler->getAll($criteria_sticky, array("topic_last_post_id"));
        $items_obj = $items_obj + $sticky_obj;
        $items = array();
        foreach (array_keys($items_obj) as $key) {
            $items[$key] = $items_obj[$key]->getVar("topic_last_post_id");
        }
        unset($items_obj, $sticky_obj);
        foreach (array_keys($items) as $key) {
            $this->setRead_db($key, $items[$key], $uid);
        }
        return true;
    }
}
?>