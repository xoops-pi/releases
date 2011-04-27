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
 * @version         $Id: transfer.php 2175 2008-09-23 14:07:03Z phppp $
 */

include "header.php";

$topic_id = intval( empty($_GET["topic_id"]) ? (empty($_POST["topic_id"]) ? 0 : $_POST["topic_id"]) : $_GET["topic_id"] );
$post_id = intval( empty($_GET["post_id"]) ? (empty($_POST["post_id"]) ? 0 : $_POST["post_id"]) : $_GET["post_id"] );

if ( empty($post_id) )  {
    if (empty($_SERVER['HTTP_REFERER'])) {
        include XOOPS_ROOT_PATH . "/header.php";
        xoops_error(_NOPERM);
        $xoopsOption['output_type'] = "plain";
        include XOOPS_ROOT_PATH . "/footer.php";
        exit();
    } else {
        $ref_parser = parse_url($_SERVER['HTTP_REFERER']);
        $uri_parser = parse_url($_SERVER['REQUEST_URI']);
        if (
            (!empty($ref_parser['host']) && !empty($uri_parser['host']) && $uri_parser['host'] != $ref_parser['host']) 
            || 
            ($ref_parser["path"] != $uri_parser["path"])
        ) {
            include XOOPS_ROOT_PATH . "/header.php";
            xoops_confirm(array(), "javascript: window.close();", sprintf(_MD_TRANSFER_DONE, ""), _CLOSE, $_SERVER['HTTP_REFERER']);
            $xoopsOption['output_type'] = "plain";
            include XOOPS_ROOT_PATH . "/footer.php";
            exit();
        } else {
            include XOOPS_ROOT_PATH . "/header.php";
            xoops_error(_NOPERM);
            $xoopsOption['output_type'] = "plain";
            include XOOPS_ROOT_PATH . "/footer.php";
            exit();
        }
    }
}

$post_handler =& xoops_getmodulehandler('post', 'newbb');
$post = & $post_handler->get($post_id);
if (!$approved = $post->getVar('approved'))    die(_NOPERM);

$topic_handler =& xoops_getmodulehandler('topic', 'newbb');
$topic_obj =& $topic_handler->getByPost($post_id);
$topic_id = $topic_obj->getVar('topic_id');
if (!$approved = $topic_obj->getVar('approved'))    die(_NOPERM);

$forum_handler =& xoops_getmodulehandler('forum', 'newbb');
$forum_id = $topic_obj->getVar('forum_id');
$forum_obj =& $forum_handler->get($forum_id);
if (!$forum_handler->getPermission($forum_obj))    die(_NOPERM);
if (!$topic_handler->getPermission($forum_obj, $topic_obj->getVar('topic_status'), "view"))   die(_NOPERM);

$op = empty($_GET["op"]) ? ( empty($_POST["op"])? @$args["op"] : $_POST["op"] ) : $_GET["op"];
$op = strtolower(trim($op));

// Display option form
if (empty($op)) {
    $module_variables  =  "<input type=\"hidden\" name=\"category\" id=\"category\" value=\"{$category_id}\">";
    $module_variables .=  "<input type=\"hidden\" name=\"article\" id=\"article\" value=\"{$article_id}\">";
    $module_variables .=  "<input type=\"hidden\" name=\"page\" id=\"page\" value=\"{$page}\">";
    include XOOPS_ROOT_PATH . "/Frameworks/transfer/option.transfer.php";
    exit();
} else {
    $data = array();
    $data["id"] = $post_id;
    $data["uid"] = $post->getVar("uid");
    $data["url"] = XOOPS_URL . "/modules/newbb/viewtopic.php?topic_id=" . $topic_id . "&post_id=" . $post_id;
    $post_data =& $post->getPostBody();
    $data["author"] = $post_data["author"];
    $data["title"] = $post_data["subject"];
    $data["content"] = $post_data["text"];
    $data["date"] = $post_data["date"];
    $data["time"] = formatTimestamp($post_data["date"]);
    
    switch($op) {
        case "pdf":
            $data['subtitle'] = $topic_obj->getVar('topic_title');
            break;
        
        // Use regular content
        default:
            break;
    }
    include XOOPS_ROOT_PATH . "/Frameworks/transfer/action.transfer.php";
    exit();
}
?>