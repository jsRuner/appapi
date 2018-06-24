<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_thread.php 31364 2012-08-20 03:19:05Z zhengqingpeng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$page = empty($_GET['page'])? l : intval($_GET['page']);
if($page<1) $page=1;
$perpage = 20;
$pagesize = intval($_GET['pagesize']);
if($pagesize > 0)
{
	$perpage = $pagesize;
}
$start = ($page-1)*$perpage;
$pagetotal = 1;
$uid = intval($_GET['uid']);
if($uid < 1)
{
	$uid = $_G['uid'];
}

//用户获取数据
$space = getuserbyuid($uid, 1);
if(empty($space)) {
	BfdApp::display_result('space_does_not_exist');
}

if(empty($_GET['view'])) 
{
	$_GET['view'] = 'me';
}
$_GET['order'] = empty($_GET['order']) ? 'dateline' : $_GET['order'];


//是否允许读取用户帖子列表
$allowviewuserthread = $_G['setting']['allowviewuserthread'];

$list = array();
$userlist = array();
$hiddennum = $count = $pricount = 0;
$gets = array(
	'mod' => 'space',
	'uid' => $space['uid'],
	'do' => 'thread',
	'fid' => $_GET['fid'],
	'view' => $_GET['view'],
	'type' => $_GET['type'],
	'order' => $_GET['order'],
	'fuid' => $_GET['fuid'],
	'searchkey' => $_GET['searchkey'],
	'from' => $_GET['from'],
	'filter' => $_GET['filter']
);
unset($gets['fid']);
$authorid = 0;
$replies = $closed = $displayorder = null;
$dglue = '=';
$vfid = $_GET['fid'] ? intval($_GET['fid']) : null;

require_once libfile('function/misc');
require_once libfile('function/forum');
loadcache(array('forums'));
$fids = $comma = '';
if($_GET['view'] != 'me') {
	$displayorder = 0;
	$dglue = '>=';
}
$f_index = '';
$ordersql = 't.dateline DESC';
$need_count = true;
$viewuserthread = false;
$listcount = 0;

