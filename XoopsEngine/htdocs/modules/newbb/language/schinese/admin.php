<?php
// $Id: admin.php 2171 2008-09-23 13:43:42Z phppp $
//%%%%%%	File Name  index.php   	%%%%%
define("_AM_NEWBB_FORUMCONF", "����������");
define("_AM_NEWBB_ADDAFORUM", "�½�������");
define("_AM_NEWBB_SYNCFORUM", "����������ͬ��");
define("_AM_NEWBB_REORDERFORUM", "��������������");
define("_AM_NEWBB_FORUM_MANAGER", "����������");
define("_AM_NEWBB_PRUNE_TITLE", "����ѡ��");
define("_AM_NEWBB_CATADMIN", "������");
define("_AM_NEWBB_GENERALSET", "ģ������" );
define("_AM_NEWBB_MODULEADMIN", "ģ�����");
define("_AM_NEWBB_HELP", "����");
define("_AM_NEWBB_ABOUT", "����");
define("_AM_NEWBB_BOARDSUMMARY", "��������Ϣͳ��");
define("_AM_NEWBB_PENDING_POSTS_FOR_AUTH", "�ȴ���˵�����");
define("_AM_NEWBB_POSTID", "ID");
define("_AM_NEWBB_POSTDATE", "��������");
define("_AM_NEWBB_POSTER", "������");
define("_AM_NEWBB_TOPICS", "����");
define("_AM_NEWBB_SHORTSUMMARY", "��̳ͳ��");
define("_AM_NEWBB_TOTALPOSTS", "��������");
define("_AM_NEWBB_TOTALTOPICS", "��������");
define("_AM_NEWBB_TOTALVIEWS", "��������");
define("_AM_NEWBB_BLOCKS", "��̳��ʾ����");
define("_AM_NEWBB_SUBJECT", "����");
define("_AM_NEWBB_APPROVE", "���");
define("_AM_NEWBB_APPROVETEXT", "�������");
define("_AM_NEWBB_POSTAPPROVED", "�������ͨ��");
define("_AM_NEWBB_POSTNOTAPPROVED", "�������δͨ��");
define("_AM_NEWBB_POSTSAVED", "�����Ѿ�����");
define("_AM_NEWBB_POSTNOTSAVED", "�����޷�����");

define("_AM_NEWBB_TOPICAPPROVED", "�������ͨ��");
define("_AM_NEWBB_TOPICNOTAPPROVED", "�������δͨ��");
define("_AM_NEWBB_TOPICID", "������");
define("_AM_NEWBB_ORPHAN_TOPICS_FOR_AUTH", "�������©������");


define('_AM_NEWBB_DEL_ONE','ֻɾ������');
define('_AM_NEWBB_POSTSDELETED','����ѡ������Ѿ���ɾ����');
define('_AM_NEWBB_NOAPPROVEPOST','��ʱû�����ӵȴ���ˡ�');
define('_AM_NEWBB_SUBJECTC','���⣺');
define('_AM_NEWBB_MESSAGEICON','ͼ�꣺');
define('_AM_NEWBB_MESSAGEC','���ݣ�');
define('_AM_NEWBB_CANCELPOST','ȡ������');
define('_AM_NEWBB_GOTOMOD','ģ����ҳ');

