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
 * @version         $Id: menu.php 2167 2008-09-23 13:33:57Z phppp $
 */

$i = 0;
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_INDEX;
$adminmenu[$i++]['link'] = "admin/index.php";
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_CATEGORY;
$adminmenu[$i++]['link'] = "admin/admin_cat_manager.php";
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_FORUM;
$adminmenu[$i++]['link'] = "admin/admin_forum_manager.php";
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_PERMISSION;
$adminmenu[$i++]['link'] = "admin/admin_permissions.php";
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_BLOCK;
$adminmenu[$i++]['link'] = "admin/admin_blocks.php";
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_SYNC;
$adminmenu[$i++]['link'] = "admin/admin_synchronization.php";
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_ORDER;
$adminmenu[$i++]['link'] = "admin/admin_forum_reorder.php";
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_PRUNE;
$adminmenu[$i++]['link'] = "admin/admin_forum_prune.php";
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_REPORT;
$adminmenu[$i++]['link'] = "admin/admin_report.php";
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_DIGEST;
$adminmenu[$i++]['link'] = "admin/admin_digest.php";
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_VOTE;
$adminmenu[$i++]['link'] = "admin/admin_votedata.php";
$adminmenu[$i]['title'] = _MI_NEWBB_ADMENU_TYPE;
$adminmenu[$i++]['link'] = "admin/admin_type_manager.php";

?>