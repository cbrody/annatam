<?php

header('Content-type: application/xml');

$wp_path = preg_split('/(?=((\\\|\/)wp-content)).*/', dirname(__file__));
$wp_path = (isset($wp_path[0]) && $wp_path[0] !== '') ? $wp_path[0] : $_SERVER['DOCUMENT_ROOT'];

require_once($wp_path . '/wp-load.php');
require_once($wp_path . '/wp-admin/includes/plugin.php');

$gallery_id = $_GET['gallery_id'];

$gallery_path = $Juicebox->get_gallery_path();
$gallery_filename = $gallery_path . $gallery_id . '.xml';

if (file_exists($gallery_filename)) {

	$custom_values = $Juicebox->get_custom_values($gallery_filename);

	$dom_doc = new DOMDocument('1.0', 'UTF-8');
	$dom_doc->formatOutput = true;

	$settings_tag = $dom_doc->createElement('juiceboxgallery');

	foreach ($custom_values as $key=>$value) {
		if (!(strpos($key, 'e_') === 0 || $key === 'postID')) {
			$settings_tag->setAttribute($key, $value);
		}
	}

	switch ($custom_values['e_library']) {
		case 'media':
			$post_record = $custom_values['postID'] !== '0' && get_post($custom_values['postID']);
			if ($post_record) {
				$attachments = $Juicebox->get_attachments_media($custom_values['e_featuredImage'], $custom_values['postID']);
				if ($attachments) {
					$attachments = $custom_values['e_mediaOrder'] === 'descending' ? array_reverse($attachments) : $attachments;
					foreach ($attachments as $attachment) {
						$attachment_id = $attachment->ID;
						$thumbnail = wp_get_attachment_image_src($attachment_id, 'thumbnail');
						$image = wp_get_attachment_image_src($attachment_id, 'full');
						if ($thumbnail && $image) {
							$image_url = $image[0];
							$image_element = $dom_doc->createElement('image');
							$image_element->setAttribute('imageURL', $image_url);
							$image_element->setAttribute('thumbURL', $thumbnail[0]);
							$image_element->setAttribute('linkURL', $image_url);
							$image_element->setAttribute('linkTarget', '_blank');
							$title_element = $dom_doc->createElement('title');
							$title_text = $dom_doc->createCDATASection($attachment->post_title);
							$title_element->appendChild($title_text);
							$caption_element = $dom_doc->createElement('caption');
							$caption_text = $dom_doc->createCDATASection($attachment->post_excerpt);
							$caption_element->appendChild($caption_text);
							$image_element->appendChild($title_element);
							$image_element->appendChild($caption_element);
							$settings_tag->appendChild($image_element);
						}
					}
				}
			}
			break;
		case 'nextgen':
			if (is_plugin_active('nextgen-gallery/nggallery.php')) {
				$attachments = $Juicebox->get_attachments_nextgen($custom_values['e_nextgenGalleryId']);
				if ($attachments) {
					$base_url = site_url('/' . $attachments[0]->path . '/');
					foreach ($attachments as $attachment) {
						$image_basename = $attachment->filename;
						$image_url = $base_url . $image_basename;
						$image_element = $dom_doc->createElement('image');
						$image_element->setAttribute('imageURL', $image_url);
						$image_element->setAttribute('thumbURL', $base_url . "thumbs/thumbs_" . $image_basename);
						$image_element->setAttribute('linkURL', $image_url);
						$image_element->setAttribute('linkTarget', '_blank');
						$title_element = $dom_doc->createElement('title');
						$title_text = $dom_doc->createCDATASection($attachment->alttext);
						$title_element->appendChild($title_text);
						$caption_element = $dom_doc->createElement('caption');
						$caption_text = $dom_doc->createCDATASection($attachment->description);
						$caption_element->appendChild($caption_text);
						$image_element->appendChild($title_element);
						$image_element->appendChild($caption_element);
						$settings_tag->appendChild($image_element);
					}
				}
			}
			break;
		case 'picasa':
			$attachments = $Juicebox->get_attachments_picasa($custom_values['e_picasaUserId'], $custom_values['e_picasaAlbumName']);
			if ($attachments) {
				foreach ($attachments as $attachment) {
					$media_group = $attachment->children('http://search.yahoo.com/mrss/')->group;
					$image_url = $media_group->content->attributes()->{'url'};
					$image_element = $dom_doc->createElement('image');
					$image_element->setAttribute('imageURL', $image_url);
					$image_element->setAttribute('thumbURL', $media_group->thumbnail[1]->attributes()->{'url'});
					$image_element->setAttribute('linkURL', $image_url);
					$image_element->setAttribute('linkTarget', '_blank');
					$title_element = $dom_doc->createElement('title');
					$title_text = $dom_doc->createCDATASection($attachment->title);
					$title_element->appendChild($title_text);
					$caption_element = $dom_doc->createElement('caption');
					$caption_text = $dom_doc->createCDATASection($attachment->summary);
					$caption_element->appendChild($caption_text);
					$image_element->appendChild($title_element);
					$image_element->appendChild($caption_element);
					$settings_tag->appendChild($image_element);
				}
			}
			break;
		default:
			break;
	}

	$dom_doc->appendChild($settings_tag);

	echo $dom_doc->saveXML();
}

?>
