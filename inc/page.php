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

$redir_url = $p_url.'&do=edit';

$post_id = '';
$cat_id = '';
$post_dt = '';
$post_type = 'flatpage';
$post_format = $core->auth->getOption('post_format');
$post_password = '';
$post_url = '';
$post_lang = $core->auth->getInfo('user_lang');
$post_title = '';
$post_excerpt = '';
$post_excerpt_xhtml = '';
$post_content = '';
$post_content_xhtml = '';
$post_notes = '';
$post_status = $core->auth->getInfo('user_post_status');
$post_selected = true;
$post_open_comment = false;
$post_open_tb = false;
$page_template = '';

$page_title = __('New flatpage');

$can_view_page = true;
$can_edit_post = $core->auth->check('contentadmin,pages',$core->blog->id);
$can_publish = $core->auth->check('contentadmin',$core->blog->id);
$can_delete = false;
$preview = false;

# If user can't publish
if (!$can_publish) {
	$post_status = -2;
}

# Status combo
foreach ($core->blog->getAllPostStatus() as $k => $v) {
	$status_combo[$v] = (string) $k;
}

# Formaters combo
foreach ($core->getFormaters() as $v) {
	$formaters_combo[$v] = $v;
}

# Get entry informations
if (!empty($_REQUEST['id']))
{
	$params = array();
	$params['post_id'] = $_REQUEST['id'];
	$params['post_type'] = 'flatpage';
	
	$post = $core->blog->getPosts($params, false);
	$post->extend("rsFlatpage");
	
	if ($post->isEmpty())
	{
		$core->error->add(__('This page does not exist.'));
		$can_view_page = false;
	}
	else
	{
		$post_id = $post->post_id;
		$cat_id = $post->cat_id;
		$post_dt = date('Y-m-d H:i',strtotime($post->post_dt));
		$post_format = $post->post_format;
		$post_password = $post->post_password;
		$post_url = $post->post_url;
		$post_lang = $post->post_lang;
		$post_title = $post->post_title;
		$post_excerpt = $post->post_excerpt;
		$post_excerpt_xhtml = $post->post_excerpt_xhtml;
		$post_content = $post->post_content;
		$post_content_xhtml = $post->post_content_xhtml;
		$post_notes = $post->post_notes;
		$post_status = $post->post_status;
		$post_selected = (boolean) $post->post_selected;
		$post_open_comment = (boolean) $post->post_open_comment;
		$post_open_tb = (boolean) $post->post_open_tb;
		
		$page_title = __('Edit page');
		
		$can_edit_post = $post->isEditable();
		$can_delete = $post->isDeletable();

		try {
			$post_metas = $core->meta->getMetaRecordset($post->post_meta,'template');
			if (!$post_metas->isEmpty()) {
				$page_template = $post_metas->meta_id;
			}
		} catch (Exception $e) {}
		
	}
}

# Format excerpt and content
if (!empty($_POST) && $can_edit_post)
{
	$post_format = $_POST['post_format'];
	$post_excerpt = $_POST['post_excerpt'];
	$post_content = $_POST['post_content'];
	$post_title = $_POST['post_title'];
	$page_template = $_POST['page_template'];
	$post_open_comment = !empty($_POST['post_open_comment']);
	$post_open_tb = !empty($_POST['post_open_tb']);
		
	if (isset($_POST['post_status'])) {
		$post_status = (integer) $_POST['post_status'];
	}
	
	if (empty($_POST['post_dt'])) {
		$post_dt = '';
	} else {
		$post_dt = strtotime($_POST['post_dt']);
		$post_dt = date('Y-m-d H:i',$post_dt);
	}
	
	$post_lang = $_POST['post_lang'];
	$post_password = !empty($_POST['post_password']) ? $_POST['post_password'] : null;
	
	$post_notes = $_POST['post_notes'];
	
	if (isset($_POST['post_url'])) {
		$post_url = $_POST['post_url'];
	}
	
	$core->blog->setPostContent(
		$post_id,$post_format,$post_lang,
		$post_excerpt,$post_excerpt_xhtml,$post_content,$post_content_xhtml
	);
	
	$preview = !empty($_POST['preview']);
}

