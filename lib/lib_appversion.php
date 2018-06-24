<?php
/**
 * app更新信息类
 *
 **/
// 本类由系统自动生成，仅供测试用途
class Appversion{
	private $_ios_list = array(
			'1.0' => array(
				'msg' => 'DiscuzFan 官网IOS版App首发',
				'url' => '',
				'md5' => '',
				),
			'2.0' => array(
				'msg' => 'DiscuzFan官网IOS版App首发',
				'url' => '',
				'md5' => '',
				),
		);
	private $_ios_current = '1.0';
	private $_android_list = array(
			'1.0' => array(
				'msg' => 'DiscuzFan官网Android版App首发',
				'url' => '',
				'md5' => '',
				),
			'2.0' => array(
				'msg' => 'DiscuzFan官网Android版App2.0',
				'url' => 'http://www.discuzfan.com/appapi/Discuz.apk',
				'md5' => md5_file('/home/discuzfan/appapi/Discuz.apk'),
				),
		);
	private $_android_current = '2.0';

	/** 
         * 获取ios 最新版app
         **/
	public function ios_version($version)
	{
		$version = trim($version);
		$result = array();
		if(isset($this->_ios_list[$this->_ios_current]) && $version !== $this->_ios_current)
		{
			$result = array(
				'lastversion' => $this->_ios_current,
				'versioninfo' => $this->_ios_list[$this->_ios_current],	
			);
		}
		return $result;
	}

	/** 
         * 获取android 最新版app
	 **/
	public function android_version($version)
	{
		$version = trim($version);
		$result = array();
		if(isset($this->_android_list[$this->_android_current]) && $version !== $this->_android_current)
		{
			$result = array(
				'lastversion' => $this->_android_current,
				'versioninfo' => $this->_android_list[$this->_android_current],	
			);
		}
		return $result;
	}
}
