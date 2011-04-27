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
 * @version         $Id: user.php 2169 2008-09-23 13:37:10Z phppp $
 */
 
if (!defined("XOOPS_ROOT_PATH")) {
    exit();
}

function newbb_calculateLevel($RPG, $RPGDIFF)
{
    $today = time();
    $diff = $today - $RPGDIFF;
    $exp = round($diff / 86400,0);
    if ($exp <= 0) {
        $exp = 1;
    }
    $ppd= round($RPG / $exp, 0);
    $level = pow (log10 ($RPG), 3);
    $ep = floor (100 * ($level - floor ($level)));
    $showlevel = floor ($level + 1);
    $hpmulti =round ($ppd / 6, 1);
    if ($hpmulti > 1.5) { 
        $hpmulti = 1.5; 
    }
    if ($hpmulti < 1) { 
        $hpmulti = 1;
    }
    $maxhp = $level * 25 * $hpmulti;
    $hp= $ppd / 5;
    if ($hp >= 1) {
        $hp= $maxhp;
    } else {
        $hp= floor ($hp * $maxhp);
    }
    $hp= floor ($hp);
    $maxhp= floor ($maxhp);
    if ($maxhp <= 0) {
        $zhp = 1;
    } else {
        $zhp = $maxhp;
    }
    $hpf= floor (100 * ($hp / $zhp)) - 1;
    $maxmp= ($exp * $level) / 5;
    $mp= $RPG / 3;
    if ($mp >= $maxmp) {
        $mp = $maxmp;
    }
    $maxmp = floor ($maxmp);
    $mp = floor ($mp);
    if ($maxmp <= 0) {
        $zmp = 1;
    } else {
        $zmp = $maxmp;
    }
    $mpf= floor (100 * ($mp / $zmp)) - 1;
    if ( $hpf >= 98 ) { $hpf = $hpf - 2; }
    if ( $ep >= 98 ) { $ep = $ep - 2; }
    if ( $mpf >= 98 ) { $mpf = $mpf - 2; }

    $level = array();
    $level['level']  = $showlevel ;
    $level['exp'] = $ep;
    $level['exp_width'] = $ep . '%';
    $level['hp']  = $hp;
    $level['hp_max']  = $maxhp;
    $level['hp_width'] = $hpf . '%';
    $level['mp']  = $mp;
    $level['mp_max']  = $maxmp;
    $level['mp_width'] = $mpf . '%';

    return $level;
}

class User
{
    var $user = null;
    
    function User()
    {
    }

    function getUserbar()
    {
        global $xoopsModuleConfig, $xoopsUser, $isadmin;
        
        $userbar = array();
        if (empty($xoopsModuleConfig['userbar_enabled'])) return $userbar;
        
        $user = $this->user;
        $userbar["profile"] = array("link" => XOOPS_URL . "/userinfo.php?uid=" . $user->getVar("uid"), "name" => _PROFILE);
        
        if (is_object($xoopsUser)) {
            $userbar["pm"] = array("link" => "javascript:void openWithSelfMain('" . XOOPS_URL . "/pmlite.php?send2=1&amp;to_userid=" . $user->getVar("uid") . "', 'pmlite', 450, 380);", "name" => _MD_PM);
        }
        if ($user->getVar('user_viewemail') || $isadmin) {
            $userbar["email"] = array("link" => "javascript:void window.open('mailto:" . $user->getVar('email') . "', 'new');", "name" => _MD_EMAIL);
        }
        if ($url = $user->getVar('url')) {
            $userbar["url"] = array("link" => "javascript:void window.open('" . $url . "', 'new');", "name" => _MD_WWW);
        }
        if ($icq = $user->getVar('user_icq')) {
            $userbar["icq"] = array("link" => "javascript:void window.open('http://wwp.icq.com/scripts/search.dll?to=" . $icq."', 'new');", "name" => _MD_ICQ);
        }
        if ($aim = $user->getVar('user_aim')) {
            $userbar["aim"]= array("link" => "javascript:void window.open('aim:goim?screenname=" . $aim . "&amp;message=Hi+" . $aim . "+Are+you+there?" . "', 'new');", "name" => _MD_AIM);
        }
        if ($yim = $user->getVar('user_yim')) {
            $userbar["yim"] = array("link" => "javascript:void window.open('http://edit.yahoo.com/config/send_webmesg?.target=" . $yim . "&.src=pg" . "', 'new');", "name" => _MD_YIM);
        }
        if ($msn = $user->getVar('user_msnm')) {
            $userbar["msnm"] = array("link" => "javascript:void window.open('http://members.msn.com?mem=" . $msn . "', 'new');", "name" => _MD_MSNM);
        }
        
        return $userbar;
    }
    
