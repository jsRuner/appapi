<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_notice.php 30269 2012-05-18 01:58:22Z liulanbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$_G['uid'])
{
	BfdApp::display_result('user_no_login');
}

loaducenter();

$result = array();

//通知数
$typestr = " `type` not in('post','blogcomment','at','follower')";
$count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_notification')." WHERE uid={$_G['uid']} AND `new`='1' AND {$typestr}");
$result['notpost'] = intval($count);

//回复我的数
$typestr = " `type` in ('post','blogcomment')";;
$count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_notification')." WHERE uid={$_G['uid']} AND `new`='1' AND {$typestr}");
$result['post'] = intval($count);


//公共消息数
$announcepm = 0;
foreach(C::t('common_member_grouppm')->fetch_all_by_uid($_G['uid'], 1) as $gpmid => $gpuser) 
{
	$gpmstatus[$gpmid] = $gpuser['status'];
	if($gpuser['status'] == 0) {
		$announcepm ++;
	}
}

//私人消息数
$newpmarr = uc_pm_checknew($_G['uid'], 1);
$newpm = $newpmarr['newpm'];
$result['pm'] = $announcepm + $newpm;

BfdApp::display_result('get_success',$result);

?>