define('_AM_NEWBB_PREFERENCES','ģ�����ú˲�');
define('_AM_NEWBB_POLLMODULE','ͶƱģ��');
define('_AM_NEWBB_POLL_OK','����ʹ��');
define('_AM_NEWBB_GDLIB1','GD1 ͼ�ο�');
define('_AM_NEWBB_GDLIB2','GD2 ͼ�ο�:');
define('_AM_NEWBB_AUTODETECTED','�Զ����: ');
define('_AM_NEWBB_AVAILABLE','��Ч');
define('_AM_NEWBB_NOTAVAILABLE','<font color="red">����ʹ��</font>');
define('_AM_NEWBB_NOTWRITABLE','<font color="red">����д</font>');
define('_AM_NEWBB_IMAGEMAGICK','ImageMagicK');
define('_AM_NEWBB_IMAGEMAGICK_NOTSET','δ����');
define('_AM_NEWBB_ATTACHPATH','����·��');
define('_AM_NEWBB_THUMBPATH','��������ͼ·��');
define('_AM_NEWBB_RSSPATH','RSS�ļ�·��');
define('_AM_NEWBB_REPORT','�ٱ�');
define('_AM_NEWBB_REPORT_PENDING','�ȴ�����ľٱ�');
define('_AM_NEWBB_REPORT_PROCESSED','�Ѵ���ľٱ�');

define('_AM_NEWBB_CREATETHEDIR','����');
define('_AM_NEWBB_SETMPERM','����Ȩ��');
define('_AM_NEWBB_DIRCREATED','Ŀ¼�Ѿ�����');
define('_AM_NEWBB_DIRNOTCREATED','Ŀ¼�޷�����');
define('_AM_NEWBB_PERMSET','Ȩ��������');
define('_AM_NEWBB_PERMNOTSET','Ȩ���޷�����');

define('_AM_NEWBB_DIGEST','������ժ֪ͨ');
define('_AM_NEWBB_DIGEST_PAST','<font color="red">Ӧ���� %d ����ǰ����</font>');
define('_AM_NEWBB_DIGEST_NEXT','��Ҫ�� %d ���Ӻ���');
define('_AM_NEWBB_DIGEST_ARCHIVE','��ժ�浵');
define('_AM_NEWBB_DIGEST_SENT','��ժ�ѷ���');
define('_AM_NEWBB_DIGEST_FAILED','��ժδ����');

// admin_forum_manager.php
define("_AM_NEWBB_NAME", "����");
define("_AM_NEWBB_CREATEFORUM", "���������");
define("_AM_NEWBB_EDIT", "�༭");
define("_AM_NEWBB_CLEAR", "���");
define("_AM_NEWBB_DELETE", "ɾ��");
define("_AM_NEWBB_ADD", "���");
define("_AM_NEWBB_MOVE", "�ƶ�");
define("_AM_NEWBB_ORDER", "����");
define("_AM_NEWBB_TWDAFAP", "ע�⣺�⽫��ɾ�����������ͼ���ȫ�������ݡ�<br /><br />���棺ȷ��Ҫɾ������������");
define("_AM_NEWBB_FORUMREMOVED", "������ɾ����ϡ�");
define("_AM_NEWBB_CREATENEWFORUM", "�������������");
define("_AM_NEWBB_EDITTHISFORUM", "���������ã�");
define("_AM_NEWBB_SET_FORUMORDER", "������˳��");
define("_AM_NEWBB_ALLOWPOLLS", "����ͶƱ��");
define("_AM_NEWBB_ATTACHMENT_SIZE" ,"��󸽼���ǧ�ֽ�Kb����");
define("_AM_NEWBB_ALLOWED_EXTENSIONS", "����ʹ�õ���չ����<span style='font-size: xx-small; font-weight: normal; display: block;'>'*'����û�����ơ������չ����'|'�ָ�</span>");
//define("_AM_NEWBB_ALLOW_ATTACHMENTS", "�������ϴ���");
define("_AM_NEWBB_ALLOWHTML", "����ʹ��HTML��");
define("_AM_NEWBB_YES", "��");
define("_AM_NEWBB_NO", "��");
define("_AM_NEWBB_ALLOWSIGNATURES", "����ʹ��ǩ������");
define("_AM_NEWBB_HOTTOPICTHRESHOLD", "�����������ޣ�");
define("_AM_NEWBB_POSTPERPAGE", "ÿҳ��ʾ��������<span style='font-size: xx-small; font-weight: normal; display: block;'>��ÿҳ��ʾ��������Ŀ��</span>");
define("_AM_NEWBB_TOPICPERFORUM", "ÿҳ��ʾ��������<span style='font-size: xx-small; font-weight: normal; display: block;'>��ÿҳ��ʾ��������Ŀ��</span>");
define("_AM_NEWBB_SHOWNAME", "��ʾ��ʵ������");
define("_AM_NEWBB_SHOWICONSPANEL", "��ʾͼ�꣺");
define("_AM_NEWBB_SHOWSMILIESPANEL", "��ʾ�������");
define("_AM_NEWBB_MODERATOR_REMOVE", "ɾ�����а���");
define("_AM_NEWBB_MODERATOR_ADD", "��Ӱ���");

