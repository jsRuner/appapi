<?php

require 'inc.php';


$filepath = dirname(__FILE__).'/config/';
$filename = $filepath . 'config_extra.php';

if(file_exists($filename))
{
	install_output ('已经安装过了');
}

if(!is_writable($filepath))
{
	install_output ('appapi/config 目录不可写');
}

$sql = "SELECT * FROM ".DB::table('common_block')." WHERE name in('dzapp_index_focus', 'dzapp_index_hot_thread')";

$result = DB::fetch_all($sql);

$newblock = array();
if(empty($result))
{
	$data = array(
		array(
			"blockclass" => "forum_thread",
			"blocktype" => "0",
			"name" => "dzapp_index_focus",
			"title" => "",
			"classname" => "",
			"summary" =>  "",
			"uid" => "1",
			"username" => "admin",
			"styleid" => "100",
			"blockstyle" => 'a:10:{s:10:"blockclass";s:12:"forum_thread";s:4:"name";s:0:"";s:8:"template";a:9:{s:3:"raw";s:164:"<div class="module cl xld fcs">,
		[loop],
		<dl class="cl">,
			<dt><a href="{url}" title="{title}"{target}>{title}</a></dt>,
			<dd>{summary}</dd>,
		</dl>,
		[/loop],
		</div>";s:6:"footer";s:0:"";s:6:"header";s:0:"";s:9:"indexplus";a:0:{}s:5:"index";a:0:{}s:9:"orderplus";a:0:{}s:5:"order";a:0:{}s:8:"loopplus";a:0:{}s:4:"loop";s:106:"<dl class="cl">,
			<dt><a href="{url}" title="{title}"{target}>{title}</a></dt>,
			<dd>{summary}</dd>,
		</dl>";}s:4:"hash";s:8:"9670c626";s:6:"getpic";s:1:"0";s:10:"getsummary";s:1:"1";s:9:"makethumb";s:1:"0";s:9:"settarget";s:1:"1";s:6:"fields";a:3:{i:0;s:3:"url";i:1;s:5:"title";i:2;s:7:"summary";}s:7:"moreurl";s:1:"0";}',
			"picwidth"  =>    "600",
			"picheight"  =>    "400",
			"target"  =>    "blank",
			"dateformat"  =>    "Y-m-d",
			"dateuformat"  =>    "0",
			"script"  =>    "thread",
			"param"  =>    'a:21:{s:4:"tids";s:0:"";s:4:"uids";s:0:"";s:7:"keyword";s:0:"";s:10:"tagkeyword";s:0:"";s:4:"fids";a:1:{i:0;s:1:"0";}s:7:"typeids";s:0:"";s:9:"recommend";s:1:"0";s:7:"special";a:1:{i:0;s:1:"0";}s:7:"viewmod";s:1:"0";s:12:"rewardstatus";s:1:"0";s:11:"picrequired";s:1:"1";s:7:"orderby";s:8:"lastpost";s:12:"postdateline";s:1:"0";s:8:"lastpost";s:1:"0";s:9:"highlight";s:1:"0";s:11:"titlelength";s:2:"40";s:13:"summarylength";s:2:"80";s:8:"startrow";s:1:"0";s:5:"items";i:4;s:5:"gtids";a:1:{i:0;s:1:"0";}s:9:"gviewperm";s:2:"-1";}',
			"shownum"  =>    "4",
			"cachetime"  =>    "0",
			"cachetimerange"  =>    "",
			"punctualupdate"  =>    "0",
			"hidedisplay"  =>    "0",
			"dateline"  =>    "1383286472",
			"notinherited"  =>    "0",
			"isblank"  =>    "0",
		),
		array(
			"blockclass"  =>    "forum_thread",
			"blocktype"  =>    "1",
			"name"  =>    "dzapp_index_hot_thread",
			"title"  =>    "",
			"classname"  =>    "",
			"summary"  =>    "",
			"uid"  =>    "1",
			"username"  =>    "admin",
			"styleid"  =>    "100",
			"blockstyle"  =>    "",
			"picwidth"  =>    "175",
			"picheight"  =>    "175",
			"target"  =>    "blank",
			"dateformat"  =>    "Y-m-d",
			"dateuformat"  =>    "0",
			"script"  =>    "threadhot",
			"param"  =>    'a:10:{s:7:"special";a:1:{i:0;s:1:"0";}s:7:"viewmod";s:1:"0";s:12:"rewardstatus";s:1:"0";s:11:"picrequired";s:1:"1";s:7:"orderby";s:5:"heats";s:12:"postdateline";s:1:"0";s:8:"lastpost";s:1:"0";s:11:"titlelength";s:2:"40";s:13:"summarylength";s:2:"80";s:5:"items";i:10;}',
			"shownum"  =>    "10",
			"cachetime"  =>    "3600",
			"cachetimerange"  =>    "",
			"punctualupdate"  =>    "0",
			"hidedisplay"  =>    "0",
			"dateline"  =>    "1383286472",
			"notinherited"  =>    "0",
			"isblank"  =>    "0",
		),
	);
	$newblock = array();
	$sqlpre ="REPLACE INTO ".DB::table('common_block')." SET ";
	foreach($data as $block)
	{
			$sqlset = array();
			foreach($block as $key  =>  $val)
			{
				$sqlset[] = "`{$key}`='{$val}'";
			}
			
			$sql = $sqlpre . implode(', ',$sqlset);
			$res = DB::query($sql);
			if(!$res)
			{
				if($block['name'] == 'dzapp_index_hot_thread')
				{
					install_output ('创建App焦点图模块失败！');
				}
				else
				{
					install_output ('创建App热帖图模块失败！');
				}
			}
			$newblock[$block['name']] = DB::insert_id();
			$newblock[$block['name']] = intval($newblock[$block['name']]);
	}
}
else
{
	foreach($result as $val)
	{
		$newblock[$val['name']] = intval($val['bid']);
	}
}



