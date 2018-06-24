<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum.php 29133 2012-03-27 08:04:24Z liulanbo $
 */

define('APPTYPEID', 2);
define('CURSCRIPT', 'forum');


require_once libfile('function/forum');
require_once libfile('function/forumlist');

loadforum();
$page = intval($_GET['page']);
$persize = BFD_APP_GROUP_THREAD_PAGESIZE;
$pagetotal = 0;
$pagesize = intval($_GET['pagesize']);
if($pagesize > 0)
{
	$persize = $pagesize;
}
if($page < 1)
{
	$page = 1;
}
$_G['ppp'] = $persize;
$_G['tpp'] = $persize;
$_G['page'] = $page;


if(empty($_G['forum']['fid']) || $_G['forum']['redirect'] || ($_G['fid'] == $_G['setting']['followforumid'] && $_G['adminid'] != 1)) {
	BfdApp::display_result('forum_nonexistence');
}

/*if($_G['forum']['redirect']) {
	dheader("Location: {$_G[forum][redirect]}");
} elseif($_G['forum']['type'] == 'group') {
	dheader("Location: forum.php?gid=$_G[fid]");
} elseif(empty($_G['forum']['fid'])) {
	showmessage('forum_nonexistence', NULL);
} elseif($_G['fid'] == $_G['setting']['followforumid'] && $_G['adminid'] != 1) {
	dheader("Location: home.php?mod=follow");
}*/

$_G['action']['fid'] = $_G['fid'];

$_GET['specialtype'] = isset($_GET['specialtype']) ? $_GET['specialtype'] : '';
$_GET['dateline'] = isset($_GET['dateline']) ? intval($_GET['dateline']) : 0;
$_GET['digest'] = isset($_GET['digest']) ? 1 : '';
$_GET['archiveid'] = isset($_GET['archiveid']) ? intval($_GET['archiveid']) : 0;

$showoldetails = isset($_GET['showoldetails']) ? $_GET['showoldetails'] : '';


$_G['forum']['name'] = strip_tags($_G['forum']['name']) ? strip_tags($_G['forum']['name']) : $_G['forum']['name'];
$_G['forum']['extra'] = empty($_G['forum']['extra']) ? array() : dunserialize($_G['forum']['extra']);
if(!is_array($_G['forum']['extra'])) {
	$_G['forum']['extra'] = array();
}

if($_G['forum']['status'] == 3) {
require_once libfile('function/group');
    $status = groupperm($_G['forum'], $_G['uid']);
    if($status == -1) {
        showmessage('forum_not_group', 'group.php');
    } elseif($status == 1) {
        showmessage('forum_group_status_off');
    } elseif($status == 2) {
        showmessage('forum_group_noallowed', 'forum.php?mod=group&fid='.$_G['fid']);
    } elseif($status == 3) {
        showmessage('forum_group_moderated', 'forum.php?mod=group&fid='.$_G['fid']);
    }

}

$threadtable_info = !empty($_G['cache']['threadtable_info']) ? $_G['cache']['threadtable_info'] : array();
/*$forumarchive = array();
if($_G['forum']['archive']) {
	foreach(C::t('forum_forum_threadtable')->fetch_all_by_fid($_G['fid']) as $archive) {
		$forumarchive[$archive['threadtableid']] = array(
			'displayname' => dhtmlspecialchars($threadtable_info[$archive['threadtableid']]['displayname']),
			'threads' => $archive['threads'],
			'posts' => $archive['posts'],
		);
		if(empty($forumarchive[$archive['threadtableid']]['displayname'])) {
			$forumarchive[$archive['threadtableid']]['displayname'] = lang('forum/thread', 'forum_archive').' '.$archive['threadtableid'];
		}
	}
}
*/


$forum_up = $_G['cache']['forums'][$_G['forum']['fup']];

$_G['forum']['banner'] = get_forumimg($_G['forum']['banner']);

