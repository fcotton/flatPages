<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of flatPages, a plugin for DotClear2.
# Copyright (c) 2010 Pep and contributors.
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------
if (!defined('DC_RC_PATH')) return;

global $__autoload, $core;

$__autoload['rsFlatpage']		= dirname(__FILE__).'/inc/lib.flatpages.php';
$__autoload['adminFlatPagesList']	= dirname(__FILE__).'/inc/lib.flatpages.php';

// Registering new post_type
$core->setPostType('flatpage','plugin.php?p=flatPages&do=edit&id=%d','%s');

class flatpagesUrlHandlers extends dcUrlHandlers
{
	protected static function flatpage($args)
	{
		global $core, $_ctx;
		
		$params = new ArrayObject();
		$params['post_type'] = 'flatpage';
		$params['post_url'] = $args;

		$core->blog->withoutPassword(false);
		$_ctx->posts = $core->blog->getPosts($params);
		$core->blog->withoutPassword(true);
		
		if (!$_ctx->posts->isEmpty()) {
			$post_id = $_ctx->posts->post_id;
			$post_password = $_ctx->posts->post_password;
		
			if ($post_password != '' && !$_ctx->preview)	{
				if (isset($_COOKIE['dc_passwd'])) {
					$pwd_cookie = unserialize($_COOKIE['dc_passwd']);
				}
				else {
					$pwd_cookie = array();
				}
				if ((!empty($_POST['password']) && $_POST['password'] == $post_password) ||
					(isset($pwd_cookie[$post_id]) && $pwd_cookie[$post_id] == $post_password))	{
					$pwd_cookie[$post_id] = $post_password;
					setcookie('dc_passwd',serialize($pwd_cookie),0,'/');
				}
				else {
					self::serveDocument('password-form.html','text/html',false);
					return true;
				}
			}
			
			$_ctx->comment_preview = new ArrayObject();
			$_ctx->comment_preview['content'] = '';
			$_ctx->comment_preview['rawcontent'] = '';
			$_ctx->comment_preview['name'] = '';
			$_ctx->comment_preview['mail'] = '';
			$_ctx->comment_preview['site'] = '';
			$_ctx->comment_preview['preview'] = false;
			$_ctx->comment_preview['remember'] = false;

			$post_comment =
				isset($_POST['c_name']) && isset($_POST['c_mail']) &&
				isset($_POST['c_site']) && isset($_POST['c_content']) &&
				$_ctx->posts->commentsActive();
			
			if ($post_comment) {
				if (!empty($_POST['f_mail'])) {
					http::head(412,'Precondition Failed');
					header('Content-Type: text/plain');
					echo "So Long, and Thanks For All the Fish";
					exit;
				}
				
				$name = $_POST['c_name'];
				$mail = $_POST['c_mail'];
				$site = $_POST['c_site'];
				$content = $_POST['c_content'];
				$preview = !empty($_POST['preview']);
				
				if ($content != '') {
					if ($core->blog->settings->system->wiki_comments) {
						$core->initWikiComment();
					}
					else {
						$core->initWikiSimpleComment();
					}
					$content = $core->wikiTransform($content);
					$content = $core->HTMLfilter($content);
				}
				
				$_ctx->comment_preview['content'] = $content;
				$_ctx->comment_preview['rawcontent'] = $_POST['c_content'];
				$_ctx->comment_preview['name'] = $name;
				$_ctx->comment_preview['mail'] = $mail;
				$_ctx->comment_preview['site'] = $site;
				
				if ($preview) {
					# --BEHAVIOR-- publicBeforeCommentPreview
					$core->callBehavior('publicBeforeCommentPreview',$_ctx->comment_preview);
					$_ctx->comment_preview['preview'] = true;
				}
				else {
					$cur = $core->con->openCursor($core->prefix.'comment');
					$cur->comment_author = $name;
					$cur->comment_site = html::clean($site);
					$cur->comment_email = html::clean($mail);
					$cur->comment_content = $content;
					$cur->post_id = $_ctx->posts->post_id;
					$cur->comment_status = $core->blog->settings->system->comments_pub ? 1 : -1;
					$cur->comment_ip = http::realIP();
					
					$redir = $_ctx->posts->getURL();
					$redir .= strpos($redir,'?') !== false ? '&' : '?';
					
					try {
						if (!text::isEmail($cur->comment_email)) {
							throw new Exception(__('You must provide a valid email address.'));
						}
						# --BEHAVIOR-- publicBeforeCommentCreate
						$core->callBehavior('publicBeforeCommentCreate',$cur);
						if ($cur->post_id) {					
							$comment_id = $core->blog->addComment($cur);
							# --BEHAVIOR-- publicAfterCommentCreate
							$core->callBehavior('publicAfterCommentCreate',$cur,$comment_id);
						}
						if ($cur->comment_status == 1) {
							$redir_arg = 'pub=1';
						}
						else {
							$redir_arg = 'pub=0';
						}
						header('Location: '.$redir.$redir_arg);
					}
					catch (Exception $e) {
						$_ctx->form_error = $e->getMessage();
						$_ctx->form_error;
					}
				}
			}
			
			$tpl = $_ctx->posts->getSpecificTemplate();
			if (!$tpl || !$core->tpl->getFilePath($tpl)) {
				$tpl = 'flatpage.html';
			}
			$core->url->type = $_ctx->current_mode = 'flatpage';
			self::serveDocument($tpl);
		}
		else {
			self::p404();
		}
	}
	
	public static function flatpageMiddleware($args,$type,$e)
	{
		global $core, $_ctx;
		
		if ($e->getCode() == 404) {
			try {
				self::flatpage($args);
				return true;
			}
			catch (Exception $e) {}
		}
	}
	
	public static function preview($args)
	{
		global $core, $_ctx;
		
		if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#',$args,$m)) {
			self::default404(null,null,new Exception('Page not found',404));
		}
		else {
			$user_id = $m[1];
			$user_key = $m[2];
			$post_url = $m[3];
			if (!$core->auth->checkUser($user_id,null,$user_key)) {
				self::default404(null,null,new Exception('Page not found',404));
			}
			else {
				$_ctx->preview = true;
				self::flatpage($post_url);
			}
		}
	}
}
$core->url->registerError(array('flatpagesUrlHandlers','flatpageMiddleware'));
$core->url->register('flatpagepreview','flatprev','^flatprev/(.+)$',array('flatpagesUrlHandlers','preview'));

/**
 * 
 */
class rsFlatpageBase
{
	public static function getSpecificTemplate($rs)
	{
		$meta_rs = $rs->core->meta->getMetaRecordset($rs->post_meta,'template');
		if (!$meta_rs->isEmpty()) {
			return $meta_rs->meta_id;
		}
		return false;
	}
}
?>