// admin_cat_manager.php

define("_AM_NEWBB_SETCATEGORYORDER", "���˳��");
define("_AM_NEWBB_ACTIVE", "��Ч");
define("_AM_NEWBB_INACTIVE", "δ����");
define("_AM_NEWBB_STATE", "״̬");
define("_AM_NEWBB_CATEGORYDESC", "���˵����");
define("_AM_NEWBB_SHOWDESC", "��ʾ˵����");
define("_AM_NEWBB_IMAGE", "ͼ�꣺");
//define("_AM_NEWBB_SPONSORIMAGE", "������logo��");
define("_AM_NEWBB_SPONSORLINK", "��������ַ��");
define("_AM_NEWBB_DELCAT", "ɾ�����");
define("_AM_NEWBB_WAYSYWTDTTAL", "ע�⣺����ɾ��������ȫ���������������ڱ༭�������ڲ�����<br /><br />���棺ȷ��ɾ�������");



//%%%%%%        File Name  admin_forums.php           %%%%%
define("_AM_NEWBB_FORUMNAME", "���������ƣ�");
define("_AM_NEWBB_FORUMDESCRIPTION", "������������");
define("_AM_NEWBB_MODERATOR", "�������ã�");
define("_AM_NEWBB_REMOVE", "ɾ��");
define("_AM_NEWBB_CATEGORY", "������ƣ�");
define("_AM_NEWBB_DATABASEERROR", "���ݿ����");
define("_AM_NEWBB_CATEGORYUPDATED", "��̳��������ϡ�");
define("_AM_NEWBB_EDITCATEGORY", "�༭��̳���");
define("_AM_NEWBB_CATEGORYTITLE", "�����⣺");
define("_AM_NEWBB_CATEGORYCREATED", "��̳��𴴽���ϡ�");
define("_AM_NEWBB_CREATENEWCATEGORY", "�������̳���");
define("_AM_NEWBB_FORUMCREATED", "�����������ɹ���");
define("_AM_NEWBB_ACCESSLEVEL", "����Ȩ�ޣ�");
define("_AM_NEWBB_CATEGORY1", "�������");
define("_AM_NEWBB_FORUMUPDATE", "��̳���ñ�����");
define("_AM_NEWBB_FORUM_ERROR", "������̳���ô���");
define("_AM_NEWBB_CLICKBELOWSYNC", "�������������������ť�����������ݿ⡣");
define("_AM_NEWBB_SYNCHING", "���ڽ�������ͬ������");
define("_AM_NEWBB_CATEGORYDELETED", "���ɾ�����");
define("_AM_NEWBB_MOVE2CAT", "Ŀ�����");
define("_AM_NEWBB_MAKE_SUBFORUM_OF", "��������������");
define("_AM_NEWBB_MSG_FORUM_MOVED", "��̳�Ѿ��ƶ��ɹ���");
define("_AM_NEWBB_MSG_ERR_FORUM_MOVED", "�ƶ���̳ʧ�ܡ�");
define("_AM_NEWBB_SELECT", "< ѡ����� >");
define("_AM_NEWBB_MOVETHISFORUM", "ת��������");
define("_AM_NEWBB_MERGE", "�ϲ�");
define("_AM_NEWBB_MERGETHISFORUM", "�ϲ���������");
define("_AM_NEWBB_MERGETO_FORUM", "�ϲ�����");
define("_AM_NEWBB_MSG_FORUM_MERGED", "�ϲ��ɹ���");
define("_AM_NEWBB_MSG_ERR_FORUM_MERGED", "�ϲ�ʧ�ܡ�");

