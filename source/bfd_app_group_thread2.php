<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum_viewthread.php 31525 2012-09-05 07:19:35Z liulanbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once libfile('function/forumlist');
require_once libfile('function/forum');
require_once libfile('function/discuzcode');
require_once libfile('function/post');
require_once DISCUZ_ROOT.'./source/language/forum/lang_template.php';
$_G['lang']['forum'] = $lang;

loadforum();

BfdApp::check_forum_password();

$thread = & $_G['forum_thread'];
$forum = & $_G['forum'];
$page = max(1, intval($_GET['page']));
$totalpage = 0;
$persize =  BFD_APP_GROUP_POST_PAGESIZE;
$pagesize = intval($_GET['pagesize']);
if($pagesize > 0)
{
	$persize = $pagesize;
}
$_G['ppp'] = $persize;

//判断是否有定位到某楼的需求 支持 根据pid跳转 和 直接跳转到某楼
$pid = $showpid = intval(getgpc('pid'));
$postno = intval(getgpc('postno'));
$post = array();
if($pid > 0)
{
		if($thread) {
			$post = C::t('forum_post')->fetch($thread['posttableid'], $pid);
		} else {
			$post = get_post_by_pid($pid);
		}

		
}
else if($postno > 0)
{
	if(getstatus($thread['status'], 3)) {
		$rowarr = C::t('forum_post')->fetch_all_by_tid_position($thread['posttableid'], $thread['tid'], $postno);
		$pid = $rowarr[0]['pid'];
	}

	if($pid) {
		$post = C::t('forum_post')->fetch($thread['posttableid'], $pid);
		if($post['invisible'] != 0) {
			$post = array();
		}
	} else {
		$postno = $postno > 1 ? $postno - 1 : 0;
		$post = C::t('forum_post')->fetch_visiblepost_by_tid($thread['posttableid'], $thread['tid'], $postno);
	}
}
if($post && empty($thread)) {
	$_G['forum_thread'] = get_thread_by_tid($post['tid']);
}

if(!$_G['forum_thread'] || !$_G['forum']) {
	BfdApp::display_result('thread_nonexistence');
}



if($post)
{
		$showpid = $post['pid'];
		if($thread['maxposition']) {
			$tmpmaxposition = $thread['maxposition'];
		} else {
			$tmpmaxposition = C::t('forum_post')->fetch_maxposition_by_tid($thread['posttableid'], $thread['tid']);
		}
		$thread['replies'] = $maxposition;
		$curpostnum = $post['position'];
		$ordertype = !isset($_GET['ordertype']) && getstatus($thread['status'], 4) ? 1 : $ordertype;
		if($ordertype != 1) {
			$page = ceil($curpostnum / $_G['ppp']);
		} elseif($curpostnum > 1) {
			$page = ceil(($thread['replies'] - $curpostnum + 3) / $_G['ppp']);
		} else {
			$page = 1;
		}
}



$threadtableids = !empty($_G['cache']['threadtableids']) ? $_G['cache']['threadtableids'] : array();
$threadtable_info = !empty($_G['cache']['threadtable_info']) ? $_G['cache']['threadtable_info'] : array();

$archiveid = $thread['threadtableid'];
$thread['is_archived'] = $archiveid ? true : false;
$thread['archiveid'] = $archiveid;
$forum['threadtableid'] = $archiveid;
$threadtable = $thread['threadtable'];
$posttableid = $thread['posttableid'];
$posttable = $thread['posttable'];


$_G['action']['fid'] = $_G['fid'];
$_G['action']['tid'] = $_G['tid'];
$_GET['authorid'] =  !empty($_GET['authorid']) ? intval($_GET['authorid']) : 0;
$_GET['ordertype'] =  0;
$_GET['from'] =  '';

$fromuid = $_G['setting']['creditspolicy']['promotion_visit'] && $_G['uid'] ? '&amp;fromuid='.$_G['uid'] : '';
$feeduid = $_G['forum_thread']['authorid'] ? $_G['forum_thread']['authorid'] : 0;
$feedpostnum = $_G['forum_thread']['replies'] > $_G['ppp'] ? $_G['ppp'] : ($_G['forum_thread']['replies'] ? $_G['forum_thread']['replies'] : 1);

if($_GET['authorid'] == -1)
{
	$_GET['authorid'] = $feeduid ;
}

$aimgs = array();
$skipaids = array();

$thread['subjectenc'] = rawurlencode($_G['forum_thread']['subject']);
$thread['short_subject'] = cutstr($_G['forum_thread']['subject'], 52);

$navigation = '';
if($_G['forum']['status'] == 3) {
	$_G['action']['action'] = 3;
	require_once libfile('function/group');
	$status = groupperm($_G['forum'], $_G['uid']);
	if($status == 1) {
		BfdApp::display_result('forum_group_status_off');
	} elseif($status == 2) {
		BfdApp::display_result('forum_group_noallowed');
	} elseif($status == 3) {
		BfdApp::display_result('forum_group_moderated');
	}
/*COMMENTS
//调整forum的icon 20121125 ep
*/
	require_once libfile('function/home');
/*COMMENTS END*/
/*COMMENTS
//引入group扩展帮助类，同时修改nav 20121125 ep
*/

} else {

}



$_G['forum_tagscript'] = '';

if(empty($_G['forum']['allowview'])) {

	if(!$_G['forum']['viewperm'] && !$_G['group']['readaccess']) {
		BfdApp::display_result('group_nopermission');
	} elseif($_G['forum']['viewperm'] && !forumperm($_G['forum']['viewperm'])) {
		BfdApp::display_result('group_nopermission');
	}

} elseif($_G['forum']['allowview'] == -1) {
	BfdApp::display_result('forum_access_view_disallow');
}

if($_G['forum']['formulaperm']) {
	formulaperm($_G['forum']['formulaperm']);
}
/*
if($_G['forum']['password'] && $_G['forum']['password'] != $_G['cookie']['fidpw'.$_G['fid']]) {
	BfdApp::display_result('view_password_error');
}
*/

if($_G['forum_thread']['readperm'] && $_G['forum_thread']['readperm'] > $_G['group']['readaccess'] && !$_G['forum']['ismoderator'] && $_G['forum_thread']['authorid'] != $_G['uid']) {
	BfdApp::display_result('thread_nopermission');
}

$usemagic = array('user' => array(), 'thread' => array());

$replynotice = getstatus($_G['forum_thread']['status'], 6);

$hiddenreplies = getstatus($_G['forum_thread']['status'], 2);

$rushreply = getstatus($_G['forum_thread']['status'], 3);

$savepostposition = getstatus($_G['forum_thread']['status'], 1);

$incollection = getstatus($_G['forum_thread']['status'], 9);

$_G['forum_threadpay'] = FALSE;
if($_G['forum_thread']['price'] > 0 && $_G['forum_thread']['special'] == 0) {
	if($_G['setting']['maxchargespan'] && TIMESTAMP - $_G['forum_thread']['dateline'] >= $_G['setting']['maxchargespan'] * 3600) {
		C::t('forum_thread')->update($_G['tid'], array('price' => 0), false, false, $archiveid);
		$_G['forum_thread']['price'] = 0;
	} else {
		$exemptvalue = $_G['forum']['ismoderator'] ? 128 : 16;
		if(!($_G['group']['exempt'] & $exemptvalue) && $_G['forum_thread']['authorid'] != $_G['uid']) {
			if(!(C::t('common_credit_log')->count_by_uid_operation_relatedid($_G['uid'], 'BTC', $_G['tid']))) {
				require_once libfile('thread/pay', 'include');
				$_G['forum_threadpay'] = TRUE;
			}
		}
	}
}
/*
if($rushreply) {
	$rewardfloor = '';
	$rushresult = $rewardfloorarr = $rewardfloorarray = array();
	$rushresult = C::t('forum_threadrush')->fetch($_G['tid']);
	if($rushresult['creditlimit'] == -996) {
		$rushresult['creditlimit'] = '';
	}
	if((TIMESTAMP < $rushresult['starttimefrom'] || ($rushresult['starttimeto'] && TIMESTAMP > $rushresult['starttimeto']) || ($rushresult['stopfloor'] && $_G['forum_thread']['replies'] + 1 >= $rushresult['stopfloor'])) && $_G['forum_thread']['closed'] == 0) {
		C::t('forum_thread')->update($_G['tid'], array('closed'=>1));
	} elseif(($rushresult['starttimefrom'] && TIMESTAMP > $rushresult['starttimefrom']) && $_G['forum_thread']['closed'] == 1) {
		if(!$rushresult['starttimeto'] && !$rushresult['stopfloor']) {
			C::t('forum_thread')->update($_G['tid'], array('closed'=>0));
		} else {
			if(($rushresult['starttimeto'] && TIMESTAMP < $rushresult['starttimeto'] && $rushresult['stopfloor'] > $_G['forum_thread']['replies'] + 1) || ($rushresult['stopfloor'] && $_G['forum_thread']['replies'] + 1 < $rushresult['stopfloor'])) {
				C::t('forum_thread')->update($_G['tid'], array('closed'=>0));
			}
		}
	}
	$rushresult['starttimefrom'] = $rushresult['starttimefrom'] ? dgmdate($rushresult['starttimefrom']) : '';
	$rushresult['starttimeto'] = $rushresult['starttimeto'] ? dgmdate($rushresult['starttimeto']) : '';
	$rushresult['creditlimit_title'] = $_G['setting']['creditstransextra'][11] ? $_G['setting']['extcredits'][$_G['setting']['creditstransextra'][11]]['title'] : lang('forum/misc', 'credit_total');
}
*/
if($_G['forum_thread']['replycredit'] > 0) {
	$_G['forum_thread']['replycredit_rule'] = C::t('forum_replycredit')->fetch($thread['tid']);
	$_G['forum_thread']['replycredit_rule']['remaining'] = $_G['forum_thread']['replycredit'] / $_G['forum_thread']['replycredit_rule']['extcredits'];
	$_G['forum_thread']['replycredit_rule']['extcreditstype'] = $_G['forum_thread']['replycredit_rule']['extcreditstype'] ? $_G['forum_thread']['replycredit_rule']['extcreditstype'] : $_G['setting']['creditstransextra'][10] ;
}
$_G['group']['raterange'] = $_G['setting']['modratelimit'] && $adminid == 3 && !$_G['forum']['ismoderator'] ? array() : $_G['group']['raterange'];

