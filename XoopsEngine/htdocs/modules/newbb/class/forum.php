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
 * @version         $Id: forum.php 2169 2008-09-23 13:37:10Z phppp $
 */

if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}

class Forum extends XoopsObject
{

    function Forum()
    {
        //$this->ArtObject("bb_forums");
        $this->initVar('forum_id',                  XOBJ_DTYPE_INT);
        $this->initVar('forum_name',                XOBJ_DTYPE_TXTBOX);
        $this->initVar('forum_desc',                XOBJ_DTYPE_TXTBOX);
        $this->initVar('forum_moderator',           XOBJ_DTYPE_ARRAY,       serialize(array()));
        $this->initVar('forum_topics',              XOBJ_DTYPE_INT);
        $this->initVar('forum_posts',               XOBJ_DTYPE_INT);
        $this->initVar('forum_last_post_id',        XOBJ_DTYPE_INT);
        $this->initVar('cat_id',                    XOBJ_DTYPE_INT);
        $this->initVar('parent_forum',              XOBJ_DTYPE_INT);

        $this->initVar('hot_threshold',             XOBJ_DTYPE_INT,         20);
        $this->initVar('attach_maxkb',              XOBJ_DTYPE_INT,         100);
        $this->initVar('attach_ext',                XOBJ_DTYPE_SOURCE,      "zip|jpg|gif");

        $this->initVar('forum_order',               XOBJ_DTYPE_INT,         99);

        /*
         * For forum description only, not for post body
         */
        $this->initVar("dohtml",                    XOBJ_DTYPE_INT,        1);
    }

    function disp_forumModerators()
    {
        global $xoopsModuleConfig;

        $ret = "";
        if (!$valid_moderators = $this->getVar("forum_moderator")) {
            return $ret;
        }
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.user.php";
        $moderators = newbb_getUnameFromIds($valid_moderators, !empty($xoopsModuleConfig['show_realname']), true);
        $ret = implode(", ", $moderators);
        return $ret;
    }
}

class NewbbForumHandler extends XoopsPersistableObjectHandler
{
    function __construct(&$db)
    {
        parent::__construct($db, 'bb_forums', 'Forum', 'forum_id', 'forum_name');
    }

    function insert(&$forum)
    {
        if (!parent::insert($forum, true)) {
            return false;
        }

        if ($forum->isNew()) {
            $this->applyPermissionTemplate($forum);
        }

        return $forum->getVar('forum_id');
    }

    function delete(&$forum)
    {
        global $xoopsModule;
        // RMV-NOTIFY
        xoops_notification_deletebyitem ($xoopsModule->getVar('mid'), 'forum', $forum->getVar('forum_id'));
        // Get list of all topics in forum, to delete them too
        $topic_handler =& xoops_getmodulehandler('topic', 'newbb');
        $topic_handler->deleteAll(new Criteria("forum_id", $forum->getVar('forum_id')), true, true);
        $this->updateAll("parent_forum", $forum->getVar('parent_forum'), new Criteria("parent_forum", $forum->getVar('forum_id')));
        $this->deletePermission($forum);
        return parent::delete($forum);
    }

    function getIdsByPermission($perm = "access")
    {
        $perm_handler =& xoops_getmodulehandler('permission', 'newbb');
        return $perm_handler->getForums($perm);
    }

    function &getByPermission($cat = 0, $permission = "access", $tags = null, $asObject = true)
    {
        $_cachedForums = array();
        if ( !$valid_ids = $this->getIdsByPermission($permission) ) {
            return $_cachedForums;
        }

        $criteria = new CriteriaCompo( new Criteria("forum_id", "(" . implode(", ", $valid_ids) . ")", "IN") );
        if (is_numeric($cat) && $cat> 0) {
            $criteria->add(new Criteria("cat_id", intval($cat)));
        } elseif (is_array($cat) && count($cat) >0) {
            $criteria->add(new Criteria("cat_id", "(" . implode(", ", $cat) . ")", "IN"));
        }
        $criteria->setSort("forum_order");
        $criteria->setOrder("ASC");
        $_cachedForums =& $this->getAll($criteria, $tags, $asObject);
        return $_cachedForums;
    }

    function &getForumsByCategory($categoryid = 0, $permission = "", $asObject = true, $tags = null)
    {
        $forums =& $this->getByPermission($categoryid, $permission, $tags);
        if ($asObject) return $forums;

        $forums_array = array();
        $array_cat=array();
        $array_forum=array();
        if (!is_array($forums)) return array();
        foreach (array_keys($forums) as $forumid) {
            $forum =& $forums[$forumid];
            $forums_array[$forum->getVar('parent_forum')][$forumid] = array(
                'cid'   => $forum->getVar('cat_id'),
                'title' => $forum->getVar('forum_name')
            );
        }
        if (!isset($forums_array[0])) {
            $ret = array();
            return $ret;
        }
        foreach ($forums_array[0] as $key => $forum) {
            if (isset($forums_array[$key])) {
                $forum['sub'] = $forums_array[$key];
            }
            $array_forum[$forum['cid']][$key] = $forum;
        }
        ksort($array_forum);
        unset($forums);
        unset($forums_array);
        return $array_forum;
    }

