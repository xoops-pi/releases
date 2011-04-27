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
 * @version         $Id: module.php 2284 2008-10-12 03:45:46Z phppp $
 */
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

function xoops_module_update_newbb(&$module, $oldversion = null)
{
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.config.php";
    $newbbConfig = newbb_loadConfig();

    // NewBB 1.0
    //if ($oldversion == 100) {
    if (version_compare($oldversion, "1.0.0", "=")) {
        include_once dirname(__FILE__) . "/module.v100.php";
        xoops_module_update_newbb_v100($module);
    }

    // NewBB 2.* and CBB 1.*
    // change group permission name
    // change forum moderators
    //if ($oldversion < 220) {
    if (version_compare($oldversion, "2.2.0", "<")) {
        include_once dirname(__FILE__) . "/module.v220.php";
        xoops_module_update_newbb_v220($module);
    }

    //if ($oldversion < 230) {
    if (version_compare($oldversion, "2.3.0", "<")) {
        $GLOBALS['xoopsDB']->queryFromFile(XOOPS_ROOT_PATH . "/modules/" . $module->getVar("dirname", "n") . "/sql/upgrade_230.sql");
    }

    //if ($oldversion < 304) {
    if (version_compare($oldversion, "3.4.0", "<")) {
        $GLOBALS['xoopsDB']->queryFromFile(XOOPS_ROOT_PATH . "/modules/" . $module->getVar("dirname", "n") . "/sql/mysql.304.sql");
    }

    //if ($oldversion < 400) {
    if (version_compare($oldversion, "4.0.0", "<")) {
        $GLOBALS['xoopsDB']->queryFromFile(XOOPS_ROOT_PATH . "/modules/" . $module->getVar("dirname", "n") . "/sql/mysql.400.sql");
        include dirname(__FILE__) . "/module.v400.php";
        xoops_module_update_newbb_v400($module);
    }

    if (!empty($newbbConfig["syncOnUpdate"])) {
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.recon.php";
        newbb_synchronization();
    }

    return true;
}

function xoops_module_pre_update_newbb(&$module)
{
    return newbb_setModuleConfig($module, true);
}

function xoops_module_pre_install_newbb(&$module)
{
    $mod_tables = $module->getInfo("tables");
    foreach ($mod_tables as $table) {
        $GLOBALS["xoopsDB"]->queryF("DROP TABLE IF EXISTS " . $GLOBALS["xoopsDB"]->prefix($table) . ";");
    }
    return newbb_setModuleConfig($module);
}

function xoops_module_install_newbb(&$module)
{
    /* Create a test category */
    $category_handler =& xoops_getmodulehandler('category', $module->getVar("dirname"));
    $category = $category_handler->create();
    $category->setVar('cat_title', _MI_NEWBB_INSTALL_CAT_TITLE, true);
    $category->setVar('cat_image', "", true);
    $category->setVar('cat_description', _MI_NEWBB_INSTALL_CAT_DESC, true);
    $category->setVar('cat_url', "http://xoops.org XOOPS", true);
    if (!$cat_id = $category_handler->insert($category)) {
        return true;
    }

    /* Create a forum for test */
    $forum_handler =& xoops_getmodulehandler('forum', $module->getVar("dirname"));
    $forum =& $forum_handler->create();
    $forum->setVar('forum_name', _MI_NEWBB_INSTALL_FORUM_NAME, true);
    $forum->setVar('forum_desc', _MI_NEWBB_INSTALL_FORUM_DESC, true);
    $forum->setVar('forum_moderator', array());
    $forum->setVar('parent_forum', 0);
    $forum->setVar('cat_id', $cat_id);
    $forum->setVar('attach_maxkb', 100);
    $forum->setVar('attach_ext', "zip|jpg|gif");
    $forum->setVar('hot_threshold', 20);
    $forum_id = $forum_handler->insert($forum);

    /* Set corresponding permissions for the category and the forum */
    $module_id = $module->getVar("mid") ;
    $gperm_handler =& xoops_gethandler("groupperm");
    $groups_view = array(XOOPS_GROUP_ADMIN, XOOPS_GROUP_USERS, XOOPS_GROUP_ANONYMOUS);
    $groups_post = array(XOOPS_GROUP_ADMIN, XOOPS_GROUP_USERS);
    $post_items = array('post', 'reply', 'edit', 'delete', 'addpoll', 'vote', 'attach', 'noapprove', 'type');
    foreach ($groups_view as $group_id) {
        $gperm_handler->addRight("category_access", $cat_id, $group_id, $module_id);
        $gperm_handler->addRight("forum_access", $forum_id, $group_id, $module_id);
        $gperm_handler->addRight("forum_view", $forum_id, $group_id, $module_id);
    }
    foreach ($groups_post as $group_id) {
        foreach ($post_items as $item) {
            $gperm_handler->addRight("forum_" . $item, $forum_id, $group_id, $module_id);
        }
    }

    /* Create a test post */
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.user.php";
    $post_handler =& xoops_getmodulehandler('post', $module->getVar("dirname"));
    $forumpost =& $post_handler->create();
    $forumpost->setVar('poster_ip', newbb_getIP());
    $forumpost->setVar('uid', is_object($GLOBALS["xoopsUser"]) ? $GLOBALS["xoopsUser"]->getVar("uid") : 1);
    $forumpost->setVar('approved', 1);
    $forumpost->setVar('forum_id', $forum_id);
    $forumpost->setVar('subject', _MI_NEWBB_INSTALL_POST_SUBJECT, true);
    $forumpost->setVar('dohtml', 0);
    $forumpost->setVar('dosmiley', 1);
    $forumpost->setVar('doxcode', 1);
    $forumpost->setVar('dobr', 1);
    $forumpost->setVar('icon', "", true);
    $forumpost->setVar('attachsig', 1);
    $forumpost->setVar('post_time', time());
    $forumpost->setVar('post_text', _MI_NEWBB_INSTALL_POST_TEXT, true);
    $postid = $post_handler->insert($forumpost);

    return true;
}

function newbb_setModuleConfig(&$module, $isUpdate = false)
{
    return true;
}
?>