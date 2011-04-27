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
 * @version         $Id: topic.php 2284 2008-10-12 03:45:46Z phppp $
 */
 
if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}

class Topic extends XoopsObject 
{
    function Topic()
    {
        //$this->ArtObject("bb_topics");
        $this->initVar('topic_id',                 XOBJ_DTYPE_INT);
        $this->initVar('topic_title',             XOBJ_DTYPE_TXTBOX);
        $this->initVar('topic_poster',             XOBJ_DTYPE_INT);
        $this->initVar('topic_time',             XOBJ_DTYPE_INT);
        $this->initVar('topic_views',             XOBJ_DTYPE_INT);
        $this->initVar('topic_replies',         XOBJ_DTYPE_INT);
        $this->initVar('topic_last_post_id',     XOBJ_DTYPE_INT);
        $this->initVar('forum_id',                 XOBJ_DTYPE_INT);
        $this->initVar('topic_status',             XOBJ_DTYPE_INT);
        $this->initVar('type_id',                 XOBJ_DTYPE_INT);
        $this->initVar('topic_sticky',             XOBJ_DTYPE_INT);
        $this->initVar('topic_digest',             XOBJ_DTYPE_INT);
        $this->initVar('digest_time',             XOBJ_DTYPE_INT);
        $this->initVar('approved',                 XOBJ_DTYPE_INT);
        $this->initVar('poster_name',             XOBJ_DTYPE_TXTBOX);
        $this->initVar('rating',                 XOBJ_DTYPE_OTHER);
        $this->initVar('votes',                 XOBJ_DTYPE_INT);
        $this->initVar('topic_haspoll',         XOBJ_DTYPE_INT);
        $this->initVar('poll_id',                 XOBJ_DTYPE_INT);
        $this->initVar('topic_tags',             XOBJ_DTYPE_SOURCE);
    }
    
    function incrementCounter()
    {
        $sql = 'UPDATE ' . $GLOBALS["xoopsDB"]->prefix('bb_topics') . ' SET topic_views = topic_views + 1 WHERE topic_id =' . $this->getVar('topic_id');
        $GLOBALS["xoopsDB"]->queryF($sql);
    }
    
    /**
     * Create full title of the topic
     *
     * the title is composed of [type_name] if type_id is greater than 0 plus topic_title
     *
     */
    function getFullTitle()
    {
        $topic_title = $this->getVar("topic_title");
        if (!$this->getVar("type_id")) return $topic_title;
        $type_handler =& xoops_getmodulehandler('type', 'newbb');
        if (!$type_obj =& $type_handler->get($this->getVar("type_id"))) return $topic_title;
        
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.topic.php";
        return newbb_getTopicTitle($topic_title, $type_obj->getVar("type_name"), $type_obj->getVar("type_color"));
    }
}

class NewbbTopicHandler extends XoopsPersistableObjectHandler
{
    function NewbbTopicHandler(&$db)
    {
        $this->XoopsPersistableObjectHandler($db, 'bb_topics', 'Topic', 'topic_id', 'topic_title');
    }
    
    function &get($id, $var = null)
    {
        $ret = null;
        if (!empty($var) && is_string($var)) {
            $tags = array($var);
        } else {
            $tags = $var;
        }
        if (!$topic_obj = parent::get($id, $tags)) {
            return $ret;
        }
        if (!empty($var) && is_string($var)) {
            $ret = @$topic_obj->getVar($var);
        } else {
            $ret =& $topic_obj;
        }
        return $ret;
    }

    function insert(&$object, $force = true)
    {
        if (!$object->getVar("topic_time")) {
            $object->setVar("topic_time", time());
        }
        if (!parent::insert($object, $force) || !$object->getVar("approved")) {
            return $object->getVar("topic_id");
        }
        
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.config.php";
        $newbbConfig = newbb_loadConfig();
        if ( !empty($newbbConfig['do_tag']) && @include_once XOOPS_ROOT_PATH . "/modules/tag/include/functions.php" ) {
            if ( $tag_handler = tag_getTagHandler() ) {
                $tag_handler->updateByItem($object->getVar('topic_tags', 'n'), $object->getVar('topic_id'), "newbb");
            }
        }
        return $object->getVar("topic_id");
    }
    
