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

class rsFlatpage extends rsFlatpageBase
{
	public static function isEditable($rs)
	{
		if ($rs->core->auth->check('contentadmin',$rs->core->blog->id)) {
			return true;
		}
		
		if (!$rs->exists('user_id')) {
			return false;
		}
		
		if ($rs->core->auth->check('pages',$rs->core->blog->id)
		&& $rs->user_id == $rs->core->auth->userID()) {
			return true;
		}
		
		return false;
	}
	
	public static function isDeletable($rs)
	{
		if ($rs->core->auth->check('contentadmin',$rs->core->blog->id)) {
			return true;
		}
		
		if (!$rs->exists('user_id')) {
			return false;
		}
		
		if ($rs->core->auth->check('pages',$rs->core->blog->id)
		&& $rs->user_id == $rs->core->auth->userID()) {
			return true;
		}
		
		return false;
	}
}

class adminFlatPagesList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if ($this->rs->isEmpty()) {
			echo '<p><strong>'.__('No page').'</strong></p>';
		}
		else {
			$pager = new pager($page,$this->rs_count,$nb_per_page,10);
			$pager->var_page = 'page';
			
			$html_block =
			'<table class="clear"><tr>'.
			'<th colspan="2">'.__('Title').'</th>'.
			'<th>'.__('Slug').'</th>'.
			'<th>'.__('Template').'</th>'.
			'<th>'.__('Date').'</th>'.
			'<th>'.__('Author').'</th>'.
			'<th>'.__('Comments').'</th>'.
			'<th>'.__('Trackbacks').'</th>'.
			'<th>'.__('Status').'</th>'.
			'</tr>%s</table>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			$blocks = explode('%s',$html_block);	
			echo $blocks[0];
			while ($this->rs->fetch()) {
				echo $this->pageLine();
			}
			echo $blocks[1];
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	private function pageLine()
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case  1 : $img_status = sprintf($img,__('published'),'check-on.png'); break;
			case  0 : $img_status = sprintf($img,__('unpublished'),'check-off.png'); break;
			case -2 : $img_status = sprintf($img,__('pending'),'check-wrn.png'); break;
		}
		
		$protected = '';
		if ($this->rs->post_password) {
			$protected = sprintf($img,__('protected'),'locker.png');
		}
		
		$subtype = '(N/A)';
		$meta = new dcMeta($this->rs->core);
		$meta_rs = $this->rs->core->meta->getMetaRecordset($this->rs->post_meta,'template');
		$template = (!$meta_rs->isEmpty())?$meta_rs->meta_id:'flatpage.html';
		
		$res = '<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">';
		
		$res .=
		'<td class="nowrap">'.
		form::checkbox(array('entries[]'),$this->rs->post_id,'','','',!$this->rs->isEditable()).'</td>'.
		'<td class="maximal"><a href="plugin.php?p=flatPages&amp;do=edit&amp;id='.$this->rs->post_id.'">'.
		html::escapeHTML($this->rs->post_title).'</a></td>'.
		'<td class="nowrap">'.$this->rs->post_url.'</td>'.
		'<td class="nowrap">'.$template.'</td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>'.
		'<td class="nowrap">'.$this->rs->user_id.'</td>'.
		'<td class="nowrap">'.$this->rs->nb_comment.'</td>'.
		'<td class="nowrap">'.$this->rs->nb_trackback.'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$protected.'</td>'.
		'</tr>';
		
		return $res;
	}
}
?>