<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum_post.php 31012 2012-07-09 03:10:43Z liulanbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
define('NOROBOT', TRUE);

$usercheck = lib_bfd_perm::cknewuser();
if($usercheck !== true)
{
	BfdApp::display_result($usercheck);
}

require_once libfile('class/credit');
require_once libfile('function/post');
/*COMMENTS
//引入group帮助类 20121125 ep
*/
/*COMMENTS END*/
require_once libfile('function/discuzcode');
require_once libfile('function/upload');
require_once libfile('function/forum');

loadforum();


BfdApp::check_forum_password();

if(empty($_G['forum']['allowpost'])) {
    if(!$_G['forum']['postperm'] && !$_G['group']['allowpost']) {
		BfdApp::display_result('group_nopermission');
//		BfdApp::display_result('postperm_none_nopermission');
        //showmessage('postperm_none_nopermission', NULL, array(), array('login' => 1));
    } elseif($_G['forum']['postperm'] && !forumperm($_G['forum']['postperm'])) {
        //showmessagenoperm('postperm', $_G['fid'], $_G['forum']['formulaperm']);
		BfdApp::display_result('group_nopermission');
//		BfdApp::display_result('postperm');
    }
} elseif($_G['forum']['allowpost'] == -1) {
	BfdApp::display_result('group_nopermission');
//	BfdApp::display_result('post_forum_newthread_nopermission');
    //showmessage('post_forum_newthread_nopermission', NULL);
}

if(!$_G['uid'] && ($_G['setting']['need_avatar'] || $_G['setting']['need_email'] || $_G['setting']['need_friendnum'])) {
	BfdApp::display_result('group_nopermission');
	//BfdApp::display_result('postperm_login_nopermission');
    //showmessage('postperm_login_nopermission', NULL, array(), array('login' => 1));
}

lib_bfd_perm::checklowerlimit('post', 0, 1, $_G['forum']['fid']);
lib_bfd_perm::formulaperm($_G['forum']['formulaperm']);

$pid = intval(getgpc('pid'));
$fid = intval(getgpc('fid'));
$_G['fid'] = $fid;
$sortid = intval(getgpc('sortid'));
$typeid = intval(getgpc('typeid'));
$special = intval(getgpc('special'));
$special = $special > 0 && $special < 7 || $special == 127 ? intval($special) : 0;

//标题
$subject = trim(getgpc('subject'));
$subject = urldecode($subject);
//内容, censor 关键字处理
$message = getgpc('message');
$message = urldecode($message);
if( BFD_APP_CHARSET_OUTPUT != BFD_APP_CHARSET ){
    $subject = iconv( BFD_APP_CHARSET_OUTPUT, BFD_APP_CHARSET."//IGNORE", $subject );
    $message = iconv( BFD_APP_CHARSET_OUTPUT, BFD_APP_CHARSET."//IGNORE", $message );
}

$subject = BfdApp::censor($subject);
$subject = !empty($subject) ? str_replace("\t", ' ', $subject) : $subject;
$subject = dhtmlspecialchars($subject);
$message = BfdApp::censor($message);

$modnewthreads = $modnewreplies = 0;
$urloffcheck = $usesigcheck = $smileyoffcheck = $codeoffcheck = $htmloncheck = $emailcheck = '';

$policykey = '';
$postcredits = 10;
$policykey = 'post';
if($policykey) {
	$postcredits = $_G['forum'][$policykey.'credits'] ? $_G['forum'][$policykey.'credits'] : $_G['setting']['creditspolicy'][$policykey];
}

//check_allow_action('allowpost');

/*
if(empty($_G['forum']['fid']) || $_G['forum']['type'] == 'group') {
	BfdApp::display_result('forum_nonexistence');
}


if(empty($_G['forum']['allowpost'])) {
	if(!$_G['forum']['postperm'] && !$_G['group']['allowpost']) {
		BfdApp::display_result('postperm_none_nopermission');
	} elseif($_G['forum']['postperm'] && !forumperm($_G['forum']['postperm'])) {
		BfdApp::display_result('postperm');
	}
} elseif($_G['forum']['allowpost'] == -1) {
	BfdApp::display_result('post_forum_newthread_nopermission');
}

checklowerlimit('post', 0, 1, $_G['forum']['fid']);
*/

if(trim($subject) == '') {
	BfdApp::display_result('post_subject_isnull');
}

if(trim($message) == '' &&  empty($_FILES['attach'])) {
	BfdApp::display_result('post_message_isnull');
}

/*if($post_invalid = checkpost($subject, $message, ($special || $sortid))) {
	BfdApp::display_result($post_invalid);
}
*/

