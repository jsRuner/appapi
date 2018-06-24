<?php
//工具函数类

class BfdApp
{
	static function my_json_encode($phparr)
	{
		if(function_exists("json_encode"))
		{
			return json_encode($phparr);
		}
		else
		{
			require_once 'json.class.php';

			$json = new Services_JSON;
			return $json->encode($phparr);
		}
	}
	static function output_json($result=array(),$html='')
	{
		if(!empty($result))
		{
			if(empty($html))
            {
                header("Content-type: application/json");
                //echo json_encode($result);
			//	echo DzApp_json::encode($result);
				echo self::my_json_encode($result);
            }
            else if(strlen($html) > 1)
            {
                header("Content-type: application/json");
                //$output = sprintf($html,DzApp_json::encode($result));
                $output = sprintf($html,self::my_json_encode($result));
                echo $output;
            }
			else
			{
				$html = '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body style="display:none;">
%s
</body>
</html>';
                //$output = sprintf($html,DzApp_json::encode($result));
                $output = sprintf($html,self::my_json_encode($result));
                echo $output;
			}
			exit;
		}
		exit;
	}
	
	static function display_result($error,$data=array(),$lastcode='',$pagetotal=0,$html='')
	{
		global $_G;
		$result = array();
		$result['errornum'] = '';
		$result['errormsg'] = '';
		if(BFD_APP_DEBUG)
		{
			$result['error'] = $error;
		}
		if($lastcode)
		{
			$result['lastcode'] = $lastcode;
		}
		if($pagetotal)
		{
			if(is_array($pagetotal) && !empty($pagetotal))
			{
				$pagetotal = self::format_string($pagetotal);
				$result = array_merge($result,$pagetotal);
			}
			else
			{
				$result['pagetotal'] = intval($pagetotal);
			}
		}
		$result['data'] = array();

		if(isset($_G['bfd_app_errorcode'][$error]))
		{
			$result['errornum'] = $_G['bfd_app_errorcode'][$error];
		}
		else
		{
			$result['errornum'] = $_G['bfd_app_errorcode']['undefined_error'];
		}
		if(isset($_G['bfd_app_language'][$error]))
		{
			$result['errormsg'] = $_G['bfd_app_language'][$error];
		}
		else
		{
			$result['errormsg'] = $_G['bfd_app_language']['undefined_error'].$error;
		}
		if(!empty($data))
		{
			$data = self::format_string($data);
			$result['data'] = $data;
		}
		if($error == 'action_info')
		{
			$result['errormsg'] = $data;
			$result['data'] = array();
		}
//		$result = self::format_string($result);
		self::output_json($result,$html);
	}

	static function check_token($token)
	{
		if(empty($token))
		{
			return false;
		}	
		$authstr = authcode($token,'DECODE',BFD_APP_KEY,BFD_APP_KEY_EXPIRY);
		
		$result = array();
		list($result['password'],$result['uid'],$result['time']) = daddslashes(explode("\t",$authstr));
		return $result;
	}

	/**
 	 * 根据token 检查用户状态及权限控制 
    	 **/	
	static function check_user($token)
	{
		global $_G;
		$user = self::check_token($token);
		if(!is_array($user) || empty($user['uid']) || empty($user['password']) || empty($user['time']))
		{
			return 'token_error';
		}
		$uid = intval($user['uid']);
		$userinfo = DB::fetch_first("SELECT * FROM ".DB::table('common_member')." WHERE uid={$uid}");
		if(empty($userinfo) || $userinfo['password'] !== $user['password'])
		{
			return 'token_error';
		}
		if(($user['time'] - time()) > BFD_APP_KEY_EXPIRY)
		{
			return 'token_has_expired';
		}
		if($userinfo['status'] != 0)
		{
			return 'user_status_excption';
		}
		if($userinfo['groupid'] == 5)
		{
			return 'user_visit_been_banned';
		}
		$_G['uid'] = $uid;
		self::init_user();
		//$_G['username'] = $userinfo['username'];	
		//$_G['adminid'] = $userinfo['adminid'];	
		//$_G['groupid'] = $userinfo['groupid'];	

		return 'token_check_successed';
	}
	
