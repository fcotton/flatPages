$(function() {
	if (!document.getElementById) { return; }
	
	if (document.getElementById('edit-entry'))
	{
		// Default hreflang
		var langField = document.getElementById('post_lang');
		jsToolBar.prototype.elements.link.default_hreflang = langField.value;
		
		// Get document format and prepare toolbars
		var formatField = $('#post_format').get(0);
		$(formatField).change(function() {
			excerptTb.switchMode(this.value);
			contentTb.switchMode(this.value);
		});
		
		var excerptTb = new jsToolBar(document.getElementById('post_excerpt'));
		var contentTb = null;
		if (contentArea = document.getElementById('post_content')) {
			contentTb = new jsToolBar(contentArea);
			contentTb.context = 'post';
		}
		excerptTb.context = 'post';
	}

	// Post preview
	$('#post-preview').modalWeb($(window).width()-40,$(window).height()-40);
	
	// Tabs events
	$('#edit-entry').onetabload(function() {
		dotclear.hideLockable();
		
		// Add date picker
		var post_dtPick = new datePicker($('#post_dt').get(0));
		post_dtPick.img_top = '1.5em';
		post_dtPick.draw();
		
		// Hide some fields
		$('#notes-area label').toggleWithLegend($('#notes-area').children().not('label'),{
			cookie: 'dcx_post_notes',
			hide: $('#post_notes').val() == ''
		});
		$('#page_options h3').parent().toggleWithLegend($('#page_options').children().not('h3'),{
			cookie: 'dcx_page_options'
		});
		
		// We load toolbar on excerpt only when it's ready
		$('#excerpt-area label').toggleWithLegend($('#excerpt-area').children().not('label'),{
			fn: function() { excerptTb.switchMode(formatField.value); },
			cookie: 'dcx_post_excerpt',
			hide: $('#post_excerpt').val() == ''
		});
		
		// Load toolbars
		if (contentTb) {
			contentTb.switchMode(formatField.value);
		}
		
		// Replace attachment remove links by a POST form submit
		$('a.attachment-remove').click(function() {
			this.href = '';
			if (window.confirm(dotclear.msg.confirm_remove_attachment)) {
				var f = $('#attachment-remove-hide').get(0);
				f.elements['media_id'].value = this.id.substring(11);
				f.submit();
			}
			return false;
		});
	});
});