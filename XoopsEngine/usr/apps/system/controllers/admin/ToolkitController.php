<?php
/**
 * System admin toolkit controller
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

class System_ToolkitController extends Xoops_Zend_Controller_Action_Admin
{
    public function cacheAction()
    {
        $this->template->assign('cache_list', XOOPS::_('Cache List'));
        $caches = array(
            "stat"      => array("title"        => XOOPS::_("File stats"),
                                "description"   => XOOPS::_("Remove file status caches.")),
            "compile"   => array("title"        => XOOPS::_("Compiled templates"),
                                "description"   => XOOPS::_("Remove compiled smarty templates in smarty_compile folder.")),
            "content"   => array("title"        => XOOPS::_("Cached page content"),
                                "description"   => XOOPS::_("Remove cached page content data generated by smarty in smarty_cache folder.")),
            "model"     => array("title"        => XOOPS::_("System model cache"),
                                "description"   => XOOPS::_("Remove cached model table meta data in cache folder.")),
            "config"    => array("title"        => XOOPS::_("Configuration cache"),
                                "description"   => XOOPS::_("Remove cached configuration data in cache folder.")),
            "event"     => array("title"        => XOOPS::_("Event cache"),
                                "description"   => XOOPS::_("Remove cached event configuration data in cache folder.")),
            "module"    => array("title"        => XOOPS::_("Module cache"),
                                "description"   => XOOPS::_("Remove module meta data in xoops_cache folder.")),
            "modulelist"    => array("title"    => XOOPS::_("Module list"),
                                "description"   => XOOPS::_("Remove cached module list data in xoops_cache folder.")),
            "theme"     => array("title"        => XOOPS::_("Theme list"),
                                "description"   => XOOPS::_("Remove cached theme list data in xoops_cache folder.")),
            "translate"     => array("title"    => XOOPS::_("Locale and translation cache"),
                                "description"   => XOOPS::_("Remove cached translation data in xoops_cache folder.")),
            "navigation"    => array("title"    => XOOPS::_("Menu and navigation cache"),
                                "description"   => XOOPS::_("Remove cached menu and navigation data in xoops_cache folder.")),
            "all"       => array("title"        => XOOPS::_("All system caches"),
                                "description"   => XOOPS::_("Remove all cache files in xoops_cache folder. Be careful, the operation may take long time.")),
            );
        if ('flush' != $this->_getParam('op')) {
            $this->template->assign('flush', "Refresh");
            $this->template->assign('caches', $caches);
        } else {
            $key = $this->_getParam('key');
            switch ($key) {
                case 'stat':
                    clearstatcache(true);
                    break;
                case 'model':
                    XOOPS::registry('cache')->clean('matchingTag', array('model_cache'));
                    break;
                case 'compile':
                    XOOPS::registry('view')->getEngine()->clearTemplate('', '');
                    break;
                case 'content':
                    XOOPS::registry('view')->getEngine()->clearCaches('', '');
                    break;
                case 'config':
                case 'navigation':
                case 'translate':
                case 'event':
                case 'module':
                case 'modulelist':
                case 'theme':
                    XOOPS::service('registry')->{$key}->flush();
                    break;
                case 'all':
                default:
                    $path = XOOPS::path("var") . "/cache/";
                    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($objects as $object) {
                        if ($object->isFile() && 'index.html' !== $object->getFilename()) {
                            unlink($object->getPathname());
                        }
                    }

                    XOOPS::persist()->clean();
                    //XOOPS::registry('cache')->clean('all');
                    break;
            }
            $message = sprintf(XOOPS::_("%s has been flushed."), $caches[$key]['title']);

            $this->redirect(
                array('controller' => 'toolkit', 'action' => 'cache', 'reset' => true),
                array('message' => $message, 'time' => 3)
            );
        }
    }

    public function __call($method, $args)
    {
        Debug::e($method . ' called');
    }
}