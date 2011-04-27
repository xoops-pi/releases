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
 * @version         $Id: tree.php 2169 2008-09-23 13:37:10Z phppp $
 */

if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}
require_once XOOPS_ROOT_PATH . "/class/tree.php";

class newbbObjectTree extends XoopsObjectTree
{

    function newbbObjectTree(&$objectArr, $rootId = null)
    {
        $this->XoopsObjectTree($objectArr, "forum_id", "parent_forum", $rootId);
    }

    /**
     * Make options for a select box from
     *
     * @param   string  $fieldName   Name of the member variable from the
     *  node objects that should be used as the title for the options.
     * @param   string  $selected    Value to display as selected
     * @param   int $key         ID of the object to display as the root of select options
     * @param   string  $ret         (reference to a string when called from outside) Result from previous recursions
     * @param   string  $prefix_orig  String to indent items at deeper levels
     * @param   string  $prefix_curr  String to indent the current item
     * @return
     *
     * @access    private
     **/
    function _makeTreeItems($key, &$ret, $prefix_orig, $prefix_curr = '', $tags = null)
    {
        if ($key > 0) {
            if (count($tags) > 0) {
                foreach ($tags as $tag) {
                    $ret[$key][$tag] = $this->_tree[$key]['obj']->getVar($tag);
                } 
            } else {
                $ret[$key]["forum_name"] = $this->_tree[$key]['obj']->getVar("forum_name");
            }
            $ret[$key]["prefix"] = $prefix_curr;
            $prefix_curr .= $prefix_orig;
        }
        if (isset($this->_tree[$key]['child']) && !empty($this->_tree[$key]['child'])) {
            foreach ($this->_tree[$key]['child'] as $childkey) {
                $this->_makeTreeItems($childkey, $ret, $prefix_orig, $prefix_curr, $tags);
            }
        }
    }

    /**
     * Make a select box with options from the tree
     *
     * @param   string  $name            Name of the select box
     * @param   string  $fieldName       Name of the member variable from the
     *  node objects that should be used as the title for the options.
     * @param   string  $prefix          String to indent deeper levels
     * @param   string  $selected        Value to display as selected
     * @param   bool    $addEmptyOption  Set TRUE to add an empty option with value "0" at the top of the hierarchy
     * @param   integer $key             ID of the object to display as the root of select options
     * @return  string  HTML select box
     **/
    function &makeTree($prefix = '-', $key = 0, $tags = null)
    {
        $ret = array();
        $this->_makeTreeItems($key, $ret, $prefix, '', $tags);
        return $ret;
    }

    /**
     * Make a select box with options from the tree
     *
     * @param   string  $name            Name of the select box
     * @param   string  $fieldName       Name of the member variable from the
     *  node objects that should be used as the title for the options.
     * @param   string  $prefix          String to indent deeper levels
     * @param   string  $selected        Value to display as selected
     * @param   bool    $addEmptyOption  Set TRUE to add an empty option with value "0" at the top of the hierarchy
     * @param   integer $key             ID of the object to display as the root of select options
     * @return  string  HTML select box
     **/
    function &makeSelBox($name, $prefix = '-', $selected = '', $EmptyOption = false, $key = 0)
    {
        $ret = '<select name=' . $name . '>';
        if (!empty($addEmptyOption)) {
            $ret .= '<option value="0">' . (is_string($EmptyOption) ? $EmptyOption : '') . '</option>';
        }
        $this->_makeSelBoxOptions("forum_name", $selected, $key, $ret, $prefix);
        $ret .= '</select>';
        return $ret;
    }
    
    
    /**
     * Make a tree for the array of a given category
     * 
     * @param   string  $key    top key of the tree
     * @param   array    $ret    the tree
     * @param   array    $tags   fields to be used
     * @param   integer    $depth    level of subcategories
     * @return  array      
     **/
    function getAllChild_object($key, &$ret, $depth = 0)
    {
        if (-- $depth == 0) {
            return;
        }
        
        if (isset($this->_tree[$key]['child'])) {
            foreach ($this->_tree[$key]['child'] as $childkey) {
                if (isset($this->_tree[$childkey]['obj'])) {
                    $ret['child'][$childkey] =& $this->_tree[$childkey]['obj'];
                }
                $this->getAllChild_object($childkey, $ret['child'][$childkey], $depth);
            }
        }
    }

