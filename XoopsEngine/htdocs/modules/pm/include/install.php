<?php
/**
 * Private Message
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
 * @package         pm
 * @since           2.3.0
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @version         $Id: install.php 2022 2008-08-31 02:07:17Z phppp $
 */

function xoops_module_install_pm($module)
{
    global $xoopsDB;

    // Check pm table version
    $sql = "SHOW COLUMNS FROM " . $xoopsDB->prefix("priv_msgs");
    if (!$result = $xoopsDB->queryF($sql)) {
        return false;
    }
    // Migrate from existent pm module
    if ( ($rows = $xoopsDB->getRowsNum($result)) == 12) {
        return true;
    } elseif ($rows == 8) {
        return $xoopsDB->queryFromFile(XOOPS_ROOT_PATH . "/modules/" . $module->getVar('dirname', 'n') . "/sql/mysql.upgrade.sql");
    } else {
        return false;
    }
}
?>