if(checkflood()) {
	BfdApp::display_result('post_flood_ctrl');
} elseif(checkmaxperhour('tid')) {
	BfdApp::display_result('thread_flood_ctrl_threads_per_hour');
}
$_GET['save'] = $_G['uid'] ? $_GET['save'] : 0;

$publishdate = $_G['timestamp'];

$displayorder = 0;
$digest = 0;
$readperm = 0;
$isanonymous = 0;

$price = intval($price);
$sortid = $special && $_G['forum']['threadsorts']['types'][$sortid] ? 0 : $sortid;


$author = !$isanonymous ? $_G['username'] : '';
$moderated = $digest || $displayorder > 0 ? 1 : 0;
$thread['status'] = 32;
$isgroup = $_G['forum']['status'] == 3 ? 1 : 0;


$newthread = array(
	'fid' => $_G['fid'],
	'posttableid' => 0,
	'readperm' => $readperm,
	'price' => $price,
	'typeid' => $typeid,
	'sortid' => $sortid,
	'author' => $author,
	'authorid' => $_G['uid'],
	'subject' => $subject,
	'dateline' => $publishdate,
	'lastpost' => $publishdate,
	'lastposter' => $author,
	'displayorder' => $displayorder,
	'digest' => $digest,
	'special' => $special,
	'attachment' => 0,
	'moderated' => $moderated,
	'status' => $thread['status'],
	'isgroup' => $isgroup,
	'replycredit' => $replycredit,
	'closed' => $closed ? 1 : 0
);
$tid = C::t('forum_thread')->insert($newthread, true);
useractionlog($_G['uid'], 'tid');

if(!getuserprofile('threads') && $_G['setting']['newbie']) {
	C::t('forum_thread')->update($tid, array('icon' => $_G['setting']['newbie']));
}


if(!$isanonymous) {
	C::t('common_member_field_home')->update($_G['uid'], array('recentnote'=>$subject));
}


if($moderated) {
	updatemodlog($tid, ($displayorder > 0 ? 'STK' : 'DIG'));
	updatemodworks(($displayorder > 0 ? 'STK' : 'DIG'), 1);
}


if(1 || $_G['group']['allowat']) {
	$atlist = $atlist_tmp = array();
	preg_match_all("/@([^\r\n]*?)\s/Ui", $message.' ', $atlist_tmp);
	$atlist_tmp = array_slice(array_unique($atlist_tmp[1]), 0, $_G['group']['allowat']);
	if(!empty($atlist_tmp)) {
		if(empty($_G['setting']['at_anyone'])) {
			foreach(C::t('home_follow')->fetch_all_by_uid_fusername($_G['uid'], $atlist_tmp) as $row) {
				$atlist[$row['followuid']] = $row['fusername'];
			}
			if(count($atlist) < $_G['group']['allowat']) {
				$query = C::t('home_friend')->fetch_all_by_uid_username($_G['uid'], $atlist_tmp);
				foreach($query as $row) {
					$atlist[$row['fuid']] = $row['fusername'];
				}
			}
		} else {
			foreach(C::t('common_member')->fetch_all_by_username($atlist_tmp) as $row) {
				$atlist[$row['uid']] = $row['username'];
			}
		}
	}
	if($atlist) {
		foreach($atlist as $atuid => $atusername) {
			$atsearch[] = "/@$atusername /i";
			$atreplace[] = "[url=home.php?mod=space&uid=$atuid]@{$atusername}[/url] ";
		}
		$message = preg_replace($atsearch, $atreplace, $message.' ', 1);
	}
}

//$bbcodeoff = checkbbcodes($message, !empty($_GET['bbcodeoff']));
//$smileyoff = checksmilies($message, !empty($_GET['smileyoff']));
//$parseurloff = !empty($_GET['parseurloff']);
$bbcodeoff = 0;
$smileyoff = 0;
$parseurloff = 0;

$htmlon = 0;
$htmlon = $_G['group']['allowhtml'] && !empty($_GET['htmlon']) ? 1 : 0;
$usesig = !empty($_GET['usesig']) && $_G['group']['maxsigsize'] ? 1 : 0;
$usesig = 0;

$class_tag = new tag();
$tagstr = $class_tag->add_tag($_GET['tags'], $tid, 'tid');


$pinvisible = $modnewthreads ? -2 : (empty($_GET['save']) ? 0 : -3);

$post_status = (defined('IN_MOBILE') ? 8 : 0);
$post_mobile = BfdApp::get_mobile_status();
if($post_mobile > 0 )
{
	$post_status = $post_mobile;
}