    function getLevel()
    {
        global $xoopsModuleConfig, $forumUrl;
        
        $level = newbb_calculateLevel($this->user->getVar("posts"), $this->user->getVar("user_regdate"));
        if ($xoopsModuleConfig['user_level'] == 2) {
            static $rpg_images;
            if (!isset($rpg_images)) {
                $icon_handler = newbb_getIconHandler();
                $rpg_path = $icon_handler->getPath("rpg");
                foreach (array("img_left", "img_backing", "img_right", "blue", "green", "orange") as $img) {
                    $rpg_images[$img] = XOOPS_URL . '/' . $rpg_path . '/' . $img . '.gif';
                }
            }
            $table = "<table class='userlevel'><tr><td class='end'><img src='" . $rpg_images['img_left'] . "' alt='' /></td><td class='center' background='" . $rpg_images['img_backing'] . "'><img src='%s' width='%d' alt='' /></td><td><img src='" . $rpg_images['img_right'] . "' alt='' /></td></tr></table>";

            $info = _MD_LEVEL . " " . $level['level'] . "<br />" . _MD_HP . " " . $level['hp'] . " / " . $level['hp_max'] . "<br />".
                sprintf($table, $rpg_images["orange"], $level['hp_width']);
            $info .= _MD_MP . " " . $level['mp'] . " / " . $level['mp_max'] . "<br />".
                sprintf($table, $rpg_images["green"], $level['mp_width']);
            $info .= _MD_EXP . " " . $level['exp'] . "<br />".
                sprintf($table, $rpg_images["blue"], $level['exp_width']);
        } else {
            $info = _MD_LEVEL . " " . $level['level'] . "; ". _MD_EXP . " " . $level['exp'] . "<br />";
            $info .= _MD_HP . " " . $level['hp'] . " / " . $level['hp_max'] . "<br />";
            $info .= _MD_MP . " " . $level['mp'] . " / " . $level['mp_max'];
        }
        return $info;
    }

    function getInfo(&$user)
    {
        global $xoopsModuleConfig, $myts;
        static $name_anonymous;
        
        if ( !(is_object($user)) || !($user->isActive()) )    {
            if (!isset($name_anonymous)) {
                $name_anonymous = $myts->HtmlSpecialChars($GLOBALS["xoopsConfig"]['anonymous']);
            }
            return array("name" => $name_anonymous, "link" => $name_anonymous);
        }
        
        $this->user = $user;
        
        $userinfo["uid"] = $user->getVar("uid");
        
        $name = empty($xoopsModuleConfig['show_realname']) ? $user->getVar('uname') : $user->getVar('name');
        $userinfo["name"] = $name ? $name : $user->getVar('uname');
        
        $userinfo["link"] = "<a href=\"".XOOPS_URL . "/userinfo.php?uid=" . $user->getVar("uid") . "\" title=''>" . $userinfo["name"] . "</a>";
        
        $userinfo["avatar"] = $user->getVar('user_avatar');
        
        $userinfo["from"] = $user->getVar('user_from');
        
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.time.php";
        $userinfo["regdate"] = newbb_formatTimestamp($user->getVar('user_regdate'), 'reg');
        
        $userinfo["posts"] = $user->getVar('posts');
        
        if (!empty($xoopsModuleConfig['user_level'])) {
            $userinfo["level"] = $this->getLevel();
        }
        
        if (!empty($xoopsModuleConfig['userbar_enabled'])) {
            $userinfo["userbar"] = $this->getUserbar();
        }

        $userinfo["signature"] = $user->getVar('user_sig');
        return $userinfo;
    }
}

class NewbbUserHandler
{
    var $enableGroup;
    var $enableOnline;
    var $userlist = array();
    var $users = array();
    //var $online = array();

    function NewbbUserHandler($enableGroup = true, $enableOnline = true)
    {
        $this->enableGroup = $enableGroup;
        $this->enableOnline = $enableOnline;
    }
    
