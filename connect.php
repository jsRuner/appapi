<?php
/*
   [Discuz!] (C)2001-2009 Comsenz Inc.
   This is NOT a freeware, use is subject to license terms

   $Id: connect.php 26424 2011-12-13 03:02:20Z zhouxiaobo $
*/


require './inc.php';
global $_app_debug;
$mod = trim($_GET['mod']);

if(!in_array($mod, array('config', 'login', 'feed', 'check', 'user'))) {
	BfdApp::display_result('undefined_action');
}

if(!$_G['setting']['connect']['allow']) {
	BfdApp::display_result('qqconnect:qqconnect_closed');
	//showmessage('qqconnect:qqconnect_closed');
}

define('CURMODULE', $mod);
runhooks();

$connectService = Cloud::loadClass('Service_Connect');
require_once 'qqconnect/connect/connect_'.$mod.'.php';
?>
