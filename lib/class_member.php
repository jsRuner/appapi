<?php

class register_ctl {

	var $showregisterform = 1;

	function register_ctl() {

	}

	function on_register() {
		global $_G;

		$_GET['username'] = $_GET[''.$this->setting['reginput']['username']];
		$_GET['password'] = $_GET[''.$this->setting['reginput']['password']];
		$_GET['password2'] = $_GET[''.$this->setting['reginput']['password2']];
		$_GET['email'] = $_GET[''.$this->setting['reginput']['email']];


		$bbrules = & $this->setting['bbrules'];
		$bbrulesforce = & $this->setting['bbrulesforce'];
		$bbrulestxt = & $this->setting['bbrulestxt'];
		$welcomemsg = & $this->setting['welcomemsg'];
		$welcomemsgtitle = & $this->setting['welcomemsgtitle'];
		$welcomemsgtxt = & $this->setting['welcomemsgtxt'];
		$regname = $this->setting['regname'];

		if($this->setting['regverify']) {
			if($this->setting['areaverifywhite']) {
				$location = $whitearea = '';
				$location = trim(convertip($_G['clientip'], "./"));
				if($location) {
					$whitearea = preg_quote(trim($this->setting['areaverifywhite']), '/');
					$whitearea = str_replace(array("\\*"), array('.*'), $whitearea);
					$whitearea = '.*'.$whitearea.'.*';
					$whitearea = '/^('.str_replace(array("\r\n", ' '), array('.*|.*', ''), $whitearea).')$/i';
					if(@preg_match($whitearea, $location)) {
						$this->setting['regverify'] = 0;
					}
				}
			}

		}

		$invitestatus = false;
		if($this->setting['regstatus'] == 2) {
			if($this->setting['inviteconfig']['inviteareawhite']) {
				$location = $whitearea = '';
				$location = trim(convertip($_G['clientip'], "./"));
				if($location) {
					$whitearea = preg_quote(trim($this->setting['inviteconfig']['inviteareawhite']), '/');
					$whitearea = str_replace(array("\\*"), array('.*'), $whitearea);
					$whitearea = '.*'.$whitearea.'.*';
					$whitearea = '/^('.str_replace(array("\r\n", ' '), array('.*|.*', ''), $whitearea).')$/i';
					if(@preg_match($whitearea, $location)) {
						$invitestatus = true;
					}
				}
			}

			if($this->setting['inviteconfig']['inviteipwhite']) {
				foreach(explode("\n", $this->setting['inviteconfig']['inviteipwhite']) as $ctrlip) {
					if(preg_match("/^(".preg_quote(($ctrlip = trim($ctrlip)), '/').")/", $_G['clientip'])) {
						$invitestatus = true;
						break;
					}
				}
			}
		}

		$groupinfo = array();
		if($this->setting['regverify']) {
			$groupinfo['groupid'] = 8;
		} else {
			$groupinfo['groupid'] = $this->setting['newusergroupid'];
		}

		if($_G['setting']['version'] < 'X3.1')
        {
            $seccodecheck = $this->setting['seccodestatus'] & 1;
            $secqaacheck = $this->setting['secqaa']['status'] & 1;
        }
        else
        {
            list($seccodecheck, $secqaacheck) = seccheck('register');
        }

		$fromuid = !empty($_G['cookie']['promotion']) && $this->setting['creditspolicy']['promotion_register'] ? intval($_G['cookie']['promotion']) : 0;
		$username = isset($_GET['username']) ? $_GET['username'] : '';
		$bbrulehash = $bbrules ? substr(md5(FORMHASH), 0, 8) : '';
		$auth = $_GET['auth'];

		if(!$invitestatus) {
			$invite = getinvite();
		}
		$sendurl = $this->setting['sendregisterurl'] ? true : false;
		if($sendurl) {
			if(!empty($_GET['hash'])) {
				$_GET['hash'] = preg_replace("/[^\[A-Za-z0-9_\]%\s+-\/=]/", '', $_GET['hash']);
				$hash = explode("\t", authcode($_GET['hash'], 'DECODE', $_G['config']['security']['authkey']));
				if(is_array($hash) && isemail($hash[0]) && TIMESTAMP - $hash[1] < 259200) {
					$sendurl = false;
				}
			}
		}

		if(!BfdApp::submitcheck('regsubmit', 0, $seccodecheck, $secqaacheck)) {
	//	if(empty($_POST)) {
			include template('header',0,'appapi/template');
			include template('register',0,'appapi/template');
			include template('footer',0,'appapi/template');

		} else {

			if(!$activationauth && ($sendurl || !$_G['setting']['forgeemail'])) {
				BfdApp::checkemail($_GET['email'],1);
			}
			if($sendurl) {
				$hashstr = urlencode(authcode("$_GET[email]\t$_G[timestamp]", 'ENCODE', $_G['config']['security']['authkey']));
				$registerurl = "{$_G[siteurl]}member.php?mod=".$this->setting['regname']."&amp;hash={$hashstr}&amp;email={$_GET[email]}";
				$email_register_message = lang('email', 'email_register_message', array(
					'bbname' => $this->setting['bbname'],
					'siteurl' => $_G['siteurl'],
					'url' => $registerurl
				));
				if(!sendmail("$_GET[email] <$_GET[email]>", lang('email', 'email_register_subject'), $email_register_message)) {
					runlog('sendmail', "$_GET[email] sendmail failed.");
				}
				BfdApp::display_result('register_email_send_succeed',null,'','',1);
				//showmessage('register_email_send_succeed', dreferer(), array('bbname' => $this->setting['bbname']), array('showdialog' => false, 'msgtype' => 3, 'closetime' => 10));
			}
			$emailstatus = 0;
			if($this->setting['sendregisterurl'] && !$sendurl) {
				$_GET['email'] = strtolower($hash[0]);
				$this->setting['regverify'] = $this->setting['regverify'] == 1 ? 0 : $this->setting['regverify'];
				if(!$this->setting['regverify']) {
					$groupinfo['groupid'] = $this->setting['newusergroupid'];
				}
				$emailstatus = 1;
			}

			if($this->setting['regstatus'] == 2 && empty($invite) && !$invitestatus) {
				BfdApp::display_result('not_open_registration_invite',null,'','',1);
				//showmessage('not_open_registration_invite');
			}

			if($bbrules && $bbrulehash != $_POST['agreebbrule']) {
				//showmessage('register_rules_agree');
				BfdApp::display_result('register_rules_agree',null,'','',1);
			}


			if(!$activation) {
				$usernamelen = dstrlen($username);
				if($usernamelen < 3) {
					BfdApp::display_result('profile_username_tooshort',null,'','',1);
				} elseif($usernamelen > 15) {
					BfdApp::display_result('profile_username_toolong',null,'','',1);
				}
				if(uc_get_user(addslashes($username)) && !C::t('common_member')->fetch_uid_by_username($username) && !C::t('common_member_archive')->fetch_uid_by_username($username)) {
						BfdApp::display_result('register_activation_message',null,'','',1);
				}
				if($this->setting['pwlength']) {
					if(strlen($_GET['password']) < $this->setting['pwlength']) {
						BfdApp::display_result('profile_password_tooshort',null,'','',1);
						//showmessage('profile_password_tooshort', '', array('pwlength' => $this->setting['pwlength']));
					}
				}
				if($this->setting['strongpw']) {
					$strongpw_str = array();
					if(in_array(1, $this->setting['strongpw']) && !preg_match("/\d+/", $_GET['password'])) {
						$strongpw_str[] = lang('member/template', 'strongpw_1');
					}
					if(in_array(2, $this->setting['strongpw']) && !preg_match("/[a-z]+/", $_GET['password'])) {
						$strongpw_str[] = lang('member/template', 'strongpw_2');
					}
					if(in_array(3, $this->setting['strongpw']) && !preg_match("/[A-Z]+/", $_GET['password'])) {
						$strongpw_str[] = lang('member/template', 'strongpw_3');
					}
					if(in_array(4, $this->setting['strongpw']) && !preg_match("/[^a-zA-z0-9]+/", $_GET['password'])) {
						$strongpw_str[] = lang('member/template', 'strongpw_4');
					}
					if($strongpw_str) {
						BfdApp::display_result('profile_password_password_weak',null,'','',1);
						//showmessage(lang('member/template', 'password_weak').implode(',', $strongpw_str));
					}
				}
				$email = strtolower(trim($_GET['email']));
				if(empty($email) && $_G['setting']['forgeemail']) {
					$_GET['email'] = $email = strtolower(random(6)).'@'.$_SERVER['HTTP_HOST'];
				}
				if(empty($this->setting['ignorepassword'])) {
					if($_GET['password'] !== $_GET['password2']) {
						//showmessage('profile_passwd_notmatch');
						BfdApp::display_result('profile_passwd_notmatch',null,'','',1);
					}

					if(!$_GET['password'] || $_GET['password'] != addslashes($_GET['password'])) {
						BfdApp::display_result('profile_passwd_illegal',null,'','',1);
					}
					$password = $_GET['password'];
				} else {
					$password = md5(random(10));
				}
			}

			$censorexp = '/^('.str_replace(array('\\*', "\r\n", ' '), array('.*', '|', ''), preg_quote(($this->setting['censoruser'] = trim($this->setting['censoruser'])), '/')).')$/i';

			if($this->setting['censoruser'] && @preg_match($censorexp, $username)) {
				BfdApp::display_result('profile_username_protect',null,'','',1);
			}

			if($this->setting['regverify'] == 2 && !trim($_GET['regmessage'])) {
				BfdApp::display_result('profile_required_info_invalid',null,'','',1);
			}

			if($_G['cache']['ipctrl']['ipregctrl']) {
				foreach(explode("\n", $_G['cache']['ipctrl']['ipregctrl']) as $ctrlip) {
					if(preg_match("/^(".preg_quote(($ctrlip = trim($ctrlip)), '/').")/", $_G['clientip'])) {
						$ctrlip = $ctrlip.'%';
						$this->setting['regctrl'] = $this->setting['ipregctrltime'];
						break;
					} else {
						$ctrlip = $_G['clientip'];
					}
				}
			} else {
				$ctrlip = $_G['clientip'];
			}

			if($this->setting['regctrl']) {
				if(C::t('common_regip')->count_by_ip_dateline($ctrlip, $_G['timestamp']-$this->setting['regctrl']*3600)) {
					BfdApp::display_result('register_ctrl',null,'','',1);
				}
			}

			$setregip = null;
			if($this->setting['regfloodctrl']) {
				$regip = C::t('common_regip')->fetch_by_ip_dateline($_G['clientip'], $_G['timestamp']-86400);
				if($regip) {
					if($regip['count'] >= $this->setting['regfloodctrl']) {
						BfdApp::display_result('register_flood_ctrl',null,'','',1);
					} else {
						$setregip = 1;
					}
				} else {
					$setregip = 2;
				}
			}


			if(!$activation) {
				$uid = uc_user_register(addslashes($username), $password, $email, $questionid, $answer, $_G['clientip']);
				if($uid <= 0) {
					if($uid == -1) {
						BfdApp::display_result('profile_username_illegal',null,'','',1);
					} elseif($uid == -2) {
						BfdApp::display_result('profile_username_protect',null,'','',1);
					} elseif($uid == -3) {
						BfdApp::display_result('profile_username_duplicate',null,'','',1);
					} elseif($uid == -4) {
						BfdApp::display_result('profile_email_illegal',null,'','',1);
					} elseif($uid == -5) {
						BfdApp::display_result('profile_email_domain_illegal',null,'','',1);
					} elseif($uid == -6) {
						BfdApp::display_result('profile_email_duplicate',null,'','',1);
					} else {
						BfdApp::display_result('undefined_action',null,'','',1);
					}
				}
			} else {
				list($uid, $username, $email) = $activation;
			}
			$_G['username'] = $username;
			if(getuserbyuid($uid, 1)) {
				if(!$activation) {
					uc_user_delete($uid);
				}
				BfdApp::display_result('profile_uid_duplicate',null,'','',1);
			}

			$password = md5(random(10));
			$secques = $questionid > 0 ? random(8) : '';
/*
			if(isset($_POST['birthmonth']) && isset($_POST['birthday'])) {
				$profile['constellation'] = get_constellation($_POST['birthmonth'], $_POST['birthday']);
			}
			if(isset($_POST['birthyear'])) {
				$profile['zodiac'] = get_zodiac($_POST['birthyear']);
			}
*/

			if($setregip !== null) {
				if($setregip == 1) {
					C::t('common_regip')->update_count_by_ip($_G['clientip']);
				} else {
					C::t('common_regip')->insert(array('ip' => $_G['clientip'], 'count' => 1, 'dateline' => $_G['timestamp']));
				}
			}

			if($invite && $this->setting['inviteconfig']['invitegroupid']) {
				$groupinfo['groupid'] = $this->setting['inviteconfig']['invitegroupid'];
			}

			$init_arr = array('credits' => explode(',', $this->setting['initcredits']), 'profile'=>$profile, 'emailstatus' => $emailstatus);

			C::t('common_member')->insert($uid, $username, $password, $email, $_G['clientip'], $groupinfo['groupid'], $init_arr);
			if($emailstatus) {
				updatecreditbyaction('realemail', $uid);
			}
			if($verifyarr) {
				$setverify = array(
					'uid' => $uid,
					'username' => $username,
					'verifytype' => '0',
					'field' => serialize($verifyarr),
					'dateline' => TIMESTAMP,
				);
				C::t('common_member_verify_info')->insert($setverify);
				C::t('common_member_verify')->insert(array('uid' => $uid));
			}

			require_once libfile('cache/userstats', 'function');
			build_cache_userstats();

			if($this->extrafile && file_exists($this->extrafile)) {
				require_once $this->extrafile;
			}

			if($this->setting['regctrl'] || $this->setting['regfloodctrl']) {
				C::t('common_regip')->delete_by_dateline($_G['timestamp']-($this->setting['regctrl'] > 72 ? $this->setting['regctrl'] : 72)*3600);
				if($this->setting['regctrl']) {
					C::t('common_regip')->insert(array('ip' => $_G['clientip'], 'count' => -1, 'dateline' => $_G['timestamp']));
				}
			}

			$regmessage = dhtmlspecialchars($_GET['regmessage']);
			if($this->setting['regverify'] == 2) {
				C::t('common_member_validate')->insert(array(
					'uid' => $uid,
					'submitdate' => $_G['timestamp'],
					'moddate' => 0,
					'admin' => '',
					'submittimes' => 1,
					'status' => 0,
					'message' => $regmessage,
					'remark' => '',
				), false, true);
				manage_addnotify('verifyuser');
			}

			setloginstatus(array(
				'uid' => $uid,
				'username' => $_G['username'],
				'password' => $password,
				'groupid' => $groupinfo['groupid'],
			), 0);
			include_once libfile('function/stat');
			updatestat('register');

			if($invite['id']) {
				$result = C::t('common_invite')->count_by_uid_fuid($invite['uid'], $uid);
				if(!$result) {
					C::t('common_invite')->update($invite['id'], array('fuid'=>$uid, 'fusername'=>$_G['username'], 'regdateline' => $_G['timestamp'], 'status' => 2));
					updatestat('invite');
				} else {
					$invite = array();
				}
			}
			if($invite['uid']) {
				if($this->setting['inviteconfig']['inviteaddcredit']) {
					updatemembercount($uid, array($this->setting['inviteconfig']['inviterewardcredit'] => $this->setting['inviteconfig']['inviteaddcredit']));
				}
				if($this->setting['inviteconfig']['invitedaddcredit']) {
					updatemembercount($invite['uid'], array($this->setting['inviteconfig']['inviterewardcredit'] => $this->setting['inviteconfig']['invitedaddcredit']));
				}
				require_once libfile('function/friend');
				friend_make($invite['uid'], $invite['username'], false);
				notification_add($invite['uid'], 'friend', 'invite_friend', array('actor' => '<a href="home.php?mod=space&uid='.$invite['uid'].'" target="_blank">'.$invite['username'].'</a>'), 1);

				space_merge($invite, 'field_home');
				if(!empty($invite['privacy']['feed']['invite'])) {
					require_once libfile('function/feed');
					$tite_data = array('username' => '<a href="home.php?mod=space&uid='.$_G['uid'].'">'.$_G['username'].'</a>');
					feed_add('friend', 'feed_invite', $tite_data, '', array(), '', array(), array(), '', '', '', 0, 0, '', $invite['uid'], $invite['username']);
				}
				if($invite['appid']) {
					updatestat('appinvite');
				}
			}

			if($welcomemsg && !empty($welcomemsgtxt)) {
				$welcomemsgtitle = replacesitevar($welcomemsgtitle);
				$welcomemsgtxt = replacesitevar($welcomemsgtxt);
				if($welcomemsg == 1) {
					$welcomemsgtxt = nl2br(str_replace(':', '&#58;', $welcomemsgtxt));
					notification_add($uid, 'system', $welcomemsgtxt, array('from_id' => 0, 'from_idtype' => 'welcomemsg'), 1);
				} elseif($welcomemsg == 2) {
					sendmail_cron($email, $welcomemsgtitle, $welcomemsgtxt);
				} elseif($welcomemsg == 3) {
					sendmail_cron($email, $welcomemsgtitle, $welcomemsgtxt);
					$welcomemsgtxt = nl2br(str_replace(':', '&#58;', $welcomemsgtxt));
					notification_add($uid, 'system', $welcomemsgtxt, array('from_id' => 0, 'from_idtype' => 'welcomemsg'), 1);
				}
			}
/*
			if($fromuid) {
				updatecreditbyaction('promotion_register', $fromuid);
				dsetcookie('promotion', '');
			}
			dsetcookie('loginuser', '');
			dsetcookie('activationauth', '');
			dsetcookie('invite_auth', '');
*/
			$url_forward = dreferer();
			$refreshtime = 3000;
			switch($this->setting['regverify']) {
				case 1:
					$idstring = random(6);
					$authstr = $this->setting['regverify'] == 1 ? "$_G[timestamp]\t2\t$idstring" : '';
					C::t('common_member_field_forum')->update($_G['uid'], array('authstr' => $authstr));
					$verifyurl = "{$_G[siteurl]}member.php?mod=activate&amp;uid={$_G[uid]}&amp;id=$idstring";
					$email_verify_message = lang('email', 'email_verify_message', array(
						'username' => $_G['member']['username'],
						'bbname' => $this->setting['bbname'],
						'siteurl' => $_G['siteurl'],
						'url' => $verifyurl
					));
					if(!sendmail("$username <$email>", lang('email', 'email_verify_subject'), $email_verify_message)) {
						runlog('sendmail', "$email sendmail failed.");
					}
					$message = 'register_email_verify';
					$locationmessage = 'register_email_verify_location';
					$refreshtime = 10000;
					break;
				case 2:
					$message = 'register_manual_verify';
					$locationmessage = 'register_manual_verify_location';
					break;
				default:
					$message = 'register_succeed';
					$locationmessage = 'register_succeed_location';
					break;

			}
			$userinfo = array();
			if($uid)
			{
				$userinfo = lib_bfd_user::get_user_info($uid);
			}
			BfdApp::display_result($message,$userinfo,'','',1);
		}
	}

}
