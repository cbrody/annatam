<?php
/**
 Plugin Name: WP-Juicebox
 Plugin URI: http://www.juicebox.net/support/wp-juicebox/
 Description: Create Juicebox galleries with WordPress
 Author: Juicebox
 Version: 1.4.0.1
 Author URI: http://www.juicebox.net/
 Text Domain: juicebox
 */

/**
 * Juicebox plugin class
 */
class Juicebox {

	var $version = '1.4.0.1';

	/**
	 * Initalize plugin by registering hooks
	 */
	function __construct() {

		add_action('admin_init', array(&$this, 'add_setting'));
		add_action('admin_menu', array(&$this, 'add_menu'));
		add_action('admin_head', array(&$this, 'add_javascript'));
		add_action('admin_enqueue_scripts', array(&$this, 'add_scripts_admin'));
		add_action('wp_enqueue_scripts', array(&$this, 'add_scripts_wp'));

		add_action('media_buttons_context', array(&$this, 'add_media_button'));

		add_action('save_post', array(&$this, 'save_post_data'));

		$file = plugin_dir_path(__FILE__) . 'jbcore/juicebox.js';
		$contents = file_get_contents($file);
		if (strpos($contents, 'Juicebox-Pro') !== false) {
			add_filter('upgrader_pre_install', array(&$this, 'backup_pro'));
			add_filter('upgrader_post_install', array(&$this, 'restore_pro'));
		}

		add_shortcode('juicebox', array(&$this, 'shortcode_handler'));
	}

	/**
	 * Add setting
	 *
	 * @return void
	 */
	function add_setting() {
		register_setting('juicebox', 'juicebox_options');
	}

	/**
	 * Add menu
	 *
	 * @return void
	 */
	function add_menu() {
		add_menu_page('WP-Juicebox', 'WP-Juicebox', 'edit_posts', 'jb-manage-galleries', array(&$this, 'manage_galleries_page'), plugins_url('img/icon_16.png', __FILE__));
		add_submenu_page('jb-manage-galleries', 'WP-Juicebox - Manage Galleries', 'Manage Galleries', 'edit_posts', 'jb-manage-galleries', array(&$this, 'manage_galleries_page'));
		add_submenu_page('jb-manage-galleries', 'WP-Juicebox - Help', 'Help', 'edit_posts', 'jb-help', array(&$this, 'help_page'));
	}

	/**
	 * Add JavaScript
	 *
	 * @return void
	 */
	function add_javascript() {
		$current_screen = get_current_screen();
		$post_type = !empty($current_screen->post_type) ? $current_screen->post_type : 'post';
?>
		<script type="text/javascript">
			// <![CDATA[
			if (typeof JB !== 'undefined' && typeof JB.Gallery !== 'undefined') {
				var jbPostType = '<?php echo $post_type; ?>';
				JB.Gallery.configUrl = "<?php echo plugins_url('jb-config.php', __FILE__); ?>";
			}
			// ]]>
		</script>
<?php
	}

	/**
	 * Add scripts admin
	 *
	 * @param string hook
	 * @return
	 */
	function add_scripts_admin($hook) {

		$generate = $hook === 'post.php' || $hook === 'post-new.php';
		$edit = preg_match('/jb-manage-galleries/', $hook);
		$help = preg_match('/jb-help/', $hook);

		if ($generate || $edit) {
			wp_enqueue_script('jquery');
		}

		if ($generate) {
			wp_enqueue_script('thickbox');
			wp_enqueue_style('thickbox');
			wp_register_script('jb_script_admin_generate', plugins_url('js/generate.js', __FILE__), array('jquery', 'thickbox'), $this->version);
			wp_enqueue_script('jb_script_admin_generate');
		}

		if ($edit) {
			wp_register_script('jb_script_admin_table', plugins_url('js/table.js', __FILE__), array('jquery'), $this->version);
			wp_register_script('jb_script_admin_edit', plugins_url('js/edit.js', __FILE__), array('jquery'), $this->version);
			wp_enqueue_script('jb_script_admin_table');
			wp_enqueue_script('jb_script_admin_edit');
			wp_register_style('jb_style_admin_edit', plugins_url('css/edit.css', __FILE__), array(), $this->version);
			wp_enqueue_style('jb_style_admin_edit');
		}

		if ($help) {
			wp_register_style('jb_style_admin_help', plugins_url('css/help.css', __FILE__), array(), $this->version);
			wp_enqueue_style('jb_style_admin_help');
		}
	}

	/**
	 * Add scripts wp
	 *
	 * @return void
	 */
	function add_scripts_wp() {
		wp_register_script('jb_script_wp_core', plugins_url('jbcore/juicebox.js', __FILE__), array(), $this->version);
		wp_enqueue_script('jb_script_wp_core');
	}

	/**
	 * Add media button
	 *
	 * @return
	 */
	function add_media_button($context) {
		$current_screen = get_current_screen();
		$post_type = !empty($current_screen->post_type) ? $current_screen->post_type : 'post';

		if ($post_type === 'attachment' || ($post_type === 'page' && !current_user_can('edit_pages')) || ($post_type === 'post' && !current_user_can('edit_posts'))) {
			return;
		}

		$context .= '<a id="jb-media-button" class="button" href="#" onclick="javascript: JB.Gallery.embed.apply(JB.Gallery); return false;" title="Add a Juicebox Gallery to your ' . $post_type . '"><img src="' . plugins_url('img/icon_16.png', __FILE__) . '" width="16" height="16" alt="button" /> Add Juicebox Gallery</a>';

		return $context;
	}	