    function approve(&$object)
    {
        $topic_id = $object->getVar("topic_id");
        $sql = "UPDATE " . $this->db->prefix("bb_topics") . " SET approved = 1 WHERE topic_id = {$topic_id}";
        if (!$result = $this->db->queryF($sql)) {
            return false;
        }
        $post_handler =& xoops_getmodulehandler('post', 'newbb');
        $posts_obj = $post_handler->getAll(new Criteria('topic_id', $topic_id));
        foreach (array_keys($posts_obj) as $post_id) {
            $post_handler->approve($posts_obj[$post_id]);
        }
        unset($posts_obj);
        $stats_handler =& xoops_getmodulehandler('stats', 'newbb');
        $stats_handler->update($object->getVar("forum_id"), "topic");
        return true;
    }

    /**
     * get previous/next topic
     *
     * @param    integer    $topic_id    current topic ID
     * @param    integer    $action
     * <ul>
     *        <li> -1: previous </li>
     *        <li> 0: current </li>
     *        <li> 1: next </li>
     * </ul>
     * @param    integer    $forum_id    the scope for moving
     * <ul>
     *        <li> >0 : inside the forum </li>
     *        <li> <= 0: global </li>
     * </ul>
     * @access public
     */
    function &getByMove($topic_id, $action, $forum_id = 0)
    {
        $topic = null;
        if (!empty($action)):
        $sql = "SELECT * FROM " . $this->table .
                " WHERE 1=1" .
                (($forum_id>0) ? " AND forum_id=" . intval($forum_id) : "") .
                   " AND topic_id " . (($action > 0) ? ">" : "<") . intval($topic_id) .
                " ORDER BY topic_id " . (($action > 0) ? "ASC" : "DESC") . " LIMIT 1";
        if ($result = $this->db->query($sql)) {
            if ($row = $this->db->fetchArray($result)):
            $topic =& $this->create(false);
            $topic->assignVars($row);
            return $topic;
            endif;
        }
        endif;
        $topic =& $this->get($topic_id);
        return $topic;
    }

    function &getByPost($post_id)
    {
        $topic = null;
        $sql = "SELECT t.* FROM " . $this->db->prefix('bb_topics') . " t, " . $this->db->prefix('bb_posts') . " p
                WHERE t.topic_id = p.topic_id AND p.post_id = " . intval($post_id);
        $result = $this->db->query($sql);
        if (!$result) {
            return $topic;
        }
        $row = $this->db->fetchArray($result);
        $topic =& $this->create(false);
        $topic->assignVars($row);
        return $topic;
    }

    function getPostCount(&$topic, $type ="")
    {
        switch($type) {
        case "pending":
            $approved = 0;                
            break;
        case "deleted":
            $approved = -1;                
            break;
        default:
            $approved = 1;                
            break;
        }
        $criteria =& new CriteriaCompo(new Criteria("topic_id", $topic->getVar('topic_id')));
        $criteria->add(new Criteria("approved", $approved));
        $post_handler =& xoops_getmodulehandler("post", "newbb");
        $count = $post_handler->getCount($criteria);
        return $count;
    }

    function &getTopPost($topic_id)
    {
        $post = null;
        $sql = "SELECT p.*, t.* FROM " . $this->db->prefix('bb_posts') . " p,
            " . $this->db->prefix('bb_posts_text') . " t
            WHERE
            p.topic_id = " . $topic_id . " AND p.pid = 0
            AND t.post_id = p.post_id";

        $result = $this->db->query($sql);
        if (!$result) {
            return $post;
        }
        $post_handler =& xoops_getmodulehandler('post', 'newbb');
        $myrow = $this->db->fetchArray($result);
        $post =& $post_handler->create(false);
        $post->assignVars($myrow);
        return $post;
    }

    function getTopPostId($topic_id)
    {
        $sql = "SELECT MIN(post_id) AS post_id FROM " . $this->db->prefix('bb_posts') . " WHERE topic_id = " . $topic_id . " AND pid = 0";
        $result = $this->db->query($sql);
        if (!$result) {
            return false;
        }
        list($post_id) = $this->db->fetchRow($result);
        return $post_id;
    }

