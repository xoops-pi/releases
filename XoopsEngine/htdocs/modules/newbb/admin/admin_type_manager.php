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
 * @version         $Id: admin_type_manager.php 2167 2008-09-23 13:33:57Z phppp $
 */

include 'admin_header.php';
include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
xoops_cp_header();

/*
 * The 'op' could be
 * <ol>
 *    <li>'save_type': saving for batch edit or add</li>
 *    <li>'delete': batch delete</li>
 *    <li>'template': set type setting template</li>
 *    <li>'apply': apply template to forums</li>
 *    <li>'forum': type setting per forum</li>
 *    <li>'add': batch add</li>
 *    <li>default: list of existing types</li>
 * </ol>
 */
$op = !empty($_GET['op']) ? $_GET['op'] : ( !empty($_POST['op']) ? $_POST['op'] : "" );

$type_handler =& xoops_getmodulehandler('type', 'newbb');

switch ($op) {
case "save_type":
    $type_names = $_POST['type_name'];
    $type_del = array();
    foreach (array_keys($type_names) as $key) {
        if (!empty($_POST["isnew"])) {
            $type_obj =& $type_handler->create();
        } elseif (!$type_obj =& $type_handler->get($key)) {
            continue;
        }
        if (!empty($_POST['type_del'][$key])) {
            $type_del[] = $key;
            continue;
        } else {
            foreach (array("type_name", "type_color", "type_description") as $var) {
                if ($type_obj->getVar($var) != @$_POST[$var][$key]) {
                    $type_obj->setVar($var, @$_POST[$var][$key]);
                }
            }
            $type_handler->insert($type_obj);
            unset($type_obj);
        }
    }
    if (count($type_del) >0) {
        $type_list = $type_handler->getList(new Criteria("type_id", "(" . implode(", ", $type_del) . ")", "IN"));
        xoops_confirm(array('op' => 'delete', 'type_del' => serialize($type_del)), xoops_getenv("PHP_SELF"), sprintf(_AM_NEWBB_TODEL_TYPE, implode(", ", array_values($type_list))), '', false);
    } else {
        redirect_header(xoops_getenv("PHP_SELF"), 2, _MD_DBUPDATED);
    }
    break;

case "delete":
    $type_dels = @unserialize($_POST['type_del']);
    foreach ($type_dels as $key) {
        if (!$type_obj =& $type_handler->get($key)) {
            continue;
        }
        $type_handler->delete($type_obj);
        unset($type_obj);
    }
    redirect_header(xoops_getenv("PHP_SELF"), 2, _MD_DBUPDATED);
    break;

case "template":
    $types_obj = $type_handler->getAll();
    if (count($types_obj) ==0) {
        redirect_header(xoops_getenv("PHP_SELF"), 2, _AM_NEWBB_TYPE_ADD);
        exit();
    }

    loadModuleAdminMenu(11, _AM_NEWBB_TYPE_TEMPLATE);
    echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_ACTION . "</legend>";
    echo "<br />";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=add'>";
    echo     _AM_NEWBB_TYPE_ADD . "</a> | ";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=template'>";
    echo     _AM_NEWBB_TYPE_TEMPLATE . "</a> | ";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=apply'>";
    echo     _AM_NEWBB_TYPE_TEMPLATE_APPLY . "</a> | ";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=forum'>";
    echo     _AM_NEWBB_TYPE_FORUM . "</a> | ";
    echo "</fieldset>";
    echo "<br />";
    echo "<br />";
    
    echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_TYPE_TEMPLATE . "</legend>";
    echo "<br />";

    echo "<form name='template' method='post' action='" . xoops_getenv("PHP_SELF") . "'>";
    echo "<table border='0' cellpadding='4' cellspacing='1' width='100%' class='outer'>";
    echo "<tr align='center'>";
    echo "<td class='bg3' width='20%'>" . _AM_NEWBB_TYPE_ORDER . "</td>";
    echo "<td class='bg3' width='20%'>" . _AM_NEWBB_TYPE_NAME . "</td>";
    echo "<td class='bg3'>" . _AM_NEWBB_TYPE_DESCRIPTION . "</td>";
    echo "</tr>";

    if ($templates = mod_loadCacheFile("type_template")) {
        arsort($templates);
        foreach ($templates as $order => $key) {
            if (!isset($types_obj[$key])) continue;
            $type_obj =& $types_obj[$key];
            echo "<tr class='even' align='left'>";
            echo "<td><input type='text' name='type_order[{$key}]' value='" . $order . "' size='10' /></td>";
            echo "<td><em style='color:" . $type_obj->getVar("type_color") . "'>" . $type_obj->getVar("type_name") . "</em></td>";
            echo "<td>" . $type_obj->getVar("type_description") . "</td>";
            echo "</tr>";
            unset($types_obj[$key]);
        }
        echo "<tr><td colspan='3' height='5px'></td></tr>";
    }
    foreach ($types_obj as $key => $type_obj) {
        echo "<tr class='odd' align='left'>";
        echo "<td><input type='text' name='type_order[{$key}]' value='0' size='10' /></td>";
        echo "<td><em style='color:" . $type_obj->getVar("type_color") . "'>" . $type_obj->getVar("type_name") . "</em></td>";
        echo "<td>" . $type_obj->getVar("type_description") . "</td>";
        echo "</tr>";
    }
    
    echo "<tr><td colspan='3'>";
    echo _AM_NEWBB_TYPE_ORDER_DESC . "<br /><br />";
    echo "<input type='hidden' name='op' value='save_template' />";
    echo "<input type='submit' name='submit' value='" . _SUBMIT . "' /> ";
    echo "<input type='reset' value='" . _CANCEL . "' />";
    echo "</td></tr></table>";
    echo "</form>";
    echo "</fieldset>";
    break;
    
case "save_template":
    $templates = array_flip(array_filter($_POST['type_order']));
    mod_createCacheFile($templates, "type_template");
    redirect_header(xoops_getenv("PHP_SELF")."?op=template", 2, _MD_DBUPDATED);
    break;

case "apply":
    if (!$templates = mod_loadCacheFile("type_template")) {
        redirect_header(xoops_getenv("PHP_SELF") . "?op=template", 2, _AM_NEWBB_TYPE_TEMPLATE);
        exit();
    }
    
    $category_handler =& xoops_getmodulehandler('category', 'newbb');
    $criteria_category = new CriteriaCompo(new criteria('1', 1));
    $criteria_category->setSort('cat_order');
    $categories = $category_handler->getList($criteria_category);
    $forum_handler =& xoops_getmodulehandler('forum', 'newbb');
    $forums = $forum_handler->getTree(array_keys($categories), 0, "all");
    foreach (array_keys($forums) as $c) {
        $fm_options[-1*$c] = "[".$categories[$c]."]";
        foreach (array_keys($forums[$c]) as $f) {
            $fm_options[$f] = $forums[$c][$f]["prefix"].$forums[$c][$f]["forum_name"];
        }
    }
    unset($forums, $categories);        
    $fmform = new XoopsThemeForm(_AM_NEWBB_TYPE_TEMPLATE_APPLY, 'fmform', xoops_getenv("PHP_SELF"), "post");
    $fm_select = new XoopsFormSelect(_AM_NEWBB_PERM_FORUMS, 'forums', null, 10, true);
    $fm_select->addOptionArray($fm_options);
    $fmform->addElement($fm_select);
    $tray = new XoopsFormElementTray('');
    $tray->addElement(new XoopsFormHidden('op', 'save_apply'));
    $tray->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
    $tray->addElement(new XoopsFormButton('', 'reset', _CANCEL, 'reset'));
    $fmform->addElement($tray);
    
    loadModuleAdminMenu(11, _AM_NEWBB_TYPE_TEMPLATE_APPLY);
    echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_ACTION . "</legend>";
    echo "<br />";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=add'>";
    echo     _AM_NEWBB_TYPE_ADD . "</a> | ";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=template'>";
    echo     _AM_NEWBB_TYPE_TEMPLATE . "</a> | ";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=apply'>";
    echo     _AM_NEWBB_TYPE_TEMPLATE_APPLY . "</a> | ";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=forum'>";
    echo     _AM_NEWBB_TYPE_FORUM . "</a> | ";
    echo "</fieldset>";
    echo "<br />";
    echo "<br />";
    
    echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_TYPE_TEMPLATE . "</legend>";
    echo "<br />";

    echo "<table border='0' cellpadding='4' cellspacing='1' width='100%' class='outer'>";
    echo "<tr align='center'>";
    echo "<td class='bg3' width='20%'>" . _AM_NEWBB_TYPE_NAME . "</td>";
    echo "<td class='bg3' width='20%'>" . _AM_NEWBB_TYPE_ORDER . "</td>";
    echo "<td class='bg3'>" . _AM_NEWBB_TYPE_DESCRIPTION . "</td>";
    echo "</tr>";

    $types_obj = $type_handler->getAll(new Criteria("type_id", "(" . implode(", ", array_values($templates)) . ")", "IN"));
    arsort($templates);
    foreach ($templates as $order => $key) {
        if (!isset($types_obj[$key])) continue;
        $type_obj =& $types_obj[$key];
        echo "<tr class='even' align='left'>";
        echo "<td><em style='color:" . $type_obj->getVar("type_color") . "'>" . $type_obj->getVar("type_name") . "</em></td>";
        echo "<td>" . $order . "</td>";
        echo "<td>" . $type_obj->getVar("type_description") . "</td>";
        echo "</tr>";
        unset($types_obj[$key]);
    }
    echo "</table>";
    echo "<br />";
    $fmform->display();
    echo "</fieldset>";
    break;
        
case "save_apply":
    if (!$templates = mod_loadCacheFile("type_template")) {
        redirect_header(xoops_getenv("PHP_SELF") . "?op=template", 2, _AM_NEWBB_TYPE_TEMPLATE);
        exit();
    }
    foreach ($_POST["forums"] as $forum) {
        if ($forum < 1) continue;
        $type_handler->updateByForum($forum, array_flip($templates));
    }
    redirect_header(xoops_getenv("PHP_SELF"), 2, _MD_DBUPDATED);
    break;
    

case "forum":
    $category_handler =& xoops_getmodulehandler('category', 'newbb');
    $criteria_category = new CriteriaCompo(new criteria('1', 1));
    $criteria_category->setSort('cat_order');
    $categories = $category_handler->getList($criteria_category);
    if (empty($categories)) {
        redirect_header("admin_cat_manager.php", 2, _AM_NEWBB_CREATENEWCATEGORY);
        exit();
    }
    $forum_handler =& xoops_getmodulehandler('forum', 'newbb');
    $forums = $forum_handler->getTree(array_keys($categories));
    if (empty($forums)) {
        redirect_header("admin_forum_manager.php", 2, _AM_NEWBB_CREATENEWFORUM);
        exit();
    }
    
    foreach (array_keys($forums) as $c) {
        $fm_options[-1 * $c] = "[" . $categories[$c] . "]";
        foreach (array_keys($forums[$c]) as $f) {
            $fm_options[$f] = $forums[$c][$f]["prefix"] . $forums[$c][$f]["forum_name"];
        }
    }
    unset($forums, $categories);
    $fmform = new XoopsThemeForm(_AM_NEWBB_TYPE_FORUM, 'fmform', xoops_getenv("PHP_SELF"), "post");
    $fm_select = new XoopsFormSelect(_AM_NEWBB_PERM_FORUMS, 'forum', null, 5, false);
    $fm_select->addOptionArray($fm_options);
    $fmform->addElement($fm_select);
    $tray = new XoopsFormElementTray('');
    $tray->addElement(new XoopsFormHidden('op', 'edit_forum'));
    $tray->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
    $tray->addElement(new XoopsFormButton('', 'reset', _CANCEL, 'reset'));
    $fmform->addElement($tray);
    
    loadModuleAdminMenu(11, _AM_NEWBB_TYPE_FORUM);
    echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_ACTION . "</legend>";
    echo "<br />";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=add'>";
    echo     _AM_NEWBB_TYPE_ADD . "</a> | ";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=template'>";
    echo     _AM_NEWBB_TYPE_TEMPLATE . "</a> | ";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=forum'>";
    echo     _AM_NEWBB_TYPE_FORUM . "</a> | ";
    echo "</fieldset>";
    echo "<br />";
    echo "<br />";
    
    echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_TYPE_FORUM . "</legend>";
    echo "<br />";
    $fmform->display();
    echo "</fieldset>";
    break;
        
case "edit_forum":
    if (empty($_POST["forum"]) || $_POST["forum"] < 1) {
        redirect_header(xoops_getenv("PHP_SELF") . "?op=forum", 2, _AM_NEWBB_TYPE_FORUM);
        exit();
    }

    $forum_handler =& xoops_getmodulehandler('forum', 'newbb');
    if ( !$forum_obj = $forum_handler->get(intval($_POST["forum"])) ) {
        redirect_header(xoops_getenv("PHP_SELF") . "?op=forum", 2, _AM_NEWBB_TYPE_FORUM);
        exit();
    }
    
    $types_obj = $type_handler->getAll();
    if (count($types_obj) ==0) {
        redirect_header(xoops_getenv("PHP_SELF"), 2, _AM_NEWBB_TYPE_ADD);
        exit();
    }
    
    loadModuleAdminMenu(11, _AM_NEWBB_TYPE_FORUM);
    echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_ACTION . "</legend>";
    echo "<br />";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=add'>";
    echo     _AM_NEWBB_TYPE_ADD . "</a> | ";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=template'>";
    echo     _AM_NEWBB_TYPE_TEMPLATE . "</a> | ";
    echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=forum'>";
    echo     _AM_NEWBB_TYPE_FORUM . "</a> | ";
    echo "</fieldset>";
    echo "<br />";
    echo "<br />";
    echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_TYPE_FORUM . "</legend>";
    echo "<br />";
    
    echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_TYPE_FORUM . " - " . $forum_obj->getVar("forum_name") . "</legend>";
    echo "<form name='template' method='post' action='" . xoops_getenv("PHP_SELF") . "'>";
    echo "<table border='0' cellpadding='4' cellspacing='1' width='100%' class='outer'>";
    echo "<tr align='center'>";
    echo "<td class='bg3' width='20%'>" . _AM_NEWBB_TYPE_ORDER . "</td>";
    echo "<td class='bg3' width='20%'>" . _AM_NEWBB_TYPE_NAME . "</td>";
    echo "<td class='bg3'>" . _AM_NEWBB_TYPE_DESCRIPTION . "</td>";
    echo "</tr>";

    $types = $type_handler->getByForum(intval($_POST["forum"]));
    $types_order = array();
    foreach ($types as $key => $type) {
        $types_order[] = $type["type_order"];
    }
    array_multisort($types_order, $types);
    foreach ($types as $key => $type) {
        if (!isset($types_obj[$type["type_id"]])) continue;
        $type_obj =& $types_obj[$type["type_id"]];
        echo "<tr class='even' align='left'>";
        echo "<td><input type='text' name='type_order[".$type["type_id"]."]' value='" . $type["type_order"] . "' size='10' /></td>";
        echo "<td><em style='color:" . $type_obj->getVar("type_color") . "'>" . $type_obj->getVar("type_name") . "</em></td>";
        echo "<td>" . $type_obj->getVar("type_description") . "</td>";
        echo "</tr>";
        unset($types_obj[$type["type_id"]]);
    }
    echo "<tr><td colspan='3' height='5px'></td></tr>";
    foreach ($types_obj as $key => $type_obj) {
        echo "<tr class='odd' align='left'>";
        echo "<td><input type='text' name='type_order[{$key}]' value='0' size='10' /></td>";
        echo "<td><em style='color:" . $type_obj->getVar("type_color") . "'>" . $type_obj->getVar("type_name") . "</em></td>";
        echo "<td>" . $type_obj->getVar("type_description") . "</td>";
        echo "</tr>";
    }
    
    echo "<tr><td colspan='3'>";
    echo "<ul><li>" . _AM_NEWBB_TYPE_EDITFORUM_DESC . "</li>";
    echo "<li>" . _AM_NEWBB_TYPE_ORDER_DESC . "</li></ol><br />";
    echo "<input type='hidden' name='forum' value='" . intval($_POST["forum"]) . "' />";
    echo "<input type='hidden' name='op' value='save_forum' />";
    echo "<input type='submit' name='submit' value='" . _SUBMIT . "' /> ";
    echo "<input type='reset' value='" . _CANCEL . "' />";
    echo "</td></tr></table>";
    echo "</form>";
    echo "</fieldset>";
    break;

case "save_forum":
    if (empty($_POST["forum"]) || $_POST["forum"] < 1) {
        redirect_header(xoops_getenv("PHP_SELF") . "?op=forum", 2, _AM_NEWBB_TYPE_FORUM);
        exit();
    }
    $type_handler->updateByForum(intval($_POST["forum"]), $_POST["type_order"]);
    redirect_header(xoops_getenv("PHP_SELF") . "?op=forum", 2, _MD_DBUPDATED);
    break;
    
case "add":
default:
    $types_obj = $type_handler->getAll();
    if (count($types_obj)==0) {
        $op = "add";
        $title = _AM_NEWBB_TYPE_ADD;
    } else {
        $title = _AM_NEWBB_TYPE_LIST;
    }

    loadModuleAdminMenu(11, $title);
    if ($op != "add") {
        echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_ACTION . "</legend>";
        echo "<br />";
        echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=add'>";
        echo     _AM_NEWBB_TYPE_ADD . "</a> | ";
        echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=template'>";
        echo     _AM_NEWBB_TYPE_TEMPLATE . "</a> | ";
        echo "<a style='border: 1px solid #5E5D63; color: #000000; font-family: verdana, tahoma, arial, helvetica, sans-serif; font-size: 1em; padding: 4px 8px; text-align:center;' href='" . xoops_getenv("PHP_SELF") . "?op=forum'>";
        echo     _AM_NEWBB_TYPE_FORUM . "</a> | ";
        echo "</fieldset>";
        echo "<br />";
        echo "<br />";
    }
    
    echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . $title . "</legend>";
    echo "<br />";

    echo "<form name='list' method='post' action='" . xoops_getenv("PHP_SELF") . "'>";
    echo "<table border='0' cellpadding='4' cellspacing='1' width='100%' class='outer'>";
    echo "<tr align='center'>";
    if ($op != "add") {
        echo "<td class='bg3' width='5%'>" . _DELETE . "</td>";
    }
    echo "<td class='bg3' width='20%'>" . _AM_NEWBB_TYPE_NAME . "</td>";
    echo "<td class='bg3' width='15%'>" . _AM_NEWBB_TYPE_COLOR . "</td>";
    echo "<td class='bg3'>" . _AM_NEWBB_TYPE_DESCRIPTION . "</td>";
    echo "</tr>";

    $isColorpicker = require_once XOOPS_ROOT_PATH . "/class/xoopsform/formcolorpicker.php";
    
    if ($op != "add") {
        foreach ($types_obj as $key => $type_obj) {
            echo "<tr class='odd' align='left'>";
            echo "<td><input type='checkbox' name='type_del[{$key}]' /></td>";
            echo "<td><input type='text' name='type_name[{$key}]' value='" . $type_obj->getVar("type_name") . "' size='10' /></td>";
            if ($isColorpicker) {
                $form_colorpicker = new XoopsFormColorPicker("", "type_color[{$key}]", $type_obj->getVar("type_color"));
                echo "<td>" . $form_colorpicker->render() . "</td>";
            } else {
                echo "<td><input type='text' name='type_color[{$key}]' value='" . $type_obj->getVar("type_color") . "' size='10' /></td>";
            }
            echo "<td><input type='text' name='type_description[{$key}]' value='" . $type_obj->getVar("type_description") . "' size='30' /></td>";
            echo "</tr>";
        }
        echo "<tr><td colspan='4'>";
    } else {
        for($i = 0; $i < 10; $i++) {
            echo "<tr class='odd' align='left'>";
            echo "<td><input type='text' name='type_name[{$i}]' value='' size='10' /></td>";
            if ($isColorpicker) {
                $form_colorpicker = new XoopsFormColorPicker("", "type_color[{$i}]", "");
                echo "<td>" . $form_colorpicker->render() . "</td>";
            } else {
                echo "<td><input type='text' name='type_color[{$i}]' value='' size='10' /></td>";
            }
            echo "<td><input type='text' name='type_description[{$i}]' value='' size='40' /></td>";
            echo "</tr>";
        }
        echo "<tr><td colspan='3'>";
        echo "<input type='hidden' name='isnew' value='1' />";
    }
    echo "<input type='hidden' name='op' value='save_type' />";
    echo "<input type='submit' name='submit' value='" . _SUBMIT . "' /> ";
    echo "<input type='reset' value='" . _CANCEL . "' />";
    echo "</td></tr></table>";
    echo "</form>";
    echo "</fieldset>";
    break;
}

xoops_cp_footer();
?>