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
require_once libfile('function/forumlist');
require_once libfile('function/forum');
/*COMMENTS
//引入group帮助类 20121125 ep
*/
/*COMMENTS END*/
require_once libfile('function/discuzcode');
require_once libfile('function/upload');

$tid  = intval(getgpc('tid'));
$pid = intval(getgpc('pid'));
$fid = intval(getgpc('fid'));
$sortid = intval(getgpc('sortid'));
$typeid = intval(getgpc('typeid'));
$special = intval(getgpc('special'));
$special = $special > 0 && $special < 7 || $special == 127 ? intval($special) : 0;

$_G['tid'] = $tid;
$_G['fid'] = $fid;

loadforum();

BfdApp::check_forum_password();

if(!$_G['uid'] && !((!$_G['forum']['replyperm'] && $_G['group']['allowreply']) || ($_G['forum']['replyperm'] && forumperm($_G['forum']['replyperm'])))) {
		BfdApp::display_result('group_nopermission');
    //showmessage('replyperm_login_nopermission', NULL, array(), array('login' => 1));
} elseif(empty($_G['forum']['allowreply'])) {
    if(!$_G['forum']['replyperm'] && !$_G['group']['allowreply']) {
		BfdApp::display_result('group_nopermission');
        //showmessage('replyperm_none_nopermission', NULL, array(), array('login' => 1));
    } elseif($_G['forum']['replyperm'] && !forumperm($_G['forum']['replyperm'])) {
		BfdApp::display_result('group_nopermission');
        //showmessagenoperm('replyperm', $_G['forum']['fid']);
    }
} elseif($_G['forum']['allowreply'] == -1) {
		BfdApp::display_result('group_nopermission');
    //showmessage('post_forum_newreply_nopermission', NULL);
}
/*
if(empty($_G['forum']['allowpost'])) {
    if(!$_G['forum']['postperm'] && !$_G['group']['allowpost']) {
		BfdApp::display_result('group_nopermission');
        //showmessage('postperm_none_nopermission', NULL, array(), array('login' => 1));
    } elseif($_G['forum']['postperm'] && !forumperm($_G['forum']['postperm'])) {
        //showmessagenoperm('postperm', $_G['fid'], $_G['forum']['formulaperm']);
		BfdApp::display_result('group_nopermission');
    }
} elseif($_G['forum']['allowpost'] == -1) {
	BfdApp::display_result('group_nopermission');
    //showmessage('post_forum_newthread_nopermission', NULL);
}
*/
if(!$_G['uid'] && ($_G['setting']['need_avatar'] || $_G['setting']['need_email'] || $_G['setting']['need_friendnum'])) {
	BfdApp::display_result('group_nopermission');
    //showmessage('postperm_login_nopermission', NULL, array(), array('login' => 1));
}

lib_bfd_perm::checklowerlimit('reply', 0, 1, $_G['forum']['fid']);
lib_bfd_perm::formulaperm($_G['forum']['formulaperm']);

$thread = C::t('forum_thread')->fetch($_G['tid']);
if(!$_G['forum_auditstatuson'] && !($thread['displayorder']>=0 || (in_array($thread['displayorder'], array(-4, -2)) && $thread['authorid']==$_G['uid']))) {
	$thread = array();
}
if(!empty($thread)) {

	if($thread['readperm'] && $thread['readperm'] > $_G['group']['readaccess'] && !$_G['forum']['ismoderator'] && $thread['authorid'] != $_G['uid']) {
		BfdApp::display_result('thread_nopermission');
	}

	$_G['fid'] = $thread['fid'];
	$special = $thread['special'];

} else {
	BfdApp::display_result('thread_nonexistence');
}

if($thread['closed'] == 1 && !$_G['forum']['ismoderator']) {
	BfdApp::display_result('post_thread_closed');
}

$_GET = array_merge($_GET,$_POST); 
$_GET['action'] = 'reply';
$subject = '';
$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';



