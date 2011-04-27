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
 * @version         $Id: form.post.php 2170 2008-09-23 13:40:23Z phppp $
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";


$xoopsTpl->assign('lang_forum_index', sprintf(_MD_FORUMINDEX, htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES)));

$category_handler =& xoops_getmodulehandler("category");
$category_obj =& $category_handler->get($forum_obj->getVar("cat_id"), array("cat_title"));
$xoopsTpl->assign('category', array("id" => $forum_obj->getVar("cat_id"), "title" => $category_obj->getVar('cat_title')));
$xoopsTpl->assign("parentforum", $forum_handler->getParents($forum_obj));
$xoopsTpl->assign(array(
    'forum_id'      => $forum_obj->getVar('forum_id'), 
    'forum_name'    => $forum_obj->getVar('forum_name'), 
    ));

if ($topic_obj->isNew()) {
    $form_title = _MD_POSTNEW;
} elseif ($post_obj->isNew()) {
    if (empty($post_parent_obj)) {
        $post_parent_obj =& $post_handler->get($pid);
    }
    $form_title = _MD_REPLY . ": <a href=\"viewtopic.php?topic_id={$topic_id}&amp;post_id={$pid}\" rel=\"external\">" . $post_parent_obj->getVar("subject") . "</a>";
} else {
    $form_title = _EDIT . ": <a href=\"viewtopic.php?post_id={$post_id}\" rel=\"external\">" . $post_obj->getVar("subject") . "</a>";
}
$xoopsTpl->assign("form_title", $form_title);

foreach (array(
        'start',
        'topic_id',
        'post_id',
        'pid',
        'isreply',
        'isedit',
        'contents_preview'
        ) as $getint) {
    ${$getint} = isset($_GET[$getint]) ? intval($_GET[$getint]) : ( (!empty(${$getint})) ? ${$getint} : 0 );
}
foreach (array(
        'order',
        'viewmode',
        'hidden',
        'newbb_form',
        'icon',
        'op'
        ) as $getstr) {
    ${$getstr} = isset($_GET[$getstr]) ? $_GET[$getstr] : ( (!empty(${$getstr})) ? ${$getstr} : '' );
}


$topic_handler =& xoops_getmodulehandler('topic', 'newbb');
$topic_status = $topic_handler->get(@$topic_id,'topic_status');

$forum_form = new XoopsThemeForm(htmlspecialchars(@$form_title), 'form_post', "post.php", 'post', true);
$forum_form->setExtra('enctype="multipart/form-data"');

$uid = (is_object($xoopsUser)) ? $xoopsUser->getVar('uid') : 0;
if ( newbb_isAdmin($forum_obj)
    ||
    (    $topic_handler->getPermission($forum_obj, $topic_status, 'type')
        &&
        ($topic_id == 0 || $uid == $topic_handler->get(@$topic_id, 'topic_poster'))
    )
) {
    $type_id = $topic_handler->get(@$topic_id, 'type_id');
    $type_handler =& xoops_getmodulehandler('type', 'newbb');
    $types = $type_handler->getByForum($forum_obj->getVar("forum_id"));
    if (!empty($types)) {
        $type_element = new XoopsFormRadio(_MD_NEWBB_TYPE, 'type_id', $type_id);
        $type_element->addOption(0, _NONE);
        foreach ($types as $key => $type) {
            $value = empty($type["type_color"]) ? $type["type_name"] : "<em style=\"font-style: normal; color: " . $type["type_color"] . ";\">" . $type["type_name"] . "</em>";
            $type_element->addOption($key, $value);
        }
        $forum_form->addElement($type_element);
    } else {
        $forum_form->addElement(new XoopsFormHidden('type_id', 0));
    }
}

$subject_form = new XoopsFormText(_MD_SUBJECTC, 'subject', 60, 100, $subject);
$subject_form->setExtra("tabindex='1'");
$forum_form->addElement($subject_form,true);

