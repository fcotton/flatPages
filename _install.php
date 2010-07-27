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

$this_version = $core->plugins->moduleInfo('flatPages','version');
$installed_version = $core->getVersion('flatPages');
if (version_compare($installed_version,$this_version,'>=')) {
	return;
}

$core->blog->settings->addNamespace('flatpages');
$core->setVersion('flatPages',$this_version);
return true;
?>