	static function init_user() 
	{
		global $_G;
		$discuz_uid = $_G['uid'];

		if($discuz_uid) {
			$user = getuserbyuid($discuz_uid, 1);
			if(isset($user['_inarchive'])) {
				C::t('common_member_archive')->move_to_master($discuz_uid);
			}
			$_G['member'] = $user;

			if($user && $user['groupexpiry'] > 0 && $user['groupexpiry'] < TIMESTAMP) {
			'脱离了小组';
			}
		}
		else
		{
        	$username = '';
        	$groupid = 7;
        	setglobal('member', array( 'uid' => 0, 'username' => $username, 'adminid' => 0, 'groupid' => $groupid, 'credits' => 0, 'timeoffset' => 9999));
		//	loadcache(array('usergroup_7'));
		}

		$cachelist[] = 'usergroup_'.$_G['member']['groupid'];
		if($user && $user['adminid'] > 0 && $user['groupid'] != $user['adminid']) {
			$cachelist[] = 'admingroup_'.$_G['member']['adminid'];
		}

		setglobal('groupid', getglobal('groupid', 'member'));
		!empty($cachelist) && loadcache($cachelist);

		setglobal('uid', getglobal('uid', 'member'));
		setglobal('username', getglobal('username', 'member'));
		setglobal('adminid', getglobal('adminid', 'member'));
		setglobal('groupid', getglobal('groupid', 'member'));

		if($_G['member'] && $_G['group']['radminid'] == 0 && $_G['member']['adminid'] > 0 && $_G['member']['groupid'] != $_G['member']['adminid'] && !empty($_G['cache']['admingroup_'.$_G['member']['adminid']])) {
			$_G['group'] = array_merge($_G['group'], $_G['cache']['admingroup_'.$_G['member']['adminid']]);
		}
	}

	/**
	 * 图片压缩函数
	 * @param $source 源文件目录
	 * @param $width 目标图片 宽度
	 * @param $height 目标文件高度
	 * @param $quality 目标文件清晰度百分比  100 表示100%
	 * @param $border  是否加边框 1 是 0 否
	 * return  string 目标文件存储路径 or false
	 **/
	function bfd_app_get_thumb_image($source,$width,$height=200,$quality=75,$border=0,$type=2)
	{
		global $_G;
		$source = trim($source);
		$width  = intval($width);
		$height = intval($height);
		$quality  = intval($quality);
		$border = intval($border);
		$type = intval($type);
		if($type < 1)
		{
			$type = 1;
		}
		//参数检查
		if(empty($source) || $width < 1)
		{
			return false;
		}

		//源文件合法检查,必须问图片
		$pic_exts = array('jpg','jpeg','gif','png','attach');
		$pic_ext = substr($source,strrpos($source,'.')+1);
if($_GET['dd'] == 1)
{
var_dump(__FILE__.__LINE__);
var_dump($source);
var_dump(is_file($source));
};
		//if(!is_file($source) || !in_array($pic_ext,$pic_exts))
		if(!in_array($pic_ext,$pic_exts))
		{
			return false;
		}
		if($pic_ext == 'attach')
		{
			$pic_ext = 'jpg';
		}
		
		//目标文件检查	
		$md5str =  md5($source);
		$thumbpath = substr($md5str,0,2).'/'.substr($md5str,0,8).'/';
		$thumbname = $width.'x'.$height.'x'.$quality.'x'.$border.'.'.$pic_ext;
		
		$dist = $thumbpath.$thumbname;
		$distpath = BFD_APP_THUMB_IMAGE_PATH.$dist;
		if(file_exists($_G['setting']['attachdir'].$distpath))
		{
			return $distpath;
		}	
if($_GET['dd'] == 1)
{
var_dump($_G['setting']['attachdir']);
var_dump(__FILE__.__LINE__);
var_dump($source);
	var_dump($dist);
	var_dump($distpath);
	
}

		//生成目标文件
		require_once libfile('class/image');
		$image = new image();
		$result = $image->Thumb($source,$distpath,$width,$height,$type);		
		if($result)
		{
			return $distpath;
		}
		return false;
	}

