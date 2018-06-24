<?php
/**
 * @filename : bfd_app_group_action.php
 * @date : 2013-03-05
 * @desc :
	用户加入退出小组
 **/
if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

require_once libfile('function/group');
require_once libfile('function/forum');
require_once libfile('function/forumlist');
require_once libfile('lib/group_helper');

loadforum();

$action_arr = array('join','out');
$action = getgpc('action');
$fid    = getgpc('fid');

if(!in_array($action,$action_arr) || !$fid)
{
	BfdApp::display_result('params_error');
}
$myfids = C::t('forum_group_follow')->get_all_group_by_uid($_G['uid']);
if('join' == $action)
{
	if(is_array($myfids) && in_array($fid,$myfids))
	{
		BfdApp::display_result('group_has_joined');
	}

	$inviteuid = 0;
        $membermaximum = $_G['current_grouplevel']['specialswitch']['membermaximum'];
        if(!empty($membermaximum)) {
                $curnum = C::t('forum_groupuser')->fetch_count_by_fid($_G['fid']);
                if($curnum >= $membermaximum) {
			BfdApp::display_result('group_member_maximum');
                }
        }
        $modmember = 4;
	$showmessage = '';
	$confirmjoin = TRUE;
	$inviteuid = C::t('forum_groupinvite')->fetch_uid_by_inviteuid($_G['fid'], $_G['uid']);
	if($_G['forum']['jointype'] == 1) {
		if(!$inviteuid) {
			$confirmjoin = FALSE;
			$showmessage = 'group_join_need_invite';
		}
	} elseif($_G['forum']['jointype'] == 2) {
		$modmember = !empty($groupmanagers[$inviteuid]) || $_G['adminid'] == 1 ? 4 : 0;
		!empty($groupmanagers[$inviteuid]) && $showmessage = 'group_join_apply_succeed';
	}
	if($confirmjoin) {
		C::t('forum_groupuser')->insert($_G['fid'], $_G['uid'], $_G['username'], $modmember, TIMESTAMP, TIMESTAMP);
		if($_G['forum']['jointype'] == 2 && (empty($inviteuid) || empty($groupmanagers[$inviteuid]))) {
			foreach($groupmanagers as $manage) {
				notification_add($manage['uid'], 'group', 'group_member_join', array('fid' => $_G['fid'], 'groupname' => $_G['forum']['name'], 'url' => $_G['siteurl'].'forum.php?mod=group&action=manage&op=checkuser&fid='.$_G['fid']), 1);
			}
/*COMMENTS
//对于需要审核加入的小组，修改提示语句，设定是否需要审核参数 20121125 ep
*/                              if($_G['adminid'] != 1){
				$showmessage = 'group_join_apply_wait_for_pend_succeed';
				$pending = '1';
			}
/*COMMENTS END*/
		} else {
/*COMMENTS
//加入小组后自然进入关注状态 20121125 ep
*/
			//此处是无需审核加入，不需要check member和gviewperm操作
			C::t('forum_group_follow')->add_follow($_G['uid'],$_G['fid']);
			$showmessage = 'group_join_apply_succeed';
/*COMMENTS END*/
		}
		if($inviteuid) {
			C::t('forum_groupinvite')->delete_by_inviteuid($_G['fid'], $_G['uid']);
		}
		if($modmember == 4) {
			C::t('forum_forumfield')->update_membernum($_G['fid']);
		}
		//小组成员变化不影响小组排序
//C::t('forum_forumfield')->update($_G['fid'], array('lastupdate' => TIMESTAMP));
	}
	include_once libfile('function/stat');
	updatestat('groupjoin');
	delgroupcache($_G['fid'], array('activityuser', 'newuserlist'));

/*COMMENTS
//加入小组后个人加积分 20121125 ep
*/
	lib_group_helper::joinGroup('+',$_G['uid'],$_G['fid']);
/*COMMENTS END*/
	if(empty($showmessage))
	{
		$showmessage = 'undefined_error';
	}
	BfdApp::display_result($showmessage);
}
else if('out' == $action)
{
	if(!is_array($myfids) || !in_array($fid,$myfids))
	{
		BfdApp::display_result('not_joined_group');
	}
 	if($_G['uid'] == $_G['forum']['founderuid']) {
		BfdApp::display_result('not_joined_group');
                //showmessage('group_exit_founder');
        }

        C::t('forum_groupuser')->delete_by_fid($_G['fid'], $_G['uid']);
        C::t('forum_forumfield')->update_membernum($_G['fid'], -1);
        update_groupmoderators($_G['fid']);
        delgroupcache($_G['fid'], array('activityuser', 'newuserlist'));
/*COMMENTS
//加入小组后个人加积分 20121125 ep
*/
                lib_group_helper::joinGroup('-',$_G['uid'],$_G['fid']);
/*COMMENTS END*/
/*COMMENTS
//退出小组后强制取消关注状态 回跳url修改 回传参数 20121125 ep
*/
        C::t('forum_group_follow')->del_follow($_G['uid'],$_G['fid']);

	BfdApp::display_result('group_out_success');
/*COMMENTS END*/
}

BfdApp::display_result('undefined_error');

