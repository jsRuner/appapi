<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: spacecp_pm.php 33289 2013-05-22 05:44:06Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$pmid = empty($_GET['pmid'])?0:floatval($_GET['pmid']);
$uid = empty($_GET['uid'])?0:intval($_GET['uid']);
$plid = empty($_GET['plid'])?0:intval($_GET['plid']);

if($uid) {
	$touid = $uid;
} else {
	$touid = empty($_GET['touid'])?0:intval($_GET['touid']);
}
$daterange = empty($_GET['daterange'])?1:intval($_GET['daterange']);

loaducenter();

require_once libfile('function/spacecp');

$waittime = interval_check('post');
if($waittime > 0) {
	BfdApp::display_result('message_can_not_send_2');
}

cknewuser();

if(!checkperm('allowsendpm')) {
	BfdApp::display_result('no_privilege_sendpm');
}

if($touid) {
	if(isblacklist($touid)) {
		BfdApp::display_result('is_blacklist');
	}
}

if(!empty($_POST['username'])) {
	$_POST['users'][] = $_POST['username'];
}
$type = intval($_POST['type']);
$coef = 1;

!($_G['group']['exempt'] & 1) && checklowerlimit('sendpm', 0, $coef);

$message = (!empty($_POST['messageappend']) ? $_POST['messageappend']."\n" : '').trim($_POST['message']);
$message = urldecode($message);
if(empty($message)) {
	BfdApp::display_result('unable_to_send_air_news');
}
if( BFD_APP_CHARSET_OUTPUT != BFD_APP_CHARSET ){
    $message = iconv( BFD_APP_CHARSET_OUTPUT, BFD_APP_CHARSET."//IGNORE", $message );
}
$message = censor($message);
loadcache(array('smilies', 'smileytypes'));
foreach($_G['cache']['smilies']['replacearray'] AS $key => $smiley) {
	$_G['cache']['smilies']['replacearray'][$key] = '[img]'.$_G['siteurl'].'static/image/smiley/'.$_G['cache']['smileytypes'][$_G['cache']['smilies']['typearray'][$key]]['directory'].'/'.$smiley.'[/img]';
}
$message = preg_replace($_G['cache']['smilies']['searcharray'], $_G['cache']['smilies']['replacearray'], $message);
$subject = '';
if($type == 1) {
	$subject = dhtmlspecialchars(trim($_POST['subject']));
}

include_once libfile('function/friend');
$return = 0;
if($touid || $pmid) {
	if($touid) {
		if(($value = getuserbyuid($touid))) {
			$value['onlyacceptfriendpm'] = $value['onlyacceptfriendpm'] ? $value['onlyacceptfriendpm'] : ($_G['setting']['onlyacceptfriendpm'] ? 1 : 2);
			if($_G['group']['allowsendallpm'] || $value['onlyacceptfriendpm'] == 2 || ($value['onlyacceptfriendpm'] == 1 && friend_check($touid))) {
				$return = sendpm($touid, $subject, $message, '', 0, 0, $type);
			} else {
				BfdApp::display_result('message_can_not_send_onlyfriend');
			}
		} else {
			BfdApp::display_result('message_bad_touid');
		}
	} else {
		$topmuid = intval($_GET['topmuid']);
		$return = sendpm($topmuid, $subject, $message, '', $pmid, 0);
	}

} 

if($return > 0) {
	include_once libfile('function/stat');
	updatestat('sendpm', 0, $coef);

	C::t('common_member_status')->update($_G['uid'], array('lastpost' => TIMESTAMP));
	!($_G['group']['exempt'] & 1) && updatecreditbyaction('sendpm', 0, array(), '', $coef);
	BfdApp::display_result('do_success');

} else {
	BfdApp::display_result('message_can_not_send');
}

?>