    /**
     * Make a tree for the array
     * 
     * @param   string  $key    top key of the tree
     * @param   array    $tags   fields to be used
     * @param   integer    $depth    level of subcategories
     * @return  array      
     **/
    function &makeObjectTree($key = 0, $depth = 0)
    {
        $ret = array();
        if ($depth > 0) $depth++;
        $this->getAllChild_object($key, $ret, $depth);
        return $ret;
    }
    
    /**
     * Make a tree for the array of a given category
     * 
     * @param   string  $key    top key of the tree
     * @param   array    $ret    the tree
     * @param   array    $tags   fields to be used
     * @param   integer    $depth    level of subcategories
     * @return  array      
     **/
    function getAllChild_array($key, &$ret, $tags = array(), $depth = 0)
    {
        if (-- $depth == 0) {
            return;
        }
        
        if (isset($this->_tree[$key]['child'])) {
            foreach ($this->_tree[$key]['child'] as $childkey) {
                if (isset($this->_tree[$childkey]['obj'])):
                if (count($tags)>0) {
                    foreach ($tags as $tag) {
                        $ret['child'][$childkey][$tag] = $this->_tree[$childkey]['obj']->getVar($tag);
                    }
                } else {
                    $ret['child'][$childkey]["forum_name"] = $this->_tree[$childkey]['obj']->getVar("forum_name");
                }
                endif;
                
                $this->getAllChild_array($childkey, $ret['child'][$childkey], $tags, $depth);
            }
        }
    }

    /**
     * Make a tree for the array
     * 
     * @param   string  $key    top key of the tree
     * @param   array    $tags   fields to be used
     * @param   integer    $depth    level of subcategories
     * @return  array      
     **/
    function &makeArrayTree($key = 0, $tags = null, $depth = 0)
    {
        $ret = array();
        if ($depth > 0) $depth++;
        $this->getAllChild_array($key, $ret, $tags, $depth);
        return $ret;
    }

    /**#@+
     * get all parent forums
     * 
     * @param   string    $key        ID of the child object
     * @param   array   $ret        (empty when called from outside) Result from previous recursions
     * @param   int        $uplevel    (empty when called from outside) level of recursion
     * @return  array   Array of parent nodes. 
     */
    function &_getParentForums($key, $ret = array(), $uplevel = 0)
    {
        if (isset($this->_tree[$key]['parent']) && isset($this->_tree[$this->_tree[$key]['parent']]['obj'])) {
            $ret[$uplevel] = $this->_tree[$this->_tree[$key]['parent']]['obj'];
            $parents = $this->getParentForums($this->_tree[$key]['parent'], $ret, $uplevel + 1);
            foreach (array_keys($parents) as $newkey) {
                $ret[$newkey] =& $parents[$newkey];
            }
        }
        return $ret;
    }
    
    function &getParentForums($key, $reverse = true)
    {
        $ret = array();
        $pids = array();
        if (isset($this->_tree[$key]['parent']) && isset($this->_tree[$this->_tree[$key]['parent']]['obj'])) {
            $pids[] = $this->_tree[$this->_tree[$key]['parent']]['obj']->getVar($this->_myId);
            $parents = $this->_getParentForums($this->_tree[$key]['parent'], $ret);
            foreach (array_keys($parents) as $newkey) {
                if (!is_object($newkey)) continue;
                $ret[] = $parents[$newkey]->getVar($this->_myId);
            }
        }
        if ($reverse) {
            $pids = array_reverse($ret) + $pids;
        } else {
            $pids = $pids + $ret;
        }
        return $pids;
    }
    /**#@-*/
    
}
?>