<?php
/**
 * Title         : On-The-Fly Image Resizer
 * Description   : Resizes WordPress images on the fly
 * Version       : 1.2
 * Author        : Joshua Cerbito (@joshuacerbito)
 *
 * @param string  $img_object       - (required) must be uploaded using wp media uploader
 * @param int     $width            - (optional)
 * @param int     $height           - (optional)
 * @param bool    $crop             - (optional) default to soft crop
 * @param bool    $retina           - (optional) defaults to false (non-retina image)
 * @param bool    $return_object    - (optional) default to false (returns image url)
 * @uses  wp_upload_dir()
 * @uses  image_resize_dimensions()
 * @uses  wp_get_image_editor()
 *
 * @return array|str
 */

function otf_image_resize( $img_object, $width = NULL, $height = NULL, $crop = true, $retina = false, $return_object = false, $use_guid = false ) {

	global $wpdb;

  $url = $img_object['url'];

	if ( empty( $url ) ) {
		return new WP_Error( 'no_image_url', __( 'No image URL has been entered.','wta' ), $url );
  }

	// Get default size from database
	$width = ( $width )  ? $width : get_option( 'thumbnail_size_w' );
	$height = ( $height ) ? $height : get_option( 'thumbnail_size_h' );

	// Allow for different retina sizes
	$retina = $retina ? ( $retina === true ? 2 : $retina ) : 1;

	// Get the image file path
	$file_path = parse_url( $url );
	$file_path = $_SERVER['DOCUMENT_ROOT'] . $file_path['path'];

	// Check for Multisite
	if ( is_multisite() ) {
		global $blog_id;
		$blog_details = get_blog_details( $blog_id );
		$file_path = str_replace( $blog_details->path . 'files/', '/wp-content/blogs.dir/'. $blog_id .'/files/', $file_path );
	}

	// Destination width and height variables
	$dest_width = $width * $retina;
	$dest_height = $height * $retina;

	// File name suffix (appended to original file name)
	$suffix = "{$dest_width}x{$dest_height}";

	// Some additional info about the image
	$info = pathinfo( $file_path );
	$dir = $info['dirname'];
	$ext = $info['extension'];
	$name = wp_basename( $file_path, ".$ext" );

  if ( 'bmp' == $ext ) {
		return new WP_Error( 'bmp_mime_type', __( 'Image is BMP. Please use either JPG or PNG.','wta' ), $url );
	}

	// Suffix applied to filename
	$suffix = "{$dest_width}x{$dest_height}";

	// Get the destination file name
	$dest_file_name = "{$dir}/{$name}-{$suffix}.{$ext}";

	if ( !file_exists( $dest_file_name ) ) {

		/*
		 *  Bail if this image isn't in the Media Library.
		 *  We only want to resize Media Library images, so we can be sure they get deleted correctly when appropriate.
		 */
		$query = ( $use_guid )? $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE guid='%s'", $url ) : $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE id='%s' AND post_type='%s'", $img_object['ID'], 'attachment' );
		$get_attachment = $wpdb->get_results( $query );
		if ( !$get_attachment )
      return ( $return_object )? array( 'url' => $url, 'width' => $width, 'height' => $height ) : $url;

		// Load Wordpress Image Editor
		$editor = wp_get_image_editor( $file_path );
		if ( is_wp_error( $editor ) )
      return ( $return_object )? array( 'url' => $url, 'width' => $width, 'height' => $height ) : $url;

		// Get the original image size
		$size = $editor->get_size();
		$orig_width = $size['width'];
		$orig_height = $size['height'];

		$src_x = $src_y = 0;
		$src_w = $orig_width;
		$src_h = $orig_height;

		if ( $crop ) {

			$cmp_x = $orig_width / $dest_width;
			$cmp_y = $orig_height / $dest_height;

			// Calculate x or y coordinate, and width or height of source
			if ( $cmp_x > $cmp_y ) {
				$src_w = round( $orig_width / $cmp_x * $cmp_y );
				$src_x = round( ( $orig_width - ( $orig_width / $cmp_x * $cmp_y ) ) / 2 );
			}
			else if ( $cmp_y > $cmp_x ) {
				$src_h = round( $orig_height / $cmp_y * $cmp_x );
				$src_y = round( ( $orig_height - ( $orig_height / $cmp_y * $cmp_x ) ) / 2 );
			}

		}

		// Time to crop the image!
		$editor->crop( $src_x, $src_y, $src_w, $src_h, $dest_width, $dest_height );

		// Now let's save the image
		$saved = $editor->save( $dest_file_name );

		// Get resized image information
		$resized_url = str_replace( basename( $url ), basename( $saved['path'] ), $url );
		$resized_width = $saved['width'];
		$resized_height = $saved['height'];
		$resized_type = $saved['mime-type'];

		// Add the resized dimensions to original image metadata (so we can delete our resized images when the original image is delete from the Media Library)
		$metadata = wp_get_attachment_metadata( $get_attachment[0]->ID );
		if ( isset( $metadata['image_meta'] ) ) {
			$metadata['image_meta']['resized_images'][] = $resized_width .'x'. $resized_height;
			wp_update_attachment_metadata( $get_attachment[0]->ID, $metadata );
		}

		// Create the image array
		$image_array = array(
			'url' => $resized_url,
			'width' => $resized_width,
			'height' => $resized_height,
			'type' => $resized_type
		);

	}
	else {
		$image_array = array(
			'url' => str_replace( basename( $url ), basename( $dest_file_name ), $url ),
			'width' => $dest_width,
			'height' => $dest_height,
			'type' => $ext
		);
	}

  // Return image array|url
  return ( $return_object )? $image_array : $image_array['url'];
}

?>