if (!is_object($xoopsUser)) {
    $required = !empty($xoopsModuleConfig["require_name"]);
    $forum_form->addElement(new XoopsFormText(_MD_NAMEMAIL, 'poster_name', 60, 255, ( !empty($isedit) && !empty($poster_name) ) ? $poster_name : ''), $required);
}

$icons_radio = new XoopsFormRadio(_MD_MESSAGEICON, 'icon', $icon);
$subject_icons = XoopsLists::getSubjectsList();
foreach ($subject_icons as $iconfile) {
    $icons_radio->addOption($iconfile, '<img src="' . XOOPS_URL . '/images/subject/' . $iconfile . '" alt="" />');
}
$forum_form->addElement($icons_radio);

$nohtml = !$topic_handler->getPermission($forum_obj, $topic_status, 'html');

if (count( @$xoopsModuleConfig["editor_allowed"] ) == 1) {
    $editor = $xoopsModuleConfig["editor_allowed"][0];
} else {
    if (!empty($editor)) {
        newbb_setcookie("editor",$editor);
    } elseif (!$editor = newbb_getcookie("editor")) {
        if (is_object($xoopsUser)) {
            $editor =@ $xoopsUser->getVar("editor"); // Need set through user profile
        }
        if (empty($editor)) {
            $editor =@ $xoopsModuleConfig["editor_default"];
        }
    }
    $forum_form->addElement(new XoopsFormSelectEditor($forum_form, "editor", $editor, $nohtml, @$xoopsModuleConfig["editor_allowed"]));
}

$editor_configs = array();
$editor_configs["name"] = "message";
$editor_configs["value"] = $message;
$editor_configs["rows"] = empty($xoopsModuleConfig["editor_rows"]) ? 35 : $xoopsModuleConfig["editor_rows"];
$editor_configs["cols"] = empty($xoopsModuleConfig["editor_cols"]) ? 60 : $xoopsModuleConfig["editor_cols"];
$editor_configs["width"] = empty($xoopsModuleConfig["editor_width"]) ? "100%" : $xoopsModuleConfig["editor_width"];
$editor_configs["height"] = empty($xoopsModuleConfig["editor_height"]) ? "400px" : $xoopsModuleConfig["editor_height"];
$forum_form->addElement(new XoopsFormEditor(_MD_MESSAGEC, $editor, $editor_configs, $nohtml, $onfailure = null ), true);

if (!empty($xoopsModuleConfig['do_tag']) && (empty($post_obj) || $post_obj->isTopic())) {
    $topic_tags = "";
    if (!empty($_POST["topic_tags"])) {
        $topic_tags = $myts->htmlSpecialChars($myts->stripSlashesGPC($_POST["topic_tags"]));
    } elseif (!empty($topic_id)) {
        $topic_tags = $topic_handler->get($topic_id,'topic_tags');
    }
    if (@include_once XOOPS_ROOT_PATH . "/modules/tag/include/formtag.php") {
        $forum_form->addElement(new XoopsFormTag("topic_tags", 60, 255, $topic_tags));
    }
}

$options_tray = new XoopsFormElementTray(_MD_OPTIONS, '<br />');
if (is_object($xoopsUser) && $xoopsModuleConfig['allow_user_anonymous'] == 1) {
    $noname = (!empty($isedit) && is_object($post_obj) && $post_obj->getVar('uid') == 0) ? 1 : 0;
    $noname_checkbox = new XoopsFormCheckBox('', 'noname', $noname);
    $noname_checkbox->addOption(1, _MD_POSTANONLY);
    $options_tray->addElement($noname_checkbox);
}

if (!$nohtml) {
    $html_checkbox = new XoopsFormCheckBox('', 'dohtml', $dohtml);
    $html_checkbox->addOption(1, _MD_DOHTML);
    $options_tray->addElement($html_checkbox);
} else {
    $forum_form->addElement(new XoopsFormHidden('dohtml', 0));
}

$smiley_checkbox = new XoopsFormCheckBox('', 'dosmiley', $dosmiley);
$smiley_checkbox->addOption(1, _MD_DOSMILEY);
$options_tray->addElement($smiley_checkbox);

