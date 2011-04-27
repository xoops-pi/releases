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
 * @version		$Id: fckeditor.connector.php 2175 2008-09-23 14:07:03Z phppp $
 */
include_once '../../mainfile.php';
$xoopsLogger->activated = false;

define("XOOPS_FCK_FOLDER", $xoopsModule->getVar("dirname", "n"));
chdir(XOOPS_ROOT_PATH . "/class/xoopseditor/fckeditor/fckeditor/editor/filemanager/connectors/php/");
include_once "connector.php";
?>