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
$uid = empty($_GET['uid']) ? 0 : intval($_GET['uid']);
if(empty($uid))
{
	$uid = $_G['uid'];
}
$member = array();
$do = $_GET['do'] = 'home';


$space = getuserbyuid($uid, 1);
if(empty($space)) {
	BfdApp::display_result('space_does_not_exist');
}


if($space['status'] == -1 && $_G['adminid'] != 1) {
	BfdApp::display_result('space_has_been_locked');
}

if(!$space['self'] && $_GET['view'] != 'eccredit' && $_GET['view'] != 'admin') $_GET['view'] = 'me';

$diymode = 0;

$seccodecheck = $_G['setting']['seccodestatus'] & 4;
$secqaacheck = $_G['setting']['secqaa']['status'] & 2;
if($do != 'index') {
	$_G['disabledwidthauto'] = 0;
}



require_once libfile('function/feed');

if(empty($_G['setting']['feedhotday'])) {
	$_G['setting']['feedhotday'] = 2;
}

$minhot = $_G['setting']['feedhotmin']<1?3:$_G['setting']['feedhotmin'];

if(empty($_GET['view'])) {
	if($space['self']) {
		$_GET['view'] = 'me';
	} else {
		$_GET['view'] = 'all';
	}
} elseif(!in_array($_GET['view'], array('we', 'me', 'all', 'app'))) {
	$_GET['view'] = 'me';
}
if(empty($_GET['order'])) {
	$_GET['order'] = 'dateline';
}

$perpage = BFD_APP_USER_FEED_PAGESIZE;
$page = intval($_GET['page']);
if($page < 1 || !$page) $page = 1;
$pagesize = intval($_GET['pagesize']);
if($pagesize > 0)
{
	$perpage = $pagesize;
}
$start = ($page-1)*$perpage;

//信息流ajax请求在此处理并返回结果 20121125 ep
//require_once libfile('lib/followfeedall_helper');
//require_once libfile('lib/space_helper');


$_G['home_today'] = $_G['timestamp'] - ($_G['timestamp'] + $_G['setting']['timeoffset'] * 3600) % 86400;

