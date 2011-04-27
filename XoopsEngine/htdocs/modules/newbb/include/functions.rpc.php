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
 * @version         $Id: functions.rpc.php 2284 2008-10-12 03:45:46Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

/**
 * Function to respond to a trackback
 */
function newbb_trackback_response($error = 0, $error_message = '') 
{
    require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.config.php";
    $moduleConfig = newbb_loadConfig();
    
    if (!empty($moduleConfig["rss_utf8"])) {
        $charset = "utf-8";
        $error_message = xoops_utf8_encode($error_message);
    } else {
        $charset = _CHARSET;
    }
    header('Content-Type: text/xml; charset="' . $charset . '"');
    if ($error) {
        echo '<?xml version="1.0" encoding="' . $charset . '"?'.">\n";
        echo "<response>\n";
        echo "<error>1</error>\n";
        echo "<message>{$error_message}</message>\n";
        echo "</response>";
        die();
    } else {
        echo '<?xml version="1.0" encoding="' . $charset . '"?'.">\n";
        echo "<response>\n";
        echo "<error>0</error>\n";
        echo "</response>";
    }
}
?>