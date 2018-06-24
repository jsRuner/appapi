<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_notice.php 30269 2012-05-18 01:58:22Z liulanbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
loaducenter();
if(!$_G['uid'])
{
	BfdApp::display_result('user_no_login');
}
$perpage = 20;

$page = empty($_GET['page'])?0:intval($_GET['page']);
if($page<1) $page = 1;
$pagesize = intval($_GET['pagesize']);
if($pagesize > 0)
{
	$perpage = $pagesize;
}
$start = ($page-1)*$perpage;
$totalpage = 1;

$list = array();
$mynotice = $count = 0;
$multi = '';

$view = (!empty($_GET['view']) && in_array($_GET['view'], array('userapp','check')))?$_GET['view']:'notice';
$type = trim($_GET['type']);
if(!in_array($type,array('post','at')))
{
	$type = '';
}

if($view == 'userapp') {

} else {
	
	if(!empty($_GET['ignore'])) {
		C::t('home_notification')->ignore($_G['uid']);
	}
/*
	foreach (array('wall', 'piccomment', 'blogcomment', 'clickblog', 'clickpic', 'sharecomment', 'doing', 'friend', 'credit', 'bbs', 'system', 'thread', 'task', 'group') as $key) {
		$noticetypes[$key] = lang('notification', "type_$key");
	}

	$isread = in_array($_GET['isread'], array(0, 1)) ? intval($_GET['isread']) : 0;
	$type = trim($_GET['type']);
	$wherearr = array();
	if(!empty($type)) {
		$wherearr[] = "`type`='$type'";
	}
	$new = !$isread;
	$wherearr[] = "`new`='$new'";

	$sql = ' AND '.implode(' AND ', $wherearr);
*/	
	
	$newnotify = false;
/*COMMENTS
//扩展查询函数 20121125 ep

*/
	$typestr = '';
	$category = '';
	if(empty($type))
	{
		$typestr = " `type` not in('post','blogcomment')";
	}
	elseif('post' == $type)
	{
		$category = 'mypost';
		$typestr = " `type` in ('post','blogcomment')";
	}
	else
	{
		$typestr = " `type`='{$type}'";
	}
	$count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_notification')." WHERE uid={$_G['uid']} AND {$typestr}");
/*COMMENTS END*/
	if($count) {
		$noticelist = DB::fetch_all("SELECT * FROM ".DB::table('home_notification')." WHERE uid={$_G['uid']} AND {$typestr} ORDER BY new DESC, dateline DESC Limit {$start},{$perpage}");
		foreach($noticelist as $value){
			if($value['new']) {
				$newnotify = true;
			}
			if($value['from_num'] > 0) $value['from_num'] = $value['from_num'] - 1;
			if(strpos($value['note'],'<a ') !== false)
			{
				fix_atag($value['note']);
			}
			$value['note'] = strip_tags($value['note'],'<a>');
			if(!empty($value['authorid']))
			{
				$value['authoravatar'] = avatar($value['authorid'],'middle',true);
			}
			else
			{
				$value['authoravatar'] = '';
			}
			$value['note'] = BfdApp::bfd_html_entity_decode($value['note']);
			if(empty($value['note']))
			{
				$value['note'] = '';
			}
			$list[] = $value;
		}

		$totalpage = ceil($count/$perpage);
	}
	if($newnotify) {
		if($_G['setting']['version'] == 'X2.5')
		{
			C::t('home_notification')->ignore($_G['uid'], true, true);
		}
		else
		{
			C::t('home_notification')->ignore($_G['uid'],$type,$category, true, true);
		}
		if($_G['setting']['cloud_status']) {
			$noticeService = Cloud::loadClass('Service_Client_Notification');
			$noticeService->setNoticeFlag($_G['uid'], TIMESTAMP);
		}
	}

	if($space['newprompt']) {
		C::t('common_member')->update($_G['uid'], array('newprompt'=>0));
	}

}

//通知数
if($page == 1)
{
    $typestr = " `type` not in('post','blogcomment','at','follower')";
    $count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_notification')." WHERE uid={$_G['uid']} AND `new`='1' AND {$typestr}");
    $result['notpost'] = intval($count);

    //回复我的数
    $typestr = " `type` in ('post','blogcomment')";;
    $count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_notification')." WHERE uid={$_G['uid']} AND `new`='1' AND {$typestr}");
    $result['post'] = intval($count);


    //公共消息数
    $announcepm = 0;
    foreach(C::t('common_member_grouppm')->fetch_all_by_uid($_G['uid'], 1) as $gpmid => $gpuser) 
    {
        $gpmstatus[$gpmid] = $gpuser['status'];
        if($gpuser['status'] == 0) {
            $announcepm ++;
        }
    }

    //私人消息数
    $newpmarr = uc_pm_checknew($_G['uid'], 1);
    $newpm = $newpmarr['newpm'];
    $result['pm'] = $announcepm + $newpm;
}
$result['pagetotal'] = $totalpage;

BfdApp::display_result('get_success',$list,'',$result);


function fix_atag( &$msg )
{
        $pattern = '/<a .*href="([^"]*)"[^>]*>([^<]*)<\/a>/iUs';
        $homepattern = '/^home\.php\?mod=(space|spacecp)&/';
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
																								$tmppattern = '/ac=friend&op=add/';
																								$flagfriend = preg_match($tmppattern,$val,$uidarray);
																								if($flagfriend)
																								{
																											$replacestr = '<a type="5" href="'.$uid.'">'.$atags[2][$key].'</a>';
																								}
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
                                $tmppattern2 = '/pid=([0-9]+)/';
                                $flag2 = preg_match($tmppattern,$val,$tidarray);
                                $flag3 = preg_match($tmppattern2,$val,$tidarray2);
                                if($flag2)
                                {
                                        $tid = $tidarray[1];
                                        $pid = $tidarray2[1];
                                        if($tid)
                                        {
                                                $replacestr = '<a type="2" href="'.$tid.'" pid="'.$pid.'">'.$atags[2][$key].'</a>';
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