$_G['group']['allowgetattach'] = !empty($_G['forum']['allowgetattach']) || ($_G['group']['allowgetattach'] && !$_G['forum']['getattachperm']) || forumperm($_G['forum']['getattachperm']);
$_G['group']['allowgetimage'] = !empty($_G['forum']['allowgetimage']) || ($_G['group']['allowgetimage'] && !$_G['forum']['getattachperm']) || forumperm($_G['forum']['getattachperm']);
$_G['getattachcredits'] = '';
if($_G['forum_thread']['attachment']) {
	$exemptvalue = $_G['forum']['ismoderator'] ? 32 : 4;
	if(!($_G['group']['exempt'] & $exemptvalue)) {
		$creditlog = updatecreditbyaction('getattach', $_G['uid'], array(), '', 1, 0, $_G['forum_thread']['fid']);
		$p = '';
		if($creditlog['updatecredit']) for($i = 1;$i <= 8;$i++) {
			if($policy = $creditlog['extcredits'.$i]) {
				$_G['getattachcredits'] .= $p.$_G['setting']['extcredits'][$i]['title'].' '.$policy.' '.$_G['setting']['extcredits'][$i]['unit'];
				$p = ', ';
			}
		}
	}
}

$exemptvalue = $_G['forum']['ismoderator'] ? 64 : 8;
$_G['forum_attachmentdown'] = $_G['group']['exempt'] & $exemptvalue;

$seccodecheck = ($_G['setting']['seccodestatus'] & 4) && (!$_G['setting']['seccodedata']['minposts'] || getuserprofile('posts') < $_G['setting']['seccodedata']['minposts']);
$secqaacheck = $_G['setting']['secqaa']['status'] & 2 && (!$_G['setting']['secqaa']['minposts'] || getuserprofile('posts') < $_G['setting']['secqaa']['minposts']);
$usesigcheck = $_G['uid'] && $_G['group']['maxsigsize'];

$postlist = $_G['forum_attachtags'] = $attachlist = $_G['forum_threadstamp'] = array();
$aimgcount = 0;
$_G['forum_attachpids'] = array();


if($_G['forum_thread']['stamp'] >= 0) {
	$_G['forum_threadstamp'] = $_G['cache']['stamps'][$_G['forum_thread']['stamp']];
}

$lastmod = viewthread_lastmod($_G['forum_thread']);

$showsettings = str_pad(decbin($_G['setting']['showsettings']), 3, '0', STR_PAD_LEFT);

$showsignatures = $showsettings{0};
$showavatars = $showsettings{1};
$_G['setting']['showimages'] = $showsettings{2};

$highlightstatus = 0;

$threadtag = array();
viewthread_updateviews($archiveid);


$postfieldsadd = $specialadd1 = $specialadd2 = $specialextra = '';
$tpids = array();
/*
if($_G['forum_thread']['special'] == 2) {
	if(!empty($_GET['do']) && $_GET['do'] == 'tradeinfo') {
		require_once libfile('thread/trade', 'include');
	}
	$query = C::t('forum_trade')->fetch_all_thread_goods($_G['tid']);
	foreach($query as $trade) {
		$tpids[] = $trade['pid'];
	}
	$specialadd2 = 1;

} elseif($_G['forum_thread']['special'] == 5) {
	$_GET['stand'] = isset($_GET['stand']) && in_array($_GET['stand'], array(0, 1, 2)) ? $_GET['stand'] : null;
	if(isset($_GET['stand'])) {
		$specialadd2 = 1;
		$specialextra = "&amp;stand=$_GET[stand]";
	}
}
*/
$onlyauthoradd = $threadplughtml = '';

$maxposition = 0;
if(empty($_GET['viewpid'])) {
	$disablepos = !$rushreply && C::t('forum_threaddisablepos')->fetch($_G['tid']) ? 1 : 0;
	if(!$disablepos && !in_array($_G['forum_thread']['special'], array(2,3,5))) {
		if($_G['forum_thread']['maxposition']) {
			$maxposition = $_G['forum_thread']['maxposition'];
		} else {
			$maxposition = C::t('forum_post')->fetch_maxposition_by_tid($posttableid, $_G['tid']);
		}
	}

	$ordertype = empty($_GET['ordertype']) && getstatus($_G['forum_thread']['status'], 4) ? 1 : $_GET['ordertype'];
	$sticklist = array();
/*
	if($_G['forum_thread']['stickreply'] && $page == 1 && (!$_GET['authorid'] || $_GET['authorid'] == $_G['thread']['authorid'])) {
		$poststick = C::t('forum_poststick')->fetch_all_by_tid($_G['tid']);
		foreach(C::t('forum_post')->fetch_all($posttableid, array_keys($poststick)) as $post) {
			$post['position'] = $poststick[$post['pid']]['position'];
			$post['message'] = messagecutstr($post['message'], 400);
			$post['avatar'] = avatar($post['authorid'], 'small');
			$sticklist[$post['pid']] = $post;
		}
		$stickcount = count($sticklist);
	}
	if($rushreply) {
		$rushids = $rushpids = $rushpositionlist = $preg = $arr = array();
		$str = ',,';
		$preg_str = rushreply_rule($rushresult);
		if($_GET['checkrush']) {
			$maxposition = 0;
			for($i = 1; $i <= $_G['forum_thread']['replies'] + 1; $i++) {
				$str = $str.$i.',,';
			}
			preg_match_all($preg_str, $str, $arr);
			$arr = $arr[0];
			foreach($arr as $var) {
				$var = str_replace(',', '', $var);
				$rushids[$var] = $var;
			}
			$temp_reply = $_G['forum_thread']['replies'];
			$_G['forum_thread']['replies'] = $countrushpost = max(0, count($rushids) - 1);
			$rushids = array_slice($rushids, ($page - 1) * $_G['ppp'], $_G['ppp']);
			foreach(C::t('forum_post')->fetch_all_by_tid_position($posttableid, $_G['tid'], $rushids) as $post) {
				$postarr[$post['position']] = $post;
			}
		} else {
			for($i = ($page - 1) * $_G['ppp'] + 1; $i <= $page * $_G['ppp']; $i++) {
				$str = $str.$i.',,';
			}
			preg_match_all($preg_str, $str, $arr);
			$arr = $arr[0];
			foreach($arr as $var) {
				$var = str_replace(',', '', $var);
				$rushids[$var] = $var;
			}
			$_G['forum_thread']['replies'] = $_G['forum_thread']['replies'] - 1;
		}
	}
*/
	if($_GET['authorid']) {
		$maxposition = 0;
		$_G['forum_thread']['replies'] = C::t('forum_post')->count_by_tid_invisible_authorid($_G['tid'], $_GET['authorid']);
		$_G['forum_thread']['replies']--;
		if($_G['forum_thread']['replies'] < 0) {
			showmessage('undefined_action');
		}
		$onlyauthoradd = 1;
	}
/* elseif($_G['forum_thread']['special'] == 5) {
		if(isset($_GET['stand']) && $_GET['stand'] >= 0 && $_GET['stand'] < 3) {
			$_G['forum_thread']['replies'] = C::t('forum_debatepost')->count_by_tid_stand($_G['tid'], $_GET['stand']);
		} else {
			$_G['forum_thread']['replies'] = C::t('forum_post')->count_visiblepost_by_tid($_G['tid']);
			$_G['forum_thread']['replies'] > 0 && $_G['forum_thread']['replies']--;
		}
	} elseif($_G['forum_thread']['special'] == 2) {
		$tradenum = C::t('forum_trade')->fetch_counter_thread_goods($_G['tid']);
		$_G['forum_thread']['replies'] -= $tradenum;
	}
*/
	if($maxposition) {
		$_G['forum_thread']['replies'] = $maxposition - 1;
	}
	$totalpage = ceil(($_G['forum_thread']['replies'] + 1) / $_G['ppp']);
	$page > $totalpage && $page = $totalpage;
/*	$_G['forum_pagebydesc'] = !$maxposition && $page > 2 && $page > ($totalpage / 2) ? TRUE : FALSE;

	if($_G['forum_pagebydesc']) {
		$firstpagesize = ($_G['forum_thread']['replies'] + 1) % $_G['ppp'];
		$_G['forum_ppp3'] = $_G['forum_ppp2'] = $page == $totalpage && $firstpagesize ? $firstpagesize : $_G['ppp'];
		$realpage = $totalpage - $page + 1;
		if($firstpagesize == 0) {
			$firstpagesize = $_G['ppp'];
		}
		$start_limit = max(0, ($realpage - 2) * $_G['ppp'] + $firstpagesize);
		$_G['forum_numpost'] = ($page - 1) * $_G['ppp'];
		if($ordertype != 1) {
		} else {
			$_G['forum_numpost'] = $_G['forum_thread']['replies'] + 2 - $_G['forum_numpost'] + ($page > 1 ? 1 : 0);
		}
	} else {
*/
		$start_limit = $_G['forum_numpost'] = max(0, ($page - 1) * $_G['ppp']);
		if($start_limit > $_G['forum_thread']['replies']) {
			$start_limit = $_G['forum_numpost'] = 0;
			$page = 1;
		}
		if($ordertype != 1) {
		} else {
			$_G['forum_numpost'] = $_G['forum_thread']['replies'] + 2 - $_G['forum_numpost'] + ($page > 1 ? 1 : 0);
		}
//	}
}

