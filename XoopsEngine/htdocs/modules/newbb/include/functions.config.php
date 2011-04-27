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
 * @version         $Id: functions.config.php 2170 2008-09-23 13:40:23Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

function newbb_loadConfig()
{
    return XOOPS::service("registry")->config->read("newbb");
    
    global $xoopsModuleConfig;
    static $moduleConfig;
    
    if (isset($moduleConfig)) {
        return $moduleConfig;
    }
    
    if (isset($GLOBALS["xoopsModule"]) && is_object($GLOBALS["xoopsModule"]) && $GLOBALS["xoopsModule"]->getVar("dirname", "n") == "newbb") {
        if (!empty($GLOBALS["xoopsModuleConfig"])) {
            $moduleConfig = $GLOBALS["xoopsModuleConfig"];
        } else {
            return null;
        }
    } else {
        $module_handler =& xoops_gethandler('module');
        $module = $module_handler->getByDirname("newbb");
    
        $config_handler =& xoops_gethandler('config');
        $criteria = new CriteriaCompo(new Criteria('conf_modid', $module->getVar('mid')));
        $configs = $config_handler->getConfigs($criteria);
        foreach (array_keys($configs) as $i) {
            $moduleConfig[$configs[$i]->getVar('conf_name')] = $configs[$i]->getConfValueForOutput();
        }
        unset($configs);
    }
    if ($customConfig = @include XOOPS_ROOT_PATH . "/modules/newbb/include/plugin.php") {
        $moduleConfig = array_merge($moduleConfig, $customConfig);
    }
    
    return $moduleConfig;
}

?>