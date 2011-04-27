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
 * @version         $Id: topic.renderer.php 2169 2008-09-23 13:37:10Z phppp $
 */
 
if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}

class NewbbTopicRenderer
{
    /**
     * reference to an object handler
     */
    var $handler;
    
    /**
     * reference to moduleConfig
     */
    var $config;
    
    /**
     * Requested page
     */
    var $page = "list.topic.php";
    
    /**
     * query variables
     */
    var $args = array("forum", "uid", "type", "status", "mode", "sort", "order", "start", "since");
    var $vars = array();

    /**
     * For multiple forums
     */
    var $is_multiple = false; 
    
    /**
     * Vistitor's level: 0 - anonymous; 1 - user; 2 - moderator or admin
     */    
    var $userlevel = 0;
    
    /**
     * Current user has no access to current page
     */
    var $noperm = false;
    
    /**
     *
     */
    var $query = array();
    
    /**
     * Constructor
     */
    function NewbbTopicRenderer()
    {
        $this->handler = xoops_getModuleHandler("topic", "newbb");
    }
    

    /**
     * Access the only instance of this class
     * 
     * @return 
     **/
    function &instance()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new NewbbTopicRenderer();
        }
        return $instance;
    }

    function init()
    {
        $this->noperm = false;
        $this->query = array();
    }
    
    function setVar($var, $val)
    {
        switch($var) {
        case "forum":
            if (is_numeric($val)) { 
                $val = intval($val);
            } elseif (!empty($val)) {
                $val = implode("|", array_map("intval", explode(", ", $val)));
            }
            break;
            
        case "uid":
        case "type":
        case "mode":
        case "order":
        case "start":
        case "since":
            $val = intval($val);
            break;
        
        case "status":
            $val = ( !empty($val) && in_array($val, array_keys($this->getStatus( $this->userlevel ))) ) ? $val : "all";
            if ($val == "all" && !$this->is_multiple) $val = "";
            break;
                
        default:
            break;
        }
        return $val;
    }

    function setVars($vars = array())
    {
        $this->init();
        
        foreach ($vars as $var => $val) {
            if (!in_array($var, $this->args)) continue;
            $this->vars[$var] = $this->setVar($var, $val);
        }
        $this->parseVars();
    }
    
    function _parseStatus($status = null)
    {
        switch ($status) {
            case 'digest':
                $this->query["where"][] = 't.approved = 1';
                $this->query["where"][] = 't.topic_digest = 1';
                break;
                
            case 'unreplied':
                $this->query["where"][] = 't.approved = 1';
                $this->query["where"][] = 't.topic_replies < 1';
                break;
                
            case 'unread':
                $this->query["where"][] = 't.approved = 1';
                // Skip
                if (empty($this->config["read_mode"])) {
                // Use database
                } elseif ($this->config["read_mode"] == 2) {
                    $this->query["join"][] = 'LEFT JOIN ' . $this->handler->db->prefix('bb_reads_topic') . ' AS r ON r.read_item = t.topic_id';
                    $this->query["where"][] = '(r.read_id IS NULL OR r.post_id < t.topic_last_post_id)';
                // User cookie
                } elseif ($this->config["read_mode"] == 1) {
                    if ($lastvisit =$GLOBALS['last_visit']) {
                        $this->query["where"][] = 'p.post_time > ' . $lastvisit;
                    }
                }
                break;
                
            case 'pending':
                if ($this->userlevel < 2) {
                    $this->noperm = true;
                } else {                    
                    $this->query["where"][] = 't.approved = 0';
                }
                break;
                
            case 'deleted':
                if ($this->userlevel < 2) {
                    $this->noperm = true;
                } else {                    
                    $this->query["where"][] = 't.approved = -1';
                }
                break;
                
            case 'all': // For viewall.php; do not display sticky topics at first
            case 'active': // same as "all"
                $this->query["where"][] = 't.approved = 1';
                break;
                
            default:
                $this->query["where"][] = 't.approved = 1';
                $this->query["sort"][] = 't.topic_sticky DESC';
                break;
        }
    }
    
    function parseVar($var, $val)
    {
        switch($var) {
        case "forum":
            $forum_handler = xoops_getmodulehandler('forum', 'newbb');
            // Get accessible forums
            $access_forums = $forum_handler->getIdsByPermission();
            // Filter specified forums if any
            if (!empty($val) && $_forums = @explode("|", $val)) {
                $access_forums = array_intersect($access_forums, array_map("intval", $_forums));
            }
            if (empty($access_forums)) {
                $this->noperm = true;
            } elseif (count($access_forums) == 1) {
                $this->query["where"][] = "t.forum_id = " . $access_forums[0];
            } else {
                $this->query["where"][] = "t.forum_id IN ( " . implode(", ", $access_forums) . " )";
            }
            break;
            
        case "uid":
            if (!empty($val)) {
                $this->query["where"][] = "t.topic_poster = " . $val;
            }
            break;
            
        case "since":
            if (!empty($val)) {
                $this->query["where"][] = "p.post_time = " . (time() - newbb_getSinceTime($val));
            }
            break;
            
        case "type":
            if (!empty($val)) {
                $this->query["where"][] = "t.type_id = " . $val;
            }
            break;
            
        case "status":
            $this->_parseStatus($val);
            break;
            
        case "sort":
            if ($sort = $this->getSort($val, "sort")) {
                $this->query["sort"][] = $sort . (empty($this->vars["order"]) ? " DESC" : " ASC");
            }
            break;
            
        default:
            break;
        }
    }

    function parseVars()
    {
        static $parsed;
        if (isset($parsed)) return true;
        
        if (!isset($this->vars["forum"])) $this->vars["forum"] = null;
        
        foreach ($this->vars as $var => $val) {
            $this->parseVar($var, $val);
            if (empty($val)) unset($this->vars[$var]);
        }
        $parsed = true;
        
        return true;
    }
    
    function getSort($header = null, $var = null)
    {
        $headers = array(
            "topic"        => array(
                            "title"    => _MD_TOPICS,
                            "sort"    => "t.topic_title",
                            ),
            "forum"        => array(
                            "title"    => _MD_FORUM,
                            "sort"    => "t.forum_id",
                            ),
            "poster"    => array(
                            "title"    => _MD_POSTER,
                            "sort"    => "t.topic_poster",
                            ),
            "replies"    => array(
                            "title"    => _MD_REPLIES,
                            "sort"    => "t.topic_replies",
                            ),
            "views"        => array(
                            "title"    => _MD_VIEWS,
                            "sort"    => "t.topic_views",
                            ),
            "lastpost"    => array(
                            "title"    => _MD_DATE,
                            "sort"    => "t.topic_last_post_id",
                            ),
            "ratings"    => array(
                            "title"    => _MD_RATINGS,
                            "sort"    => "t.topic_ratings",
                            ),
            "publish"    => array(
                            "title"    => _MD_TOPICTIME,
                            "sort"    => "t.topic_id",
                            ),
            );
        
        if (empty($header) && empty($var)) {
            return $headers;
        }
        if (!empty($var) && !empty($header)) {
            return @$headers[$header][$var];
        }    
        if (empty($var)) {
            return @$headers[$header];
        }
        $ret = null;
        foreach (array_keys($headers) as $key) {
            $ret[$key] = @$headers[$key][$var];
        }
        return $ret;
    }
    
    function getStatus($type = null, $status = null)
    {
        $links = array(
            ""            => "",
            "all"        => _ALL,
            "digest"    => _MD_DIGEST,
            "unreplied"    => _MD_UNREPLIED,
            "unread"    => _MD_UNREAD,
            );
        $links_admin = array(
            " "            => "",
            "active"    => _MD_TYPE_ADMIN,
            "pending"    => _MD_TYPE_PENDING,
            "deleted"    => _MD_TYPE_DELETED,
            );
        
        // specified status
        if ($status !== null) {
            if (isset($links[$status])) return $links[$status];
            return @$links_admin[$status];
        }
        
        // all status, for admin
        if ($type > 1) {
            return array_merge($links, $links_admin);
        }
        
        // for regular users
        //if ($type == 1) {
            return $links;
        //}
        
        //return $links_admin;
    }
    
    function buildSelection(&$xoopsTpl)
    {
        $selection = array("action" => $this->page);
        $selection["vars"] = $this->vars;
        $selection["vars"]["order"] = $selection["vars"]["since"] = null;
                
        $sort_selected = empty($this->vars["sort"]) ? "lastpost" : $this->vars["sort"];
        $sorts = $this->getSort("", "title");
        $selection["sort"] = "<select name='sort'>";
        foreach ($sorts as $sort => $title) {
            $selection["sort"] .= "<option value='{$sort}' " . (($sort == $sort_selected) ? " selected='selected'" : "") . ">{$title}</option>";
        }
        $selection["sort"] .= "</select>";
        
        $selection["order"] = "<select name='order'>";
        $selection["order"] .= "<option value='0' " . (empty($this->vars["order"]) ? " selected='selected'" : "") . ">" . _DESCENDING . "</option>";
        $selection["order"] .= "<option value='1' " . (!empty($this->vars["order"]) ? " selected='selected'" : "") . ">" . _ASCENDING . "</option>";
        $selection["order"] .= "</select>";
        
        $since = isset($this->vars['since']) ? $this->vars['since'] : $this->config["since_default"];
        $selection["since"] = newbb_sinceSelectBox($since);
        
        $xoopsTpl->assign_by_ref('selection', $selection);
    }
    
    function buildSearch(&$xoopsTpl)
    {
        $search = array();
        $search["forum"] = @$this->vars["forum"];
        $search["since"] = @$this->vars["since"];
        $search["searchin"] = "both";
        
        $xoopsTpl->assign_by_ref('search', $search);
    }
    
    function buildHeaders(&$xoopsTpl)
    {
        $args = array();
        foreach ($this->vars as $var => $val) {
            if ($var == "sort" || $var == "order") continue;
            $args[] = "{$var}={$val}";
        }
        
        $headers = $this->getSort("", "title");
        foreach ($headers as $header => $title) {
            $_args = array("sort={$header}");
            if (@$this->vars["sort"] == $header) {
                $_args[] = "order=" . ( (@$this->vars["order"] + 1) % 2);
            }
            $headers_data[$header]["title"] = $title;
            $headers_data[$header]["link"] = $this->page . "?" . implode("&amp;", array_merge($args, $_args));
        }
        $xoopsTpl->assign_by_ref('headers', $headers_data);
    }
    
    function buildFilters(&$xoopsTpl)
    {
        $args = array();
        foreach ($this->vars as $var => $val) {
            if ($var == "status") continue;
            $args[] = "{$var}={$val}";
        }
        
        $links = $this->getStatus( $this->userlevel );
        
        $status = array();
        foreach ($links as $link => $title) {
            $_args = array("status={$link}");
            $status[$link]["title"] = $title;
            $status[$link]["link"] = $this->page . "?" . implode("&amp;", array_merge($args, $_args));
        }
        $xoopsTpl->assign_by_ref('filters', $status);
    }
    
    function getTypes($type_id = null)
    {
        static $types;
        if (!isset($types)) {
            $type_handler =& xoops_getmodulehandler('type', 'newbb');
            $types = $type_handler->getByForum(explode("|", @$this->vars["forum"]));
        }
        
        if (empty($type_id)) return $types;
        return @$types[$type_id];
    }
    
    function buildTypes(&$xoopsTpl)
    {
        if (!$types = $this->getTypes()) {
            return true;
        }
        
        $args = array();
        foreach ($this->vars as $var => $val) {
            if ($var == "type") continue;
            $args[] = "{$var}={$val}";
        }
        
        foreach ($types as $id => $type) {
            $_args = array("type={$id}");
            $status[$id]["title"] = $type["type_name"];
            $status[$id]["link"] = $this->page . "?" . implode("&amp;", array_merge($args, $_args));
        }
        $xoopsTpl->assign_by_ref('types', $status);
    }
    
    function buildCurrent(&$xoopsTpl)
    {
        if (empty($this->vars["status"]) && !$this->is_multiple) return true;
        
        $args = array();
        foreach ($this->vars as $var => $val) {
            $args[] = "{$var}={$val}";
        }
        
        $status = array();
        $status["title"] = $this->getStatus(0, empty($this->vars["status"]) ? "all" : $this->vars["status"]);
        //$status["link"] = $this->page.(empty($this->vars["status"]) ? "" : "?status=".$this->vars["status"]);
        $status["link"] = $this->page . (empty($args) ? "" : "?" . implode("&amp;", $args));
        
        $xoopsTpl->assign_by_ref('current', $status);
    }
    
    function buildPagenav(&$xoopsTpl)
    {
        $count_topic = $this->getCount();
        if ($count_topic > $this->config['topics_per_page']) {
            $args = array();
            foreach ($this->vars as $var => $val) {
                if ($var == "start") continue;
                $args[] = "{$var}={$val}";
            }
            require_once XOOPS_ROOT_PATH . '/class/pagenav.php';
            $nav = new XoopsPageNav($count_topic, $this->config['topics_per_page'], @$this->vars["start"], "start", implode("&amp;", $args));
            $xoopsTpl->assign('pagenav', $nav->renderNav(4));
        } else {
            $xoopsTpl->assign('pagenav', '');
        }
    }
    
    function getCount()
    {
        
        if ($this->noperm) {
            return 0;
        }
        
        $selects = array();
        $froms = array();
        $joins = array();
        $wheres = array();
        
        // topic fields
        $selects[] = 'COUNT(*)';
        
        $froms[] = $this->handler->db->prefix("bb_topics") . ' AS t ';
        $joins[] = 'LEFT JOIN ' . $this->handler->db->prefix('bb_posts') . ' AS p ON p.post_id = t.topic_last_post_id';
        $wheres[] = "1 = 1";
 
$GLOBALS['xoopsLogger']->startTime( 'XOOPS output module - render - topics - count' );
        
        $sql =  '    SELECT ' . implode(", ", $selects) .
                '     FROM ' . implode(", ", $froms) .
                '        ' . implode(" ", $joins) .
                '        ' . implode(" ", $this->query["join"]) .
                '     WHERE ' . implode(" AND ", $wheres) .
                '        AND ' . @implode(" AND ", @$this->query["where"]);
        if (!$result = $this->handler->db->query($sql)) {
            return 0;
        }
$GLOBALS['xoopsLogger']->stopTime( 'XOOPS output module - render - topics - count' );
        list($count) = $this->handler->db->fetchRow($result);
        return $count;
    }
    
    function renderTopics($xoopsTpl = null)
    {
        global $myts;
        
        $ret = array();
        //$this->parseVars();
        
        if ($this->noperm) {
            if (is_object($xoopsTpl)) {
                $xoopsTpl->assign_by_ref("topics", $ret);
                return;
            }
            return $ret;
        }
        
        $selects = array();
        $froms = array();
        $joins = array();
        $wheres = array();
        
        // topic fields
        $selects[] = 't.*';
        // post fields
        $selects[] = 'p.post_time as last_post_time, p.poster_name as last_poster_name, p.icon, p.post_id, p.uid';
        
        $froms[] = $this->handler->db->prefix("bb_topics") . ' AS t ';
        $joins[] = 'LEFT JOIN ' . $this->handler->db->prefix('bb_posts') . ' AS p ON p.post_id = t.topic_last_post_id';
        $wheres[] = "1 = 1";
        
        if (!empty($this->config['post_excerpt'])) {
            $selects[] = 'p.post_karma, p.require_reply, pt.post_text';
            $this->query["join"][] = 'LEFT JOIN ' . $this->handler->db->prefix('bb_posts_text') . ' AS pt ON pt.post_id = t.topic_last_post_id';
        }
        if (empty($this->query["sort"])) $this->query["sort"][] = 't.topic_last_post_id DESC';
 
$GLOBALS['xoopsLogger']->startTime( 'XOOPS output module - render - topics - query' );
        
        $sql =  '    SELECT ' . implode(", ", $selects) .
                '     FROM ' . implode(", ", $froms) .
                '        ' . implode(" ", $joins) .
                '        ' . implode(" ", $this->query["join"]) .
                '     WHERE ' . implode(" AND ", $wheres) .
                '        AND ' . @implode(" AND ", @$this->query["where"]) .
                '     ORDER BY ' . implode(", ", $this->query["sort"]);
        if (!$result = $this->handler->db->query($sql, $this->config['topics_per_page'], @$this->vars["start"])) {
            if (is_object($xoopsTpl)) {
                $xoopsTpl->assign_by_ref("topics", $ret);
                return;
            }
            return $ret;
        }
$GLOBALS['xoopsLogger']->stopTime( 'XOOPS output module - render - topics - query' );
         
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.render.php";
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.session.php";
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.read.php";
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.topic.php";

        $sticky = 0;
        $topics = array();
        $posters = array();
        $reads = array();
        $types = array();
        $forums = array();
        $anonymous = $myts->htmlSpecialChars( $GLOBALS["xoopsConfig"]['anonymous'] );
$GLOBALS['xoopsLogger']->startTime( 'XOOPS output module - render - topics - fetch' );
        
        while ($myrow = $this->handler->db->fetchArray($result)) {
            if ($myrow['topic_sticky']) {
                $sticky++;
            }
            
            // ------------------------------------------------------
            // topic_icon: priority: sticky -> digest -> regular
            
            if ($myrow['topic_haspoll']) {
                if ($myrow['topic_sticky']) {
                    $topic_icon = newbb_displayImage("topic_sticky", _MD_TOPICSTICKY) . '<br />' . newbb_displayImage("poll", _MD_TOPICHASPOLL);
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
            $totalpages = ceil(($myrow['topic_replies'] + 1) / $this->config['posts_per_page']);
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
                        $topic_page_jump .= '[<a href="viewtopic.php?topic_id=' . $myrow['topic_id'] . '&amp;start=' . (($i - 1) * $this->config['posts_per_page']) . '">' . $i . '</a>]';
                        $topic_page_jump_icon = "<a href='" . XOOPS_URL . "/modules/newbb/viewtopic.php?topic_id=" . $myrow['topic_id'] . "&amp;start=" . (($i - 1) * $this->config['posts_per_page']) . "#forumpost" . $myrow['post_id'] . "'>" . newbb_displayImage('document') . "</a>";
                    }
                }
            }
            else {
                $topic_page_jump_icon = "<a href='" . XOOPS_URL . "/modules/newbb/viewtopic.php?topic_id=" . $myrow['topic_id'] . "#forumpost" . $myrow['post_id'] . "'>" . newbb_displayImage('document') . "</a>";
            }
            
            // ------------------------------------------------------
            // => topic array

               $topic_title = $myts->htmlSpecialChars( $myrow['topic_title'] );
            if ($myrow['topic_digest']) {
                $topic_title = "<span class='digest'>" . $topic_title . "</span>";
            }

            if ( empty($this->config["post_excerpt"]) ) {
                $topic_excerpt = "";
            } elseif ( ($myrow['post_karma'] > 0 || $myrow['require_reply'] > 0) && !newbb_isAdmin($myrow['forum_id']) ) {
                $topic_excerpt = "";
            } else {
$GLOBALS['xoopsLogger']->startTime( 'XOOPS output module - render - topics - fetch - substr '. $myrow['topic_id']);
                $topic_excerpt = xoops_substr(newbb_html2text($myts->displayTarea($myrow['post_text'])), 0, $this->config["post_excerpt"]);
$GLOBALS['xoopsLogger']->stopTime( 'XOOPS output module - render - topics - fetch - substr '. $myrow['topic_id']);
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
                'topic_poster_name'        => !empty($myrow['poster_name']) ? $myts->htmlSpecialChars($myrow['poster_name']) : $anonymous,
                'topic_views'            => $myrow['topic_views'],
                'topic_time'            => newbb_formatTimestamp($myrow['topic_time']),
                'topic_last_posttime'        => newbb_formatTimestamp($myrow['last_post_time']),
                'topic_last_poster_uid'        => $myrow['uid'],
                'topic_last_poster_name'    => !empty($myrow['last_poster_name']) ? $myts->htmlSpecialChars( $myrow['last_poster_name'] ) : $anonymous,
                'topic_forum'            => $myrow['forum_id'],
                'topic_excerpt'            => $topic_excerpt,
                'stick' => empty($myrow['topic_sticky']),
                "stats" => array($myrow['topic_status'], $myrow['topic_digest'], $myrow['topic_replies']),
                );
                
            /* users */
            $posters[$myrow['topic_poster']] = 1;
            $posters[$myrow['uid']] = 1;
            // reads
            if (!empty($this->config["read_mode"])) {
                $reads[$myrow['topic_id']] = ($this->config["read_mode"] == 1) ? $myrow['last_post_time'] : $myrow["topic_last_post_id"];
            }
            // types
            if (!empty($myrow['type_id'])) {
                //$types[$myrow['type_id']] = 1;
            }
            // forums
               $forums[$myrow['forum_id']] = 1;
        }