    function &getAllPosts(&$topic, $order = "ASC", $perpage = 10, &$start, $post_id = 0, $type = "")
    {
        global $xoopsModuleConfig;

        $ret = array();
        $perpage = (intval($perpage)>0) ? intval($perpage) : (empty($xoopsModuleConfig['posts_per_page']) ? 10 : $xoopsModuleConfig['posts_per_page']);
        $start = intval($start);
        switch($type) {
        case "pending":
            $approve_criteria = ' AND p.approved = 0';
            break;
        case "deleted":
            $approve_criteria = ' AND p.approved = -1';
            break;
        default:
            $approve_criteria = ' AND p.approved = 1';
            break;
        }

        if ($post_id) {
            if ($order == "DESC") {
                $operator_for_position = '>' ;
            } else {
                $order = "ASC" ;
                $operator_for_position = '<' ;
            }
            //$approve_criteria = ' AND approved = 1'; // any others?
            $sql = "SELECT COUNT(*) FROM " . $this->db->prefix('bb_posts') . " AS p WHERE p.topic_id=" . intval($topic->getVar('topic_id')) . $approve_criteria . " AND p.post_id $operator_for_position $post_id";
            $result = $this->db->query($sql);
            if (!$result) {
                return $ret;
            }
            list($position) = $this->db->fetchRow($result);
            $start = intval($position / $perpage) * $perpage;
        }

        $sql = 'SELECT p.*, t.* FROM ' . $this->db->prefix('bb_posts') . ' p, ' . $this->db->prefix('bb_posts_text') . " t WHERE p.topic_id=" . $topic->getVar('topic_id') . " AND p.post_id = t.post_id" . $approve_criteria . " ORDER BY p.post_id $order";
        $result = $this->db->query($sql, $perpage, $start);
        if (!$result) {
            return $ret;
        }
        $post_handler = &xoops_getmodulehandler('post', 'newbb');
        while ($myrow = $this->db->fetchArray($result)) {
            $post =& $post_handler->create(false);
            $post->assignVars($myrow);
            $ret[$myrow['post_id']] = $post;
            unset($post);
        }
        return $ret;
    }

    function &getPostTree(&$postArray, $pid=0)
    {
        include_once XOOPS_ROOT_PATH . "/modules/newbb/class/newbbtree.php";
        $NewBBTree = new NewBBTree('bb_posts');
        $NewBBTree->setPrefix('&nbsp;&nbsp;');
        $NewBBTree->setPostArray($postArray);
        $NewBBTree->getPostTree($postsArray, $pid);
        return $postsArray;
    }

    function showTreeItem(&$topic, &$postArray)
    {
        global $xoopsConfig, $xoopsModuleConfig, $viewtopic_users, $myts;

        $postArray['post_time'] = newbb_formatTimestamp($postArray['post_time']);

        if (!empty($postArray['icon'])) {
            $postArray['icon'] = '<img src="' . XOOPS_URL . "/images/subject/" . htmlspecialchars($postArray['icon']) . '" alt="" />';
        } else {
            $postArray['icon'] = '<a name="' . $postArray['post_id'] . '"><img src="' . XOOPS_URL . '/images/icons/no_posticon.gif" alt="" /></a>';
        }

        $postArray['subject'] = '<a href="viewtopic.php?viewmode=thread&amp;topic_id=' . $topic->getVar('topic_id') . '&amp;forum=' . $postArray['forum_id'] . '&amp;post_id=' . $postArray['post_id'] . '">' . $postArray['subject'] . '</a>';

        $isActiveUser = false;
        if (isset($viewtopic_users[$postArray['uid']]['name'])) {
            $postArray['poster'] = $viewtopic_users[$postArray['uid']]['name'];
            if ($postArray['uid'] > 0)
            $postArray['poster'] = "<a href=\"".XOOPS_URL . "/userinfo.php?uid=" . $postArray['uid'] ."\">".$viewtopic_users[$postArray['uid']]['name']."</a>";
        } else {
            $postArray['poster'] = (empty($postArray['poster_name'])) ? $myts->HtmlSpecialChars($xoopsConfig['anonymous']) : $postArray['poster_name'];
        }

        return $postArray;
    }

