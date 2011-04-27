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
 * @version         $Id: newbb_block_tag.php 2168 2008-09-23 13:34:39Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

/**#@+
 * Function to display tag cloud
 */
function newbb_tag_block_cloud_show($options) 
{
    if (!@include_once XOOPS_ROOT_PATH."/modules/tag/blocks/block.php") {
        return null; 
    } 
    $block_content = tag_block_cloud_show($options, "newbb");
    return $block_content;
}

function newbb_tag_block_cloud_edit($options) 
{
    if (!@include_once XOOPS_ROOT_PATH."/modules/tag/blocks/block.php") {
        return null; 
    }
    $form = tag_block_cloud_edit($options);
    return $form;
}

/**#@+
 * Function to display top tag list
 */
function newbb_tag_block_top_show($options) 
{
    if (!@include_once XOOPS_ROOT_PATH."/modules/tag/blocks/block.php") {
        return null; 
    }
    $block_content = tag_block_top_show($options, "newbb");
    return $block_content;
}

function newbb_tag_block_top_edit($options) {
    if (!@include_once XOOPS_ROOT_PATH."/modules/tag/blocks/block.php") {
        return null; 
    } 
    $form = tag_block_top_edit($options);
    return $form;
}
?>