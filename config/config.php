<?php
include 'config_extra.php';

define('BFD_APP_CREDITS_AWARD',1);
//define('BFD_APP_VERSION','1.1.0');//加密密匙
//define('BFD_APP_KEY','123DF##@@@');//加密密匙
//define('BFD_APP_CHARSET','GBK');//网站服务端编码
//define('BFD_APP_CHARSET_HTML_DECODE','ISO-8859-1');//网站服务端编码
//define('BFD_APP_CHARSET_HTML_DECODE','GB2312');//网站服务端编码
define('BFD_APP_CHARSET_OUTPUT','UTF-8');//接口输出编码，请不要修改
define('BFD_APP_KEY_EXPIRY',3600*24*30);//token  过期时间
//define('BFD_APP_INDEX_BLOCK_ID',1);//公告类型id 首页上方
//define('BFD_APP_INDEX_BLOCK_ID_2',3);//公告类型id  首页下方
define('BFD_APP_INDEX_BLOCK_ITEM_NUM',40);//首页热帖条数
define('BFD_INDEX_HOT_THREAD_PAGENUM', 10);//首页热帖 每页条数
//define('BFD_APP_PIC_PATH','/data/attachment/');//
//define('BFD_APP_PIC_PATH_DIY','http://img.hfcyh.com/hfcyhimg/');//自定义附件图片前缀
//define('BFD_APP_PIC_PATH_DIY','');//自定义附件图片前缀
//define('BFD_APP_DATA_URL_PRE','http://'.$_SERVER['HTTP_HOST'].'/');//
define('BFD_APP_THUMB_IMAGE_PATH','appthumb/');
define('BFD_APP_THUMB_IMAGE_PATH_URL',BFD_APP_DATA_URL_PRE.'data/attachment/');
define('BFD_APP_THUMB_IMAGE_PATH_URL_DIY',BFD_APP_PIC_PATH_DIY);
define('BFD_APP_THUMB_IMAGE_HEIGHT', 820);
define('BFD_APP_THUMB_IMAGE_WIDTH', 720);
define('BFD_APP_THUMB_IMAGE_THUMB_HEIGHT', 250);
define('BFD_APP_THUMB_IMAGE_THUMB_WIDTH', 300);
define('BFD_APP_ATTACH_IMAGE_FIX_URL', BFD_APP_DATA_URL_PRE.'appapi/image.php');//附件图片压缩接口
define('BFD_APP_THUMB_IMAGE_FIX_URL', BFD_APP_DATA_URL_PRE.'appapi/remote_image.php');//附件图片压缩接口
 
define('BFD_APP_GROUP_ACTIVE_PAGESIZE',5);
define('BFD_APP_GROUP_ACTIVE_PER_GROUP',3);
define('BFD_APP_GROUP_MY_PAGESIZE',20);
define('BFD_APP_GROUP_ALL_PAGESIZE',5);
define('BFD_APP_GROUP_THREAD_PAGESIZE',10);
define('BFD_APP_GROUP_POST_PAGESIZE',10);
define('BFD_APP_USER_FOLLOW_PAGESIZE',10);
define('BFD_APP_USER_FEED_PAGESIZE',10);