if($_G['forum']['viewperm'] && !forumperm($_G['forum']['viewperm']) && !$_G['forum']['allowview']) {
	BfdApp::display_result('group_nopermission');
} elseif($_G['forum']['formulaperm']) {
	$res = lib_bfd_perm::formulaperm($_G['forum']['formulaperm']);
}

BfdApp::check_forum_password();
/*
if($_G['forum']['password']) {
	$headers = getallheaders();
	if($_G['forum']['password'] && $_G['forum']['password'] != $headers['df_app_forumpwd_'.$_G['fid']]) {
    	BfdApp::display_result('view_password_error');
	}
}
*/

$threadtableids = !empty($_G['cache']['threadtableids']) ? $_G['cache']['threadtableids'] : array();

$tableid = $_GET['archiveid'] && in_array($_GET['archiveid'], $threadtableids) ? intval($_GET['archiveid']) : 0;


$optionadd = $filterurladd = $searchsorton = '';
/*
$quicksearchlist = array();
if(!empty($_G['forum']['threadsorts']['types'])) {
	require_once libfile('function/threadsort');

	$showpic = intval($_GET['showpic']);
	$templatearray = $sortoptionarray = array();
	foreach($_G['forum']['threadsorts']['types'] as $stid => $sortname) {
		loadcache(array('threadsort_option_'.$stid, 'threadsort_template_'.$stid));
		sortthreadsortselectoption($stid);
		$templatearray[$stid] = $_G['cache']['threadsort_template_'.$stid]['subject'];
		$sortoptionarray[$stid] = $_G['cache']['threadsort_option_'.$stid];
	}

	if(!empty($_G['forum']['threadsorts']['defaultshow']) && empty($_GET['sortid']) && empty($_GET['sortall'])) {
		$_GET['sortid'] = $_G['forum']['threadsorts']['defaultshow'];
		$_GET['filter'] = 'sortid';
		$_SERVER['QUERY_STRING'] = $_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'].'&sortid='.$_GET['sortid'] : 'sortid='.$_GET['sortid'];
		$filterurladd = '&amp;filter=sort';
	}

	$_GET['sortid'] = $_GET['sortid'] ? $_GET['sortid'] : $_GET['searchsortid'];
	if(isset($_GET['sortid']) && $_G['forum']['threadsorts']['types'][$_GET['sortid']]) {
		$searchsortoption = $sortoptionarray[$_GET['sortid']];
		$quicksearchlist = quicksearch($searchsortoption);
		$_G['forum_optionlist'] = $_G['cache']['threadsort_option_'.$_GET['sortid']];
		$forum_optionlist = getsortedoptionlist();
	}
}
*/

//$page = $_G['page'];
//$page = $_G['setting']['threadmaxpages'] && $page > $_G['setting']['threadmaxpages'] ? 1 : $page;

$filteradd = $sortoptionurl = $sp = '';
$sorturladdarray = $selectadd = array();
$forumdisplayadd = array('orderby' => '');
$specialtype = array('poll' => 1, 'trade' => 2, 'reward' => 3, 'activity' => 4, 'debate' => 5);
$filterfield = array('digest', 'recommend', 'sortall', 'typeid', 'sortid', 'dateline', 'page', 'orderby', 'specialtype', 'author', 'view', 'reply', 'lastpost');

foreach($filterfield as $v) {
	$forumdisplayadd[$v] = '';
}