$_G['forum_newpostanchor'] = $_G['forum_postcount'] = 0;

$_G['forum_onlineauthors'] = $_G['forum_cachepid'] = array();


$isdel_post = $cachepids = $postusers = $skipaids = array();

if($_G['forum_auditstatuson'] || in_array($_G['forum_thread']['displayorder'], array(-2, -3, -4)) && $_G['forum_thread']['authorid'] == $_G['uid']) {
	$visibleallflag = 1;
}


if($maxposition) {
	$start = ($page - 1) * $_G['ppp'] + 1;
	$end = $start + $_G['ppp'];
	if($ordertype == 1) {
		$end = $maxposition - ($page - 1) * $_G['ppp'] + ($page > 1 ? 2 : 1);
		$start = $end - $_G['ppp'] + ($page > 1 ? 0 : 1);
		$start = max(array(1,$start));
	}
	$have_badpost = $realpost = $lastposition = 0;
	foreach(C::t('forum_post')->fetch_all_by_tid_range_position($posttableid, $_G['tid'], $start, $end, $maxposition, $ordertype) as $post) {
		if($post['invisible'] != 0) {
			$have_badpost = 1;
		}
		$cachepids[$post[position]] = $post['pid'];
		$postarr[$post[position]] = $post;
		$lastposition = $post['position'];
	}
	$realpost = count($postarr);
	if($realpost != $_G['ppp'] || $have_badpost) {
		$k = 0;
		for($i = $start; $i < $end; $i ++) {
			if(!empty($cachepids[$i])) {
				$k = $cachepids[$i];
				$isdel_post[$i] = array('deleted' => 1, 'pid' => $k, 'message' => '', 'position' => $i);
			} elseif($i < $maxposition || ($lastposition && $i < $lastposition)) {
				$isdel_post[$i] = array('deleted' => 1, 'pid' => $k, 'message' => '', 'position' => $i);
			}
			$k ++;
		}
	}
	$pagebydesc = false;
}

if(!$maxposition && empty($postarr)) {

	if(empty($_GET['viewpid'])) {
		if($_G['forum_thread']['special'] == 2) {
			$postarr = C::t('forum_post')->fetch_all_tradepost_viewthread_by_tid($_G['tid'], $visibleallflag, $_GET['authorid'], $tpids, $_G['forum_pagebydesc'], $ordertype, $start_limit, ($_G['forum_pagebydesc'] ? $_G['forum_ppp2'] : $_G['ppp']));
		} elseif($_G['forum_thread']['special'] == 5) {
			$postarr = C::t('forum_post')->fetch_all_debatepost_viewthread_by_tid($_G['tid'], $visibleallflag, $_GET['authorid'], $_GET['stand'], $_G['forum_pagebydesc'], $ordertype, $start_limit, ($_G['forum_pagebydesc'] ? $_G['forum_ppp2'] : $_G['ppp']));
		} else {
			$postarr = C::t('forum_post')->fetch_all_common_viewthread_by_tid($_G['tid'], $visibleallflag, $_GET['authorid'], $_G['forum_pagebydesc'], $ordertype, $_G['forum_thread']['replies'] + 1, $start_limit, ($_G['forum_pagebydesc'] ? $_G['forum_ppp2'] : $_G['ppp']));
		}
	} else {
		$post = array();
		if($_G['forum_thread']['special'] == 2) {
			if(!in_array($_GET['viewpid'], $tpids)) {
				$post = C::t('forum_post')->fetch('tid:'.$_G['tid'],$_GET['viewpid']);
			}
		} elseif($_G['forum_thread']['special'] == 5) {
			$post = C::t('forum_post')->fetch('tid:'.$_G['tid'], $_GET['viewpid']);
			$debatpost = C::t('forum_debatepost')->fetch($_GET['viewpid']);
			if(!isset($_GET['stand']) || (isset($_GET['stand']) && ($post['first'] == 1 || $debatpost['stand'] == $_GET['stand']))) {
				$post = array_merge($post, $debatpost);
			} else {
				$post = array();
			}
			unset($debatpost);
		} else {
			$post = C::t('forum_post')->fetch('tid:'.$_G['tid'], $_GET['viewpid']);
		}

		if($post) {
			if($visibleallflag || (!$visibleallflag && !$post['invisible'])) {
				$postarr[0] = $post;
			}
		}
	}

}
if(!empty($isdel_post)) {
	$updatedisablepos = false;
	foreach($isdel_post as $id => $post) {
		if(isset($postarr[$id]['invisible']) && ($postarr[$id]['invisible'] == 0 || $postarr[$id]['invisible'] == -3 || $visibleallflag)) {
			continue;
		}
		$postarr[$id] = $post;
		$updatedisablepos = true;
	}
	if($updatedisablepos && !$rushreply) {
		C::t('forum_threaddisablepos')->insert(array('tid' => $_G['tid']), false, true);
	}
	$ordertype != 1 ? ksort($postarr) : krsort($postarr);
}
$summary = '';
if($page == 1 && $ordertype == 1) {
	$firstpost = C::t('forum_post')->fetch_threadpost_by_tid_invisible($_G['tid']);
	if($firstpost['invisible'] == 0 || $visibleallflag == 1) {
		$postarr = array_merge(array($firstpost), $postarr);
		unset($firstpost);
	}
}
$tagnames = $locationpids = array();
foreach($postarr as $post) {
	if(($onlyauthoradd && $post['anonymous'] == 0) || !$onlyauthoradd) {
		$postusers[$post['authorid']] = array();
		if($post['first']) {
			if($ordertype == 1 && $page != 1) {
				continue;
			}
			$tagarray_all = $posttag_array = array();
			$tagarray_all = explode("\t", $post['tags']);
			if($tagarray_all) {
				foreach($tagarray_all as $var) {
					if($var) {
						$tag = explode(',', $var);
						$posttag_array[] = $tag;
						$tagnames[] = $tag[1];
					}
				}
			}
			$post['tags'] = $posttag_array;
			if($post['tags']) {
				$post['relateitem'] = getrelateitem($post['tags'], $post['tid'], $_G['setting']['relatenum'], $_G['setting']['relatetime']);
			}
			if(!$_G['forum']['disablecollect']) {
				if($incollection) {
					$post['relatecollection'] = getrelatecollection($post['tid'], false, $post['releatcollectionnum'], $post['releatcollectionmore']);
					if($_G['group']['allowcommentcollection'] && $_GET['ctid']) {
						$ctid = dintval($_GET['ctid']);
						$post['sourcecollection'] = C::t('forum_collection')->fetch($ctid);
					}
				} else {
					$post['releatcollectionnum'] = 0;
				}
			}
		}
		$postlist[$post['pid']] = $post;
	}
}
foreach($postlist as $pid => $post) {
	$postlist[$pid] = viewthread_procpost($post, $_G['member']['lastvisit'], $ordertype, $maxposition);
}

//$postno = & $_G['cache']['custominfo']['postno'];

if($locationpids) {
	$locations = C::t('forum_post_location')->fetch_all($locationpids);
}

if($postlist && $rushids) {
	foreach($postlist as $pid => $post) {
		$post['number'] = $post['position'];
		$postlist[$pid] = checkrushreply($post);
	}
}

if($_G['forum_thread']['special'] > 0 && $page == 1) {
	$_G['forum_thread']['starttime'] = gmdate($_G['forum_thread']['dateline']);
	$_G['forum_thread']['remaintime'] = '';
	switch($_G['forum_thread']['special']) {
		case 1: require_once './source/include/thread_poll.php'; break;
//		case 2: require_once libfile('thread/trade', 'include'); break;
//		case 3: require_once libfile('thread/reward', 'include'); break;
		case 4: require_once './source/include/thread_activity.php'; break;
	}
}

/*
if($_G['forum_thread']['special'] > 0 && (empty($_GET['viewpid']) || $_GET['viewpid'] == $_G['forum_firstpid'])) {
	$_G['forum_thread']['starttime'] = gmdate($_G['forum_thread']['dateline']);
	$_G['forum_thread']['remaintime'] = '';
	switch($_G['forum_thread']['special']) {
		case 1: require_once libfile('thread/poll', 'include'); break;
		case 2: require_once libfile('thread/trade', 'include'); break;
		case 3: require_once libfile('thread/reward', 'include'); break;
		case 4: require_once libfile('thread/activity', 'include'); break;
		case 5: require_once libfile('thread/debate', 'include'); break;
		case 127:
			if($_G['forum_firstpid']) {
				$sppos = strpos($postlist[$_G['forum_firstpid']]['message'], chr(0).chr(0).chr(0));
				$specialextra = substr($postlist[$_G['forum_firstpid']]['message'], $sppos + 3);
				$postlist[$_G['forum_firstpid']]['message'] = substr($postlist[$_G['forum_firstpid']]['message'], 0, $sppos);
				if($specialextra) {
					if(array_key_exists($specialextra, $_G['setting']['threadplugins'])) {
						@include_once DISCUZ_ROOT.'./source/plugin/'.$_G['setting']['threadplugins'][$specialextra]['module'].'.class.php';
						$classname = 'threadplugin_'.$specialextra;
						if(class_exists($classname) && method_exists($threadpluginclass = new $classname, 'viewthread')) {
							$threadplughtml = $threadpluginclass->viewthread($_G['tid']);
						}
					}
				}
			}
			break;
	}
}

*/
$ratelogs = $comments = $commentcount = $totalcomment = array();

