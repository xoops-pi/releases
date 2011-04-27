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
 * @version         $Id: admin_votedata.php 2167 2008-09-23 13:33:57Z phppp $
 */

include 'admin_header.php';

$op = !empty($_GET['op'])? $_GET['op'] : (!empty($_POST['op']) ? $_POST['op'] : "");

switch ($op)
{
    case "delvotes":
        $rid = intval($_GET['rid']);
        $topic_id = intval($_GET['topic_id']);
        $sql = $xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix('bb_votedata') . " WHERE ratingid = {$rid}");
        $xoopsDB->query($sql);
        
        $query = "select rating FROM " . $xoopsDB -> prefix('bb_votedata') . " WHERE topic_id = {$topic_id}";
        $voteresult = $xoopsDB -> query($query);
        $votesDB = $xoopsDB -> getRowsNum($voteresult);
        $totalrating = 0;
        while (list($rating) = $xoopsDB -> fetchRow($voteresult)) {
            $totalrating += $rating;
        }
        $finalrating = $totalrating / $votesDB;
        $finalrating = number_format($finalrating, 4);
        $sql = sprintf("UPDATE %s SET rating = %u, votes = %u WHERE topic_id = %u", $xoopsDB -> prefix('bb_topics'), $finalrating, $votesDB, $topic_id);
        $xoopsDB -> queryF($sql);
        
        redirect_header("admin_votedata.php", 1, _AM_NEWBB_VOTEDELETED);
        break;

    case 'main':
    default:

        include_once XOOPS_ROOT_PATH . "/modules/" . $xoopsModule->getVar("dirname") . "/include/functions.render.php";
        $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
        $useravgrating = '0';
        $uservotes = '0';

        $sql = "SELECT * FROM " . $xoopsDB->prefix('bb_votedata') . " ORDER BY ratingtimestamp DESC";
        $results = $xoopsDB->query($sql, 20, $start);
        $votes = $xoopsDB->getRowsNum($results);

        $sql = "SELECT rating FROM " . $xoopsDB->prefix('bb_votedata');
        $result2 = $xoopsDB->query($sql, 20, $start);
        $uservotes = $xoopsDB->getRowsNum($result2);
        $useravgrating = 0;

        while (list($rating2) = $xoopsDB->fetchRow($result2))
        {
            $useravgrating = $useravgrating + $rating2;
        }
        if ($useravgrating > 0)
        {
            $useravgrating = $useravgrating / $uservotes;
            $useravgrating = number_format($useravgrating, 2);
        }

        xoops_cp_header();
        loadModuleAdminMenu(10, _AM_NEWBB_VOTE_RATINGINFOMATION);


    echo "
        <fieldset><legend style='font-weight: bold; color: #900;'>" . _AM_NEWBB_VOTE_DISPLAYVOTES . "</legend>\n
        <div style='padding: 8px;'>\n
        <div><strong>" . _AM_NEWBB_VOTE_USERAVG . ": </strong>{$useravgrating}</div>\n
        <div><strong>" . _AM_NEWBB_VOTE_TOTALRATE . ": </strong>{$uservotes}</div>\n
        <div style='padding: 8px;'>\n
        <ul><li>" . newbb_displayImage('admin_delete', _DELETE) . " " . _AM_NEWBB_VOTE_DELETEDSC . "</li></ul>
        <div>\n
        </fieldset>\n
        <br />\n

        <table width='100%' cellspacing='1' cellpadding='2' class='outer'>\n
        <tr>\n
        <th align='center'>" . _AM_NEWBB_VOTE_ID . "</th>\n
        <th align='center'>" . _AM_NEWBB_VOTE_USER . "</th>\n
        <th align='center'>" . _AM_NEWBB_VOTE_IP . "</th>\n
        <th align='center'>" . _AM_NEWBB_VOTE_FILETITLE . "</th>\n
        <th align='center'>" . _AM_NEWBB_VOTE_RATING . "</th>\n
        <th align='center'>" . _AM_NEWBB_VOTE_DATE . "</th>\n
        <th align='center'>" . _AM_NEWBB_ACTION . "</th></tr>\n";

        if ($votes == 0) {
            echo "<tr><td align='center' colspan='7' class='head'>" . _AM_NEWBB_VOTE_NOVOTES . "</td></tr>";
        }
        while (list($ratingid, $topic_id, $ratinguser, $rating, $ratinghostname, $ratingtimestamp) = $xoopsDB->fetchRow($results)) {
            $sql = "SELECT topic_title FROM " . $xoopsDB->prefix('bb_topics') . " WHERE topic_id=" . $topic_id . "";
            $down_array = $xoopsDB->fetchArray($xoopsDB->query($sql));

            $formatted_date = formatTimestamp($ratingtimestamp, _DATESTRING);
            $ratinguname = newbb_getUnameFromId($ratinguser, $xoopsModuleConfig['show_realname']);
    echo "
        <tr>\n
        <td class='head' align='center'>{$ratingid}</td>\n
        <td class='even' align='center'>{$ratinguname}</td>\n
        <td class='even' align='center'>{$ratinghostname}</td>\n
        <td class='even' align='left'><a href='" . XOOPS_URL . "/modules/newbb/viewtopic.php?topic_id={$topic_id}' rel='external'>" . $myts->htmlSpecialChars($down_array['topic_title']) . "</a></td>\n
        <td class='even' align='center'>{$rating}</td>\n
        <td class='even' align='center'>{$formatted_date}</td>\n
        <td class='even' align='center'><strong><a href='admin_votedata.php?op=delvotes&amp;topic_id={$topic_id}&amp;rid={$ratingid}'>" . newbb_displayImage('admin_delete', _DELETE) . "</a></strong></td>\n
        </tr>\n";
        }
        echo "</table>";
        //Include page navigation
        include_once XOOPS_ROOT_PATH . '/class/pagenav.php';
        $page = ($votes > 20) ? _AM_NEWBB_MINDEX_PAGE : '';
        $pagenav = new XoopsPageNav($page, 20, $start, 'start');
        echo '<div align="right" style="padding: 8px;">' . $page . '' . $pagenav->renderImageNav(4) . '</div>';
        break;
}
xoops_cp_footer();
?>