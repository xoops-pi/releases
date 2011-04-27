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
 * @version         $Id: dl_attachment.php 2175 2008-09-23 14:07:03Z phppp $
 */

ob_start();
include "header.php";
include XOOPS_ROOT_PATH . '/header.php';

$attach_id = isset($_GET['attachid']) ? strval($_GET['attachid']) : '';
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

if (!$post_id || !$attach_id) {
    die(_MD_NO_SUCH_FILE . ': post_id:' . $post_id . '; attachid' . $attachid);
}

$post_handler =& xoops_getmodulehandler('post', 'newbb');
$forumpost =& $post_handler->get($post_id);
if (!$approved = $forumpost->getVar('approved')) {
    die(_MD_NORIGHTTOVIEW);
}
$topic_handler =& xoops_getmodulehandler('topic', 'newbb');
$topic_obj =& $topic_handler->getByPost($post_id);
$topic_id = $topic_obj->getVar('topic_id');
if (!$approved = $topic_obj->getVar('approved')) {
    die(_MD_NORIGHTTOVIEW);
}
$forum_handler =& xoops_getmodulehandler('forum', 'newbb');
$forum_obj =& $forum_handler->get($topic_obj->getVar('forum_id'));
if (!$forum_handler->getPermission($forum_obj)) {
    die(_MD_NORIGHTTOACCESS);
}
if (!$topic_handler->getPermission($forum_obj, $topic_obj->getVar('topic_status'), "view")) {
    die(_MD_NORIGHTTOVIEW);
}

$attachments = $forumpost->getAttachment();
if (!$attach = $attachments[$attach_id]) {
    die(_MD_NO_SUCH_FILE);
}
$file_saved = XOOPS_ROOT_PATH . '/' . $xoopsModuleConfig['dir_attachments'] . '/' . $attach['name_saved'];
if (!file_exists($file_saved)) {
    die(_MD_NO_SUCH_FILE);
}
if ($down = $forumpost->incrementDownload($attach_id)) {
    $forumpost->saveAttachment();
}
unset($forumpost);
$msg = ob_get_contents();
ob_end_clean();

$xoopsLogger->activated = false;
if (!empty($GLOBALS["xoopsModuleConfig"]["download_direct"])):

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache"); 
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("location: " . XOOPS_URL . '/' . $xoopsModuleConfig['dir_attachments'] . '/' . $attach['name_saved']);

else:
$file_display = $attach['name_display'];
//$mimetype = $attach['mimetype'];

if (ini_get('zlib.output_compression')) {
    @ini_set('zlib.output_compression', 'Off');
}

if (function_exists('mb_http_output')) {
    mb_http_output('pass');
}
header('Expires: 0');
//header('Content-Type: '.$mimetype);
header('Content-Type: application/octet-stream');
if (preg_match("/MSIE ([0-9]\.[0-9]{1,2})/", $_SERVER["HTTP_USER_AGENT"])) {
    header('Content-Disposition: attachment; filename="' . $file_display . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
} else {
    header('Content-Disposition: attachment; filename="' . $file_display . '"');
    header('Pragma: no-cache');
}
header("Content-Type: application/force-download");
header("Content-Transfer-Encoding: binary");

$handle = fopen($file_saved, "rb");
while (!feof($handle)) {
   $buffer = fread($handle, 4096);
   echo $buffer;
}
fclose($handle);

endif;
?>