$GLOBALS['xoopsLogger']->stopTime( 'XOOPS output module - render - topics - fetch' );
        $posters_name = newbb_getUnameFromIds(array_keys($posters), $this->config['show_realname'], true);
        $topic_isRead = newbb_isRead("topic", $reads);
        /*
        $type_list = array();
         if (count($types) > 0) {
            $type_handler =& xoops_getmodulehandler('type', 'newbb');
            $type_list = $type_handler->getAll(new Criteria("type_id", "(".implode(", ", array_keys($types)).")", "IN"), null, false);
        }
        */
        $type_list = $this->getTypes();
        $forum_handler =& xoops_getmodulehandler('forum', 'newbb');
        $forum_list = $forum_handler->getAll(new Criteria("forum_id", "(".implode(", ", array_keys($forums)).")", "IN"), array("forum_name", "hot_threshold"), false);
       
        foreach (array_keys($topics) as $id) {
            $topics[$id]["topic_forum_link"] = '<a href="' . XOOPS_URL . '/modules/newbb/viewforum.php?forum=' . $topics[$id]["topic_forum"] . '">' . $forum_list[$topics[$id]["topic_forum"]]["forum_name"] . '</a>';
            
            if (!empty($topics[$id]["type_id"]) && isset($type_list[$topics[$id]["type_id"]])) {
                $topics[$id]["topic_title"] = newbb_getTopicTitle($topics[$id]["topic_title"], $type_list[$topics[$id]["type_id"]]["type_name"], $type_list[$topics[$id]["type_id"]]["type_color"]);
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
                } elseif ($topic_replies >= $forum_list[$topics[$id]["topic_forum"]]["hot_threshold"]) {
                    $topic_folder = empty($topic_isRead[$id]) ? 'topic_hot_new' : 'topic_hot';
                } else {
                    $topic_folder = empty($topic_isRead[$id]) ? 'topic_new' : 'topic';
                }
            }
            $topics[$id]['topic_folder'] = newbb_displayImage($topic_folder);
                                            
            unset($topics[$id]["topic_poster_name"], $topics[$id]["topic_last_poster_name"], $topics[$id]["stats"]);
        }

        if ( count($topics) > 0) {
            $sql = " SELECT DISTINCT topic_id FROM " . $this->handler->db->prefix("bb_posts").
                     " WHERE attachment != ''".
                     " AND topic_id IN (" . implode(',', array_keys($topics)) . ")";
            if ($result = $this->handler->db->query($sql)) {
                while (list($topic_id) = $this->handler->db->fetchRow($result)) {
                    $topics[$topic_id]['attachment'] = '&nbsp;' . newbb_displayImage('attachment', _MD_TOPICSHASATT);
                }
            }
        }
        
        if (is_object($xoopsTpl)) {
            $xoopsTpl->assign_by_ref("sticky", $sticky);
            $xoopsTpl->assign_by_ref("topics", $topics);
            return;
        }
        return array($topics, $sticky);
    }
}

?>