    function getAllTopics(&$forum, $criteria = null)
    {
        global $xoopsModule, $xoopsConfig, $xoopsModuleConfig, $myts, $xoopsUser, $viewall_forums;
global $xoopsLogger;
$xoopsLogger->startTime( 'XOOPS output module - forum - topic - init' );

        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.render.php";
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.session.php";
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.read.php";
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.topic.php";

        $criteria_vars = array("startdate", "start", "sort", "order", "type", "status", "excerpt");
        foreach ($criteria_vars as $var) {
            ${$var} = $criteria[$var];
        }

        $topic_lastread = newbb_getcookie('LT', true);

        if (is_object($forum)) {
            $criteria_forum = ' AND t.forum_id = ' . $forum->getVar('forum_id');
            $hot_threshold = $forum->getVar('hot_threshold');
        } else {
            $hot_threshold = 10;
            if (is_array($forum) && count($forum) > 0) {
                $criteria_forum = ' AND t.forum_id IN (' . implode(',', array_keys($forum)) . ')';
            } elseif (!empty($forum)) {
                $criteria_forum = ' AND t.forum_id =' . intval($forum);
            } else {
                $criteria_forum = '';
            }
        }

        $criteria_post = ($startdate) ? ' p.post_time > ' . $startdate : " 1 = 1 ";
        $criteria_topic = empty($type) ? '' : " AND t.type_id={$type}";
        $criteria_extra = '';
        $criteria_approve = ' AND t.approved = 1';
        $post_on = ' p.post_id = t.topic_last_post_id';
        $leftjoin = ' LEFT JOIN ' . $this->db->prefix('bb_posts') . ' p ON p.post_id = t.topic_last_post_id';
        $sort_array = array();
        switch ($status) {
        case 'digest':
            $criteria_extra = ' AND t.topic_digest = 1';
            break;

        case 'unreplied':
            $criteria_extra = ' AND t.topic_replies < 1';
            break;

        case 'unread':
            if (empty($xoopsModuleConfig["read_mode"])) {
            } elseif ($xoopsModuleConfig["read_mode"] ==2) {
                $leftjoin .= ' LEFT JOIN ' . $this->db->prefix('bb_reads_topic') . ' r ON r.read_item = t.topic_id';
                $criteria_post .= ' AND (r.read_id IS NULL OR r.post_id < t.topic_last_post_id)';
            } elseif ($xoopsModuleConfig["read_mode"] == 1) {
                $topics = array();
                $topic_lastread = newbb_getcookie('LT', true);
                if (count($topic_lastread)>0) {
                    foreach ($topic_lastread as $id => $time) {
                        if ($time > $time_criterion) $topics[] = $id;
                    }
                }
                if (count($topics)>0) {
                    $criteria_extra = ' AND t.topic_id NOT IN (' . implode(",", $topics) . ')';
                }
                if ($lastvisit = max($GLOBALS['last_visit'], $startdate)) {
                    $criteria_post = ' p.post_time > ' . max($GLOBALS['last_visit'], $startdate);
                }
            }
            break;

        case 'pending':
            $post_on = ' p.topic_id = t.topic_id';
            $criteria_post .= ' AND p.pid = 0';
            $criteria_approve = ' AND t.approved = 0';
            break;

        case 'deleted':
            $criteria_approve = ' AND t.approved = -1';
            break;

        case 'all': // For viewall.php; do not display sticky topics at first
        case 'active': // same as "all"
            break;

        default:
            if ($startdate > 0) {
                $criteria_post = ' (p.post_time > ' . $startdate . ' OR t.topic_sticky=1)';
            }
            $sort_array[] = 't.topic_sticky DESC';
            break;
        }

        $select =   't.*, ' .
                    ' p.post_time as last_post_time, p.poster_name as last_poster_name, p.icon, p.post_id, p.uid';
        $from = $this->db->prefix("bb_topics") . ' t ' . $leftjoin;
        $where = $criteria_post. $criteria_topic. $criteria_forum . $criteria_extra . $criteria_approve;

        if ($excerpt) {
            $select .= ', p.post_karma, p.require_reply, pt.post_text';
            $from .= ' LEFT JOIN ' . $this->db->prefix('bb_posts_text') . ' pt ON pt.post_id = t.topic_last_post_id';
        }
        if ($sort == "u.uname") {
            $sort = "t.topic_poster";
        }

        $sort_array[] = trim($sort . ' ' . $order);
        $sortby = implode(", ", array_filter($sort_array) );
        if (empty($sortby)) $sortby = 't.topic_last_post_id DESC';
$xoopsLogger->stopTime( 'XOOPS output module - forum - topic - init' );

        $sql =  'SELECT ' . $select .
                ' FROM ' . $from .
                ' WHERE ' . $where .
                ' ORDER BY ' . $sortby;
$xoopsLogger->startTime( 'XOOPS output module - forum - topic - query' );
        if (!$result = $this->db->query($sql, $xoopsModuleConfig['topics_per_page'], $start)) {
            redirect_header('index.php', 2, _MD_ERROROCCURED);
            exit();
        }
$xoopsLogger->stopTime( 'XOOPS output module - forum - topic - query' );

        $sticky = 0;
        $topics = array();
        $posters = array();
        $reads = array();
        $types = array();
$xoopsLogger->startTime( 'XOOPS output module - forum - topic - display' );
$xoopsLogger->startTime( 'XOOPS output module - forum - topic - display - assign' );
        while ($myrow = $this->db->fetchArray($result)) {
//$xoopsLogger->startTime( 'XOOPS output module - forum - topic - display - assign '. $myrow['topic_id']);
            if ($myrow['topic_sticky']) {
                $sticky++;
            }

            // ------------------------------------------------------
            // topic_icon: priority: sticky -> digest -> regular

            if ($myrow['topic_haspoll']) {
                if ($myrow['topic_sticky']) {
                    $topic_icon = newbb_displayImage('topic_sticky', _MD_TOPICSTICKY) . '<br />' . newbb_displayImage('poll', _MD_TOPICHASPOLL);
                } else {
                    $topic_icon = newbb_displayImage('poll', _MD_TOPICHASPOLL);
                }
            } elseif ($myrow['topic_sticky']) {
                $topic_icon = newbb_displayImage('topic_sticky', _MD_TOPICSTICKY);
            } elseif (!empty($myrow['icon'])) {
                $topic_icon = '<img src="' . XOOPS_URL . '/images/subject/' . htmlspecialchars($myrow['icon']) . '" alt="" />';
            } else {
                $topic_icon = '<img src="' . XOOPS_URL . '/images/icons/no_posticon.gif" alt="" />';
            }

            // ------------------------------------------------------
            // rating_img
            $rating = number_format($myrow['rating'] / 2, 0);
            $rating_img = newbb_displayImage( ($rating < 1) ? 'blank' : 'rate' . $rating );

            // ------------------------------------------------------
            // topic_page_jump
            $topic_page_jump = '';
            $topic_page_jump_icon = '';
            $totalpages = ceil(($myrow['topic_replies'] + 1) / $xoopsModuleConfig['posts_per_page']);
            if ($totalpages > 1) {
                $topic_page_jump .= '&nbsp;&nbsp;';
                $append = false;
                for ($i = 1; $i <= $totalpages; $i++) {
                    if ($i > 3 && $i < $totalpages) {
                        if (!$append) {
                            $topic_page_jump .= "...";
                            $append = true;
                        }
                    } else {
                        $topic_page_jump .= '[<a href="viewtopic.php?topic_id=' . $myrow['topic_id'] . '&amp;start=' . (($i - 1) * $xoopsModuleConfig['posts_per_page']) . '">' . $i . '</a>]';
                        $topic_page_jump_icon = "<a href='" . XOOPS_URL . "/modules/newbb/viewtopic.php?topic_id=" . $myrow['topic_id'] . "&amp;start=" . (($i - 1) * $xoopsModuleConfig['posts_per_page']) . "#forumpost" . $myrow['post_id'] . "'>" . newbb_displayImage('document') . "</a>";
                    }
                }
            }
            else {
                $topic_page_jump_icon = "<a href='" . XOOPS_URL . "/modules/newbb/viewtopic.php?topic_id=" . $myrow['topic_id'] . "#forumpost" . $myrow['post_id'] . "'>" . newbb_displayImage('document') . "</a>";
            }

            // ------------------------------------------------------
            // => topic array
            if (!empty($viewall_forums[$myrow['forum_id']])) {
                $forum_link = '<a href="' . XOOPS_URL . '/modules/newbb/viewforum.php?forum=' . $myrow['forum_id'] . '">' . $viewall_forums[$myrow['forum_id']]['forum_name'] . '</a>';
            } else {
                $forum_link = '';
            }

            $topic_title = $myts->htmlSpecialChars( $myrow['topic_title'] );
            if ($myrow['topic_digest']) {
                $topic_title = "<span class='digest'>" . $topic_title . "</span>";
            }

            if ( $excerpt == 0 ) {
                $topic_excerpt = "";
            } elseif ( ($myrow['post_karma'] > 0 || $myrow['require_reply'] > 0) && !newbb_isAdmin($forum) ) {
                $topic_excerpt = "";
            } else {
                $topic_excerpt = xoops_substr(newbb_html2text($myts->displayTarea($myrow['post_text'])), 0, $excerpt);
                $topic_excerpt = str_replace("[", "&#91;", $myts->htmlSpecialChars($topic_excerpt));
            }

            $topics[$myrow['topic_id']] = array(
                'topic_id'        => $myrow['topic_id'],
                'topic_icon'    => $topic_icon,
                'type_id'        => $myrow['type_id'],
                'topic_title'    => $topic_title,
                'topic_link'    => 'viewtopic.php?topic_id=' . $myrow['topic_id'] . '&amp;forum=' . $myrow['forum_id'],
                'rating_img'    => $rating_img,
                'topic_page_jump'        => $topic_page_jump,
                'topic_page_jump_icon'    => $topic_page_jump_icon,
                'topic_replies'            => $myrow['topic_replies'],
                'topic_poster_uid'        => $myrow['topic_poster'],
                'topic_poster_name'        => $myts->htmlSpecialChars( ($myrow['poster_name']) ? $myrow['poster_name'] : $xoopsConfig['anonymous'] ),
                'topic_views'            => $myrow['topic_views'],
                'topic_time'            => newbb_formatTimestamp($myrow['topic_time']),
                'topic_last_posttime'        => newbb_formatTimestamp($myrow['last_post_time']),
                'topic_last_poster_uid'        => $myrow['uid'],
                'topic_last_poster_name'    => $myts->htmlSpecialChars( ($myrow['last_poster_name']) ? $myrow['last_poster_name'] : $xoopsConfig['anonymous'] ),
                'topic_forum_link'        => $forum_link,
                'topic_excerpt'            => $topic_excerpt,
                'stick' => empty($myrow['topic_sticky']),
                "stats" => array($myrow['topic_status'], $myrow['topic_digest'], $myrow['topic_replies']),
                );

            /* users */
            $posters[$myrow['topic_poster']] = 1;
            $posters[$myrow['uid']] = 1;
            // reads
            if (!empty($xoopsModuleConfig["read_mode"])) {
                $reads[$myrow['topic_id']] = ($xoopsModuleConfig["read_mode"] == 1) ? $myrow['last_post_time'] : $myrow["topic_last_post_id"];
            }
            // types
            if (!empty($myrow['type_id'])) {
                $types[$myrow['type_id']] = 1;
            }
//$xoopsLogger->stopTime( 'XOOPS output module - forum - topic - display - assign '. $myrow['topic_id']);
        }
$xoopsLogger->stopTime( 'XOOPS output module - forum - topic - display - assign' );
        $posters_name = newbb_getUnameFromIds(array_keys($posters), $xoopsModuleConfig['show_realname'], true);
        $topic_isRead = newbb_isRead("topic", $reads);
        $types_obj = array();
         if (count($types) > 0) {
            $type_handler =& xoops_getmodulehandler('type', 'newbb');
            $types_obj = $type_handler->getAll(new Criteria("type_id", "(" . implode(", ", array_keys($types)) . ")", "IN"));
        }

        foreach (array_keys($topics) as $id) {
            if (!empty($topics[$id]["type_id"]) && isset($types_obj[$topics[$id]["type_id"]])) {
                $topics[$id]["topic_title"] = newbb_getTopicTitle($topics[$id]["topic_title"], $types_obj[$topics[$id]["type_id"]]->getVar("type_name"), $types_obj[$topics[$id]["type_id"]]->getVar("type_color"));
            }
            $topics[$id]["topic_poster"] = !empty($posters_name[$topics[$id]["topic_poster_uid"]])
                                            ? $posters_name[$topics[$id]["topic_poster_uid"]]
                                            : $topics[$id]["topic_poster_name"];
            $topics[$id]["topic_last_poster"] = !empty($posters_name[$topics[$id]["topic_last_poster_uid"]])
                                            ? $posters_name[$topics[$id]["topic_last_poster_uid"]]
                                            : $topics[$id]["topic_last_poster_name"];
               // ------------------------------------------------------
            // topic_folder: priority: newhot -> hot/new -> regular
            list($topic_status, $topic_digest, $topic_replies) = $topics[$id]["stats"];
            if ($topic_status == 1) {
                $topic_folder = 'topic_locked';
            } else {
                if ($topic_digest) {
                    $topic_folder = 'topic_digest';
                } elseif ($topic_replies >= $hot_threshold) {
                    $topic_folder = empty($topic_isRead[$id]) ? 'topic_hot_new' : 'topic_hot';
                } else {
                    $topic_folder = empty($topic_isRead[$id]) ? 'topic_new' : 'topic';
                }
            }
            $topics[$id]['topic_folder'] = newbb_displayImage($topic_folder);

            unset($topics[$id]["topic_poster_name"], $topics[$id]["topic_last_poster_name"], $topics[$id]["stats"]);
        }
        unset($types_obj);
$xoopsLogger->stopTime( 'XOOPS output module - forum - topic - display' );

$xoopsLogger->startTime( 'XOOPS output module - forum - topic - attach' );
        if ( count($topics) > 0) {
            $sql = " SELECT DISTINCT topic_id FROM " . $this->db->prefix("bb_posts").
                     " WHERE attachment != ''" .
                     " AND topic_id IN (" . implode(',', array_keys($topics)) . ")";
            if ($result = $this->db->query($sql)) {
                while (list($topic_id) = $this->db->fetchRow($result)) {
                    $topics[$topic_id]['attachment'] = '&nbsp;' . newbb_displayImage('attachment', _MD_TOPICSHASATT);
                }
            }
        }
$xoopsLogger->stopTime( 'XOOPS output module - forum - topic - attach' );

        return array($topics, $sticky);
    }

