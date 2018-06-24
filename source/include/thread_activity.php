<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: thread_activity.php 28709 2012-03-08 08:53:48Z liulanbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$threadapplyinfo = array();
$isverified = $applied = 0;
$ufielddata = $applyinfo = '';
if($_G['uid']) {
	$applyinfo = C::t('forum_activityapply')->fetch_info_for_user($_G['uid'], $_G['tid']);
	if($applyinfo) {
		$isverified = $applyinfo['verified'];
		if($applyinfo['ufielddata']) {
			$ufielddata = dunserialize($applyinfo['ufielddata']);
		}
		$applied = 1;
	}
}
$applylist = array();
$activity = C::t('forum_activity')->fetch($_G['tid']);
$activityclose = $activity['expiration'] ? ($activity['expiration'] > TIMESTAMP ? 0 : 1) : 0;
$activity['starttimefrom'] = BfdApp::bfd_html_entity_decode(strip_tags(dgmdate($activity['starttimefrom'], 'u')),1);
//$activity['starttimefrom'] = strip_tags(dgmdate($activity['starttimefrom'], 'u'));
$activity['starttimeto'] = $activity['starttimeto'] ?  BfdApp::bfd_html_entity_decode(strip_tags(dgmdate($activity['starttimeto'], 'u')),1) : '';
$activity['expiration'] = $activity['expiration'] ?  BfdApp::bfd_html_entity_decode(strip_tags(dgmdate($activity['expiration'], 'u')),1) : '';

$activity['activityclose'] = $activityclose;
$activity['attachurl'] = $activity['thumb'] = '';
unset($activity['ufield']);

if($activity['aid']) {
	$attach = C::t('forum_attachment_n')->fetch('tid:'.$_G['tid'], $activity['aid']);
	if($attach['isimage']) {
		$activity['attachurl'] = ($attach['remote'] ? $_G['setting']['ftp']['attachurl'] : $_G['setting']['attachurl']).'forum/'.$attach['attachment'];
		$activity['thumb'] = $attach['thumb'] ? getimgthumbname($activity['attachurl']) : $activity['attachurl'];
		$activity['width'] = $attach['thumb'] && $_G['setting']['thumbwidth'] < $attach['width'] ? $_G['setting']['thumbwidth'] : $attach['width'];
	}
	$skipaids[] = $activity['aid'];
		if(BFD_APP_PIC_PATH_DIY)
		{
			$activity['attachurl']  = BFD_APP_PIC_PATH_DIY.$activity['attachurl'];
			$activity['thumb']  = BFD_APP_PIC_PATH_DIY.$activity['thumb'];
		}
		else
		{
			$activity['attachurl']  = 'http://'.$_SERVER['HTTP_HOST'].'/'.$activity['attachurl'];
			$activity['thumb']  = 'http://'.$_SERVER['HTTP_HOST'].'/'.$activity['thumb'];
		}
}


$applylistverified = array();
$noverifiednum = 0;
$query = C::t('forum_activityapply')->fetch_all_for_thread($_G['tid'], 0, 0, 0, 1);
foreach($query as $activityapplies) {
	$activityapplies['dateline'] =  BfdApp::bfd_html_entity_decode(strip_tags(dgmdate($activityapplies['dateline'], 'u')),1);
	unset($activityapplies['ufielddata']);
	if($activityapplies['verified'] == 1) {
		//$activityapplies['ufielddata'] = dunserialize($activityapplies['ufielddata']);
		if(count($applylist) < $_G['setting']['activitypp']) {
			$activityapplies['message'] = preg_replace("/(".lang('forum/misc', 'contact').".*)/", '', $activityapplies['message']);
			$applylist[] = $activityapplies;
		}
	} else {
		if(count($applylistverified) < 8) {
			$applylistverified[] = $activityapplies;
		}
		$noverifiednum++;
	}

}

$applynumbers = $activity['applynumber'];
$aboutmembers = $activity['number'] >= $applynumbers ? $activity['number'] - $applynumbers : 0;
$allapplynum = $applynumbers + $noverifiednum;
if($_G['forum']['status'] == 3) {
	$isgroupuser = groupperm($_G['forum'], $_G['uid']);
}

$threadapplyinfo['userapplyinfo'] = array();
$threadapplyinfo['userapplyinfo']['isverified'] = $isverified ? 1 : 0;
$threadapplyinfo['userapplyinfo']['applied'] = $applied ? 1 : 0;

$threadapplyinfo['activity'] = $activity;
$threadapplyinfo['activity']['aboutmembers'] = $aboutmembers;
$threadapplyinfo['activity']['noverifiednum'] = $noverifiednum;
$threadapplyinfo['activity']['allapplynum'] = $allapplynum;
$threadapplyinfo['activity']['activityapplyurl'] = BFD_APP_DATA_URL_PRE.'appapi/index.php?mod=thread_activity&token='.urlencode($_GET['token']).'&tid='.$_G['tid'];
$threadapplyinfo['applylist'] = $applylist;
$threadapplyinfo['applylistverified'] = $applylistverified;

?>
