<?php
/**
 * Startpage selection element
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Xoops Engine http://www.xoopsengine.org
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @since           3.0
 * @category        Xoops_Module
 * @package         System
 * @version         $Id$
 */

class App_System_Form_Element_Startpage extends Zend_Form_Element_Select
//class System_Form_Element_Startpage extends Zend_Form_Element_Select
{
    /**
     * Constructor
     *
     * @param  string|array|Zend_Config $spec Element name or configuration
     * @param  string|array|Zend_Config $options Element value or configuration
     * @return void
     */
    public function __construct($spec, $options = null)
    {
        parent::__construct($spec, $options);
        $this->setServiceOptions();
    }

    protected function setServiceOptions()
    {
        $moduleList = XOOPS::service("registry")->module->read();
        if (isset($moduleList["system"])) {
            unset($moduleList["system"]);
        }
        $modules = array("" => XOOPS::_("None"));
        foreach ($moduleList as $key => $module) {
            $modules[$key] = $module["name"];
        }
        $this->setMultiOptions($modules);
    }
}