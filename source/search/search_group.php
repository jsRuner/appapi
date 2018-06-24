<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: search_group.php 30188 2012-05-16 03:25:14Z chenmengshu $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
define('NOROBOT', TRUE);

require_once libfile('function/home');
require_once libfile('function/post');

if(!$_G['setting']['search']['group']['status']) {
	BfdApp::display_result('search_group_closed');
}

if($_G['adminid'] != 1 && !($_G['group']['allowsearch'] & 16)) {
	BfdApp::display_result('group_nopermission');
}

$_G['setting']['search']['group']['searchctrl'] = intval($_G['setting']['search']['group']['searchctrl']);

$srchmod = 5;

$cachelife_time = 300;		// Life span for cache of searching in specified range of time
$cachelife_text = 3600;		// Life span for cache of text searching

$srchtype = empty($_GET['srchtype']) ? '' : trim($_GET['srchtype']);
$searchid = isset($_GET['searchid']) ? intval($_GET['searchid']) : 0;

$srchtxt = $_GET['srchtxt'];
$srchfid = intval($_GET['srchfid']);
$viewgroup = intval($_GET['viewgroup']);
$keyword = isset($srchtxt) ? dhtmlspecialchars(trim($srchtxt)) : '';

/*COMMENTS
//重构小组搜索逻辑  20121125 ep
*/
/*if(!submitcheck('searchsubmit', 1))

	include template('search/group');

} else {

	$orderby = in_array($_GET['orderby'], array('dateline', 'replies', 'views')) ? $_GET['orderby'] : 'lastpost';
	$ascdesc = isset($_GET['ascdesc']) && $_GET['ascdesc'] == 'asc' ? 'asc' : 'desc';

	if(!empty($searchid)) {
			
		require_once libfile('function/group');

		$page = max(1, intval($_GET['page']));
		$start_limit = ($page - 1) * $_G['tpp'];

		$index = C::t('common_searchindex')->fetch_by_searchid_srchmod($searchid, $srchmod);
		if(!$index) {
			showmessage('search_id_invalid');
		}

		$keyword = dhtmlspecialchars($index['keywords']);
		$keyword = $keyword != '' ? str_replace('+', ' ', $keyword) : '';

		$index['keywords'] = rawurlencode($index['keywords']);
		$index['ids'] = dunserialize($index['ids']);
		$searchstring = explode('|', $index['searchstring']);
		$srchfid = $searchstring[2];
		$threadlist = $grouplist = $posttables = array();
		if($index['ids']['thread'] && ($searchstring[2] || empty($viewgroup))) {
			require_once libfile('function/misc');
			$threads = C::t('forum_thread')->fetch_all_by_tid_fid_displayorder(explode(',', $index['ids']['thread']), null, 0, $orderby, $start_limit, $_G['tpp'], '>=', $ascdesc);
			foreach($threads as $value) {
				$fids[$value['fid']] = $value['fid'];
			}
			$forums = C::t('forum_forum')->fetch_all_name_by_fid($fids);
			foreach($threads as $thread) {
				$thread['forumname'] = $forums[$thread['fid']]['name'];
				$thread['subject'] = bat_highlight($thread['subject'], $keyword);
				$thread['realtid'] = $thread['tid'];
				$threadlist[$thread['tid']] = procthread($thread);
				$posttables[$thread['posttableid']][] = $thread['tid'];
			}
			if($threadlist) {
				foreach($posttables as $tableid => $tids) {
					foreach(C::t('forum_post')->fetch_all_by_tid($tableid, $tids, true, '', 0, 0, 1) as $post) {
						$threadlist[$post['tid']]['message'] = bat_highlight(messagecutstr($post['message'], 200), $keyword);
					}
				}
			}
		}
		$groupnum = !empty($index['ids']['group']) ? count(explode(',', $index['ids']['group'])) - 1 : 0;
		if($index['ids']['group'] && ($viewgroup || empty($searchstring[2]))) {
			if(empty($viewgroup)) {
				$index['ids']['group'] = implode(',', array_slice(explode(',', $index['ids']['group']), 0, 9));
			}
			if($viewgroup) {
				$query = C::t('forum_forum')->fetch_all_info_by_fids(explode(',', $index['ids']['group']), 3, $_G['tpp'], 0, 0, 0, 0, 'sub', $start_limit);
			} else {
				$query = C::t('forum_forum')->fetch_all_info_by_fids(explode(',', $index['ids']['group']), 3, 0, 0, 0, 0, 0, 'sub');
			}

			foreach($query as $group) {
				$group['icon'] = get_groupimg($group['icon'], 'icon');
				$group['name'] = bat_highlight($group['name'], $keyword);
				$group['description'] = bat_highlight($group['description'], $keyword);
				$group['dateline'] = dgmdate($group['dateline'], 'u');
				$grouplist[] = $group;
			}
		}
		if(empty($viewgroup)) {
			$multipage = multi($index['num'], $_G['tpp'], $page, "search.php?mod=group&searchid=$searchid&orderby=$orderby&ascdesc=$ascdesc&searchsubmit=yes".($viewgroup ? '&viewgroup=1' : ''));
		} else {
			$multipage = multi($groupnum, $_G['tpp'], $page, "search.php?mod=group&searchid=$searchid&orderby=$orderby&ascdesc=$ascdesc&searchsubmit=yes".($viewgroup ? '&viewgroup=1' : ''));
		}

		$url_forward = 'search.php?mod=group&'.$_SERVER['QUERY_STRING'];

		include template('search/group');

	} else {

		$srchuname = isset($_GET['srchuname']) ? trim($_GET['srchuname']) : '';

		$searchstring = 'group|title|'.$srchfid.'|'.addslashes($srchtxt);
		$searchindex = array('id' => 0, 'dateline' => '0');

		foreach(C::t('common_searchindex')->fetch_all_search($_G['setting']['search']['group']['searchctrl'], $_G['clientip'], $_G['uid'], $_G['timestamp'], $searchstring, $srchmod) as $index) {
			if($index['indexvalid'] && $index['dateline'] > $searchindex['dateline']) {
				$searchindex = array('id' => $index['searchid'], 'dateline' => $index['dateline']);
				break;
			} elseif($_G['adminid'] != '1' && $index['flood']) {
				showmessage('search_ctrl', 'search.php?mod=group', array('searchctrl' => $_G['setting']['search']['group']['searchctrl']));
			}
		}

		if($searchindex['id']) {

			$searchid = $searchindex['id'];

		} else {

			!($_G['group']['exempt'] & 2) && checklowerlimit('search');

			if(!$srchtxt && !$srchuid && !$srchuname) {
				dheader('Location: search.php?mod=group');
			}
			
			if($_G['adminid'] != '1' && $_G['setting']['search']['group']['maxspm']) {
				if(C::t('common_searchindex')->count_by_dateline($_G['timestamp'], $srchmod) >= $_G['setting']['search']['group']['maxspm']) {
					showmessage('search_toomany', 'search.php?mod=group', array('maxspm' => $_G['setting']['search']['group']['maxspm']));
				}
			}

			$num = $ids = $tnum = $tids = 0;
			$_G['setting']['search']['group']['maxsearchresults'] = $_G['setting']['search']['group']['maxsearchresults'] ? intval($_G['setting']['search']['group']['maxsearchresults']) : 500;
			list($srchtxt, $srchtxtsql) = searchkey($keyword, "subject LIKE '%{text}%'", true);

			$threads = C::t('forum_thread')->fetch_all_by_tid_fid(0, $srchfid, 1, '', $keyword, $_G['setting']['search']['group']['maxsearchresults']);
			foreach($threads as $value) {
				$fids[$value['fid']] = $value['fid'];
			}
			$forums = C::t('forum_forum')->fetch_all_by_fid($fids);
			foreach($threads as $thread) {
				if($forums[$value['fid']]['status'] == 3) {
					$tids .= ','.$thread['tid'];
					$tnum++;
				}
			}

			if(!$srchfid) {
				$srchtxtsql = str_replace('subject LIKE', 'name LIKE', $srchtxtsql);
				$query = C::t('forum_forum')->fetch_all_fid_for_group(0, $_G['setting']['search']['group']['maxsearchresults'], 1, $srchtxtsql);
				foreach($query as $group) {
					$ids .= ','.$group['fid'];
					$num++;
				}
			}
			$allids = array('thread' => $tids, 'group' => $ids);
			$keywords = str_replace('%', '+', $srchtxt);
			$expiration = TIMESTAMP + $cachelife_text;

			$searchid = C::t('common_searchindex')->insert(array(
				'srchmod' => $srchmod,
				'keywords' => $keywords,
				'searchstring' => $searchstring,
				'useip' => $_G['clientip'],
				'uid' => $_G['uid'],
				'dateline' => $_G['timestamp'],
				'expiration' => $expiration,
				'num' => $tnum,
				'ids' => serialize($allids)
			), true);

			!($_G['group']['exempt'] & 2) && updatecreditbyaction('search');
		}

		dheader("location: search.php?mod=group&searchid=$searchid&searchsubmit=yes&kw=".urlencode($keyword));

	}

}*/
/*COMMENTS END*/


	
	require_once libfile('function/group');
	require_once libfile('lib/group_helper');
	
	$gid = TOP_GROUP_ID;
	$perpage = GROUP_LIST_PER_PAGE_NUM;
	
	//$sgid = intval(getgpc('sgid'));
	$groupids = array(0=>$gid);
	$curtype = array();
	$groupnav = $typelist = '';
	$selectorder = array('default' => '', 'thread' => '', 'membernum' => '', 'dateline' => '', 'activity' => '');
	if(!empty($_GET['orderby'])) {
		$selectorder[$_GET['orderby']] = 'selected';
	} else {
		$selectorder['default'] = 'selected';
	}
	$first = $_G['cache']['grouptype']['first'];
	$second = $_G['cache']['grouptype']['second'];
	
	$url = $_G['basescript'].'.php?mod=group&searchsubmit=yes';
	if($keyword) $url .= '&srchtxt='.$keyword;
	
	
	
	$data = $randgrouplist = $randgroupdata = $grouptop = $newgrouplist = array();
	$groupnum = 0;
	$list = array();
	if($groupids) {
		$orderby = in_array(getgpc('orderby'), array('membernum', 'dateline', 'thread', 'activity')) ? getgpc('orderby') : 'displayorder';
		$page = intval(getgpc('page')) ? intval($_GET['page']) : 1;
		$start = ($page - 1) * $perpage;
		$groupnum = $getcount = lib_group_helper::grouplist(null,$keyword,null, '', '', $groupids, 1, 1);
		if($getcount) {		
			$list = lib_group_helper::grouplist(null,$keyword,$orderby, '', array($start, $perpage), $groupids, 1);
		}
	}
	
	
	
	
	//我参加的小组 获得我参加的所有小组数组，用于前台判断是否已加入
	$myfids_data = C::t('forum_groupuser')->fetch_all_fid_by_uids_without_pending($_G['uid'],array_keys($lists),$return_all_data=true);
	$myfids = array_keys($myfids_data);
	$join_str1 = lang('extra','join_group');
	$join_str1b = lang('extra','group_waiting_for_pending');
	$join_str2 = lang('extra','group_already_join');
	$fids = array();
	foreach($list as &$item){
		if(!in_array($item['fid'],$myfids)){
			$item['join_link'] = 'forum.php?mod=group&action=join&from=all&fid='.$item['fid'];
			$item['join_status'] = false;
			$item['join_str'] = $join_str1;
		}else{
			$item['join_link'] = 'forum.php?mod=group&action=out&from=all&fid='.$item['fid'];
			$item['join_status'] = true;
			$item['join_str'] = ($myfids_data[$item['fid']]['level'])?$join_str2:$join_str1b;
		}
		$fids[] = $item['fid'];
	}
	//每个小组最新的一个帖子
	$fid_threads = lib_group_helper::fetch_thread_by_fids($fids,$count=1);
	
$pagetotal = 1;
if($getcount)
{
	$pagetotal = ceil($getcount / $perpage);
}

$result = array();
foreach($list as $val)
{
	$tmp = array();
	$tmp['fid'] = $val['fid'];
	$tmp['name'] = $val['name'];
	$tmp['description'] = $val['description'];
	$tmp['icon'] = BFD_APP_DATA_URL_PRE.$val['icon'];
	$tmp['membernum'] = $val['membernum'];
	$tmp['threads'] = $val['threads'];
	$tmp['posts'] = $val['posts'];
	$tmp['join_status'] = $val['join_status'];//小组加入状态 true false
	$tmp['join_str'] = $val['join_str'];
/*	$threads = array_values($fid_threads[$val['fid']]);
	foreach($threads as &$thread)
	{
		$thread['lastpost'] = date('Y-m-d H:i',$thread['lastpost']);
	}
	$tmp['threads'] = $threads;
*/
	$result[] = $tmp;
}


if(empty($result))
{
	BfdApp::display_result('result_is_null');
}
BfdApp::display_result('get_success',$result,'',$pagetotal);
?>
