<?php
/*
	dsu_paulsign Main By shy9000[DSU.CC] 2012-04-15
*/
!defined('IN_DISCUZ') && exit('Access Denied');

$type = trim($_GET['type']);
$type_array = array('sign','message','all','pmdetail');


if(empty($_G['uid']))
{
	BfdApp::display_result('not_loggedin');
}

if(!in_array($type,$type_array))
{
	BfdApp::display_result('params_error');
}

if('sign' == $type)
{
		define('IN_dsu_paulsign', '1');
		$var = $_G['cache']['plugin']['dsu_paulsign'];
		$tdtime = gmmktime(0,0,0,dgmdate($_G['timestamp'], 'n',$var['tos']),dgmdate($_G['timestamp'], 'j',$var['tos']),dgmdate($_G['timestamp'], 'Y',$var['tos'])) - $var['tos']*3600;
		$htime = dgmdate($_G['timestamp'], 'H',$var['tos']);
		$qiandaodb = DB::fetch_first("SELECT * FROM ".DB::table('dsu_paulsign')." WHERE uid='$_G[uid]'");

		$result = array();
		if(!$var['ifopen']) 
		{
			$result['qiandao_status'] = '0';//不可签到
		}

		if( $qiandaodb['time'] < $tdtime )
		{
				$result['qiandao_status'] = '1';//可签到
				if($var['timeopen'] && ($htime < $var['stime'] || $htime > $var['ftime']))
				{
					$result['qiandao_status'] = '0';//不可签到
				}
		}
		else
		{
				$result['qiandao_status'] = '2';//已签到
		}
			
		BfdApp::display_result('get_success',$result);
}
else if('message' == $type)
{
	$sql = "SELECT newpm,newprompt FROM ".DB::table('common_member')." WHERE uid='{$_G['uid']}'";
	$result = DB::fetch_first($sql);
	$message = 0;
	if(!empty($result))
	{
		$message = $result['newpm'] + $result['newprompt'];
	}
	$result = array();
	$result['message_count'] = $message;
	BfdApp::display_result('get_success',$result);
}
else if('all' == $type)
{
		define('IN_dsu_paulsign', '1');
		$var = $_G['cache']['plugin']['dsu_paulsign'];
		$tdtime = gmmktime(0,0,0,dgmdate($_G['timestamp'], 'n',$var['tos']),dgmdate($_G['timestamp'], 'j',$var['tos']),dgmdate($_G['timestamp'], 'Y',$var['tos'])) - $var['tos']*3600;
		$htime = dgmdate($_G['timestamp'], 'H',$var['tos']);
		$qiandaodb = DB::fetch_first("SELECT * FROM ".DB::table('dsu_paulsign')." WHERE uid='$_G[uid]'");

		$result = array();
		if(!$var['ifopen']) 
		{
			$result['qiandao_status'] = '0';//不可签到
		}

		if( $qiandaodb['time'] < $tdtime )
		{
				$result['qiandao_status'] = '1';//可签到
				if($var['timeopen'] && ($htime < $var['stime'] || $htime > $var['ftime']))
				{
					$result['qiandao_status'] = '0';//不可签到
				}
		}
		else
		{
				$result['qiandao_status'] = '2';//已签到
		}
			
	loaducenter();

	$count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_notification')." WHERE uid={$_G['uid']} AND `new`='1'");
	//公共消息数
    $announcepm = 0;
    foreach(C::t('common_member_grouppm')->fetch_all_by_uid($_G['uid'], 1) as $gpmid => $gpuser)
    {
        $gpmstatus[$gpmid] = $gpuser['status'];
        if($gpuser['status'] == 0) {
            $announcepm ++;
        }
    }

    //私人消息数
    $newpmarr = uc_pm_checknew($_G['uid'], 1);
    $newpm = $newpmarr['newpm'];
    $message = $count + $announcepm + $newpm;

/*
	$sql = "SELECT newpm,newprompt FROM ".DB::table('common_member')." WHERE uid='{$_G['uid']}'";
	$result2 = DB::fetch_first($sql);
	$message = 0;
	if(!empty($result))
	{
		$message = $result2['newpm'] + $result2['newprompt'];
	}
*/
	$result['message_count'] = $message;
	BfdApp::display_result('get_success',$result);
}
else if ('pmdetail' == $type)
{
	loaducenter();

    $result = array();

    //通知数
    $typestr = " `type` not in('post','blogcomment','at','follower')";
    $count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_notification')." WHERE uid={$_G['uid']} AND `new`='1' AND {$typestr}");
    $result['notpost'] = intval($count);

    //回复我的数
    $typestr = " `type` in ('post','blogcomment')";;
    $count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_notification')." WHERE uid={$_G['uid']} AND `new`='1' AND {$typestr}");
    $result['post'] = intval($count);


    //公共消息数
    $announcepm = 0;
    foreach(C::t('common_member_grouppm')->fetch_all_by_uid($_G['uid'], 1) as $gpmid => $gpuser) 
    {
        $gpmstatus[$gpmid] = $gpuser['status'];
        if($gpuser['status'] == 0) {
            $announcepm ++;
        }
    }

    //私人消息数
    $newpmarr = uc_pm_checknew($_G['uid'], 1);
    $newpm = $newpmarr['newpm'];
    $result['pm'] = $announcepm + $newpm;

    BfdApp::display_result('get_success',$result);
}