	/**
	 * Save post data
	 *
	 * @param string post id
	 * @return
	 */
	function save_post_data($post_id) {

		if ((isset($_POST['post_type']) && $_POST['post_type'] === 'attachment') || !current_user_can('edit_post', $post_id) || ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id))) {
			return;
		}

		$jb_term_id = get_post_meta($post_id, '_jb_term_id', true);
		if ($jb_term_id === '') {
			update_post_meta($post_id, '_jb_term_id', 'update');
			return;
		}

		$pattern = '/\\[juicebox.*?gallery_id="([1-9][0-9]*)".*?\\]/i';
		$post_record = get_post($post_id);
		$content = $post_record->post_content;
		$matches = array();

		preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

		$matches_count = count($matches);

		if ($matches_count > 0) {
			$gallery_path = $this->get_gallery_path();
			for ($i = 0; $i < $matches_count; $i++) {
				$gallery_filename = $gallery_path . $matches[$i][1] . '.xml';
				if (file_exists($gallery_filename)) {
					$this->set_post_id($gallery_filename, $post_id);
				}
			}
		}
	}

	/**
	 * Backup pro jbcore folder
	 *
	 * @return void
	 */
	function backup_pro() {
		$from = plugin_dir_path(__FILE__) . 'jbcore';
		$to = $this->get_upload_dir() . 'jbcore_backup';
		$this->copy_directory($from, $to);
	}

	/**
	 * Restore pro jbcore folder
	 *
	 * @return void
	 */
	function restore_pro() {
		$to = plugin_dir_path(__FILE__) . 'jbcore';
		$from = $this->get_upload_dir() . 'jbcore_backup';
		$this->delete_directory($to);
		$this->copy_directory($from, $to);
		$this->delete_directory($from);
	}

	/**
	 * Shortcode handler
	 *
	 * @param array attributes
	 * @return string embed code
	 */
	function shortcode_handler($atts) {
		extract(shortcode_atts(array('gallery_id'=>0), $atts));

		$gallery_id_intval = intval($gallery_id);

		if ($gallery_id_intval > 0) {

			$gallery_path = $this->get_gallery_path();
			$gallery_filename = $gallery_path . $gallery_id . '.xml';

			if (file_exists($gallery_filename)) {

				$custom_values = $this->get_custom_values($gallery_filename);

				$gallery_width = $custom_values['e_galleryWidth'];

				$gallery_height = $custom_values['e_galleryHeight'];

				$background_color = $this->get_rgba($custom_values['e_backgroundColor'], $custom_values['e_backgroundOpacity']);

				$config_url = plugins_url('config.php?gallery_id=' . $gallery_id, __FILE__);

				$string_builder = '<!--START JUICEBOX EMBED-->' . PHP_EOL;
				$string_builder .= '<script type="text/javascript">' . PHP_EOL;
				$string_builder .= '	new juicebox({' . PHP_EOL;
				$string_builder .= '		backgroundColor: "' . $background_color . '",' . PHP_EOL;
				$string_builder .= '		configUrl: "' . $config_url . '",' . PHP_EOL;
				$string_builder .= '		containerId: "juicebox-container-' . $gallery_id . '",' . PHP_EOL;
				$string_builder .= '		galleryHeight: "' . $gallery_height . '",' . PHP_EOL;
				$string_builder .= '		galleryWidth: "' . $gallery_width . '"' . PHP_EOL;
				$string_builder .= '	});' . PHP_EOL;
				$string_builder .= '</script>' . PHP_EOL;
				$string_builder .= '<div id="juicebox-container-' . $gallery_id . '">' . $seo_content . '</div>' . PHP_EOL;
				$string_builder .= '<!--END JUICEBOX EMBED-->' . PHP_EOL;

				return $string_builder;
			} else {
				return '<div><p>Juicebox Gallery Id ' . $gallery_id . ' has been deleted.</p></div>' . PHP_EOL;
			}
		} else {
			return '<div><p>Juicebox Gallery Id cannot be found.</p></div>' . PHP_EOL;
		}
	}

	/**
	 * Help page
	 *
	 * @return void
	 */
	function help_page() {
?>
		<div id="jb-help-page" class="wrap">

			<h2><img src="<?php echo plugins_url('img/icon_32.png', __FILE__); ?>" width="32" height="32" alt="logo" />&nbsp;WP-Juicebox - Help</h2>

			<p>
				<a href = "http://www.juicebox.net/support/wp-juicebox/">Get support and view WP-Juicebox documentation.</a>
			</p>

		</div>
<?php
	}

	/**
	 * Add footer links
	 *
	 * @return void
	 */
	function add_footer_links() {
		$plugin_data = get_plugin_data(__FILE__);
		printf('%1$s Plugin | Version %2$s | By %3$s<br>', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
	}

	/**
	 * Add TinyMCE plugin
	 *
	 * @param array plugin array
	 * @return array plugin array
	 */
	function add_tinymce_plugin($plugin_array) {
		$plugin_array['juicebox'] = plugins_url('tinymce/editor_plugin.js', __FILE__);
		return $plugin_array;
	}

	/**
	 * Add TinyMCE button
	 *
	 * @param array buttons
	 * @return array buttons
	 */
	function add_tinymce_button($buttons) {
		array_push($buttons, 'separator', 'juicebox');
		return $buttons;
	}

	/**
	 * Get reset values
	 *
	 * @return array reset values
	 */
	function get_reset_values() {

		$reset_values = array();

		$reset_values['galleryTitle'] = 'Juicebox Gallery';
		$reset_values['useFlickr'] = 'false';
		$reset_values['flickrUserName'] = '';
		$reset_values['flickrTags'] = '';
		$reset_values['textColor'] = 'rgba(255, 255, 255, 1)';
		$reset_values['thumbFrameColor'] = 'rgba(255, 255, 255, 0.5)';
		$reset_values['showOpenButton'] = 'true';
		$reset_values['showExpandButton'] = 'true';
		$reset_values['showThumbsButton'] = 'true';
		$reset_values['useThumbDots'] = 'false';
		$reset_values['useFullscreenExpand'] = 'false';
		$reset_values['e_galleryWidth'] = '100%';
		$reset_values['e_galleryHeight'] = '600px';
		$reset_values['e_backgroundColor'] = '222222';
		$reset_values['e_backgroundOpacity'] = '1';
		$reset_values['e_textColor'] = 'ffffff';
		$reset_values['e_textOpacity'] = '1';
		$reset_values['e_thumbColor'] = 'ffffff';
		$reset_values['e_thumbOpacity'] = '0.5';
		$reset_values['e_library'] = 'media';
		$reset_values['e_featuredImage'] = 'true';
		$reset_values['e_mediaOrder'] = 'ascending';
		$reset_values['e_nextgenGalleryId'] = '';
		$reset_values['e_picasaUserId'] = '';
		$reset_values['e_picasaAlbumName'] = '';
		$reset_values['postID'] = '0';

		return $reset_values;
	}

	/**
	 * Get values
	 *
	 * @return array values
	 */
	function get_values($filename) {

		$values = array();

		if (file_exists($filename)) {

			$dom_doc = new DOMDocument('1.0', 'UTF-8');
			$dom_doc->load($filename);

			$settings_tags = $dom_doc->getElementsByTagName('juiceboxgallery');
			$settings_tag = $settings_tags->item(0);

			if ($settings_tag->hasAttributes()) {
				foreach ($settings_tag->attributes as $attribute) {
					$name = $attribute->nodeName;
					$value = $attribute->nodeValue;
					$values[$name] = $value;
				}
			}
		}

		return $values;
	}

	/**
	 * Get default values
	 *
	 * @return array default values
	 */
	function get_default_values() {

		$reset_values = $this->get_reset_values();

		$default_filename = $this->get_default_filename();

		$default_values = file_exists($default_filename) ? $this->get_values($default_filename) : array();

		return array_merge($reset_values, $default_values);
	}

	/**
	 * Get custom values
	 *
	 * @param string gallery filename
	 * @return array custom values
	 */
	function get_custom_values($gallery_filename) {

		$default_values = $this->get_default_values();

		$reset_values = $this->strip_options($default_values, true);

		$custom_values = file_exists($gallery_filename) ? $this->get_values($gallery_filename) : array();

		return array_merge($reset_values, $custom_values);
	}

	/**
	 * Get keys
	 *
	 * @return array keys
	 */
	function get_keys() {
		return array('galleryTitle', 'useFlickr', 'flickrUserName', 'flickrTags', 'textColor', 'thumbFrameColor', 'showOpenButton', 'showExpandButton', 'showThumbsButton', 'useThumbDots', 'useFullscreenExpand', 'e_galleryWidth', 'e_galleryHeight', 'e_backgroundColor', 'e_backgroundOpacity', 'e_textColor', 'e_textOpacity', 'e_thumbColor', 'e_thumbOpacity', 'e_library', 'e_featuredImage', 'e_mediaOrder', 'e_nextgenGalleryId', 'e_picasaUserId', 'e_picasaAlbumName', 'postID');
	}

	/**
	 * Get pro options
	 *
	 * @param simplexmlelement custom values
	 * @return string pro options
	 */
	function get_pro_options($custom_values) {

		$pro_options = '';

		$keys = $this->get_keys();
		$keys_lower = array_map('strtolower', $keys);

		foreach ($custom_values as $key=>$value) {
			if (!in_array(strtolower($key), $keys_lower, true)) {
				$pro_options .= $key . '="' . $value . '"' . "\n";
			}
		}

		return $pro_options;
	}

	/**
	 * Strip options
	 *
	 * @param simplexmlelement custom values
	 * @return array options
	 */
	function strip_options($custom_values, $type) {

		$options = array();

		$keys = $this->get_keys();
		$keys_lower = array_map('strtolower', $keys);

		foreach ($custom_values as $key=>$value) {
			if (in_array(strtolower($key), $keys_lower, true) === $type) {
				$options[$key] = $value;
			}
		}

		return $options;
	}

	/**
	 * Get post id
	 *
	 * @param string gallery filename
	 * @return string post id
	 */
	function get_post_id($gallery_filename) {

		$post_id = '0';

		if (file_exists($gallery_filename)) {

			$dom_doc = new DOMDocument('1.0', 'UTF-8');
			$dom_doc->load($gallery_filename);

			$settings_tags = $dom_doc->getElementsByTagName('juiceboxgallery');
			$settings_tag = $settings_tags->item(0);

			$post_id = $settings_tag->hasAttribute('postID') ? $settings_tag->getAttribute('postID') : '0';
		}

		return $post_id;
	}

	/**
	 * Get upload directory
	 *
	 * @return string upload directory
	 */
	function get_upload_dir() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/';
	}

	/**
	 * Get gallery path
	 *
	 * @return string gallery path
	 */
	function get_gallery_path() {
		return $this->get_upload_dir() . 'juicebox/';
	}

	/**
	 * Get default filename
	 *
	 * @return string default filename
	 */
	function get_default_filename() {
		$gallery_path = $this->get_gallery_path();
		return $gallery_path . 'default.xml';
	}

	/**
	 * Get all galleries
	 *
	 * @param string gallery path
	 * @return array galleries
	 */
	function get_all_galleries($gallery_path) {
		return array_filter(@scandir($gallery_path), array(&$this, 'filter_gallery'));
	}

	/**
	 * Sort galleries ascending
	 *
	 * @param string gallery
	 * @param string gallery
	 * @return integer gallery
	 */
	function sort_galleries_ascending($a, $b) {
		$a_intval = intval(pathinfo($a, PATHINFO_FILENAME));
		$b_intval = intval(pathinfo($b, PATHINFO_FILENAME));
		if ($a_intval === $b_intval) {
			return 0;
		}
		return $a_intval < $b_intval ? -1 : 1;
	}

	/**
	 * Sort galleries descending
	 *
	 * @param string gallery
	 * @param string gallery
	 * @return integer gallery
	 */
	function sort_galleries_descending($a, $b) {
		$a_intval = intval(pathinfo($a, PATHINFO_FILENAME));
		$b_intval = intval(pathinfo($b, PATHINFO_FILENAME));
		if ($a_intval === $b_intval) {
			return 0;
		}
		return $a_intval > $b_intval ? -1 : 1;
	}

	/**
	 * Filter element
	 *
	 * @param string value
	 * @return boolean success
	 */
	function filter_element($value) {
		return $value !== '.' && $value !== '..';
	}

	/**
	 * Filter gallery
	 *
	 * @param string value
	 * @return boolean success
	 */
	function filter_gallery($value) {
		return $value !== '.' && $value !== '..' && pathinfo($value, PATHINFO_EXTENSION) === 'xml' && is_numeric(pathinfo($value, PATHINFO_FILENAME));
	}

	/**
	 * Filter image media
	 *
	 * @param string attachment
	 * @return boolean success
	 */
	function filter_image_media($attachment) {
		$mime = array('image/gif', 'image/jpeg', 'image/png');
		return in_array($attachment->post_mime_type, $mime);
	}

	/**
	 * Get rgba from color and opacity
	 *
	 * @ param string color
	 * @ param string opacity
	 * @ return string rgba
	 */
	function get_rgba($color, $opacity) {
		return 'rgba(' . hexdec(substr($color, 0, 2)) . ', ' . hexdec(substr($color, 2, 2)) . ', ' . hexdec(substr($color, 4, 2)) . ', ' . $opacity . ')';
	}

	/**
	 * Clean dimension
	 *
	 * @param string dimension
	 * @param string default
	 * @return string clean dimension
	 */
	function clean_dimension($dimension, $default) {
		$dimension_intval = abs(intval(filter_var($dimension, FILTER_SANITIZE_NUMBER_INT)));
		$dimension_string = (string)$dimension_intval;
		$type = substr(trim($dimension), -1) === '%' ? '%' : 'px';
		return $type !== '%' || ($dimension_intval >= 0 && $dimension_intval <= 100) ? $dimension_string . $type : $default;
	}

	/**
	 * Clean color
	 *
	 * @param string color
	 * @param string default
	 * @return string clean color
	 */
	function clean_color($color, $default) {
		$output = ltrim($color, '#');
		$output = str_replace('0x', '', $output);
		$output = strtolower($output);
		$length = strlen($output);
		if ($length < 3) {
			$output = str_pad($output, 3, '0');
		} elseif ($length > 3 && $length < 6) {
			$output = str_pad($output, 6, '0');
		} elseif ($length > 6) {
			$output = substr($output, 0, 6);
		}
		$new_length = strlen($output);
		if ($new_length === 3) {
			$r = dechex(hexdec(substr($output, 0, 1)));
			$g = dechex(hexdec(substr($output, 1, 1)));
			$b = dechex(hexdec(substr($output, 2, 1)));
			$output = $r . $r . $g . $g . $b . $b;
		} elseif ($new_length === 6) {
			$r = str_pad(dechex(hexdec(substr($output, 0, 2))), 2, '0', STR_PAD_LEFT);
			$g = str_pad(dechex(hexdec(substr($output, 2, 2))), 2, '0', STR_PAD_LEFT);
			$b = str_pad(dechex(hexdec(substr($output, 4, 2))), 2, '0', STR_PAD_LEFT);
			$output = $r . $g . $b;
		} else {
			return $default;
		}
		return $output;
	 }

	/**
	 * Clean opacity
	 *
	 * @param string opacity
	 * @param string default
	 * @return string clean opacity
	 */
	function clean_opacity($opacity, $default) {
		$opacity_floatval = abs(floatval(filter_var($opacity, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)));
		$opacity_string = (string)$opacity_floatval;
		return $opacity_floatval >= 0 && $opacity_floatval <= 1 ? $opacity_string : $default;
	}

	/**
	 * Build gallery
	 *
	 * @param string gallery filename
	 * @param array custom values
	 * @return void
	 */
	function build_gallery($gallery_filename, $custom_values) {

		$default_values = $this->get_default_values();

		$dom_doc = new DOMDocument('1.0', 'UTF-8');
		$dom_doc->formatOutput = true;

		$settings_tag = $dom_doc->createElement('juiceboxgallery');

		$clean_values = array();
		$clean_values['galleryTitle'] = trim(strip_tags(stripslashes($custom_values['galleryTitle']), '<a><b><br><font><i><u>'));
		if ($custom_values['e_library'] === 'flickr') {
			$clean_values['useFlickr'] = 'true';
			$clean_values['flickrUserName'] = $custom_values['flickrUserName'];
			$clean_values['flickrTags'] = $custom_values['flickrTags'];
		} else {
			$clean_values['useFlickr'] = 'false';
			$clean_values['flickrUserName'] = '';
			$clean_values['flickrTags'] = '';
		}
		$clean_values['e_textColor'] = $this->clean_color($custom_values['e_textColor'], $default_values['e_textColor']);
		$clean_values['e_textOpacity'] = $this->clean_opacity($custom_values['e_textOpacity'], $default_values['e_textOpacity']);
		$clean_values['textColor'] = $this->get_rgba($clean_values['e_textColor'], $clean_values['e_textOpacity']);
		$clean_values['e_thumbColor'] = $this->clean_color($custom_values['e_thumbColor'], $default_values['e_thumbColor']);
		$clean_values['e_thumbOpacity'] = $this->clean_opacity($custom_values['e_thumbOpacity'], $default_values['e_thumbOpacity']);
		$clean_values['thumbFrameColor'] = $this->get_rgba($clean_values['e_thumbColor'], $clean_values['e_thumbOpacity']);
		$clean_values['showOpenButton'] = isset($custom_values['showOpenButton']) ? $custom_values['showOpenButton'] : 'false';
		$clean_values['showExpandButton'] = isset($custom_values['showExpandButton']) ? $custom_values['showExpandButton'] : 'false';
		$clean_values['showThumbsButton'] = isset($custom_values['showThumbsButton']) ? $custom_values['showThumbsButton'] : 'false';
		$clean_values['useThumbDots'] = isset($custom_values['useThumbDots']) ? $custom_values['useThumbDots'] : 'false';
		$clean_values['useFullscreenExpand'] = isset($custom_values['useFullscreenExpand']) ? $custom_values['useFullscreenExpand'] : 'false';
		$clean_values['e_galleryWidth'] = $this->clean_dimension($custom_values['e_galleryWidth'], $default_values['e_galleryWidth']);
		$clean_values['e_galleryHeight'] = $this->clean_dimension($custom_values['e_galleryHeight'], $default_values['e_galleryHeight']);
		$clean_values['e_backgroundColor'] = $this->clean_color($custom_values['e_backgroundColor'], $default_values['e_backgroundColor']);
		$clean_values['e_backgroundOpacity'] = $this->clean_opacity($custom_values['e_backgroundOpacity'], $default_values['e_backgroundOpacity']);
		$clean_values['e_library'] = $custom_values['e_library'];
		$clean_values['e_featuredImage'] = '';
		$clean_values['e_mediaOrder'] = '';
		if ($custom_values['e_library'] === 'media') {
			$clean_values['e_featuredImage'] = isset($custom_values['e_featuredImage']) ? $custom_values['e_featuredImage'] : 'false';
			$clean_values['e_mediaOrder'] = $custom_values['e_mediaOrder'];
		}
		$clean_values['e_nextgenGalleryId'] = '';
		if ($custom_values['e_library'] === 'nextgen') {
			$clean_values['e_nextgenGalleryId'] = $custom_values['e_nextgenGalleryId'];
		}
		$clean_values['e_picasaUserId'] = '';
		$clean_values['e_picasaAlbumName'] = '';
		if ($custom_values['e_library'] === 'picasa') {
			$clean_values['e_picasaUserId'] = $custom_values['e_picasaUserId'];
			$clean_values['e_picasaAlbumName'] = $custom_values['e_picasaAlbumName'];
		}

		$pro_options = explode("\n", $custom_values['proOptions']);
		$all_options = array();
		foreach ($pro_options as $pro_option) {
			$attrs = explode('=', trim($pro_option));
			if (count($attrs) === 2) {
				$key = str_replace(' ', '', trim($attrs[0]));
				$value = trim(stripslashes($attrs[1]), ' `\'"');
				$all_options[$key] = $value;
			}
		}

		$accepted_options = $this->strip_options($all_options, false);

		$complete_options = array_merge($clean_values, $accepted_options);

		foreach ($complete_options as $key=>$value) {
			$settings_tag->setAttribute($key, $value);
		}

		$dom_doc->appendChild($settings_tag);
		$dom_doc->save($gallery_filename);
	}

	/**
	 * Set post id
	 *
	 * @param string gallery filename
	 * @param string post id
	 * @return void
	 */
	function set_post_id($gallery_filename, $post_id) {

		if (file_exists($gallery_filename)) {

			$dom_doc = new DOMDocument('1.0', 'UTF-8');
			$dom_doc->preserveWhiteSpace = false;
			$dom_doc->formatOutput = true;
			$dom_doc->load($gallery_filename);

			$settings_tags = $dom_doc->getElementsByTagName('juiceboxgallery');
			$settings_tag = $settings_tags->item(0);

			$settings_tag->setAttribute('postID', $post_id);
			$dom_doc->save($gallery_filename);
		}
	}

	/**
	 * Get term
	 *
	 * @param string actual
	 * @param string total
	 * @return string term
	 */
	function get_term($actual, $total) {
		$term = '';
		switch ($actual) {
			case 0:
				$term = 'no galleries';
				break;
			case 1:
				$term = $actual === $total ? 'all galleries' : '1 gallery';
				break;
			default:
				$term = $actual === $total ? 'all galleries' : strval($actual) . ' galleries';
				break;
		}
		return $term;
	}

	/**
	 * Manage galleries page
	 *
	 * @return void
	 */
	function manage_galleries_page() {
?>
		<div id="jb-manage-galleries-page" class="wrap">

			<h2><img src="<?php echo plugins_url('img/icon_32.png', __FILE__); ?>" width="32" height="32" alt="logo" />&nbsp;WP-Juicebox - Manage Galleries</h2>
<?php
			if (isset($_GET['jb-action']) && $_GET['jb-action'] !== '') {
				switch ($_GET['jb-action']) {
					case 'edit-gallery':
						$gallery_id = $_GET['jb-gallery-id'];
						$this->edit_gallery_form($gallery_id);
						break;
					case 'gallery-edited':
						if (!check_admin_referer('jb_edit', 'jb_edit_nonce')) {
							break;
						}
						$gallery_path = $this->get_gallery_path();
						$gallery_id = $_POST['jb-gallery-id'];
						$gallery_filename = $gallery_path . $gallery_id . '.xml';
						if (file_exists($gallery_filename)) {
							$post_id = $this->get_post_id($gallery_filename);
							$this->build_gallery($gallery_filename, $_POST);
							$this->set_post_id($gallery_filename, $post_id);
							echo '<div class="updated"><p>Gallery Id ' . $gallery_id . ' successfully edited.</p></div>';
						} else {
							echo '<div class="updated"><p>Gallery Id ' . $gallery_id . ' cannot be found.</p></div>';
						}
						$this->gallery_table();
						break;
					case 'delete-gallery':
						$gallery_path = $this->get_gallery_path();
						$gallery_id = $_GET['jb-gallery-id'];
						$gallery_filename = $gallery_path . $gallery_id . '.xml';
						if (file_exists($gallery_filename)) {
							if (unlink($gallery_filename)) {
								echo '<div class="updated"><p>Gallery Id ' . $gallery_id . ' successfully deleted.</p></div>';
							} else {
								echo '<div class="updated"><p>Gallery Id ' . $gallery_id . ' cannot be deleted.</p></div>';
							}
						} else {
							echo '<div class="updated"><p>Gallery Id ' . $gallery_id . ' cannot be found.</p></div>';
						}
						$this->gallery_table();
						break;
					case 'set-defaults':
						$this->set_defaults_form();
						break;
					case 'defaults-set':
						if (!check_admin_referer('jb_set', 'jb_set_nonce')) {
							break;
						}
						$default_filename = $this->get_default_filename();
						$this->build_gallery($default_filename, $_POST);
						echo '<div class="updated"><p>Custom default values successfully set.</p></div>';
						$this->gallery_table();
						break;
					case 'reset-defaults':
						$default_filename = $this->get_default_filename();
						if (file_exists($default_filename)) {
							if (unlink($default_filename)) {
								echo '<div class="updated"><p>Default values successfully reset.</p></div>';
							} else {
								echo '<div class="updated"><p>Default values cannot be reset.</p></div>';
							}
						} else {
							echo '<div class="updated"><p>No custom default values to reset.</p></div>';
						}
						$this->gallery_table();
						break;
					case 'delete-all-data':
						$gallery_path = $this->get_gallery_path();
						$galleries = $this->get_all_galleries($gallery_path);
						$galleries_text = 'No galleries to delete.';
						if (!empty($galleries)) {
							$actual = 0;
							foreach ($galleries as $gallery) {
								$gallery_filename = $gallery_path . $gallery;
								if (file_exists($gallery_filename)) {
									$actual = unlink($gallery_filename) ? $actual + 1 : $actual;
								}
							}
							$total = count($galleries);
							$term = $this->get_term($actual, $total);
							$formatted_term = ucfirst($term);
							$galleries_text = $formatted_term . ' successfully deleted.';
						}
						$default_filename = $this->get_default_filename();
						$default_text = 'No custom default values to delete.';
						if (file_exists($default_filename)) {
							$default_text = unlink($default_filename) ? 'All custom default values successfully deleted.' : 'All custom default values cannot be deleted.';
						}
						$options = get_option('juicebox_options', array());
						$options_text = 'No options to delete.';
						if (!empty($options)) {
							$options_text = delete_option('juicebox_options') ? 'All options successfully deleted.' : 'All options cannot be deleted.';
						}
						echo '<div class="updated"><p>' . $galleries_text . ' ' . $default_text . ' ' . $options_text . '</p></div>';
						$this->gallery_table();
						break;
					default:
						$this->gallery_table();
						break;
				}
			} else {
				$this->gallery_table();
			}
?>
		</div>
<?php
		add_action('in_admin_footer', array(&$this, 'add_footer_links'));
	}

	/**
	 * Gallery table
	 *
	 * @return void
	 */
	function gallery_table() {
		$admin_url = admin_url() . 'admin.php';
		$options = get_option('simpleviewer_options', array());
?>
		<div class="jb-table-buttons">
			<form action="<?php echo $admin_url; ?>" method="get">
				<input class="button jb-table-set" title="Set custom default values of the gallery configuration options." type="submit" name="table-set-header" value="Set Defaults" />
				<input type="hidden" name="page" value="jb-manage-galleries" />
				<input type="hidden" name="jb-action" value="set-defaults" />
			</form>
			<form action="<?php echo $admin_url; ?>" method="get">
				<input class="button jb-table-reset" title="Reset the default values of the gallery configuration options to original values." type="submit" name="table-reset-header" value="Reset Defaults" />
				<input type="hidden" name="page" value="jb-manage-galleries" />
				<input type="hidden" name="jb-action" value="reset-defaults" />
			</form>
			<form action="<?php echo $admin_url; ?>" method="get">
				<input class="button jb-table-delete" title="Delete all galleries, custom default values and options." type="submit" name="table-delete-header" value="Delete All Data" />
				<input type="hidden" name="page" value="jb-manage-galleries" />
				<input type="hidden" name="jb-action" value="delete-all-data" />
			</form>
		</div>

		<br />

		<div id="jb-bulk">
			<table class="wp-list-table widefat posts">

				<thead>
					<tr>
						<th>Gallery Id</th>
						<th>Last Modified Date</th>
						<th>Page/Post Title</th>
						<th>Gallery Title</th>
						<th>View Page/Post</th>
						<th>Edit Gallery</th>
						<th>Delete Gallery</th>
					</tr>
				</thead>

				<tbody>
<?php
				$gallery_path = $this->get_gallery_path();
				$galleries = $this->get_all_galleries($gallery_path);
				if (!empty($galleries)) {
					$options = get_option('simpleviewer_options', array());
					if (!isset($options['order']) || $options['order']) {
						usort($galleries, array(&$this, 'sort_galleries_descending'));
					} else {
						usort($galleries, array(&$this, 'sort_galleries_ascending'));
					}
					foreach ($galleries as $gallery) {
						$gallery_id = pathinfo($gallery, PATHINFO_FILENAME);
						$gallery_filename = $gallery_path . $gallery;
						if (file_exists($gallery_filename)) {
							$post_id = $this->get_post_id($gallery_filename);
							$post = get_post($post_id);
							$post_record = $post_id !== '0' && $post;
							$custom_values = $this->get_custom_values($gallery_filename);
							$gallery_title = !empty($custom_values['galleryTitle']) ? htmlspecialchars($custom_values['galleryTitle']) : '<i>Untitled</i>';
							$post_type = get_post_type($post_id);
							$post_type_text = ucfirst(strtolower($post_type));
							$post_trashed = get_post_status($post_id) === 'trash';
?>
							<tr>
								<td><?php echo $gallery_id; ?></td>
								<td><?php echo date('d F Y H:i:s', filemtime($gallery_filename)); ?></td>
								<td>
<?php
									if ($post_trashed) {
										echo '<i>' . $post_type_text . ' has been trashed.</i>';
									} elseif ($post_record) {
										$post_title = get_the_title($post_id);
										$post_title = !empty($post_title) ? $post_title : '<i>Untitled</i>';
										echo $post_title;
									} else {
										echo '<i>Page/post does not exist.</i>';
									}
?>
								</td>
								<td><?php echo $gallery_title; ?></td>
								<td>
<?php
									if ($post_trashed) {
										echo '<i>' . $post_type_text . ' has been trashed.</i>';
									} elseif ($post_record) {
										$text = 'View ' . $post_type_text;
										echo '<a href="' . get_permalink($post_id) . '" title="' . $text . '">' . $text . '</a>';
									} else {
										echo '<i>Page/post does not exist.</i>';
									}
?>
								</td>
								<td><?php echo '<a href="' . $admin_url . '?page=jb-manage-galleries&jb-action=edit-gallery&jb-gallery-id=' . $gallery_id . '" title="Edit Gallery">Edit Gallery</a>'; ?></td>
								<td><?php echo '<a class="jb-delete-gallery" href="' . $admin_url . '?page=jb-manage-galleries&jb-action=delete-gallery&jb-gallery-id=' . $gallery_id . '" title="Delete Gallery">Delete Gallery</a>'; ?></td>
							</tr>
<?php
						}
					}
				} else {
?>
					<tr>
						<td>No galleries found.</td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
<?php
				}
?>
				</tbody>

				<tfoot>
					<tr>
						<th>Gallery Id</th>
						<th>Last Modified Date</th>
						<th>Page/Post Title</th>
						<th>Gallery Title</th>
						<th>View Page/Post</th>
						<th>Edit Gallery</th>
						<th>Delete Gallery</th>
					</tr>
				</tfoot>

			</table>
		</div>

		<br />

		<div class="jb-table-buttons">
			<form action="<?php echo $admin_url; ?>" method="get">
				<input class="button jb-table-set" title="Set custom default values of the gallery configuration options." type="submit" name="table-set-footer" value="Set Defaults" />
				<input type="hidden" name="page" value="jb-manage-galleries" />
				<input type="hidden" name="jb-action" value="set-defaults" />
			</form>
			<form action="<?php echo $admin_url; ?>" method="get">
				<input class="button jb-table-reset" title="Reset the default values of the gallery configuration options to original values." type="submit" name="table-reset-footer" value="Reset Defaults" />
				<input type="hidden" name="page" value="jb-manage-galleries" />
				<input type="hidden" name="jb-action" value="reset-defaults" />
			</form>
			<form action="<?php echo $admin_url; ?>" method="get">
				<input class="button jb-table-delete" title="Delete all galleries, custom default values and options." type="submit" name="table-delete-footer" value="Delete All Data" />
				<input type="hidden" name="page" value="jb-manage-galleries" />
				<input type="hidden" name="jb-action" value="delete-all-data" />
			</form>
		</div>
<?php
	}

	/**
	 * Edit gallery form
	 *
	 * @return void
	 */
	function edit_gallery_form($gallery_id) {
		$admin_url = admin_url() . 'admin.php';
		$gallery_path = $this->get_gallery_path();
		$gallery_filename = $gallery_path . $gallery_id . '.xml';
		$custom_values = $this->get_custom_values($gallery_filename);
		$pro_options = $this->get_pro_options($custom_values);
?>
		<div id="jb-edit-gallery-container" class="wrap jb-custom-wrap">

			<h3>Edit Juicebox Gallery Id <?php echo $gallery_id; ?></h3>

			<form id="jb-edit-gallery-form" action="<?php echo $admin_url . '?page=jb-manage-galleries&jb-action=gallery-edited'; ?>" method="post">

				<input type="hidden" name="jb-gallery-id" value="<?php echo $gallery_id; ?>" />
<?php
				include plugin_dir_path(__FILE__) . 'fieldset.php';
?>
				<div class="jb-column1">
					<input class="button" type="submit" name="edit" value="Save" />
					<input class="button" onclick="javascript: location.href='<?php echo $admin_url . '?page=jb-manage-galleries'; ?>'" type="button" name="do-not-edit" value="Cancel" />
				</div>
<?php
				wp_nonce_field('jb_edit', 'jb_edit_nonce');
?>
			</form>

		</div>
<?php
	}

	/**
	 * Set default values form
	 *
	 * @return void
	 */
	function set_defaults_form() {
		$admin_url = admin_url() . 'admin.php';
		$custom_values = $this->get_default_values();
		$pro_options = $this->get_pro_options($custom_values);
?>
		<div id="jb-set-defaults-container" class="wrap jb-custom-wrap">

			<h3>Set Default Values</h3>

			<form id="jb-set-defaults-form" action="<?php echo $admin_url . '?page=jb-manage-galleries&jb-action=defaults-set'; ?>" method="post">
<?php
				include plugin_dir_path(__FILE__) . 'fieldset.php';
?>
				<div class="jb-column1">
					<input class="button" type="submit" name="set" value="Set" />
					<input class="button" onclick="javascript: location.href='<?php echo $admin_url . '?page=jb-manage-galleries'; ?>'" type="button" name="do-not-set" value="Cancel" />
				</div>
<?php
				wp_nonce_field('jb_set', 'jb_set_nonce');
?>
			</form>

		</div>

		<script type="text/javascript">
			// <![CDATA[
			(function() {

				if (typeof jQuery === 'undefined') {
					return;
				}

				jQuery(document).ready(function() {
					jQuery('#jb-gallery-title').prop('disabled', true);
					jQuery('#jb-e-library').prop('disabled', true);
					jQuery('#jb-toggle-media :input').prop('disabled', true);
					jQuery('#jb-toggle-flickr :input').prop('disabled', true);
					jQuery('#jb-toggle-nextgen :input').prop('disabled', true);
					jQuery('#jb-toggle-picasa :input').prop('disabled', true);
				});

			}());
			// ]]>
		</script>
<?php
	}

	/**
	 * Remove whitespace
	 *
	 * @param string input
	 * @return string output
	 */
	function remove_whitespace($input) {
		return preg_replace('/\s+/', '', $input);
	}

	/**
	 * Get attachments media
	 *
	 * @param string featured image
	 * @param string post id
	 * @return array attachments
	 */
	function get_attachments_media($featured_image, $post_id) {
		$attachments = array();
		if ($featured_image === 'true') {
			$attachments = get_children(array('post_parent'=>$post_id, 'post_type'=>'attachment', 'post_mime_type'=>'image', 'orderby'=>'menu_order', 'order'=>'ASC'));
		} else {
			$attachments = get_children(array('post_parent'=>$post_id, 'post_type'=>'attachment', 'post_mime_type'=>'image', 'orderby'=>'menu_order', 'order'=>'ASC', 'exclude'=>get_post_thumbnail_id($post_id)));
		}
		return array_filter($attachments, array(&$this, 'filter_image_media'));
	}

	/**
	 * Get attachments NextGEN
	 *
	 * @param string NextGEN gallery id
	 * @return array attachments
	 */
	function get_attachments_nextgen($nextgen_gallery_id) {
		$attachments = array();
		global $wpdb;
		$ngg_options = get_option('ngg_options', array());
		if (isset($ngg_options['galSort']) && isset($ngg_options['galSortDir'])) {
			$attachments = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->nggallery AS t INNER JOIN $wpdb->nggpictures AS tt ON t.gid = tt.galleryid WHERE t.gid = '$nextgen_gallery_id' AND tt.exclude != 1 ORDER BY tt.$ngg_options[galSort] $ngg_options[galSortDir]");
		} else {
			$attachments = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->nggallery AS t INNER JOIN $wpdb->nggpictures AS tt ON t.gid = tt.galleryid WHERE t.gid = '$nextgen_gallery_id' AND tt.exclude != 1");
		}
		return $attachments;
	}

	/**
	 * Get attachments Picasa
	 *
	 * @param string Picasa user id
	 * @param string Picasa album name
	 * @return array attachments
	 */
	function get_attachments_picasa($picasa_user_id, $picasa_album_name) {
		$attachments = array();
		$picasa_feed = 'http://picasaweb.google.com/data/feed/api/user/' . $this->remove_whitespace($picasa_user_id) . '/album/' . $this->remove_whitespace($picasa_album_name) . '?kind=photo&imgmax=1600';
		$entries = @simplexml_load_file($picasa_feed);
		if ($entries) {
			foreach ($entries->entry as $entry) {
				$attachments[] = $entry;
			}
		}
		return $attachments;
	}

	/**
	 * Copy directory
	 *
	 * @param string source
	 * @param string destination
	 * @return boolean success
	 */
	function copy_directory($source, $destination) {
		if (is_link($source)) {
			return symlink(readlink($source), $destination);
		}
		if (is_file($source)) {
			return copy($source, $destination);
		}
		if (!is_dir($destination)) {
			mkdir($destination);
		}
		$files = array_filter(@scandir($source), array(&$this, 'filter_element'));
		foreach ($files as $file) {
			$this->copy_directory($source . '/' . $file, $destination . '/' . $file);
		}
		return true;
	}

	/**
	 * Delete directory
	 *
	 * @param string directory
	 * @return boolean success
	 */
	function delete_directory($directory) {
		if (!file_exists($directory)) {
			return false;
		}
		if (is_file($directory)) {
			return unlink($directory);
		}
		$files = array_filter(@scandir($directory), array(&$this, 'filter_element'));
		foreach ($files as $file) {
			$this->delete_directory($directory . '/' . $file);
		}
		return rmdir($directory);
	}
}

