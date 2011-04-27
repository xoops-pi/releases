<?php
/**
 * XOOPS event observer class
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         BSD Licence
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @since           3.0
 * @package         xoops_Core
 * @version         $Id$
 */

class Pm_Msg
{
    public static function send($data)
    {
        xoops_result("Inside " . __METHOD__);
        xoops_result($data);
    }
    public static function call($data)
    {
        xoops_result("Inside " . __METHOD__);
        xoops_result($data);
    }
}
?>