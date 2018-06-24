<?php
/**
 * @filename : bfd_app_index.php
 * @date : 2013-03-05
 * @desc :
	公告列表
 **/
if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}
$bid = BFD_APP_INDEX_BLOCK_ID;
$bid2 = BFD_APP_INDEX_BLOCK_ID_2;
$limit_num = BFD_APP_INDEX_BLOCK_ITEM_NUM;
$time = time();
include_once libfile('function/block');
$_G['block'][$bid] = C::t('common_block')->fetch($bid);
block_updatecache($bid,true);
$_G['block'][$bid2] = C::t('common_block')->fetch($bid2);
block_updatecache($bid2,true);

$lastcode = trim($_GET['lastcode']);
//block2
$block_list = DB::fetch_all("SELECT * FROM ".DB::table('common_block_item')." WHERE `bid`={$bid} AND `startdate`<{$time} AND (`enddate`=0 OR `enddate`>{$time}) ORDER BY displayorder LIMIT {$limit_num}");

$result = array();
$blocklist = array();
if(is_array($block_list) && !empty($block_list))
{
	foreach($block_list as $val)
	{
		$tmp = array();
		$tmp['itemid'] = $val['itemid'];
		$tmp['title']  = html_entity_decode($val['title'], ENT_COMPAT | ENT_HTML401,BFD_APP_CHARSET);//$val['title'];
		$tmp['url']  = $val['url'];
//		$tmp['tid'] = $val['itemid'];
		$tmp['itemid'] = app_gettid_fromurl($tmp['url']);
		if(!$tmp['itemid'])
		{
			continue;
		}
		$tmp['summary']  = trim($val['summary']);
		
		$source = $val['pic'];
		if(BFD_APP_PIC_PATH_DIY)
		{
			$tmp['pic']  = BFD_APP_PIC_PATH_DIY.$val['pic'];
			$tmp['thumbpath']  = BFD_APP_PIC_PATH_DIY.$val['thumbpath'];
			$source = $_G['setting']['ftp']['attachurl'].$source;
		}
		else
		{
			$tmp['pic']  = 'http://'.$_SERVER['HTTP_HOST'].BFD_APP_PIC_PATH.$val['pic'];
			$tmp['thumbpath']  = 'http://'.$_SERVER['HTTP_HOST'].BFD_APP_PIC_PATH.$val['thumbpath'];
			$source = $_G['setting']['attachdir'].$source;
		}
		$thumb  = BfdApp::bfd_app_get_thumb_image($source,600,400);
		if($thumb)
		{
			$tmp['thumbpath'] = BFD_APP_THUMB_IMAGE_PATH_URL.$thumb;
		}
		else
		{
			$tmp['thumbpath'] = $tmp['pic'];
		}
		$tmp['displayorder']  = $val['displayorder'];
//		$tmp['startdate']  = date('Y-m-d H:i:s',$val['startdate']);
//		$tmp['enddate']  = date('Y-m-d H:i:s',$val['enddate']);
		$blocklist[] = $tmp;
	}
}
$result['blocklist'] = $blocklist;
//block3
$block_list = DB::fetch_all("SELECT * FROM ".DB::table('common_block_item')." WHERE `bid`={$bid2} AND `startdate`<{$time} AND (`enddate`=0 OR `enddate`>{$time}) ORDER BY displayorder LIMIT {$limit_num}");

$blocklist = array();
if(is_array($block_list) && !empty($block_list))
{
	foreach($block_list as $val)
	{
		$tmp = array();
		$tmp['itemid'] = $val['itemid'];
		$tmp['title']  = html_entity_decode($val['title'], ENT_COMPAT | ENT_HTML401,BFD_APP_CHARSET);//$val['title'];
		$tmp['url']  = $val['url'];
		$tmp['itemid'] = app_gettid_fromurl($tmp['url']);
		if(!$tmp['itemid'])
		{
			continue;
		}
		
//		$tmp['tid'] = $val['itemid'];
		$tmp['summary']  = trim($val['summary']);
		$source = $val['pic'];
		if(BFD_APP_PIC_PATH_DIY)
		{
			$tmp['pic']  = BFD_APP_PIC_PATH_DIY.$val['pic'];
			$tmp['thumbpath']  = BFD_APP_PIC_PATH_DIY.$val['thumbpath'];
			$source = $_G['setting']['ftp']['attachurl'].$source;
		}
		else
		{
			$tmp['pic']  = 'http://'.$_SERVER['HTTP_HOST'].BFD_APP_PIC_PATH.$val['pic'];
			$tmp['thumbpath']  = 'http://'.$_SERVER['HTTP_HOST'].BFD_APP_PIC_PATH.$val['thumbpath'];
			$source = $_G['setting']['attachdir'].$source;
		}
		$thumb = BfdApp::bfd_app_get_thumb_image($tmp['pic'],160,120);
		if($thumb)
		{
			$tmp['thumbpath'] = BFD_APP_THUMB_IMAGE_PATH_URL.$thumb;
		}
		else
		{
			$tmp['thumbpath'] = $tmp['pic'];
		}

		$tmp['displayorder']  = $val['displayorder'];
		//$tmp['startdate']  = date('Y-m-d H:i:s',$val['startdate']);
		//$tmp['enddate']  = date('Y-m-d H:i:s',$val['enddate']);
		$fileds = unserialize($val['fields']);
		if(!empty($fileds))
		{
			$tmp['author'] = $fileds['author'];
			$tmp['authorid'] = $fileds['authorid'];
			$tmp['dateline'] = date('Y-m-d H:i',$fileds['dateline']);
			$tmp['replies'] = $fileds['replies'];
		}
		else
		{
			$tmp['author'] = '';
			$tmp['authorid'] = '';
			$tmp['dateline'] = '0';
			$tmp['replies'] = '0';
		}
		$blocklist[] = $tmp;
	}
}

$page = intval($_GET['page']);
if($page < 1)
{
	$page = 1;
}
$perpage = BFD_INDEX_HOT_THREAD_PAGENUM;
$pagetotal = ceil(count($blocklist)/$perpage);
$start = ($page - 1) * $perpage;

$result['threadlist'] = array_slice($blocklist,$start,$perpage);

BfdApp::display_result('get_success',$result,'',$pagetotal);

//"http://www.ixian.cn/thread-664431-1-1.html
//forum.php?mod=viewthread&tid=664635
function app_gettid_fromurl($url)
{
	if(empty($url))
	{
		return false;
	}
	$pattern = '/tid=([0-9]+)/';
	$result = array();
	$flag = preg_match($pattern,$url,$result);
	if($flag)
	{
		return $result[1];
	}
	$pattern = '/thread-([0-9]+)-/';
	$result = array();
	$flag = preg_match($pattern,$url,$result);
	if($flag)
	{
		return $result[1];
	}
	return false;
}