    function &getAllPosters(&$topic, $isApproved = true)
    {
        $sql = 'SELECT DISTINCT uid FROM ' . $this->db->prefix('bb_posts') . "  WHERE topic_id=" . $topic->getVar('topic_id')." AND uid>0";
        if ($isApproved) $sql .= ' AND approved = 1';
        $result = $this->db->query($sql);
        if (!$result) {
            //xoops_error($this->db->error());
            return array();
        }
        $ret = array();
        while ($myrow = $this->db->fetchArray($result)) {
            $ret[] = $myrow['uid'];
        }
        return $ret;
    }

    function delete(&$topic, $force = true) {
        $topic_id = is_object($topic) ? $topic->getVar("topic_id") : intval($topic);
        if (empty($topic_id)) {
            return false;
        }
        $post_obj =& $this->getTopPost($topic_id);
        $post_handler =& xoops_getmodulehandler('post', 'newbb');
        $post_handler->delete($post_obj, false, $force);
        
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.config.php";
        $newbbConfig = newbb_loadConfig();
        if (!empty($newbbConfig['do_tag']) && $tag_handler = @xoops_getmodulehandler('tag', 'tag', true)) {
            $tag_handler->updateByItem(array(), $topic_id, "newbb");
        }
        
        return true;
    }
    
    // get permission
    // parameter: $type: 'post', 'view',  'reply', 'edit', 'delete', 'addpoll', 'vote', 'attach'
    // $gperm_names = "'forum_can_post', 'forum_can_view', 'forum_can_reply', 'forum_can_edit', 'forum_can_delete', 'forum_can_addpoll', 'forum_can_vote', 'forum_can_attach', 'forum_can_noapprove'";
    function getPermission($forum, $topic_locked = 0, $type = "view")
    {
        global $xoopsUser, $xoopsModule;
        static $_cachedTopicPerms;
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.user.php";
        if (newbb_isAdmin($forum)) return 1;

        $forum_id = is_object($forum) ? $forum->getVar('forum_id') : intval($forum);
        if ( $forum_id < 1 ) return false;

        if ($topic_locked && 'view' != $type) {
            $permission = 0;
        } else {
            $perm_handler =& xoops_getmodulehandler('permission', 'newbb');
            $permission = $perm_handler->getPermission("forum", $type, $forum_id);
        }
        return $permission;
    }
    
    /**
     * clean orphan items from database
     * 
     * @return     bool    true on success
     */
    function cleanOrphan()
    {
        $this->deleteAll(new Criteria("topic_time", 0), true, true);
        parent::cleanOrphan($this->db->prefix("bb_forums"), "forum_id");
        parent::cleanOrphan($this->db->prefix("bb_posts"), "topic_id");
        
        return true;
    }

    /**
     * clean expired objects from database
     * 
     * @param     int     $expire     time limit for expiration
     * @return     bool    true on success
     */
    function cleanExpires($expire = 0)
    {
        $crit_expire =& new CriteriaCompo(new Criteria("approved", 0, "<="));
        $crit_expire->add(new Criteria("topic_time", time() - intval($expire), "<"));
        return $this->deleteAll($crit_expire, true/*, true*/);
    }
    
    function synchronization(&$object/*, $force = true*/)
    {
        if (!is_object($object)) {
            $object =& $this->get(intval($object));
        }
        if (!$object->getVar("topic_id")) return false;

        $sql =    "    SELECT MAX(post_id) AS last_post, COUNT(*) AS total ".
                "    FROM " . $this->db->prefix("bb_posts") . 
                "    WHERE approved=1 AND topic_id = ".$object->getVar("topic_id");
        if ( $result = $this->db->query($sql) ) {
            if ( $row = $this->db->fetchArray($result) ) {
                if ($object->getVar("topic_last_post_id") != $row['last_post']) {
                    $object->setVar("topic_last_post_id", $row['last_post']);
                }
                if ($object->getVar("topic_replies") != $row['total'] -1 ) {
                    $object->setVar("topic_replies", $row['total'] -1);
                }
            }
            $this->insert($object, true);
        }

        return true;
    }
}

?>