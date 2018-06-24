<?php
/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: spacecp_follow.php 32002 2012-10-30 07:53:32Z zhangjie $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}


if(!$_G['uid'])
{
    BfdApp::display_result('user_no_login');
}

$ops = array('add', 'del');
$op = in_array($_GET['op'], $ops) ? $_GET['op'] : '';

if(empty($op))
{
	BfdApp::display_result('params_error');
}

if($op == 'add') {
	$followuid = intval($_GET['fuid']);
	if(empty($followuid)) {
		BfdApp::display_result('params_error');
	}
	if($_G['uid'] == $followuid) {
		BfdApp::display_result('follow_not_follow_self');
	}
	$special = intval($_GET['special']) ? intval($_GET['special']) : 0;
	$followuser = getuserbyuid($followuid);
	$mutual = 0;
	$followed = C::t('home_follow')->fetch_by_uid_followuid($followuid, $_G['uid']);	
	if(!empty($followed)) {
		if($followed['status'] == '-1') {
			BfdApp::display_result('follow_other_unfollow');
		}
		$mutual = 1;
		C::t('home_follow')->update_by_uid_followuid($followuid, $_G['uid'], array('mutual'=>1));
	}
	$followed = C::t('home_follow')->fetch_by_uid_followuid($_G['uid'], $followuid);
	if(empty($followed)) {
		$followdata = array(
			'uid' => $_G['uid'],
			'username' => $_G['username'],
			'followuid' => $followuid,
			'fusername' => $followuser['username'],
			'status' => 0,
			'mutual' => $mutual,
			'dateline' => TIMESTAMP
		);
		C::t('home_follow')->insert($followdata, false, true);
		C::t('common_member_count')->increase($_G['uid'], array('following' => 1));
		C::t('common_member_count')->increase($followuid, array('follower' => 1, 'newfollower' => 1));
		notification_add($followuid, 'follower', 'member_follow_add', array('count' => $count, 'from_id'=>$_G['uid'], 'from_idtype' => 'following'), 1);
	} elseif($special) {
		$status = $special == 1 ? 1 : 0;
		C::t('home_follow')->update_by_uid_followuid($_G['uid'], $followuid, array('status'=>$status));
		$special = $special == 1 ? 2 : 1;
	} else {
		BfdApp::display_result('follow_followed_ta');
	}
	$type = !$special ? 'add' : 'special';
/*COMMENTS
//follow反馈参数调整 同时调用加减积分接口 20121125 ep
*/
	$follow_array = array('fuid' => $followuid, 'type' => $type, 'special' => $special, 'from' => !empty($_GET['from']) ? $_GET['from'] : 'list');
	$follow_array['mutual'] = $mutual;
	
	//调用积分
	require_once libfile('lib/space_helper');
	lib_space_helper::followuserCreditPlus('+',$_G['uid']);
/*COMMENTS END*/
	BfdApp::display_result('follow_add_succeed');
} elseif($op == 'del') {
	$delfollowuid = intval($_GET['fuid']);
	if(empty($delfollowuid)) {
		BfdApp::display_result('params_error');
	}
	$affectedrows = C::t('home_follow')->delete_by_uid_followuid($_G['uid'], $delfollowuid);
	if($affectedrows) {
		C::t('home_follow')->update_by_uid_followuid($delfollowuid, $_G['uid'], array('mutual'=>0));
		C::t('common_member_count')->increase($_G['uid'], array('following' => -1));
		C::t('common_member_count')->increase($delfollowuid, array('follower' => -1, 'newfollower' => -1));
	}
/*COMMENTS
//follow反馈参数调整 同时调用加减积分接口 20121125 ep
*/
	//调用积分
	require_once libfile('lib/space_helper');
	lib_space_helper::followuserCreditPlus('-',$_G['uid']);
/*COMMENTS END*/
	BfdApp::display_result('follow_cancel_succeed');
} elseif($op == 'bkname') {
	$followuid = intval($_GET['fuid']);
	$followuser = C::t('home_follow')->fetch_by_uid_followuid($_G['uid'], $followuid);
	if(empty($followuser)) {
		BfdApp::display_result('follow_not_assignation_user');
	}
	if(submitcheck('editbkname')) {
		$bkname = cutstr(strip_tags($_GET['bkname']), 30, '');
		C::t('home_follow')->update_by_uid_followuid($_G['uid'], $followuid, array('bkname'=>$bkname));
		BfdApp::display_result('follow_remark_succeed');
	}
	BfdApp::display_result('follow_remark_failed');
} 

?>

