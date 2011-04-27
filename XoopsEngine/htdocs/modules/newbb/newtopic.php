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
 * @version         $Id: newtopic.php 2175 2008-09-23 14:07:03Z phppp $
 */

include 'header.php';

if ( !$forum = intval(@$_GET["forum"]) ) {
    redirect_header("index.php", 2, _MD_ERRORFORUM);
    exit();
}

$forum_handler =& xoops_getmodulehandler('forum');
$forum_obj = $forum_handler->get($forum);
if (!$forum_handler->getPermission($forum_obj)) {
    redirect_header("index.php", 2, _NOPERM);
    exit();
}

$topic_handler =& xoops_getmodulehandler('topic');
$topic_obj = $topic_handler->create();
$topic_obj->setVar("forum_id", $forum);
if (!$topic_handler->getPermission($forum_obj, 0, 'post')) {
    /*
     * Build the page query
     */
    $query_vars = array("forum", "order", "mode", "viewmode");
    $query_array = array();
    foreach ($query_vars as $var) {
        if (!empty($_GET[$var])) $query_array[$var] = "{$var}={$_GET[$var]}";
    }
    $page_query = htmlspecialchars(implode("&", array_values($query_array)));
    unset($query_array);
    redirect_header("viewforum.php?{$page_query}", 2, _MD_NORIGHTTOPOST);
    exit();
}

if ($xoopsModuleConfig['wol_enabled']) {
    $online_handler =& xoops_getmodulehandler('online');
    $online_handler->init($forum_obj);
}


$xoopsOption['template_main'] =  'newbb_edit_post.html';
$xoopsConfig["module_cache"][$xoopsModule->getVar("mid")] = 0; // Disable cache
include XOOPS_ROOT_PATH . '/header.php';

if ($xoopsModuleConfig['disc_show'] == 1 || $xoopsModuleConfig['disc_show'] == 3 ) {
    $xoopsTpl->assign("disclaimer", $xoopsModuleConfig['disclaimer']);
}


$subject = "";
$message = "";
$dohtml = 1;
$dosmiley = 1;
$doxcode = 1;
$dobr = 1;
$icon = '';
$post_karma = 0;
$require_reply = 0;
$attachsig = (is_object($xoopsUser) && $xoopsUser->getVar('attachsig')) ? 1 : 0;
$post_id = 0;
$topic_id = 0;

include 'include/form.post.php';
include XOOPS_ROOT_PATH . '/footer.php';
?>