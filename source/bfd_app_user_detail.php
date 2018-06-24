<?php

if(empty($_G['uid']) && in_array($_GET['do'], array('thread', 'trade', 'poll', 'activity', 'debate', 'reward'))) {
	showmessage('login_before_enter_home', null, array(), array('showmsg' => true, 'login' => 1));
}
$uid = empty($_GET['uid']) ? 0 : intval($_GET['uid']);

$member = array();
if($_GET['username']) {
	$member = C::t('common_member')->fetch_by_username($_GET['username']);
	if(empty($member) && !($member = C::t('common_member_archive')->fetch_by_username($_GET['username']))) {
		showmessage('space_does_not_exist');
	}
	$uid = $member['uid'];
	$member['self'] = $uid == $_G['uid'] ? 1 : 0;
}

if(empty($uid) || in_array($do, array('notice', 'pm'))) $uid = $_G['uid'];

if($uid && empty($member)) {
	$space = getuserbyuid($uid, 1);
	if(empty($space)) {
		showmessage('space_does_not_exist');
	}
} else {
	$space = &$member;
}

if(empty($space)) {
		showmessage('login_before_enter_home', null, array(), array('showmsg' => true, 'login' => 1));
} else {

	if($space['status'] == -1 && $_G['adminid'] != 1) {
		showmessage('space_has_been_locked');
	}

	if(in_array($space['groupid'], array(4, 5, 6)) && ($_G['adminid'] != 1 && $space['uid'] != $_G['uid'])) {
		$_GET['do'] = $do = 'profile';
	}

}

require_once libfile('function/spacecp');

space_merge($space, 'count');
space_merge($space, 'field_home');
space_merge($space, 'field_forum');
space_merge($space, 'profile');
space_merge($space, 'status');


$space['admingroup'] = $_G['cache']['usergroups'][$space['adminid']];
$space['admingroup']['icon'] = g_icon($space['adminid'], 1);

$space['group'] = $_G['cache']['usergroups'][$space['groupid']];
$space['group']['icon'] = g_icon($space['groupid'], 1);

if($space['extgroupids']) {
	$newgroup = array();
	$e_ids = explode(',', $space['extgroupids']);
	foreach ($e_ids as $e_id) {
		$newgroup[] = $_G['cache']['usergroups'][$e_id]['grouptitle'];
	}
	$space['extgroupids'] = implode(',', $newgroup);
}

$space['regdate'] = dgmdate($space['regdate']);
if($space['lastvisit']) $space['lastvisit'] = dgmdate($space['lastvisit']);
if($space['lastactivity']) {
	$space['lastactivitydb'] = $space['lastactivity'];
	$space['lastactivity'] = dgmdate($space['lastactivity']);
}
if($space['lastpost']) $space['lastpost'] = dgmdate($space['lastpost']);
if($space['lastsendmail']) $space['lastsendmail'] = dgmdate($space['lastsendmail']);


if($_G['uid'] == $space['uid'] || $_G['group']['allowviewip']) {
	require_once libfile('function/misc');
	$space['regip_loc'] = convertip($space['regip']);
	$space['lastip_loc'] = convertip($space['lastip']);
}

$space['buyerrank'] = 0;
if($space['buyercredit']){
	foreach($_G['setting']['ec_credit']['rank'] AS $level => $credit) {
		if($space['buyercredit'] <= $credit) {
			$space['buyerrank'] = $level;
			break;
		}
	}
}

$space['sellerrank'] = 0;
if($space['sellercredit']){
	foreach($_G['setting']['ec_credit']['rank'] AS $level => $credit) {
		if($space['sellercredit'] <= $credit) {
			$space['sellerrank'] = $level;
			break;
		}
	}
}

$space['attachsize'] = formatsize($space['attachsize']);

$space['timeoffset'] = empty($space['timeoffset']) ? '9999' : $space['timeoffset'];
if(strtotime($space['regdate']) + $space['oltime'] * 3600 > TIMESTAMP) {
	$space['oltime'] = 0;
}
require_once libfile('function/friend');
$isfriend = friend_check($space['uid'], 1);

