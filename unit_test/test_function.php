<?php
function curl_get($url, $get = Array(), array $options = array())
{
    $defaults = array(
        CURLOPT_URL => $url.(strpos($url, '?')===FALSE? '?' :'').http_build_query($get),
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'mozilla/5.0 (android/4.1.2; coolpad 5950)',
        CURLOPT_TIMEVALUE => START_TIME,
        CURLOPT_TIMECONDITION => CURL_TIMECOND_IFMODSINCE,
        
        
    );
    $ch = curl_init();
    curl_setopt_array($ch, ($options + $defaults));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
function curl_post($url,$params)
{
    $defaults = array(
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $params,
    );
    $ch = curl_init();
    curl_setopt_array($ch,$defaults);
    if (! $result = curl_exec($ch))
    {
    	echo 'errot';
    }
    curl_close($ch);
    return $result;
}