if($_G['forum_attachpids'] && !defined('IN_ARCHIVER')) {
	require_once libfile('function/attachment');
	if(is_array($threadsortshow) && !empty($threadsortshow['sortaids'])) {
		$skipaids = $threadsortshow['sortaids'];
	}
	bfd_app_parseattach($_G['forum_attachpids'], $_G['forum_attachtags'], $postlist, $skipaids);
}

if($_G['forum_thread']['replies'] > $_G['forum_thread']['views']) {
	$_G['forum_thread']['views'] = $_G['forum_thread']['replies'];
}

$result = array();
foreach($postlist as $pid => $post)
{
	$postinfo  = array();
	$postinfo['pid'] = $post['pid'];
	$postinfo['tid'] = $post['tid'];
	$postinfo['fid'] = $post['fid'];
	$postinfo['first'] = $post['first'];
	$postinfo['author'] = $post['author'];
	$postinfo['authorid'] = $post['authorid'];
	//$postinfo['authoravatar'] = 'http://'.$_SERVER['HTTP_HOST'].'/uc_server/avatar.php?uid='.$post['authorid'].'&size=middle';
	$postinfo['authoravatar'] = avatar($post['authorid'],'middle',true);
	$postinfo['subject'] = html_entity_decode($post['subject'], ENT_COMPAT | ENT_XHTML,BFD_APP_CHARSET_HTML_DECODE);
	$postinfo['dateline'] = strip_tags($post['dateline']);
	//$postinfo['dateline'] = html_entity_decode($postinfo['dateline'], ENT_COMPAT | ENT_XHTML,BFD_APP_CHARSET_HTML_DECODE);
	//$postinfo['dateline'] = html_entity_decode($postinfo['dateline'], ENT_COMPAT | ENT_HTML401, 'GBK');
	$postinfo['dateline'] = str_replace('&nbsp;',' ',$postinfo['dateline']);
	$postinfo['dbdateline'] = date('Y-m-d H:i:s',$post['dbdateline']);
	$postinfo['message'] = $post['message'];
	foreach($post['imagelist'] as $aid)
	{
		$postinfo['message'] .= "\n";
		$postinfo['message'] .= "<attach>{$aid}</attach>\n";
	}
	//处理远程图片
	/*
     * 替换<img>标签
     */
	$imgarr = array();
	$imgflag = preg_match_all('/<img>([^<]*)<\/img>/',$postinfo['message'],$imgarr);
	$rep_imgarr = array();
	if($imgflag)
	{
		foreach($imgarr[1] as $imgval)
		{
			$rattach = array();
			$rattach['aid'] = substr(md5($imgval),0,8);
			$rattach['attachment'] = $imgval;
			$rattach['remote'] = 2;
			$rattach['url'] = '';
			$rattach['isimage'] = 1;
			$post['attachments'][$rattach['aid']] = $rattach;
			$rep_imgarr[] = "\n<attach>{$rattach['aid']}</attach>\n";
		}
		$postinfo['message'] = str_replace($imgarr[0],$rep_imgarr,$postinfo['message']);
	}
	$postinfo['message'] = preg_replace('/[\r\n]{2,}/',"\n",$postinfo['message']);
	//end

	$postinfo['position'] = $post['position'];
	$postinfo['attachments'] = array();
	
	$attachments = array();
	foreach($post['attachments'] as $aid => $attach)
	{
		$tmparr = array();
		$tmparr['aid'] = $attach['aid']; 
//		$tmparr['tid'] = $attach['tid']; 
//		$tmparr['pid'] = $attach['pid']; 
//		$tmparr['uid'] = $attach['uid']; 
//		$tmparr['dateline'] = strip_tags($attach['dateline']); 
//		$tmparr['dbdateline'] = date('Y-m-d H:i:s',$attach['dbdateline']); 
		if(BFD_APP_THUMB_IMAGE_PATH_URL_DIY && $attach['remote']==1)
		{
			$tmparr['attachment'] = BFD_APP_THUMB_IMAGE_PATH_URL_DIY.'forum/'.$attach['attachment'];
		}
		else if($attach['remote'] == 2)
		{
			$tmparr['attachment'] = $attach['attachment'];
		}
		else
		{
			$tmparr['attachment'] = BFD_APP_DATA_URL_PRE.$attach['url'].$attach['attachment'];
		}
		$tmparr['isimage'] = $attach['isimage']; 
		if($attach['isimage'])
		{
			
			if(BFD_APP_THUMB_IMAGE_PATH_URL_DIY && $attach['remote'] == 1)
			{
				$imagefile = BFD_APP_THUMB_IMAGE_PATH_URL_DIY.'forum/'.$attach['attachment'];
			}
			else if($attach['remote'] == 2)
			{
				$imagefile = $attach['attachment'];
			}
			else
			{
				$imagefile = getglobal('setting/attachdir').'forum/'.$attach['attachment'];
			}
			/*
			if($attach['width'] <= BFD_APP_THUMB_IMAGE_WIDTH)
			{
				$imageinfo = @getimagesize($imagefile);
				if($imageinfo)
				{
					$attach['height'] = $imageinfo[1].'';
					$attach['width'] = $imageinfo[0].'';
				}
			}
			else
			{
				$dist = BfdApp::bfd_app_get_thumb_image($imagefile,BFD_APP_THUMB_IMAGE_WIDTH);
				if($dist)
				{
					if(!strpos($dist,'ttp:'))
					{
						$tmparr['attachment'] = BFD_APP_THUMB_IMAGE_PATH_URL.$dist;
						$distfile =  getglobal('setting/attachdir').$dist;
					}
					else
					{
						$tmparr['attachment'] = $dist;
						$distfile = $dist;
					}
					$imageinfo = @getimagesize($distfile);
					if($imageinfo)
					{
						$attach['width'] = $imageinfo[0].'';
						$attach['height'] = $imageinfo[1].'';
					}
				}
			}
			*/
			$attach['width'] = '0';
			$attach['height'] = '0';
			$tmparr['thumbwidth'] = BFD_APP_THUMB_IMAGE_THUMB_WIDTH;
			$tmparr['thumbheight'] = BFD_APP_THUMB_IMAGE_THUMB_HEIGHT;
			//$tmparr['thumbattachurl'] = BFD_APP_ATTACH_IMAGE_FIX_URL."?aid={$aid}&width={$tmparr['thumbwidth']}&height={$tmparr['thumbheight']}";
			//如果压缩失败，取原地址
			$tmparr['thumbattachurl'] = $tmparr['attachment'];
			$thumbimg = BfdApp::bfd_app_get_thumb_image($imagefile,$tmparr['thumbwidth'],$tmparr['thumbheight'],75,0,1);
			if($thumbimg)
			{
				$tmparr['thumbattachurl'] = BFD_APP_THUMB_IMAGE_PATH_URL.$thumbimg;
				$thumbimgpath = $_G['setting']['attachdir'].$thumbimg;
				$thumbinfo = getimagesize($thumbimgpath);
				$dw =  $thumbinfo[0];
				$dh =  $thumbinfo[1];
				if($dw > $tmparr['thumbwidth'])
				{
					$tmparr['thumbheight'] = $dh * ($tmparr['thumbwidth'] / $dw);
				}
				else
				{
					$tmparr['thumbwidth'] = $dw;
					$tmparr['thumbheight'] = $dh;
				}
			}
			else if($attach['remote'] == 2)
			{
				$tmparr['thumbattachurl'] = $attach['attachment'];
			}
		}
		//$tmparr['width'] = $attach['width']; 
		//$tmparr['height'] = empty($attach['height']) ? BFD_APP_THUMB_IMAGE_HEIGHT : $attach['height']; 
		//$tmparr['height'] = 0;
		$tmparr['filesize'] = $attach['filesize']; 
		$tmparr['remote'] = $attach['remote'];
		$postinfo['attachments'][$aid] = $tmparr;
	}

	//投票贴
	if($_G['forum_thread']['special']==1 && $post['first'] == 1)
	{
		$postinfo['pollinfo'] = $threadpollinfo;
	}
	//活动帖
	if($_G['forum_thread']['special']==4 && $post['first'] == 1)
	{	
		$postinfo['activityinfo'] = $threadapplyinfo;
	}
	//admin 权限
	if($_G['member']['groupid'] == '1')
    {
        $postinfo['power'] = array(
			'delete' => 1,
		);
    }
    else
    {
        $postinfo['power'] = array(
			'delete' => 0,
		);
    }
	if($_G['group']['raterange'] && $postinfo['authorid'])
	{
		$postinfo['power']['rate'] = 1;
	}
	else
	{
		$postinfo['power']['rate'] = 0;
	}
	$result[] = $postinfo;	
}

$showresult = array();
$showresult['fid'] = (string)$_G['fid'];
$showresult['tid'] = (string)$_G['tid'];
$showresult['subject'] = $thread['subject'];
$showresult['pid'] = (string)$showpid;
$showresult['postno'] = (string)$postno;
$showresult['page'] = (string)$page;
$showresult['pagetotal'] = (string)$totalpage;

$fav = C::t('home_favorite')->fetch_by_id_idtype($_G['tid'], 'tid', $_G['uid']);
if($fav) {
	$showresult['is_fav'] = 1;
	$showresult['favid'] = $fav['favid'];
}
else
{
	$showresult['is_fav'] = 0;
	$showresult['favid'] = 0;
}

