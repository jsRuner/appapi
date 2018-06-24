<?php
/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: home_follow.php 30281 2012-05-18 03:43:42Z liulanbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once libfile('function/home');
$dos = array('follower', 'following','view');
$do = (!empty($_GET['do']) && in_array($_GET['do'], $dos)) ? $_GET['do'] : (!$_GET['uid'] ? 'feed' : 'view');

$page = empty($_GET['page']) ? 1 : intval($_GET['page']);
if($page<1) $page=1;
$perpage = BFD_APP_USER_FOLLOW_PAGESIZE;
$pagesize = intval($_GET['pagesize']);
if($pagesize > 0)
{
	$perpage = $pagesize;
}
$start = ($page-1)*$perpage;
$pagetotal  = 0;
$uid = $_GET['uid'] ? $_GET['uid'] : $_G['uid'];
$viewself = $uid == $_G['uid'] ? true : false;
$space = $viewself ? $_G['member'] : getuserbyuid($uid, 1);
if(empty($space)) {
	BfdApp::display_result('follow_visituser_not_exist');
} elseif(in_array($space['groupid'], array(4, 5, 6)) && ($_G['adminid'] != 1 && $space['uid'] != $_G['uid'])) {
	BfdApp::display_result('follow_view_banned');
}
space_merge($space, 'count');
space_merge($space, 'profile');
space_merge($space, 'field_home');

if($viewself) {
/*COMMENTS
//增加self判断参数 20121125 ep
*/	
	$space['self']=1;
/*COMMENTS END*/
	$showguide = false;
} else {

	$flag = C::t('home_follow')->fetch_status_by_uid_followuid($_G['uid'], $uid);
}
$showrecommend = true;
$archiver = $primary = 1;
$followerlist = array();
$space['bio'] = cutstr($space['bio'], 200);
$lastviewtime = 0;
$count = 0;
if($do == 'follower') {

	$count = C::t('home_follow')->count_follow_user($uid, 1);
	if($count) {
		$list = C::t('home_follow')->fetch_all_follower_by_uid($uid, $start, $perpage);
	}
/*COMMENTS
//获取用户扩展用户组信息用于verify显示 20121125 ep
*/
///	$memberlist = C::t('common_member')->fetch_all(array_keys($list));
} elseif($do == 'following') {
	$count = C::t('home_follow')->count_follow_user($uid);
	if($count) {
		$status = $_GET['status'] ? 1 : 0;
		$list = C::t('home_follow')->fetch_all_following_by_uid($uid, $status, $start, $perpage);
	}
/*COMMENTS
//获取用户扩展用户组信息用于verify显示 20121125 ep
*/
//	$memberlist = C::t('common_member')->fetch_all(array_keys($list));
/*COMMENTS END*/
}

$pagetotal = ceil($count/$perpage);

if(($do == 'follower' || $do == 'following') && $list) {
	$uids = array_keys($list);
	$fieldhome = C::t('common_member_field_home')->fetch_all($uids);
	foreach($fieldhome as $fuid => $val) {
		$list[$fuid]['recentnote'] = $val['recentnote'];
	}
//	$memberinfo = C::t('common_member_count')->fetch_all($uids);
//	$memberprofile = C::t('common_member_profile')->fetch_all($uids);

	if(!$viewself) {
		$myfollow = C::t('home_follow')->fetch_all_by_uid_followuid($_G['uid'], $uids);
		foreach($uids as $muid) {
			$list[$muid]['mutual'] = 0;
			if(!empty($myfollow[$muid])) {
				$list[$muid]['mutual'] = $myfollow[$muid]['mutual'] ? 1 : -1;
			}

		}
	}
//	$specialfollow = C::t('home_follow')->fetch_all_following_by_uid($uid, 1, 10);
}
foreach($list as $key=>$val)
{
	$list[$key]['avatar']= avatar($key,'small',true);
	$list[$key]['dateline'] = date('Y-m-d H:i:s',$val['dateline']);
}

BfdApp::display_result('get_success',$list,'',$pagetotal);

?>
