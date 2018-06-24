<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: thread_poll.php 24621 2011-09-28 06:29:43Z liulanbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$threadpollinfo = array();
$polloptions = array();
$votersuid = '';
if($count = C::t('forum_polloption')->fetch_count_by_tid($_G['tid'])) {

	$options = C::t('forum_poll')->fetch($_G['tid']);
	$multiple = $options['multiple'];
	$visible = $options['visible'];
	$maxchoices = $options['maxchoices'];
	$expiration = $options['expiration'];
	$overt = $options['overt'];
	$voterscount = $options['voters'];

	$threadpollinfo['multiple'] = $multiple;
	$threadpollinfo['maxchoices'] = $maxchoices;
	$threadpollinfo['expiration'] = $expiration;
	$threadpollinfo['overt'] = $overt;
	$threadpollinfo['voterscount'] = $voterscount;

	$query = C::t('forum_polloption')->fetch_all_by_tid($_G['tid'], 1);
	$colors = array('E92725', 'F27B21', 'F2A61F', '5AAF4A', '42C4F5', '0099CC', '3365AE', '2A3591', '592D8E', 'DB3191');
	$voterids = $polloptionpreview = '';
	$ci = 0;
	$opts = 1;
	foreach($query as $options) {
		$viewvoteruid[] = $options['voterids'];
		$voterids .= "\t".$options['voterids'];
		$option = preg_replace("/\[url=(https?){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/i", "<a href=\"\\1://\\2\" target=\"_blank\">\\3</a>", $options['polloption']);
		$polloptions[] = array
		(
			'polloptionid'	=> $options['polloptionid'],
			'polloption'	=> $option,
			'votes'		=> $options['votes'],
			'width'		=> $options['votes'] > 0 ? (@round($options['votes'] * 100 / $count['total'])).'%' : '8px',
			'percent'	=> @sprintf("%01.2f", $options['votes'] * 100 / $count['total']),
			'color'		=> $colors[$ci]
		);
		if($ci < 2) {
			$polloptionpreview .= $option."\t";
		}
		$ci++;
		if($ci == count($colors)) {
			$ci = 0;
		}
	}

	$voterids = explode("\t", $voterids);
	$voters = array_unique($voterids);
	array_shift($voters);

	if(!$expiration) {
		$expirations = TIMESTAMP + 86400;
	} else {
		$expirations = $expiration;
		if($expirations > TIMESTAMP) {
			$_G['forum_thread']['remaintime'] = remaintime($expirations - TIMESTAMP);
			$_G['forum_thread']['remaintime'] = $_G['forum_thread']['remaintime'][0].'天'.$_G['forum_thread']['remaintime'][1].'时'.$_G['forum_thread']['remaintime'][2].'分'.$_G['forum_thread']['remaintime'][3].'秒';
		}
		
	}
	$threadpollinfo['remaintime'] = $_G['forum_thread']['remaintime'] ? $_G['forum_thread']['remaintime'] : '';//剩余时间 天 时 分 秒

	$allwvoteusergroup = $_G['group']['allowvote'];
	$allowvotepolled = !in_array(($_G['uid'] ? $_G['uid'] : $_G['clientip']), $voters);
	$allowvotethread = ($_G['forum_thread']['isgroup'] || !$_G['forum_thread']['closed'] && !checkautoclose($_G['forum_thread']) || $_G['group']['alloweditpoll']) && TIMESTAMP < $expirations && $expirations > 0;

	$_G['group']['allowvote'] = $allwvoteusergroup && $allowvotepolled && $allowvotethread;

	$optiontype = $multiple ? 'checkbox' : 'radio';
	$visiblepoll = $visible || $_G['forum']['ismoderator'] || ($_G['uid'] && $_G['uid'] == $_G['forum_thread']['authorid']) || ($expirations >= TIMESTAMP && in_array(($_G['uid'] ? $_G['uid'] : $_G['clientip']), $voters)) || $expirations < TIMESTAMP ? 0 : 1;
	$threadpollinfo['optiontype'] = $optiontype; //checkbox radio
	$threadpollinfo['visiblepoll'] = $visiblepoll; //0 可见，1 不可见
	//去掉投票数据	
	if($visiblepoll)
	{
		foreach($threadpollinfo['polloptions'] as &$value)
		{
			$value['width'] = 0;
			$value['votes'] = 0;
			$value['percent'] = 0;
		}
	}
	$threadpollinfo['allowvote'] = $_G['group']['allowvote']; //是否允许投票 false true
	//$threadpollinfo['allwvoteusergroup'] = $allwvoteusergroup;//用户所在用户组没有权限
	//$threadpollinfo['allowvotepolled'] = $allowvotepolled;//已经投过票
	//$threadpollinfo['allowvotethread'] = $allowvotethread;//投票已关闭


	$msg = '';
	if($_G['group']['allowvote'])
	{
		if($overt)
		{
        	$msg = lang('forum/template','poll_msg_overt');
		}
	}
	else if (!$allwvoteusergroup)
	{
		$msg = lang('forum/template','poll_msg_allwvoteusergroup');
	}
	else if(!$allowvotepolled)
	{
		$msg = lang('forum/template','poll_msg_allowvotepolled');
	}
	else if(!$allowvotethread)
	{
		$msg = lang('forum/template','poll_msg_allowvotethread');
	}

	$threadpollinfo['showmessage'] = $msg;
	$threadpollinfo['pollsubmiturl'] = BFD_APP_DATA_URL_PRE.'appapi/index.php?mod=forum_misc&action=votepoll&tid='.$_G['tid'];
	$threadpollinfo['pollsubmitparams'] = 'pollanswers';

	$threadpollinfo['polloptions'] = $polloptions;
}

?>
