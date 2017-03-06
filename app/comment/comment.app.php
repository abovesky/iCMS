<?php
/**
 * @package iCMS
 * @copyright 2007-2017, iDreamSoft
 * @license http://www.idreamsoft.com iDreamSoft
 * @author coolmoo <idreamsoft@qq.com>
 */
defined('iPHP') OR exit('What are you doing?');

class commentApp {
	public $methods = array('like','widget', 'json', 'add', 'form', 'list', 'redirect');
	public $config  = null;
	public function __construct() {
		$this->config = iCMS::$config['comment'];
		$this->id     = (int) $_GET['id'];
	}
	public function API_redirect() {
		$appid = (int) $_GET['appid'];
		$iid   = (int) $_GET['iid'];
		// $_GET  = iSecurity::escapeStr($_GET);

		$url = apps::get_url($appid, $iid);
		iPHP::redirect($url);
	}
	public function API_widget() {
		$name = iSecurity::escapeStr($_GET['name']);
		iView::render('iCMS://comment/widget.'.$name.'.htm');
	}
	public function API_list() {
		$_GET['_display'] = $_GET['display'];
		$_GET['display'] = 'default';
		$_GET = iSecurity::escapeStr($_GET);
		return commentFunc::comment_list($_GET);
	}
	public function API_form() {
		$_GET['_display'] = $_GET['display'];
		$_GET['display'] = 'default';
		$_GET = iSecurity::escapeStr($_GET);
		return commentFunc::comment_form($_GET);
	}

	public function API_like() {
		// user::get_cookie() OR iUI::code(0,'iCMS:!login',0,'json');

		$this->id OR iUI::code(0, 'iCMS:article:empty_id', 0, 'json');
		$lckey = 'like_comment_' . $this->id;
		$like = iPHP::get_cookie($lckey);
		$like && iUI::code(0, 'iCMS:comment:!like', 0, 'json');
		//$ip = iPHP::get_ip();
		iDB::query("UPDATE `#iCMS@__comment` SET `up`=up+1 WHERE `id`='$this->id'");
		iPHP::set_cookie($lckey, $_SERVER['REQUEST_TIME'], 86400);
		iUI::code(1, 'iCMS:comment:like', 0, 'json');
	}
	public function API_json() {
		$vars = array(
			'appid' => iCMS_APP_ARTICLE,
			'id' => (int) $_GET['id'],
			'iid' => (int) $_GET['iid'],
			'date_format' => 'Y-m-d H:i',
		);
		$_GET['by'] && $vars['by'] = iSecurity::escapeStr($_GET['by']);
		$_GET['date_format'] && $vars['date_format'] = iSecurity::escapeStr($_GET['date_format']);
		$vars['page'] = true;
		// $array = comment_list($vars);
		// iUI::json($array);
		iView::assign('vars',$vars);
		iView::render('iCMS://comment/api.json.htm');
	}
	public function pm($a) {
		$fields = array('send_uid', 'send_name', 'receiv_uid', 'receiv_name', 'content');
		$data = compact($fields);
		message::send($data, 1);
	}
	public static function redirect_url($var) {
		$url = iCMS_API.'?app=comment&do=redirect&iid='.$var['iid'].'&appid='.$var['appid'].'&cid='.$var['cid'];
		return $url;
	}
	public function ACTION_add() {
		if (!$this->config['enable']) {
			iUI::code(0, 'iCMS:comment:close', 0, 'json');
		}

		user::get_cookie() OR iUI::code(0, 'iCMS:!login', 0, 'json');

		if ($this->config['seccode']) {
			$seccode = iSecurity::escapeStr($_POST['seccode']);
			iSeccode::check($seccode, true) OR iUI::code(0, 'iCMS:seccode:error', 'seccode', 'json');
		}

		$appid = (int) $_POST['appid'];
		$iid = (int) $_POST['iid'];
		$cid = (int) $_POST['cid'];
		$suid = (int) $_POST['suid'];
		$reply_id = (int) $_POST['id'];
		$reply_uid = (int) $_POST['userid'];
		$reply_name = iSecurity::escapeStr($_POST['name']);
		$title = iSecurity::escapeStr($_POST['title']);
		$content = iSecurity::escapeStr($_POST['content']);
		$iid OR iUI::code(0, 'iCMS:article:empty_id', 0, 'json');
		$content OR iUI::code(0, 'iCMS:comment:empty', 0, 'json');

		$fwd = iPHP::callback(array("filterApp","run"),array(&$content));
		$fwd && iUI::code(0, 'iCMS:comment:filter', 0, 'json');

		$appid OR $appid = iCMS_APP_ARTICLE;
		$addtime = $_SERVER['REQUEST_TIME'];
		$ip = iPHP::get_ip();
		$userid = user::$userid;
		$username = user::$nickname;
		$status = $this->config['examine'] ? '0' : '1';
		$up = '0';
		$down = '0';
		$quote = '0';
		$floor = '0';

		$fields = array('appid', 'cid', 'iid', 'suid', 'title', 'userid', 'username', 'content', 'reply_id', 'reply_uid', 'reply_name', 'addtime', 'status', 'up', 'down', 'ip', 'quote', 'floor');
		$data = compact($fields);
		$id = iDB::insert('comment', $data);
		iDB::query("UPDATE `#iCMS@__article` SET comments=comments+1 WHERE `id` ='{$iid}' limit 1");
		user::update_count($userid, 1, 'comments');
		if ($this->config['examine']) {
			iUI::code(0, 'iCMS:comment:examine', $id, 'json');
		}
		iUI::code(1, 'iCMS:comment:success', $id, 'json');
	}

}