    function getTopicCount(&$forum, $startdate, $type)
    {
        global $xoopsModuleConfig;
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.session.php";

        $criteria_extra = '';
        $criteria_approve = ' AND t.approved = 1'; // any others?
        $leftjoin = ' LEFT JOIN ' . $this->db->prefix('bb_posts') . ' p ON p.post_id = t.topic_last_post_id';
        $criteria_post = ' p.post_time > ' . $startdate;
        switch ($type) {
            case 'digest':
                $criteria_extra = ' AND topic_digest = 1';
                break;
            case 'unreplied':
                $criteria_extra = ' AND topic_replies < 1';
                break;
            case 'unread':
                if (empty($xoopsModuleConfig["read_mode"])) {
                } elseif ($xoopsModuleConfig["read_mode"] ==2) {
                    $leftjoin .= ' LEFT JOIN ' . $this->db->prefix('bb_reads_topic') . ' r ON r.read_item = t.topic_id';
                    $criteria_post .= ' AND (r.read_id IS NULL OR r.post_id < t.topic_last_post_id)';
                } elseif ($xoopsModuleConfig["read_mode"] == 1) {
                    $criteria_post = ' p.post_time > ' . max($GLOBALS['last_visit'], $startdate);
                    $topics = array();
                    $topic_lastread = newbb_getcookie('LT', true);
                    if (count($topic_lastread)>0) foreach ($topic_lastread as $id=>$time) {
                        if ($time > $time_criterion) $topics[] = $id;
                    }
                    if (count($topics)>0) {
                        $criteria_extra = ' AND t.topic_id NOT IN (' . implode(",", $topics) . ')';
                    }
                }
                break;
            case 'pending':
                $criteria_approve = ' AND t.approved = 0';
                break;
            case 'deleted':
                $criteria_approve = ' AND t.approved = -1';
                break;
            case 'all':
                break;
            default:
                $criteria_post = ' (p.post_time > ' . $startdate . ' OR t.topic_sticky=1)';
                break;
        }
        if (is_object($forum)) {
            $criteria_forum = ' AND t.forum_id = ' . $forum->getVar('forum_id');
        } else {
            if (is_array($forum) && count($forum) > 0) {
                $criteria_forum = ' AND t.forum_id IN (' . implode(',', array_keys($forum)) . ')';
            } elseif (!empty($forum)) {
                $criteria_forum = ' AND t.forum_id =' . intval($forum);
            } else {
                $criteria_forum = '';
            }
        }

        $sql = 'SELECT COUNT(*) as count FROM ' . $this->db->prefix("bb_topics") . ' t ' . $leftjoin;
        $sql .= ' WHERE ' . $criteria_post . $criteria_forum . $criteria_extra . $criteria_approve;
        if (!$result = $this->db->query($sql)) {
            //xoops_error($this->db->error().'<br />'.$sql);
            return null;
        }
        $myrow = $this->db->fetchArray($result);
        $count = $myrow['count'];
        return $count;
    }

