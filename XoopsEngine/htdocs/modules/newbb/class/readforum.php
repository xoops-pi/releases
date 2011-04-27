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
 * @version         $Id: readforum.php 2169 2008-09-23 13:37:10Z phppp $
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

class Readforum extends Read 
{
    function Readforum()
    {
        $this->Read("forum");
    }
}

class NewbbReadforumHandler extends NewbbReadHandler
{
    function NewbbReadforumHandler(&$db)
    {
        $this->NewbbReadHandler($db, "forum");
    }
    
    /**
     * clean orphan items from database
     * 
     * @return     bool    true on success
     */
    function cleanOrphan()
    {
        parent::cleanOrphan($this->db->prefix("bb_posts"), "post_id");
        return parent::cleanOrphan($this->db->prefix("bb_forums"), "forum_id", "read_item");
    }    
    
    function setRead_items($status = 0, $uid = null)
    {
        if (empty($this->mode)) return true;
        
        if ($this->mode == 1) return $this->setRead_items_cookie($status);
        else return $this->setRead_items_db($status, $uid);
    }
        
    function setRead_items_cookie($status, $items)
    {
        $cookie_name = "LF";
        $items = array();
        if (!empty($status)):
        $item_handler =& xoops_getmodulehandler('forum', 'newbb');
        $items_id = $item_handler->getIds();
        foreach ($items_id as $key) {
            $items[$key] = time();
        }
        endif;
        newbb_setcookie($cookie_name, $items);
        return true;
    }
    
    function setRead_items_db($status, $uid)
    {
        if (empty($uid)) {
            if (is_object($GLOBALS["xoopsUser"])) {
                $uid = $GLOBALS["xoopsUser"]->getVar("uid");
            } else {
                return false;
            }
        }
        if (empty($status)) {
            $this->deleteAll(new Criteria("uid", $uid));
            return true;
        }

        $item_handler =& xoops_getmodulehandler('forum', 'newbb');
        $items_obj =& $item_handler->getAll(null, array("forum_last_post_id"));
        foreach (array_keys($items_obj) as $key) {
            $this->setRead_db($key, $items_obj[$key]->getVar("forum_last_post_id"), $uid);
        }
        unset($items_obj);
        
        return true;
    }
}
?>