BfdApp::display_result('get_success',$result,'',$showresult);



function viewthread_updateviews($tableid) {
	global $_G;

	if(!$_G['setting']['preventrefresh'] || $_G['cookie']['viewid'] != 'tid_'.$_G['tid']) {
		if(!$tableid && $_G['setting']['optimizeviews']) {
			if($_G['forum_thread']['addviews']) {
				if($_G['forum_thread']['addviews'] < 100) {
					C::t('forum_threadaddviews')->update_by_tid($_G['tid']);
				} else {
					if(!discuz_process::islocked('update_thread_view')) {
						$row = C::t('forum_threadaddviews')->fetch($_G['tid']);
						C::t('forum_threadaddviews')->update($_G['tid'], array('addviews' => 0));
						C::t('forum_thread')->increase($_G['tid'], array('views' => $row['addviews']+1), true);
						discuz_process::unlock('update_thread_view');
					}
				}
			} else {
				C::t('forum_threadaddviews')->insert(array('tid' => $_G['tid'], 'addviews' => 1), false, true);
			}
		} else {
			C::t('forum_thread')->increase($_G['tid'], array('views' => 1), true, $tableid);
		}
	}
	dsetcookie('viewid', 'tid_'.$_G['tid']);
}

function viewthread_procpost($post, $lastvisit, $ordertype, $maxposition = 0) {
	global $_G, $rushreply;

	if(!$_G['forum_newpostanchor'] && $post['dateline'] > $lastvisit) {
		$post['newpostanchor'] = '<a name="newpost"></a>';
		$_G['forum_newpostanchor'] = 1;
	} else {
		$post['newpostanchor'] = '';
	}

	$post['lastpostanchor'] = ($ordertype != 1 && $_G['forum_numpost'] == $_G['forum_thread']['replies']) || ($ordertype == 1 && $_G['forum_numpost'] == $_G['forum_thread']['replies'] + 2) ? '<a name="lastpost"></a>' : '';

	if($_G['forum_pagebydesc']) {
		if($ordertype != 1) {
			$post['number'] = $_G['forum_numpost'] + $_G['forum_ppp2']--;
		} else {
			$post['number'] = $post['first'] == 1 ? 1 : ($_G['forum_numpost'] - 1) - $_G['forum_ppp2']--;
		}
	} else {
		if($ordertype != 1) {
			$post['number'] = ++$_G['forum_numpost'];
		} else {
			$post['number'] = $post['first'] == 1 ? 1 : --$_G['forum_numpost'];
			$post['number'] = $post['number'] - 1;
		}
	}

	if($maxposition) {
		$post['number'] = $post['position'];
	}
	$_G['forum_postcount']++;

	$post['dbdateline'] = $post['dateline'];
	$post['dateline'] = dgmdate($post['dateline'], 'u', '9999', getglobal('setting/dateformat').' H:i:s');
	$post['groupid'] = $_G['cache']['usergroups'][$post['groupid']] ? $post['groupid'] : 7;

	if($post['username']) {

		$_G['forum_onlineauthors'][$post['authorid']] = 0;
		$post['usernameenc'] = rawurlencode($post['username']);
		$post['readaccess'] = $_G['cache']['usergroups'][$post['groupid']]['readaccess'];
		if($_G['cache']['usergroups'][$post['groupid']]['userstatusby'] == 1) {
			$post['authortitle'] = $_G['cache']['usergroups'][$post['groupid']]['grouptitle'];
			$post['stars'] = $_G['cache']['usergroups'][$post['groupid']]['stars'];
		}
		$post['upgradecredit'] = false;
		if($_G['cache']['usergroups'][$post['groupid']]['type'] == 'member' && $_G['cache']['usergroups'][$post['groupid']]['creditslower'] != 999999999) {
			$post['upgradecredit'] = $_G['cache']['usergroups'][$post['groupid']]['creditslower'] - $post['credits'];
		}

		$post['taobaoas'] = addslashes($post['taobao']);
		$post['regdate'] = dgmdate($post['regdate'], 'd');
		$post['lastdate'] = dgmdate($post['lastvisit'], 'd');

		$post['authoras'] = !$post['anonymous'] ? ' '.addslashes($post['author']) : '';

		if($post['medals']) {
			loadcache('medals');
			foreach($post['medals'] = explode("\t", $post['medals']) as $key => $medalid) {
				list($medalid, $medalexpiration) = explode("|", $medalid);
				if(isset($_G['cache']['medals'][$medalid]) && (!$medalexpiration || $medalexpiration > TIMESTAMP)) {
					$post['medals'][$key] = $_G['cache']['medals'][$medalid];
					$post['medals'][$key]['medalid'] = $medalid;
					$_G['medal_list'][$medalid] = $_G['cache']['medals'][$medalid];
				} else {
					unset($post['medals'][$key]);
				}
			}
		}
/*COMMENTS
//默认头像为小头像 20121125 ep
*/
		$post['avatar'] = avatar($post['authorid'],'small');
/*COMMENTS END*/
		$post['groupicon'] = $post['avatar'] ? g_icon($post['groupid'], 1) : '';
		$post['banned'] = $post['status'] & 1;
		$post['warned'] = ($post['status'] & 2) >> 1;

	} else {
		if(!$post['authorid']) {
			$post['useip'] = substr($post['useip'], 0, strrpos($post['useip'], '.')).'.x';
		}
	}
	$post['attachments'] = array();
	$post['imagelist'] = $post['attachlist'] = '';

	if($post['attachment']) {
		if(1 || $_G['group']['allowgetattach'] || $_G['group']['allowgetimage']) {
			$_G['forum_attachpids'][] = $post['pid'];
			$post['attachment'] = 0;
			if(preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $post['message'], $matchaids)) {
				$_G['forum_attachtags'][$post['pid']] = $matchaids[1];
			}
		} else {
			$post['message'] = preg_replace("/\[attach\](\d+)\[\/attach\]/i", '', $post['message']);
		}
	}

	if($_G['setting']['ratelogrecord'] && $post['ratetimes']) {
		$_G['forum_cachepid'][$post['pid']] = $post['pid'];
	}
	if($_G['setting']['commentnumber'] && ($post['first'] && $_G['setting']['commentfirstpost'] || !$post['first']) && $post['comment']) {
		$_G['forum_cachepid'][$post['pid']] = $post['pid'];
	}
	$post['allowcomment'] = $_G['setting']['commentnumber'] && in_array(1, $_G['setting']['allowpostcomment']) && ($_G['setting']['commentpostself'] || $post['authorid'] != $_G['uid']) &&
		($post['first'] && $_G['setting']['commentfirstpost'] && in_array($_G['group']['allowcommentpost'], array(1, 3)) ||
		(!$post['first'] && in_array($_G['group']['allowcommentpost'], array(2, 3))));
	$forum_allowbbcode = $_G['forum']['allowbbcode'] ? -$post['groupid'] : 0;
	$post['signature'] = $post['usesig'] ? ($_G['setting']['sigviewcond'] ? (strlen($post['message']) > $_G['setting']['sigviewcond'] ? $post['signature'] : '') : $post['signature']) : '';
	if(!defined('IN_ARCHIVER')) {
//		$post['message'] = discuzcode($post['message'], $post['smileyoff'], $post['bbcodeoff'], $post['htmlon'] & 1, $_G['forum']['allowsmilies'], $forum_allowbbcode, ($_G['forum']['allowimgcode'] && $_G['setting']['showimages'] ? 1 : 0), $_G['forum']['allowhtml'], ($_G['forum']['jammer'] && $post['authorid'] != $_G['uid'] ? 1 : 0), 0, $post['authorid'], $_G['cache']['usergroups'][$post['groupid']]['allowmediacode'] && $_G['forum']['allowmediacode'], $post['pid'], $_G['setting']['lazyload'], $post['dbdateline']);
/*		if($post['first']) {
			$_G['relatedlinks'] = '';
			$relatedtype = !$_G['forum_thread']['isgroup'] ? 'forum' : 'group';
			if(!$_G['setting']['relatedlinkstatus']) {
				$_G['relatedlinks'] = get_related_link($relatedtype);
			} else {
				$post['message'] = parse_related_link($post['message'], $relatedtype);
			}

		}
*/
		$post['message'] = bfd_app_filter_bbscode($post['message'],$post['pid'],$post['authorid']);
	}
	$_G['forum_firstpid'] = intval($_G['forum_firstpid']);
	$post['custominfo'] = viewthread_custominfo($post);
	$post['mobiletype'] = getstatus($post['status'], 4) ? base_convert(getstatus($post['status'], 10).getstatus($post['status'], 9).getstatus($post['status'], 8), 2, 10) : 0;
	return $post;
}

function viewthread_loadcache() {
	global $_G;
	$_G['forum']['livedays'] = ceil((TIMESTAMP - $_G['forum']['dateline']) / 86400);
	$_G['forum']['lastpostdays'] = ceil((TIMESTAMP - $_G['forum']['lastthreadpost']) / 86400);
	$threadcachemark = 100 - (
	$_G['forum']['displayorder'] * 15 +
	$_G['thread']['digest'] * 10 +
	min($_G['thread']['views'] / max($_G['forum']['livedays'], 10) * 2, 50) +
	max(-10, (15 - $_G['forum']['lastpostdays'])) +
	min($_G['thread']['replies'] / $_G['setting']['postperpage'] * 1.5, 15));
	if($threadcachemark < $_G['forum']['threadcaches']) {

		$threadcache = getcacheinfo($_G['tid']);

		if(TIMESTAMP - $threadcache['filemtime'] > $_G['setting']['cachethreadlife']) {
			@unlink($threadcache['filename']);
			define('CACHE_FILE', $threadcache['filename']);
		} else {
			readfile($threadcache['filename']);

			viewthread_updateviews($_G['forum_thread']['threadtableid']);
			$_G['setting']['debug'] && debuginfo();
			$_G['setting']['debug'] ? die('<script type="text/javascript">document.getElementById("debuginfo").innerHTML = " '.($_G['setting']['debug'] ? 'Updated at '.gmdate("H:i:s", $threadcache['filemtime'] + 3600 * 8).', Processed in '.$debuginfo['time'].' second(s), '.$debuginfo['queries'].' Queries'.($_G['gzipcompress'] ? ', Gzip enabled' : '') : '').'";</script>') : die();
		}
	}
}