if( BFD_APP_CHARSET_OUTPUT != BFD_APP_CHARSET ){
    $message = iconv( BFD_APP_CHARSET_OUTPUT, BFD_APP_CHARSET."//IGNORE", $message );
}
$message = BfdApp::censor($message);

$modnewthreads = $modnewreplies = 0;
$urloffcheck = $usesigcheck = $smileyoffcheck = $codeoffcheck = $htmloncheck = $emailcheck = '';


$policykey = '';
$postcredits = 10;

if($_GET['action'] == 'newthread') {
	$policykey = 'post';
} elseif($_GET['action'] == 'reply') {
	$policykey = 'reply';
}
if($policykey) {
	$postcredits = $_G['forum'][$policykey.'credits'] ? $_G['forum'][$policykey.'credits'] : $_G['setting']['creditspolicy'][$policykey];
}


$isfirstpost = 0;
$showthreadsorts = 0;
$quotemessage = '';



/*if($_G['setting']['commentnumber'] && !empty($_GET['comment'])) {
	if(!submitcheck('commentsubmit', 0, $seccodecheck, $secqaacheck)) {
		showmessage('submitcheck_error', NULL);
	}
	$post = C::t('forum_post')->fetch('tid:'.$_G['tid'], $_GET['pid']);
	if(!$post) {
		showmessage('post_nonexistence', NULL);
	}
	if($thread['closed'] && !$_G['forum']['ismoderator'] && !$thread['isgroup']) {
		showmessage('post_thread_closed');
	} elseif(!$thread['isgroup'] && $post_autoclose = checkautoclose($thread)) {
		showmessage($post_autoclose, '', array('autoclose' => $_G['forum']['autoclose']));
	} elseif(checkflood()) {
		showmessage('post_flood_ctrl', '', array('floodctrl' => $_G['setting']['floodctrl']));
	} elseif(checkmaxperhour('pid')) {
		showmessage('post_flood_ctrl_posts_per_hour', '', array('posts_per_hour' => $_G['group']['maxpostsperhour']));
	}
	$commentscore = '';
	if(!empty($_GET['commentitem']) && !empty($_G['uid']) && $post['authorid'] != $_G['uid']) {
		foreach($_GET['commentitem'] as $itemk => $itemv) {
			if($itemv !== '') {
				$commentscore .= strip_tags(trim($itemk)).': <i>'.intval($itemv).'</i> ';
			}
		}
	}
	$comment = cutstr(($commentscore ? $commentscore.'<br />' : '').censor(trim(dhtmlspecialchars($_GET['message'])), '***'), 200, ' ');
	if(!$comment) {
		showmessage('post_sm_isnull');
	}
	C::t('forum_postcomment')->insert(array(
		'tid' => $post['tid'],
		'pid' => $post['pid'],
		'author' => $_G['username'],
		'authorid' => $_G['uid'],
		'dateline' => TIMESTAMP,
		'comment' => $comment,
		'score' => $commentscore ? 1 : 0,
		'useip' => $_G['clientip'],
	));
	C::t('forum_post')->update('tid:'.$_G['tid'], $_GET['pid'], array('comment' => 1));
	!empty($_G['uid']) && updatepostcredits('+', $_G['uid'], 'reply', $_G['fid']);
	if(!empty($_G['uid']) && $_G['uid'] != $post['authorid']) {
		notification_add($post['authorid'], 'pcomment', 'comment_add', array(
			'tid' => $_G['tid'],
			'pid' => $_GET['pid'],
			'subject' => $thread['subject'],
			'from_id' => $_G['tid'],
			'from_idtype' => 'pcomment',
			'commentmsg' => cutstr(str_replace(array('[b]', '[/b]', '[/color]'), '', preg_replace("/\[color=([#\w]+?)\]/i", "", $comment)), 200)
		));
	}
	update_threadpartake($post['tid']);
	$pcid = C::t('forum_postcomment')->fetch_standpoint_by_pid($_GET['pid']);
	$pcid = $pcid['id'];
	if(!empty($_G['uid']) && $_GET['commentitem']) {
		$totalcomment = array();
		foreach(C::t('forum_postcomment')->fetch_all_by_pid_score($_GET['pid'], 1) as $comment) {
			$comment['comment'] = addslashes($comment['comment']);
			if(strexists($comment['comment'], '<br />')) {
				if(preg_match_all("/([^:]+?):\s<i>(\d+)<\/i>/", $comment['comment'], $a)) {
					foreach($a[1] as $k => $itemk) {
						$totalcomment[trim($itemk)][] = $a[2][$k];
					}
				}
			}
		}
		$totalv = '';
		foreach($totalcomment as $itemk => $itemv) {
			$totalv .= strip_tags(trim($itemk)).': <i>'.(floatval(sprintf('%1.1f', array_sum($itemv) / count($itemv)))).'</i> ';
		}

		if($pcid) {
			C::t('forum_postcomment')->update($pcid, array('comment' => $totalv, 'dateline' => TIMESTAMP + 1));
		} else {
			C::t('forum_postcomment')->insert(array(
				'tid' => $post['tid'],
				'pid' => $post['pid'],
				'author' => '',
				'authorid' => '-1',
				'dateline' => TIMESTAMP + 1,
				'comment' => $totalv
			));
		}
	}
	C::t('forum_postcache')->delete($post['pid']);
	showmessage('comment_add_succeed', "forum.php?mod=viewthread&tid=$post[tid]&pid=$post[pid]&page=$_GET[page]&extra=$extra#pid$post[pid]", array('tid' => $post['tid'], 'pid' => $post['pid']));
}
*/
/*if($special == 127) {
	$postinfo = C::t('forum_post')->fetch_threadpost_by_tid_invisible($_G['tid']);
	$sppos = strrpos($postinfo['message'], chr(0).chr(0).chr(0));
	$specialextra = substr($postinfo['message'], $sppos + 3);
}
if(getstatus($thread['status'], 3)) {
	$rushinfo = C::t('forum_threadrush')->fetch($_G['tid']);
	if($rushinfo['creditlimit'] != -996) {
		$checkcreditsvalue = $_G['setting']['creditstransextra'][11] ? getuserprofile('extcredits'.$_G['setting']['creditstransextra'][11]) : $_G['member']['credits'];
		if($checkcreditsvalue < $rushinfo['creditlimit']) {
			$creditlimit_title = $_G['setting']['creditstransextra'][11] ? $_G['setting']['extcredits'][$_G['setting']['creditstransextra'][11]]['title'] : lang('forum/misc', 'credit_total');
			showmessage('post_rushreply_creditlimit', '', array('creditlimit_title' => $creditlimit_title, 'creditlimit' => $rushinfo['creditlimit']));
		}
	}

}
*/


