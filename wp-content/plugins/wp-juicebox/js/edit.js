(function() {

	if (typeof jQuery === 'undefined') {
		return;
	}

	jQuery(document).ready(function() {

		jQuery('#jb-e-library').change(function() {
			switch (jQuery('#jb-e-library').val()) {
				case 'media':
					jQuery('#jb-toggle-media').show();
					jQuery('#jb-toggle-flickr').hide();
					jQuery('#jb-toggle-nextgen').hide();
					jQuery('#jb-toggle-picasa').hide();
					break;
				case 'flickr':
					jQuery('#jb-toggle-media').hide();
					jQuery('#jb-toggle-flickr').show();
					jQuery('#jb-toggle-nextgen').hide();
					jQuery('#jb-toggle-picasa').hide();
					break;
				case 'nextgen':
					jQuery('#jb-toggle-media').hide();
					jQuery('#jb-toggle-flickr').hide();
					jQuery('#jb-toggle-nextgen').show();
					jQuery('#jb-toggle-picasa').hide();
					break;
				case 'picasa':
					jQuery('#jb-toggle-media').hide();
					jQuery('#jb-toggle-flickr').hide();
					jQuery('#jb-toggle-nextgen').hide();
					jQuery('#jb-toggle-picasa').show();
					break;
				default:
					break;
			}
		});

		jQuery('#jb-e-library').trigger('change');

	});

}());
