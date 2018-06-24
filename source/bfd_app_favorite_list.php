<?php

/*
$do = 'favorite';
$uid = empty($_GET['uid']) ? 0 : intval($_GET['uid']);

$member = array();

if(empty($uid)) $uid = $_G['uid'];

if($uid && empty($member)) {
	$space = getuserbyuid($uid, 1);
	if(empty($space)) {
		showmessage('space_does_not_exist');
	}
} else {
	$space = &$member;
}
*/

$space = getuserbyuid($_G['uid']);
if(empty($space)) {
	BfdApp::display_result('space_does_not_exist');
}

$page = empty($_GET['page'])?1:intval($_GET['page']);
if($page<1) $page=1;
$id = empty($_GET['id'])?0:intval($_GET['id']);

$perpage = 20;

$_G['disabledwidthauto'] = 0;

$start = ($page-1)*$perpage;
ckstart($start, $perpage);

$idtypes = array('thread'=>'tid', 'forum'=>'fid', 'blog'=>'blogid', 'group'=>'gid', 'album'=>'albumid', 'space'=>'uid', 'article'=>'aid');
$_GET['type'] = isset($idtypes[$_GET['type']]) ? $_GET['type'] : 'all';
$actives[$_GET['type']] = ' class="a"';

$gets = array(
	'mod' => 'space',
	'uid' => $space['uid'],
	'do' => 'favorite',
	'type' => $_GET['type'],
	'from' => $_GET['from']
);
$theurl = 'home.php?'.url_implode($gets);

$wherearr = $list = array();
$favid = empty($_GET['favid'])?0:intval($_GET['favid']);
$idtype = isset($idtypes[$_GET['type']]) ? $idtypes[$_GET['type']] : '';

$count = C::t('home_favorite')->count_by_uid_idtype($_G['uid'], $idtype, $favid);
$list = array();
$threadlist = array();
if($count) {
	$icons = array(
		'tid'=>'<img src="static/image/feed/thread.gif" alt="thread" class="t" /> ',
		'fid'=>'<img src="static/image/feed/discuz.gif" alt="forum" class="t" /> ',
		'blogid'=>'<img src="static/image/feed/blog.gif" alt="blog" class="t" /> ',
		'gid'=>'<img src="static/image/feed/group.gif" alt="group" class="t" /> ',
		'uid'=>'<img src="static/image/feed/profile.gif" alt="space" class="t" /> ',
		'albumid'=>'<img src="static/image/feed/album.gif" alt="album" class="t" /> ',
		'aid'=>'<img src="static/image/feed/article.gif" alt="article" class="t" /> ',
	);
	$articles = array();
	$tidarr = array();
	foreach(C::t('home_favorite')->fetch_all_by_uid_idtype($_G['uid'], $idtype, $favid, $start,$perpage) as $value) {
//		$value['icon'] = isset($icons[$value['idtype']]) ? $icons[$value['idtype']] : '';
//		$value['url'] = makeurl($value['id'], $value['idtype'], $value['spaceuid']);
		$value['description'] = !empty($value['description']) ? nl2br($value['description']) : '';
		$list[$value['favid']] = $value;
		if($value['idtype'] == 'tid')
		{
			$tidarr[] = $value['id'];
		}
		if($value['idtype'] == 'aid') {
			$articles[$value['favid']] = $value['id'];
		}
	}
	$filterarr1['intids'] = $tidarr;
    $threadarray = C::t('forum_thread')->fetch_all_search($filterarr1);
	foreach($threadarray as $val)
	{
		$threadlist[$val['tid']] = $val;
	}	
/*
	if(!empty($articles)) {
		$_urls = array();
		foreach(C::t('portal_article_title')->fetch_all($articles) as $aid => $article) {
			$_urls[$aid] = fetch_article_url($article);
		}
		foreach ($articles as $favid => $aid) {
			$list[$favid]['url'] = $_urls[$aid];
		}
	}
*/
}
$pagetotal = 1;
if($count > 0)
{
	$pagetotal = ceil($count / $perpage);
}
$result = array();
foreach($list as $val)
{
	$tmp = array();
	$tmp['favid'] = $val['favid'];
	$tmp['uid'] = $val['uid'];
	$tmp['id'] = $val['id'];
	$tmp['idtype'] = $val['idtype'];
//	$tmp['spaceuid'] = $val['spaceuid'];
//	$tmp['title'] = BfdApp::bfd_html_entity_decode(strip_tags($val['title']));
//	$tmp['description'] = BfdApp::bfd_html_entity_decode(strip_tags($val['description']));
	$tmp['dateline'] = date('Y-m-d H:i:s',$val['dateline']);
	if(isset($threadlist[$val['id']]))
	{
		$thread = $threadlist[$val['id']];
		$tmp['tid'] = $thread['tid'];
		$tmp['fid'] = $thread['fid'];
        $tmp['author'] = $thread['author'];
        $tmp['authorid'] = $thread['authorid'];
        $tmp['subject'] = BfdApp::bfd_html_entity_decode($thread['subject']);
        $thread['lastpost'] = str_replace('"', '\'', dgmdate($thread['lastpost'], 'u', '9999', getglobal('setting/dateformat')));
        $tmp['lastpost'] = str_replace('&nbsp;',' ',strip_tags($thread['lastpost']));
        $tmp['lastposter'] = $thread['lastposter'];
        $tmp['views'] = $thread['views'];
        $tmp['replies'] = $thread['replies'];
        $tmp['displayorder'] = $thread['displayorder'];
        $tmp['typeid'] = $thread['typeid'];
        $tmp['digest'] = $thread['digest'];
        $tmp['ispicture'] = $thread['attachment'] == 2 ? 1:0;
	}
	else
	{
		$tmp['tid'] = $val['id'];
        $tmp['fid'] = 0;
        $tmp['author'] = '';
        $tmp['authorid'] = '';
        $tmp['subject'] = BfdApp::bfd_html_entity_decode($val['title']);
        $tmp['lastpost'] = '';
        $tmp['lastposter'] = '';
        $tmp['views'] = 0;
        $tmp['replies'] = 0;
        $tmp['displayorder'] = 0; 
        $tmp['typeid'] = 0;
        $tmp['digest'] = 0;
        $tmp['ispicture'] = 0;
	}
	$result[] = $tmp;
}
BfdApp::display_result('get_success',$result,'',$pagetotal);

function makeurl($id, $idtype, $spaceuid=0) {
	$url = '';
	switch($idtype) {
		case 'tid':
			$url = 'forum.php?mod=viewthread&tid='.$id;
			break;
		case 'fid':
			$url = 'forum.php?mod=forumdisplay&fid='.$id;
			break;
		case 'blogid':
			$url = 'home.php?mod=space&uid='.$spaceuid.'&do=blog&id='.$id;
			break;
		case 'gid':
			$url = 'forum.php?mod=group&fid='.$id;
			break;
		case 'uid':
			$url = 'home.php?mod=space&uid='.$id;
			break;
		case 'albumid':
			$url = 'home.php?mod=space&uid='.$spaceuid.'&do=album&id='.$id;
			break;
		case 'aid':
			$url = 'portal.php?mod=view&aid='.$id;
			break;
	}
	return $url;
}

?>
