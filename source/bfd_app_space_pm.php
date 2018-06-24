<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_pm.php 30277 2012-05-18 02:57:25Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

loaducenter();

$list = array();

$plid = empty($_GET['plid'])?0:intval($_GET['plid']);
$daterange = empty($_GET['daterange'])?0:intval($_GET['daterange']);
$touid = empty($_GET['touid'])?0:intval($_GET['touid']);

$page = empty($_GET['page']) ? 1 : intval($_GET['page']);
$perpage = 20;
$pagesize = intval($_GET['pagesize']);
if($pagesize > 1)
{
	$perpage = $pagesize;
}
$count = 0;
$result = array();

if($_GET['subop'] == 'view') {
	$type = $_GET['type'];

	$chatpmmember = intval($_GET['chatpmmember']);
	$chatpmmemberlist = array();
	if($chatpmmember) {
		$chatpmmember = uc_pm_chatpmmemberlist($_G['uid'], $plid);
		if(!empty($chatpmmember)) {
			$authorid = $founderuid = $chatpmmember['author'];
			$chatpmmemberlist = C::t('common_member')->fetch_all($chatpmmember['member']);
			foreach(C::t('common_member_field_home')->fetch_all($chatpmmember['member']) as $uid => $member) {
				$chatpmmemberlist[$uid] = array_merge($member, $chatpmmemberlist[$uid]);
			}
		}
		require_once libfile('function/friend');
		$friendgrouplist = friend_group_list();
		$actives = array('chatpmmember'=>' class="a"');
	} else {
		if($touid) {
			$ols = array();
			if(!$daterange) {
				$member = getuserbyuid($touid);
				$tousername = $member['username'];
				unset($member);
				$count = uc_pm_view_num($_G['uid'], $touid, 0);
/*				if(!$page) {
					$page = ceil($count/$perpage);
				}
				$list = uc_pm_view($_G['uid'], 0, $touid, 5, ceil($count/$perpage)-$page+1, $perpage, 0, 0);
*/
				$list = uc_pm_view($_G['uid'], 0, $touid, 5, $page, $perpage, 0, 0);
			} else {
				showmessage('parameters_error');
			}
		} else {
			if(!$daterange) {
				$count = uc_pm_view_num($_G['uid'], $plid, 1);
				if(!$page) {
					$page = ceil($count/$perpage);
				}
				$list = uc_pm_view($_G['uid'], 0, $plid, 5, ceil($count/$perpage)-$page+1, $perpage, $type, 1);
			} else {
				$list = uc_pm_view($_G['uid'], 0, $plid, 5, ceil($count/$perpage)-$page+1, $perpage, $type, 1);
				$chatpmmember = uc_pm_chatpmmemberlist($_G['uid'], $plid);
				if(!empty($chatpmmember)) {
					$authorid = $founderuid = $chatpmmember['author'];
					$chatpmmemberlist = C::t('common_member')->fetch_all($chatpmmember['member']);
					foreach(C::t('common_member_field_home')->fetch_all($chatpmmember['member']) as $uid => $member) {
						$chatpmmemberlist[$uid] = array_merge($member, $chatpmmemberlist[$uid]);
					}
					foreach(C::app()->session->fetch_all_by_uid($chatpmmember['member']) as $value) {
						if(!$value['invisible']) {
							$ols[$value['uid']] = $value['lastactivity'];
						}
					}
				}
				$membernum = count($chatpmmemberlist);
				$subject = $list[0]['subject'];
				$refreshtime = $_G['setting']['chatpmrefreshtime'];

			}
		}
		$founderuid = empty($list)?0:$list[0]['founderuid'];
		$pmid = empty($list)?0:$list[0]['pmid'];
	}
	foreach($list as $val)
	{
		$tmparr  = array();
		$tmparr['plid'] = $val['plid'];
		$tmparr['pmtype'] = $val['pmtype'];
		$tmparr['pmid'] = $val['pmid'];
		$tmparr['message'] = html_entity_decode(strip_tags($val['message']), ENT_COMPAT | ENT_XHTML,BFD_APP_CHARSET_HTML_DECODE);
		$tmparr['message'] = preg_replace("/[\r\n]{2,}/","\n",$tmparr['message']);
		$tmparr['message'] = preg_replace('/\[([a-z]*)[^]]*\][^[]*\[\/[a-z]*\]/U','',$tmparr['message']);
		$tmparr['msgfromid'] = $val['msgfromid'];
		$tmparr['msgfrom'] = $val['msgfrom'];
		$tmparr['msgfromavatar'] = avatar($val['msgfromid'],'small',true);
		$tmparr['msgtoid'] = $val['msgtoid'];
		$result[] = $tmparr;
	}
} elseif($_GET['subop'] == 'viewg') {

        $grouppm = C::t('common_grouppm')->fetch($_GET['pmid']);
        if(!$grouppm) {
                $grouppm = array_merge((array)C::t('common_member_grouppm')->fetch($_G['uid'], $_GET['pmid']), $grouppm);
        }
        if($grouppm) {
                $grouppm['numbers'] = $grouppm['numbers'] - 1;
        }
        if(!$grouppm['status']) {
                C::t('common_member_grouppm')->update($_G['uid'], $_GET['pmid'], array('status' => 1, 'dateline' => TIMESTAMP));
        }
	$grouppm['message'] = strip_tags($grouppm['message'],'<br>');
	$grouppm['message'] = str_replace(array('<br />','<br \>','&nbsp;'),array("\n","\n",' '),$grouppm['message']);
	$grouppm['message'] = preg_replace("/[\r\n]{2,}/","\n",$grouppm['message']);
	$grouppm['message'] = html_entity_decode(strip_tags($grouppm['message']), ENT_COMPAT | ENT_HTML401,BFD_APP_CHARSET_HTML_DECODE);
	$grouppm['message'] = preg_replace('/\[([a-z]*)[^]]*\][^[]*\[\/[a-z]*\]/U','',$grouppm['message']);
	$grouppm['dateline'] = date('Y-m-d H:i:s',$grouppm['dateline']);
	$grouppm['authoravatar'] = avatar($grouppm['authorid'],'small',true);
	$result = $grouppm;
	BfdApp::display_result('get_success',$result);

} else {

	$filter = in_array($_GET['filter'], array('newpm', 'privatepm', 'announcepm')) ? $_GET['filter'] : 'privatepm';

	$grouppms = $gpmids = $gpmstatus = array();
	$newpm = $newpmcount = 0;

	if($filter == 'announcepm') {
		$announcepm  = 0;
		foreach(C::t('common_member_grouppm')->fetch_all_by_uid($_G['uid'], $filter == 'announcepm' ? 1 : 0) as $gpmid => $gpuser) {
			$gpmstatus[$gpmid] = $gpuser['status'];
			if($gpuser['status'] == 0) {
				$announcepm ++;
			}
		}
		$gpmids = array_keys($gpmstatus);
		if($gpmids) {
			foreach(C::t('common_grouppm')->fetch_all_by_id_authorid($gpmids) as $grouppm) {
				$grouppm['message'] =strip_tags($grouppm['message']);
				$grouppm['message'] = preg_replace("/[\r\n]{2,}/","\n",$grouppm['message']);
				$grouppm['message'] = html_entity_decode(strip_tags($grouppm['message']), ENT_COMPAT | ENT_XHTML,BFD_APP_CHARSET_HTML_DECODE);
				$grouppm['message'] = preg_replace('/\[([a-z]*)[^]]*\][^[]*\[\/[a-z]*\]/U','',$grouppm['message']);
				$grouppm['dateline'] = date('Y-m-d H:i:s',$grouppm['dateline']);
				$grouppm['authoravatar'] = avatar($grouppm['authorid'],'small',true);
				$grouppms[] = $grouppm;
			}
		}
		$result = $grouppms;
		BfdApp::display_result('get_success',$result,'',1);
	}

	if($filter == 'privatepm' || $filter == 'newpm') {
		$resultlist = uc_pm_list($_G['uid'], $page, $perpage, 'inbox', $filter, 200);
		$count = $resultlist['count'];
		$list = $resultlist['data'];
	}

	if($filter == 'privatepm' && $page == 1 || $filter == 'newpm') {
		$newpmarr = uc_pm_checknew($_G['uid'], 1);
		$newpm = $newpmarr['newpm'];
	}
	$newpmcount = $newpm + $announcepm;
	if($_G['member']['newpm']) {
		if($newpm && $_G['setting']['cloud_status']) {
			$msgService = Cloud::loadClass('Cloud_Service_Client_Message');
			$msgService->setMsgFlag($_G['uid'], $_G['timestamp']);
		}
		C::t('common_member')->update($_G['uid'], array('newpm' => 0));
		uc_pm_ignore($_G['uid']);
	}
	$plids = array();
	foreach($list as $val)
	{
		$tmparr = array();
		$tmparr['plid'] = $val['plid'];
		$tmparr['isnew'] = $val['isnew'];
		$tmparr['pmnum'] = $val['pmnum'];
		$tmparr['pmtype'] = $val['pmtype'];
		$tmparr['dateline'] = date('Y-m-d H:i:s',$val['dateline']);
		$tmparr['msgfromid'] = $val['msgfromid'];
		$tmparr['msgfrom'] = $val['msgfrom'];
		$tmparr['msgfromavatar'] = avatar($val['touid'],'small',true);
		$tmparr['touid'] = $val['touid'];
		$tmparr['tousername'] = $val['tousername'];
		$tmparr['message'] =  html_entity_decode(strip_tags($val['message']), ENT_COMPAT | ENT_XHTML,BFD_APP_CHARSET_HTML_DECODE);
		$tmparr['message'] = preg_replace('/\[([a-z]*)[^]]*\][^[]*\[\/[a-z]*\]/U','',$tmparr['message']);
		
		$result[] = $tmparr;
		if($tmparr['isnew'])
		{
			$plids[] = $tmparr['plid'];
		}
	
	}
	uc_pm_readstatus($_G['uid'], array(), $plids, 0);	
	if($page == 1)
	{
		$list = $result;
		$result = array();

    		//通知数
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
		$totalpage = ceil($count/$perpage);
		if($totalpage < 1) $totalpage = 1;
		$result['pagetotal'] = $totalpage;

		BfdApp::display_result('get_success',$list,'',$result);
	}
}
$totalpage = ceil($count/$perpage);
if($totalpage < 1) $totalpage = 1;
BfdApp::display_result('get_success',$result,'',$totalpage);
?>
