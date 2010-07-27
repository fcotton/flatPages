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
if (!defined('DC_CONTEXT_ADMIN')) return;

$_menu['Blog']->addItem(
	__('FlatPages'),
	'plugin.php?p=flatPages',
	'index.php?pf=flatPages/icon.png',
	preg_match('/plugin.php\?p=flatPages(&.*)?$/',$_SERVER['REQUEST_URI']),
	$core->auth->check('contentadmin,pages',$core->blog->id)
);

$core->auth->setPermissionType('pages',__('manage flatpages'));

/**
 * 
 */
class flatpagesAdminBehaviors
{
	/**
	 * 
	 */
	public static function sitemapsDefineParts($map)
	{	
		$map[__('FlatPages')] = 'flatpages';
	}

}

$core->addBehavior('sitemapsDefineParts',array('flatpagesAdminBehaviors','sitemapsDefineParts'));
?>