$filter = isset($_GET['filter']) && in_array($_GET['filter'], $filterfield) ? $_GET['filter'] : '';
$filterarr = $multiadd = array();
if($filter) {
	if($query_string = $_SERVER['QUERY_STRING']) {
		$query_string = substr($query_string, (strpos($query_string, "&") + 1));
		parse_str($query_string, $geturl);
		$geturl = daddslashes($geturl, 1);
		if($geturl && is_array($geturl)) {
			$issort = isset($_GET['sortid']) && isset($_G['forum']['threadsorts']['types'][$_GET['sortid']]) && $quicksearchlist ? TRUE : FALSE;
			$selectadd = $issort ? $geturl : array();
			foreach($filterfield as $option) {
				foreach($geturl as $field => $value) {
					if(in_array($field, $filterfield) && $option != $field && $field != 'page' && ($field != 'orderby' || !in_array($option, array('author', 'reply', 'view', 'lastpost', 'heat')))) {
						if(!(in_array($option, array('digest', 'recommend')) && in_array($field, array('digest', 'recommend')))) {
							$forumdisplayadd[$option] .= '&'.$field.'='.rawurlencode($value);
						}
					}
				}
				if($issort) {
					$sfilterfield = array_merge(array('filter', 'sortid', 'orderby', 'fid'), $filterfield);
					foreach($geturl as $soption => $value) {
						$forumdisplayadd[$soption] .= !in_array($soption, $sfilterfield) ? "&$soption=".rawurlencode($value) : '';
					}
				}
			}
			if($issort && is_array($quicksearchlist)) {
				foreach($quicksearchlist as $option) {
					$identifier = $option['identifier'];
					foreach($geturl as $option => $value) {
						$sorturladdarray[$identifier] .= !in_array($option, array('filter', 'sortid', 'orderby', 'fid', 'searchsort', $identifier)) ? "&amp;$option=$value" : '';
					}
				}
			}

			foreach($geturl as $field => $value) {
				if($field != 'page' && $field != 'fid' && $field != 'searchoption') {
					$multiadd[] = $field.'='.rawurlencode($value);
					if(in_array($field, $filterfield)) {
						$filteradd .= $sp;
						if($field == 'digest') {
							$filteradd .= "AND digest>'0'";
							$filterarr['digest'] = 1;
						} elseif($field == 'recommend') {
							$filteradd .= "AND recommends>'".intval($_G['setting']['recommendthread']['iconlevels'][0])."'";
							$filterarr['recommends'] = intval($_G['setting']['recommendthread']['iconlevels'][0]);
						} elseif($field == 'specialtype') {
							$filteradd .= "AND special='$specialtype[$value]'";
							$filterarr['special'] = $specialtype[$value];
							$filterarr['specialthread'] = 1;
							if($value == 'reward') {
								if($_GET['rewardtype'] == 1) {
									$filteradd .= "AND price>0";
									$filterarr['pricemore'] = 0;
								} elseif($_GET['rewardtype'] == 2) {
									$filteradd .= "AND price<0";
									$filterarr['pricesless'] = 0;
								}
							}
						} elseif($field == 'dateline') {
							$filteradd .= $value ? "AND lastpost>='".(TIMESTAMP - $value)."'" : '';
							if($value) {
								$filterarr['lastpostmore'] = TIMESTAMP - $value;
							}
						} elseif($field == 'typeid' || $field == 'sortid') {
							$filteradd .= "AND $field='$value'";
							$fieldstr = $field == 'typeid' ? 'intype' : 'insort';
							$filterarr[$fieldstr] = $value;
						}
						$sp = ' ';
					}
				}
			}
		}
	}
	$simplestyle = true;
}

if(!empty($_GET['orderby']) && !$_G['setting']['closeforumorderby'] && in_array($_GET['orderby'], array('lastpost', 'dateline', 'replies', 'views', 'recommends', 'heats'))) {
	$forumdisplayadd['orderby'] .= '&orderby='.$_GET['orderby'];
} else {
	$_GET['orderby'] = isset($_G['cache']['forums'][$_G['fid']]['orderby']) ? $_G['cache']['forums'][$_G['fid']]['orderby'] : 'lastpost';
}

$_GET['ascdesc'] = isset($_G['cache']['forums'][$_G['fid']]['ascdesc']) ? $_G['cache']['forums'][$_G['fid']]['ascdesc'] : 'DESC';

$check = array();
$check[$filter] = $check[$_GET['orderby']] = $check[$_GET['ascdesc']] = 'selected="selected"';