function viewthread_lastmod(&$thread) {
	global $_G;
	if(!$thread['moderated']) {
		return array();
	}
	$lastmod = array();
	$lastlog = C::t('forum_threadmod')->fetch_by_tid($thread['tid']);
	if($lastlog) {
		$lastmod = array(
					'moduid' => $lastlog['uid'],
					'modusername' => $lastlog['username'],
					'moddateline' => $lastlog['dateline'],
					'modaction' => $lastlog['action'],
					'magicid' => $lastlog['magicid'],
					'stamp' => $lastlog['stamp'],
					'reason' => $lastlog['reason']
				);
	}
	if($lastmod) {
		$modactioncode = lang('forum/modaction');
		$lastmod['modusername'] = $lastmod['modusername'] ? $lastmod['modusername'] : 'System';
		$lastmod['moddateline'] = dgmdate($lastmod['moddateline'], 'u');
		$lastmod['modactiontype'] = $lastmod['modaction'];
		if($modactioncode[$lastmod['modaction']]) {
			$lastmod['modaction'] = $modactioncode[$lastmod['modaction']].($lastmod['modaction'] != 'SPA' ? '' : ' '.$_G['cache']['stamps'][$lastmod['stamp']]['text']);
		} elseif(substr($lastmod['modaction'], 0, 1) == 'L' && preg_match('/L(\d\d)/', $lastmod['modaction'], $a)) {
			$lastmod['modaction'] = $modactioncode['SLA'].' '.$_G['cache']['stamps'][intval($a[1])]['text'];
		} else {
			$lastmod['modaction'] = '';
		}
		if($lastmod['magicid']) {
			loadcache('magics');
			$lastmod['magicname'] = $_G['cache']['magics'][$lastmod['magicid']]['name'];
		}
	} else {
		C::t('forum_thread')->update($thread['tid'], array('moderated' => 0), false, false, $thread['threadtableid']);
		$thread['moderated'] = 0;
	}
	return $lastmod;
}

function viewthread_custominfo($post) {
	global $_G;

	$types = array('left', 'menu');
	foreach($types as $type) {
		if(!is_array($_G['cache']['custominfo']['setting'][$type])) {
			continue;
		}
		$data = '';
		foreach($_G['cache']['custominfo']['setting'][$type] as $key => $order) {
			$v = '';
			if(substr($key, 0, 10) == 'extcredits') {
				$i = substr($key, 10);
				$extcredit = $_G['setting']['extcredits'][$i];
				if($extcredit) {
					$v = '<dt>'.($extcredit['img'] ? $extcredit['img'].' ' : '').$extcredit['title'].'</dt><dd>'.$post['extcredits'.$i].' '.$extcredit['unit'].'</dd>';
				}
			} elseif(substr($key, 0, 6) == 'field_') {
				$field = substr($key, 6);
				if(!empty($post['privacy']['profile'][$field])) {
					continue;
				}
				require_once libfile('function/profile');
				$v = profile_show($field, $post);
				if($v) {
					$v = '<dt>'.$_G['cache']['custominfo']['profile'][$key][0].'</dt><dd title="'.dhtmlspecialchars(strip_tags($v)).'">'.$v.'</dd>';
				}
			} elseif($key == 'creditinfo') {
				$v = '<dt>'.lang('space', 'viewthread_userinfo_buyercredit').'</dt><dd><a href="home.php?mod=space&uid='.$post['uid'].'&do=trade&view=eccredit#buyercredit" target="_blank" class="vm"><img src="'.STATICURL.'image/traderank/seller/'.countlevel($post['buyercredit']).'.gif" /></a></dd>';
				$v .= '<dt>'.lang('space', 'viewthread_userinfo_sellercredit').'</dt><dd><a href="home.php?mod=space&uid='.$post['uid'].'&do=trade&view=eccredit#sellercredit" target="_blank" class="vm"><img src="'.STATICURL.'image/traderank/seller/'.countlevel($post['sellercredit']).'.gif" /></a></dd>';
			} else {
				switch($key) {
					case 'uid': $v = $post['uid'];break;
					case 'posts': $v = '<a href="home.php?mod=space&uid='.$post['uid'].'&do=thread&type=reply&view=me&from=space" target="_blank" class="xi2">'.$post['posts'].'</a>';break;
					case 'threads': $v = '<a href="home.php?mod=space&uid='.$post['uid'].'&do=thread&type=thread&view=me&from=space" target="_blank" class="xi2">'.$post['threads'].'</a>';break;
					case 'doings': $v = '<a href="home.php?mod=space&uid='.$post['uid'].'&do=doing&view=me&from=space" target="_blank" class="xi2">'.$post['doings'].'</a>';break;
					case 'blogs': $v = '<a href="home.php?mod=space&uid='.$post['uid'].'&do=blog&view=me&from=space" target="_blank" class="xi2">'.$post['blogs'].'</a>';break;
					case 'albums': $v = '<a href="home.php?mod=space&uid='.$post['uid'].'&do=album&view=me&from=space" target="_blank" class="xi2">'.$post['albums'].'</a>';break;
					case 'sharings': $v = '<a href="home.php?mod=space&uid='.$post['uid'].'&do=share&view=me&from=space" target="_blank" class="xi2">'.$post['sharings'].'</a>';break;
					case 'friends': $v = '<a href="home.php?mod=space&uid='.$post['uid'].'&do=friend&view=me&from=space" target="_blank" class="xi2">'.$post['friends'].'</a>';break;
					case 'follower': $v = '<a href="home.php?mod=follow&do=follower&uid='.$post['uid'].'" target="_blank" class="xi2">'.$post['follower'].'</a>';break;
					case 'following': $v = '<a href="home.php?mod=follow&do=following&uid='.$post['uid'].'" target="_blank" class="xi2">'.$post['following'].'</a>';break;
					case 'digest': $v = $post['digestposts'];break;
					case 'credits': $v = $post['credits'];break;
					case 'readperm': $v = $post['readaccess'];break;
					case 'regtime': $v = $post['regdate'];break;
					case 'lastdate': $v = $post['lastdate'];break;
					case 'oltime': $v = $post['oltime'].' '.lang('space', 'viewthread_userinfo_hour');break;
				}
				if($v !== '') {
					$v = '<dt>'.lang('space', 'viewthread_userinfo_'.$key).'</dt><dd>'.$v.'</dd>';
				}
			}
			$data .= $v;
		}
		$return[$type] = $data;
	}
	return $return;
}
function countlevel($usercredit) {
	global $_G;

	$rank = 0;
	if($usercredit){
		foreach($_G['setting']['ec_credit']['rank'] AS $level => $credit) {
			if($usercredit <= $credit) {
				$rank = $level;
				break;
			}
		}
	}
	return $rank;
}
function remaintime($time) {
	$days = intval($time / 86400);
	$time -= $days * 86400;
	$hours = intval($time / 3600);
	$time -= $hours * 3600;
	$minutes = intval($time / 60);
	$time -= $minutes * 60;
	$seconds = $time;
	return array((int)$days, (int)$hours, (int)$minutes, (int)$seconds);
}

function getrelateitem($tagarray, $tid, $relatenum, $relatetime, $relatecache = '', $type = 'tid') {
	$tagidarray = $relatearray = $relateitem = array();
	$updatecache = 0;
	$limit = $relatenum;
	if(!$limit) {
		return '';
	}
	foreach($tagarray as $var) {
		$tagidarray[] = $var['0'];
	}
	if(!$tagidarray) {
		return '';
	}
	if(empty($relatecache)) {
		$thread = C::t('forum_thread')->fetch($tid);
		$relatecache = $thread['relatebytag'];
	}
	if($relatecache) {
		$relatecache = explode("\t", $relatecache);
		if(TIMESTAMP > $relatecache[0] + $relatetime * 60) {
			$updatecache = 1;
		} else {
			if(!empty($relatecache[1])) {
				$relatearray = explode(',', $relatecache[1]);
			}
		}
	} else {
		$updatecache = 1;
	}
	if($updatecache) {
		$query = C::t('common_tagitem')->select($tagidarray, $tid, $type, '', '', $limit, 0, '<>');
		foreach($query as $result) {
			if($result['itemid']) {
				$relatearray[] = $result['itemid'];
			}
		}
		if($relatearray) {
			$relatebytag = implode(',', $relatearray);
		}
		C::t('forum_thread')->update($tid, array('relatebytag'=>TIMESTAMP."\t".$relatebytag));
	}


	if(!empty($relatearray)) {
		foreach(C::t('forum_thread')->fetch_all_by_tid($relatearray) as $result) {
			if($result['displayorder'] >= 0) {
				$relateitem[] = $result;
			}
		}
	}
	return $relateitem;
}

