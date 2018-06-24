<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum_misc.php 31609 2012-09-13 09:09:43Z liulanbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
define('NOROBOT', TRUE);

require_once libfile('function/forumlist');
require_once libfile('function/forum');
require_once libfile('function/post');

loadforum();

	if(empty($_G['forum']['allowview'])) {
		if(!$_G['forum']['viewperm'] && !$_G['group']['readaccess']) {
			BfdApp::display_result('group_nopermission');
		} elseif($_G['forum']['viewperm'] && !forumperm($_G['forum']['viewperm'])) {
			BfdApp::display_result('forum_nopermission');
		}
	}

	$thread = C::t('forum_thread')->fetch($_G['tid']);
	if(!($thread['displayorder']>=0 || $thread['displayorder']==-4 && $thread['authorid']==$_G['uid'])) {
		$thread = array();
	}
	if($thread['readperm'] && $thread['readperm'] > $_G['group']['readaccess'] && !$_G['forum']['ismoderator'] && $thread['authorid'] != $_G['uid']) {
		BfdApp::display_result('thread_nopermission');
	}


//	BfdApp::check_forum_password();

	if(!$thread) {
		BfdApp::display_result('thread_nonexistence');
	}

if($_GET['action'] == 'votepoll') {

	$_GET['pollanswers'] = explode(',',$_GET['pollanswers']);
	if(!$_G['group']['allowvote']) {
		BfdApp::display_result('group_nopermission');
	} elseif(!empty($thread['closed'])) {
		BfdApp::display_result('thread_poll_closed');
	} elseif(empty($_GET['pollanswers'])) {
		BfdApp::display_result('thread_poll_invalid');
	}

	$pollarray = C::t('forum_poll')->fetch($_G['tid']);
	$overt = $pollarray['overt'];
	if(!$pollarray) {
		BfdApp::display_result('poll_not_found');
	} elseif($pollarray['expiration'] && $pollarray['expiration'] < TIMESTAMP) {
		BfdApp::display_result('poll_overdue');
	} elseif($pollarray['maxchoices'] && $pollarray['maxchoices'] < count($_GET['pollanswers'])) {
		BfdApp::display_result('poll_choose_most');
	}

	$voterids = $_G['uid'] ? $_G['uid'] : $_G['clientip'];

	$polloptionid = array();
	$query = C::t('forum_polloption')->fetch_all_by_tid($_G['tid']);
	foreach($query as $pollarray) {
		if(strexists("\t".$pollarray['voterids']."\t", "\t".$voterids."\t")) {
			BfdApp::display_result('thread_poll_voted');
		}
		$polloptionid[] = $pollarray['polloptionid'];
	}

	$polloptionids = '';
	foreach($_GET['pollanswers'] as $key => $id) {
		if(!in_array($id, $polloptionid)) {
			BfdApp::display_result('parameters_error');
		}
		unset($polloptionid[$key]);
		$polloptionids[] = $id;
	}
	if(empty($polloptionids))
	{
		BfdApp::display_result('thread_poll_invalid2');
	}

	C::t('forum_polloption')->update_vote($polloptionids, $voterids."\t", 1);
	C::t('forum_thread')->update($_G['tid'], array('lastpost'=>$_G['timestamp']), true);
	C::t('forum_poll')->update_vote($_G['tid']);
	C::t('forum_pollvoter')->insert(array(
		'tid' => $_G['tid'],
		'uid' => $_G['uid'],
		'username' => $_G['username'],
		'options' => implode("\t", $_GET['pollanswers']),
		'dateline' => $_G['timestamp'],
		));
	updatecreditbyaction('joinpoll');

	$space = array();
	space_merge($space, 'field_home');

	if($overt && !empty($space['privacy']['feed']['newreply'])) {
		$feed['icon'] = 'poll';
		$feed['title_template'] = 'feed_thread_votepoll_title';
		$feed['title_data'] = array(
			'subject' => "<a href=\"forum.php?mod=viewthread&tid=$_G[tid]\">$thread[subject]</a>",
			'author' => "<a href=\"home.php?mod=space&uid=$thread[authorid]\">$thread[author]</a>",
			'hash_data' => "tid{$_G[tid]}"
		);
		$feed['id'] = $_G['tid'];
		$feed['idtype'] = 'tid';
		postfeed($feed);
	}

	BfdApp::display_result('thread_poll_succeed');
} elseif($_GET['action'] == 'activityapplies') {

	if(!$_G['uid']) {
		BfdApp::display_result('not_loggedin',null,'','','1');
	}

	if($_GET['activitysubmit']) {
		$activity = C::t('forum_activity')->fetch($_G['tid']);

		if($activity['expiration'] && $activity['expiration'] < TIMESTAMP) {
			BfdApp::display_result('activity_stop',null,'','','1');
		}
		$applyinfo = array();
		$applyinfo = C::t('forum_activityapply')->fetch_info_for_user($_G['uid'], $_G['tid']);
		if($applyinfo && $applyinfo['verified'] < 2) {
			BfdApp::display_result('activity_repeat_apply',null,'','','1');
		}
		$payvalue = intval($_GET['payvalue']);
		$payment = $_GET['payment'] ? $payvalue : -1;
		$message = cutstr(dhtmlspecialchars($_GET['message']), 200);
		if( BFD_APP_CHARSET_OUTPUT != BFD_APP_CHARSET ){
		    //$message = iconv( BFD_APP_CHARSET_OUTPUT, BFD_APP_CHARSET."//IGNORE", $message );
		}
		$verified = $thread['authorid'] == $_G['uid'] ? 1 : 0;
		if($activity['ufield']) {
			$ufielddata = array();
			$activity['ufield'] = dunserialize($activity['ufield']);
			if(!empty($activity['ufield']['userfield'])) {
				$censor = discuz_censor::instance();
				loadcache('profilesetting');

				foreach($_POST as $key => $value) {
					if(empty($_G['cache']['profilesetting'][$key])) continue;
					if(is_array($value)) {
						$value = implode(',', $value);
					}
					$value = cutstr(dhtmlspecialchars(trim($value)), 100, '.');
					if( BFD_APP_CHARSET_OUTPUT != BFD_APP_CHARSET ){
//		    			$value = iconv( BFD_APP_CHARSET_OUTPUT, BFD_APP_CHARSET."//IGNORE", $value);
					}
					if($_G['cache']['profilesetting'][$key]['formtype'] == 'file' && !preg_match("/^https?:\/\/(.*)?\.(jpg|png|gif|jpeg|bmp)$/i", $value)) {
						BfdApp::display_result('activity_imgurl_error',null,'','','1');
					}
					if(empty($value) && $key != 'residedist' && $key != 'residecommunity') {
						BfdApp::display_result('activity_exile_field',null,'','','1');
					}
					$ufielddata['userfield'][$key] = $value;
				}
			}
			if(!empty($activity['ufield']['extfield'])) {
				foreach($activity['ufield']['extfield'] as $fieldid) {
					$value = cutstr(dhtmlspecialchars(trim($_GET[''.$fieldid])), 50, '.');
					if( BFD_APP_CHARSET_OUTPUT != BFD_APP_CHARSET ){
 // 						$value = iconv( BFD_APP_CHARSET_OUTPUT, BFD_APP_CHARSET."//IGNORE", $value);
					}
					$ufielddata['extfield'][$fieldid] = $value;
				}
			}
			$ufielddata = !empty($ufielddata) ? serialize($ufielddata) : '';
		}
		if($_G['setting']['activitycredit'] && $activity['credit'] && empty($applyinfo['verified'])) {
			lib_bfd_perm::checklowerlimit(array('extcredits'.$_G['setting']['activitycredit'] => '-'.$activity['credit']));
			updatemembercount($_G['uid'], array($_G['setting']['activitycredit'] => '-'.$activity['credit']), true, 'ACC', $_G['tid']);
		}
		if($applyinfo && $applyinfo['verified'] == 2) {
			$newinfo = array(
				'tid' => $_G['tid'],
				'username' => $_G['username'],
				'uid' => $_G['uid'],
				'message' => $message,
				'verified' => $verified,
				'dateline' => $_G['timestamp'],
				'payment' => $payment,
				'ufielddata' => $ufielddata
			);
			C::t('forum_activityapply')->update($applyinfo['appyid'], $newinfo);
		} else {
			$data = array('tid' => $_G['tid'], 'username' => $_G['username'], 'uid' => $_G['uid'], 'message' => $message, 'verified' => $verified, 'dateline' => $_G['timestamp'], 'payment' => $payment, 'ufielddata' => $ufielddata);
			C::t('forum_activityapply')->insert($data);
		}

		$applynumber = C::t('forum_activityapply')->fetch_count_for_thread($_G['tid']);
		C::t('forum_activity')->update($_G['tid'], array('applynumber' => $applynumber));

		if($thread['authorid'] != $_G['uid']) {
			notification_add($thread['authorid'], 'activity', 'activity_notice', array(
				'tid' => $_G['tid'],
				'subject' => $thread['subject'],
			));
			$space = array();
			space_merge($space, 'field_home');

			if(!empty($space['privacy']['feed']['newreply'])) {
				$feed['icon'] = 'activity';
				$feed['title_template'] = 'feed_reply_activity_title';
				$feed['title_data'] = array(
					'subject' => "<a href=\"forum.php?mod=viewthread&tid=$_G[tid]\">$thread[subject]</a>",
					'hash_data' => "tid{$_G[tid]}"
				);
				$feed['id'] = $_G['tid'];
				$feed['idtype'] = 'tid';
				postfeed($feed);
			}
		}
		
		BfdApp::display_result('activity_completion',null,'','','1');

	} elseif($_GET['activitycancel']) {
		C::t('forum_activityapply')->delete_for_user($_G['uid'], $_G['tid']);
		$applynumber = C::t('forum_activityapply')->fetch_count_for_thread($_G['tid']);
		C::t('forum_activity')->update($_G['tid'], array('applynumber' => $applynumber));
		$message = cutstr(dhtmlspecialchars($_GET['message']), 200);
		if($thread['authorid'] != $_G['uid']) {
			notification_add($thread['authorid'], 'activity', 'activity_cancel', array(
				'tid' => $_G['tid'],
				'subject' => $thread['subject'],
				'reason' => $message
			));
		}
		BfdApp::display_result('activity_cancel_success',null,'','','1');
	}
} elseif($_GET['action'] == 'viewvote') {
	if($_G['forum_thread']['special'] != 1) {
		BfdApp::display_result('thread_poll_none');
	}
	require_once libfile('function/post');
	$polloptionid = is_numeric($_GET['polloptionid']) ? $_GET['polloptionid'] : '';

	$page = intval($_GET['page']) ? intval($_GET['page']) : 1;
	$perpage = 100;
	$pollinfo = C::t('forum_poll')->fetch($_G['tid']);
	$overt = $pollinfo['overt'];

	$polloptions = array();
	$query = C::t('forum_polloption')->fetch_all_by_tid($_G['tid']);
	foreach($query as $options) {
		if(empty($polloptionid)) {
			$polloptionid = $options['polloptionid'];
		}
		$options['polloption'] = preg_replace("/\[url=(https?){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/i",
			"<a href=\"\\1://\\2\" target=\"_blank\">\\3</a>", $options['polloption']);
		$polloptions[] = $options;
	}

	$arrvoterids = array();
	if($overt || $_G['adminid'] == 1 || $thread['authorid'] == $_G['uid']) {
		$polloptioninfo = C::t('forum_polloption')->fetch($polloptionid);
		$voterids = $polloptioninfo['voterids'];
		$arrvoterids = explode("\t", trim($voterids));
	} else {
		BfdApp::display_result('thread_poll_nopermission');
	}


	$totalpage = 1;
	if(!empty($arrvoterids)) {
		$count = count($arrvoterids);
		$multi = $perpage * ($page - 1);
		$totalpage = ceil($count / $perpage);
		$arrvoterids = array_slice($arrvoterids, $multi, $perpage);
	}
	$voterlist = $voter = array();
	if($arrvoterids) {
		$voterlist = C::t('common_member')->fetch_all($arrvoterids);
	}
	$voterresult = array();
	foreach($voterlist as $item)
	{
		$voter = array();
		$voter['uid'] = $item['uid'];
		$voter['username'] = $item['username'];
		$voter['avatar'] = avatar($item['uid'],'small',true);
		$voterresult[] = $voter;
	}
	$result = array();
	$result['polloptionid'] = $polloptionid;
	$result['polloptions'] = $polloptions;
	$result['voterlist'] = $voterresult;
	BfdApp::display_result('get_success',$result,'',$totalpage);

} elseif($_GET['action'] == 'activityapplylist') {

	$isactivitymaster = $thread['authorid'] == $_G['uid'] ||
						(in_array($_G['group']['radminid'], array(1, 2)) || ($_G['group']['radminid'] == 3 && $_G['forum']['ismoderator'])
						&& $_G['group']['alloweditactivity']);
	if(!$isactivitymaster) {
		BfdApp::display_result('activity_is_not_manager');
	}

	$activity = C::t('forum_activity')->fetch($_G['tid']);
	if(empty($activity) || $thread['special'] != 4) {
		BfdApp::display_result('activity_is_not_exists');
	}

	if(!submitcheck('applylistsubmit')) {
		$applylist = array();
		$activity['ufield'] = $activity['ufield'] ? dunserialize($activity['ufield']) : array();
		$query = C::t('forum_activityapply')->fetch_all_for_thread($_G['tid'], 0, 500, $_GET['uid'], $isactivitymaster);
		foreach($query as $activityapplies) {
			$ufielddata = '';
			$activityapplies['dateline'] = dgmdate($activityapplies['dateline'], 'u');
			$activityapplies['ufielddata'] = !empty($activityapplies['ufielddata']) ? dunserialize($activityapplies['ufielddata']) : '';
			if($activityapplies['ufielddata']) {
				if($activityapplies['ufielddata']['userfield']) {
					require_once libfile('function/profile');
					loadcache('profilesetting');
					$data = '';
					foreach($activity['ufield']['userfield'] as $fieldid) {
						$data = profile_show($fieldid, $activityapplies['ufielddata']['userfield']);
						$ufielddata .= '<li>'.$_G['cache']['profilesetting'][$fieldid]['title'].'&nbsp;&nbsp;:&nbsp;&nbsp;';
						if(empty($data)) {
							$ufielddata .= '</li>';
							continue;
						}
						if($_G['cache']['profilesetting'][$fieldid]['formtype'] != 'file') {
							$ufielddata .= $data;
						} else {
							$ufielddata .= '<a href="'.$data.'" target="_blank" onclick="zoom(this, this.href, 0, 0, 0); return false;">'.lang('forum/misc', 'activity_viewimg').'</a>';
						}
						$ufielddata .= '</li>';
					}
				}
				if($activityapplies['ufielddata']['extfield']) {
					foreach($activity['ufield']['extfield'] as $name) {
						$ufielddata .= '<li>'.$name.'&nbsp;&nbsp;:&nbsp;&nbsp;'.$activityapplies['ufielddata']['extfield'][$name].'</li>';
					}
				}
			}
			$activityapplies['ufielddata'] = $ufielddata;
			$applylist[] = $activityapplies;
		}

		$activity['starttimefrom'] = dgmdate($activity['starttimefrom'], 'u');
		$activity['starttimeto'] = $activity['starttimeto'] ? dgmdate($activity['starttimeto'], 'u') : 0;
		$activity['expiration'] = $activity['expiration'] ? dgmdate($activity['expiration'], 'u') : 0;

		include template('forum/activity_applylist');
	} else {
		if(empty($_GET['applyidarray'])) {
			showmessage('activity_choice_applicant');
		} else {
			$reason = cutstr(dhtmlspecialchars($_GET['reason']), 200);
			$tempuid = $uidarray = $unverified = array();
			$query = C::t('forum_activityapply')->fetch_all($_GET['applyidarray']);
			foreach($query as $row) {
				if($row['tid'] == $_G['tid']) {
					$tempusers[$row['uid']] = $row['verified'];
				}
			}
			$query  = C::t('common_member')->fetch_all(array_keys($tempusers));
			foreach($query as $user) {
				$uidarray[] = $user['uid'];
				if($tempusers[$user['uid']]['verified'] != 1) {
					$unverified[] = $user['uid'];
				}
			}
			$activity_subject = $thread['subject'];

			if($_GET['operation'] == 'notification') {
				if(empty($uidarray)) {
					showmessage('activity_notification_user');
				}
				if(empty($reason)) {
					showmessage('activity_notification_reason');
				}
				if($uidarray) {
					foreach($uidarray as $uid) {
						notification_add($uid, 'activity', 'activity_notification', array('tid' => $_G['tid'], 'subject' => $activity_subject, 'msg' => $reason));
					}
					showmessage('activity_notification_success', "forum.php?mod=viewthread&tid=$_G[tid]&do=viewapplylist".($_GET['from'] ? '&from='.$_GET['from'] : ''), array(), array('showdialog' => 1, 'closetime' => true));
				}
			} elseif($_GET['operation'] == 'delete') {
				if($uidarray) {
					C::t('forum_activityapply')->delete_for_thread($_G['tid'], $_GET['applyidarray']);
					foreach($uidarray as $uid) {
						notification_add($uid, 'activity', 'activity_delete', array(
							'tid' => $_G['tid'],
							'subject' => $activity_subject,
							'reason' => $reason,
						));
					}
				}
				$applynumber = C::t('forum_activityapply')->fetch_count_for_thread($_G['tid']);
				C::t('forum_activity')->update($_G['tid'], array('applynumber' => $applynumber));
				showmessage('activity_delete_completion', "forum.php?mod=viewthread&tid=$_G[tid]&do=viewapplylist".($_GET['from'] ? '&from='.$_GET['from'] : ''), array(), array('showdialog' => 1, 'closetime' => true));
			} else {
				if($unverified) {
					$verified = $_GET['operation'] == 'replenish' ? 2 : 1;

					C::t('forum_activityapply')->update_verified_for_thread($verified, $_G['tid'], $_GET['applyidarray']);
					$notification_lang = $verified == 1 ? 'activity_apply' : 'activity_replenish';
					foreach($unverified as $uid) {
						notification_add($uid, 'activity', $notification_lang, array(
							'tid' => $_G['tid'],
							'subject' => $activity_subject,
							'reason' => $reason,
						));
					}
				}
				$applynumber = C::t('forum_activityapply')->fetch_count_for_thread($_G['tid']);
				C::t('forum_activity')->update($_G['tid'], array('applynumber' => $applynumber));

				showmessage('activity_auditing_completion', "forum.php?mod=viewthread&tid=$_G[tid]&do=viewapplylist".($_GET['from'] ? '&from='.$_GET['from'] : ''), array(), array('showdialog' => 1, 'closetime' => true));
			}
		}
	}

} elseif($_GET['action'] == 'getactivityapplylist') {
	$pp = $_G['setting']['activitypp'];
	$page = max(1, $_G['page']);
	$start = ($page - 1) * $pp;
	$activity = C::t('forum_activity')->fetch($_G['tid']);
	if(!$activity || $thread['special'] != 4) {
		BfdApp::display_result('activity_is_not_exists');
	}
	$query = C::t('forum_activityapply')->fetch_all_for_thread($_G['tid'], $start, $pp,0,1);
	foreach($query as $activityapplies) {
		$activityapplies['dateline'] = BfdApp::bfd_html_entity_decode(strip_tags(dgmdate($activityapplies['dateline'])));
		unset($activityapplies['ufielddata']);
		$activityapplies['avatar'] = avatar($activityapplies['uid'],'small',true);
		$applylist[] = $activityapplies;
	}
	$totalpage = 1;
	if($activity['applynumber'])
	{
		$totalpage = ceil($activity['applynumber'] / $pp);
	}
	BfdApp::display_result('get_success',$applylist,'',$totalpage);
}
/*elseif($_GET['action'] == 'recommend') {

	if(empty($_G['uid'])) {
		showmessage('to_login', null, array(), array('showmsg' => true, 'login' => 1));
	}

	if(!$_G['setting']['recommendthread']['status'] || !$_G['group']['allowrecommend']) {
		showmessage('no_privilege_recommend');
	}

	if($thread['authorid'] == $_G['uid'] && !$_G['setting']['recommendthread']['ownthread']) {
		showmessage('recommend_self_disallow', '', array('recommendc' => $thread['recommends']), array('msgtype' => 3));
	}
	if(C::t('forum_memberrecommend')->fetch_by_recommenduid_tid($_G['uid'], $_G['tid'])) {
		showmessage('recommend_duplicate', '', array('recommendc' => $thread['recommends']), array('msgtype' => 3));
	}

	$recommendcount = C::t('forum_memberrecommend')->count_by_recommenduid_dateline($_G['uid'], $_G['timestamp']-86400);
	if($_G['setting']['recommendthread']['daycount'] && $recommendcount >= $_G['setting']['recommendthread']['daycount']) {
		showmessage('recommend_outoftimes', '', array('recommendc' => $thread['recommends']), array('msgtype' => 3));
	}

	$_G['group']['allowrecommend'] = intval($_GET['do'] == 'add' ? $_G['group']['allowrecommend'] : -$_G['group']['allowrecommend']);
	$fieldarr = array();
	if($_GET['do'] == 'add') {
		$heatadd = 'recommend_add=recommend_add+1';
		$fieldarr['recommend_add'] = 1;
	} else {
		$heatadd = 'recommend_sub=recommend_sub+1';
		$fieldarr['recommend_sub'] = 1;
	}

	update_threadpartake($_G['tid']);
	$fieldarr['heats'] = 0;
	$fieldarr['recommends'] = $_G['group']['allowrecommend'];
	C::t('forum_thread')->increase($_G['tid'], $fieldarr);
	C::t('forum_thread')->update($_G['tid'], array('lastpost' => TIMESTAMP));
	C::t('forum_memberrecommend')->insert(array('tid'=>$_G['tid'], 'recommenduid'=>$_G['uid'], 'dateline'=>$_G['timestamp']));

	dsetcookie('recommend', 1, 43200);
	$recommendv = $_G['group']['allowrecommend'] > 0 ? '+'.$_G['group']['allowrecommend'] : $_G['group']['allowrecommend'];
	if($_G['setting']['recommendthread']['daycount']) {
		$daycount = $_G['setting']['recommendthread']['daycount'] - $recommendcount;
		showmessage('recommend_daycount_succeed', '', array('recommendv' => $recommendv, 'recommendc' => $thread['recommends'], 'daycount' => $daycount), array('msgtype' => 3));
	} else {
		showmessage('recommend_succeed', '', array('recommendv' => $recommendv, 'recommendc' => $thread['recommends']), array('msgtype' => 3));
	}

} 
*/
elseif($_GET['action'] == 'rate' && $_GET['pid']) {

/*	if($_GET['showratetip']) {
		include template('forum/rate');
		exit();
	}

	if(!$_G['inajax']) {
		showmessage('undefined_action');
	}
*/
	$_GET['pid'] = intval($_GET['pid']);
	if(!$_G['group']['raterange']) {
		BfdApp::display_result('group_nopermission');
	} elseif($_G['setting']['modratelimit'] && $_G['adminid'] == 3 && !$_G['forum']['ismoderator']) {
		BfdApp::display_result('thread_rate_moderator_invalid');
	}
	$reasonpmcheck = $_G['group']['reasonpm'] == 2 || $_G['group']['reasonpm'] == 3 ? 'checked="checked" disabled' : '';
	if(($_G['group']['reasonpm'] == 2 || $_G['group']['reasonpm'] == 3) || !empty($_GET['sendreasonpm'])) {
		$forumname = strip_tags($_G['forum']['name']);
		$sendreasonpm = 1;
	} else {
		$sendreasonpm = 0;
	}

	$post = C::t('forum_post')->fetch('tid:'.$_G['tid'], $_GET['pid']);
	if($post['invisible'] != 0 || $post['authorid'] == 0) {
		$post = array();
	}

	if(!$post || $post['tid'] != $thread['tid'] || !$post['authorid']) {
		BfdApp::display_result('rate_post_error');
	} elseif(!$_G['forum']['ismoderator'] && $_G['setting']['karmaratelimit'] && TIMESTAMP - $post['dateline'] > $_G['setting']['karmaratelimit'] * 3600) {
		BfdApp::display_result('thread_rate_timelimit');
	} elseif($post['authorid'] == $_G['uid'] || $post['tid'] != $_G['tid']) {
		BfdApp::display_result('thread_rate_member_invalid');
	} elseif($post['anonymous']) {
		BfdApp::display_result('thread_rate_anonymous');
	} elseif($post['status'] & 1) {
		BfdApp::display_result('thread_rate_banned');
	}

	$allowrate = TRUE;
	if(!$_G['setting']['dupkarmarate']) {
		if(C::t('forum_ratelog')->count_by_uid_pid($_G['uid'], $_GET['pid'])) {
			BfdApp::display_result('thread_rate_duplicate');
		}
	}

	$page = intval($_GET['page']);
	require_once libfile('function/misc');
	$maxratetoday = getratingleft($_G['group']['raterange']);

	if(empty($_POST)) {
		$ratelist = getratelist($_G['group']['raterange']);
		$result = array();
		$result['tid'] = $_G['tid'];
		$result['pid'] = $_GET['pid'];
		foreach($ratelist as $id => $options)
		{
			$tmp = array();
			$tmp['img'] = $_G['setting']['extcredits'][$id]['img'];
			$tmp['title'] = $_G['setting']['extcredits'][$id]['title'];
			$tmp['raterange'] = $_G['group']['raterange'][$id]['min'].' ~ '.$_G['group']['raterange'][$id]['max'];
			$tmp['maxratetoday'] = $maxratetoday[$id];
			$tmp['name'] = 'score'.$id;
			$result['extcredits'][] = $tmp;
		}
		
		BfdApp::display_result('get_success',$result);

	} else {

		//$reason = checkreasonpm();
		$reason = '';

		$rate = $ratetimes = 0;
		$creditsarray = $sub_self_credit = array();
		getuserprofile('extcredits1');
		foreach($_G['group']['raterange'] as $id => $rating) {
			$score = intval($_GET['score'.$id]);
			if(isset($_G['setting']['extcredits'][$id]) && !empty($score)) {
				if($rating['isself'] && (intval($_G['member']['extcredits'.$id]) - $score < 0)) {
					BfdApp::display_result('thread_rate_range_self_invalid');
				}
				if(abs($score) <= $maxratetoday[$id]) {
					if($score > $rating['max'] || $score < $rating['min']) {
						BfdApp::display_result('thread_rate_range_invalid');
					} else {
						$creditsarray[$id] = $score;
						if($rating['isself']) {
							$sub_self_credit[$id] = -abs($score);
						}
						$rate += $score;
						$ratetimes += ceil(max(abs($rating['min']), abs($rating['max'])) / 5);
					}
				} else {
					BfdApp::display_result('thread_rate_ctrl');
				}
			}
		}

		if(!$creditsarray) {
			BfdApp::display_result('thread_rate_range_invalid');
		}

		updatemembercount($post['authorid'], $creditsarray, 1, 'PRC', $_GET['pid']);

		if(!empty($sub_self_credit)) {
			updatemembercount($_G['uid'], $sub_self_credit, 1, 'RSC', $_GET['pid']);
		}
		C::t('forum_post')->increase_rate_by_pid('tid:'.$_G['tid'], $_GET['pid'], $rate, $ratetimes);
		if($post['first']) {
			$threadrate = intval(@($post['rate'] + $rate) / abs($post['rate'] + $rate));
			C::t('forum_thread')->update($_G['tid'], array('rate'=>$threadrate));

		}

		require_once libfile('function/discuzcode');
		$sqlvalues = $comma = '';
		//$sqlreason = censor(trim($_GET['reason']));
		//$sqlreason = cutstr(dhtmlspecialchars($sqlreason), 40, '.');
		$sqlreason = '';
		foreach($creditsarray as $id => $addcredits) {
			$insertarr = array(
				'pid' => $_GET['pid'],
				'uid' => $_G['uid'],
				'username' => $_G['username'],
				'extcredits' => $id,
				'dateline' => $_G['timestamp'],
				'score' => $addcredits,
				'reason' => $sqlreason
			);
			C::t('forum_ratelog')->insert($insertarr);
		}

		include_once libfile('function/post');
		$_G['forum']['threadcaches'] && @deletethreadcaches($_G['tid']);

		$reason = dhtmlspecialchars(censor(trim($reason)));
		if($sendreasonpm) {
			$ratescore = $slash = '';
			foreach($creditsarray as $id => $addcredits) {
				$ratescore .= $slash.$_G['setting']['extcredits'][$id]['title'].' '.($addcredits > 0 ? '+'.$addcredits : $addcredits).' '.$_G['setting']['extcredits'][$id]['unit'];
				$slash = ' / ';
			}
			sendreasonpm($post, 'rate_reason', array(
				'tid' => $thread['tid'],
				'pid' => $_GET['pid'],
				'subject' => $thread['subject'],
				'ratescore' => $ratescore,
				'reason' => $reason,
				'from_id' => 0,
				'from_idtype' => 'rate'
			));
		}

		$logs = array();
		foreach($creditsarray as $id => $addcredits) {
			$logs[] = dhtmlspecialchars("$_G[timestamp]\t{$_G[member][username]}\t$_G[adminid]\t$post[author]\t$id\t$addcredits\t$_G[tid]\t$thread[subject]\t$reason");
		}
		update_threadpartake($post['tid']);
		C::t('forum_postcache')->delete($_GET['pid']);
		writelog('ratelog', $logs);

		BfdApp::display_result('thread_rate_succeed');
	}
}
BfdApp::display_result('params_error');