    // get permission
    function getPermission($forum, $type = "access", $checkCategory = true)
    {
        global $xoopsUser, $xoopsModule;
        static $_cachedPerms;

        if ($type == "all") return true;
        if ($GLOBALS["xoopsUserIsAdmin"] && $xoopsModule->getVar("dirname") == "newbb") {
            return true;
        }

        if (!is_object($forum)) $forum =& $this->get($forum);

        if (!empty($checkCategory)) {
            $category_handler =& xoops_getmodulehandler('category', 'newbb');
            $categoryPerm = $category_handler->getPermission($forum->getVar('cat_id'));
            if (!$categoryPerm) return false;
        }

        $type = strtolower($type);
        if ("moderate" == $type) {
            require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.user.php";
            $permission = newbb_isModerator($forum);
        } else {
            $forum_id = $forum->getVar('forum_id');
            $perm_handler =& xoops_getmodulehandler('permission', 'newbb');
            $permission = $perm_handler->getPermission("forum", $type, $forum_id);
        }
        return $permission;
    }

    function deletePermission(&$forum)
    {
        $perm_handler =& xoops_getmodulehandler('permission', 'newbb');
        return $perm_handler->deleteByForum($forum->getVar("forum_id"));
    }

    function applyPermissionTemplate(&$forum)
    {
        $perm_handler =& xoops_getmodulehandler('permission', 'newbb');
        return $perm_handler->applyTemplate($forum->getVar("forum_id"));
    }