	static function format_string($params)
    {
        if(is_array($params))
        {
            foreach($params as $key => $val)
            {
                $params[ $key ] = self::format_string($val);
            }
        }
        else
        {
			if( BFD_APP_CHARSET_OUTPUT != BFD_APP_CHARSET ){
				$params	= iconv( BFD_APP_CHARSET, BFD_APP_CHARSET_OUTPUT."//IGNORE", $params );
			}
            $params = is_null( $params) ? '' : $params;
        }
        return $params;
    }
	
	static function bfd_html_entity_decode($string,$replace=0)
	{
		if(!$replace)
		{
			$string = html_entity_decode($string, ENT_COMPAT | ENT_XHTML,BFD_APP_CHARSET_HTML_DECODE);
		}
		$replace_array = array('&rsaquo;','&nbsp;');
		$dest_array = array('',' ');
		$string = str_replace($replace_array,$dest_array,$string);
		return $string;
	}

	static function censor($message, $modword = NULL, $return = FALSE) {
		global $_G;
		$censor = discuz_censor::instance();
		$censor->check($message, $modword);
		if($censor->modbanned() && empty($_G['group']['ignorecensor'])) {
			$wordbanned = implode(', ', $censor->words_found);
			BfdApp::display_result('word_banned');
		}
		if($_G['group']['allowposturl'] == 0 || $_G['group']['allowposturl'] == 2) {
			$urllist = self::get_url_list($message);
			if(is_array($urllist[1])) foreach($urllist[1] as $key => $val) {
				if(!$val = trim($val)) continue;
				if(!iswhitelist($val)) {
					if($_G['group']['allowposturl'] == 0) {
						if($return) {
							return array('message' => 'post_url_nopermission');
						}
						BfdApp::display_result('post_url_nopermission');
					} elseif($_G['group']['allowposturl'] == 2) {
						$message = str_replace('[url]'.$urllist[0][$key].'[/url]', $urllist[0][$key], $message);
						$message = preg_replace(
							array(
								"@\[url=[^\]]*?".preg_quote($urllist[0][$key],'@')."[^\]]*?\](.*?)\[/url\]@is",
								"@href=('|\")".preg_quote($urllist[0][$key],'@')."\\1@is",
								"@\[url\]([^\]]*?".preg_quote($urllist[0][$key],'@')."[^\]]*?)\[/url\]@is",
							),
							array(
								'\\1',
								'',
								'\\1',
							),
							$message);
					}
				}
			}
		}
		return $message;
	}
	
	static function get_url_list($message) {
		$return = array();

		(strpos($message, '[/img]') || strpos($message, '[/flash]')) && $message = preg_replace("/\[img[^\]]*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]|\[flash[^\]]*\]\s*([^\[\<\r\n]+?)\s*\[\/flash\]/is", '', $message);
		if(preg_match_all("/((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.)[^ \[\]\"']+/i", $message, $urllist)) {
			foreach($urllist[0] as $key => $val) {
				$val = trim($val);
				$return[0][$key] = $val;
				if(!preg_match('/^http:\/\//is', $val)) $val = 'http://'.$val;
				$tmp = parse_url($val);
				$return[1][$key] = $tmp['host'];
				if($tmp['port']){
					$return[1][$key] .= ":$tmp[port]";
				}
			}
		}
		return $return;
	}

