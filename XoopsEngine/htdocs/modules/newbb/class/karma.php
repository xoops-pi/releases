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
 * @version         $Id: karma.php 2169 2008-09-23 13:37:10Z phppp $
 */
 
class NewbbKarmaHandler
{
    var $user;

    function getUserKarma($user = null)
    {
        $user = is_null($user) ? $GLOBALS["xoopsUser"] : $user;

        return NewbbKarmaHandler::calUserKarma($user);
    }

    /**
     * Placeholder for calcuating user karma
     */
    function calUserKarma($user)
    {
        if (!is_object($user)) {
            $user_karma = 0;
        } else {
            $user_karma = $user->getVar('posts') * 50;
        }
        return $user_karma;
    } 

    function updateUserKarma()
    {
    } 

    function writeUserKarma()
    {
    } 

    function readUserKarma()
    {
    } 
} 

?>