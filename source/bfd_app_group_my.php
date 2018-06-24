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

require_once libfile('lib/group_helper');
require_once libfile('function/group');

$page = intval($_GET['page']);
$pagesize = intval($_GET['pagesize']);
$persize = BFD_APP_GROUP_MY_PAGESIZE;
$pagetotal = 0;
if($pagesize > 0)
{
	$persize = $pagesize;
}
if($page < 1)
{
	$page = 1;
}
$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
if(!$uid)
{
	$uid = $_G['uid'];
}
$start = ($page - 1) * $persize;
$ismanager = 0;
$num = mygrouplist($uid, 'lastupdate', array('f.name', 'ff.icon','ff.membernum'), 0, 0, $ismanager, 1);
$grouplist = mygrouplist($uid, 'lastupdate', array('f.name','ff.dateline', 'ff.icon','ff.banner','ff.membernum'), $persize, $start, $ismanager);
$pagetotal = ceil($num/$persize);
foreach($grouplist as $fid=>$val)
{
	$grouplist[$fid]['icon'] = BFD_APP_DATA_URL_PRE.$val['icon'];
	$grouplist[$fid]['banner'] = BFD_APP_DATA_URL_PRE.$val['banner'];
}

BfdApp::display_result('get_success',$grouplist,'',$pagetotal);

