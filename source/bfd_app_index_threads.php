<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum.php 31999 2012-10-30 07:19:49Z cnteacher $
 */


require_once libfile('function/forum'); 

loadforum();

$type_array = array('newpost','newreply');
$type = trim($_GET['type']);
if(empty($type) || !in_array($type,$type_array))
{
	$type = 'newthread';
}

/*
$grids = array();
if($_G['setting']['grid']['showgrid']) {
		loadcache('grids');
		$_G['setting']['grid']['fids'] = in_array(0, $_G['setting']['grid']['fids']) ? 0 : $_G['setting']['grid']['fids'];
		$grids['newthread'] = C::t('forum_thread')->fetch_all_for_guide('newthread', 0, array(), 0, 0, 0, 10, $_G['setting']['grid']['fids']);
		$grids['newreply'] = C::t('forum_thread')->fetch_all_for_guide('reply', 0, array(), 0, 0, 0, 10, $_G['setting']['grid']['fids']);
}
var_dump($grids);
*/
/*
if($_G['setting']['grid']['showgrid']) {
	loadcache('grids');
	$cachelife = $_G['setting']['grid']['cachelife'] ? $_G['setting']['grid']['cachelife'] : 600;
	$now = dgmdate(TIMESTAMP, lang('form/misc', 'y_m_d')).' '.lang('forum/misc', 'week_'.dgmdate(TIMESTAMP, 'w'));
	if(TIMESTAMP - $_G['cache']['grids']['cachetime'] < $cachelife) {
		$grids = $_G['cache']['grids'];
	} else {
		$images = array();
		$_G['setting']['grid']['fids'] = in_array(0, $_G['setting']['grid']['fids']) ? 0 : $_G['setting']['grid']['fids'];

		if($_G['setting']['grid']['gridtype']) {
			$grids['digest'] = C::t('forum_thread')->fetch_all_for_guide('digest', 0, array(), 3, 0, 0, 10, $_G['setting']['grid']['fids']);
		} else {
			$images = C::t('forum_threadimage')->fetch_all_order_by_tid(10);
			foreach($images as $key => $value) {
				$tids[$value['tid']] = $value['tid'];
			}
			$grids['image'] = C::t('forum_thread')->fetch_all_by_tid($tids);
		}
		$grids['newthread'] = C::t('forum_thread')->fetch_all_for_guide('newthread', 0, array(), 0, 0, 0, 10, $_G['setting']['grid']['fids']);

		$grids['newreply'] = C::t('forum_thread')->fetch_all_for_guide('reply', 0, array(), 0, 0, 0, 10, $_G['setting']['grid']['fids']);
		$grids['hot'] = C::t('forum_thread')->fetch_all_for_guide('hot', 0, array(), 3, 0, 0, 10, $_G['setting']['grid']['fids']);

		$_G['forum_colorarray'] = array('', '#EE1B2E', '#EE5023', '#996600', '#3C9D40', '#2897C5', '#2B65B7', '#8F2A90', '#EC1282');
		foreach($grids as $type => $gridthreads) {
			foreach($gridthreads as $key => $gridthread) {
				$gridthread['dateline'] = str_replace('"', '\'', dgmdate($gridthread['dateline'], 'u', '9999', getglobal('setting/dateformat')));
				$gridthread['lastpost'] = str_replace('"', '\'', dgmdate($gridthread['lastpost'], 'u', '9999', getglobal('setting/dateformat')));
				if($gridthread['highlight'] && $_G['setting']['grid']['highlight']) {
					$gridthread['highlight'] .= '1';
				} else {
					$gridthread['highlight'] = '0';
				}
				if($_G['setting']['grid']['textleng']) {
					$gridthread['oldsubject'] = dhtmlspecialchars($gridthread['subject']);
					$gridthread['subject'] = cutstr($gridthread['subject'], $_G['setting']['grid']['textleng']);
				}

				$grids[$type][$key] = $gridthread;
			}
		}
		if(!$_G['setting']['grid']['gridtype']) {

			$focuspic = $focusurl = $focustext = array();
			$grids['focus'] = 'config=5|0xffffff|0x0099ff|50|0xffffff|0x0099ff|0x000000';
			foreach($grids['image'] as $ithread) {
				if($ithread['displayorder'] < 0) {
					continue;
				}
				if($images[$ithread['tid']]['remote']) {
					$imageurl = $_G['setting']['ftp']['attachurl'].'forum/'.$images[$ithread['tid']]['attachment'];
				} else {
					$imageurl = $_G['setting']['attachurl'].'forum/'.$images[$ithread['tid']]['attachment'];
				}
				$grids['slide'][$ithread['tid']] = array(
						'image' => $imageurl,
						'url' => 'forum.php?mod=viewthread&tid='.$ithread['tid'],
						'subject' => $ithread['subject']
					);
			}
		}
		$grids['cachetime'] = TIMESTAMP;
		savecache('grids', $grids);
	}
}
*/
$_G['setting']['grid']['fids'] = 0;
$grids = array();
/*
$grids['newthread'] = C::t('forum_thread')->fetch_all_for_guide('newthread', 0, array(), 0, 0, 0, 10, $_G['setting']['grid']['fids']);

$grids['newreply'] = C::t('forum_thread')->fetch_all_for_guide('reply', 0, array(), 0, 0, 0, 10, $_G['setting']['grid']['fids']);
*/
$page = max(1,$_GET['page']);
$pagesize = 10;
$start = ($page - 1) * $pagesize;
$grids['newthread'] = dz_fetch_all_for_guide('newthread', 0 , 100);