$pid = insertpost(array(
	'fid' => $_G['fid'],
	'tid' => $tid,
	'first' => '1',
	'author' => $_G['username'],
	'authorid' => $_G['uid'],
	'subject' => $subject,
	'dateline' => $publishdate,
	'message' => $message,
	'useip' => $_G['clientip'],
	'invisible' => $pinvisible,
	'anonymous' => $isanonymous,
	'usesig' => $usesig,
	'htmlon' => $htmlon,
	'bbcodeoff' => $bbcodeoff,
	'smileyoff' => $smileyoff,
	'parseurloff' => $parseurloff,
	'attachment' => '0',
	'tags' => $tagstr,
	'replycredit' => 0,
	//'status' => (defined('IN_MOBILE') ? 8 : 0)
	'status' => $post_status
));
if($_G['group']['allowat'] && $atlist) {
	foreach($atlist as $atuid => $atusername) {
		notification_add($atuid, 'at', 'at_message', array('from_id' => $tid, 'from_idtype' => 'at', 'buyerid' => $_G['uid'], 'buyer' => $_G['username'], 'tid' => $tid, 'subject' => $subject, 'pid' => $pid, 'message' => messagecutstr($message, 150)));
	}
}
$threadimageaid = 0;
$threadimage = array();
if(!empty($_FILES['attach']))
{
	$threadimageaid = bfd_app_save_attach($_FILES['attach'],$tid,$pid);
}

if(($_G['group']['allowpostattach'] || $_G['group']['allowpostimage']) && ($_GET['attachnew'] || $sortid || !empty($_GET['activityaid']))) {
	updateattach($displayorder == -4 || $modnewthreads, $tid, $pid, $_GET['attachnew']);
	if(!$threadimageaid) {
		$threadimage = C::t('forum_attachment_n')->fetch_max_image('tid:'.$tid, 'tid', $tid);
		$threadimageaid = $threadimage['aid'];
	}
}


if($threadimageaid) {
	if(!$threadimage) {
		$threadimage = C::t('forum_attachment_n')->fetch('tid:'.$tid, $threadimageaid);
	}
	$threadimage = daddslashes($threadimage);
	C::t('forum_threadimage')->insert(array(
		'tid' => $tid,
		'attachment' => $threadimage['attachment'],
		'remote' => $threadimage['remote'],
	));
}

$statarr = array(0 => 'thread', 1 => 'poll', 2 => 'trade', 3 => 'reward', 4 => 'activity', 5 => 'debate', 127 => 'thread');
include_once libfile('function/stat');
updatestat($isgroup ? 'groupthread' : $statarr[$special]);


/*COMMENTS
//在推送feed后追加推送memory缓存操作 20121125 ep
*/		
//require_once libfile('lib/group_helper');
//$newthread['tid'] = $tid;
//lib_group_helper::pushToMemoryNewThread($tid,$newthread);
/*COMMENTS END*/
/*COMMENTS
//在推送feed后追加推送follow feed all信息流操作 20121125 ep
*/
//require_once libfile('lib/followfeedall_helper');
//lib_followfeedall_helper::feed_newthread($_G['uid'],$_G['fid'],$tid,$subject,$message,$_G['username'],$_G['forum']['name'],get_groupimg($_G['forum']['icon'], 'icon'),$pid,$_G['forum']['gviewperm']);		
/*COMMENTS END*/
if($displayorder != -4) {

	if(BFD_APP_CREDITS_AWARD > 1)
	{
		$uid_arrtmp = array();
		for($i=0;$i<BFD_APP_CREDITS_AWARD;$i++)
		{
			$uid_arrtmp[] = $_G['uid'];
		}
		if($digest) {
			updatepostcredits('+',  $uid_arrtmp, 'digest', $_G['fid']);
		}
		updatepostcredits('+',  $uid_arrtmp, 'post', $_G['fid']);
	}
	else
	{
			if($digest) {
				updatepostcredits('+',  $_G['uid'], 'digest', $_G['fid']);
			}
			updatepostcredits('+',  $_G['uid'], 'post', $_G['fid']);
	}

	if($isgroup) {
		C::t('forum_groupuser')->update_counter_for_user($_G['uid'], $_G['fid'], 1);
	}

	$subject = str_replace("\t", ' ', $subject);
	$lastpost = "$tid\t".$subject."\t$_G[timestamp]\t$author";
	C::t('forum_forum')->update($_G['fid'], array('lastpost' => $lastpost));
	C::t('forum_forum')->update_forum_counter($_G['fid'], 1, 1, 1);
	if($_G['forum']['type'] == 'sub') {
		C::t('forum_forum')->update($_G['forum']['fup'], array('lastpost' => $lastpost));
	}
/*COMMENTS
//活动贴额外有加分 20121125 ep
*/			
	if($special == 4){
//		lib_group_helper::activityPub('+',$_G['uid']);
	}
/*COMMENTS END*/
}