$_G['group']['allowat'] = 1;

if($thread['closed'] && !$_G['forum']['ismoderator'] && !$thread['isgroup']) {
	BfdApp::display_result('post_thread_closed');
} elseif(!$thread['isgroup'] && $post_autoclose = checkautoclose($thread)) {
	BfdApp::display_result($post_autoclose);
} if(trim($subject) == '' && trim($message) == '' && $thread['special'] != 2 && empty($_FILES['attach'])) {
	BfdApp::display_result('post_sm_isnull');
} /*elseif($post_invalid = checkpost($subject, $message, $special == 2 && $_G['group']['allowposttrade'])) {
	BfdApp::display_result($post_invalid);
}*/ elseif(checkflood()) {
	BfdApp::display_result('post_flood_ctrl');
} elseif(checkmaxperhour('pid')) {
	BfdApp::display_result('post_flood_ctrl_posts_per_hour');
}

/*COMMENTS
//增加小组违禁词过滤提示 20121125 ep
*/	
/*COMMENTS END*/
$attentionon = empty($_GET['attention_add']) ? 0 : 1;
$attentionoff = empty($attention_remove) ? 0 : 1;
$heatthreadset = update_threadpartake($_G['tid'], true);
if(1 || $_G['group']['allowat']) {
	$atlist = $atlist_tmp = $ateduids = array();
	preg_match_all("/@([^\r\n]*?)\s/i", $message.' ', $atlist_tmp);
	$atlist_tmp = array_slice(array_unique($atlist_tmp[1]), 0, $_G['group']['allowat']);
	$atnum = $maxselect = 0;
	foreach(C::t('home_notification')->fetch_all_by_authorid_fromid($_G['uid'], $_G['tid'], 'at') as $row) {
		$atnum ++;
		$ateduids[$row[uid]] = $row['uid'];
	}
	$maxselect = $_G['group']['allowat'] - $atnum;
	if($maxselect > 0 && !empty($atlist_tmp)) {
		if(empty($_G['setting']['at_anyone'])) {
			foreach(C::t('home_follow')->fetch_all_by_uid_fusername($_G['uid'], $atlist_tmp) as $row) {
				if(!in_array($row['followuid'], $ateduids) && (count($atlist) <= $maxselect) ) {
					$atlist[$row['followuid']] = $row['fusername'];
				}
/*COMMENTS
//将at功能允许给已at过的人显示连接 20130123 ep
*/					
				else{
					$atlist[$row['followuid']] = $row['fusername'];
				}
//					if(count($atlist) == $maxselect) {
//						break;
//					}
/*COMMENTS END*/
			}
			if(count($atlist) < $maxselect) {
				$query = C::t('home_friend')->fetch_all_by_uid_username($_G['uid'], $atlist_tmp);
				foreach($query as $row) {
					if(!in_array($row['followuid'], $ateduids)) {
						$atlist[$row[fuid]] = $row['fusername'];
					}
				}
			}
		} else {
			foreach(C::t('common_member')->fetch_all_by_username($atlist_tmp) as $row) {
				if(!in_array($row['uid'], $ateduids) && (count($atlist) <= $maxselect) ) {
					$atlist[$row['uid']] = $row['username'];
				}
/*COMMENTS
//将at功能允许给已at过的人显示连接 20130123 ep
*/					
				else{
					$atlist[$row['uid']] = $row['username'];
				}
//					if(count($atlist) == $maxselect) {
//						break;
//					}
/*COMMENTS END*/

			}
		}
	}
/*COMMENTS
//将at功能允许给已at过的人显示连接 20130123 ep
*/	
	else if(!empty($atlist_tmp)){
		if(empty($_G['setting']['at_anyone'])) {
			foreach(C::t('home_follow')->fetch_all_by_uid_fusername($_G['uid'], $atlist_tmp) as $row) {
				$atlist[$row['followuid']] = $row['fusername'];					
			}
		}else{
			foreach(C::t('common_member')->fetch_all_by_username($atlist_tmp) as $row) {
				$atlist[$row['followuid']] = $row['fusername'];
			}
		}	
	}
/*COMMENTS END*/
	if($atlist) {
		foreach($atlist as $atuid => $atusername) {
			$atsearch[] = "/@$atusername /i";
			$atreplace[] = "[url=home.php?mod=space&uid=$atuid]@{$atusername}[/url] ";
		}
		$message = preg_replace($atsearch, $atreplace, $message.' ', 1);
	}
/*COMMENTS
//将at功能允许给已at过的人显示连接 20130123 ep
*/
//		if($atlist2) {
//			foreach($atlist2 as $atuid => $atusername) {
//				$atsearch[] = "/@$atusername /i";
//				$atreplace[] = "[url=home.php?mod=space&uid=$atuid]@{$atusername}[/url] ";
//			}
//			$message = preg_replace($atsearch, $atreplace, $message.' ', 1);
//		}
/*COMMENTS END*/
}
/*$bbcodeoff = checkbbcodes($message, !empty($_GET['bbcodeoff']));
$smileyoff = checksmilies($message, !empty($_GET['smileyoff']));
$parseurloff = !empty($_GET['parseurloff']);
$htmlon = $_G['group']['allowhtml'] && !empty($_GET['htmlon']) ? 1 : 0;
$usesig = !empty($_GET['usesig']) && $_G['group']['maxsigsize'] ? 1 : 0;
$isanonymous = $_G['group']['allowanonymous'] && !empty($_GET['isanonymous'])? 1 : 0;
$author = empty($isanonymous) ? $_G['username'] : '';
*/
$bbcodeoff = 0;
$smileyoff = 0;
$parseurloff = 0;
$htmlon = 0;
$usesig = 0;
$isanonymous = 0;
$author = $_G['username'];

