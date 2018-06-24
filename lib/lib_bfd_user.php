<?php
/**
 * app 专用权限检测

 **/
class lib_bfd_user
{
	static function get_user_info($uid)
	{
		//用户信息
		$member = DB::fetch_first("SELECT * FROM ".DB::table('common_member')." WHERE uid='".$uid."'");
		if(!$member) {
			return false;
		}

		//用户详细信息
		$member_profile = DB::fetch_first("SELECT * FROM ".DB::table('common_member_profile')." WHERE uid={$uid}");
		//获取勋章
		$member_field_forum = DB::fetch_first("SELECT * FROM ".DB::table('common_member_field_forum')." WHERE uid='{$uid}'");

		$medalids = $member_field_forum['medals'];
		$usermedals = $medal_detail = $usermedalmenus = array();
		if($medalids) {
			foreach($medalids = explode("\t", $medalids) as $key => $medalid) {
				list($medalid, $medalexpiration) = explode("|", $medalid);
				if(!$medalexpiration || $medalexpiration > TIMESTAMP) {
					$usermedals[] = $medalid;
				}
			}
			$medal_detail = DB::fetch_all("SELECT * FROM ".DB::table('forum_medal')." WHERE medalid in(".implode(',',$usermedals).")"); 
			foreach($medal_detail as $val)
			{
				if($val['expiration']==0 || $val['expiration'] > TIMESTAMP)
				{
					$usermedalmenus[] = array('medalid'=>$val['medalid'],'name'=>$val['name'],'image'=>STATICURL.'image/common/'.$val['image']);
				}
			}	
		}

		//统计数据
		$member_count = DB::fetch_first("SELECT * FROM ".DB::table('common_member_count')." WHERE uid='{$uid}'");

		$data = array();
		$data['uid']    = $member['uid'];
		$data['avatar'] =  avatar($ucresult['uid'],'middle',true);
		$data['username'] = $member['username'];
		$data['email']    = $member['email'];
		$data['password'] = $member['password'];
		$data['groupid']  = $member['groupid'];//等级
		$data['extcredits1'] = $member_count['extcredits1'];//蓝宝石
		$data['extcredits2'] = $member_count['extcredits2'];//积分
		$data['follower']    = $member_count['follower'];//分数数
		$data['following']   = $member_count['following'];//关注数
		$data['gender']      = lang('space','gender_'.$member_profile['gender']);
		$data['department']   = $member_profile['field2'];//部门
		$data['constellation']   = $member_profile['constellation'];//星座
		$data['medals']   = $usermedalmenus;//勋章
		$data['bloodtype'] = $member_profile['bloodtype'];//血型
		$data['sightml'] = strip_tags($member_profile_forum['sightml']);//签名

		$time = time();
		$data['token']      =  authcode("{$member['password']}\t{$member['uid']}\t{$time}", 'ENCODE',BFD_APP_KEY,BFD_APP_KEY_EXPIRY);
		$data['token_expire'] = $time+BFD_APP_KEY_EXPIRY;

		return $data;

	}
}
