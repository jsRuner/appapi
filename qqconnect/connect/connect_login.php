<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: connect_login.php 33177 2013-05-06 02:43:31Z theoliu $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$op = !empty($_GET['op']) ? $_GET['op'] : '';
if(!in_array($op, array('init', 'callback', 'change'))) {
	BfdApp::display_result('undefined_action');
	//showmessage('undefined_action');
}
$referer = dreferer();

try {
	$connectOAuthClient = Cloud::loadClass('Service_Client_ConnectOAuth');
} catch(Exception $e) {
	BfdApp::display_result('qqconnect:connect_app_invalid');
//	showmessage('qqconnect:connect_app_invalid');
}
if($op == 'init') {
	dsetcookie('con_request_token');
	dsetcookie('con_request_token_secret');

	try {
		$callback = $_G['connect']['callback_url'] . '&referer=' . urlencode($_GET['referer']) . (!empty($_GET['isqqshow']) ? '&isqqshow=yes' : '');
		$response = $connectOAuthClient->connectGetRequestToken($callback);
	} catch(Exception $e) {
		BfdApp::display_result('qqconnect:connect_get_request_token_failed_code');
	//	showmessage('qqconnect:connect_get_request_token_failed_code', $referer, array('codeMessage' => getErrorMessage($e->getmessage()), 'code' => $e->getmessage()));
	}

	$request_token = $response['oauth_token'];
	$request_token_secret = $response['oauth_token_secret'];

	dsetcookie('con_request_token', $request_token);
	dsetcookie('con_request_token_secret', $request_token_secret);

	$redirect = $connectOAuthClient->getOAuthAuthorizeURL($request_token);

	$redirect .= '&oauth_style=mobile';

	dheader('Location:' . $redirect);

} elseif($op == 'callback') {

	$params = $_GET;

	if(!isset($params['receive'])) {
		/*$utilService = Cloud::loadClass('Service_Util');
		echo '<script type="text/javascript">setTimeout("window.location.href=\'connect.php?receive=yes&'.str_replace("'", "\'", $utilService->httpBuildQuery($_GET, '', '&')).'\'", 1)</script>';
		exit;
		*/
		$params['receive'] = 'yes';
	}

	try {
		$response = $connectOAuthClient->connectGetAccessToken($params, $_G['cookie']['con_request_token_secret']);
	} catch(Exception $e) {
		BfdApp::display_result('qqconnect:connect_get_access_token_failed_code'.$e->getmessage());
		//showmessage('qqconnect:connect_get_access_token_failed_code', $referer, array('codeMessage' => getErrorMessage($e->getmessage()), 'code' => $e->getmessage()));
	}


	$conuin = $response['oauth_token'];
	$conuinsecret = $response['oauth_token_secret'];
	$conopenid = strtoupper($response['openid']);
	if(!$conuin || !$conuinsecret || !$conopenid) {
		BfdApp::display_result('qqconnect:connect_get_access_token_failed_code'.$e->getmessage());
		//showmessage('qqconnect:connect_get_access_token_failed_code', $referer, array('codeMessage' => getErrorMessage($e->getmessage()), 'code' => $e->getmessage()));
	}

	loadcache('connect_blacklist');
	if(in_array($conopenid, array_map('strtoupper', $_G['cache']['connect_blacklist']))) {
		$change_qq_url = $_G['connect']['discuz_change_qq_url'];
		BfdApp::display_result('qqconnect:connect_uin_in_blacklist');
		//showmessage('qqconnect:connect_uin_in_blacklist', $referer, array('changeqqurl' => $change_qq_url));
	}

	$referer = $referer && (strpos($referer, 'logging') === false) && (strpos($referer, 'mod=login') === false) ? $referer : 'index.php';

	if($params['uin']) {
		$old_conuin = $params['uin'];
	}

	$is_notify = true;

	$conispublishfeed = 0;
	$conispublisht = 0;

	$is_user_info = 1;
	$is_feed = 1;

	$user_auth_fields = 1;


	$connect_member = array();
	$fields = array('uid', 'conuin', 'conuinsecret', 'conopenid');
	if($old_conuin) {
		$connect_member = C::t('#qqconnect#common_member_connect')->fetch_fields_by_openid($old_conuin, $fields);
	}
	if(empty($connect_member)) {
		$connect_member = C::t('#qqconnect#common_member_connect')->fetch_fields_by_openid($conopenid, $fields);
	}
	if($connect_member) {
		$member = getuserbyuid($connect_member['uid']);
		if($member) {
			if(!$member['conisbind']) {
				C::t('#qqconnect#common_member_connect')->delete($connect_member['uid']);
				unset($connect_member);
			} else {
				$connect_member['conisbind'] = $member['conisbind'];
			}
		} else {
			C::t('#qqconnect#common_member_connect')->delete($connect_member['uid']);
			unset($connect_member);
		}
	}

	//

		if($connect_member) { // debug 此分支是用户直接点击QQ登录，并且这个QQ号已经绑好一个论坛账号了，将直接登进论坛了
			C::t('#qqconnect#common_member_connect')->update($connect_member['uid'],
				array(
					'conuin' => $conuin,
					'conuinsecret' => $conuinsecret,
					'conopenid' => $conopenid,
					'conisfeed' => 1,
				)
			);

			$params['mod'] = 'login';
			connect_login($connect_member);

			loadcache('usergroups');
			$usergroups = $_G['cache']['usergroups'][$_G['groupid']]['grouptitle'];
			$param = array('username' => $_G['member']['username'], 'usergroup' => $_G['group']['grouptitle']);

			C::t('common_member_status')->update($connect_member['uid'], array('lastip'=>$_G['clientip'], 'lastvisit'=>TIMESTAMP, 'lastactivity' => TIMESTAMP));
			
			$userinfo = lib_bfd_user::get_user_info($connect_member['uid']);
			$html = '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body style="display:none;">
%s
</body>
</html>';
			if(false == $userinfo)
			{
				BfdApp::display_result('user_login_failed',null,'','',$html);
			}
			BfdApp::display_result('user_login_successed',$userinfo,'','',$html);

		} else { // debug 此分支是用户直接点击QQ登录，并且这个QQ号还未绑定任何论坛账号，将将跳转到一个新页引导用户注册个新论坛账号或绑一个已有的论坛账号

			$auth_hash = authcode($conopenid, 'ENCODE');
			$insert_arr = array(
				'conuin' => $conuin,
				'conuinsecret' => $conuinsecret,
				'conopenid' => $conopenid,
			);

			$connectGuest = C::t('#qqconnect#common_connect_guest')->fetch($conopenid);
			if ($connectGuest['conqqnick']) {
				$insert_arr['conqqnick'] = $connectGuest['conqqnick'];
			} else {
				try {
					$connectOAuthClient = Cloud::loadClass('Service_Client_ConnectOAuth');
					$connectUserInfo = $connectOAuthClient->connectGetUserInfo($conopenid, $conuin, $conuinsecret);
					if ($connectUserInfo['nickname']) {
						$connectUserInfo['nickname'] = strip_tags($connectUserInfo['nickname']);
						$insert_arr['conqqnick'] = $connectUserInfo['nickname'];
					}
				} catch(Exception $e) {
				}
			}

			C::t('#qqconnect#common_connect_guest')->insert($insert_arr, false, true);
			$html = '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body style="display:none;">
%s
</body>
</html>';
			BfdApp::display_result('unbinded_user',null,'','',$html);
		}

} elseif($op == 'change') {
	dsetcookie('con_request_token');
	dsetcookie('con_request_token_secret');

	$callback = $_G['connect']['callback_url'] . '&referer=' . urlencode($_GET['referer']);
	try {
		$response = $connectOAuthClient->connectGetRequestToken($callback);
	} catch(Exception $e) {
	//	showmessage('qqconnect:connect_get_request_token_failed_code', $referer, array('codeMessage' => getErrorMessage($e->getmessage()), 'code' => $e->getmessage()));
		BfdApp::display_result('qqconnect:connect_get_request_token_failed_code');
	}

	$request_token = $response['oauth_token'];
	$request_token_secret = $response['oauth_token_secret'];

	dsetcookie('con_request_token', $request_token);
	dsetcookie('con_request_token_secret', $request_token_secret);

	$redirect = $connectOAuthClient->getOAuthAuthorizeURL($request_token);

		$redirect .= '&oauth_style=mobile';

	dheader('Location:' . $redirect);
}

function connect_login($connect_member) {
	global $_G;

	if(!($member = getuserbyuid($connect_member['uid'], 1))) {
		return false;
	} else {
		if(isset($member['_inarchive'])) {
			C::t('common_member_archive')->move_to_master($member['uid']);
		}
	}

	require_once libfile('function/member');
	$cookietime = 1296000;
	setloginstatus($member, $cookietime);

	dsetcookie('connect_login', 1, $cookietime);
	dsetcookie('connect_is_bind', '1', 31536000);
	dsetcookie('connect_uin', $connect_member['conopenid'], 31536000);
	return true;
}

function getErrorMessage($errroCode) {
	$str = sprintf('connect_error_code_%d', $errroCode);

	return lang('plugin/qqconnect', $str);
}