if($thread['displayorder'] == -4) {
	$modnewreplies = 0;
}
$pinvisible = $modnewreplies ? -2 : ($thread['displayorder'] == -4 ? -3 : 0);
//$message = preg_replace('/\[attachimg\](\d+)\[\/attachimg\]/is', '[attach]\1[/attach]', $message);
$message = preg_replace('/<attach>(\d+)<\/attach>/is', '', $message);



$post_status = (defined('IN_MOBILE') ? 8 : 0);
$post_mobile = BfdApp::get_mobile_status();
if($post_mobile > 0 )
{
    $post_status = $post_mobile;
}


$pid = insertpost(array(
	'fid' => $_G['fid'],
	'tid' => $_G['tid'],
	'first' => '0',
	'author' => $_G['username'],
	'authorid' => $_G['uid'],
	'subject' => $subject,
	'dateline' => $_G['timestamp'],
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
	//'status' => (defined('IN_MOBILE') ? 8 : 0),
	'status' => $post_status,
));

if(!empty($_FILES['attach']))
{
	$aid = bfd_app_save_attach($_FILES['attach'],$tid,$pid);
}

if($_G['group']['allowat'] && $atlist) {

	foreach($atlist as $atuid => $atusername) {
		notification_add($atuid, 'at', 'at_message', array('from_id' => $_G['tid'], 'from_idtype' => 'at', 'buyerid' => $_G['uid'], 'buyer' => $_G['username'], 'tid' => $_G['tid'], 'subject' => $thread['subject'], 'pid' => $pid, 'message' => messagecutstr($message, 150)));
	}
}
$updatethreaddata = $heatthreadset ? $heatthreadset : array();
$postionid = C::t('forum_post')->fetch_maxposition_by_tid($thread['posttableid'], $_G['tid']);
$updatethreaddata[] = DB::field('maxposition', $postionid);
if(getstatus($thread['status'], 3) && $postionid) {
	$rushstopfloor = $rushinfo['stopfloor'];
	if($rushstopfloor > 0 && $thread['closed'] == 0 && $postionid >= $rushstopfloor) {
		$updatethreaddata[] = 'closed=1';
	}
}
useractionlog($_G['uid'], 'pid');

