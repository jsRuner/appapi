<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum.php 31999 2012-10-30 07:19:49Z cnteacher $
 */


define('APPTYPEID', 2);
define('CURSCRIPT', 'forum');


/*params

action = delpost(删评论)  moderate(删帖)
fid
tid
topiclist array() pids
moderate array() tids
*/
require_once libfile('function/forum');
require_once libfile('function/post');
require_once libfile('function/misc');

loadforum();


$_GET['topiclist'] = !empty($_GET['topiclist']) ? (is_array($_GET['topiclist']) ? array_unique($_GET['topiclist']) : $_GET['topiclist']) : array();

loadcache(array('modreasons', 'stamptypeid', 'threadtableids'));


$modpostsnum = 0;
$resultarray = $thread = array();

$threadtableids = !empty($_G['cache']['threadtableids']) ? $_G['cache']['threadtableids'] : array();
$specialperm = $_GET['action'] == 'stickreply' && $_G['thread']['authorid'] == $_G['uid'];

if(!$specialperm && (!$_G['uid'] || !$_G['forum']['ismoderator'])) {
	//showmessage('admin_nopermission', NULL);
	BfdApp::display_result('admin_nopermission');
}

$frommodcp = !empty($_GET['frommodcp']) ? intval($_GET['frommodcp']) : 0;


if(!empty($_G['tid'])) {
	$_GET['archiveid'] = intval($_GET['archiveid']);
	$archiveid = 0;
	if(!empty($_GET['archiveid']) && in_array($_GET['archiveid'], $threadtableids)) {
		$archiveid = $_GET['archiveid'];
	}
	$displayorder = !$_G['forum_auditstatuson'] ? 0 : null;
	$thread = C::t('forum_thread')->fetch_by_tid_fid_displayorder($_G['tid'], $_G['fid'], $displayorder, $archiveid);
	if(!$thread) {
	//	showmessage('thread_nonexistence');
		BfdApp::display_result('thread_nonexistence');
	}
	if($thread['special'] && in_array($_GET['action'], array('copy', 'split', 'merge'))) {
	//	showmessage('special_noaction');
		BfdApp::display_result('special_noaction');
	}
}
if(($_G['group']['reasonpm'] == 2 || $_G['group']['reasonpm'] == 3) || !empty($_GET['sendreasonpm'])) {
	$forumname = strip_tags($_G['forum']['name']);
	$sendreasonpm = 1;
} else {
	$sendreasonpm = 0;
}

$_GET['handlekey'] = 'mods';


if(preg_match('/^\w+$/', $_GET['action']) && file_exists($topicadminfile = './source/topicadmin/topicadmin_'.$_GET['action'].'.php')) {
	require_once $topicadminfile;
} else {
	BfdApp::display_result('undefined_action');
}

if($resultarray) {

	if($resultarray['modtids']) {
		updatemodlog($resultarray['modtids'], $modaction, $resultarray['expiration']);
	}

	updatemodworks($modaction, $modpostsnum);
	if(is_array($resultarray['modlog'])) {
		if(isset($resultarray['modlog']['tid'])) {
			modlog($resultarray['modlog'], $modaction);
		} else {
			foreach($resultarray['modlog'] as $thread) {
				modlog($thread, $modaction);
			}
		}
	}

	if($resultarray['reasonpm']) {
		$modactioncode = lang('forum/modaction');
		$modaction = $modactioncode[$modaction];
		foreach($resultarray['reasonpm']['data'] as $var) {
			sendreasonpm($var, $resultarray['reasonpm']['item'], $resultarray['reasonvar'], $resultarray['reasonpm']['notictype']);
		}
	}

	BfdApp::display_result('admin_succeed');
	//showmessage((isset($resultarray['message']) ? $resultarray['message'] : 'admin_succeed'), $resultarray['redirect']);
}

?>
