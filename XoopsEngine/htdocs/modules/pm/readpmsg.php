<?php
/**
 * Private message module
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
 * @package         pm
 * @since           2.3.0
 * @author          Jan Pedersen
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @version         $Id: readpmsg.php 2025 2008-08-31 04:16:39Z phppp $
 */

include_once "../../mainfile.php";

if ( !is_object($xoopsUser) ) {
    redirect_header(XOOPS_URL, 3, _NOPERM);
    exit();
}

$_REQUEST['op'] = empty($_REQUEST['op']) ? "in" : $_REQUEST['op'];
$msg_id = empty($_REQUEST['msg_id']) ? 0 : intval($_REQUEST['msg_id']);
$pm_handler =& xoops_getModuleHandler('message');
if ($msg_id > 0) {
	$pm =& $pm_handler->get($msg_id);
} else {
	$pm = null;
}

if (is_object($pm) && !$xoopsUser->isAdmin() && ($pm->getVar('from_userid') != $xoopsUser->getVar('uid'))
	&& ($pm->getVar('to_userid') != $xoopsUser->getVar('uid'))
){
    redirect_header(XOOPS_URL . '/modules/' . $xoopsModule->getVar("dirname", "n") . '/index.php', 2, _NOPERM);
    exit();
}

if (is_object($pm) && !empty($_POST['action']) ) {
    if (!$GLOBALS['xoopsSecurity']->check()) {
        echo implode('<br />', $GLOBALS['xoopsSecurity']->getErrors());
        exit();
    }
    $res = false;
	if (!empty($_REQUEST['email_message'])) {
   		$res = $pm_handler->sendEmail($pm, $xoopsUser);
  	} elseif (
  		!empty($_REQUEST['move_message']) 
  		&& $_REQUEST['op']!='save' 
  		&& !$xoopsUser->isAdmin()
  		&& $pm_handler->getSavecount() >= $xoopsModuleConfig['max_save']
  		) {
		$res_message = sprintf(_PM_SAVED_PART, $xoopsModuleConfig['max_save'], 0);
  	} else {
        switch ($_REQUEST['op']) {
			case 'out':
		         if ($pm->getVar('from_userid') != $xoopsUser->getVar('uid')) break;
		         if (!empty($_REQUEST['delete_message'])) {
		         	$res = $pm_handler->setFromdelete($pm);
		         } elseif (!empty($_REQUEST['move_message'])) {
		         	$res = $pm_handler->setFromsave($pm); 
		         }
		         break;
			case 'save':
		         if ($pm->getVar('to_userid') == $xoopsUser->getVar('uid')) {
			         if (!empty($_REQUEST['delete_message'])) {
			         	$res1 = $pm_handler->setTodelete($pm); 
			         	$res1 = ($res1) ? $pm_handler->setTosave($pm, 0) : false; 
			         } elseif (!empty($_REQUEST['move_message'])) {
			         	$res1 = $pm_handler->setTosave($pm, 0); 
			         }
		         }
		         if ($pm->getVar('from_userid') == $xoopsUser->getVar('uid')) {
			         if (!empty($_REQUEST['delete_message'])) {
			         	$res2 = $pm_handler->setFromDelete($pm); 
			         	$res2 = ($res2) ? $pm_handler->setFromsave($pm, 0) : false; 
			         } elseif (!empty($_REQUEST['move_message'])) {
			         	$res2 = $pm_handler->setFromsave($pm, 0); 
			         }
		         }
		         $res = $res1 && $res2; 
		         break;
		         
			case 'in':
			default:
		         if ($pm->getVar('to_userid') != $xoopsUser->getVar('uid')) break;
		         if (!empty($_REQUEST['delete_message'])) {
		         	$res = $pm_handler->setTodelete($pm);
		         } elseif (!empty($_REQUEST['move_message'])) {
		         	$res = $pm_handler->setTosave($pm); 
		         }
		         break;
		}
	}
    $res_message = isset($res_message) ? $res_message : ( ($res) ? _PM_ACTION_DONE : _PM_ACTION_ERROR );
    redirect_header('viewpmsg.php?op=' . htmlspecialchars( $_REQUEST['op'] ) , 2, $res_message);
}
$start = !empty($_GET['start']) ? intval($_GET['start']) : 0;
$total_messages = !empty($_GET['total_messages']) ? intval($_GET['total_messages']) : 0;
$xoopsOption['template_main'] = "pm_readpmsg.html";
include XOOPS_ROOT_PATH . '/header.php';

