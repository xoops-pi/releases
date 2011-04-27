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
 * @version         $Id: update.php 2022 2008-08-31 02:07:17Z phppp $
 */

function xoops_module_update_pm(&$module, $oldversion = null) 
{
    GLOBAL $xoopsDB;
    
    return true;
    
    if ($oldversion <= 100) {
        // Create new tables for new profile module
        $xoopsDB->queryFromFile(XOOPS_ROOT_PATH . "/modules/" . $module->getVar('dirname', 'n') . "/sql/mysql.sql");
    
        // Check if legacy pm table exists
        $sql = "SHOW TABLES LIKE '" . $xoopsDB->prefix("priv_msgs") . "'";
        if (!$result = $xoopsDB->queryF($sql)) {
            return true;
        }
        if ($xoopsDB->getRowsNum($result) == 0) {
            return true;
        }
        
        // Check pm table version
        $sql = "SHOW COLUMNS FROM " . $xoopsDB->prefix("priv_msgs");
        if (!$result = $xoopsDB->queryF($sql)) {
            return true;
        }
        // Migrate from existent pm module
        if ( ($rows = $xoopsDB->getRowsNum($result)) == 12) {
            $sql = "INSERT INTO `" . $xoopsDB->prefix("pm_messages") . "` SELECT * FROM `" . $xoopsDB->prefix("priv_msgs") . "`";
        } elseif ($rows == 8) {
            $fields = "`msg_id`, `msg_image`, `subject`, `from_userid`, `to_userid`, `msg_time`, `msg_text`, `read_msg`";
            $sql = "INSERT INTO `" . $xoopsDB->prefix("pm_messages") . "` ({$fields}) SELECT ({$fields}) FROM `" . $xoopsDB->prefix("priv_msgs") . "`";
        } else {
            return true;
        }
        $result = $xoopsDB->queryF($sql);
        return true;
    }
    
    return true;
}
?>