//生成配置文件

$appversion = '1.1.8';
$appkey = md5(rand(1-1000).'sjdfdsf@@3l');
$charset = strtoupper($_G['charset']);
$decodecharset = ($charset == 'UTF-8') ? 'UTF-8' : 'ISO-8859-1';


$ftpurl = $_G['ftp']['on'] ? $_G['ftp']['attachurl'] : '';

$serverdir = $_SERVER['PHP_SELF'];
$serverdir_arr = explode('/',$serverdir);
$urlpath = '';
if(is_array($serverdir_arr) && count($serverdir_arr) > 0)
{
	foreach($serverdir_arr as $val)
	{
		if($val == 'appapi')
		{
			break;
		}
		if(!empty($val) && $val != 'install.php')
		{
			$urlpath .= $val.'/';
		}
	}
}

$content =  "<?php
define('BFD_APP_VERSION','{$appversion}');
define('BFD_APP_KEY','{$appkey}');
define('BFD_APP_CHARSET','{$charset}');
define('BFD_APP_INDEX_BLOCK_ID',{$newblock['dzapp_index_focus']});
define('BFD_APP_INDEX_BLOCK_ID_2',{$newblock['dzapp_index_hot_thread']});
define('BFD_APP_PIC_PATH_DIY','{$ftpurl}');//自定义附件图片前缀
define('BFD_APP_DATA_URL_PRE','http://{$_SERVER['HTTP_HOST']}/{$urlpath}');
define('BFD_APP_PIC_PATH','/{$urlpath}data/attachment/');//
define('BFD_APP_CHARSET_HTML_DECODE','{$decodecharset}');
define('BFD_APP_CREDITS_AWARD','2');
";


$res = file_put_contents($filename,$content);
if($res)
{
	install_output('安装成功');
}


function install_output($msg)
{
	header('Content-type: text/html;charset=utf-8');
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>DiscuzFan App Install</title>
<style type="text/css">
* {margin:0;padding:0}
body{background-color: #E8EFF5;width:100%;font-family:"microsoft yahei";}
</style>
</head>
<body>
	<div style="text-align:center;margin:100px auto;width:300px;height:150px;background-color:#ffffff;">
	<br/>
	<br/>
	<br/>
	'.$msg.'
	</div>
</body>
</html>
	';
	exit;
}
