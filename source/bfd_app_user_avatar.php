<?php
/**
 * @filename : bfd_app_index.php
 * @date : 2013-03-05
 * @desc :
	公告列表
 **/
if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

$uid = intval($_GET['uid']);
if($uid < 1)
{
	$uid = $_G['uid'];
}
$size = trim($_GET['size']); 
if(!in_array($size,array('big','middle','small')))
{
	$size = 'middle';
}
$avatar = avatar($uid,$size,true);

$data['uid'] = $uid;
$data['avatar']   = $avatar;//勋章

BfdApp::display_result('get_success',$data);
