var jbGetEditor = function() {
	var win = window.parent || window;
	if (typeof win.CKEDITOR !== 'undefined') {
		return 'ckeditor';
	} else if (typeof win.CodeMirror !== 'undefined') {
		return 'codemirror';
	} else if (typeof win.FCKeditor !== 'undefined') {
		return 'fckeditor';
	} else if (typeof win.quickPressLoad !== 'undefined') {
		return 'quickpress';
	} else if (typeof win.tinyMCE !== 'undefined' && (!win.tinyMCE.activeEditor || win.tinyMCE.activeEditor.isHidden())) {
		return 'text';
	} else if (typeof win.tinyMCE !== 'undefined' && win.tinyMCE.activeEditor && !win.tinyMCE.activeEditor.isHidden()) {
		return 'visual';
	} else {
		return 'unknown';
	}
};

var jbGetId = function() {
	return jQuery('.wp-media-buttons:eq(0) .add_media').attr('data-editor') || 'content';
};

var JB = window.JB || {};

JB.Gallery = function() {

	return {

		embed : function() {

			if (typeof jQuery === 'undefined') {
				return;
			}

			var win = window.parent || window;

			var editor = jbGetEditor();
			var id = jbGetId();

			if (editor === 'ckeditor' && (typeof win.CKEDITOR.instances.content === 'undefined' || win.CKEDITOR.instances.content.mode !== 'wysiwyg')) {
				win.alert('You must be in WYSIWYG edit mode to insert a Juicebox Gallery/Index shortcode tag.');
				return;
			}
			if (editor === 'codemirror') {
				win.alert('The CodeMirror editor is not supported by WP-Juicebox.');
				return;
			}
			if (editor === 'fckeditor' && win.FCKeditorAPI.GetInstance(id).EditMode !== 0) {
				win.alert('You must be in WYSIWYG edit mode to insert a Juicebox Gallery/Index shortcode tag.');
				return;
			}
			if (editor === 'unknown') {
				win.alert('WP-Juicebox is unable to determine the editor.');
				return;
			}

			var postContent = '';
			switch (editor) {
				case 'ckeditor':
					postContent = win.CKEDITOR.instances.content.getData();
					break;
				case 'fckeditor':
					postContent = win.FCKeditorAPI.GetInstance(id).GetData();
					break;
				case 'quickpress':
				case 'text':
					postContent = jQuery(win.edCanvas).val();
					break;
				case 'visual':
					postContent = win.tinyMCE.activeEditor.getContent();
					break;
				case 'codemirror':
				case 'unknown':
				default:
					break;
			}

			var pattern = new RegExp('\\[juicebox.*?gallery_id="([0-9]+)".*?\\]', 'gi');
			var matches = pattern.exec(postContent);

			if (matches) {
				win.alert('This ' + jbPostType + ' already contains a Juicebox Gallery shortcode tag.');
				return;
			}

			if (typeof this.configUrl !== 'string' || typeof win.tb_show !== 'function') {
				return;
			}

			var connector = this.configUrl.match(/\?/) ? '&' : '?';
			var url = this.configUrl + connector + 'TB_iframe=true&width=600&height=400';
			win.tb_show('Add Juicebox Gallery', url , false);
		}
	};
}();

JB.Gallery.Generator = function() {

	var insertTag = function(tag) {

		tag = tag || '';

		var win = window.parent || window;

		var editor = jbGetEditor();
		var id = jbGetId();

		switch (editor) {
			case 'ckeditor':
				win.CKEDITOR.instances.content.insertText(tag);
				break;
			case 'codemirror':
				break;
			case 'fckeditor':
				win.FCKeditorAPI.GetInstance(id).InsertHtml(tag);
				break;
			case 'quickpress':
			case 'text':
				if (typeof win.QTags !== 'undefined' && typeof win.QTags.insertContent === 'function') {
					win.QTags.insertContent(tag);
				} else if (typeof win.edInsertContent === 'function') {
					win.edInsertContent(win.edCanvas, tag);
				} else {
					win.alert("WP-Juicebox is unable to insert a Juicebox Gallery/Index shortcode tag.");
				}
				break;
			case 'visual':
				win.tinyMCE.activeEditor.focus();
				if (win.tinyMCE.isIE) {
					win.tinyMCE.activeEditor.selection.moveToBookmark(win.tinyMCE.EditorManager.activeEditor.windowManager.bookmark);
				}
				win.tinyMCE.activeEditor.execCommand('mceInsertContent', false, tag);
				break;
			case 'unknown':
			default:
				break;
		}

	};

	return {

		initialize : function() {

			if (typeof jQuery === 'undefined') {
				return;
			}

			jQuery('#jb-gallery-action input').click(function() {
				jQuery('#jb-gallery-action input').prop('disabled', true);
			});

			jQuery('#jb-add-gallery').click(function() {
				jQuery.post(JB.Gallery.Generator.postUrl, jQuery('#jb-add-gallery-form').serialize(), function(data) {
					if (data !== '') {
						var tag = '[juicebox gallery_id="' + data + '"]';
						insertTag(tag);
					} else {
						var win = window.parent || window;
						win.alert('WP-Juicebox is unable to determine the Gallery Id.');
					}
				}).fail(function() {
					var win = window.parent || window;
					win.alert('WP-Juicebox is unable to determine the Gallery Id.');
				}).always(function() {
					var win = window.parent || window;
					win.tb_remove();
				});
			});

			jQuery('#jb-cancel').click(function() {
				var win = window.parent || window;
				win.tb_remove();
			});
		}
	};
}();
