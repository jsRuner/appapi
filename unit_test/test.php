<?php
include 'test_inc.php';

$modlist = array(
'send_pm' => '',
);

foreach($modlist as $mod => $val)
{
	include 'test_'.$mod.'.php';

	if($method == 'get')
	{
		$result2 = curl_get($url,$params);
	}
	else
	{
		$result2 = curl_post($url,$params);
	}	
	echo $result2 ;
	echo "\n";
	echo $result;
}