    /**
     * clean orphan items from database
     *
     * @return     bool    true on success
     */
    function cleanOrphan()
    {
        parent::cleanOrphan($this->db->prefix("bb_categories"), "cat_id");

        if (version_compare( mysql_get_server_info(), "4.1.0", "ge" )):
        /*
        $sql = "DELETE FROM ".$this->table.
                " WHERE (parent_forum >0 AND parent_forum NOT IN ( SELECT DISTINCT forum_id FROM ".$this->table.") )";
        */
        $sql =  "    DELETE {$this->table} FROM {$this->table}" .
                "    LEFT JOIN {$this->table} AS aa ON {$this->table}.parent_forum = aa.forum_id ".
                "    WHERE {$this->table}.parent_forum>0 AND (aa.forum_id IS NULL)";
        if (!$result = $this->db->queryF($sql)):
            //xoops_error("cleanOrphan error:". $sql);
        endif;
        else:
        $this->identifierName = "parent_forum";
        $forum_list = $this->getList(new Criteria("parent_forum", 0, ">"));
        $this->identifierName = "forum_name";
        if ($parent_forums = @array_values($forum_list)) {
            $parent_list = $this->getIds(new Criteria("forum_id", "(" . implode(", ", $parent_forums) . ")", "IN"));
            foreach ($forum_list as $forum_id => $parent_forum) {
                if (in_array($parent_forum, $parent_list)) continue;
                $forum_obj =& $this->get($forum_id);
                $this->delete($forum_obj);
                unset($forum_obj);
            }
        }
        endif;

        return true;
    }

