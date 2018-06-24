<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: search_forum.php 29447 2012-04-12 08:15:15Z chenmengshu $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
define('NOROBOT', TRUE);

if(!$_G['setting']['search']['forum']['status']) {
	BfdApp::display_result('search_forum_closed');
}

if(!$_G['adminid'] && !($_G['group']['allowsearch'] & 2)) {
	BfdApp::display_result('group_nopermission');
}

$_G['setting']['search']['forum']['searchctrl'] = intval($_G['setting']['search']['forum']['searchctrl']);

require_once libfile('function/forumlist');
require_once libfile('function/forum');
require_once libfile('function/post');
loadcache(array('forums', 'posttable_info'));
$srchmod = 2;

$cachelife_time = 300;		// Life span for cache of searching in specified range of time
$cachelife_text = 3600;		// Life span for cache of text searching

$srchtype = empty($_GET['srchtype']) ? '' : trim($_GET['srchtype']);
$searchid = isset($_GET['searchid']) ? intval($_GET['searchid']) : 0;
$seltableid = intval($_GET['seltableid']);

if($srchtype != 'title' && $srchtype != 'fulltext') {
	$srchtype = '';
}

//$srchtxt = trim($_GET['srchtxt']);
$srchuid = intval($_GET['srchuid']);
$srchuname = isset($_GET['srchuname']) ? trim(str_replace('|', '', $_GET['srchuname'])) : '';;
$srchfrom = intval($_GET['srchfrom']);
$before = intval($_GET['before']);
$srchfid = $_GET['srchfid'];
$srhfid = intval($_GET['srhfid']);

$keyword = isset($srchtxt) ? dhtmlspecialchars(trim($srchtxt)) : '';