function getratingleft($raterange) {
	global $_G;
	$maxratetoday = array();

	foreach($raterange as $id => $rating) {
		$maxratetoday[$id] = $rating['mrpd'];
	}

	foreach(C::t('forum_ratelog')->fetch_all_sum_score($_G['uid'], $_G['timestamp']-86400) as $rate) {
		$maxratetoday[$rate['extcredits']] = $raterange[$rate['extcredits']]['mrpd'] - $rate['todayrate'];
	}
	return $maxratetoday;
}

function getratelist($raterange) {
	global $_G;
	$maxratetoday = getratingleft($raterange);

	$ratelist = array();
	foreach($raterange as $id => $rating) {
		if(isset($_G['setting']['extcredits'][$id])) {
			$ratelist[$id] = '';
			$rating['max'] = $rating['max'] < $maxratetoday[$id] ? $rating['max'] : $maxratetoday[$id];
			$rating['min'] = -$rating['min'] < $maxratetoday[$id] ? $rating['min'] : -$maxratetoday[$id];
			$offset = abs(ceil(($rating['max'] - $rating['min']) / 10));
			if($rating['max'] > $rating['min']) {
				for($vote = $rating['max']; $vote >= $rating['min']; $vote -= $offset) {
					$ratelist[$id] .= $vote ? '<li>'.($vote > 0 ? '+'.$vote : $vote).'</li>' : '';
				}
			}
		}
	}
	return $ratelist;
}