$xcode_checkbox = new XoopsFormCheckBox('', 'doxcode', $doxcode);
$xcode_checkbox->addOption(1, _MD_DOXCODE);
$options_tray->addElement($xcode_checkbox);

$br_checkbox = new XoopsFormCheckBox('', 'dobr', $dobr);
$br_checkbox->addOption(1, _MD_DOBR);
$options_tray->addElement($br_checkbox);

if ($topic_handler->getPermission($forum_obj, $topic_status, 'signature') && is_object($xoopsUser)) {
    $attachsig_checkbox = new XoopsFormCheckBox('', 'attachsig', $attachsig);
    $attachsig_checkbox->addOption(1, _MD_ATTACHSIG);
    $options_tray->addElement($attachsig_checkbox);
}

if ( is_object($xoopsUser) && $xoopsModuleConfig['notification_enabled']) {
    if (!empty($notify)) {
        // If 'notify' set, use that value (e.g. preview or upload)
        //$notify = 1;
    } else {
        // Otherwise, check previous subscribed status...
        $notification_handler =& xoops_gethandler('notification');
        if (!empty($topic_id) && $notification_handler->isSubscribed('thread', $topic_id, 'new_post', $xoopsModule->getVar('mid'), $xoopsUser->getVar('uid'))) {
            $notify = 1;
        } else {
            $notify = 0;
        }
    }

    $notify_checkbox = new XoopsFormCheckBox('', 'notify', $notify);
    $notify_checkbox->addOption(1, _MD_NEWPOSTNOTIFY);
    $options_tray->addElement($notify_checkbox);
}
$forum_form->addElement($options_tray);

if ($topic_handler->getPermission($forum_obj, $topic_status, 'attach')) {
    $upload_tray = new XoopsFormElementTray(_MD_ATTACHMENT);
    $upload_tray->addElement(new XoopsFormFile('', 'userfile', ''));
    $upload_tray->addElement(new XoopsFormButton('', 'contents_upload', _MD_UPLOAD, "submit"));
    $upload_tray->addElement(new XoopsFormLabel("<br /><br />" . _MD_MAX_FILESIZE . ":", $forum_obj->getVar('attach_maxkb') . "K; "));
    $extensions = trim(str_replace('|',' ',$forum_obj->getVar('attach_ext')));
    $extensions = (empty($extensions) || $extensions == "*") ? _ALL : $extensions;
    $upload_tray->addElement(new XoopsFormLabel(_MD_ALLOWED_EXTENSIONS . ":", $extensions));
    $forum_form->addElement($upload_tray);
}

if (!empty($attachments) && is_array($attachments) && count($attachments)) {
    $delete_attach_checkbox = new XoopsFormCheckBox(_MD_THIS_FILE_WAS_ATTACHED_TO_THIS_POST, 'delete_attach[]');
    foreach ($attachments as $key => $attachment) {
        $attach = _DELETE . ' <a href=' . XOOPS_URL . '/' . $xoopsModuleConfig['dir_attachments'] . '/' . $attachment['name_saved'] . ' rel="external">' . $attachment['name_display'] . '</a>';
        $delete_attach_checkbox->addOption($key, $attach);
    }
    $forum_form->addElement($delete_attach_checkbox);
    unset($delete_attach_checkbox);
}

if (!empty($attachments_tmp) && is_array($attachments_tmp) && count($attachments_tmp)) {
    $delete_attach_checkbox = new XoopsFormCheckBox(_MD_REMOVE, 'delete_tmp[]');
    $url_prefix = str_replace(XOOPS_ROOT_PATH, XOOPS_URL, XOOPS_CACHE_PATH);
    foreach ($attachments_tmp as $key => $attachment) {
        $attach = ' <a href="' . $url_prefix . '/' . $attachment[0] . '" rel="external">' . $attachment[1] . '</a>';
        $delete_attach_checkbox->addOption($key, $attach);
    }
    $forum_form->addElement($delete_attach_checkbox);
    unset($delete_attach_checkbox);
    $attachments_tmp =  base64_encode(serialize($attachments_tmp));
    $forum_form->addElement(new XoopsFormHidden('attachments_tmp', $attachments_tmp));
}

