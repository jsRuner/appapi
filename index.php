<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: portal.php 28297 2012-02-27 08:35:59Z monkey $
 */


require './inc.php';
global $_app_debug;



$mod_array = array(
'group_index', //我的小组动态
'group_my', //我的小组列表
'group_all', //全部小组列表
'group_info', //小组信息+帖子列表
'group_action', //加入退出小组
'group_thread', //帖子详细页
'group_thread2', //帖子详细页
'group_thread3', //帖子详细页
'group_post', //帖子回复列表
'post_newthread', //发表帖子
'post_reply', //回复帖子
'user_follow', //用户关系
'user_space', //用户空间
'space_thread', //用户帖子列表
'space_pm', //用户用户消息列表
'space_notice', //用户通知列表
'user_detail', //用户详细
'user_avatar', //用户头像
'user_action', //用户添加关注\取消关注
'user_friend', //用户好友
'thread_list', //用户好友
'forum_list', //用户好友
'forum_misc', //用户好友
'thread_activity', //用户好友
'index2', //首页
'sign', //首页
'tip', //首页
'tip_message', //首页
'index_threads', //首页
'topicadmin',//
'search',//
'favorite_list',//
'favorite_action',//
'send_pm',//
'userinfo',//

);

$need_login = array('post_newthread','post_reply','user_follow','user_space','space_thread','space_pm','space_notice','user_detail','user_avatar','user_action','user_friend','thread_activity','forum_misc','sign','tip');

if(empty($_GET['mod']) || !in_array($_GET['mod'], $mod_array)) 
{
	$_GET['mod'] = 'index';
}
//define('CURMODULE', $mod);

$token = trim($_REQUEST['token']);
//$token = urldecode($token);
$token_result = BfdApp::check_user($token);
if(BFD_APP_DEBUG)
{
	$_G['uid'] = 1;
	BfdApp::init_user();
	$token_result = 'token_check_successed';
}
if($token_result == 'token_check_successed')
{
	require  './source/bfd_app_'.$_GET['mod'].'.php';
	exit;
}
else
{
	if(!in_array($_GET['mod'],$need_login))
	{
		BfdApp::init_user();
		require  './source/bfd_app_'.$_GET['mod'].'.php';
    	exit;
	}
	BfdApp::display_result('user_no_login');
	
}

BfdApp::display_result($token_result);

?>