    function loadUserInfo()
    {
        @include_once XOOPS_ROOT_PATH . "/modules/" . $GLOBALS["xoopsModule"]->getVar("dirname", "n") . "/language/" . $GLOBALS["xoopsConfig"]["language"] . "/user.php";
        if (class_exists("User_language")) {
            $handler = new User_language();
        } else {
            $handler = new User();
        }
        foreach (array_keys($this->users) as $uid) {
               $this->userlist[$uid] = $handler->getInfo($this->users[$uid]);
        }
    }
    
    function loadUserOnline()
    {
        if (empty($this->users) || !$this->enableOnline) return;
        require_once XOOPS_ROOT_PATH . "/modules/newbb/include/functions.render.php";
        $image_online = newbb_displayImage('online', _MD_ONLINE);
        $image_offline = newbb_displayImage('offline',_MD_OFFLINE);
        
        $online_handler =& xoops_getmodulehandler('online', 'newbb');
        $onlines = $online_handler->checkStatus(array_keys($this->users));
        
        foreach (array_keys($this->users) as $uid) {
            $this->userlist[$uid]["status"] = empty($onlines[$uid]) ? $image_offline : $image_online;
        }
    }
    
    function loadUserGroups()
    {
        GLOBAL $xoopsDB;
        
        if (empty($this->users) || !$this->enableGroup) return;
        
        $groups = array();
        $member_handler =& xoops_gethandler('member');
        $groups_obj = $member_handler->getGroups();
        $count = count($groups_obj);
        for ($i = 0; $i < $count; $i++) {
            $groups[$groups_obj[$i]->getVar('groupid')] = $groups_obj[$i]->getVar('name');
        }
        unset($groups_obj);
        
        $sql = 'SELECT groupid, uid FROM ' . $xoopsDB->prefix('groups_users_link') . " WHERE uid IN( " . implode(", ", array_keys($this->users)) . ")";
        $result = $xoopsDB->query($sql);
        while ($myrow = $xoopsDB->fetchArray($result)) {
            $this->userlist[$myrow['uid']]["groups"][] = $groups[$myrow['groupid']];
        }
    }
    
    function loadUserDigest()
    {
        GLOBAL $xoopsDB;
        
        if (empty($this->users)) return;
        
        $sql = 'SELECT user_digests, uid FROM ' . $xoopsDB->prefix('bb_user_stats') . " WHERE uid IN( " . implode(", ", array_keys($this->users)) . ")";
        $result = $xoopsDB->query($sql);
        while ($myrow = $xoopsDB->fetchArray($result)) {
            $this->userlist[$myrow['uid']]["digests"] = intval( $myrow['user_digests'] );
        }
    }
    
    function loadUserRank()
    {
        GLOBAL $xoopsDB;
        
        if (empty($this->users)) return;
        $myts =& MyTextSanitizer::getInstance();
        
        $sql = 'SELECT * FROM ' . $xoopsDB->prefix('ranks');
        $result = $xoopsDB->query($sql);
        while ($myrow = $xoopsDB->fetchArray($result)) {
            $ranks[$myrow['rank_id']] = $myrow;
            $ranks[$myrow['rank_id']]["rank_title"] = $myts->htmlspecialchars($ranks[$myrow['rank_id']]["rank_title"]);
            if (!empty($ranks[$myrow['rank_id']]['rank_image'])) {
                $ranks[$myrow['rank_id']]['rank_image'] = "<img src='" . XOOPS_UPLOAD_URL . "/" . htmlspecialchars($ranks[$myrow['rank_id']]['rank_image'], ENT_QUOTES) . "' alt='' />";
            }
        }
    
        foreach (array_keys($this->userlist) as $uid) {
            if ($rank = $this->users[$uid]->getVar("rank")) {
                $this->userlist[$uid]["rank"]["title"] = $ranks[$rank]["rank_title"];        
                $this->userlist[$uid]["rank"]["image"] = $ranks[$rank]["rank_image"];        
                continue;
            }
            foreach ($ranks as $id => $rank) {
                if ($rank["rank_min"] <= $this->userlist[$uid]["posts"] && $rank["rank_max"] >= $this->userlist[$uid]["posts"]) {
                    $this->userlist[$uid]["rank"]["title"] = $rank["rank_title"];        
                    $this->userlist[$uid]["rank"]["image"] = $rank["rank_image"];        
                    break;
                }
            }
        }
    }
    
    function getUsers()
    {
        $this->loadUserInfo();
        $this->loadUserOnline();
        $this->loadUserGroups();
        $this->loadUserRank();
        $this->loadUserDigest();
        
        return $this->userlist;
    }
}

?>