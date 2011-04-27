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
 * @version         $Id: uploader.php 2169 2008-09-23 13:37:10Z phppp $
 */
 
if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}

include_once XOOPS_ROOT_PATH . "/class/uploader.php";

class newbb_uploader extends XoopsMediaUploader
{

    /**
     * No admin check for uploads
     */
    /**
     * Constructor
     *
     * @param string     $uploadDir
     * @param array     $allowedMimeTypes
     * @param int         $maxFileSize
     * @param int         $maxWidth
     * @param int         $maxHeight
     */
    function newbb_uploader($uploadDir, $allowedMimeTypes = null, $maxFileSize = null, $maxWidth = null, $maxHeight = null)
    {
        if (!is_array($allowedMimeTypes)) {
            if (empty($allowedMimeTypes) || $allowedMimeTypes == "*") {
                $allowedMimeTypes = array();
            } else {
                $allowedMimeTypes = array_filter(array_map("trim", explode("|", strtolower($allowedMimeTypes))));
            }
        }
        $_allowedMimeTypes = array();
        $extensionToMime = include XOOPS_ROOT_PATH . '/class/mimetypes.inc.php';
        foreach ($allowedMimeTypes as $type) {
            if (isset($extensionToMime[$type])) {
                $_allowedMimeTypes[] = $extensionToMime[$type];
            } else {
                $_allowedMimeTypes[] = $type;
            }
        }
        $this->XoopsMediaUploader($uploadDir, $_allowedMimeTypes, $maxFileSize, $maxWidth, $maxHeight);
    }

    /**
     * Set the CheckMediaTypeByExt
     * Deprecated
     *
     * @param string $value
     */
    function setCheckMediaTypeByExt($value = true)
    {
    }

    /**
     * Set the imageSizeCheck
     * Deprecated
     *
     * @param string $value
     */
    function setImageSizeCheck($value)
    {
    }

    /**
     * Set the fileSizeCheck
     * Deprecated
     *
     * @param string $value
     */
    function setFileSizeCheck($value)
    {
    }

    /**
     * Get the file extension
     *
     * @return string
     */
    function getExt()
    {
        $this->ext = strtolower(ltrim(strrchr($this->getMediaName(), '.'), '.'));
        return $this->ext;
    }
}

?>