	static function curl_get_contents($url,$header=0)
    {
        $ch = curl_init();
        $timeout = 10;
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, $header);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    
        $contents = curl_exec($ch);
        return $contents;
    }
	static function get_user_agent()
	{
		if (isset($_SERVER['HTTP_USER_AGENT']))
		{
			return trim($_SERVER['HTTP_USER_AGENT']);
		}
	}
	
	static function get_mobile_status()
	{
		$mobile_type = array(
				'iphone' => array('name'=>"Apple iPhone",'status'=>256),
				'ipad' => array('name'=>"iPad", 'status' => 256),
				'ipod' => array('name' => "Apple iPod Touch",'status' => 256),	
				'android' => array('name' => "Android", 'status' => 512),
			);
		if($useragent = self::get_user_agent())
		{
			foreach($mobile_type as $key => $val)
			{
				if(strpos(strtolower($useragent),$key))
				{
					return $val['status'];
				}
			}
			return false;
		}
	}

	static function checkemail($email,$ishtml=0)
	{
		global $_G;
		if($ishtml)
		{		
			$email = strtolower(trim($email));
			if(strlen($email) > 32) {
				BfdApp::display_result('profile_email_illegal',null,'','',1);
			} 
			if($_G['setting']['regmaildomain']) {
				$maildomainexp = '/('.str_replace("\r\n", '|', preg_quote(trim($_G['setting']['maildomainlist']), '/')).')$/i';
				if($_G['setting']['regmaildomain'] == 1 && !preg_match($maildomainexp, $email)) {
					BfdApp::display_result('profile_email_domain_illegal',null,'','',1);
				} elseif($_G['setting']['regmaildomain'] == 2 && preg_match($maildomainexp, $email)) {
					BfdApp::display_result('profile_email_domain_illegal',null,'','',1);
				}
			}

			loaducenter();
			$ucresult = uc_user_checkemail($email);

			if($ucresult == -4) {
				BfdApp::display_result('profile_email_illegal',null,'','',1);
			} elseif($ucresult == -5) {
				BfdApp::display_result('profile_email_domain_illegal',null,'','',1);
			} elseif($ucresult == -6) {
				BfdApp::display_result('profile_email_duplicate',null,'','',1);
			}
		}
		else
		{
			$email = strtolower(trim($email));
			if(strlen($email) > 32) {
				BfdApp::display_result('profile_email_illegal');
			} 
			if($_G['setting']['regmaildomain']) {
				$maildomainexp = '/('.str_replace("\r\n", '|', preg_quote(trim($_G['setting']['maildomainlist']), '/')).')$/i';
				if($_G['setting']['regmaildomain'] == 1 && !preg_match($maildomainexp, $email)) {
					BfdApp::display_result('profile_email_domain_illegal');
				} elseif($_G['setting']['regmaildomain'] == 2 && preg_match($maildomainexp, $email)) {
					BfdApp::display_result('profile_email_domain_illegal');
				}
			}

			loaducenter();
			$ucresult = uc_user_checkemail($email);

			if($ucresult == -4) {
				BfdApp::display_result('profile_email_illegal');
			} elseif($ucresult == -5) {
				BfdApp::display_result('profile_email_domain_illegal');
			} elseif($ucresult == -6) {
				BfdApp::display_result('profile_email_duplicate');
			}
		}
	}

	 static function submitcheck($var, $allowget = 0, $seccodecheck = 0, $secqaacheck = 0) {
        if(!getgpc($var)) {
            return FALSE;
        } else {
            global $_G;
            if($allowget || ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_GET['formhash']) && $_GET['formhash'] == formhash() )) {
                if(checkperm('seccode')) {
                    if($secqaacheck && !check_secqaa($_GET['secanswer'], $_GET['secqaahash'])) {
						BfdApp::display_result('submit_secqaa_invalid',null,'','',1);
                    }
                    if($seccodecheck && !check_seccode($_GET['seccodeverify'], $_GET['seccodehash'])) {
						BfdApp::display_result('submit_seccode_invalid',null,'','',1);
                    }
                }
                return TRUE;
            } else {
				BfdApp::display_result('submit_invalid',null,'','',1);
            }
        }
    }
	
	static function check_forum_password()
	{
		global $_G;
		$headers = array();
        if(function_exists( 'getallheaders' ) )
        {
            $headers = getallheaders();
        }
        else if ( isset( $_SERVER['HTTP_DF_APP_FORUMPASS'] ) )
        {
            $headers['df_app_forumpass'] = $_SERVER['HTTP_DF_APP_FORUMPASS'];
        }
//	var_dump($headers['df_app_forumpass']);	
//	var_dump($_G['forum']['password']);	
//	var_dump($_G['fid']);	
    	if($_G['forum']['password']) {
			if(empty($headers['df_app_forumpass']))
			{
        		BfdApp::display_result('view_password_error');
			}
			$forumpass = explode('; ',$headers['df_app_forumpass']);
			$forumpassarr = array();
			foreach($forumpass as $pass)
			{
				list($fid,$password) = explode('=',$pass);
				$forumpassarr[$fid] = $password;
			}
			if(empty($forumpassarr[$_G['fid']]) || $_G['forum']['password'] != $forumpassarr[$_G['fid']])
			{
        		BfdApp::display_result('view_password_error');
			}
    	}
		return;
	}

}
