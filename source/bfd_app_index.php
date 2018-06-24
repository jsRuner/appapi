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
$limit_num = BFD_APP_INDEX_BLOCK_ITEM_NUM;
$time = time();

$lastcode = trim($_GET['lastcode']);

$block_list = DB::fetch_all("SELECT * FROM ".DB::table('common_block_item')." WHERE `bid`={$bid} AND `startdate`<{$time} AND (`enddate`=0 OR `enddate`>{$time}) ORDER BY displayorder LIMIT {$limit_num}");
$result = array();
if(is_array($block_list) && !empty($block_list))
{
	foreach($block_list as $val)
	{
		$tmp = array();
		$tmp['itemid'] = $val['itemid'];
		$tmp['title']  = $val['title'];
		$tmp['url']  = $val['url'];
		$pos =strpos($tmp['url'],'tid=');
		$tmp['itemid'] = substr($tmp['url'],$pos+4);
		$tmp['summary']  = $val['summary'];
		
		$tmp['pic']  = 'http://'.$_SERVER['HTTP_HOST'].BFD_APP_PIC_PATH.$val['pic'];
		$tmp['thumbpath']  = 'http://'.$_SERVER['HTTP_HOST'].BFD_APP_PIC_PATH.$val['thumbpath'];
		$tmp['displayorder']  = $val['displayorder'];
		$tmp['startdate']  = date('Y-m-d H:i:s',$val['startdate']);
		$tmp['enddate']  = date('Y-m-d H:i:s',$val['enddate']);
		$result[] = $tmp;
	}
}

$newcode = substr(md5(serialize($result)),0,10);
if(!empty($lastcode))
{
	if($lastcode == $newcode)
	{
		BfdApp::display_result('no_new_items',array(),$lastcode);
	}
}
BfdApp::display_result('get_success',$result,$newcode);