    /**
     * forum data synchronization
     *
     * @param    mixed    $object    null for all forums; integer for forum_id; object for forum object
     * @param    integer    $mode    1 for stats only; 2 for forum index data only; 0 for both
     *
     */
    function synchronization($object = null)
    {
        if (empty($object)) {
            $forums = $this->getIds();
            foreach ($forums as $id) {
                $this->synchronization($id);
            }
            return true;
        }

        if (!is_object($object)) {
            $object =& $this->get(intval($object));
        }
        if (!$object->getVar("forum_id")) return false;

        $sql = "SELECT MAX(post_id) AS last_post, COUNT(*) AS total FROM " . $this->db->prefix("bb_posts") . " AS p LEFT JOIN  " . $this->db->prefix("bb_topics") . " AS t ON p.topic_id=t.topic_id WHERE p.approved=1 AND t.approved=1 AND p.forum_id = ".$object->getVar("forum_id");
        if ( $result = $this->db->query($sql)) {
            $last_post = 0;
            $posts = 0;
            if ( $row = $this->db->fetchArray($result) ) {
                $last_post = intval($row['last_post']);
                $posts = intval($row['total']);
            }
            if ($object->getVar("forum_last_post_id") != $last_post) {
                $object->setVar("forum_last_post_id", $last_post);
            }
            if ($object->getVar("forum_posts") != $posts) {
                $object->setVar("forum_posts", $posts);
            }
        }

        $sql = "SELECT COUNT(*) AS total FROM " . $this->db->prefix("bb_topics") . " WHERE approved=1 AND forum_id = " . $object->getVar("forum_id");
        if ( $result = $this->db->query($sql) ) {
            if ( $row = $this->db->fetchArray($result) ) {
                if ($object->getVar("forum_topics") != $row['total']) {
                    $object->setVar("forum_topics", $row['total']);
                }
            }
        }

        return $this->insert($object, true);
    }