if($_GET['view'] == 'me') {

	if($_GET['from'] == 'space') $diymode = 1;
	$allowview = true;
	$viewtype = in_array($_GET['type'], array('reply', 'thread', 'postcomment')) ? $_GET['type'] : 'thread';
	$filter = in_array($_GET['filter'], array('recyclebin', 'ignored', 'save', 'aduit', 'close', 'common')) ? $_GET['filter'] : '';
	if($space['uid'] != $_G['uid'] && in_array($viewtype, array('reply', 'thread'))) {
		if($allowviewuserthread === -1 && $_G['adminid'] != 1) {
			$allowview = false;
		}
		if($allowview) {
			$viewuserthread = true;
			$viewfids = str_replace("'", '', $allowviewuserthread);
			if(!empty($viewfids)) {
				$viewfids = explode(',', $viewfids);
			}
		}
	}

	if($viewtype == 'thread' && $allowview) {
		$authorid = $space['uid'];

		if($filter == 'recyclebin') {
			$displayorder = -1;
		} elseif($filter == 'aduit') {
			$displayorder = -2;
		} elseif($filter == 'ignored') {
			$displayorder = -3;
		} elseif($filter == 'save') {
			$displayorder = -4;
		} elseif($filter == 'close') {
			$closed = 1;
		} elseif($filter == 'common') {
			$closed = 0;
			$displayorder = 0;
			$dglue = '>=';
		}

		$ordersql = 't.tid DESC';
	} elseif($viewtype == 'postcomment') {
		$posttable = getposttable();
		require_once libfile('function/post');
		$pids = $tids = array();
		$postcommentarr = C::t('forum_postcomment')->fetch_all_by_authorid($_G['uid'], $start, $perpage);
		foreach($postcommentarr as $value) {
			$pids[] = $value['pid'];
			$tids[] = $value['tid'];
		}
		$pids = C::t('forum_post')->fetch_all(0, $pids);
		$tids = C::t('forum_thread')->fetch_all($tids);

		$list = $fids = array();
		foreach($postcommentarr as $value) {
			$value['authorid'] = $pids[$value['pid']]['authorid'];
			$value['fid'] = $pids[$value['pid']]['fid'];
			$value['invisible'] = $pids[$value['pid']]['invisible'];
			$value['dateline'] = $pids[$value['pid']]['dateline'];
			$value['message'] = $pids[$value['pid']]['message'];
			$value['special'] = $tids[$value['tid']]['special'];
			$value['status'] = $tids[$value['tid']]['status'];
			$value['subject'] = $tids[$value['tid']]['subject'];
			$value['digest'] = $tids[$value['tid']]['digest'];
			$value['attachment'] = $tids[$value['tid']]['attachment'];
			$value['replies'] = $tids[$value['tid']]['replies'];
			$value['views'] = $tids[$value['tid']]['views'];
			$value['lastposter'] = $tids[$value['tid']]['lastposter'];
			$value['lastpost'] = $tids[$value['tid']]['lastpost'];
			$value['tid'] = $pids[$value['pid']]['tid'];

			$fids[] = $value['fid'];
			$value['comment'] = messagecutstr($value['comment'], 100);
			$list[] = procthread($value);
		}
		unset($pids, $tids, $postcommentarr);
		if($fids) {
			$fids = array_unique($fids);
			$query = C::t('forum_forum')->fetch_all($fids);
			foreach($query as $forum) {
				$forums[$forum['fid']] = $forum['name'];
			}
		}


	} elseif($allowview) {
		$invisible = null;

		$postsql = $threadsql = '';
		if($filter == 'recyclebin') {
			$invisible = -5;
		} elseif($filter == 'aduit') {
			$invisible = -2;
		} elseif($filter == 'save' || $filter == 'ignored') {
			$invisible = -3;
			$displayorder = -4;
		} elseif($filter == 'close') {
			$closed = 1;
		} elseif($filter == 'common') {
			$invisible = 0;
			$displayorder = 0;
			$dglue = '>=';
			$closed = 0;
		} else {
			if($space['uid'] != $_G['uid']) {
				$invisible = 0;
			}
		}
		require_once libfile('function/post');
		$posts = C::t('forum_post')->fetch_all_by_authorid(0, $space['uid'], true, 'DESC', $start, $perpage, 0, $invisible, $vfid);
		$listcount = count($posts);
		foreach($posts as $pid => $post) {
			$delrow = false;
			if($post['anonymous'] && $post['authorid'] != $_G['uid']) {
				$delrow = true;
			} elseif($viewuserthread && $post['authorid'] != $_G['uid']) {
				if(($_G['adminid'] != 1 && !empty($viewfids) && !in_array($post['fid'], $viewfids))) {
					$delrow = true;
				}
			}
			if($delrow) {
				unset($posts[$pid]);
				$hiddennum++;
				continue;
			} else {
				$tids[$post['tid']][] = $pid;
				$post['message'] = !getstatus($post['status'], 2) || $post['authorid'] == $_G['uid'] ? messagecutstr($post['message'], 100) : '';
				$posts[$pid] = $post;
			}
		}

		if(!empty($tids)) {

			$threads = C::t('forum_thread')->fetch_all_by_tid_displayorder(array_keys($tids), $displayorder, $dglue, array(), $closed);

			foreach($threads as $tid => $thread) {
				$delrow = false;
				if($_G['adminid'] != 1 && $thread['displayorder'] < 0) {
					$delrow = true;
				} elseif($_G['adminid'] != 1 && $_G['uid'] != $thread['authorid'] && getstatus($thread['status'], 2)) {
					$delrow = true;
				} elseif(!isset($_G['cache']['forums'][$thread['fid']])) {
					if(!$_G['setting']['groupstatus']) {
						$delrow = true;
					} else {
						$gids[$thread['fid']] = $thread['tid'];
					}
				}
				if($delrow) {
					foreach($tids[$tid] as $pid) {
						unset($posts[$pid]);
						$hiddennum++;
					}
					unset($tids[$tid]);
					unset($threads[$tid]);
					continue;
				} else {
					$threads[$tid] = procthread($thread);
					$forums[$thread['fid']] = $threads[$tid]['forumname'];
				}

			}
			if(!empty($gids)) {
				$groupforums = C::t('forum_forum')->fetch_all_name_by_fid(array_keys($gids));
				foreach($gids as $fid => $tid) {
					$threads[$tid]['forumname'] = $groupforums[$fid]['name'];
					$forums[$fid] = $groupforums[$fid]['name'];
				}
			}
			if(!empty($tids)) {
				foreach($tids as $tid => $pids) {
					foreach($pids as $pid) {
						if(!isset($threads[$tid])) {
							unset($posts[$pid]);
							unset($tids[$tid]);
							$hiddennum++;
							continue;
						}
					}
				}
			}
			$list = &$threads;
		}
	}
	if(!$allowview) {
	}
}