if($_G['forum']['threadsorts']['types'] && $sortoptionarray && ($_GET['searchoption'] || $_GET['searchsort'])) {
	$sortid = intval($_GET['sortid']);

	if($_GET['searchoption']){
		$forumdisplayadd['page'] = '&sortid='.$sortid;
		foreach($_GET['searchoption'] as $optionid => $option) {
			$optionid = intval($optionid);
			$searchoption = '';
			if(is_array($option['value'])) {
				foreach($option['value'] as $v) {
					$v = rawurlencode((string)$v);
					$searchoption .= "&searchoption[$optionid][value][$v]=$v";
				}
			} else {
				$option['value'] = rawurlencode((string)$option['value']);
				$option['value'] && $searchoption = "&searchoption[$optionid][value]=$option[value]";
			}
			$option['type'] = rawurlencode((string)$option['type']);
			$identifier = $sortoptionarray[$sortid][$optionid]['identifier'];
			$forumdisplayadd['page'] .= $searchoption ? "$searchoption&searchoption[$optionid][type]=$option[type]" : '';
		}
	}

	$searchsorttids = sortsearch($_GET['sortid'], $sortoptionarray, $_GET['searchoption'], $selectadd, $_G['fid']);
	$filteradd .= "AND t.tid IN (".dimplode($searchsorttids).")";
	$filterarr['intids'] = $searchsorttids ? $searchsorttids : array(0);
}

if(isset($_GET['searchoption'])) {
    $_GET['searchoption'] = dhtmlspecialchars($_GET['searchoption']);
}

if($_G['forum']['relatedgroup']) {
	$relatedgroup = explode(',', $_G['forum']['relatedgroup']);
	$relatedgroup[] = $_G['fid'];
	$filterarr['inforum'] = $relatedgroup;
} else {
	$filterarr['inforum'] = $_G['fid'];
}
if(empty($filter) && empty($_GET['sortid']) && empty($_G['forum']['relatedgroup'])) {
	if($forumarchive) {
		if($_GET['archiveid']) {
			$_G['forum_threadcount'] = $forumarchive[$_GET['archiveid']]['threads'];
		} else {
			$primarytabthreads = $_G['forum']['threads'];
			foreach($forumarchive as $arcid => $avalue) {
				if($arcid) {
					$primarytabthreads = $primarytabthreads - $avalue['threads'];
				}
			}
			$_G['forum_threadcount'] = $primarytabthreads;
		}
	} else {
		$_G['forum_threadcount'] = $_G['forum']['threads'];
	}
} else {
	$filterarr['sticky'] = 0;
	$_G['forum_threadcount'] = C::t('forum_thread')->count_search($filterarr, $tableid);
}

$thisgid = $_G['forum']['type'] == 'forum' ? $_G['forum']['fup'] : (!empty($_G['cache']['forums'][$_G['forum']['fup']]['fup']) ? $_G['cache']['forums'][$_G['forum']['fup']]['fup'] : 0);
$forumstickycount = $stickycount = 0;
$stickytids = '';
if($_G['setting']['globalstick'] && $_G['forum']['allowglobalstick']) {
	$stickytids = explode(',', str_replace("'", '', $_G['cache']['globalstick']['global']['tids']));
	if(!empty($_G['cache']['globalstick']['categories'][$thisgid]['count'])) {
		$stickytids = array_merge($stickytids, explode(',', str_replace("'", '', $_G['cache']['globalstick']['categories'][$thisgid]['tids'])));
	}

	if($_G['forum']['status'] != 3) {
		$stickycount = $_G['cache']['globalstick']['global']['count'];
		if(!empty($_G['cache']['globalstick']['categories'][$thisgid])) {
			$stickycount += $_G['cache']['globalstick']['categories'][$thisgid]['count'];
		}
	}
}