function rushreply_rule () {
	global $rushresult;
	if(!empty($rushresult['rewardfloor'])) {
		$rushresult['rewardfloor'] = preg_replace('/\*+/', '*', $rushresult['rewardfloor']);
		$rewardfloorarr = explode(',', $rushresult['rewardfloor']);
		if($rewardfloorarr) {
			foreach($rewardfloorarr as $var) {
				$var = trim($var);
				if(strlen($var) > 1) {
					$var = str_replace('*', '[^,]?[\d]*', $var);
				} else {
					$var = str_replace('*', '\d+', $var);
				}
				$preg[] = "(,$var,)";
			}
			$preg_str = "/".implode('|', $preg)."/";
		}
	}
	return $preg_str;
}

function checkrushreply($post) {
	global $_G, $rushids;
	if($_GET['authorid']) {
		return $post;
	}
	if(in_array($post['number'], $rushids)) {
		$post['rewardfloor'] = 1;
	}
	return $post;
}

function bfd_app_parseattach($attachpids, $attachtags, &$postlist, $skipaids = array()) {
	global $_G;
	if(!$attachpids) {
		return;
	}
	$attachpids = is_array($attachpids) ? $attachpids : array($attachpids);
	$attachexists = FALSE;
	$skipattachcode = $aids = $payaids = $findattach = array();
	foreach(C::t('forum_attachment_n')->fetch_all_by_id('tid:'.$_G['tid'], 'pid', $attachpids) as $attach) {
		$attachexists = TRUE;
		if($skipaids && in_array($attach['aid'], $skipaids)) {
			$skipattachcode[$attach[pid]][] = "/\[attach\]$attach[aid]\[\/attach\]/i";
			continue;
		}
		$attached = 0;
		$extension = strtolower(fileext($attach['filename']));
		$attach['ext'] = $extension;
		$attach['imgalt'] = $attach['isimage'] ? strip_tags(str_replace('"', '\"', $attach['description'] ? $attach['description'] : $attach['filename'])) : '';
		$attach['attachicon'] = attachtype($extension."\t".$attach['filetype']);
		$attach['attachsize'] = sizecount($attach['filesize']);
		if($attach['isimage'] && !$_G['setting']['attachimgpost']) {
			$attach['isimage'] = 0;
		}
		$attach['attachimg'] = $attach['isimage'] && (!$attach['readperm'] || $_G['group']['readaccess'] >= $attach['readperm']) ? 1 : 0;
		if($attach['attachimg']) {
			$GLOBALS['aimgs'][$attach['pid']][] = $attach['aid'];
		}
		if($attach['price']) {
			if($_G['setting']['maxchargespan'] && TIMESTAMP - $attach['dateline'] >= $_G['setting']['maxchargespan'] * 3600) {
				C::t('forum_attachment_n')->update('tid:'.$_G['tid'], $attach['aid'], array('price' => 0));
				$attach['price'] = 0;
			} elseif(!$_G['forum_attachmentdown'] && $_G['uid'] != $attach['uid']) {
				$payaids[$attach['aid']] = $attach['pid'];
			}
		}
		$attach['payed'] = $_G['forum_attachmentdown'] || $_G['uid'] == $attach['uid'] ? 1 : 0;
		$attach['url'] = ($attach['remote'] ? $_G['setting']['ftp']['attachurl'] : $_G['setting']['attachurl']).'forum/';
		$attach['dbdateline'] = $attach['dateline'];
		$attach['dateline'] = dgmdate($attach['dateline'], 'u');
		$postlist[$attach['pid']]['attachments'][$attach['aid']] = $attach;
		if(!defined('IN_MOBILE_API') && !empty($attachtags[$attach['pid']]) && is_array($attachtags[$attach['pid']]) && in_array($attach['aid'], $attachtags[$attach['pid']])) {
			$findattach[$attach['pid']][$attach['aid']] = "/\[attach\]$attach[aid]\[\/attach\]/i";
			$attached = 1;
		}

		if(!$attached) {
			if($attach['isimage']) {
				$postlist[$attach['pid']]['imagelist'][] = $attach['aid'];
				$postlist[$attach['pid']]['imagelistcount']++;
				if($postlist[$attach['pid']]['first']) {
					$GLOBALS['firstimgs'][] = $attach['aid'];
				}
			} else {
				if(!$_G['forum_skipaidlist'] || !in_array($attach['aid'], $_G['forum_skipaidlist'])) {
					$postlist[$attach['pid']]['attachlist'][] = $attach['aid'];
				}
			}
		}
		$aids[] = $attach['aid'];
	}
	if($aids) {
		$attachs = C::t('forum_attachment')->fetch_all($aids);
		foreach($attachs as $aid => $attach) {
			if($postlist[$attach['pid']]) {
				$postlist[$attach['pid']]['attachments'][$attach['aid']]['downloads'] = $attach['downloads'];
			}
		}
	}
	if($payaids) {
		foreach(C::t('common_credit_log')->fetch_all_by_uid_operation_relatedid($_G['uid'], 'BAC', array_keys($payaids)) as $creditlog) {
			$postlist[$payaids[$creditlog['relatedid']]]['attachments'][$creditlog['relatedid']]['payed'] = 1;
		}
	}
	if(!empty($skipattachcode)) {
		foreach($skipattachcode as $pid => $findskipattach) {
			foreach($findskipattach as $findskip) {
				$postlist[$pid]['message'] = preg_replace($findskip, '', $postlist[$pid]['message']);
			}
		}
	}
	/*if($attachexists) {
		foreach($attachtags as $pid => $aids) {
			if($findattach[$pid]) {
				foreach($findattach[$pid] as $aid => $find) {
					$postlist[$pid]['message'] = preg_replace($find, attachinpost($postlist[$pid]['attachments'][$aid], $postlist[$pid]), $postlist[$pid]['message'], 1);
					$postlist[$pid]['message'] = preg_replace($find, '', $postlist[$pid]['message']);
				}
			}
		}
	}
	*/
}

