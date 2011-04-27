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
 * @version         $Id: newbbtree.php 2169 2008-09-23 13:37:10Z phppp $
 */

if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}
include_once XOOPS_ROOT_PATH . "/class/xoopstree.php";

class NewBBTree extends XoopsTree
{
    var $prefix = '&nbsp;&nbsp;';
    var $increment = '&nbsp;&nbsp;';
    var $postArray = '';

    function __construct($table_name, $id_name = "post_id", $pid_name = "pid")
    {
        parent::__construct($table_name, $id_name, $pid_name);
    }

    function setPrefix($val = '')
    {
        $this->prefix = $val;
        $this->increment = $val;
    }

    function getAllPostArray($sel_id, $order = '')
    {
        $this->postArray = $this->getAllChild($sel_id, $order);
    }

    function setPostArray($postArray)
    {
        $this->postArray = &$postArray;
    }

    // returns an array of first child objects for a given id($sel_id)
    function getPostTree(&$postTree_array, $pid = 0, $prefix = '&nbsp;&nbsp;')
    {
        if (!is_array($postTree_array)) $postTree_array = array();

        $newPostArray = array();
        $prefix .= $this->increment;
        foreach ($this->postArray as $post) {
            if ($post->getVar('pid') == $pid) {
                $postTree_array[] = array(
                    'prefix'    => $prefix,
                    'icon'      => $post->getVar('icon'),
                    'post_time' => $post->getVar('post_time'),
                    'post_id'   => $post->getVar('post_id'),
                    'forum_id'  => $post->getVar('forum_id'),
                    'subject'   => $post->getVar('subject'),
                    'poster_name'   => $post->getVar('poster_name'),
                    'uid'       => $post->getVar('uid')
                    );
                $this->getPostTree($postTree_array, $post->getVar('post_id'), $prefix);
            } else {
                $newPostArray[] = $post;
            }
        }
        $this->postArray = $newPostArray;
        unset($newPostArray);

        return true;
    }
}

?>