$nauthorid = 0;
if(!empty($_GET['noticeauthor']) && !$isanonymous && !$modnewreplies) {
	list($ac, $nauthorid) = explode('|', $_GET['noticeauthor']);
//	$ac = getgpc('noticetype');
//	$nauthorid = getgpc('nauthorid');
	if($nauthorid != $_G['uid']) {
		if($ac == 'q') {
			notification_add($nauthorid, 'post', 'reppost_noticeauthor', array(
				'tid' => $thread['tid'],
				'subject' => $thread['subject'],
				'fid' => $_G['fid'],
				'pid' => $pid,
				'from_id' => $pid,
				'from_idtype' => 'quote',
			));
		} elseif($ac == 'r') {
			notification_add($nauthorid, 'post', 'reppost_noticeauthor', array(
				'tid' => $thread['tid'],
				'subject' => $thread['subject'],
				'fid' => $_G['fid'],
				'pid' => $pid,
				'from_id' => $thread['tid'],
				'from_idtype' => 'post',
			));
		}
	}

}

if($thread['authorid'] != $_G['uid'] && getstatus($thread['status'], 6) && empty($_GET['noticeauthor']) && !$isanonymous && !$modnewreplies) {
	$thapost = C::t('forum_post')->fetch_threadpost_by_tid_invisible($_G['tid'], 0);
	notification_add($thapost['authorid'], 'post', 'reppost_noticeauthor', array(
		'tid' => $thread['tid'],
		'subject' => $thread['subject'],
		'fid' => $_G['fid'],
		'pid' => $pid,
		'from_id' => $thread['tid'],
		'from_idtype' => 'post',
	));
}
$feedid = 0;
if(helper_access::check_module('follow') && !empty($_GET['adddynamic']) && !$isanonymous) {
	require_once libfile('function/discuzcode');
	require_once libfile('function/followcode');
	$feedcontent = C::t('forum_threadpreview')->count_by_tid($thread['tid']);
	$firstpost = C::t('forum_post')->fetch_threadpost_by_tid_invisible($thread['tid']);

	if(empty($feedcontent)) {
		$feedcontent = array(
			'tid' => $thread['tid'],
			'content' => followcode($firstpost['message'], $thread['tid'], $pid, 1000),
		);
		C::t('forum_threadpreview')->insert($feedcontent);
		C::t('forum_thread')->update_status_by_tid($thread['tid'], '512');
	} else {
		C::t('forum_threadpreview')->update_relay_by_tid($thread['tid'], 1);
	}
	$notemsg = cutstr($message, 140);
	$followfeed = array(
		'uid' => $_G['uid'],
		'username' => $_G['username'],
		'tid' => $thread['tid'],
		'note' => followcode($notemsg, $thread['tid'], $pid, 0, false),
		'dateline' => TIMESTAMP
	);
	$feedid = C::t('home_follow_feed')->insert($followfeed, true);
	C::t('common_member_count')->increase($_G['uid'], array('feeds'=>1));
}