function bfd_app_filter_bbscode($message,$pid=0,$authorid=0)
{
	global $_G;
	$message = trim($message);
	if(empty($message))
	{
		return '';
	}
	$message = strip_tags($message);
	if((strpos($message, '[/code]') || strpos($message, '[/CODE]')) !== FALSE) {
                $message = preg_replace("/\s?\[code\](.+?)\[\/code\]\s?/ies", "", $message);
        }
	
	if($_G['setting']['plugins']['func']['hookscript']['discuzcode'] && isset($_G['setting']['hookscript']['global']['discuzcode']['funcs']))
	{
		$_G['discuzcodemessage'] = & $message;
		if(file_exists( DISCUZ_ROOT .'source/plugin/soso_smilies/soso.class.php'))
		{
				require_once(DISCUZ_ROOT .'source/plugin/soso_smilies/soso.class.php');
				$obj = new plugin_soso_smilies();
				$param = array(
					'param' => array(1=>0,4=>1,12=>0),
					'caller' => 'discuzcode',
				);
				$obj->discuzcode($param);
				$smilies = array();
				$replace = array();
				$flag = preg_match_all('/<img .*src="([^"]*)".*smilieid="([^"]*)".*\/>/U',$message,$smilies);
				if($flag)
				{
					foreach($smilies[2] as $key => $val)
					{
						if(substr($val,0,6) == 'soso__')
						{
							$replace[$key] = '<img src="'.$smilies[1][$key].'" width="64" height="64"/>';
						}
						else
						{
							$replace[$key] = '<img src="'.$smilies[1][$key].'" width="24" height="24"/>';
						}
					}
					$message = str_replace($smilies[0],$replace,$message);
				}
		}
		//$message = preg_replace('/<img .*src="([^"]*)".*\/>/U','<img src="\\1" width="24" height="24"/>',$message);
	}

	$msglower = strtolower($message);


	if(strpos($msglower,'<script') !== FALSE){
        	$message = preg_replace("/<script[^\>]*?>(.*?)<\/script>/i", '', $message);
	}

	if($_G['setting']['allowattachurl'] && strpos($msglower, 'attach://') !== FALSE) {
                $message = preg_replace("/attach:\/\/(\d+)\.?(\w*)/ie", "", $message);
        }

        if(strpos($msglower, 'ed2k://') !== FALSE) {
		$message = preg_replace("/ed2k:\/\/(.+?)\//e", "", $message);
	}
	if(strpos($msglower, '[/email]') !== FALSE) {
		$message = preg_replace("/\[email(=([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+))?\](.+?)\[\/email\]/ies", "", $message);
	}
	$nest = 0;
	if(strpos($msglower, '[table') !== FALSE && strpos($msglower, '[/table]') !== FALSE){
		$message = preg_replace("/\[table(?:=(\d{1,4}%?)(?:,([\(\)%,#\w ]+))?)?\]\s*(.+?)\s*\[\/table\]/ies", "", $message);
	}
	if(strpos($msglower, '[/media]') !== FALSE) {
		//$message = preg_replace("/\[media=([\w,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/iUs", "<url type=\"15\" href=\"\\2\">Media</url>", $message);
		$message = preg_replace("/\[media=([\w,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/iUs", "<url type=\"15\" href=\"\\2\"><img  src=\"http://x3gbk.discuzfan.com/static/image/smiley/default/huffy.gif\"></url>", $message);
	//	$message = preg_replace("/\[media=([\w,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/iUs", "<img type=\"15\" width=\"35\" height=\"35\" src=\"\\2\"/>", $message);
	}
	if(strpos($msglower, '[/audio]') !== FALSE) {
		$message = preg_replace("/\[audio(=1)*\]\s*([^\[\<\r\n]+?)\s*\[\/audio\]/ies", '', $message);
	}
	if(strpos($msglower, '[/flash]') !== FALSE) {
		//$message = preg_replace("/\[flash(=(\d+),(\d+))?\]\s*([^\[\<\r\n]+?)\s*\[\/flash\]/iUs", "<url type=\"15\" href=\"\\4\">Media</url>", $message);
		$message = preg_replace("/\[flash(=(\d+),(\d+))?\]\s*([^\[\<\r\n]+?)\s*\[\/flash\]/iUs", "<url type=\"15\" href=\"\\4\"><img  src=\"http://x3gbk.discuzfan.com/static/image/smiley/default/huffy.gif\"></url>", $message);
	//	$message = preg_replace("/\[flash(=(\d+),(\d+))?\]\s*([^\[\<\r\n]+?)\s*\[\/flash\]/iUs", "<img type=\"15\" width=\"35\" height=\"35\"  src=\"\\4\"/>", $message);
	}

	if(strpos($msglower, '[swf]') !== FALSE) {
		$message = preg_replace("/\[swf\]\s*([^\[\<\r\n]+?)\s*\[\/swf\]/ies", "", $message);
	}
	if(strpos($msglower, '[/free]') !== FALSE) {
		$message = preg_replace("/\s*\[free\][\n\r]*(.+?)[\n\r]*\[\/free\]\s*/is", '', $message);	 
	}
	
	$message = str_replace(array(
			'[/color]', '[/backcolor]', '[/size]', '[/font]', '[/align]', '[b]', '[/b]', '[s]', '[/s]', '[hr]', '[/p]',
			'[i=s]', '[i]', '[/i]', '[u]', '[/u]', '[list]', '[list=1]', '[list=a]',
			'[list=A]', "\r\n[*]", '[*]', '[/list]', '[indent]', '[/indent]', '[/float]'
			), array(
			''), 
			preg_replace(array(
			"/\[color=([#\w]+?)\]/i",
			"/\[color=(rgb\([\d\s,]+?\))\]/i",
			"/\[backcolor=([#\w]+?)\]/i",
			"/\[backcolor=(rgb\([\d\s,]+?\))\]/i",
			"/\[size=(\d{1,2}?)\]/i",
			"/\[size=(\d{1,2}(\.\d{1,2}+)?(px|pt)+?)\]/i",
			"/\[font=([^\[\<]+?)\]/i",
			"/\[align=(left|center|right)\]/i",
			"/\[p=(\d{1,2}|null), (\d{1,2}|null), (left|center|right)\]/i",
			"/\[float=left\]/i",
			"/\[float=right\]/i",
		//	"/\[url=.*\]\[img\].*\[\/img\]\[\/url\]/iU",
		//	"/\[img\].*\[\/img\]/iU",

			), array(
			"",
			), $message));
	if(strpos($msglower, '[/quote]') !== FALSE) {
		$tmpflag = preg_match_all("/\s?\[quote\][\n\r]*(.+?)[\n\r]*\[\/quote\]\s*/is",$message,$tmpresult);
		if($tmpflag)
		{
			$replace_arr = array();
			foreach($tmpresult[1] as $val)
			{
				$replace_arr[] = '<quote>'.preg_replace('/\[.*\]/iUs','',$val).'</quote>'."\n";
			}
		}
		$message = str_replace($tmpresult[0],$replace_arr,$message);
		//$quotemsg = preg_replace("/\[.*\]/","\\1",$tmpresult[1]);
		//$quotemsg = trim($quotemsg);
		//$message = preg_replace("/\s?\[quote\][\n\r]*(.+?)[\n\r]*\[\/quote\]\s*/is", "<quote>{$quotemsg}</quote>\n", $message);
		//$message = preg_replace("/\s?\[quote\][\n\r]*(.+?)[\n\r]*\[\/quote\]\s*/is", "<quote>\\1</quote>\n", $message);
	}
	if(strpos($msglower, '[/attach]') !== FALSE)
	{
		$message = preg_replace("/\[attach\](\d+)\[\/attach\]/iUs", "<attach>\\1</attach>", $message);
	}
	if(strpos($msglower, '[/url]') !== FALSE)
	{
		$pattern = "/\[url(.*)\](.*)\[\/url\]/iUs";
		$tmparr = array();
		$flag = preg_match_all($pattern,$message,$tmparr);	
		if($flag)
		{
			$count = count($tmparr['1']);
			$source_arr = array();
			$dest_arr = array();
			for($i = 0; $i < $count; $i++)
			{
				if(substr($tmparr['1'][$i],0,1) == '=')
				{
					$tmparr['1'][$i] = substr($tmparr[1][$i],1);
				}
				if(substr($tmparr[1][$i],0,4) == 'www.')
				{
					$tmparr[1][$i] = 'http://'.$tmparr[1][$i];
				}
				if(substr($tmparr[2][$i],0,1) == '@')
				{
					$flag = preg_match('/uid=(\d+)/',$tmparr[1][$i],$uid);
					if($uid[1])
					{
						$tmpstr = "<url type='1' href='{$uid[1]}'>{$tmparr[2][$i]}</url>";	
					}
					else
					{
						$tmpstr = $tmparr[2][$i];
					}
				}
				elseif(strpos($tmparr[1][$i],$_SERVER['HTTP_HOST'].'/forum.php?mod=viewthread') !== false)
				{
					$flag = preg_match('/tid=(\d+)/',$tmparr[1][$i],$tid);
					if($tid[1])
					{
						$tmpstr = "<url type='2' href='{$tid[1]}'>{$tmparr[2][$i]}</url>";	
					}
					else
					{
						$tmpstr = $tmparr[2][$i];
					}
						
				}
				elseif(!empty($tmparr[1][$i]))
				{
					$tmpstr = "<url type='0' href='{$tmparr[1][$i]}'>{$tmparr[2][$i]}</url>";	
				}
				else
				{
					$tmpstr = "<url type='0' href='{$tmparr[2][$i]}'>{$tmparr[2][$i]}</url>";	
				}
				$source_arr[] = $tmparr[0][$i];
				$desc_arr[] = $tmpstr;
			}	
			$message = str_replace($source_arr,$desc_arr,$message);
		}
	}
	if(strpos($msglower,'[/img]') !== FALSE)
	{
		$message = preg_replace("/\[img[^]]*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/iUs","<img>\\1</img>",$message);
		$message = preg_replace("/\[img[^]]*\].*\[\/img\]/iUs","",$message);
	}	

	if(strpos($msglower, '[/hide]') !== FALSE && $pid) {
			if($_G['setting']['hideexpiration'] && $pdateline && (TIMESTAMP - $pdateline) / 86400 > $_G['setting']['hideexpiration']) {
				$message = preg_replace("/\[hide[=]?(d\d+)?[,]?(\d+)?\]\s*(.*?)\s*\[\/hide\]/is", "\\3", $message);
				$msglower = strtolower($message);
			}
			if(strpos($msglower, '[hide=d') !== FALSE) {
				$message = preg_replace("/\[hide=(d\d+)?[,]?(\d+)?\]\s*(.*?)\s*\[\/hide\]/ies", "dzapp_expirehide('\\1','\\2','\\3', $pdateline)", $message);
				$msglower = strtolower($message);
			}
			if(strpos($msglower, '[hide]') !== FALSE) {
				if($authorreplyexist === null) {
					if(!$_G['forum']['ismoderator']) {
						if($_G['uid']) {
							$authorreplyexist = C::t('forum_post')->fetch_pid_by_tid_authorid($_G['tid'], $_G['uid']);
						}
					} else {
						$authorreplyexist = TRUE;
					}
				}
				if($authorreplyexist) {
					$message = preg_replace("/\[hide\]\s*(.*?)\s*\[\/hide\]/is", dzapp_tpl_hide_reply(), $message);
				} else {
					$message = preg_replace("/\[hide\](.*?)\[\/hide\]/is", dzapp_tpl_hide_reply_hidden(), $message);
				}
			}
			if(strpos($msglower, '[hide=') !== FALSE) {
				$message = preg_replace("/\[hide=(\d+)\]\s*(.*?)\s*\[\/hide\]/ies", "dzapp_creditshide(\\1,'\\2', $pid, $authorid)", $message);
			}
		}
	return $message;
}

function dzapp_creditshide($creditsrequire, $message, $pid, $authorid) {
	global $_G;
	if($_G['member']['credits'] >= $creditsrequire || $_G['forum']['ismoderator'] || $_G['uid'] && $authorid == $_G['uid']) {
		$message = str_replace('\\"', '"', $message);
		$str  = $_G['lang']['forum']['post_hide_credits'];
		eval("\$str = \"$str\";");
		$message = $str . $message;
		return $message;
		//return tpl_hide_credits($creditsrequire, str_replace('\\"', '"', $message));
	} else {
		$str = $_G['lang']['forum']['post_hide_credits_hidden'];
		eval("\$str = \"$str\";");
		return $str; 
		//return tpl_hide_credits_hidden($creditsrequire);
	}
}
function dzapp_expirehide($expiration, $creditsrequire, $message, $dateline) {
	$expiration = $expiration ? substr($expiration, 1) : 0;
	if($expiration && $dateline && (TIMESTAMP - $dateline) / 86400 > $expiration) {
		return str_replace('\\"', '"', $message);
	}
	return '[hide'.($creditsrequire ? "=$creditsrequire" : '').']'.str_replace('\\"', '"', $message).'[/hide]';
}
function dzapp_tpl_hide_reply()
{
	global $_G;
	//return '***'.$_G['lang']['forum']['post_hide']."\\1***";
	return "\\1";
}
function dzapp_tpl_hide_reply_hidden()
{
	global $_G;
	$username = '';
	if($_G['username'])
	{
		$username = $_G['username'];
	}
	else
	{
		$username = $_G['lang']['forum']['guest'];
	}
	return '***'.$username . strip_tags($_G['lang']['forum']['post_hide_reply_hidden']).'***';
}
?>
