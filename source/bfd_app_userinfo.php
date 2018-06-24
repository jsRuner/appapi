<?php


$uids = trim($_REQUEST['lbsuids']);
if(empty($uids))
{
	BfdApp::display_result('get_success',array());
}
$uids = explode(',',$uids);

foreach($uids as $key=>$val)
{
	$uids[$key] = intval($val);
}

$memberlist = array();
if(!empty($uids))
{
	$uids_str = implode(',',$uids);;
	$member = DB::fetch_all("SELECT uid,username FROM ".DB::table('common_member')." WHERE uid in({$uids_str})");
	if(!empty($member))
	{
		foreach($member as $val)
		{
			$memberlist[$val['uid']] = $val;
			$memberlist[$val['uid']]['avatar'] = avatar($val['uid'],'middle',true);
		}
		//用户详细信息
		$member_profile = DB::fetch_all("SELECT uid,gender FROM ".DB::table('common_member_profile')." WHERE uid in({$uids_str})");
		if(!empty($member_profile))
		{
			foreach($member_profile as $val)
			{
				$memberlist[$val['uid']]['gender'] = lang('space','gender_'.$val['gender']);
			}
		}
		//用户签名	
		$member_field_forum = DB::fetch_all("SELECT uid,sightml FROM ".DB::table('common_member_field_forum')." WHERE uid in({$uids_str})");
		if(!empty($member_field_forum))
		{
			foreach($member_field_forum as $val)
			{
				$memberlist[$val['uid']]['sign'] = strip_tags($val['sightml']);
			}
		}
	}
	
}


$result = array();
$result['userlist'] = array_values($memberlist);
BfdApp::display_result('get_success',$result);

