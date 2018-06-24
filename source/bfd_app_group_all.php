<?php
/*COMMENTS
//全体小组页面,使用groupindex缓存，基本逻辑来自group index 20121125 ep
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

//require_once libfile('lib/group_params');
//require_once libfile('lib/group_helper');
//require_once libfile('function/group');

$gid = TOP_GROUP_ID;
$perpage = BFD_APP_GROUP_ALL_PAGESIZE;
$pagetotal = 0;

$pagesize = intval(getgpc('pagesize'));
if($pagesize > 0)
{
	$perpage = $pagesize;
}
$sgid = intval(getgpc('sgid'));
$groupids = array();
$groupnav = $typelist = '';
$selectorder = array('default' => '', 'thread' => '', 'membernum' => '', 'dateline' => '', 'activity' => '');
if(!empty($_GET['orderby'])) {
	$selectorder[$_GET['orderby']] = 'selected';
} else {
	$selectorder['default'] = 'selected';
}

$keywords = '';
$gp_tagid = '';
if($_G['gp_kw']) 
{
	$keywords = $_G['gp_kw'];
}

if($_G['gp_tagid']) 
{
	$gp_tagid = $_G['gp_tagid'];
}


$groupids = array();
$list = array();
//$list = lib_group_helper::grouplist($gp_tagid,$keywords,$orderby, '', array($start, $perpage), $groupids, 1);
$list = C::t('forum_forum')->fetch_all_forum();
//$list2 = C::t('forum_forum')->fetch_all_fids();
//var_dump($list2);
//exit;

//var_dump($list);

$result = array();
foreach($list as $fid=>$value)
{
	if($value['type'] != 'forum')
	{
		continue;
	}
	$result[$fid]['fid'] = $value['fid'];	
	$result[$fid]['name'] = $value['name'];	
	//$result[$fid]['membernum'] = $value['membernum'];	
	$result[$fid]['description'] = $value['description'];	
	$result[$fid]['icon'] = BFD_APP_DATA_URL_PRE.getglobal('setting/attachurl').'common/'.$value['icon'];	
	$result[$fid]['founderuid'] = $value['founderuid'];//创建者id	
	$result[$fid]['foundername'] = $value['foundername'];//创建者名称	
	$result[$fid]['threads'] = $value['threads'];//主题数	
	$result[$fid]['posts'] = $value['posts'];//帖子数	
	$result[$fid]['todayposts'] = $value['todayposts'];//今日帖子数	
	//$result[$fid]['dateline'] = $value['dateline'];//创建日期	
	$result[$fid]['lastupdate'] = $value['lastupdate'];//最后更新时间	
}

BfdApp::display_result('get_success',$result,'',$pagetotal);
?>
