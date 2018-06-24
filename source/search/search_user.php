<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: spacecp_search.php 28292 2012-02-27 07:23:14Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
/*COMMENTS
//增加分页参数 20121125 ep
*/
$perpage = 20;
$page = max(1, intval($_GET['page']));
$start = ($page - 1) * $perpage;
/*COMMENTS END*/
$myfields = array('uid','gender','birthyear','birthmonth','birthday','birthprovince','birthcity','resideprovince','residecity', 'residedist', 'residecommunity');

loadcache('profilesetting');
$fields = array();
foreach ($_G['cache']['profilesetting'] as $key => $value) {
	if($value['title'] && $value['available'] && $value['allowsearch'] && !in_array($key, $myfields)) {
		$fields[$value['fieldid']] = $value;
	}
}

$nowy = dgmdate($_G['timestamp'], 'Y');
$_GET = daddslashes($_GET);

	$_GET['searchkey'] = $_GET['srchtxt'];
	$_GET['searchsubmit'] = $_GET['searchmode'] = 1;
	$wherearr = $fromarr = $uidjoin = array();
	$fsql = '';

	$fromarr['member'] = DB::table('common_member').' s';
	
	if($searchkey = stripsearchkey($_GET['searchkey'])) {
		$wherearr[] = 's.'.DB::field('username', '%'.$searchkey.'%','like');
		$searchkey = dhtmlspecialchars($searchkey);
	} else {
		foreach (array('uid','username','videophotostatus','avatarstatus') as $value) {
			if($_GET[$value]) {
				if($value == 'username' && empty($_GET['precision'])) {
					$_GET[$value] = stripsearchkey($_GET[$value]);
					$wherearr[] = 's.'.DB::field($value, '%'.$_GET[$value].'%', 'like');
				} else {
					$wherearr[] = 's.'.DB::field($value, $_GET[$value]);
				}
			}
		}
	}
	//去掉离职用户
//	$wherearr[] = 's.'.DB::field('groupid', '38','<>');

	$havefield = 0;
	foreach ($myfields as $fkey) {
		$_GET[$fkey] = trim($_GET[$fkey]);
		if($_GET[$fkey]) {
			$havefield = 1;
			$wherearr[] = 'sf.'.DB::field($fkey, $_GET[$fkey]);
		}
	}

	foreach ($fields as $fkey => $fvalue) {
		$_GET['field_'.$fkey] = empty($_GET['field_'.$fkey])?'':stripsearchkey($_GET['field_'.$fkey]);
		if($_GET['field_'.$fkey]) {
			$havefield = 1;
			$wherearr[] = 'sf.'.DB::field($fkey, '%'.$_GET['field_'.$fkey].'%', 'like');
		}
	}

	if($havefield || $startage || $endage) {
		$fromarr['profile'] = DB::table('common_member_profile').' sf';
		$wherearr['profile'] = "sf.uid=s.uid";
	}
	$list = array();

	if($wherearr) {
		$space['friends'] = array();
		$query = C::t('home_friend')->fetch_all_by_uid($_G['uid'], 0, 0);
		foreach($query as $value) {
			$space['friends'][$value['fuid']] = $value['fuid'];
		}

		foreach(C::t('common_member')->fetch_all_for_spacecp_search($wherearr, $fromarr, 0, 100) as $value) {
			$value['isfriend'] = ($value['uid']==$space['uid'] || $space['friends'][$value['uid']])?1:0;
			$list[$value['uid']] = $value;
		}
		$follows = C::t('home_follow')->fetch_all_by_uid_followuid($_G['uid'], array_keys($list));
		foreach($list as $uid => $value) {
			$list[$uid]['follow'] = isset($follows[$uid]) ? 1 : 0;
		}	
	}
var_dump($list);
$pagetotal = 1;
$result = array();
foreach($list as $val)
{
	$tmp = array();
	$tmp['uid'] = $val['uid'];
	$tmp['username'] = $val['username'];
	$tmp['avatar'] = avatar($val['uid'],'small',true);
	$tmp['following'] = $val['following'];
	$tmp['follower'] = $val['follower'];
	$tmp['follow'] = $val['follow'];
	$tmp['followme'] = $val['followme'];
	$tmp['recentnote'] = $val['recentnote'];
	$result[] = $tmp;

}

if(empty($result))
{
	BfdApp::display_result('result_is_null');
}
BfdApp::display_result('get_success',$result,'',$pagetotal);
