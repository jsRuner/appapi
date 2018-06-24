<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: search.php 26313 2011-12-08 09:12:56Z yangli $
 */


$type = trim($_GET['type']);
//$typearray = array('my', 'user', 'curforum', 'newthread');
$typearray = array('user','forum');
/*
if(in_array($type, $typearray) || !empty($_G['setting']['search'][$type]['status'])) {
} else {
    foreach($_G['setting']['search'] as $mod => $value) {
        if(!empty($value['status'])) {
            break;
        }
    }
}
*/

if(empty($type)) {
	$type = 'forum';
}

require_once libfile('function/search');

if($type == 'curforum') {
	$type = 'forum';
	$_GET['srchfid'] = array($_GET['srhfid']);
} elseif($type == 'forum') {
	$_GET['srhfid'] = 0;
}
$srchtxt = urldecode( trim($_GET['srchtxt']) );
if( BFD_APP_CHARSET_OUTPUT != BFD_APP_CHARSET ){
    $srchtxt = iconv( BFD_APP_CHARSET_OUTPUT, BFD_APP_CHARSET."//IGNORE", $srchtxt);
}
if(empty($srchtxt))
{
	BfdApp::display_result('params_is_null');
}

require './source/search/search_'.$type.'.php';

?>
