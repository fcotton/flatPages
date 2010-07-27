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
		
		if (!$_ctx->posts->isEmpty()) {
			$tpl = $_ctx->posts->getSpecificTemplate();
			if (!$tpl || !$core->tpl->getFilePath($tpl)) {
				$tpl = 'flatpage.html';
			}
			$core->url->type = 'flatpage';
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