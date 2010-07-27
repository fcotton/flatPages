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
if (!defined('DC_CONTEXT_ADMIN')) return;

$what = (!empty($_REQUEST['do']) && $_REQUEST['do'] == 'edit') ? 'page' : 'panel';
if ($what == 'page') {
	require_once dirname(__FILE__).'/inc/page.php';
}
else {
	require_once dirname(__FILE__).'/inc/panel.php';
}
?>