$gets = array(
	'mod' => 'space',
	'uid' => $space['uid'],
	'do' => 'home',
	'view' => $_GET['view'],
	'order' => $_GET['order'],
	'appid' => $_GET['appid'],
	'type' => $_GET['type'],
	'icon' => $_GET['icon']
);
//$followfeedall_tmp = C::t('home_follow_feed')->fetch_all_by_uid($uid);
$uids = array($uid);
$icon = '';
$hot = '';
$findex = '';
$appid = '';
$query = C::t('home_feed')->fetch_all_by_search(1, $uids, $icon, '', '', '', $hot, '', $start, $perpage, $findex, $appid);
$feed_list_my = array();
if(!empty($query))
{
	$hash_datas = array();
	$more_list = array();
	$uid_feedcount = array();

	foreach($query as $value) {
		if(!isset($hotlist[$value['feedid']]) && !isset($hotlist_all[$value['feedid']]) && ckfriend($value['uid'], $value['friend'], $value['target_ids'])) {
			$value = mkfeed($value);
			if(ckicon_uid($value)) {

				if($value['dateline']>=$_G['home_today']) {
					$dkey = 'today';
				} elseif ($value['dateline']>=$_G['home_today']-3600*24) {
					$dkey = 'yesterday';
				} else {
					$dkey = dgmdate($value['dateline'], 'Y-m-d');
				}

				$maxshownum = 3;
				if(empty($value['uid'])) $maxshownum = 10;

				if(empty($value['hash_data'])) {
					if(empty($feed_users[$dkey][$value['uid']])) $feed_users[$dkey][$value['uid']] = $value;
					if(empty($uid_feedcount[$dkey][$value['uid']])) $uid_feedcount[$dkey][$value['uid']] = 0;

					$uid_feedcount[$dkey][$value['uid']]++;

					if($uid_feedcount[$dkey][$value['uid']]>$maxshownum) {
						$more_list[$dkey][$value['uid']][] = $value;
					} else {
						$feed_list[$dkey][$value['uid']][] = $value;
						$feed_list_my[] = $value;
					}

				} elseif(empty($hash_datas[$value['hash_data']])) {
					$hash_datas[$value['hash_data']] = 1;
					if(empty($feed_users[$dkey][$value['uid']])) $feed_users[$dkey][$value['uid']] = $value;
					if(empty($uid_feedcount[$dkey][$value['uid']])) $uid_feedcount[$dkey][$value['uid']] = 0;


					$uid_feedcount[$dkey][$value['uid']] ++;

					if($uid_feedcount[$dkey][$value['uid']]>$maxshownum) {
						$more_list[$dkey][$value['uid']][] = $value;
					} else {
						$feed_list[$dkey][$value['uid']][$value['hash_data']] = $value;
						$feed_list_my[] = $value;
					}

				} else {
					$user_list[$value['hash_data']][] = "<a href=\"home.php?mod=space&uid=$value[uid]\">$value[username]</a>";
				}


			} else {
				$filtercount++;
				$filter_list[] = $value;
			}
		}
		$count++;
	}
}
$followfeedall_tmp['rows'] = $feed_list_my;
/*
if($space['self']){
	//根据view获取feed，me是authoruid和受众都是me的，we是我关注的人发给自己的，随便看看是我和我关注的人总和
	if($_GET['view']=='me'){
		$followfeedall_tmp = C::t('home_follow_feed_all')->fetch_all_myonly($_G['uid'],$start,$perpage);
	//	$followfeedall_tmp = lib_followfeedall_helper::get_stream_myonly($_G['uid'],$page);
	}else if($_GET['view']=='we'){
		//他们个人的，同时group是我能够看的
	//	$followfeedall_tmp = lib_followfeedall_helper::get_stream_myfollowonly($_G['uid'],$page);
		$followfeedall_tmp = C::t('home_follow_feed_all')->fetch_all_myfollowonly($_G['uid'],$start,$perpage);
	}else{
		//全部个人的，同时group是我能够看的
	//	$followfeedall_tmp = lib_followfeedall_helper::get_stream_myany($_G['uid'],$page);
		$followfeedall_tmp = C::t('home_follow_feed_all')->fetch_all_myany($_G['uid'],$start,$perpage);
	}
	$viewself = true;
//仅看此人动态 
}else{
	//他个人，同时group是我能看的
//	$followfeedall_tmp = lib_followfeedall_helper::get_stream_myfollowonlyone($space['uid'],$page);
	$followfeedall_tmp = C::t('home_follow_feed_all')->fetch_all_myfollowonlyone($space['uid'],$start,$perpage);
	$viewself = false;
	
	//显示follow状态
	$flag = C::t('home_follow')->fetch_status_by_uid_followuid($_G['uid'], $uid);
}
$have_after = &$followfeedall_tmp['have_after'];

//space信息
$pinfo = lib_space_helper::getProfileInfo($space);
*/
//勋章
space_merge($space, 'field_forum');
space_merge($space, 'count');
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

