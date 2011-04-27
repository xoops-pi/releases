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
 * @version         $Id: form.forum.php 2170 2008-09-23 13:40:23Z phppp $
 */
 
if (!defined("XOOPS_ROOT_PATH")) exit();

if (!is_object($forum_obj)) {
    xoops_error("forum object IS null");
    return;
}

require_once XOOPS_ROOT_PATH . "/class/xoopstree.php";
include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";

// The forum instanciation
$form_forum = new XoopsThemeForm(_AM_NEWBB_EDITTHISFORUM . " " . $forum_obj->getVar('forum_name'), "form_forum", xoops_getenv('PHP_SELF'));

// Forum name
$form_forum->addElement(new XoopsFormText(_AM_NEWBB_FORUMNAME, 'forum_name', 50, 80, $forum_obj->getVar('forum_name', 'E')), true);

// Forum description
$form_forum->addElement(new XoopsFormDhtmlTextArea(_AM_NEWBB_FORUMDESCRIPTION, 'forum_desc', $forum_obj->getVar('forum_desc', 'E'), 10, 60));

// Category
$form_forum->addElement(new XoopsFormHidden('cat_id', $forum_obj->getVar('cat_id')), true);

// Parent forums
ob_start();
$mytree = new XoopsTree($xoopsDB->prefix("bb_forums"), "forum_id", "parent_forum");
$mytree->makeMySelBox("forum_name", "parent_forum", $forum_obj->getVar("parent_forum"), 1, "parent_forum");
$form_forum->addElement(new XoopsFormLabel(_AM_NEWBB_MAKE_SUBFORUM_OF, ob_get_contents()));
ob_end_clean();

// Forum order
$form_forum->addElement(new XoopsFormText(_AM_NEWBB_SET_FORUMORDER, 'forum_order', 5, 10, $forum_obj->getVar('forum_order')));

// Threshold for "Hot Topic"
$form_forum->addElement(new XoopsFormText(_AM_NEWBB_HOTTOPICTHRESHOLD, 'hot_threshold', 5, 10, $forum_obj->getVar('hot_threshold')));

// Maximum attachment file size
$form_forum->addElement(new XoopsFormText(_AM_NEWBB_ATTACHMENT_SIZE, 'attach_maxkb', 5, 10, $forum_obj->getVar('attach_maxkb')));
// Allowed extensions for attachments
$form_forum->addElement(new XoopsFormText(_AM_NEWBB_ALLOWED_EXTENSIONS, 'attach_ext', 50, 512, $forum_obj->getVar('attach_ext')));

// Forum moderators
$form_forum->addElement(new XoopsFormSelectUser(_AM_NEWBB_MODERATOR, 'forum_moderator', false, $forum_obj->getVar("forum_moderator"), 5, true));

// Permission tray
$perm_tray = new XoopsFormElementTray(_AM_NEWBB_PERMISSIONS_TO_THIS_FORUM, '');
$perm_checkbox = new XoopsFormCheckBox('', 'perm_template', $forum_obj->isNew());
$perm_checkbox->addOption(1, _AM_NEWBB_PERM_TEMPLATEAPP);
$perm_tray->addElement($perm_checkbox);
$perm_tray->addElement(new XoopsFormLabel('', '<a href="admin_permissions.php?action=template" rel="external" title="">' . _AM_NEWBB_PERM_TEMPLATE . '</a>'));
$form_forum->addElement($perm_tray);
    
$form_forum->addElement(new XoopsFormHidden('forum', $forum_obj->getVar("forum_id")));
$form_forum->addElement(new XoopsFormHidden('op', "save"));

$button_tray = new XoopsFormElementTray('', '');
$button_tray->addElement(new XoopsFormButton('', '', _SUBMIT, 'submit'));

$button_tray->addElement(new XoopsFormButton('', '', _AM_NEWBB_CLEAR, 'reset'));

$butt_cancel = new XoopsFormButton('', '', _CANCEL, 'button');
$butt_cancel->setExtra('onclick="history.go(-1)"');
$button_tray->addElement($butt_cancel);

$form_forum->addElement($button_tray);
$form_forum->display();
?>