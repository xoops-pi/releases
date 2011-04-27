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
 * @version         $Id: text.php 2169 2008-09-23 13:37:10Z phppp $
 */
 
if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}

class Ntext extends XoopsObject
{
    function Ntext()
    {
        //$this->ArtObject("bb_posts_text");
        $this->initVar('post_id', XOBJ_DTYPE_INT);
        $this->initVar('post_text', XOBJ_DTYPE_TXTAREA);
        $this->initVar('post_edit', XOBJ_DTYPE_SOURCE);
    }
}

class NewbbTextHandler extends XoopsPersistableObjectHandler 
{
    function NewbbTextHandler(&$db)
    {
        $this->XoopsPersistableObjectHandler($db, 'bb_posts_text', 'Ntext', 'post_id');
    }
    
    /**
     * clean orphan items from database
     * 
     * @return     bool    true on success
     */
    function cleanOrphan()
    {
        return parent::cleanOrphan($this->db->prefix("bb_posts"), "post_id");
    }
}

?>