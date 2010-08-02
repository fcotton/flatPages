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

dcPage::check('pages,contentadmin');

$default_tab = 'pages_compose';

/**
 * Build "Manage Pages" tab
 */
$params = array(
	'post_type' => 'flatpage'
);

$page = !empty($_GET['page']) ? $_GET['page'] : 1;
$nb_per_page =  30;
if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
	$nb_per_page = (integer) $_GET['nb'];
}

$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
$params['no_content'] = true;

# Get pages
try {
	$pages = $core->blog->getPosts($params);
	$pages->extend("rsFlatpage");
	$counter = $core->blog->getPosts($params,true);
	$page_list = new adminFlatPagesList($core,$pages,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Actions combo box
$combo_action = array();
if ($core->auth->check('publish,contentadmin',$core->blog->id)) {
	$combo_action[__('publish')] = 'publish';
	$combo_action[__('unpublish')] = 'unpublish';
	$combo_action[__('mark as pending')] = 'pending';
}
if ($core->auth->check('admin',$core->blog->id)) {
	$combo_action[__('change author')] = 'author';
}
if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
	$combo_action[__('delete')] = 'delete';
}

# --BEHAVIOR-- adminPagesActionsCombo
$core->callBehavior('adminPagesActionsCombo',array(&$combo_action));


/**
 * Display full panel
 */
?>
<html>
	<head>
		<title><?php echo __('FlatPages'); ?></title>
		<?php
		echo
		dcPage::jsToolMan().
		dcPage::jsPageTabs($default_tab).
		dcPage::jsLoad('js/_posts_list.js');
		?>
	</head>
	<body>
		<h2><?php echo html::escapeHTML($core->blog->name); ?> &rsaquo; <?php echo __('FlatPages'); ?></h2>
		<?php if (!empty($msg)) echo '<p class="message">'.$msg.'</p>'; ?>

<?php
// "Manage Pages" tab
echo
'<div class="multi-part" id="pages_compose" title="'.__('Manage pages').'">'.
'<p><a class="button" href="plugin.php?p=flatPages&amp;do=edit">'.__('New flatpage').'</a>&nbsp;</p>';

if (!$core->error->flag())
{
	$page_list->display($page,$nb_per_page,
	'<form action="posts_actions.php" method="post" id="form-entries">'.
	'%s'.
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	'<p class="col right">'.__('Selected entries action:').
	form::combo('action',$combo_action).
	'<input type="submit" value="'.__('ok').'" /></p>'.
	form::hidden(array('post_type'),'flatpage').
	form::hidden(array('redir'),html::escapeHTML($_SERVER['REQUEST_URI'])).
	$core->formNonce().
	'</div>'.
	'</form>'
	);
}
echo '</div>';

echo dcPage::helpBlock('flatpages');
?>
	</body>
</html>