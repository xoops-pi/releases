<?php
/**
 * FPDF creator framework for XOOPS
 *
 * Supporting multi-byte languages as well as utf-8 charset
 *
 * @copyright    The XOOPS Project http://xoops.sf.net/
 * @license        http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author        Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since        4.00
 * @version        $Id: pdf.php 2175 2008-09-23 14:07:03Z phppp $
 * @package        frameworks
 */
 
ob_start();
error_reporting(0);
include "header.php";
error_reporting(0);

/**
 * If no pdf_data is set, build it from the module
 *
 * <ul>The data fields to be built:
 *        <li>title</li>
 *        <li>subtitle (optional)</li>
 *        <li>subsubtitle (optional)</li>
 *        <li>date</li>
 *        <li>author</li>
 *        <li>content</li>
 *        <li>filename</li>
 * </ul>
 */
if (empty($_POST["pdf_data"])) {
        
    $forum = isset($_GET['forum']) ? intval($_GET['forum']) : 0;
    $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
    $post_id = !empty($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    
    if ( empty($post_id) )  die(_MD_ERRORTOPIC);
    
    $post_handler =& xoops_getmodulehandler('post', 'newbb');
    $post = & $post_handler->get($post_id);
    if (!$approved = $post->getVar('approved')) die(_MD_NORIGHTTOVIEW);
    
    $post_data = $post_handler->getPostForPDF($post);
    
    $topic_handler =& xoops_getmodulehandler('topic', 'newbb');
    $topic_obj =& $topic_handler->getByPost($post_id);
    $topic_id = $topic_obj->getVar('topic_id');
    if (!$approved = $topic_obj->getVar('approved')) die(_MD_NORIGHTTOVIEW);
    
    $forum_handler =& xoops_getmodulehandler('forum', 'newbb');
    $forum = ($forum)?$forum:$topic_obj->getVar('forum_id');
    $forum_obj =& $forum_handler->get($forum);
    if (!$forum_handler->getPermission($forum_obj)) die(_MD_NORIGHTTOACCESS);
    if (!$topic_handler->getPermission($forum_obj, $topic_obj->getVar('topic_status'), "view")) die(_MD_NORIGHTTOVIEW);
    
    $pdf_data['title'] = $forum_obj->getVar("forum_name");
    $pdf_data['subtitle'] = $topic_obj->getVar('topic_title');
    $pdf_data['subsubtitle'] = $post_data['subject'];
    $pdf_data['date'] = $post_data['date'];
    $pdf_data['content'] = $post_data['text'];
    $pdf_data['author'] = $post_data['author'];

} else {
    $pdf_data = unserialize(base64_decode($_POST["pdf_data"]));
}
$pdf_data['filename'] = preg_replace("/[^0-9a-z\-_\.]/i", '', $pdf_data["title"]);

include XOOPS_ROOT_PATH . "/Frameworks/fpdf/init.php";
error_reporting(0);
ob_end_clean();

$pdf = new xoopsPDF($xoopsConfig["language"]);
$pdf->initialize();
$pdf->output($pdf_data, _CHARSET);
?>