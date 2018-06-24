<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: search_blog.php 29236 2012-03-30 05:34:47Z chenmengshu $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
define('NOROBOT', TRUE);

require_once libfile('function/home');

if(!$_G['setting']['search']['blog']['status']) {
	BfdApp::display_result('search_blog_closed');
}

if($_G['adminid'] != 1 && !($_G['group']['allowsearch'] & 4)) {
	BfdApp::display_result('group_nopermission');
}

$_G['setting']['search']['blog']['searchctrl'] = intval($_G['setting']['search']['blog']['searchctrl']);

$srchmod = 3;

$cachelife_time = 300;		// Life span for cache of searching in specified range of time
$cachelife_text = 3600;		// Life span for cache of text searching

$srchtype = empty($_GET['srchtype']) ? '' : trim($_GET['srchtype']);
$searchid = isset($_GET['searchid']) ? intval($_GET['searchid']) : 0;


$srchtxt = $_GET['srchtxt'];

$keyword = isset($srchtxt) ? dhtmlspecialchars(trim($srchtxt)) : '';


$orderby = in_array($_GET['orderby'], array('dateline', 'replies', 'views')) ? $_GET['orderby'] : 'lastpost';
$ascdesc = isset($_GET['ascdesc']) && $_GET['ascdesc'] == 'asc' ? 'asc' : 'desc';


//////////////////////////////
$searchstring = 'blog|title|'.addslashes($srchtxt);
$searchindex = array('id' => 0, 'dateline' => '0');

foreach(C::t('common_searchindex')->fetch_all_search($_G['setting']['search']['blog']['searchctrl'], $_G['clientip'], $_G['uid'], $_G['timestamp'], $searchstring, $srchmod) as $index) {
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

	if(!$srchtxt && !$srchuid && !$srchuname) {
		BfdApp::display_result('search_error');
	}

	if($_G['adminid'] != '1' && $_G['setting']['search']['blog']['maxspm']) {
		if(C::t('common_searchindex')->count_by_dateline($_G['timestamp'], $srchmod) >= $_G['setting']['search']['blog']['maxspm']) {
			BfdApp::display_result('search_toomany');
		}
	}

	$num = $ids = 0;
	$_G['setting']['search']['blog']['maxsearchresults'] = $_G['setting']['search']['blog']['maxsearchresults'] ? intval($_G['setting']['search']['blog']['maxsearchresults']) : 500;
	list($srchtxt, $srchtxtsql) = searchkey($keyword, "subject LIKE '%{text}%'", true);
	$query = C::t('home_blog')->fetch_blogid_by_subject($keyword, $_G['setting']['search']['blog']['maxsearchresults']);
	foreach($query as $blog) {
		$ids .= ','.$blog['blogid'];
		$num++;
	}
	unset($query);

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
		'num' => $num,
		'ids' => $ids
	), true);

	!($_G['group']['exempt'] & 2) && updatecreditbyaction('search');
}


///////////

	if(!empty($searchid)) {

		$page = max(1, intval($_GET['page']));
		$start_limit = ($page - 1) * $_G['tpp'];

		$index = C::t('common_searchindex')->fetch_by_searchid_srchmod($searchid, $srchmod);
		if(!$index) {
			BfdApp::display_result('search_id_invalid');
		}

		$keyword = dhtmlspecialchars($index['keywords']);
		$keyword = $keyword != '' ? str_replace('+', ' ', $keyword) : '';

		$index['keywords'] = rawurlencode($index['keywords']);
		$bloglist = array();
		$pricount = 0;
		$blogidarray = explode(',', $index['ids']);
		$data_blog = C::t('home_blog')->fetch_all($blogidarray, 'dateline', 'DESC', $start_limit, $_G['tpp']);
		$data_blogfield = C::t('home_blogfield')->fetch_all($blogidarray);

		foreach($data_blog as $curblogid => $value) {
			$result = array_merge($result, (array)$data_blogfield[$curblogid]);
			if(ckfriend($value['uid'], $value['friend'], $value['target_ids']) && ($value['status'] == 0 || $value['uid'] == $_G['uid'] || $_G['adminid'] == 1)) {
				if($value['friend'] == 4) {
					$value['message'] = $value['pic'] = '';
				} else {
					$value['message'] = bat_highlight($value['message'], $keyword);
					$value['message'] = getstr($value['message'], 255, 0, 0, 0, -1);
				}
				$value['subject'] = bat_highlight($value['subject'], $keyword);
				$value['dateline'] = dgmdate($value['dateline']);
				$value['pic'] = pic_cover_get($value['pic'], $value['picflag']);
				$bloglist[] = $value;
			} else {
				$pricount++;
			}
		}

		$pagetotal = 1;
		if($index['num'])
		{
			$pagetotal = ceil($index['num']/$_G['tpp']);
		}
		$result = array();
		foreach($bloglist as $val)
		{
			$tmp = array();
			$tmp['blogid'] = $val['blogid'];
			$tmp['uid'] = $val['uid'];
			$tmp['username'] = $val['username'];
			$tmp['subject'] = $val['subject'];
			$tmp['viewnum'] = $val['viewnum'];
			$tmp['replynum'] = $val['replynum'];
			$tmp['sharetimes'] = $val['sharetimes'];
			$result[] = $tmp;
		}
		BfdApp::display_result('get_success',$result,'',$pagetotal);
	}

BfdApp::display_result('get_success',array(),'',1);
?>