/**
 * Main
 *
 * @return void
 */
function Juicebox() {
	global $Juicebox;
	$Juicebox = new Juicebox();
}

add_action('init', 'Juicebox');

/**
 * Check dependency
 *
 * @return void
 */
function jb_check_dependency() {

	// Check PHP version
	if (version_compare(phpversion(), '5.2', '<')) {
		jb_display_error_message('<b>WP-Juicebox</b> requires PHP v5.2 or later.', E_USER_ERROR);
	}

	// Check if DOM extention is enabled
	if (!class_exists('DOMDocument')) {
		jb_display_error_message('<b>WP-Juicebox</b> requires the DOM extention to be enabled.', E_USER_ERROR);
	}

	// Check WordPress version
	global $wp_version;
	if (version_compare($wp_version, '2.8', '<')) {
		jb_display_error_message('<b>WP-Juicebox</b> requires WordPress v2.8 or later.', E_USER_ERROR);
	}

	// Find path to WordPress uploads directory
	$upload_dir = wp_upload_dir();
	$gallery_path = $upload_dir['basedir'] . '/juicebox/';

	clearstatcache();

	// Create uploads folder and assign full access permissions
	if (!file_exists($gallery_path))
	{
		$old = umask(0);
		if (!@mkdir($gallery_path, 0777, true)) {
			jb_display_error_message('<b>WP-Juicebox</b> cannot create the <b>wp-content/uploads/juicebox</b> folder. Please do this manually and assign full access permissions (777) to it.', E_USER_ERROR);
		}
		@umask($old);
		if ($old !== umask()) {
			jb_display_error_message('<b>WP-Juicebox</b> cannot cannot change back the umask after creating the <b>wp-content/uploads/juicebox</b> folder.', E_USER_ERROR);
		}
	} 
}

/**
 * Display error message
 *
 * @param string error message
 * @param integer error type
 */
function jb_display_error_message($error_msg, $error_type) {
	if(isset($_GET['action']) && $_GET['action'] === 'error_scrape') {
		echo $error_msg;
		exit;
    } else {
		trigger_error($error_msg, $error_type);
    }
}

register_activation_hook(__FILE__, 'jb_check_dependency');

?>