    function getSubforumStats($subforums = null)
    {
        $stats = array();

        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.forum.php";
        $_subforums = newbb_getSubForum();
        if (empty($subforums)) {
            $sub_forums = $_subforums;
        } else {
            foreach ($subforums as $id) {
                $sub_forums[$id] =& $_subforums[$id];
            }
        }

        $forums_id = array();
        foreach (array_keys($sub_forums) as $id) {
            if (empty($sub_forums[$id])) continue;
            $forums_id  = array_merge($forums_id, $sub_forums[$id]);
        }
        if (!$forums_id) {
            return $stats;
        }
        $sql =  "    SELECT forum_posts AS posts, forum_topics AS topics, forum_id AS id".
                "    FROM " . $this->table .
                "    WHERE forum_id IN (". implode(", ", $forums_id).")";
        if ( !$result = $this->db->query($sql) ) {
            return $stats;
        }

        $forum_stats =  array();
        while( $row = $this->db->fetchArray($result) ) {
            $forum_stats[$row["id"]] = array("topics" => $row["topics"], "posts" => $row["posts"]);
        }

        foreach (array_keys($sub_forums) as $id) {
            if (empty($sub_forums[$id])) continue;
            $stats[$id] = array( "topics" => 0, "posts" => 0 );
            foreach ($sub_forums[$id] as $fid) {
                $stats[$id]["topics"]    += $forum_stats[$fid]["topics"];
                $stats[$id]["posts"]    += $forum_stats[$fid]["posts"];
            }
        }

        return $stats;
    }

    function &display($forums, $length_title_index = 30, $count_subforum = 1)
    {
        global $xoopsModule, $xoopsConfig, $xoopsModuleConfig, $myts;

global $xoopsLogger;
        $posts = array();
        $posts_obj = array();
$xoopsLogger->startTime( 'XOOPS output block - bb - forum - display - post' );
        foreach (array_keys($forums) as $id) {
            $posts[] = $forums[$id]["forum_last_post_id"];
        }
        if (!empty($posts)) {
            $post_handler =& xoops_getmodulehandler('post', 'newbb');
            $tags_post = array("uid", "topic_id", "post_time", "poster_name", "icon");
            if (!empty($length_title_index)) {
                $tags_post[] = "subject";
            }
            $posts = $post_handler->getAll(new Criteria("post_id", "(" . implode(", ", $posts) . ")", "IN"), $tags_post, false);
        }
$xoopsLogger->stopTime( 'XOOPS output block - bb - forum - display - post' );

        // Get topic/post stats per forum
        $stats_forum = array();
$xoopsLogger->startTime( 'XOOPS output block - bb - forum - display - substats' );
        if (!empty($count_subforum)) {
            $stats_forum = $this->getSubforumStats(array_keys($forums));
        }
$xoopsLogger->stopTime( 'XOOPS output block - bb - forum - display - substats' );

        $users = array();
        $reads = array();
        $topics = array();
$xoopsLogger->startTime( 'XOOPS output block - bb - forum - display - stats' );
        foreach (array_keys($forums) as $id) {
            $forum =& $forums[$id];

            if (!$forum["forum_last_post_id"]) continue;
            if (!$post = @$posts[$forum["forum_last_post_id"]]) {
                $forum["forum_last_post_id"] = 0;
                continue;
            }

            $users[] = $post["uid"];
            if ($moderators[$id] = $forum["forum_moderator"]) {
                $users = array_merge($users, $moderators[$id]);
            }

            // reads
            if (!empty($xoopsModuleConfig["read_mode"])) {
                $reads[$id] = ($xoopsModuleConfig["read_mode"] == 1) ? $post['post_time'] : $post['post_id'];
            }
        }
$xoopsLogger->stopTime( 'XOOPS output block - bb - forum - display - stats' );

        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.user.php";
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.render.php";
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.read.php";
        $forum_isread = newbb_isRead("forum", $reads);
        $users_linked = newbb_getUnameFromIds(array_unique($users), !empty($xoopsModuleConfig['show_realname']), true);

        $forums_array = array();
        $name_anonymous = $myts->htmlSpecialChars($GLOBALS["xoopsConfig"]["anonymous"]);
$xoopsLogger->startTime( 'XOOPS output block - bb - forum - display - assign' );
        foreach (array_keys($forums) as $id) {
//$xoopsLogger->startTime( 'XOOPS output block - bb - forum - display - assign - ' . $id);
            $forum =& $forums[$id];

            $_forum_data = array();
            $_forum_data["forum_order"]        = $forum['forum_order'];
            $_forum_data["forum_id"]         = $id;
            $_forum_data["forum_cid"]         = $forum['cat_id'];
            $_forum_data["forum_name"]         = $forum['forum_name'];
            $_forum_data["forum_desc"]         = $forum['forum_desc'];
            $_forum_data["forum_topics"]     = $forum["forum_topics"] + @$stats_forum[$id]["topics"];
            $_forum_data["forum_posts"]     = $forum["forum_posts"] + @$stats_forum[$id]["posts"];
            //$_forum_data["forum_type"]         = $forum['forum_type'];

            $forum_moderators = array();
            foreach ( @$moderators[$id] as $moderator ) {
                $forum_moderators[] = @$users_linked[$moderator];
            }
            $_forum_data["forum_moderators"] = implode(", ", $forum_moderators);


            if ($post_id = $forum["forum_last_post_id"]):
            $post =& $posts[$post_id];
            $_forum_data['forum_lastpost_id'] = $post_id;
            $_forum_data['forum_lastpost_time'] = newbb_formatTimestamp($post['post_time']);
            if (!empty($users_linked[$post["uid"]])) {
                $_forum_data["forum_lastpost_user"] = $users_linked[$post["uid"]];
            } elseif ($poster_name = $post["poster_name"]) {
                $_forum_data["forum_lastpost_user"] = $poster_name;
            } else {
                $_forum_data["forum_lastpost_user"] = $name_anonymous;
            }
            if (!empty($length_title_index)) {
                $subject = $post["subject"];
                if ($length_title_index < 255) {
                    $subject = xoops_substr($subject, 0, $length_title_index);
                }
                $_forum_data['forum_lastpost_subject'] = $subject;
            }
            if ($icon = $post['icon']) {
                $_forum_data['forum_lastpost_icon'] = $icon;
            } else {
                $_forum_data['forum_lastpost_icon'] = 'icon1.gif';
            }
            endif;


            $forum_folder = empty($forum_isread[$id]) ? 'forum_new' : 'forum';
            $_forum_data['forum_folder'] = newbb_displayImage($forum_folder);

            $forums_array[$forum['parent_forum']][] = $_forum_data;
//$xoopsLogger->stopTime( 'XOOPS output block - bb - forum - display - assign - ' . $id);
        }
$xoopsLogger->stopTime( 'XOOPS output block - bb - forum - display - assign' );
        return $forums_array;
    }


