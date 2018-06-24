<?php

$permission_array = array(
	'sitehost' => 'x3gbk.discuzfan.com',
	'appid' => '41268d5ac0',
	'appversion' => '1.1.0',
	'createtime' => '1382419225',
	'exetime' => '1382419225',
	'permission' => array(
		1 => array('modid'=> 1,'modname'=>'index','needlogin'=>0),
		2 => array('modid'=> 2,'modname'=>'forum_list','needlogin'=>0),
		3 => array('modid'=> 3,'modname'=>'thread_list','needlogin'=>0),
		4 => array('modid'=> 4,'modname'=>'view_thread','needlogin'=>0),
		5 => array('modid'=> 5,'modname'=>'post_thread','needlogin'=>1),
		6 => array('modid'=> 6,'modname'=>'post_reply','needlogin'=>1),
		7 => array('modid'=> 7,'modname'=>'apply_activity','needlogin'=>1),
		8 => array('modid'=> 8,'modname'=>'vote_poll','needlogin'=>1),
		9 => array('modid'=> 9,'modname'=>'user_space','needlogin'=>1),
		10 => array('modid'=> 10,'modname'=>'user_message','needlogin'=>1),
		11 => array('modid'=> 11,'modname'=>'add_friend','needlogin'=>1),
		12 => array('modid'=> 12,'modname'=>'more_info','needlogin'=>1),
	),
);

$server_mod_permid  = array(
		'group_info' => 3, //小组信息+帖子列表
		'group_thread2' => 4, //帖子详细页
		'post_newthread' => 5, //发表帖子
		'post_reply' => 6, //回复帖子
		'user_follow' => 11, //用户关系
		'user_space' => 9, //用户空间
		'space_thread' => 9, //用户帖子列表
		'space_pm',  => 9//用户用户消息列表
		'space_notice' => 9, //用户通知列表
		'user_detail' => 9, //用户详细
		'user_avatar' => 1, //用户头像
		'user_action' => 11, //用户添加关注\取消关注
		'user_friend' => 9, //用户好友
		'thread_list' => 3, //用户好友
		'forum_list' => 2, //用户好友
		'forum_misc' => 7, //用户好友
		'thread_activity' => 7, //用户好友
		'index2' => 1, //首页
);
