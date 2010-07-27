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

// Public behaviors definition and binding
/**
 * 
 */
class flatpagesPublicBehaviors
{
	/**
	 * 
	 */
	public static function addTplPath($core)
	{
		$core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/default-templates');
	}

	/**
	 * 
	 */
	public static function templateBeforeBlock()
	{
		$args = func_get_args();
		array_shift($args);
		
		if ($args[0] == 'Entries') {
			if (!empty($args[1])) {
				$attrs = $args[1];
				if (!empty($attrs['type']) && $attrs['type'] == 'flatpage') {
					$p = "<?php \$params['post_type'] = 'flatpage'; ?>\n";
					if (!empty($attrs['basename'])) {
						$p .= "<?php \$params['post_url'] = '".$attrs['basename']."'; ?>\n";
					}
					return $p;
				}
			}
		}
	}

	/**
	 * 
	 */
	public static function coreBlogGetPosts($rs)
	{
		$rs->extend("rsFlatpageBase");
	}

	/**
	 * 
	 */
	public static function sitemapsURLsCollect($sitemaps)
	{
		global $core;
		
		if ($core->blog->settings->sitemaps->sitemaps_flatpages_url) {
			$freq = $sitemaps->getFrequency($core->blog->settings->sitemaps->sitemaps_flatpages_fq);
			$prio = $sitemaps->getPriority($core->blog->settings->sitemaps->sitemaps_flatpages_pr);

			$rs = $core->blog->getPosts(array('post_type' => 'flatpage','post_status' => 1,'no_content' => true));
			$rs->extend('rsFlatpages');
				
			while ($rs->fetch()) {
				if ($rs->post_password != '') continue;
				$sitemaps->addEntry($rs->getURL(),$prio,$freq,$rs->getISO8601Date());
			}
		}
	}
	
	public static function initCommentsWikibar($modes)
	{
		$modes[] = 'flatpage';
	}
}

$core->addBehavior('coreBlogGetPosts',    array('flatpagesPublicBehaviors','coreBlogGetPosts'));
$core->addBehavior('publicBeforeDocument',array('flatpagesPublicBehaviors','addTplPath'));
$core->addBehavior('templateBeforeBlock', array('flatpagesPublicBehaviors','templateBeforeBlock'));
$core->addBehavior('sitemapsURLsCollect', array('flatpagesPublicBehaviors','sitemapsURLsCollect'));
$core->addBehavior('initCommentsWikibar', array('flatpagesPublicBehaviors','initCommentsWikibar'));
?>