$result = array('userinfo'=>array(),'feedlist'=>array());
$result['userinfo']['uid'] = $space['uid'];
//$result['userinfo']['avatar'] = 'http://'.$_SERVER['HTTP_HOST'].'/uc_server/avatar.php?uid='.$space['uid'].'&size=middle';//avatar($space['uid'],'middle',true);
$result['userinfo']['avatar'] = avatar($space['uid'],'middle',true);
$result['userinfo']['email'] = $space['email'];
$result['userinfo']['username'] = $space['username'];
$result['userinfo']['groupid'] = $space['groupid'];
$result['userinfo']['groupname'] = $_G['cache']['usergroups'][$space['groupid']]['grouptitle'];
//$result['userinfo']['group_num'] = lib_space_helper::getGroupNumbers($space['uid']);
$result['userinfo']['extcredits1'] = $space['extcredits1'];//蓝宝石
$result['userinfo']['extcredits2'] = $space['extcredits2'];//积分
$result['userinfo']['extcreits1_name'] = $_G['setting']['extcredits']['1']['title'];
$result['userinfo']['extcreits2_name'] = $_G['setting']['extcredits']['2']['title'];
$result['userinfo']['newpm'] = $space['newpm'];
$result['userinfo']['newprompt'] = $space['newprompt'];
$result['userinfo']['posts'] = $space['posts'];
$result['userinfo']['threads'] = $space['threads'];
$result['userinfo']['follower'] = $space['follower'];
$result['userinfo']['following'] = $space['following'];
$result['userinfo']['gender'] = $space['gender'];
$result['userinfo']['constellation'] = $space['constellation'];
$result['userinfo']['field2'] = $space['field2'];//部门
$result['userinfo']['medals'] = $space['medals'];//勋章
$result['userinfo']['bloodtype'] = $space['bloodtype'];//血型
$result['userinfo']['sightml'] = strip_tags($space['sightml']);//签名
$followed = '';
if($_G['uid'] != $space['uid'])
{
	//$followed = C::t('home_follow')->fetch_status_by_uid_followuid($_G['uid'], $space['uid']);
	require_once libfile('function/friend');
	$is_friend = friend_check($space['uid']);
	if($is_friend)
	{
		$result['userinfo']['isfollowed'] = '2';//未关注
		$result['userinfo']['isfriend'] = '1';//未关注
	}
	else
	{
		$result['userinfo']['isfollowed'] = '0';//未关注
		$result['userinfo']['isfriend'] = '0';//未关注
	}
/*	elseif($followed[$_G['uid']]['mutual'])
	{
		$result['userinfo']['isfollowed'] = '2';//已互相关注
	}
	elseif(isset($followed[$space['uid']]))
	{
		$result['userinfo']['isfollowed'] = '3';//被关注
	}
	else
	{
		$result['userinfo']['isfollowed'] = '1';//已关注
	}*/
}
else
{
	$result['userinfo']['isfollowed'] = '-1';//查看本人，关注状态为空
	$result['userinfo']['isfriend'] = '-1';//查看本人，关注状态为空
}


if(!empty($followfeedall_tmp['rows']))
{
	foreach($followfeedall_tmp['rows'] as $key=>$val)
	{
		if($val['icon'] == 'blog')
		{
			continue;
		}
		$tmparr = array();
		$tmparr['authoruid'] = $val['uid'];
		$tmparr['authorusername'] = $val['username'];
		$tmparr['avatar'] = avatar($val['uid'],'small',true);
		$tmparr['fid'] = 0;
		$tmparr['fname'] = '';
		$tmparr['key_id'] = $val['id'];
		//$tmparr['dateline'] = html_entity_decode(strip_tags($val['dateline']),ENT_COMPAT | ENT_HTML401,'UTF-8');
		$tmparr['dateline'] = date('Y-m-d H:i',$val['dateline']);
		$tmparr['type'] = $val['icon'];//feed类型 thread post
		if($tmparr['type'] == 'thread')
		{
			$tmparr['tid'] = $val['id'];
		}
		else if($tmparr['type'] == 'reply' && !empty($val['param1']))
		{
			$tmparr['tid'] = $val['param1'];
		}
		else
		{
			$tmparr['tid'] = '';
		}
		$tmparr['action'] = '';
		$tmparr['dbdateline'] = $val['dateline'];
		$tmparr['note'] = $val['title_template'];
		$tmparr['preview'] = $val['body_template'];
		fix_content($tmparr['note']);
		fix_content($tmparr['preview']);
		$tmparr['attachments'] = array();
		$result['feedlist'][] = $tmparr;
	}
}
$result['have_after'] = $have_after;

BfdApp::display_result('get_success',$result);

function fix_content(&$msg)
{	
	$delete = array(
		'/<div class="prevauthor">.*<\/div>\r\n/Us',
		'/\t/',
	);
	$replace = array(
		'在 <a href="forum.php?mod=group&fid=%fid%">%forum_name%</a> 小组 ',
	);
	$replace_dist = array(
		'',
	);
	$msg = preg_replace($delete,'',$msg);
	$msg = str_replace($replace,$replace_dist,$msg);
	

	$msg = strip_tags($msg,'<a><img>');	
	$attachments = array();
	if(strpos($msg,'<img ') !== false)
	{
		$attachments = fix_img($msg);	
	}
	if(strpos($msg,'<a ') !== false)
	{
		fix_atag($msg);
	}
	return $attachments;
}