require_once 'smiley_map.php';
$_bfd_app_global_error = array(
	'user_no_login' => '用户没有登录',
	'not_loggedin' => '用户没有登录',
	'user_login_successed' => '用户登录成功',
	'user_login_failed' => '用户登录失败',
	'user_status_excption' => '用户状态异常，不具备操作权限',
	'no_privilege_newbiespan' => '抱歉，您目前处于见习期间，需要等几分钟才能进行本操作',
	'no_privilege_avatar' => '抱歉，您需要设置自己的头像后才能进行本操作',
	'no_privilege_email' => '抱歉，您需要验证激活自己的邮箱后才能进行本操作',
	'no_privilege_friendnum' => '抱歉，您需要添加足够好友才能进行本操作',

	'token_error' => '密匙验证错误',
	'token_has_expired' => '密匙已过期',
	'token_check_successed' => '密匙验证正确',

	'get_success' => '获取成功',	
	'do_success' => '操作成功',	
	'get_failed' => '获取失败',	
	'no_new_items' => '没有更新内容',	
	'group_out_success' => '退出小组成功',	
	'group_join_success' => '加入小组成功',	
	'group_member_maximum' => '小组成员数已达上限',
	'group_join_need_invite' => '加入小组需要邀请',
	'group_join_apply_wait_for_pend_succeed' => '加入小组申请发送成功，请等待管理员批准',
	'group_join_apply_succeed' => '加入小组申请成功',
	'group_has_joined' => '您已经加入了改小组，请不要重复加入',	

	'user_visit_been_banned' => '用户已被禁止访问',

	'undefined_error' => '未定义的错误',	
	'params_error' => '参数提交错误',	
	
	'forum_nonexistence' => '小组不存在',
	'group_nopermission' => '权限不足',
//post
	'post_newthread_succeed' => '发帖成功',
	'post_reply_succeed' => '评论成功',
	'post_subject_isnull' => '帖子标题不能为空',
	'post_message_isnull' => '帖子内容不能为空',
	'post_flood_ctrl' => '请不要发水贴',
	'thread_flood_ctrl_threads_per_hour' => '每小时发帖量过多',
	'post_message_tooshort' => '帖子内容太短了',
	'post_sm_isnull' => '内容不能为空',
	
	'follow_not_follow_self' => '不能关注自己',
	'follow_other_unfollow' => '对方不允许您关注TA',
	'follow_followed_ta' => '您已经关注了TA',
	'follow_add_succeed' => '关注成功',
	'follow_cancel_succeed' => '取消关注成功',
	'follow_remark_succeed' => '添加备注成功',
	'follow_remark_failed' => '添加备注失败',
	'follow_not_assignation_user' => '未指定用户',

	'appversion_is_lates' => '已是最新版本',
	'appversion_get_success' => '取得最新版本',
	//friend
	'add_friend_success' => '添加好友成功',
	'request_has_been_sent' => '好友申请发送成功',
	'ignore_friend_success' => '好友已被删除',
	'ignore_friend_failed' => '好友删除失败，请重试',
	'waiting_for_the_other_test' => '等待对方确认',
	'no_privilege_addfriend' => '抱歉，您目前没有权限添加好友',
	'friend_self_error' => '抱歉，不能添加自己为好友',
	'you_have_friends' => '对方已经是您的好友了',
	'space_does_not_exist' => '用户不存在或已被禁止访问',
	'is_blacklist' => '抱歉，您不能添加对方为好友',
	'enough_of_the_number_of_friends_with_magic' => '抱歉，您的好友数量已经超出上限',
	'enough_of_the_number_of_friends' => '抱歉，您的好友数量已经超出上限',

	'unbinded_user' => '您尚未绑定用户，请登录PC网页版进行绑定操作，谢谢！',
	
	'thread_poll_succeed' => '投票成功',
    'activity_cancel_success' => '您已经成功退出该活动',
    'activity_exile_field' => '带 "*" 号为必填项',
    'activity_repeat_apply' => '请不要重复申请',
    'activity_stop' => '报名已停止',
    'activity_imgurl_error' => '上传文件错误',
    'activity_completion' => '活动申请成功，请等待管理员审批',
	
	'thread_poll_voted' => '对不起，您已经投过票了',
	
	'thread_nonexistence' => '该帖子不存在或已被删除',
    'thread_noexists' => '该帖子不存在或已被删除',
	'word_banned' => '您填写的内容包含不良信息',

	'qiandao_success' => '签到成功',

	'admin_succeed' => '管理操作成功',	
	'admin_nopermission' => '管理权限不足',	
	'register_succeed' => '注册成功',	
	
	'profile_email_illegal' => '邮箱格式不正确',
	'profile_email_domain_illegal' => '不是允许的邮箱',
	'profile_email_duplicate' => '邮箱已被使用',
	'not_open_registration_invite' => '本站不开放普通注册，需要有效邀请码',
	'register_rules_agree' => '您必须同意服务条款后才能注册',
	'profile_username_tooshort' => '用户名不能少于3个字符',
	'profile_username_toolong' => '用户名不能大于15个字符',
	'register_activation_message' => '用户名已存在',
	'profile_password_tooshort' => '密码太短了',
	'profile_password_password_weak' => '密码太弱了',
	'profile_passwd_notmatch' => '两次输入密码不一样',
	'profile_passwd_illegal' => '密码包含特殊字符',
	'profile_username_protect' => '不能使用该用户名',
	'profile_username_illegal' => '用户名包含非法字符',
	'profile_username_duplicate' => '用户名已被使用',
	'profile_required_info_invalid' => '您尚未填写必填项目或必填项目格式不正确',
	'register_ctrl' => 'IP 地址暂时无法注册',
	'register_flood_ctrl' => 'IP 地址24小时内暂无法注册',
	'profile_uid_duplicate' => '用户id被占用',
	'register_manual_verify' => '注册成功，请等待审核',
	'register_email_verify' => '注册成功，请登录邮箱激活',
	'register_email_send_succeed' => '系统向您的注册邮箱发送了注册地址，请登录您的邮箱继续注册',
	'submit_secqaa_invalid' => '验证问题答案错误',
	'submit_seccode_invalid' => '验证码错误',
	'submit_invalid' => '参数提交错误',

	'favorite_cannot_favorite' => '指定的信息无法收藏',
	'favorite_repeat' => '请不要重复收藏',
	'favorite_do_success' => '收藏成功',
	'favorite_does_not_exist' => '收藏不存在',

	'thread_rate_succeed' => '评分成功',
	'thread_rate_range_invalid' => '请输入正确的分值',
	'thread_rate_ctrl' => '24 小时评分数超过限制',
	'thread_rate_range_self_invalid' => '积分不足，无法评分',
	'thread_rate_duplicate' => '不能对同一个帖子重复评分',
	'thread_rate_banned' => '不能对屏蔽帖评分',
	'thread_rate_anonymous' => '不能对匿名帖评分',
	'thread_rate_member_invalid' => '不能给自己发表的帖子评分',
	'thread_rate_timelimit' => '帖子尚不能评分',
	'rate_post_error' => '帖子不存在或不能被推送',
	'thread_rate_moderator_invalid' => '作为版主您只能在自己的管辖范围内评分',
    'search_forum_closed' => '论坛搜索已关闭',
    'search_ctrl' => '请稍候再搜索',
    'search_forum_invalid' => '未指定搜索论坛的范围',
    'search_toomany' => '搜索次数过多，请稍候再试',
    'search_id_invalid' => '搜索不存在或已过期',

    'message_can_not_send_2' => '请稍候再发送',
    'no_privilege_sendpm' => '您没有权限发短消息',
    'is_blacklist' => '对方设置不允许',
    'unable_to_send_air_news' => '不能发送空消息',
    'message_can_not_send_onlyfriend' => '用户只接收好友消息',
    'message_bad_touid' => '用户不存在或被冻结',
    'message_can_not_send' => '发送短消息失败',
    'view_password_error' => '输入密码有误',
); 

