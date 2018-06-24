<?php
define('BFD_APP_DEBUG',isset($_REQUEST['xdbug']) && $_REQUEST['xdbug'] == 'apidebug'); 
if(BFD_APP_DEBUG)
{
	ini_set('display_errors',1);
	error_reporting(E_ALL);
}

define('APPTYPEID', 4);

require './source/class/class_core.php';
include_once './source/function/function_home.php';
$discuz = C::app();
$cachelist = array('plugin','setting','heats','globalstick','magic','userapp','usergroups', 'diytemplatenamehome','medals');

$discuz->cachelist = $cachelist;
$discuz->init();

require './config/config.php';
require './lib/lib_bfd_app.php';
require './lib/lib_bfd_perm.php';
require './lib/lib_bfd_user.php';
require './lib/lib_app_helper_attach.php';

$_G['bfd_app_language'] = $_bfd_app_global_error;
$_G['bfd_app_errorcode'] = $_bfd_app_global_error_code;

$_G['uid'] = 0;
$_G['member'] = array();
/*
 * 权限判断
 **/
$mod_array = array(
		'group_index', //我的小组动态
		'group_my', //我的小组列表
		'group_all', //全部小组列表
		'group_info', //小组信息+帖子列表
		'group_action', //加入退出小组
//		'group_thread', //帖子详细页
		'group_thread2', //帖子详细页
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
		'index2', //首页
);

$need_login = true;