$forumstickytids = array();
if($_G['forum']['allowglobalstick']) {
	$forumstickycount = 0;
	$forumstickfid = $_G['forum']['status'] != 3 ? $_G['fid'] : $_G['forum']['fup'];
	if(isset($_G['cache']['forumstick'][$forumstickfid])) {
		$forumstickycount = count($_G['cache']['forumstick'][$forumstickfid]);
		$forumstickytids = $_G['cache']['forumstick'][$forumstickfid];
	}
	if(!empty($forumstickytids)) {
		$stickytids = array_merge($stickytids, $forumstickytids);
	}
	$stickycount += $forumstickycount;
}


$filterbool = !empty($filter) && in_array($filter, $filterfield);
$_G['forum_threadcount'] += $filterbool ? 0 : $stickycount;
if(@ceil($_G['forum_threadcount']/$_G['tpp']) < $page) {
	$page = 1;
}
$start_limit = ($page - 1) * $_G['tpp'];
if(isset($_GET['test']))
	echo $_G['forum_threadcount'];

$_G['forum_threadlist'] = $threadids = array();

$displayorderadd = !$filterbool && $stickycount ? 't.displayorder IN (0, 1)' : 't.displayorder IN (0, 1, 2, 3, 4)';
$filterarr['sticky'] = 4;
$filterarr['displayorder'] = !$filterbool && $stickycount ? array(0, 1) : array(0, 1, 2, 3, 4);
if(($start_limit && $start_limit > $stickycount) || !$stickycount || $filterbool) {
	$indexadd = '';
	if(strexists($filteradd, "t.digest>'0'")) {
		$indexadd = " FORCE INDEX (digest) ";
	}
	$querysticky = '';
	$start = $filterbool ? $start_limit : $start_limit - $stickycount;
	$threadlist = C::t('forum_thread')->fetch_all_search($filterarr, $tableid, $start, $_G['tpp'], "displayorder DESC, $_GET[orderby] $_GET[ascdesc]", '', $indexadd);

} else {
	$filterarr1 = $filterarr;
	$filterarr1['inforum'] = '';
	$filterarr1['intids'] = $stickytids;
	$limit = $stickycount - $start_limit < $_G['tpp'] ? $stickycount - $start_limit : $_G['tpp'];
	$filterarr1['displayorder'] = array(2, 3, 4);
	$threadlist = C::t('forum_thread')->fetch_all_search($filterarr1, $tableid, $start_limit, $limit, "displayorder DESC,$_GET[orderby] $_GET[ascdesc]", '');

	if($_G['tpp'] - $stickycount + $start_limit > 0) {
		$limit = $_G['tpp'] - $stickycount + $start_limit;
		$otherthread =  C::t('forum_thread')->fetch_all_search($filterarr, $tableid, 0, $limit, "displayorder DESC, $_GET[orderby] $_GET[ascdesc]", '');
		$threadlist = array_merge($threadlist, $otherthread);
		unset($otherthread);
	} else {
		$query = '';
	}

}
if(empty($threadlist) && $page <= ceil($_G['forum_threadcount'] / $_G['tpp'])) {
	require_once libfile('function/post');
	updateforumcount($_G['fid']);
}

$_G['ppp'] = $_G['forum']['threadcaches'] && !$_G['uid'] ? $_G['setting']['postperpage'] : $_G['ppp'];
$page = $_G['page'];
$todaytime = strtotime(dgmdate(TIMESTAMP, 'Ymd'));