//%%%%%% File Name admin_forum_reorder.php %%%%%
define("_AM_NEWBB_REORDERID", "���");
define("_AM_NEWBB_REORDERTITLE", "����");
define("_AM_NEWBB_REORDERWEIGHT", "����λ��");
define("_AM_NEWBB_SETFORUMORDER", "������̳˳��");
define("_AM_NEWBB_BOARDREORDER", "��̳˳���Ѿ����¡�");

// forum_access.php
define("_AM_NEWBB_PERMISSIONS_TO_THIS_FORUM", "��̳Ȩ��");
define("_AM_NEWBB_CAT_ACCESS", "���Ȩ��");
define("_AM_NEWBB_CAN_ACCESS", "���Է��ʰ���");
define("_AM_NEWBB_CAN_VIEW", "���������������");
define("_AM_NEWBB_CAN_POST", "���Է���");
define("_AM_NEWBB_CAN_REPLY", "���Իظ�");
define("_AM_NEWBB_CAN_EDIT", "���Ա༭�Լ�������");
define("_AM_NEWBB_CAN_DELETE", "����ɾ���Լ�������");
define("_AM_NEWBB_CAN_ADDPOLL", "���Է���ͶƱ");
define("_AM_NEWBB_CAN_VOTE", "����ͶƱ");
define("_AM_NEWBB_CAN_ATTACH", "�����ϴ�����");
define("_AM_NEWBB_CAN_NOAPPROVE", "���Բ������ֱ�ӷ���");
define("_AM_NEWBB_CAN_TYPE", "����ʹ���������");
define("_AM_NEWBB_CAN_HTML", "����ʹ��HTML�﷨");
define("_AM_NEWBB_CAN_SIGNATURE", "����ʹ��ǩ����");

define("_AM_NEWBB_ACTION", "����");

define("_AM_NEWBB_PERM_TEMPLATE", "����ȱʡȨ��ģ��");
define("_AM_NEWBB_PERM_TEMPLATE_DESC", "�༭����Ȩ��ģ��Ӷ����Է����Ӧ�õ�ĳ��/Щ��̳");
define("_AM_NEWBB_PERM_FORUMS", "ѡ����̳");
define("_AM_NEWBB_PERM_TEMPLATE_CREATED", "Ȩ��ģ��������");
define("_AM_NEWBB_PERM_TEMPLATE_ERROR", "����Ȩ��ģ��ʱ��������");
define("_AM_NEWBB_PERM_TEMPLATEAPP", "Ӧ��ȱʡȨ��ģ��");
define("_AM_NEWBB_PERM_TEMPLATE_APPLIED", "��ѡ�����̳�Ѿ�����ȱʡȨ��");
define("_AM_NEWBB_PERM_ACTION", "Ȩ�޹�����");
define("_AM_NEWBB_PERM_SETBYGROUP", "ֱ�Ӱ�Ⱥ������Ȩ��");

// admin_forum_prune.php

define ("_AM_NEWBB_PRUNE_RESULTS_TITLE", "������");
define ("_AM_NEWBB_PRUNE_RESULTS_TOPICS", "��������");
define ("_AM_NEWBB_PRUNE_RESULTS_POSTS", "��������");
define ("_AM_NEWBB_PRUNE_RESULTS_FORUMS", "������̳");
define ("_AM_NEWBB_PRUNE_STORE", "����������ӱ��浽");
define ("_AM_NEWBB_PRUNE_ARCHIVE", "��������Ӵ浵");
define ("_AM_NEWBB_PRUNE_FORUMSELERROR", "δѡ��Ҫ�������̳");