if($_G['forum']['status'] == 3) {
	C::t('forum_forumfield')->update($_G['fid'], array('lastupdate' => TIMESTAMP));
	require_once libfile('function/grouplog');
	updategroupcreditlog($_G['fid'], $_G['uid']);
}
//end
BfdApp::display_result('post_newthread_succeed');




/**
 * 保存附件操作
 * @params $fileinfo 文件信息 
 * @params $tid 帖子id
 * @params $pid 评论id
 **/
function bfd_app_save_attach($upload,$tid,$pid)
{
	global $_G;
	$uid = $_G['uid'];
	$aid = 0;
	$attachlist = array();
	if(!is_array($upload['name']))
	{
		$attachlist[] = $upload;
	}
	else
	{
		$count = count($upload['name']);
		for($i = 0; $i < $count; $i++)
		{
			$attach = array();
			$attach['name'] = $upload['name'][$i];
			$attach['type'] = $upload['type'][$i];
			$attach['tmp_name'] = $upload['tmp_name'][$i];
			$attach['error'] = $upload['error'][$i];
			$attach['size'] = $upload['size'][$i];
			$attachlist[] = $attach;
		}
	}
	//附件 保存
	$attach = array();
	$class_upload = new discuz_upload();
	
	$picAdd = "";
	foreach($attachlist as $fileinfo)
	{
			if(!strpos($fileinfo['name'],'.'))
      {
        $fileinfo['name'] .= '.jpg';
      }
			if($class_upload->init($fileinfo,'forum'))
			{
				if($class_upload->save())
				{
					$attach = $class_upload->attach;	
				}
			}
			
			if(!empty($attach)){
				$pic = $attach['attachment'];
				$filesize = $attach['size'];
				$width = $attach['imageinfo']['width'];
				//保存附件记录 
				DB::query("INSERT INTO ".DB::table('forum_attachment')." SET tid='$tid',pid='$pid',uid='$uid',tableid='".getattachtableid($tid)."'");
				$aid = DB::insert_id();
		//更新帖子附件信息
				$picAdd .= "\n".'[attach]'.$aid.'[/attach]';
				DB::query("INSERT INTO pre_forum_attachment_".getattachtableid($tid)." SET aid='$aid',tid='$tid',pid='$pid',dateline='{$_G['timestamp']}',filename='{$fileinfo['name']}',filesize='$filesize',attachment='$pic',width='{$width}',isimage='1'");
			}
	}
	if(!empty($picAdd))
	{
		DB::query("UPDATE pre_forum_post SET attachment='2',message=CONCAT(message,'$picAdd') WHERE pid='$pid'");
		DB::query("UPDATE pre_forum_thread SET attachment='2' WHERE tid='$tid'");
	}
/*		 if($class_upload->attach['isimage']) {
            if($_G['setting']['thumbsource'] || $_G['setting']['thumbstatus']) {
                require_once libfile('class/image');
                $image = new image;
            }
            if($_G['setting']['thumbsource'] && $_G['setting']['sourcewidth'] && $_G['setting']['sourceheight']) {
                $thumb = $image->Thumb($class_upload->attach['target'], '', $_G['setting']['sourcewidth'], $_G['setting']['sourceheight'], 1, 1) ? 1 : 0;
                $width = $image->imginfo['width'];
                $class_upload->attach['size'] = $image->imginfo['size'];
            }
            if($_G['setting']['thumbstatus']) {
                $thumb = $image->Thumb($class_upload->attach['target'], '', $_G['setting']['thumbwidth'], $_G['setting']['thumbheight'], $_G['setting']['thumbstatus'], 0) ? 1 : 0;
                $width = $image->imginfo['width'];
            }
            if($_G['setting']['thumbsource'] || !$_G['setting']['thumbstatus']) {
                list($width) = @getimagesize($class_upload->attach['target']);
            }
        }
        $insert = array(
            'aid' => $aid,
            'dateline' => $_G['timestamp'],
            'filename' => dhtmlspecialchars(censor($class_upload->attach['name'])),
            'filesize' => $class_upload->attach['size'],
            'attachment' => $class_upload->attach['attachment'],
            'isimage' => $class_upload->attach['isimage'],
            'uid' => $_G['uid'],
            'thumb' => $thumb,
            'remote' => $remote,
            'width' => $width,
        );
        C::t('forum_attachment_unused')->insert($insert);
*/	
	return $aid;
}

?>