function fix_img( &$msg )
{
	global $bfd_app_smiley_map;
	$pattern = '/<img .*src="([^"]*)".*[\/]?>/iUs';
	$flag = preg_match_all($pattern,$msg,$imgall);
	$attachments = array();
	$attachkey = 0;
	if(!empty($imgall[1]))
	{
		$findarr = array();
		$replacearr = array();
		foreach($imgall[1] as $key=>$val)
		{
			$findarr[] = $imgall[0][$key];
			if(isset($bfd_app_smiley_map[$val]))	
			{
				$replacearr[] = $bfd_app_smiley_map[$val];
			}
			else if (strpos($val,'data/attachment/forum') !== false)
			{
				$attachtmp = @getimagesize($_SERVER['DOCUMENT_ROOT'].'/'.$val);
				if($attachtmp)
				{
					$attach = array();
					$attach['width'] = $attachtmp[0];
					$attach['height'] = $attachtmp[1];
					$attach['mime'] = $attachtmp['mime'];
					$attach['url'] = 'http://'.$_SERVER['HTTP_HOST'].'/'.$val;
					$replacearr[] = '<attach>'.$attachkey.'</attach>';		
					$attachments[$attachkey] = $attach;
				}
				else
				{
					$replacearr[] = '';		
				}
				$attachkey ++;
			}
			else
			{
				$replacearr[] = '';		
			}
		}
		$msg = str_replace($findarr,$replacearr,$msg);
	}
	return $attachments;
}

function fix_atag( &$msg )
{
	$pattern = '/<a .*href="([^"]*)"[^>]*>([^<]*)<\/a>/iUs';
	$homepattern = '/^home\.php\?mod=space/';
	$threadpattern = '/^forum\.php\?mod=viewthread/';
	$postpattern = '/^forum\.php\?mod=redirect&goto=findpost/';
	$grouppattern = '/^forum\.php\?mod=group/';
	$serverhost  = 'http://'.$_SERVER['HTTP_HOST'].'/';
	$atags = array();
	$flag = preg_match_all($pattern,$msg,$atags);
	if($flag)
	{
		$findarr = array();
		$replacearr = array();
		foreach($atags[1] as $key=>$val)
		{
			$replacestr = '';
			$val = str_replace($serverhost,'',$val);
			if(preg_match($homepattern,$val))
			{
				$tmppattern = '/uid=([0-9]+)/';
				$flag1 = preg_match($tmppattern,$val,$uidarray);
				if($flag1)
				{
					$uid = $uidarray[1];
					if($uid)
					{
						$replacestr = '<a type="1" href="'.$uid.'">'.$atags[2][$key].'</a>';
					}
				}
			}
			else if(preg_match($threadpattern,$val))
			{
				$tmppattern = '/tid=([0-9]+)/';
				$flag2 = preg_match($tmppattern,$val,$tidarray);
				if($flag2)
				{
					$tid = $tidarray[1];
					if($tid)
					{
						$replacestr = '<a type="2" href="'.$tid.'">'.$atags[2][$key].'</a>';
					}
				}
			}
			else if(preg_match($postpattern,$val))
			{
				$tmppattern = '/ptid=([0-9]+)/';
				$flag2 = preg_match($tmppattern,$val,$tidarray);
				if($flag2)
				{
					$tid = $tidarray[1];
					if($tid)
					{
						$replacestr = '<a type="2" href="'.$tid.'">'.$atags[2][$key].'</a>';
					}
				}
			}
			else if(preg_match($grouppattern,$val))
			{
				$tmppattern = '/fid=([0-9]+)/';
				$flag2 = preg_match($tmppattern,$val,$fidarray);
				if($flag2)
				{
					$fid = $fidarray[1];
					if($fid)
					{
						$replacestr = '<a type="3" href="'.$fid.'">'.$atags[2][$key].'</a>';
					}
				}
			}
			else
			{
				$replacestr = '<a type="0" href="'.$val.'">'.$atags[2][$key].'</a>';
			}
			if($replacestr)
			{
				$findarr[] = $atags[0][$key];
				$replacearr[] = $replacestr;
			}
		}
		$msg = str_replace($findarr,$replacearr,$msg);
	}
}
?>