$verify = $verifyuids = $grouptids = array();
$threadindex = 0;
foreach($threadlist as $thread) {
	$thread['ordertype'] = getstatus($thread['status'], 4);
/*	if($_G['forum']['picstyle'] && empty($_G['cookie']['forumdefstyle'])) {
		if($thread['fid'] != $_G['fid'] && empty($thread['cover'])) {
			continue;
		}
		$thread['coverpath'] = getthreadcover($thread['tid'], $thread['cover']);
		$thread['cover'] = abs($thread['cover']);
	}
	$thread['forumstick'] = in_array($thread['tid'], $forumstickytids);
	$thread['related_group'] = 0;
	if($_G['forum']['relatedgroup'] && $thread['fid'] != $_G['fid']) {
		if($thread['closed'] > 1) continue;
		$thread['related_group'] = 1;
		$grouptids[] = $thread['tid'];
	}
	$thread['lastposterenc'] = rawurlencode($thread['lastposter']);
	if($thread['typeid'] && !empty($_G['forum']['threadtypes']['prefix']) && isset($_G['forum']['threadtypes']['types'][$thread['typeid']])) {
		if($_G['forum']['threadtypes']['prefix'] == 1) {
			$thread['typehtml'] = '<em>[<a href="forum.php?mod=forumdisplay&fid='.$_G['fid'].'&amp;filter=typeid&amp;typeid='.$thread['typeid'].'">'.$_G['forum']['threadtypes']['types'][$thread['typeid']].'</a>]</em>';
		} elseif($_G['forum']['threadtypes']['icons'][$thread['typeid']] && $_G['forum']['threadtypes']['prefix'] == 2) {
			$thread['typehtml'] = '<em><a title="'.$_G['forum']['threadtypes']['types'][$thread['typeid']].'" href="forum.php?mod=forumdisplay&fid='.$_G['fid'].'&amp;filter=typeid&amp;typeid='.$thread['typeid'].'">'.'<img style="vertical-align: middle;padding-right:4px;" src="'.$_G['forum']['threadtypes']['icons'][$thread['typeid']].'" alt="'.$_G['forum']['threadtypes']['types'][$thread['typeid']].'" /></a></em>';
		}
		$thread['typename'] = $_G['forum']['threadtypes']['types'][$thread['typeid']];
	} else {
		$thread['typename'] = $thread['typehtml'] = '';
	}

	$thread['sorthtml'] = $thread['sortid'] && !empty($_G['forum']['threadsorts']['prefix']) && isset($_G['forum']['threadsorts']['types'][$thread['sortid']]) ?
		'<em>[<a href="forum.php?mod=forumdisplay&fid='.$_G['fid'].'&amp;filter=sortid&amp;sortid='.$thread['sortid'].'">'.$_G['forum']['threadsorts']['types'][$thread['sortid']].'</a>]</em>' : '';
	$thread['multipage'] = '';
	$topicposts = $thread['special'] ? $thread['replies'] : $thread['replies'] + 1;
	$multipate_archive = $_GET['archiveid'] && in_array($_GET['archiveid'], $threadtableids) ? "archiveid={$_GET['archiveid']}" : '';
	if($topicposts > $_G['ppp']) {
		$pagelinks = '';
		$thread['pages'] = ceil($topicposts / $_G['ppp']);
		$realtid = $_G['forum']['status'] != 3 && $thread['isgroup'] == 1 ? $thread['closed'] : $thread['tid'];
		for($i = 2; $i <= 6 && $i <= $thread['pages']; $i++) {
			$pagelinks .= "<a href=\"forum.php?mod=viewthread&tid=$realtid&amp;".(!empty($multipate_archive) ? "$multipate_archive&amp;" : '')."extra=$extra&amp;page=$i\">$i</a>";
		}
		if($thread['pages'] > 6) {
			$pagelinks .= "..<a href=\"forum.php?mod=viewthread&tid=$realtid&amp;".(!empty($multipate_archive) ? "$multipate_archive&amp;" : '')."extra=$extra&amp;page=$thread[pages]\">$thread[pages]</a>";
		}
		$thread['multipage'] = '&nbsp;...'.$pagelinks;
	}

	if($thread['highlight']) {
		$string = sprintf('%02d', $thread['highlight']);
		$stylestr = sprintf('%03b', $string[0]);

		$thread['highlight'] = ' style="';
		$thread['highlight'] .= $stylestr[0] ? 'font-weight: bold;' : '';
		$thread['highlight'] .= $stylestr[1] ? 'font-style: italic;' : '';
		$thread['highlight'] .= $stylestr[2] ? 'text-decoration: underline;' : '';
		$thread['highlight'] .= $string[1] ? 'color: '.$_G['forum_colorarray'][$string[1]] : '';
		$thread['highlight'] .= '"';
	} else {
		$thread['highlight'] = '';
	}

	$thread['recommendicon'] = '';
	if(!empty($_G['setting']['recommendthread']['status']) && $thread['recommends']) {
		foreach($_G['setting']['recommendthread']['iconlevels'] as $k => $i) {
			if($thread['recommends'] > $i) {
				$thread['recommendicon'] = $k+1;
				break;
			}
		}
	}
*/
	$thread['moved'] = $thread['heatlevel'] = $thread['new'] = 0;
	if($_G['forum']['status'] != 3 && ($thread['closed'] || ($_G['forum']['autoclose'] && $thread['fid'] == $_G['fid'] && TIMESTAMP - $thread[$closedby] > $_G['forum']['autoclose']))) {
		if($thread['isgroup'] == 1) {
			$thread['folder'] = 'common';
			$grouptids[] = $thread['closed'];
		} else {
			if($thread['closed'] > 1) {
				$thread['moved'] = $thread['tid'];
				$thread['replies'] = '-';
				$thread['views'] = '-';
			}
			$thread['folder'] = 'lock';
		}
	} elseif($_G['forum']['status'] == 3 && $thread['closed'] == 1) {
		$thread['folder'] = 'lock';
	} else {
		$thread['folder'] = 'common';
		$thread['weeknew'] = TIMESTAMP - 604800 <= $thread['dbdateline'];
		if($thread['replies'] > $thread['views']) {
			$thread['views'] = $thread['replies'];
		}
		if($_G['setting']['heatthread']['iconlevels']) {
			foreach($_G['setting']['heatthread']['iconlevels'] as $k => $i) {
				if($thread['heats'] > $i) {
					$thread['heatlevel'] = $k + 1;
					break;
				}
			}
		}
	}
	$thread['icontid'] = $thread['forumstick'] || !$thread['moved'] && $thread['isgroup'] != 1 ? $thread['tid'] : $thread['closed'];
	if(!$thread['forumstick'] && ($thread['isgroup'] == 1 || $thread['fid'] != $_G['fid'])) {
		$thread['icontid'] = $thread['closed'] > 1 ? $thread['closed'] : $thread['tid'];
	}
	$thread['istoday'] = $thread['dateline'] > $todaytime ? 1 : 0;
	$thread['dbdateline'] = $thread['dateline'];
	$thread['dateline'] = dgmdate($thread['dateline'], 'u', '9999', getglobal('setting/dateformat'));
	$thread['dblastpost'] = $thread['lastpost'];
	$thread['lastpost'] = dgmdate($thread['lastpost'], 'u');

	if(in_array($thread['displayorder'], array(1, 2, 3, 4))) {
		$thread['id'] = 'stickthread_'.$thread['tid'];
		$separatepos++;
	} else {
		$thread['id'] = 'normalthread_'.$thread['tid'];
		if($thread['folder'] == 'common' && $thread['dblastpost'] >= $forumlastvisit || !$forumlastvisit) {
			$thread['new'] = 1;
			$thread['folder'] = 'new';
			$thread['weeknew'] = TIMESTAMP - 604800 <= $thread['dbdateline'];
		}
	}
	if(isset($_G['setting']['verify']['enabled']) && $_G['setting']['verify']['enabled']) {
		$verifyuids[$thread['authorid']] = $thread['authorid'];
	}
	$thread['mobile'] = base_convert(getstatus($thread['status'], 13).getstatus($thread['status'], 12).getstatus($thread['status'], 11), 2, 10);
	$thread['rushreply'] = getstatus($thread['status'], 3);
	$threadids[$threadindex] = $thread['tid'];
	$_G['forum_threadlist'][$threadindex] = $thread;
	$threadindex++;

}
if(!empty($threadids)) {
	$indexlist = array_flip($threadids);
	foreach(C::t('forum_threadaddviews')->fetch_all($threadids) as $tidkey => $value) {
		$index = $indexlist[$tidkey];
		$threadlist[$index]['views'] += $value['addviews'];
		$_G['forum_threadlist'][$index]['views'] += $value['addviews'];
	}
}

