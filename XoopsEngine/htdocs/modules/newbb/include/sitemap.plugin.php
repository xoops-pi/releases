<?php
// $Id: sitemap.plugin.php 2170 2008-09-23 13:40:23Z phppp $
// FILE        ::    newbb.php
// AUTHOR    ::    Ryuji AMANO <info@ryus.biz>
// WEB        ::    Ryu's Planning <http://ryus.biz/>

// CBB/newbb2 plugin: D.J., http://xoops.org.cn

function b_sitemap_newbb() {
    global $sitemap_configs;
    $sitemap = array();
    
    $forum_handler =& xoops_getmodulehandler('forum', 'newbb');
    /* Allowed forums */
    $forums_allowed = $forum_handler->getIdsByPermission();
    
    /* fetch top forums */
    $forums_top_id = array();
    if (!empty($forums_allowed)) {
        $crit_top = new CriteriaCompo(new Criteria("parent_forum", 0));
        //$crit_top->add(new Criteria("cat_id", "(".implode(", ", array_keys($categories)).")", "IN"));
        $crit_top->add(new Criteria("forum_id", "(".implode(", ", $forums_allowed).")", "IN"));
        $forums_top_id = $forum_handler->getIds($crit_top);
    }
    
    $forums_sub_id = array();
    if ($sitemap_configs["show_subcategoris"] && !empty($forums_top_id)) {
        $crit_sub = new CriteriaCompo(new Criteria("parent_forum", "(".implode(", ", $forums_top_id).")", "IN"));
        $crit_sub->add(new Criteria("forum_id", "(".implode(", ", $forums_allowed).")", "IN"));
        $forums_sub_id = $forum_handler->getIds($crit_sub);
    }
    
    /* Fetch forum data */
    $forums_available = array_merge($forums_top_id, $forums_sub_id);
    $forums_array = array();
    if (!empty($forums_available)) {
        $crit_forum = new Criteria("forum_id", "(".implode(", ", $forums_available).")", "IN");
        $crit_forum->setSort("cat_id ASC, parent_forum ASC, forum_order");
        $crit_forum->setOrder("ASC");
        $forums_array = $forum_handler->getAll($crit_forum, array("forum_name", "parent_forum", "cat_id"), false);
    }
        
    $forums = array();
    foreach ($forums_array as $forumid => $forum) {
        if (!empty($forum["parent_forum"])) {
            $forums[$forum['parent_forum']]["fchild"][$forumid] = array(
                    'id' => $forumid,
                    'url' => "viewforum.php?forum=".$forumid,
                    'title' => $forum['forum_name']
            );
        } else {
            $forums[$forumid] = array(
                'id' => $forumid,
                'cid' => $forum['cat_id'],
                'url' => "viewforum.php?forum=".$forumid,
                'title' => $forum['forum_name']
            );
        }
    }

    if ($sitemap_configs["show_subcategoris"]) {
        $category_handler =& xoops_getmodulehandler('category', 'newbb');
        $categories = $category_handler->getByPermission('access', array("cat_id", "cat_title"), false);
        
        foreach ( $categories as $key => $category ) {
            $cat_id = $category["cat_id"];
            $i = $cat_id;
            $sitemap['parent'][$i]['id'] = $cat_id;
            $sitemap['parent'][$i]['title'] = $category["cat_title"];
            $sitemap['parent'][$i]['url'] = "index.php?cat=".$cat_id;
        }
        foreach ( $forums as $id => $forum ) {
            $cid = $forum['cid'];
            $sitemap['parent'][$cid]['child'][$id] = $forum;
            $sitemap['parent'][$cid]['child'][$id]['image'] = 2;
            if (empty($forum['fchild'])) continue;
            
            foreach ($forum['fchild'] as $_id => $_forum) {
                $sitemap['parent'][$cid]['child'][$_id] = $_forum;
                $sitemap['parent'][$cid]['child'][$_id]['image'] = 3;
            }
        }
    } else {
        foreach ( $forums as $id => $forum ) {
            $sitemap['parent'][$id] = $forum;
        }
    }
    return $sitemap;

}


?>