if (!is_object($pm)) {
    if ($_REQUEST['op'] == "out") {
        $criteria = new CriteriaCompo(new Criteria('from_delete', 0));
        $criteria->add(new Criteria('from_userid', $xoopsUser->getVar('uid')));
        $criteria->add(new Criteria('from_save', 0));
    } elseif ($_REQUEST['op'] == "save") {
        $crit_to = new CriteriaCompo(new Criteria('to_delete', 0));
        $crit_to->add(new Criteria('to_save', 1));
        $crit_to->add(new Criteria('to_userid',$xoopsUser->getVar('uid')));
        $crit_from = new CriteriaCompo(new Criteria('from_delete', 0));
        $crit_from->add(new Criteria('from_save', 1));
        $crit_from->add(new Criteria('from_userid', $xoopsUser->getVar('uid')));
        $criteria = new CriteriaCompo($crit_to);
        $criteria->add($crit_from, "OR");
    } else {
        $criteria = new CriteriaCompo(new Criteria('to_delete', 0));
        $criteria->add(new Criteria('to_userid', $xoopsUser->getVar('uid')));
        $criteria->add(new Criteria('to_save', 0));
    }

    $criteria->setLimit(1);
    $criteria->setStart($start);
    $criteria->setSort('msg_time');
    $criteria->setOrder("DESC");
    list($pm) = $pm_handler->getObjects($criteria);
}

include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";

$pmform = new XoopsForm('', 'pmform', 'readpmsg.php', 'post', true);
if ($pm->getVar('from_userid') != $xoopsUser->getVar('uid')) {
    $reply_button = new XoopsFormButton('', 'send', _PM_REPLY);
    $reply_button->setExtra("onclick='javascript:openWithSelfMain(\"" . XOOPS_URL . "/modules/pm/pmlite.php?reply=1&amp;msg_id={$msg_id}\", \"pmlite\", 550, 450);'");
	$pmform->addElement($reply_button);
}
$pmform->addElement(new XoopsFormButton('', 'delete_message', _PM_DELETE, 'submit'));
$pmform->addElement(new XoopsFormButton('', 'move_message', ($_REQUEST['op'] == 'save') ? _PM_UNSAVE : _PM_TOSAVE, 'submit'));
$pmform->addElement(new XoopsFormButton('', 'email_message', _PM_EMAIL, 'submit'));
$pmform->addElement(new XoopsFormHidden('msg_id', $pm->getVar("msg_id")));
$pmform->addElement(new XoopsFormHidden('op', $_REQUEST['op']));
$pmform->addElement(new XoopsFormHidden('action', 1));
$pmform->assign($xoopsTpl);

if ($pm->getVar("from_userid") == $xoopsUser->getVar("uid")) {
    $poster = new XoopsUser($pm->getVar("to_userid"));
} else {
    $poster = new XoopsUser($pm->getVar("from_userid"));
}
if (!is_object($poster)) {
    $xoopsTpl->assign('poster', false);
    $xoopsTpl->assign('anonymous', $xoopsConfig['anonymous']);
} else {
    $xoopsTpl->assign('poster', $poster);
}

if ($pm->getVar("to_userid") == $xoopsUser->getVar("uid") && $pm->getVar('read_msg') == 0) {
    $pm_handler->setRead($pm);
}

$message = $pm->getValues();
$message['msg_time'] = formatTimestamp($pm->getVar("msg_time"));
$xoopsTpl->assign('message', $message);
$xoopsTpl->assign('op', $_REQUEST['op']);
$xoopsTpl->assign('previous', $start-1);
$xoopsTpl->assign('next', $start+1);
$xoopsTpl->assign('total_messages', $total_messages);

include XOOPS_ROOT_PATH."/footer.php";
?>