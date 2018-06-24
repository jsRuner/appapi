<?php
/**
 * @filename : bfd_app_group_thread.php
 * @date : 2013-03-05
 * @desc :
	用户小组动态列表
 **/
if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

require_once libfile('function/forum');
require_once libfile('function/discuzcode');
require_once libfile('function/post');
require_once libfile('lib/group_helper');

loadforum();

$thread = & $_G['forum_thread'];
$forum = & $_G['forum'];

$tid = getgpc('tid');
$post = C::t('forum_post')->fetch_threadpost_by_tid_invisible($tid);
//$posts = C::t('forum_post')->fetch_all_by_tid(0, $tid);
$thread['message'] = $post['message'];
if($post['attachment']) {
	if( 1 || $_G['group']['allowgetattach'] || $_G['group']['allowgetimage']) {
		$_G['forum_attachpids'][] = $post['pid'];
		$post['attachment'] = 0;
		if(preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $post['message'], $matchaids)) {
			$_G['forum_attachtags'][$post['pid']] = $matchaids[1];
		}
	} else {
		$post['message'] = preg_replace("/\[attach\](\d+)\[\/attach\]/i", '', $post['message']);
	}
}
$postlist[$post['pid']] = $post;
require_once libfile('function/attachment');
parseattach($_G['forum_attachpids'], $_G['forum_attachtags'], $postlist, $skipaids);

$result = array();
$result['tid'] = $thread['tid'];
$result['fid'] = $thread['fid'];
$result['author'] = $thread['author'];
$result['authorid'] = $thread['authorid'];
$result['authoravatar'] = 'http://'.$_SERVER['HTTP_HOST'].'/uc_server/avatar.php?uid='.$result['authorid'].'&size=middle';
$result['subject'] = $thread['subject'];
$result['displayorder'] = $thread['displayorder'];
$result['dateline'] = date('Y-m-d H:i:s',$thread['dateline']);
$result['lastpost'] = date('Y-m-d H:i:s',$thread['lastpost']);
$result['lastposter'] = $thread['lastposter'];
$result['views'] = $thread['views'];
$result['replies'] = $thread['replies'];
$result['highlight'] = $thread['highlight'];
$result['recommends'] = $thread['recommends'];
$result['recommend_add'] = $thread['recommend_add'];
$result['recommend_sub'] = $thread['recommend_sub'];
$result['message'] = $thread['message'];

//附件信息
$attachs = array();
if(!empty($postlist[$post['pid']]['attachments']))
{
	foreach($postlist[$post['pid']]['attachments'] as $aid=>$val)
	{
		$tmparr = array();
		$tmparr['aid'] = $val['aid'];
		//$tmparr['dateline'] = $val['dateline'];
		$tmparr['attachment'] = BFD_APP_DATA_URL_PRE.$val['url'].$val['attachment'];
		$tmparr['isimage'] = $val['isimage'];
		if($val['isimage'])
		{

			if($val['width'] <= BFD_APP_THUMB_IMAGE_WIDTH)	
			{
				$imagefile = getglobal('setting/attachdir').'/forum/'.$val['attachment'];
				$imageinfo = @getimagesize($imagefile);
				if($imageinfo)
				{
					$val['height'] = $imageinfo[1].'';
				}
			}
			else
                        {
                                $dist = BfdApp::bfd_app_get_thumb_image($imagefile,BFD_APP_THUMB_IMAGE_WIDTH);
                                if($dist)
                                {
                                        $tmparr['attachment'] = BFD_APP_THUMB_IMAGE_PATH_URL.$dist;
                                        $distfile =  getglobal('setting/attachdir').$dist;
                                        $imageinfo = @getimagesize($distfile);
                                        if($imageinfo)
                                        {
                                                $val['width'] = $imageinfo[0].'';
                                                $val['height'] = $imageinfo[1].'';
                                        }
                                }
                        }
		}
		$tmparr['width'] = $val['width'];
		$tmparr['height'] = $val['height'];
		$attachs[$aid] = $tmparr;
	}
}
$result['attachments'] = $attachs;
BfdApp::display_result('get_success',$result);
