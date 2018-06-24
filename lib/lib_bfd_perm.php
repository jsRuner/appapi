<?php
/**
 * app 专用权限检测

 **/
class lib_bfd_perm
{
	static function formulaperm($formula) {
			global $_G;
			if($_G['forum']['ismoderator']) {
				return TRUE;
			}

			$formula = dunserialize($formula);
			$medalperm = $formula['medal'];
			$permusers = $formula['users'];
			$permmessage = $formula['message'];
			if($_G['setting']['medalstatus'] && $medalperm) {
				$exists = 1;
				$_G['forum_formulamessage'] = '';
				$medalpermc = $medalperm;
				if($_G['uid']) {
					$memberfieldforum = C::t('common_member_field_forum')->fetch($_G['uid']);
					$medals = explode("\t", $memberfieldforum['medals']);
					unset($memberfieldforum);
					foreach($medalperm as $k => $medal) {
						foreach($medals as $r) {
							list($medalid) = explode("|", $r);
							if($medalid == $medal) {
								$exists = 0;
								unset($medalpermc[$k]);
							}
						}
					}
				} else {
					$exists = 0;
				}
				if($medalpermc) {
					loadcache('medals');
					foreach($medalpermc as $medal) {
						if($_G['cache']['medals'][$medal]) {
							$_G['forum_formulamessage'] .= '<img src="'.STATICURL.'image/common/'.$_G['cache']['medals'][$medal]['image'].'" style="vertical-align:middle;" />&nbsp;'.$_G['cache']['medals'][$medal]['name'].'&nbsp; ';
						}
					}
					 BfdApp::display_result('group_nopermission');

//					showmessage('forum_permforum_nomedal', NULL, array('forum_permforum_nomedal' => $_G['forum_formulamessage']), array('login' => 1));
				}
			}
			$formulatext = $formula[0];
			$formula = $formula[1];
			if($_G['adminid'] == 1 || $_G['forum']['ismoderator'] || in_array($_G['groupid'], explode("\t", $_G['forum']['spviewperm']))) {
				return FALSE;
			}
			if($permusers) {
				$permusers = str_replace(array("\r\n", "\r"), array("\n", "\n"), $permusers);
				$permusers = explode("\n", trim($permusers));
				if(!in_array($_G['member']['username'], $permusers)) {
					 BfdApp::display_result('group_nopermission');
					//showmessage('forum_permforum_disallow', NULL, array(), array('login' => 1));
				}
			}
			if(!$formula) {
				return FALSE;
			}
			if(strexists($formula, '$memberformula[')) {
				preg_match_all("/\\\$memberformula\['(\w+?)'\]/", $formula, $a);
				$profilefields = array();
				foreach($a[1] as $field) {
					switch($field) {
						case 'regdate':
							$formula = preg_replace("/\{(\d{4})\-(\d{1,2})\-(\d{1,2})\}/e", "'\'\\1-'.sprintf('%02d', '\\2').'-'.sprintf('%02d', '\\3').'\''", $formula);
						case 'regday':
							break;
						case 'regip':
						case 'lastip':
							$formula = preg_replace("/\{([\d\.]+?)\}/", "'\\1'", $formula);
							$formula = preg_replace('/(\$memberformula\[\'(regip|lastip)\'\])\s*=+\s*\'([\d\.]+?)\'/', "strpos(\\1, '\\3')===0", $formula);
						case 'buyercredit':
						case 'sellercredit':
							space_merge($_G['member'], 'status');break;
						case substr($field, 0, 5) == 'field':
							space_merge($_G['member'], 'profile');
							$profilefields[] = $field;break;
					}
				}
				$memberformula = array();
				if($_G['uid']) {
					$memberformula = $_G['member'];
					if(in_array('regday', $a[1])) {
						$memberformula['regday'] = intval((TIMESTAMP - $memberformula['regdate']) / 86400);
					}
					if(in_array('regdate', $a[1])) {
						$memberformula['regdate'] = date('Y-m-d', $memberformula['regdate']);
					}
					$memberformula['lastip'] = $memberformula['lastip'] ? $memberformula['lastip'] : $_G['clientip'];
				} else {
					if(isset($memberformula['regip'])) {
						$memberformula['regip'] = $_G['clientip'];
					}
					if(isset($memberformula['lastip'])) {
						$memberformula['lastip'] = $_G['clientip'];
					}
				}
			}
			@eval("\$formulaperm = ($formula) ? TRUE : FALSE;");
			if(!$formulaperm) {
				if(!$permmessage) {
					$language = lang('forum/misc');
					$search = array('regdate', 'regday', 'regip', 'lastip', 'buyercredit', 'sellercredit', 'digestposts', 'posts', 'threads', 'oltime');
					$replace = array($language['formulaperm_regdate'], $language['formulaperm_regday'], $language['formulaperm_regip'], $language['formulaperm_lastip'], $language['formulaperm_buyercredit'], $language['formulaperm_sellercredit'], $language['formulaperm_digestposts'], $language['formulaperm_posts'], $language['formulaperm_threads'], $language['formulaperm_oltime']);
					for($i = 1; $i <= 8; $i++) {
						$search[] = 'extcredits'.$i;
						$replace[] = $_G['setting']['extcredits'][$i]['title'] ? $_G['setting']['extcredits'][$i]['title'] : $language['formulaperm_extcredits'].$i;
					}
					if($profilefields) {
						loadcache(array('fields_required', 'fields_optional'));
						foreach($profilefields as $profilefield) {
							$search[] = $profilefield;
							$replace[] = !empty($_G['cache']['fields_optional']['field_'.$profilefield]) ? $_G['cache']['fields_optional']['field_'.$profilefield]['title'] : $_G['cache']['fields_required']['field_'.$profilefield]['title'];
						}
					}
					$i = 0;$_G['forum_usermsg'] = '';
					foreach($search as $s) {
						if(in_array($s, array('digestposts', 'posts', 'threads', 'oltime', 'extcredits1', 'extcredits2', 'extcredits3', 'extcredits4', 'extcredits5', 'extcredits6', 'extcredits7', 'extcredits8'))) {
							$_G['forum_usermsg'] .= strexists($formulatext, $s) ? '<br />&nbsp;&nbsp;&nbsp;'.$replace[$i].': '.(@eval('return intval(getuserprofile(\''.$s.'\'));')) : '';
						} elseif(in_array($s, array('regdate', 'regip', 'regday'))) {
							$_G['forum_usermsg'] .= strexists($formulatext, $s) ? '<br />&nbsp;&nbsp;&nbsp;'.$replace[$i].': '.(@eval('return $memberformula[\''.$s.'\'];')) : '';
						}
						$i++;
					}
					$search = array_merge($search, array('and', 'or', '>=', '<=', '=='));
					$replace = array_merge($replace, array('&nbsp;&nbsp;<b>'.$language['formulaperm_and'].'</b>&nbsp;&nbsp;', '&nbsp;&nbsp;<b>'.$language['formulaperm_or'].'</b>&nbsp;&nbsp;', '&ge;', '&le;', '='));
					$_G['forum_formulamessage'] = str_replace($search, $replace, $formulatext);
				} else {
					$_G['forum_formulamessage'] = $permmessage;
				}

				if(!$permmessage) {
					 BfdApp::display_result('group_nopermission');
					//showmessage('forum_permforum_nopermission', NULL, array('formulamessage' => $_G['forum_formulamessage'], 'usermsg' => $_G['forum_usermsg']), array('login' => 1));
				} else {
					 BfdApp::display_result('group_nopermission');
					//showmessage('forum_permforum_nopermission_custommsg', NULL, array('formulamessage' => $_G['forum_formulamessage']), array('login' => 1));
				}
			}
			return TRUE;
		}