/*
$mySearchData = $_G['setting']['my_search_data'];
if($mySearchData['status'] && !$srchfrom && !$searchid) {
	if (!$_G['setting']['my_siteid']) {
		dheader('Location: index.php');
	}
	$appService = Cloud::loadClass('Service_App');
	if($appService->getCloudAppStatus('search') && $searchparams) {
		$source = 'discuz';
		$cloudSource = array('collectionsearch', 'hotsearch');
		if(!empty($_GET['srhlocality'])) {
			$sourcetype = explode('::', $_GET['srhlocality']);
			if($sourcetype[0] == 'forum') {
				$source = $sourcetype[1] == 'index' ? 'forum' : ($sourcetype[1] == 'forumdisplay' ? 'forum_forum' : 'forum_thread');
			} elseif($sourcetype[0] == 'portal') {
				$source = $sourcetype[1] == 'view' ? 'article' : ($sourcetype[1] == 'list' ? 'portal_list': 'portal');
			} elseif($sourcetype[0] == 'group') {
				$source = $sourcetype[1] == 'viewthread' ? 'group_thread' : ($sourcetype[1] == 'group' ? 'group_forum' : 'group');
			} elseif($sourcetype[0] == 'home') {
				$source = 'home'.(empty($sourcetype[1]) ? '' : '_'.$sourcetype[1]);
			} elseif($sourcetype[0] == 'misc' && $sourcetype[1] == 'ranklist') {
				$source = 'toplist';
			}
		} elseif(in_array($_GET['source'], $cloudSource)) {
			$source = $_GET['source'];
		}

		$params = array();
		$params['source'] = $source;

		$params['q'] = $keyword;
		$params['module'] = 'forum';
		if($srhfid) {
			$params['fId'] = $srhfid;
		}
		if($_GET['adv']) {
			$params['isAdv'] = 1;
		}
		if(!empty($_GET['author'])) {
			$params['author']=$_GET['author'];
		}
		if(!empty($_GET['scope'])) {
			$params['scope']=$_GET['scope'];
		}
		if(!empty($_GET['orderField'])) {
			$params['orderField']=$_GET['orderField'];
		}
		$searchparams['params'] = array_merge($searchparams['params'], $params);

		$utilService = Cloud::loadClass('Service_Util');
		$url = $searchparams['url'] . '?' . $utilService->httpBuildQuery($searchparams['params'], '', '&');
		$utilService->redirect($url);
		die;
	}

}
*/

	$orderby = in_array($_GET['orderby'], array('dateline', 'replies', 'views')) ? $_GET['orderby'] : 'lastpost';
	$ascdesc = isset($_GET['ascdesc']) && $_GET['ascdesc'] == 'asc' ? 'asc' : 'desc';



		if($_G['group']['allowsearch'] & 32 && $srchtype == 'fulltext') {
			periodscheck('searchbanperiods');
		} elseif($srchtype != 'title') {
			$srchtype = 'title';
		}

		$forumsarray = array();
		if(!empty($srchfid)) {
			foreach((is_array($srchfid) ? $srchfid : explode('_', $srchfid)) as $forum) {
				if($forum = intval(trim($forum))) {
					$forumsarray[] = $forum;
				}
			}
		}
		$fids = $comma = '';
		foreach($_G['cache']['forums'] as $fid => $forum) {
			if( (!$forum['viewperm'] && $_G['group']['readaccess']) || ($forum['viewperm'] && forumperm($forum['viewperm']))) {
				if(!$forumsarray || in_array($fid, $forumsarray)) {
					$fids .= "$comma'$fid'";
					$comma = ',';
				}
			}
		}

		if($_G['setting']['threadplugins'] && $specialplugin) {
			$specialpluginstr = implode("','", $specialplugin);
			$special[] = 127;
		} else {
			$specialpluginstr = '';
		}
		$special = $_GET['special'];
		$specials = $special ? implode(',', $special) : '';
		$srchfilter = in_array($_GET['srchfilter'], array('all', 'digest', 'top')) ? $_GET['srchfilter'] : 'all';

		$searchstring = 'forum|'.$srchtype.'|'.base64_encode($srchtxt).'|'.intval($srchuid).'|'.$srchuname.'|'.addslashes($fids).'|'.intval($srchfrom).'|'.intval($before).'|'.$srchfilter.'|'.$specials.'|'.$specialpluginstr.'|'.$seltableid;
		$searchindex = array('id' => 0, 'dateline' => '0');

		foreach(C::t('common_searchindex')->fetch_all_search($_G['setting']['search']['forum']['searchctrl'], $_G['clientip'], $_G['uid'], $_G['timestamp'], $searchstring, $srchmod) as $index) {
			if($index['indexvalid'] && $index['dateline'] > $searchindex['dateline']) {
				$searchindex = array('id' => $index['searchid'], 'dateline' => $index['dateline']);
				break;
			} elseif($_G['adminid'] != '1' && $index['flood']) {
				BfdApp::display_result('search_ctrl');
			}
		}

		if($searchindex['id']) {

			$searchid = $searchindex['id'];

		} else {

			!($_G['group']['exempt'] & 2) && checklowerlimit('search');

			if(!$srchtxt && !$srchuid && !$srchuname && !$srchfrom && !in_array($srchfilter, array('digest', 'top')) && !is_array($special)) {
				BfdApp::display_result('search_forum_invalid');
			} elseif(isset($srchfid) && !empty($srchfid) && $srchfid != 'all' && !(is_array($srchfid) && in_array('all', $srchfid)) && empty($forumsarray)) {
				BfdApp::display_result('search_forum_invalid');
			} elseif(!$fids) {
				BfdApp::display_result('group_nopermission');
			}

			if($_G['adminid'] != '1' && $_G['setting']['search']['forum']['maxspm']) {
				if(C::t('common_searchindex')->count_by_dateline($_G['timestamp'], $srchmod) >= $_G['setting']['search']['forum']['maxspm']) {
					BfdApp::display_result('search_toomany');
				}
			}

			if($srchtype == 'fulltext' && $_G['setting']['sphinxon']) {
				require_once libfile('class/sphinx');

				$s = new SphinxClient();
				$s->setServer($_G['setting']['sphinxhost'], intval($_G['setting']['sphinxport']));
				$s->setMaxQueryTime(intval($_G['setting']['sphinxmaxquerytime']));
				$s->SetRankingMode($_G['setting']['sphinxrank']);
				$s->setLimits(0, intval($_G['setting']['sphinxlimit']), intval($_G['setting']['sphinxlimit']));
				$s->setGroupBy('tid', SPH_GROUPBY_ATTR);

				if($srchfilter == 'digest') {
					$s->setFilterRange('digest', 1, 3, false);
				}
				if($srchfilter == 'top') {
					$s->setFilterRange('displayorder', 1, 2, false);
				} else {
					$s->setFilterRange('displayorder', 0, 2, false);
				}

				if(!empty($srchfrom) && empty($srchtxt) && empty($srchuid) && empty($srchuname)) {
					$expiration = TIMESTAMP + $cachelife_time;
					$keywords = '';
					if($before) {
						$spx_timemix = 0;
						$spx_timemax = TIMESTAMP - $srchfrom;
					} else {
						$spx_timemix = TIMESTAMP - $srchfrom;
						$spx_timemax = TIMESTAMP;
					}
				} else {
					$uids = array();
					if($srchuname) {
						$uids = array_keys(C::t('common_member')->fetch_all_by_like_username($srchuname, 0, 50));
						if(count($uids) == 0) {
							$uids = array(0);
						}
					} elseif($srchuid) {
						$uids = array($srchuid);
					}
					if(is_array($uids) && count($uids) > 0) {
						$s->setFilter('authorid', $uids, false);
					}

					if($srchtxt) {
						if(preg_match("/\".*\"/", $srchtxt)) {
							$spx_matchmode = "PHRASE";
							$s->setMatchMode(SPH_MATCH_PHRASE);
						} elseif(preg_match("(AND|\+|&|\s)", $srchtxt) && !preg_match("(OR|\|)", $srchtxt)) {
							$srchtxt = preg_replace("/( AND |&| )/is", "+", $srchtxt);
							$spx_matchmode = "ALL";
							$s->setMatchMode(SPH_MATCH_ALL);
						} else {
							$srchtxt = preg_replace("/( OR |\|)/is", "+", $srchtxt);
							$spx_matchmode = 'ANY';
							$s->setMatchMode(SPH_MATCH_ANY);
						}
						$srchtxt = str_replace('*', '%', addcslashes($srchtxt, '%_'));
						foreach(explode('+', $srchtxt) as $text) {
							$text = trim(daddslashes($text));
							if($text) {
								$sqltxtsrch .= $andor;
								$sqltxtsrch .= $srchtype == 'fulltext' ? "(p.message LIKE '%".str_replace('_', '\_', $text)."%' OR p.subject LIKE '%$text%')" : "t.subject LIKE '%$text%'";
							}
						}
						$sqlsrch .= " AND ($sqltxtsrch)";
					}

					if(!empty($srchfrom)) {
						if($before) {
							$spx_timemix = 0;
							$spx_timemax = TIMESTAMP - $srchfrom;
						} else {
							$spx_timemix = TIMESTAMP - $srchfrom;
							$spx_timemax = TIMESTAMP;
						}
						$s->setFilterRange('lastpost', $spx_timemix, $spx_timemax, false);
					}
					if(!empty($specials)) {
						$s->setFilter('special', explode(",", $special), false);
					}

					$keywords = str_replace('%', '+', $srchtxt).(trim($srchuname) ? '+'.str_replace('%', '+', $srchuname) : '');
					$expiration = TIMESTAMP + $cachelife_text;

				}
				if($srchtype == "fulltext") {
					$result = $s->query("'".$srchtxt."'", $_G['setting']['sphinxmsgindex']);
				} else {
					$result = $s->query($srchtxt, $_G['setting']['sphinxsubindex']);
				}
				$tids = array();
				if($result) {
					if(is_array($result['matches'])) {
						foreach($result['matches'] as $value) {
							if($value['attrs']['tid']) {
								$tids[$value['attrs']['tid']] = $value['attrs']['tid'];
							}
						}
					}
				}
				if(count($tids) == 0) {
					$ids = 0;
					$num = 0;
				} else {
					$ids = implode(",", $tids);
					$num = $result['total_found'];
				}
			} else {
				$digestltd = $srchfilter == 'digest' ? "t.digest>'0' AND" : '';
				$topltd = $srchfilter == 'top' ? "AND t.displayorder>'0'" : "AND t.displayorder>='0'";

				if(!empty($srchfrom) && empty($srchtxt) && empty($srchuid) && empty($srchuname)) {

					$searchfrom = $before ? '<=' : '>=';
					$searchfrom .= TIMESTAMP - $srchfrom;
					$sqlsrch = "FROM ".DB::table('forum_thread')." t WHERE $digestltd t.fid IN ($fids) $topltd AND t.lastpost$searchfrom";
					$expiration = TIMESTAMP + $cachelife_time;
					$keywords = '';

				} else {
					$sqlsrch = $srchtype == 'fulltext' ?
					"FROM ".DB::table(getposttable($seltableid))." p, ".DB::table('forum_thread')." t WHERE $digestltd t.fid IN ($fids) $topltd AND p.tid=t.tid AND p.invisible='0'" :
					"FROM ".DB::table('forum_thread')." t WHERE $digestltd t.fid IN ($fids) $topltd";
					if($srchuname) {
						$srchuid = array_keys(C::t('common_member')->fetch_all_by_like_username($srchuname, 0, 50));
						if(!$srchuid) {
							$sqlsrch .= ' AND 0';
						}
					}/* elseif($srchuid) {
						$srchuid = "'$srchuid'";
					}*/

					if($srchtxt) {
						$srcharr = $srchtype == 'fulltext' ? searchkey($keyword, "(p.message LIKE '%{text}%' OR p.subject LIKE '%{text}%')", true) : searchkey($keyword,"t.subject LIKE '%{text}%'", true);
						$srchtxt = $srcharr[0];
						$sqlsrch .= $srcharr[1];
					}

					if($srchuid) {
						$sqlsrch .= ' AND '.($srchtype == 'fulltext' ? 'p' : 't').'.authorid IN ('.dimplode((array)$srchuid).')';
					}

					if(!empty($srchfrom)) {
						$searchfrom = ($before ? '<=' : '>=').(TIMESTAMP - $srchfrom);
						$sqlsrch .= " AND t.lastpost$searchfrom";
					}

					if(!empty($specials)) {
						$sqlsrch .=  " AND special IN (".dimplode($special).")";
					}

					$keywords = str_replace('%', '+', $srchtxt);
					$expiration = TIMESTAMP + $cachelife_text;

				}

				$num = $ids = 0;
				$_G['setting']['search']['forum']['maxsearchresults'] = $_G['setting']['search']['forum']['maxsearchresults'] ? intval($_G['setting']['search']['forum']['maxsearchresults']) : 500;
				$query = DB::query("SELECT ".($srchtype == 'fulltext' ? 'DISTINCT' : '')." t.tid, t.closed, t.author, t.authorid $sqlsrch LIMIT ".$_G['setting']['search']['forum']['maxsearchresults']);
				while($thread = DB::fetch($query)) {
					$ids .= ','.$thread['tid'];
					$num++;
				}
				DB::free_result($query);
			}
			//discuz_error::write_error_log("SELECT ".($srchtype == 'fulltext' ? 'DISTINCT' : '')." t.tid, t.closed, t.author, t.authorid $sqlsrch LIMIT ".$_G['setting']['search']['forum']['maxsearchresults']);
			
			$searchid = C::t('common_searchindex')->insert(array(
				'srchmod' => $srchmod,
				'keywords' => $keywords,
				'searchstring' => $searchstring,
				'useip' => $_G['clientip'],
				'uid' => $_G['uid'],
				'dateline' => $_G['timestamp'],
				'expiration' => $expiration,
				'num' => $num,
				'ids' => $ids
			), true);

			!($_G['group']['exempt'] & 2) && updatecreditbyaction('search');
		}



	if(!empty($searchid)) {

		require_once libfile('function/misc');

		$page = max(1, intval($_GET['page']));
		$start_limit = ($page - 1) * $_G['tpp'];

		$index = C::t('common_searchindex')->fetch_by_searchid_srchmod($searchid, $srchmod);
		if(!$index) {
			BfdApp::display_result('search_id_invalid');
		}

		$keyword = dhtmlspecialchars($index['keywords']);
		$keyword = $keyword != '' ? str_replace('+', ' ', $keyword) : '';

		$index['keywords'] = rawurlencode($index['keywords']);
		$searchstring = explode('|', $index['searchstring']);
		$index['searchtype'] = $searchstring[0];//preg_replace("/^([a-z]+)\|.*/", "\\1", $index['searchstring']);
		$searchstring[2] = base64_decode($searchstring[2]);
		$srchuname = $searchstring[3];
		$modfid = 0;
		if($keyword) {
			$modkeyword = str_replace(' ', ',', $keyword);
			$fids = explode(',', str_replace('\'', '', $searchstring[5]));
			if(count($fids) == 1 && in_array($_G['adminid'], array(1,2,3))) {
				$modfid = $fids[0];
				if($_G['adminid'] == 3 && !C::t('forum_moderator')->fetch_uid_by_fid_uid($modfid, $_G['uid'])) {
					$modfid = 0;
				}
			}
		}
		$threadlist = $posttables = array();
		foreach(C::t('forum_thread')->fetch_all_by_tid_fid_displayorder(explode(',',$index['ids']), null, 0, $orderby, $start_limit, $_G['tpp'], '>=', $ascdesc) as $thread) {
			$thread['subject'] = bat_highlight($thread['subject'], $keyword);
			$thread['realtid'] = $thread['isgroup'] == 1 ? $thread['closed'] : $thread['tid'];
			$threadlist[$thread['tid']] = procthread($thread, 'dt');
			$posttables[$thread['posttableid']][] = $thread['tid'];
		}
		if($threadlist) {
			foreach($posttables as $tableid => $tids) {
				foreach(C::t('forum_post')->fetch_all_by_tid($tableid, $tids, true, '', 0, 0, 1) as $post) {
					$threadlist[$post['tid']]['message'] = bat_highlight(messagecutstr($post['message'], 200), $keyword);
				}
			}

		}


		$pagetotal = 1;
		if($index['num'])
		{
			$pagetotal = ceil($index['num']/$_G['tpp']);
		}
		$result = array();
	//	$grouplist = lib_group_helper::grouplist($_G['gp_tagid'],'','', '', array(0, 10000), array(), 1);
		foreach($threadlist as $val)
		{
			$tmp = array();
			$tmp['tid'] = $val['tid'];
			$tmp['fid'] = $val['fid'];
			//$tmp['groupname'] = $grouplist[$val['fid']]['name'];
			$tmp['groupname'] = '';
			$tmp['author'] = $val['author'];
			$tmp['authorid'] = $val['authorid'];
			$tmp['subject'] = $val['subject'];
			$tmp['dateline'] = BfdApp::bfd_html_entity_decode($val['dateline'],1);
      		$tmp['lastpost'] = BfdApp::bfd_html_entity_decode($val['lastpost'],1);
			$tmp['lastposter'] = $val['lastposter'];
			$tmp['views'] = $val['views'];
			$tmp['replies'] = $val['replies'];
			$tmp['sharetimes'] = $val['sharetimes'];
			$tmp['message'] = str_replace(array("\r","\n"),'',$val['message']);
			$result[] = $tmp;
		}
		
/*		if(empty($result))
		{
			BfdApp::display_result('result_is_null');
		}
		BfdApp::display_result('get_success',$result,'',$pagetotal);
*/
	}
	if(empty($result))
	{
		$result = array();
	}
	BfdApp::display_result('get_success',$result,'',$pagetotal);


?>