    /**
     * get a hierarchical tree of forums
     *
     * {@link newbbTree}
     *
     * @param     int        $cat_id category ID
     * @param     int        $pid     Top forum ID
     * @param     string    $permission    permission type
     * @param     string    $prefix        prefix for display
     * @param     string    $tags        variables to fetch
     * @return    array    associative array of category IDs and sanitized titles
     */
    function &getTree($cat_id = 0, $pid = 0, $permission = "access", $prefix = "--", $tags = null)
    {
        $pid = intval($pid);
        $perm_string = $permission;
        if (!is_array($tags) || count($tags)==0) {
            $tags = array("forum_id", "parent_forum", "forum_name", "forum_order", "cat_id");
        }
        $forums_obj = $this->getByPermission($cat_id, $perm_string, $tags);

        require_once dirname(__FILE__) . "/tree.php";
        $forums_structured = array();
        foreach (array_keys($forums_obj) as $key) {
            $forums_structured[$forums_obj[$key]->getVar("cat_id")][$key] =& $forums_obj[$key];
        }
global $xoopsLogger;
$xoopsLogger->startTime( 'XOOPS output module - forum - getTree' );
        foreach (array_keys($forums_structured) as $cid) {
            $tree = new newbbObjectTree($forums_structured[$cid]);
            $forum_array[$cid] = $tree->makeTree($prefix, $pid, $tags);
            unset($tree);
        }
$xoopsLogger->stopTime( 'XOOPS output module - forum - getTree' );
        return $forum_array;
    }

    /**
     * get a hierarchical array tree of forums
     *
     * {@link newbbTree}
     *
     * @param     int        $cat_id category ID
     * @param     int        $pid     Top forum ID
     * @param     string    $permission    permission type
     * @param     string    $tags        variables to fetch
     * @param   integer    $depth    level of subcategories
     * @return    array    associative array of category IDs and sanitized titles
     */
    function &getArrayTree($cat_id =0, $pid = 0, $permission = "access", $tags = null, $depth = 0)
    {
        $pid = intval($pid);
        $perm_string = $permission;
        if (!is_array($tags) || count($tags)==0) $tags = array("forum_id", "parent_forum", "forum_name", "forum_order", "cat_id");
        $forums_obj =& $this->getByPermission($cat_id, $perm_string, $tags);

        require_once(dirname(__FILE__) . "/tree.php");
        $forums_structured = array();
        foreach (array_keys($forums_obj) as $key) {
            $forum_obj =& $forums_obj[$key];
            $forums_structured[$forum_obj->getVar("cat_id")][$key] =& $forums_obj[$key];
        }
        foreach (array_keys($forums_structured) as $cid) {
            $tree = new newbbObjectTree($forums_structured[$cid]);
            $forum_array[$cid] = $tree->makeArrayTree($pid, $tags, $depth);
            unset($tree);
        }
        return $forum_array;
    }

    function &getParents(&$object)
    {
        $ret = null;
        if ( !$object->getVar("forum_id") ) return $ret;

        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.forum.php";
        if ( !$parents = newbb_getParentForum($object->getVar("forum_id")) ) return $ret;
        $parents_list = $this->getList(new Criteria("forum_id", "(" . implode(", ", $parents) . ")", "IN"));
        foreach ($parents as $key => $id) {
            $ret[] = array("forum_id" => $id, "forum_name" => $parents_list[$id]);
        }
        unset($parents, $parents_list);
        return $ret;
    }
}
?>