if($need_count) {

	if($searchkey = stripsearchkey($_GET['searchkey'])) {
		$searchkey = dhtmlspecialchars($searchkey);
	}

	loadcache('forums');
	$gids = $fids = $forums = array();
	foreach(C::t('forum_thread')->fetch_all_by_authorid_displayorder($authorid, $displayorder, $dglue, $closed, $searchkey, $start, $perpage, $replies, $vfid) as $tid => $value) {
		if(empty($value['author']) && $value['authorid'] != $_G['uid']) {
			$hiddennum++;
			continue;
		} elseif($viewuserthread && $value['authorid'] != $_G['uid']) {
			if(($_G['adminid'] != 1 && !empty($viewfids) && !in_array($value['fid'], $viewfids)) || $value['displayorder'] < 0) {
				$hiddennum++;
				continue;
			}
		} elseif(!isset($_G['cache']['forums'][$value['fid']])) {
			if(!$_G['setting']['groupstatus']) {
				$hiddennum++;
				continue;
			} else {
				$gids[$value['fid']] = $value['tid'];
			}
		}
		$list[$value['tid']] = procthread($value);
		
/*COMMENTS
//修改bug原有论坛帖子改为小组帖子 20121125 ep
*/
		//$forums[$value['fid']] = $list[$value['tid']]['forumname'];
		if($list[$value['tid']]['forumname']=='Forum')
			$gids[$value['fid']] = $value['tid'];
		else
			$forums[$value['fid']] = $list[$value['tid']]['forumname'];

/*COMMENTS END*/
	}

	if(!empty($gids)) {
		$gforumnames = C::t('forum_forum')->fetch_all_name_by_fid(array_keys($gids));
		foreach($gids as $fid => $tid) {
			$list[$tid]['forumname'] = $gforumnames[$fid]['name'];
			$forums[$fid] = $gforumnames[$fid]['name'];
		}
	}

	$threads = &$list;


	if($_GET['view'] != 'all') {
		$listcount = count($list)+$hiddennum;
	}
}
$totalpage = ceil($listcount/$perpage);
$result = array();
foreach($threads as $val)
{
	$tmparr = array();
	$tmparr['tid'] = $val['tid'];
	$tmparr['fid'] = $val['fid'];
	$tmparr['subject'] = $val['subject'];
	$tmparr['authorid'] = $val['authorid'];
	$tmparr['author'] = $val['author'];
	//$tmparr['authoravatar'] = avatar($val['authorid'],'small',true);
	$tmparr['dateline'] = $val['dateline'];
	//$tmparr['lastpost'] = html_entity_decode(strip_tags($val['lastpost']),ENT_COMPAT | ENT_HTML401,'UTF-8');//strip_tags($val['lastpost']);
	$tmparr['lastpost'] = str_replace('&nbsp;',' ',strip_tags($val['lastpost']));
	$tmparr['lastposter'] = $val['lastposter'];
	$tmparr['views'] = $val['views'];
	$tmparr['replies'] = $val['replies'];
	$tmparr['forumname'] = $val['forumname'];
	$tmparr['displayorder'] = $val['displayorder'];
    $tmparr['typeid'] = $val['typeid'];
    $tmparr['digest'] = $val['digest'];
    $tmparr['ispicture'] = $val['attachment'] == 2 ? 1:0;
	$result[] = $tmparr;
}

BfdApp::display_result('get_success',$result,'',$totalpage);
?>
