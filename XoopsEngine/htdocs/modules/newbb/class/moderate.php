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
 * @version         $Id: moderate.php 2169 2008-09-23 13:37:10Z phppp $
 */

if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}

class Moderate extends XoopsObject
{

    function __construct()
    {
        //$this->ArtObject("bb_moderates");
        $this->initVar('mod_id', XOBJ_DTYPE_INT);
        $this->initVar('mod_start', XOBJ_DTYPE_INT);
        $this->initVar('mod_end', XOBJ_DTYPE_INT);
        $this->initVar('mod_desc', XOBJ_DTYPE_TXTBOX);
        $this->initVar('uid', XOBJ_DTYPE_INT);
        $this->initVar('ip', XOBJ_DTYPE_TXTBOX);
        $this->initVar('forum_id', XOBJ_DTYPE_INT);
    }
}

class NewbbModerateHandler extends XoopsPersistableObjectHandler
{
    function __construct(&$db)
    {
        parent::__construct($db, 'bb_moderates', 'Moderate', 'mod_id', 'uid');
    }

    /**
     * Clear garbage
     *
     * Delete all moderation information that has expired
     *
     * @param    int $expire Expiration time in UNIX, 0 for time()
     */
    function clearGarbage($expire = 0)
    {
        $expire = time() - intval($expire);
        $sql = sprintf("DELETE FROM %s WHERE mod_end < %u", $this->db->prefix('bb_moderates'), $expire);
        $this->db->queryF($sql);
    }

    /**
     * Check if a user is moderated, according to his uid and ip
     *
     *
     * @param    int     $uid user id
     * @param    string     $ip user ip
     */
    function verifyUser($uid = -1, $ip = "", $forum = 0)
    {
        if (!empty($GLOBALS["xoopsModuleConfig"]['cache_enabled'])) {
            $forums = $this->forumList($uid, $ip);
            return in_array($forum, $forums);
        }
        $uid = ($uid < 0) ? ( is_object($GLOBALS["xoopsUser"]) ? $GLOBALS["xoopsUser"]->getVar("uid") : 0 ) : $uid;
        $uid_criteria = empty($uid) ? "1=1" : "uid=" . intval($uid);
        $ip = empty($ip) ?  newbb_getIP(true) : $ip;
        if (!empty($ip)) {
            $ip_segs = explode(".", $ip);
            for($i = 1 ; $i <= 4; $i++) {
                $ips[] = $this->db->quoteString(implode(".", array_slice($ip_segs, 0, $i)));
            }
            $ip_criteria = "ip IN(" . implode(",", $ips) . ")";
        } else {
            $ip_criteria = "1=1";
        }
        $forum_criteria = empty($forum) ? "forum_id=0" : "forum_id=0 OR forum_id=" . intval($forum);
        $expire_criteria = "mod_end > " . time();
        $sql = sprintf("SELECT COUNT(*) AS count FROM %s WHERE (%s OR %s) AND (%s) AND (%s)", $this->db->prefix('bb_moderates'), $uid_criteria, $ip_criteria, $forum_criteria, $expire_criteria);
        if (!$result = $this->db->query($sql)) {
            return false;
        }
        list($count) = $this->db->fetchRow($result);
        return $count;
    }

