<?php

$wp_path = preg_split('/(?=((\\\|\/)wp-content)).*/', dirname(__file__));
$wp_path = (isset($wp_path[0]) && $wp_path[0] !== '') ? $wp_path[0] : $_SERVER['DOCUMENT_ROOT'];

require_once($wp_path . '/wp-load.php');
require_once($wp_path . '/wp-admin/includes/screen.php');

$title = 'Add Juicebox Gallery';

$direction = is_rtl() ? 'rtl' : 'ltr';

$options = get_option('juicebox_options', array());
$gallery_id = isset($options['last_id']) ? $options['last_id'] + 1 : 1;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php echo get_option('blog_charset'); ?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo admin_url('css/colors-classic.css'); ?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo admin_url('load-styles.php?c=0&amp;dir=' . $direction . '&amp;load=wp-admin'); ?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('css/generate.css', __FILE__); ?>?ver=<?php echo $Juicebox->version; ?>" />
		<script src="<?php echo includes_url('js/jquery/jquery.js'); ?>" type="text/javascript" charset="utf-8"></script>
		<script src="<?php echo plugins_url('js/generate.js', __FILE__); ?>?ver=<?php echo $Juicebox->version; ?>" type="text/javascript" charset="utf-8"></script>
		<script src="<?php echo plugins_url('js/edit.js', __FILE__); ?>?ver=<?php echo $Juicebox->version; ?>" type="text/javascript" charset="utf-8"></script>
		<title><?php echo esc_html($title); ?> &lsaquo; <?php bloginfo('name') ?> &#8212; WordPress</title>
	</head>
	<body class="no-js wp-admin wp-core-ui">
<?php
		$custom_values = $Juicebox->get_default_values();
		$pro_options = $Juicebox->get_pro_options($custom_values);
?>
		<div id="jb-add-gallery-container" class="wrap jb-custom-wrap">

			<h2><img src ="<?php echo plugins_url('img/icon_32.png', __FILE__); ?>" align="top" alt="logo" /><?php echo esc_html($title); ?> Id <?php echo $gallery_id; ?></h2>

			<form id="jb-add-gallery-form" action="" method="post">
<?php
				include plugin_dir_path(__FILE__) . 'fieldset.php';
?>
				<div id="jb-gallery-action" class="jb-column1">
					<input id="jb-add-gallery" class="button" type="button" name="add-gallery" value="Add Gallery" />
					<input id="jb-cancel" class="button" type="button" name="cancel" value="Cancel" />
				</div>

			</form>

		</div>

		<script type="text/javascript">
			// <![CDATA[
			(function() {

				if (typeof jQuery === 'undefined') {
					return;
				}

				jQuery(document).ready(function() {
					try {
						JB.Gallery.Generator.postUrl = "<?php echo plugins_url('save-gallery.php', __FILE__); ?>";
						JB.Gallery.Generator.initialize();
					} catch (e) {
						throw "JB is undefined.";
					}
				});

			}());
			// ]]>
		</script>

	</body>
</html>
