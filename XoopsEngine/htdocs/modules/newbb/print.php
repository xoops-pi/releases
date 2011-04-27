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
 * @version         $Id: print.php 2175 2008-09-23 14:07:03Z phppp $
 */

/* 
 * Print contents of a post or a topic
 * currently only available for post print
 *
 * TODO: topic print; print with page splitting
 * 
 */
 
error_reporting(0);
include 'header.php';
error_reporting(0);

if (empty($_POST["post_data"])) {
    
$forum = intval(@$_GET['forum']);
$topic_id = intval(@$_GET['topic_id']);
$post_id = intval(@$_GET['post_id']);

if ( empty($post_id) && empty($topic_id) ) {
    die(_MD_ERRORTOPIC);
}

if (!empty($post_id)) {
    $post_handler =& xoops_getmodulehandler('post', 'newbb');
    $post = & $post_handler->get($post_id);
    if (!$approved = $post->getVar('approved')) {
        die(_MD_NORIGHTTOVIEW);
    }
    $topic_id = $post->getVar("topic_id");
    $post_data = $post_handler->getPostForPrint($post);
    $isPost = 1;
    $post_data["url"] = XOOPS_URL . "/newbb/viewtopic.php?topic_id=" . $post->getVar("topic_id") . "&amp;post_id=" . $post_id;
}

$topic_handler =& xoops_getmodulehandler('topic', 'newbb');
$topic_obj =& $topic_handler->get($topic_id);
$topic_id = $topic_obj->getVar('topic_id');
$forum = $topic_obj->getVar('forum_id');
if (!$approved = $topic_obj->getVar('approved'))    {
    die(_MD_NORIGHTTOVIEW);
}

$isadmin = newbb_isAdmin($forum_obj);
if (!$isadmin && $topic_obj->getVar('approved') < 0 ) {
    die(_MD_NORIGHTTOVIEW);
}

$forum_handler =& xoops_getmodulehandler('forum', 'newbb');
$forum = $topic_obj->getVar('forum_id');
$forum_obj =& $forum_handler->get($forum);
if (!$forum_handler->getPermission($forum_obj)) {
    die(_MD_NORIGHTTOVIEW);
}

if (!$topic_handler->getPermission($forum_obj, $topic_obj->getVar('topic_status'), "view")) {
    die(_MD_NORIGHTTOVIEW);
}

} else {
    $post_data = unserialize(base64_decode($_POST["post_data"]));
    $isPost = 1;
}

header('Content-Type: text/html; charset=' . _CHARSET); 

if (empty($isPost)) {

    echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>\n";
    echo "<html>\n<head>\n";
    echo "<title>" . htmlspecialchars($xoopsConfig['sitename']) . "</title>\n";
    echo "<meta http-equiv='Content-Type' content='text/html; charset=" . _CHARSET . "' />\n";
    echo "<meta name='AUTHOR' content='" . htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES) . "' />\n";
    echo "<meta name='COPYRIGHT' content='Copyright (c) " . date('Y') . " by " . htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES) . "' />\n";
    echo "<meta name='DESCRIPTION' content='" . htmlspecialchars($xoopsConfig['slogan'], ENT_QUOTES) . "' />\n";
    echo "<meta name='GENERATOR' content='" . XOOPS_VERSION . "' />\n\n\n";
    echo "<body bgcolor='#ffffff' text='#000000' onload='window.print()'>
           <div style='width: 750px; border: 1px solid #000; padding: 20px;'>
           <div style='text-align: center; display: block; margin: 0 0 6px 0;'>
          <img src='" . XOOPS_URL . "/modules/newbb/images/xoopsbb_slogo.png' border='0' alt='' />
          <br />
          <br />
          ";

    $postsArray = $topic_handler->getAllPosts($topic_obj);
    foreach ($postsArray as $post) {
        if (!$post->getVar('approved'))  continue;
        $post_data = $post_handler->getPostForPrint($post);
        echo "<h2 style='margin: 0;'>" . $post_data['subject'] . "</h2>
               <div align='center'>" . _POSTEDBY . "&nbsp;" . $post_data['author'] . "&nbsp;" . _ON . "&nbsp;" . $post_data['date'] . "</div>
              <div style='text-align: center; display: block; padding-bottom: 12px; margin: 0 0 6px 0; border-bottom: 2px solid #ccc;'></div>
              <div style='text-align: left'>" . $post_data['text'] . "</div>
              <div style='padding-top: 12px; border-top: 2px solid #ccc;'></div><br />";
    }
    echo "<p>" . _MD_COMEFROM . "&nbsp;" . XOOPS_URL . "/newbb/viewtopic.php?forum=" . $forum_id . "&amp;topic_id=" . $topic_id . "</p>";
    echo "</div></div>";
    echo "</body></html>";
    
} else {

    echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>\n";
    echo "<html>\n<head>\n";
    echo "<title>" . htmlspecialchars($xoopsConfig['sitename']) . "</title>\n";
    echo "<meta http-equiv='Content-Type' content='text/html; charset=" . _CHARSET . "' />\n";
    echo "<meta name='AUTHOR' content='" . htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES) . "' />\n";
    echo "<meta name='COPYRIGHT' content='Copyright (c) " . date('Y') . " by " . htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES) . "' />\n";
    echo "<meta name='DESCRIPTION' content='" . htmlspecialchars($xoopsConfig['slogan'], ENT_QUOTES) . "' />\n";
    echo "<meta name='GENERATOR' content='" . XOOPS_VERSION . "' />\n\n\n";
    echo "</head>\n\n\n";
    echo "<body bgcolor='#ffffff' text='#000000' onload='window.print()'>
           <div style='width: 750px; border: 1px solid #000; padding: 20px;'>
           <div style='text-align: center; display: block; margin: 0 0 6px 0;'>
          <img src='" . XOOPS_URL . "/modules/newbb/images/xoopsbb_slogo.png' border='0' alt='' />
          <h2 style='margin: 0;'>" . $post_data['subject'] . "</h2></div>
           <div align='center'>" . _POSTEDBY . "&nbsp;" . $post_data['author'] . "&nbsp;" . _ON . "&nbsp;" . $post_data['date'] . "</div>
          <div style='text-align: center; display: block; padding-bottom: 12px; margin: 0 0 6px 0; border-bottom: 2px solid #ccc;'></div>
               <div style='text-align: left'>" . $post_data['text'] . "</div>
            <div style='padding-top: 12px; border-top: 2px solid #ccc;'></div>
            <p>" . _MD_COMEFROM . "&nbsp;" . $post_data["url"] . "</p>
            </div>
            <br />";
    echo "<br /></body></html>";
}
?>