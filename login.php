<?php

require 'inc.php';

if(!function_exists('uc_user_login')) {
        loaducenter();
}


$username = urldecode($_POST['username']);
$password = $_POST['password'];
if(BFD_APP_CHARSET != BFD_APP_CHARSET_OUTPUT)
{
	$username = iconv(BFD_APP_CHARSET_OUTPUT,BFD_APP_CHARSET."//IGNORE",$username);
}

//用户登录验证
$ucresult = uc_user_login($username, $password, 0, 0, '', '');

$tmp = array();
list($tmp['uid'], $tmp['username'], $tmp['password'], $tmp['email'], $duplicate) = daddslashes($ucresult, 1);
$ucresult = $tmp;

$return = array();
if($ucresult['uid'] <= 0) {
	BfdApp::display_result('user_login_failed');
}

//用户信息
$member = DB::fetch_first("SELECT * FROM ".DB::table('common_member')." WHERE uid='".$ucresult['uid']."'");
if(!$member) {
	BfdApp::display_result('user_login_failed');
}

//用户详细信息
$member_profile = DB::fetch_first("SELECT * FROM ".DB::table('common_member_profile')." WHERE uid='{$ucresult['uid']}'");
//获取勋章
$member_field_forum = DB::fetch_first("SELECT * FROM ".DB::table('common_member_field_forum')." WHERE uid='{$ucresult['uid']}'");

$medalids = $member_field_forum['medals'];
$usermedals = $medal_detail = $usermedalmenus = array();
if($medalids) {
	foreach($medalids = explode("\t", $medalids) as $key => $medalid) {
		list($medalid, $medalexpiration) = explode("|", $medalid);
		if(empty($medalid))
		{
			continue;
		}
		if(!$medalexpiration || $medalexpiration > TIMESTAMP) {
			$usermedals[] = $medalid;
		}
	}
	if(!empty($usermedals))
	{
		$medal_detail = DB::fetch_all("SELECT * FROM ".DB::table('forum_medal')." WHERE medalid in(".implode(',',$usermedals).")"); 
		foreach($medal_detail as $val)
		{
			if($val['expiration']==0 || $val['expiration'] > TIMESTAMP)
			{
				$usermedalmenus[] = array('medalid'=>$val['medalid'],'name'=>$val['name'],'image'=>STATICURL.'image/common/'.$val['image']);
			}
		}	
	}
}

//统计数据
$member_count = DB::fetch_first("SELECT * FROM ".DB::table('common_member_count')." WHERE uid='{$ucresult['uid']}'");

//头像

$data = array();
$data['uid']    = $member['uid'];
$data['avatar'] =  avatar($ucresult['uid'],'middle',true);
$data['username'] = $member['username'];
$data['email']    = $member['email'];
$data['password'] = $member['password'];
$data['groupid']  = $member['groupid'];//等级
$data['extcredits1'] = $member_count['extcredits1'];//蓝宝石
$data['extcredits2'] = $member_count['extcredits2'];//积分
$data['follower']    = $member_count['follower'];//分数数
$data['following']   = $member_count['following'];//关注数
$data['gender']      = lang('space','gender_'.$member_profile['gender']);//$member_profile['gender'] ? ($member_profile['gender']==1 ? '男' : '女') : '保密';//关注数
$data['department']   = $member_profile['field2'];//部门
$data['constellation']   = $member_profile['constellation'];//星座
$data['medals']   = $usermedalmenus;//勋章
$data['bloodtype'] = $member_profile['bloodtype'];//血型
$data['sightml'] = strip_tags($member_profile_forum['sightml']);//签名

$time = time();
$data['token']      =  authcode("{$member['password']}\t{$member['uid']}\t{$time}", 'ENCODE',BFD_APP_KEY,BFD_APP_KEY_EXPIRY);
$data['token_expire'] = $time+BFD_APP_KEY_EXPIRY;

BfdApp::display_result('user_login_successed',$data);