if ($xoopsModuleConfig['enable_karma'] || $xoopsModuleConfig['allow_require_reply']) {
    $view_require = ($require_reply) ? 'require_reply' : ( ($post_karma) ? 'require_karma' : 'require_null' );
    $radiobox = new XoopsFormRadio( _MD_VIEW_REQUIRE, 'view_require', $view_require );
    if ($xoopsModuleConfig['allow_require_reply']) {
        $radiobox->addOption( 'require_reply', _MD_REQUIRE_REPLY);
    }
    if ($xoopsModuleConfig['enable_karma']) {
        $karmas = array_map("trim", explode(',', $xoopsModuleConfig['karma_options']));
        if (count($karmas)>1) {
            foreach ($karmas as $karma) {
                $karma_array[strval($karma)] = intval($karma);
            }
            $karma_select = new XoopsFormSelect('', "post_karma", $post_karma);
            $karma_select->addOptionArray($karma_array);
            $radiobox->addOption( 'require_karma', _MD_REQUIRE_KARMA. ($karma_select->render()) );
        }
    }
    $radiobox->addOption( 'require_null', _MD_REQUIRE_NULL);
}
$forum_form->addElement( $radiobox );

if (empty($uid)) {
    $forum_form->addElement( new XoopsFormCaptcha() );
}

$forum_form->addElement(new XoopsFormHidden('pid', @$pid));
$forum_form->addElement(new XoopsFormHidden('post_id', @$post_id));
$forum_form->addElement(new XoopsFormHidden('topic_id', @$topic_id));
$forum_form->addElement(new XoopsFormHidden('forum', $forum_obj->getVar('forum_id')));
$forum_form->addElement(new XoopsFormHidden('viewmode', @$viewmode));
$forum_form->addElement(new XoopsFormHidden('order', @$order));
$forum_form->addElement(new XoopsFormHidden('start', @$start));
$forum_form->addElement(new XoopsFormHidden('isreply', @$isreply));
$forum_form->addElement(new XoopsFormHidden('isedit', @$isedit));
$forum_form->addElement(new XoopsFormHidden('op', @$op));

$button_tray = new XoopsFormElementTray('');

$submit_button = new XoopsFormButton('', 'contents_submit', _SUBMIT, "submit");
$submit_button->setExtra("tabindex='3'");

$cancel_button = new XoopsFormButton('', 'cancel', _CANCEL, 'button');
if ( !empty($topic_id) ) {
    $extra = "viewtopic.php?topic_id=" . intval($topic_id);
} else {
    $extra = "viewforum.php?forum=" . $forum_obj->getVar('forum_id');
}
$cancel_button->setExtra("onclick='location=\"" . $extra . "\"'");
$cancel_button->setExtra("tabindex='6'");

if ( !empty($isreply) && !empty($hidden) ) {
    $forum_form->addElement(new XoopsFormHidden('hidden', $hidden));

    $quote_button = new XoopsFormButton('', 'quote', _MD_QUOTE, 'button');
    $quote_button->setExtra("onclick='xoopsGetElementById(\"message\").value=xoopsGetElementById(\"message\").value+ xoopsGetElementById(\"hidden\").value;xoopsGetElementById(\"hidden\").value=\"\";'");
    $quote_button->setExtra("tabindex='4'");
    $button_tray->addElement($quote_button);
}

$preview_button = new XoopsFormButton('', 'btn_preview', _PREVIEW, "button");
$preview_button->setExtra("tabindex='5'");
$preview_button->setExtra('onclick="window.document.forms.' . $forum_form->getName() . '.contents_preview.value=1; window.document.forms.' . $forum_form->getName() . '.submit() ;"');
$forum_form->addElement(new XoopsFormHidden('contents_preview', 0));

$button_tray->addElement($preview_button);
$button_tray->addElement($submit_button);
$button_tray->addElement($cancel_button);
$forum_form->addElement($button_tray);

//$forum_form->display();
$forum_form->assign($xoopsTpl);
?>