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
 * @version         $Id: admin_digest.php 2167 2008-09-23 13:33:57Z phppp $
 */

include 'admin_header.php';
include_once XOOPS_ROOT_PATH . "/class/pagenav.php";

$op = !empty($_GET['op'])? $_GET['op'] : (!empty($_POST['op']) ? $_POST['op'] : "default");
$item = !empty($_GET['op'])? $_GET['item'] : (!empty($_POST['item']) ? $_POST['item'] : "process");

$start = (isset($_GET['start']))?$_GET['start'] : 0;

xoops_cp_header();
switch($op) {
    case "delete":
        $digest_ids = $_POST['digest_id'];
        $digest_handler =& xoops_getmodulehandler('digest', 'newbb');
        foreach ($digest_ids as $did => $value) {
            $digest_handler->delete($did);
        }
        redirect_header( "admin_digest.php", 1);
        break;

    default:
        include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";

        $limit = 5;
        loadModuleAdminMenu(9, _AM_NEWBB_DIGESTADMIN);
        echo "<fieldset><legend style='font-weight: bold; color: #900;'>" .  _AM_NEWBB_DIGESTADMIN . "</legend>";
        echo"<br />";
        echo '<form action="' . xoops_getenv('PHP_SELF') . '" method="post">';
        echo "<table border='0' cellpadding='4' cellspacing='1' width='100%' class='outer'>";
        echo "<tr align='center'>";
        echo "<td class='bg3'>" . _AM_NEWBB_DIGESTCONTENT . "</td>";
        echo "<td class='bg3' width='2%'>" . _DELETE . "</td>";
        echo "</tr>";

        $digest_handler =& xoops_getmodulehandler('digest', 'newbb');
        $digests =& $digest_handler->getAllDigests($start, $limit);
        foreach ($digests as $digest) {
            echo "<tr class='odd' align='left'>";
            echo "<td><strong>#" . $digest['digest_id'] . ' @ '. formatTimestamp($digest['digest_time']) . '</strong><br />' . str_replace("\n","<br />", $digest['digest_content']) . "</td>";
            echo "<td align='center' ><input type='checkbox' name='digest_id[" . $digest['digest_id'] . "]' value='1' /></td>";
            echo "</tr>";
            echo "<tr colspan='2'><td height='2'></td></tr>";
        }
        $submit = new XoopsFormButton('', 'submit', _SUBMIT, 'submit');
        echo "<tr colspan='2'><td align='center'>" . $submit->render() . "</td></tr>";
        $hidden = new XoopsFormHidden('op', 'delete');
        echo $hidden->render();
        $hidden = new XoopsFormHidden('item', $item);
        echo $hidden->render() . "</form>";

        echo "</table>";

        $nav = new XoopsPageNav($digest_handler->getDigestCount(), $limit, $start, "start");
        echo $nav->renderNav(4);

        echo "</fieldset>";

        break;
}
xoops_cp_footer();

?>