    /**
     * Get a forum list that a user is suspended, according to his uid and ip
     * Store the list into session if module cache is enabled
     *
     *
     * @param    int     $uid user id
     * @param    string     $ip user ip
     */
    function forumList($uid = -1, $ip = "")
    {
        static $forums = array();
        $uid = ($uid<0) ? (is_object($GLOBALS["xoopsUser"]) ? $GLOBALS["xoopsUser"]->getVar("uid") : 0) : $uid;
        $ip = empty($ip) ? newbb_getIP(true) : $ip;
        if (isset($forums[$uid][$ip])) {
            return $forums[$uid][$ip];
        }
        if (!empty($GLOBALS["xoopsModuleConfig"]['cache_enabled'])) {
            $forums[$uid][$ip] = newbb_getsession("sf" . $uid . "_" . ip2long($ip), true);
            if (is_array($forums[$uid][$ip]) && count($forums[$uid][$ip])) {
                return $forums[$uid][$ip];
            }
        }
        $uid_criteria = empty($uid) ? "1=1" : "uid=" . intval($uid);
        if (!empty($ip)) {
            $ip_segs = explode(".", $ip);
            for($i = 1; $i <= 4; $i++) {
                $ips[] = $this->db->quoteString(implode(".", array_slice($ip_segs, 0, $i)));
            }
            $ip_criteria = "ip IN(" . implode(",", $ips) . ")";
        } else {
            $ip_criteria = "1=1";
        }
        $expire_criteria = "mod_end > " . time();
        $sql = sprintf("SELECT forum_id, COUNT(*) AS count FROM %s WHERE (%s OR %s) AND (%s) GROUP BY forum_id", $this->db->prefix('bb_moderates'), $uid_criteria, $ip_criteria, $expire_criteria);
        if (!$result = $this->db->query($sql)) {
            return $forums[$uid][$ip] = array();
        }
        $_forums = array();
        while($row = $this->db->fetchArray($result)) {
            if ($row["count"] > 0) {
                $_forums[$row["forum_id"]] = 1;
            }
        }
        $forums[$uid][$ip] = count($_forums) ? array_keys($_forums) : array(-1);
        if (!empty($GLOBALS["xoopsModuleConfig"]['cache_enabled'])) {
            newbb_setsession("sf" . $uid . "_" . ip2long($ip), $forums[$uid][$ip]);
        }

        return $forums[$uid][$ip];
    }

    /**
     * Get latest expiration for a user moderation
     *
     *
     * @param    mix     $item    user id or ip
     */
    function getLatest($item, $isUid = true)
    {
        if ($isUid) {
            $criteria = "uid =" . intval($item);
        } else {
            $ip_segs = explode(".", $item);
            $segs = min(count($ip_segs), 4);
            for($i = 1; $i <= $segs; $i++) {
                $ips[] = $this->db->quoteString(implode(".", array_slice($ip_segs, 0, $i)));
            }
            $criteria = "ip IN(" . implode(",", $ips) . ")";
        }
        $sql = "SELECT MAX(mod_end) AS expire FROM " . $this->db->prefix('bb_moderates') . " WHERE " . $criteria;
        if (!$result = $this->db->query($sql)) {
            return -1;
        }
        $row = $this->db->fetchArray($result);
        return $row["expire"];
    }

    /**
     * clean orphan items from database
     *
     * @return     bool    true on success
     */
    function cleanOrphan()
    {
        /* for MySQL 4.1+ */
        if (version_compare( mysql_get_server_info(), "4.1.0", "ge" )):
        $sql = "DELETE FROM " . $this->table .
                " WHERE (forum_id >0 AND forum_id NOT IN ( SELECT DISTINCT forum_id FROM " . $this->db->prefix("bb_forums") . ") )";
        else:
        // for 4.0 +
        /* */
        $sql =  "DELETE {$this->table} FROM {$this->table}" .
                " LEFT JOIN " . $this->db->prefix("bb_forums") . " AS aa ON {$this->table}.forum_id = aa.forum_id " .
                " WHERE {$this->table}.forum_id > 0 AND (aa.forum_id IS NULL)";
        /* */
        // for 4.1+
        /*
        $sql =     "DELETE bb FROM ".$this->table." AS bb".
                " LEFT JOIN ".$this->db->prefix("bb_forums")." AS aa ON bb.forum_id = aa.forum_id ".
                " WHERE bb.forum_id > 0 AND (aa.forum_id IS NULL)";
        */
        endif;
        if (!$result = $this->db->queryF($sql)) {
            //xoops_error($this->db->error());
            return false;
        }
        return true;
    }
}
?>