define ("_AM_NEWBB_PRUNE_DAYS", "ɾ����ʱ�����û�лظ�������");
define ("_AM_NEWBB_PRUNE_FORUMS", "���������̳");
define ("_AM_NEWBB_PRUNE_STICKY", "�����ö���");
define ("_AM_NEWBB_PRUNE_DIGEST", "����������");
define ("_AM_NEWBB_PRUNE_LOCK", "����������");
define ("_AM_NEWBB_PRUNE_HOT", "�����ظ���������������");
define ("_AM_NEWBB_PRUNE_SUBMIT", "ȷ��");
define ("_AM_NEWBB_PRUNE_RESET", "���");
define ("_AM_NEWBB_PRUNE_YES", "��");
define ("_AM_NEWBB_PRUNE_NO", "��");
define ("_AM_NEWBB_PRUNE_WEEK", "һ��");
define ("_AM_NEWBB_PRUNE_2WEEKS", "����");
define ("_AM_NEWBB_PRUNE_MONTH", "һ����");
define ("_AM_NEWBB_PRUNE_2MONTH", "������");
define ("_AM_NEWBB_PRUNE_4MONTH", "�ĸ���");
define ("_AM_NEWBB_PRUNE_YEAR", "һ��");
define ("_AM_NEWBB_PRUNE_2YEARS", "����");

