<?php
require 'inc.php';
require libfile('function/member');
require libfile('function/misc');
require 'lib/class_member.php';
if($_G['setting']['version'] < 'X3.1')
{
    require libfile('function/seccode');
}
runhooks();

define('NOROBOT', TRUE);
$auth = getglobal('auth','cookie');
if(!empty($auth))
{
	dsetcookie('auth','',-1);
	header("Location: register.php");
	exit;
}
$ctl_obj = new register_ctl();
$ctl_obj->setting = $_G['setting'];
$ctl_obj->on_register();

header("Content-Type: text/html; charset=utf-8");
