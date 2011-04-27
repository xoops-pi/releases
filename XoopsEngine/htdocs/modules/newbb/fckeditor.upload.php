<?php 
/**
 *  FCKeditor adapter for XOOPS
 *
 * @copyright   The XOOPS project http://www.xoops.org/
 * @license     http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package		core
 * @subpackage	xoopseditor
 * @since       2.3.0
 * @author		Taiwen Jiang <phppp@users.sourceforge.net>
 * @version		$Id: fckeditor.upload.php 2175 2008-09-23 14:07:03Z phppp $
 */
include_once '../../mainfile.php';
$xoopsLogger->activated = false;

// Set to 1 if upload is disabled
define("FCKUPLOAD_DISABLED", 0);

// Set the upload directory
define("XOOPS_FCK_FOLDER", $xoopsModule->getVar("dirname", "n"));

// Usually no need to change this
chdir(XOOPS_ROOT_PATH . "/class/xoopseditor/fckeditor/fckeditor/editor/filemanager/connectors/php/");
include_once "upload.php";
?>