// About.php constants
define('_AM_NEWBB_AUTHOR_INFO', "��������");
define('_AM_NEWBB_AUTHOR_NAME', "����");
define('_AM_NEWBB_AUTHOR_WEBSITE', "��ҳ");
define('_AM_NEWBB_AUTHOR_EMAIL', "EMAIL");
define('_AM_NEWBB_AUTHOR_CREDITS', "��л");
define('_AM_NEWBB_MODULE_INFO', "ģ����Ϣ");
define('_AM_NEWBB_MODULE_STATUS', "����״̬");
define('_AM_NEWBB_MODULE_DEMO', "��ʾվ��");
define('_AM_NEWBB_MODULE_SUPPORT', "�ٷ�֧����վ");
define('_AM_NEWBB_MODULE_BUG', "���ⱨ��");
define('_AM_NEWBB_MODULE_FEATURE', "�¹��ܽ���");
define('_AM_NEWBB_MODULE_DISCLAIMER', "��Ȩ����");
define('_AM_NEWBB_AUTHOR_WORD', "���ߵĻ�");
define('_AM_NEWBB_BY','�����ߣ�');
define('_AM_NEWBB_AUTHOR_WORD_EXTRA', "
<br /><br />
�������İ���<a href='http://xoops.org.cn' target='_blank'>Xoops China Support Team</a>�ṩ֧��:<br />
ͼ������:<br />
----imhsy (http://hsyong.com)<br />
����:<br />
----lab<br />
----adi (http://sibu.org)<br />
----karuna (http://ppfans.com)<br />
----insraq (http://insraq.xoops.cn)<br />
ʹ�á�����˵��:<br />
----insraq (http://insraq.xoops.cn)<br />
----laelia<br /><br />
�������İ���<a href='http://cyai.net' target='_blank'>CHIA</a>����:<br />
----chia (http://cyai.net) [����]<br /><br />
�������ʲô����, ����Ҫ�����bug���棬�����<br />
----Xoops China Supp:: <a href='http://xoops.org.cn' target='_blank'>http://xoops.org.cn</a><br />
");

// admin_report.php
define("_AM_NEWBB_REPORTADMIN", "�ٱ�����");
define("_AM_NEWBB_PROCESSEDREPORT", "�鿴�Ѿ�����ľٱ�");
define("_AM_NEWBB_PROCESSREPORT", "����ٱ�");
define("_AM_NEWBB_REPORTTITLE", "�ٱ�����");
define("_AM_NEWBB_REPORTEXTRA", "������Ϣ");
define("_AM_NEWBB_REPORTPOST", "���ٱ�������");
define("_AM_NEWBB_REPORTTEXT", "�ٱ�����");
define("_AM_NEWBB_REPORTMEMO", "������¼");

// admin_report.php
define("_AM_NEWBB_DIGESTADMIN", "������ժ����");
define("_AM_NEWBB_DIGESTCONTENT", "��ժ����");

// admin_votedata.php
define("_AM_NEWBB_VOTE_RATINGINFOMATION", "������Ϣ");
define("_AM_NEWBB_VOTE_TOTALVOTES", "�ܴ�����");
define("_AM_NEWBB_VOTE_REGUSERVOTES", "ע���û����ִ�����%s");
define("_AM_NEWBB_VOTE_ANONUSERVOTES", "�ο����ִ�����%s");
define("_AM_NEWBB_VOTE_USER", "�û�");
define("_AM_NEWBB_VOTE_IP", "IP ��ַ");
define("_AM_NEWBB_VOTE_USERAVG", "�û�ƽ������");
define("_AM_NEWBB_VOTE_TOTALRATE", "�����ִ���");
define("_AM_NEWBB_VOTE_DATE", "���ύ");
define("_AM_NEWBB_VOTE_RATING", "����");
define("_AM_NEWBB_VOTE_NOREGVOTES", "û��ע���û�����");
define("_AM_NEWBB_VOTE_NOUNREGVOTES", "û���ο�����");
define("_AM_NEWBB_VOTEDELETED", "���������ѱ��档");
define("_AM_NEWBB_VOTE_ID", "ID");
define("_AM_NEWBB_VOTE_FILETITLE", "��������");
define("_AM_NEWBB_VOTE_DISPLAYVOTES", "������Ϣ");
define("_AM_NEWBB_VOTE_NOVOTES", "���û����ֿ���ʾ");
define("_AM_NEWBB_VOTE_DELETE", "ɾ������");
define("_AM_NEWBB_VOTE_DELETEDSC", "<strong>ɾ��</strong>ѡ�е�������Ϣ��");

// admin_type_manager.php
define("_AM_NEWBB_TYPE_ADD", "��ӷ���");
define("_AM_NEWBB_TYPE_TEMPLATE", "���ģ��");
define("_AM_NEWBB_TYPE_TEMPLATE_APPLY", "Ӧ�����ģ��");
define("_AM_NEWBB_TYPE_FORUM", "����̳�������");
define("_AM_NEWBB_TYPE_NAME", "�������");
define("_AM_NEWBB_TYPE_COLOR", "��ɫ");
define("_AM_NEWBB_TYPE_DESCRIPTION", "����");
define("_AM_NEWBB_TYPE_ORDER", "˳��");
define("_AM_NEWBB_TYPE_LIST", "����б�");
define("_AM_NEWBB_TODEL_TYPE", "ȷ��Ҫɾ���������[%s]��");
define("_AM_NEWBB_TYPE_EDITFORUM_DESC", "������δ���档���ύ�������ݡ�");
define("_AM_NEWBB_TYPE_ORDER_DESC", "���Ҫ����ĳ��������˳��������0��");

// admin_synchronization.php
define("_AM_NEWBB_SYNC_TYPE_FORUM", "��̳����");
define("_AM_NEWBB_SYNC_TYPE_TOPIC", "��������");
define("_AM_NEWBB_SYNC_TYPE_POST", "��������");
define("_AM_NEWBB_SYNC_TYPE_USER", "�û�����");
define("_AM_NEWBB_SYNC_TYPE_STATS", "ͳ����Ϣ");
define("_AM_NEWBB_SYNC_TYPE_MISC", "��������");

define("_AM_NEWBB_SYNC_ITEMS", "ÿ�δ������Ŀ��");
?>