if($thread['replycredit'] > 0 && !$modnewreplies && $thread['authorid'] != $_G['uid'] && $_G['uid']) {

	$replycredit_rule = C::t('forum_replycredit')->fetch($_G['tid']);
	if(!empty($replycredit_rule['times'])) {
		$have_replycredit = C::t('common_credit_log')->count_by_uid_operation_relatedid($_G['uid'], 'RCA', $_G['tid']);
		if($replycredit_rule['membertimes'] - $have_replycredit > 0 && $thread['replycredit'] - $replycredit_rule['extcredits'] >= 0) {
			$replycredit_rule['extcreditstype'] = $replycredit_rule['extcreditstype'] ? $replycredit_rule['extcreditstype'] : $_G['setting']['creditstransextra'][10];
			if($replycredit_rule['random'] > 0) {
				$rand = rand(1, 100);
				$rand_replycredit = $rand <= $replycredit_rule['random'] ? true : false ;
			} else {
				$rand_replycredit = true;
			}
			if($rand_replycredit) {
				updatemembercount($_G['uid'], array($replycredit_rule['extcreditstype'] => $replycredit_rule['extcredits']), 1, 'RCA', $_G[tid]);
				C::t('forum_post')->update('tid:'.$_G['tid'], $pid, array('replycredit' => $replycredit_rule['extcredits']));
				$updatethreaddata[] = DB::field('replycredit', $thread['replycredit'] - $replycredit_rule['extcredits']);
			}
		}
	}
}



$replymessage = 'post_reply_succeed';


$_G['forum']['threadcaches'] && deletethreadcaches($_G['tid']);

include_once libfile('function/stat');
updatestat($thread['isgroup'] ? 'grouppost' : 'post');