//success 都返回 E00000
$_bfd_app_global_error_code = array(
	'user_login_successed' => 'E00000',
	'register_succeed' => 'E00000',	
	'user_no_login' => 'E00001',
	'not_loggedin' => 'E00001',
	'user_login_failed' => 'E00002',
	'user_status_excption' => 'E00003',
	'no_privilege_newbiespan' => 'E00047',
	'no_privilege_avatar' => 'E00048',
	'no_privilege_email' => 'E00049',
	'no_privilege_friendnum' => 'E00050',

	'token_check_successed' => 'E00000',
	'token_has_expired' => 'E00004',
	'token_error' => 'E00005',
	
	'get_success' => 'E00000',
	'do_success' => 'E00000',	
	'get_failed'   => 'E00006',
	'no_new_items' => 'E00008',	
	'group_out_success' => 'E00009',	
	'group_join_success' => 'E00010',	
	'group_member_maximum' => 'E00011',
	'group_join_need_invite' => 'E00012',
	'group_join_apply_wait_for_pend_succeed' => 'E00013',
	'group_has_joined' => 'E00014',	
	'group_join_apply_succeed' => 'E00015',
	'group_nopermission' => 'E00031',

	'user_visit_been_banned' => 'E00016',

	'undefined_error' => 'E00099',	
	'params_error' => 'E00098',	
    'activity_exile_field' => 'E00098',
    'activity_repeat_apply' => 'E00098',
    'activity_stop' => 'E00098',
    'activity_imgurl_error' => 'E00098',
//post
	'post_newthread_succeed' => 'E00000',
	'post_reply_succeed' => 'E00000',
	'post_subject_isnull' => 'E00041',
	'post_message_isnull' => 'E00042',
	'post_flood_ctrl' => 'E00043',
	'thread_flood_ctrl_threads_per_hour' => 'E00044',
	'post_message_tooshort' => 'E00045',
	'post_sm_isnull' => 'E00046',
	
	'follow_not_follow_self' => 'E00017',
	'follow_other_unfollow' => 'E00018',
	'follow_followed_ta' => 'E00019',
	'follow_add_succeed' => 'E00000',
	'follow_cancel_succeed' => 'E00000',
	'follow_remark_succeed' => 'E00000',
	'follow_remark_failed' => 'E00020',
	'follow_not_assignation_user' => 'E00021',

	'appversion_is_lates' => 'E00022',
	'appversion_get_success' => 'E00000',
	
	//friend
	'add_friend_success' => 'E00000',
	'request_has_been_sent' => 'E00000',
	'appversion_get_success' => 'E00000',
	'ignore_friend_success' => 'E00000',
	'ignore_friend_failed' => 'E00032',
	'waiting_for_the_other_test' => 'E00023',
	'no_privilege_addfriend' => 'E00024',
	'friend_self_error' => 'E00025',
	'you_have_friends' => 'E00026',
	'space_does_not_exist' => 'E00027',
	'is_blacklist' => 'E00028',
	'enough_of_the_number_of_friends_with_magic' => 'E00029',
	'enough_of_the_number_of_friends' => 'E00030',

	'unbinded_user' => 'E00051',
	
	'thread_poll_succeed' => 'E00000',
    'activity_cancel_success' => 'E00000',
    'activity_completion' => 'E00000',
	'thread_poll_voted' => 'E00053',
	'thread_nonexistence' => 'E00052',
	'thread_noexists' => 'E00052',
	'word_banned' => 'E00054',

	'qiandao_success' => 'E00000',
	'action_info' => 'E00099',	

	'admin_succeed' => 'E00000',	
	'admin_nopermission' => 'E00031',	

	'profile_email_illegal' => 'E00098',
	'profile_email_domain_illegal' => 'E00098',
	'profile_email_duplicate' => 'E00098',
	'profile_email_illegal' => 'E00098',
	'profile_email_domain_illegal' => 'E00098',
	'profile_email_duplicate' => 'E00098',
	'not_open_registration_invite' => 'E00098',
	'register_rules_agree' => 'E00098',
	'profile_username_tooshort' => 'E00098',
	'profile_username_toolong' => 'E00098',
	'register_activation_message' => 'E00098',
	'profile_password_tooshort' => 'E00098',
	'profile_password_password_weak' => 'E00098',
	'profile_passwd_notmatch' => 'E00098',
	'profile_passwd_illegal' => 'E00098',
	'profile_username_protect' => 'E00098',
	'profile_username_illegal' => 'E00098',
	'profile_username_duplicate' => 'E00098',
	'profile_required_info_invalid' => 'E00098',
	'register_ctrl' => 'E00098',
	'register_flood_ctrl' => 'E00098',
	'profile_uid_duplicate' => 'E00098',
	'register_manual_verify' => 'E00000',
	'register_email_verify' => 'E00000',
	'register_email_send_succeed' => 'E00000',
	'submit_secqaa_invalid' => 'E00098',
	'submit_seccode_invalid' => 'E00098',
	'submit_invalid' => 'E00098',

	'favorite_cannot_favorite' => 'E00098',
	'favorite_repeat' => 'E00098',
	'favorite_do_success' => 'E00000',
	'favorite_does_not_exist' => 'E000098',

	'thread_rate_succeed' => 'E00000',
	'thread_rate_range_invalid' => 'E00098',
	'thread_rate_ctrl' => 'E00098',
	'thread_rate_range_self_invalid' => 'E00098',
    'thread_rate_duplicate' => 'E00098',
    'thread_rate_banned' => 'E00098',
    'thread_rate_anonymous' => 'E00098',
    'thread_rate_member_invalid' => 'E00098',
    'thread_rate_timelimit' => 'E00098',
    'rate_post_error' => 'E00098',
    'thread_rate_moderator_invalid' => 'E00098',
    'search_forum_closed' => 'E00098',
    'search_ctrl' => 'E00098',
    'search_forum_invalid' => 'E00098',
    'search_toomany' => 'E00098',
    'search_id_invalid' => 'E00098',

    'message_can_not_send_2' => 'E10001',
    'no_privilege_sendpm' => 'E10002',
    'is_blacklist' => 'E10003',
    'unable_to_send_air_news' => 'E10004',
    'message_can_not_send_onlyfriend' => 'E10005',
    'message_bad_touid' => 'E10006',
    'message_can_not_send' => 'E10007',
    'view_password_error' => 'E10008',
);