loadcache('profilesetting');
include_once libfile('function/profile');
$profiles = array();
$privacy = $space['privacy']['profile'] ? $space['privacy']['profile'] : array();

if($_G['setting']['verify']['enabled']) {
	space_merge($space, 'verify');
}
foreach($_G['cache']['profilesetting'] as $fieldid => $field) {
	if(!$field['available'] || in_array($fieldid, array('birthprovince', 'birthdist', 'birthcommunity', 'resideprovince', 'residedist', 'residecommunity','site'))) {
			continue;
	}

	if(
		$field['available'] && (strlen($space[$fieldid]) > 0 || ($fieldid == 'birthcity' && strlen($space['birthprovince']) || $fieldid == 'residecity' && strlen($space['resideprovince']))) &&
		($space['self'] || empty($privacy[$fieldid]) || ($isfriend && $privacy[$fieldid] == 1)) &&
		(!$_G['inajax'] && !$field['invisible'] || $_G['inajax'] && $field['showincard'])
	) {
		$val = profile_show($fieldid, $space);
		if($val !== false) {
			if($fieldid == 'realname' && $_G['uid'] != $space['uid'] && !ckrealname(1)) {
				continue;
			}
			if($val == '')  $val = '-';
			$profiles[] = array('title'=>$field['title'], 'value'=>strip_tags($val));
		}
	}
}
/*
$count = C::t('forum_moderator')->count_by_uid($space['uid']);
if($count) {
	foreach(C::t('forum_moderator')->fetch_all_by_uid($space['uid']) as $result) {
		$moderatefids[] = $result['fid'];
	}
	$query = C::t('forum_forum')->fetch_all_info_by_fids($moderatefids);
	foreach($query as $result) {
		$manage_forum[$result['fid']] = $result['name'];
	}
}
*/
/*
if(!$_G['inajax'] && $_G['setting']['groupstatus']) {
	$gorupcount = C::t('forum_groupuser')->fetch_all_group_for_user($space['uid'], 1);
	if($groupcount > 0) {
		$fids = C::t('forum_groupuser')->fetch_all_fid_by_uids($space['uid']);
		$usergrouplist = C::t('forum_forum')->fetch_all_info_by_fids($fids);
	}
}
*/

if($space['medals']) {
        loadcache('medals');
        foreach($space['medals'] = explode("\t", $space['medals']) as $key => $medalid) {
                list($medalid, $medalexpiration) = explode("|", $medalid);
                if(isset($_G['cache']['medals'][$medalid]) && (!$medalexpiration || $medalexpiration > TIMESTAMP)) {
                        $space['medals'][$key] = $_G['cache']['medals'][$medalid];
                        $space['medals'][$key]['medalid'] = $medalid;
                } else {
                        unset($space['medals'][$key]);
                }
        }
}
//头像
/*
foreach($_G as $key=>$val)
{
	if(isset($val['usergroups']));
	echo $key."\n";
}
exit;
*/
$avatar = avatar($uid,'middle',true);

$data = array();
$data['uid']    = $space['uid'];
$data['avatar'] = $avatar;
$data['username'] = $space['username'];
//$data['email']    = $space['email'];
$data['groupid']  = $space['groupid'];//等级
$data['groupname']  =  strip_tags($_G['cache']['usergroups'][$space['groupid']]['grouptitle']);
$extcredits = array();
foreach($_G['setting']['extcredits'] as $key =>$val)
{
	$extcredits[] = array('title'=>$val['title'],'value'=>$space['extcredits'.$key]);
}
$data['extcredits'] = $extcredits;
$data['profiles'] = $profiles;
$medals = $space['medals'];
foreach($medals as &$val)
{
		$val['image'] = BFD_APP_DATA_URL_PRE.'static/image/common/'.$val['image'];
}
$data['medals']   = array_values($medals);//勋章
$data['sightml'] = strip_tags($space['sightml']);//签名

BfdApp::display_result('get_success',$data);
?>
