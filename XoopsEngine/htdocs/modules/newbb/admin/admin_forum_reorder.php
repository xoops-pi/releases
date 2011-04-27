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
 * @version         $Id: admin_forum_reorder.php 2167 2008-09-23 13:33:57Z phppp $
 */

include 'admin_header.php';

if (isset($_POST['cat_orders'])) $cat_orders = $_POST['cat_orders'];
if (isset($_POST['orders'])) $orders = $_POST['orders'];
if (isset($_POST['cat'])) $cat = $_POST['cat'];
if (isset($_POST['forum'])) $forum = $_POST['forum'];

if (!empty($_POST['submit'])) {
    for ($i = 0; $i < count($cat_orders); $i++) {
        $sql = "update " . $xoopsDB->prefix("bb_categories") . " set cat_order = " . $cat_orders[$i] . " WHERE cat_id=$cat[$i]";
        if (!$result = $xoopsDB->query($sql)) {
            redirect_header("admin_forum_reorder.php", 1, _AM_NEWBB_FORUM_ERROR);
        }
    }

    for ($i = 0; $i < count($orders); $i++) {
        $sql = "update " . $xoopsDB->prefix("bb_forums") . " set forum_order = " . $orders[$i] . " WHERE forum_id=".$forum[$i];
        if (!$result = $xoopsDB->query($sql)) {
            redirect_header("admin_forum_reorder.php", 1, _AM_NEWBB_FORUM_ERROR);
        }
    }
    redirect_header("admin_forum_reorder.php", 1, _AM_NEWBB_BOARDREORDER);
} else {
    include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
    $orders = array();
    $cat_orders = array();
    $forum = array();
    $cat = array();

    xoops_cp_header();
    loadModuleAdminMenu(6, _AM_NEWBB_SETFORUMORDER);
    echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_SETFORUMORDER . "</legend>";
    echo"<br /><br /><table width='100%' border='0' cellspacing='1' class='outer'>"
     . "<tr><td class='odd'>";
    $tform = new XoopsThemeForm(_AM_NEWBB_SETFORUMORDER, "", "");
    $tform->display();
    echo "<form name='reorder' method='post'>";
    echo "<table border='0' width='100%' cellpadding='2' cellspacing='1' class='outer'>";
    echo "<tr>";
    echo "<td class='head' align='left' width='60%'><strong>" . _AM_NEWBB_REORDERTITLE . "</strong></td>";
    echo "<td class='head' align='center'><strong>" . _AM_NEWBB_REORDERWEIGHT . "</strong></td>";
    echo "</tr>";

    $forum_handler = &xoops_getmodulehandler('forum', 'newbb');
    $category_handler = &xoops_getmodulehandler('category', 'newbb');
    $criteria_category = new CriteriaCompo(new criteria('1', 1));
    $criteria_category->setSort('cat_order');
    $categories = $category_handler->getAll($criteria_category, array("cat_id", "cat_order", "cat_title"));
    $forums = $forum_handler->getTree(array_keys($categories), 0, 'all', "&nbsp;&nbsp;&nbsp;&nbsp;");
    foreach (array_keys($categories) as $c) {
        echo "<tr>";
        echo "<td align='left' nowrap='nowrap' class='head' >" . $categories[$c]->getVar("cat_title") . "</td>";
        echo "<td align='right' class='head'>";
        echo "<input type='text' name='cat_orders[]' value='" . $categories[$c]->getVar('cat_order') . "' size='5' maxlength='5' />";
        echo "<input type='hidden' name='cat[]' value='" . $c . "' />";
        echo "</td>";
        echo "</tr>";

        if (!isset($forums[$c])) continue;
        $i = 0;
        foreach ($forums[$c] as $key => $forum) {
            echo "<tr>";
            $class = ((++$i) % 2) ? "odd" : "even";
            echo "<td align='left' nowrap='nowrap' class='" . $class . "'>" . $forum['prefix'] . $forum['forum_name'] . "</td>";
            echo "<td align='left' class='" . $class . "'>";
            echo $forum['prefix'] . "<input type='text' name='orders[]' value='" . $forum['forum_order'] . "' size='5' maxlength='5' />";
            echo "<input type='hidden' name='forum[]' value='" . $key . "' />";
            echo "</td>";
            echo "</tr>";
        }
    }
    echo "<tr><td class='even' align='center' colspan='6'>";

    echo "<input type='submit' name='submit' value='" . _SUBMIT . "' />";

    echo "</td></tr>";
    echo "</table>";
    echo "</form>";
}

echo"</td></tr></table>";
echo "</fieldset>";
xoops_cp_footer();

?>