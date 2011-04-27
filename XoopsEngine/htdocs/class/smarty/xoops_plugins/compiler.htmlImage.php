<?php
/**
 * XOOPS smarty compiler plugin
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       The Xoops Engine http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @since           3.0
 * @package         Xoops_Smarty
 * @version         $Id$
 */

/**
 * Inserts an htmlImage element
 * @see @Xoops_Zend_View_Helper_HtmlImage
 *
 * <code>
 * <{htmlImage src=uri alt='short description' width=80}>
 * </code>
 */
function smarty_compiler_htmlImage($argStr, $compiler)
{
    global $xoops;

    $argStr = trim($argStr);
    $params = $compiler->_parse_attrs($argStr);
    if (empty($params['src'])) return false;
    $src = $params['src'];
    unset($params['src']);
    if (!isset($params['alt'])) {
        $alt = "";
    } else {
        $alt = (string) $params['alt'];
        unset($params['alt']);
    }

    if (false === strpos($argStr, "$")) {
        $src = $compiler->_dequote($src);
        $alt = $compiler->_dequote($alt);
        foreach ($params as $k => $v) {
            $params[$k] = $compiler->_dequote($v);
        }
        $str = XOOPS::registry("view")->htmlImage($src, $alt, $params);
        //$str = '"' . addslashes($str) . '"';
        return '?>' . $str . '<?php';
    } else {
        $pars = array();
        foreach ($params as $k => $v) {
            $pars[] = var_export($k, true) . " => {$v}";
        }
        $str = "XOOPS::registry(\"view\")->htmlImage({$src}, {$alt}, array(" . implode(", ", $pars) . "))";
        return "echo {$str};";
    }
}
?>