$grids['newreply'] = dz_fetch_all_for_guide('reply', 0, 100);
$result = array();

$pagetotal = ceil(count($grids[$type]) / $pagesize);

$tmpresult = array_slice($grids[$type],$start,$pagesize);
if(is_array($tmpresult) && !empty($tmpresult))
{
	foreach($tmpresult as $val)
	{
		$tmp = array();
		$tmp['tid'] = $val['tid'];
		$tmp['fid'] = $val['fid'];
		$tmp['author'] = $val['author'];
		$tmp['authorid'] = $val['authorid'];
		$tmp['subject'] = html_entity_decode($val['subject'], ENT_COMPAT | ENT_HTML401,BFD_APP_CHARSET);
		$val['dateline'] = str_replace('"', '\'', dgmdate($val['dateline'], 'u', '9999', getglobal('setting/dateformat')));
		$tmp['dateline'] = str_replace('&nbsp;',' ',strip_tags($val['dateline']));
		$val['lastpost'] = str_replace('"', '\'', dgmdate($val['lastpost'], 'u', '9999', getglobal('setting/dateformat')));
		$tmp['lastpost'] = str_replace('&nbsp;',' ',strip_tags($val['lastpost']));
		// $tmp['lastpost'] = html_entity_decode(strip_tags($thread['lastpost']), ENT_COMPAT | ENT_HTML401,BFD_APP_CHARSET);
		$tmp['lastposter'] = $val['lastposter'];
		$tmp['views'] = $val['views'];
		$tmp['replies'] = $val['replies'];
		$tmp['displayorder'] = $val['displayorder'];
		$tmp['typeid'] = $val['typeid'];
		$tmp['digest'] = $val['digest'];
		$tmp['ispicture'] = $val['attachment'] == 2 ? 1:0;
		$result[] = $tmp;
	}
}
BfdApp::display_result('get_success',$result,'',$pagetotal);

function dz_fetch_all_for_guide($type,$start,$limit)
{
    $orderby = '';
    $addsql  = '1';
    if($type == 'newthread') {
        $orderby = 'tid';
    } elseif($type == 'reply') {
        $orderby = 'lastpost';
        $addsql .= ' AND replies > 0';
    } else {
        $orderby = 'lastpost';
    }
    $addsql .= ' AND displayorder>=0 ORDER BY '.$orderby.' DESC LIMIT '. $start .', '.$limit;
    return DB::fetch_all("SELECT * FROM ".DB::table('forum_thread')." WHERE ".$addsql);
}
    