		/*
		 * 检查是否新用户
 		 **/
	static function cknewuser($return=0) 
	{
			global $_G;

			$result = true;

			if(!$_G['uid']) 
			{
				return 'user_no_login';
			}

			if(checkperm('disablepostctrl')) {
				return $result;
			}
			$ckuser = $_G['member'];

			if($_G['setting']['newbiespan'] && $_G['timestamp']-$ckuser['regdate']<$_G['setting']['newbiespan']*60) {
				if(empty($return)) 
					$result = 'no_privilege_newbiespan';
			}
			if($_G['setting']['need_avatar'] && empty($ckuser['avatarstatus'])) {
					$result = 'no_privilege_avatar';
			}
			if($_G['setting']['need_email'] && empty($ckuser['emailstatus'])) {
				$result = 'no_privilege_email';
			}
			if($_G['setting']['need_friendnum']) 
			{
				space_merge($ckuser, 'count');
				if($ckuser['friends'] < $_G['setting']['need_friendnum']) 
				{
					$result = 'no_privilege_friendnum';
				}
			}
			return $result;
	}

	static function checklowerlimit($action, $uid = 0, $coef = 1, $fid = 0, $returnonly = 0) 
	{
			global $_G;

			include_once libfile('class/credit');
			$credit = & credit::instance();
			$limit = $credit->lowerlimit($action, $uid, $coef, $fid);
			if($returnonly) return $limit;
			if($limit !== true) {
				$GLOBALS['id'] = $limit;
				$lowerlimit = is_array($action) && $action['extcredits'.$limit] ? abs($action['extcredits'.$limit]) + $_G['setting']['creditspolicy']['lowerlimit'][$limit] : $_G['setting']['creditspolicy']['lowerlimit'][$limit];
				$rulecredit = array();
				if(!is_array($action)) {
					$rule = $credit->getrule($action, $fid);
					foreach($_G['setting']['extcredits'] as $extcreditid => $extcredit) {
						if($rule['extcredits'.$extcreditid]) {
							$rulecredit[] = $extcredit['title'].($rule['extcredits'.$extcreditid] > 0 ? '+'.$rule['extcredits'.$extcreditid] : $rule['extcredits'.$extcreditid]);
						}
					}
				} else {
					$rule = array();
				}
				$values = array(
					'title' => $_G['setting']['extcredits'][$limit]['title'],
					'lowerlimit' => $lowerlimit,
					'unit' => $_G['setting']['extcredits'][$limit]['unit'],
					'ruletext' => $rule['rulename'],
					'rulecredit' => implode(', ', $rulecredit)
				);
				if(!is_array($action)) {
					if(!$fid) {
						BfdApp::display_result('credits_policy_lowerlimit');
					} else {
						BfdApp::display_result('credits_policy_lowerlimit_fid');
					}
				} else {
					BfdApp::display_result('credits_policy_lowerlimit_norule');
				}
			}
	}

}
