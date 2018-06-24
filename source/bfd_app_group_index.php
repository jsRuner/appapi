<?php
/**
 * @filename : bfd_app_group_index.php
 * @date : 2013-03-05
 * @desc :
	用户小组动态列表
 **/
if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

require_once libfile('lib/group_helper');
require_once libfile('function/group');

$page = intval($_GET['page']);
$persize = BFD_APP_GROUP_ACTIVE_PAGESIZE;
$pergroup = BFD_APP_GROUP_ACTIVE_PER_GROUP;
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
$start = ($page - 1) * $persize;

$myfids = C::t('forum_group_follow')->get_all_group_by_uid($_G['uid']);

if(!$myfids) 
{
	$myfids = array(-1);
}
else
{
	$myfids_count = count($myfids);
	$pagetotal = ceil($myfids_count/$persize);
}
$attentiongroups = lib_group_helper::grouplist($tag_id=null,$keyword=null,$orderby = 'lastupdate', array(),array($start,$persize), $myfids, $sort = 0, $getcount = 0, $grouplevel = array(),$have_threads=true);
//我加入或管理的小组的info
$attentiongroups_fids = array_keys($attentiongroups);
//取最新3个帖子
$fid_threads = lib_group_helper::fetch_thread_by_fids($attentiongroups_fids,$pergroup);

$result = array();
foreach($attentiongroups as $fid=>$val)
{
	$result[$fid]['fid'] = $val['fid'];
	$result[$fid]['icon'] = BFD_APP_DATA_URL_PRE.getglobal('setting/attachurl').'common/'.$val['icon'];
	$result[$fid]['name'] = $val['name'];
	$result[$fid]['membernum'] = $val['membernum'];
	$result[$fid]['threads'] = $fid_threads[$fid];
}

BfdApp::display_result('get_success',$result,'',$pagetotal);