$result = array();
$result['groupinfo'] = array();
$result['groupinfo']['fid'] = $_G['forum']['fid'];
$result['groupinfo']['name'] = BfdApp::bfd_html_entity_decode(strip_tags($_G['forum']['name']));
$result['groupinfo']['description'] = strip_tags($_G['forum']['description']);
$result['groupinfo']['icon'] = BFD_APP_DATA_URL_PRE.$_G['forum']['icon'];
$result['groupinfo']['membernum'] = $_G['forum']['membernum'];
$result['groupinfo']['dateline'] = $_G['forum']['dateline'];
$result['groupinfo']['lastupdate'] = date('Y-m-d H:i:s',$_G['forum']['lastupdate']);
$result['groupinfo']['founderuid'] = $_G['forum']['founderuid'];
$result['groupinfo']['foundername'] = $_G['forum']['foundername'];
$result['groupinfo']['banner'] = BFD_APP_DATA_URL_PRE.$_G['forum']['banner'];
$result['groupinfo']['threads'] = $_G['forum']['threads'];
$result['groupinfo']['posts'] = $_G['forum']['posts'];
$result['groupinfo']['todayposts'] = $_G['forum']['todayposts'];
$result['groupinfo']['lastpost'] = $_G['forum']['lastpost'];
$result['groupinfo']['join_status'] = $_G['forum']['join_status'];
$result['groupinfo']['join_status'] = $_G['forum']['password'];
$tmptypes = $_G['forum']['threadtypes']['types'];
$result['groupinfo']['threadtypes'] = array();
foreach($tmptypes as $key=>$val)
{
	$result['groupinfo']['threadtypes'][] = array('typeid'=>$key,'typename'=>$val);
}