$param = array('fid' => $_G['fid'], 'tid' => $_G['tid'], 'pid' => $pid, 'from' => $_GET['from'], 'sechash' => !empty($_GET['sechash']) ? $_GET['sechash'] : '');
if($feedid) {
	$param['feedid'] = $feedid;
}


$fieldarr = array(
	'lastposter' => array($author),
	'replies' => 1
);
if($thread['lastpost'] < $_G['timestamp']) {
	$fieldarr['lastpost'] = array($_G['timestamp']);
}
$row = C::t('forum_threadaddviews')->fetch($_G['tid']);
if(!empty($row)) {
	C::t('forum_threadaddviews')->update($_G['tid'], array('addviews' => 0));
	$fieldarr['views'] = $row['addviews'];
}
$updatethreaddata = array_merge($updatethreaddata, C::t('forum_thread')->increase($_G['tid'], $fieldarr, false, 0, true));
if($thread['displayorder'] != -4) {
	if(BFD_APP_CREDITS_AWARD > 1)
    {
        $uid_arrtmp = array();
        for($i=0;$i<BFD_APP_CREDITS_AWARD;$i++)
        {
            $uid_arrtmp[] = $_G['uid'];
        }
		updatepostcredits('+', $uid_arrtmp, 'reply', $_G['fid']);
    }
    else
    {
		updatepostcredits('+', $_G['uid'], 'reply', $_G['fid']);
    }
	//updatepostcredits('+', $_G['uid'], 'reply', $_G['fid']);
	if($_G['forum']['status'] == 3) {
		if($_G['forum']['closed'] > 1) {
			C::t('forum_thread')->increase($_G['forum']['closed'], $fieldarr, true);
		}
		C::t('forum_groupuser')->update_counter_for_user($_G['uid'], $_G['fid'], 0, 1);
		C::t('forum_forumfield')->update($_G['fid'], array('lastupdate' => TIMESTAMP));
		require_once libfile('function/grouplog');
		updategroupcreditlog($_G['fid'], $_G['uid']);
	}

	$lastpost = "$thread[tid]\t$thread[subject]\t$_G[timestamp]\t$author";
	C::t('forum_forum')->update($_G['fid'], array('lastpost' => $lastpost));
	C::t('forum_forum')->update_forum_counter($_G['fid'], 0, 1, 1);
	if($_G['forum']['type'] == 'sub') {
		C::t('forum_forum')->update($_G['forum']['fup'], array('lastpost' => $lastpost));
	}
}



if($updatethreaddata) {
	C::t('forum_thread')->update($_G['tid'], $updatethreaddata, false, false, 0, true);
}
/*COMMENTS
//回复后更新主贴mc中lastpost和replies数量 20121125 ep
*/
/*COMMENTS END*/
//var_dump($thread);
//exit;
BfdApp::display_result('post_reply_succeed');



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

	$attach = array();
	$class_upload = new discuz_upload();
	$picAdd = '';
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
				
				$config = getuploadconfig($uid);
				$pic = $attach['attachment'];
				$filesize = $attach['size'];
				$width = $attach['imageinfo']['width'];
                DB::query("INSERT INTO ".DB::table('forum_attachment')." SET tid='$tid',pid='$pid',uid='$uid',tableid='".getattachtableid($tid)."'");
                $aid = DB::insert_id();
                $picAdd .= "\n".'[attach]'.$aid.'[/attach]';
                DB::query("INSERT INTO pre_forum_attachment_".getattachtableid($tid)." SET aid='$aid',tid='$tid',pid='$pid',dateline='{$_G['timestamp']}',filename='{$fileinfo['name']}',filesize='$filesize',attachment='$pic',width='{$width}',isimage='1'");
        	}
	}
	if(!empty($picAdd))
	{
   		DB::query("UPDATE pre_forum_post SET attachment='2',message=CONCAT(message,'$picAdd') WHERE pid='$pid'");
	}
	return $aid;
}

?>
