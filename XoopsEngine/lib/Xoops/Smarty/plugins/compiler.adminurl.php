<?php
/**
 * Smarty compiler plugin for Xoops Engine
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Xoops Engine http://www.xoopsengine.org/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @since           3.0
 * @package         Xoops_Smarty
 * @version         $Id$
 */

/**
 * Inserts the URL of an application administration page
 *
 * This plug-in allows you to generate an application URL. It uses any URL rewriting
 * mechanism and rules you'll have configured for the system.
 *
 * To ensure this can be as optimized as possible, it accepts 2 modes of operation:
 *
 * <b>Static address generation</b>:<br>
 * <code>
 * // Generate an URL using variables
 * <{adminUrl var1=val1 var2=val2}>
 * </code>
 *
 * <b>Dynamic address generation</b>:<br>
 * The URL is generated dynamically each time the template is displayed, thus allowing
 * you to use the value of a template variable in the location string. To use it, you
 * must surround your location with double-quotes ("), and use the
 * {@link http://smarty.php.net/manual/en/language.syntax.quotes.php Smarty quoted strings}
 * syntax to insert variables values.
 *
 * <code>
 * // Generate an URL using variables
 * <{adminUrl var1=$val1 var2=val2}>
 * </code>
 */

class Smarty_Compiler_AdminUrl  extends Smarty_Internal_CompileBase
{
    /**
     * Compiles code for the {adminUrl} tag
     *
     * @param   array   $args array with attributes from parser
     * @param   object  $compiler compiler object
     * @return  string  compiled code
     */
    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        //$this->required_attributes = array();
        $this->optional_attributes = array("_any");
        $this->option_flags = array();

        // check and get attributes
        $_attr = $this->_get_attributes($args);
        $pars = array();
        foreach ($_attr as $k => $v) {
            $pars[] = var_export($k, true) . " => " . (empty($v) ? '""' : $v);
        }
        $route = empty($route) ? "null" : $route;
        $str = "XOOPS::registry('view')->url(";
        $str .= "array(" . implode(", ", $pars) . ")";
        //$str .= var_export($_attr, true);
        $str .= ", 'admin')";
        return "<?php echo {$str}; ?>";
    }
}