$datalist = array();
foreach($_G['forum_threadlist'] as $thread)
{
	$tmparr = array();
	$tmparr['tid'] = $thread['tid'];
    $tmparr['fid'] = $thread['fid'];
    $tmparr['author'] = $thread['author'];
    $tmparr['authorid'] = $thread['authorid'];
    $tmparr['subject'] = html_entity_decode($thread['subject'], ENT_COMPAT | ENT_HTML401,BFD_APP_CHARSET);//$thread['subject'];
    //$tmparr['dateline'] = html_entity_decode(strip_tags($thread['dateline']), ENT_COMPAT | ENT_HTML401,BFD_APP_CHARSET);
    $tmparr['dateline'] = str_replace('&nbsp;',' ',strip_tags($thread['dateline']));
    $tmparr['lastpost'] = str_replace('&nbsp;',' ',strip_tags($thread['lastpost']));
   // $tmparr['lastpost'] = html_entity_decode(strip_tags($thread['lastpost']), ENT_COMPAT | ENT_HTML401,BFD_APP_CHARSET);
    $tmparr['lastposter'] = $thread['lastposter'];
    $tmparr['views'] = $thread['views'];
    $tmparr['replies'] = $thread['replies'];
    $tmparr['displayorder'] = $thread['displayorder'];
    $tmparr['typeid'] = $thread['typeid'];
    $tmparr['digest'] = $thread['digest'];
    $tmparr['ispicture'] = $thread['attachment'] == 2 ? 1:0;
	$datalist[] = $tmparr;
}
$result['threadlist'] = $datalist;

$pagetotal = ceil($_G['forum_threadcount']/$persize);
$pagetotal = $pagetotal == 0 ? 1 : $pagetotal;
BfdApp::display_result('get_success',$result,'',$pagetotal);

?>