# Create or update post
if (!empty($_POST) && !empty($_POST['save']) && $can_edit_post)
{
	$cur = $core->con->openCursor($core->prefix.'post');
	
	$cur->post_title = $post_title;
	$cur->cat_id = null;
	$cur->post_dt = $post_dt ? date('Y-m-d H:i:00',strtotime($post_dt)) : '';
	$cur->post_type = $post_type;
	$cur->post_format = $post_format;
	$cur->post_password = $post_password;
	$cur->post_lang = $post_lang;
	$cur->post_type = $post_type;
	$cur->post_excerpt = $post_excerpt;
	$cur->post_excerpt_xhtml = $post_excerpt_xhtml;
	$cur->post_content = $post_content;
	$cur->post_content_xhtml = $post_content_xhtml;
	$cur->post_notes = $post_notes;
	$cur->post_status = $post_status;
	$cur->post_selected = (integer)$post_selected;
	$cur->post_open_comment = (integer) $post_open_comment;
	$cur->post_open_tb = (integer) $post_open_tb;
	
	if (isset($_POST['post_url'])) {
		$cur->post_url = $post_url;
	}
		
	# Update post
	if ($post_id) {
		try {
			# --BEHAVIOR-- adminBeforePostUpdate
			$core->callBehavior('adminBeforePostUpdate',$cur,$post_id);
			# --BEHAVIOR-- adminBeforePageUpdate
			$core->callBehavior('adminBeforePageUpdate',$cur,$post_id);
			
			$core->con->begin();
			$core->blog->updPost($post_id,$cur);
			if ($page_template) {
				try {
					$core->meta->delPostMeta($post_id,'template');
					$core->meta->setPostMeta($post_id,'template',$page_template);
				}
				catch (Exception $e) {
					$core->con->rollback();
					throw $e;
				}
			}
			$core->con->commit();
			
			# --BEHAVIOR-- adminAfterPostUpdate
			$core->callBehavior('adminAfterPostUpdate',$cur,$post_id);
			# --BEHAVIOR-- adminAfterPageUpdate
			$core->callBehavior('adminAfterPageUpdate',$cur,$post_id);
			
			http::redirect('plugin.php?p=flatPages&do=edit&id='.$post_id.'&upd=1');
		}
		catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
	else	{
		$cur->user_id = $core->auth->userID();
		if (!isset($_POST['post_url'])) {
			$cur->post_url = text::str2URL($post_title);
		}
		
		try {
			# --BEHAVIOR-- adminBeforePostCreate
			$core->callBehavior('adminBeforePostCreate',$cur);
			# --BEHAVIOR-- adminBeforePageCreate
			$core->callBehavior('adminBeforePageCreate',$cur);
			
			$core->con->begin();
			$return_id = $core->blog->addPost($cur);
			if ($page_isfile) {
				try {
					$core->meta->setPostMeta($return_id,'template',$page_template);
				}
				catch (Exception $e) {
					$core->con->rollback();
					throw $e;
				}
			}
			$core->con->commit();
			
			# --BEHAVIOR-- adminAfterPostCreate
			$core->callBehavior('adminAfterPostCreate',$cur,$return_id);
			# --BEHAVIOR-- adminAfterPageCreate
			$core->callBehavior('adminAfterPageCreate',$cur,$return_id);
			
			http::redirect('plugin.php?p=flatPages&do=edit&id='.$return_id.'&crea=1');
		}
		catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
}

if (!empty($_POST['delete']) && $can_delete) {
	try {
		$core->blog->delPost($post_id);
		http::redirect($p_url);
	}
	catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

/* DISPLAY
-------------------------------------------------------- */
$default_tab = 'edit-entry';
if (!empty($_GET['co'])) {
	$default_tab = 'comments';
}

?>
<html>
<head>
	<title><?php echo __('Related pages'); ?></title>
<?php
echo dcPage::jsDatePicker().
	dcPage::jsToolBar().
  	dcPage::jsModal().
	dcPage::jsLoad('js/_post.js').
	dcPage::jsConfirmClose('entry-form').
	# --BEHAVIOR-- adminRelatedHeaders
	$core->callBehavior('adminFlatPagesHeaders').	
	dcPage::jsPageTabs($default_tab);
?>
</head>

<body>
<?php
if (!empty($_GET['upd'])) {
		echo '<p class="message">'.__('Page has been successfully updated.').'</p>';
}
elseif (!empty($_GET['crea'])) {
		echo '<p class="message">'.__('Page has been successfully created.').'</p>';
}

echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <a href="'.$p_url.'">'.__('FlatPages').'</a> &rsaquo; '.$page_title;

if ($post_id && $post->post_status == 1) {
	echo ' - <a id="post-preview" href="'.$post->getURL().'" class="button">'.__('View page').'</a>';
} elseif ($post_id) {
	$preview_url =
	$core->blog->url.$core->url->getBase('flatpagepreview').'/'.
	$core->auth->userID().'/'.
	http::browserUID(DC_MASTER_KEY.$core->auth->userID().$core->auth->getInfo('user_pwd')).
	'/'.$post->post_url;
	echo ' - <a id="post-preview" href="'.$preview_url.'" class="button">'.__('Preview page').'</a>';
}
echo '</h2>';

# Exit if we cannot view page
if (!$can_view_page) {
	exit;
}

/* Page form if we can edit page
-------------------------------------------------------- */
if ($can_edit_post)
{
	echo '<div class="multi-part" title="'.__('Edit page').'" id="edit-entry">';
	echo '<form action="plugin.php?p=flatPages&amp;do=edit" method="post" id="entry-form">';
	echo '<div id="entry-sidebar">';
	
	echo
	'<p><label>'.__('Page status:').dcPage::help('post','p_status').
	form::combo('post_status',$status_combo,$post_status,'',3,!$can_publish).
	'</label></p>'.
	
	'<p><label>'.__('Text formating:').dcPage::help('post','p_format').
	form::combo('post_format',$formaters_combo,$post_format,'',3).
	'</label></p>'.
	
	'<div class="lockable">'.
	'<p><label>'.__('Basename:').dcPage::help('post','p_basename').
	form::field('post_url',10,255,html::escapeHTML($post_url),'maximal',3).
	'</label></p>'.
	'<p class="form-note warn">'.
	__('Warning: If you set the URL manually, it may conflict with another entry.').
	'</p>'.
	'</div>'.
	
	'<p><label>'.__('Template:').dcPage::help('post','p_template').
	form::field('page_template',10,255,html::escapeHTML($page_template),'maximal',3).
	'</label></p>'.
	
	'<p><label class="classic">'.form::checkbox('post_open_comment',1,$post_open_comment,'',3).' '.
	__('Accept comments').'</label></p>'.
	'<p><label class="classic">'.form::checkbox('post_open_tb',1,$post_open_tb,'',3).' '.
	__('Accept trackbacks').'</label></p>';	

	echo	
	'<div id="page_options">'.
	'<h3 style="display:inline;">'.__('More options').'</h3>'.
	'<p><label>'.__('Page lang:').dcPage::help('post','p_lang').
	form::field('post_lang',5,255,html::escapeHTML($post_lang),'',3).
	'</label></p>'.

	'<p><label>'.__('Published on:').dcPage::help('post','p_date').
	form::field('post_dt',16,16,$post_dt,'',3).
	'</label></p>'.
	
	'<p><label>'.__('Page password:').dcPage::help('post','p_password').
	form::field('post_password',10,32,html::escapeHTML($post_password),'maximal',3).
	'</label></p>'.	
	'</div>';
		
	# --BEHAVIOR-- adminPageFormSidebar
	$core->callBehavior('adminPageFormSidebar',isset($post) ? $post : null);
	
	echo '</div>';		// End #entry-sidebar
	
	echo '<div id="entry-content"><fieldset class="constrained">';
	
	echo
	'<p class="col"><label class="required" title="'.__('Required field').'">'.__('Title:').
	dcPage::help('post','p_title').
	form::field('post_title',20,255,html::escapeHTML($post_title),'maximal',2).
	'</label></p>'.
	
	'<p class="area" id="excerpt-area"><label for="post_excerpt">'.__('Summary:').
	dcPage::help('post','p_excerpt').'</label> '.
	form::textarea('post_excerpt',50,5,html::escapeHTML($post_excerpt),'',2).
	'</p>'.

	'<p class="area"><label class="required" title="'.__('Required field').'" '.
	'for="post_content">'.__('Content:').
	dcPage::help('post','p_content').'</label> '.
	form::textarea('post_content',50,$core->auth->getOption('edit_size'),html::escapeHTML($post_content),'',2).
	'</p>';
			
	echo
	'<p class="area" id="notes-area"><label>'.__('Notes:').
	dcPage::help('post','p_notes').'</label>'.
	form::textarea('post_notes',50,5,html::escapeHTML($post_notes),'',2).
	'</p>';
	
	# --BEHAVIOR-- adminPageForm
	$core->callBehavior('adminPageForm',isset($post) ? $post : null);
	
	echo
	'<p>'.
	$core->formNonce().
	($post_id ? form::hidden('id',$post_id) : '').
	'<input type="submit" value="'.__('save').' (s)" tabindex="4" '.
	'accesskey="s" name="save" /> '.
	($can_delete ? '<input type="submit" value="'.__('delete').'" name="delete" />' : '').
	'</p>';
	
	echo '</fieldset></div>';		// End #entry-content
	echo '</form>';
	echo '</div>';
}


/* Comments and trackbacks
-------------------------------------------------------- */
if ($post_id)
{
	$params = array('post_id' => $post_id, 'order' => 'comment_dt ASC');
	
	$comments = $core->blog->getComments(array_merge($params,array('comment_trackback'=>0)));
	$trackbacks = $core->blog->getComments(array_merge($params,array('comment_trackback'=>1)));
	
	# Actions combo box
	$combo_action = array();
	if ($can_edit_post && $core->auth->check('publish,contentadmin',$core->blog->id))
	{
		$combo_action[__('publish')] = 'publish';
		$combo_action[__('unpublish')] = 'unpublish';
		$combo_action[__('mark as pending')] = 'pending';
		$combo_action[__('mark as junk')] = 'junk';
	}
	
	if ($can_edit_post && $core->auth->check('delete,contentadmin',$core->blog->id))
	{
		$combo_action[__('delete')] = 'delete';
	}
	
	$has_action = !empty($combo_action) && (!$trackbacks->isEmpty() || !$comments->isEmpty());
	
	echo
	'<div id="comments" class="multi-part" title="'.__('Comments').'">';
	
	if ($has_action) {
		echo '<form action="comments_actions.php" method="post">';
	}
	
	echo '<h3>'.__('Trackbacks').'</h3>';
	
	if (!$trackbacks->isEmpty()) {
		showComments($trackbacks,$has_action);
	} else {
		echo '<p>'.__('No trackback').'</p>';
	}
	
	echo '<h3>'.__('Comments').'</h3>';
	if (!$comments->isEmpty()) {
		showComments($comments,$has_action);
	} else {
		echo '<p>'.__('No comment').'</p>';
	}
	
	if ($has_action) {
		echo
		'<div class="two-cols">'.
		'<p class="col checkboxes-helpers"></p>'.
		
		'<p class="col right">'.__('Selected comments action:').' '.
		form::combo('action',$combo_action).
		form::hidden('redir',html::escapeURL($redir_url).'&amp;id='.$post_id.'&amp;co=1').
		$core->formNonce().
		'<input type="submit" value="'.__('ok').'" /></p>'.
		'</div>'.
		'</form>';
	}
	
	echo '</div>';
}

/* Add a comment
-------------------------------------------------------- */
if ($post_id)
{
	echo
	'<div class="multi-part" id="add-comment" title="'.__('Add a comment').'">'.
	'<h3>'.__('Add a comment').'</h3>'.
	
	'<form action="comment.php" method="post" id="comment-form">'.
	'<fieldset class="constrained">'.
	'<p><label class="required" title="'.__('Required field').'">'.__('Name:').
	form::field('comment_author',30,255,html::escapeHTML($core->auth->getInfo('user_cn'))).
	'</label></p>'.
	
	'<p><label>'.__('Email:').
	form::field('comment_email',30,255,html::escapeHTML($core->auth->getInfo('user_email'))).
	'</label></p>'.
	
	'<p><label>'.__('Web site:').
	form::field('comment_site',30,255,html::escapeHTML($core->auth->getInfo('user_url'))).
	'</label></p>'.
	
	'<p class="area"><label for="comment_content" class="required" title="'.
	__('Required field').'">'.__('Comment:').'</label> '.
	form::textarea('comment_content',50,8,html::escapeHTML('')).
	'</p>'.
	
	'<p>'.form::hidden('post_id',$post_id).
	$core->formNonce().
	'<input type="submit" name="add" value="'.__('save').'" /></p>'.
	'</fieldset>'.
	'</form>'.
	'</div>';
}


# Show comments or trackbacks
function showComments($rs,$has_action)
{
	echo
	'<table class="comments-list"><tr>'.
	'<th colspan="2">'.__('Author').'</th>'.
	'<th>'.__('Date').'</th>'.
	'<th class="nowrap">'.__('IP address').'</th>'.
	'<th>'.__('Status').'</th>'.
	'<th>&nbsp;</th>'.
	'</tr>';
	
	while($rs->fetch())
	{
		$comment_url = 'comment.php?id='.$rs->comment_id;
		
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($rs->comment_status) {
			case 1:
				$img_status = sprintf($img,__('published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('pending'),'check-wrn.png');
				break;
			case -2:
				$img_status = sprintf($img,__('junk'),'junk.png');
				break;
		}
		
		echo
		'<tr class="line'.($rs->comment_status != 1 ? ' offline' : '').'"'.
		' id="c'.$rs->comment_id.'">'.
		
		'<td class="nowrap">'.
		($has_action ? form::checkbox(array('comments[]'),$rs->comment_id,'','','',0) : '').'</td>'.
		'<td class="maximal">'.$rs->comment_author.'</td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$rs->comment_dt).'</td>'.
		'<td class="nowrap"><a href="comments.php?ip='.$rs->comment_ip.'">'.$rs->comment_ip.'</a></td>'.
		'<td class="nowrap status">'.$img_status.'</td>'.
		'<td class="nowrap status"><a href="'.$comment_url.'">'.
		'<img src="images/edit-mini.png" alt="" title="'.__('Edit this comment').'" /></a></td>'.
		
		'</tr>';
	}
	
	echo '</table>';
}
?>
	</body>
</html>