<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of flatPages, a plugin for DotClear2.
# Copyright (c) 2010 Pep.
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
	public static function flatpages($args,$type,$e)
	{
		global $core, $_ctx;
		
		if ($e->getCode() == 404) {
			$core->blog->withoutPassword(false);
			
			$params = new ArrayObject();
			$params['post_type'] = 'flatpage';
			$params['post_url'] = $args;
			
			$_ctx->posts = $core->blog->getPosts($params);
			
			if (!$_ctx->posts->isEmpty()) {
				$tpl = $_ctx->posts->getSpecificTemplate();
				if (!$tpl || !$core->tpl->getFilePath($tpl)) {
					$tpl = 'flatpage.html';
				}
				/*
				header('Content-Type: text/html; charset=UTF-8');
				var_dump($args,$type,$e);
				/**/
				self::serveDocument($tpl);
				return true;
			}
		}
	}
}
$core->url->registerError(array('flatpagesUrlHandlers','flatpages'));


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