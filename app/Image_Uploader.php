<?php
namespace Woo_BG;

defined( 'ABSPATH' ) || exit;

class Image_Uploader {
	public static function upload_image_from_base64( $image, $name = 'attachment', $parent = null ) {
		if ( empty( $image[ 'data' ] ) ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		$upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;

		$base64 = $image[ 'data' ];

		if ( !is_string($base64) ) {
			return;
		}
		
		$base64 = str_replace(array(
			'data:image/jpeg;base64,',
			'data:image/jpg;base64,',
			'data:image/png;base64,',
		), '', $base64);
		$base64 = str_replace(' ', '+', $base64);

		$decoded = base64_decode($base64);
		$extension = "." . str_replace( 'image/', '', $image[ 'type' ] );
		$filename = $name . $extension;
		$hashed_filename = md5( $filename . microtime() ) . $extension;

		$image_upload = file_put_contents( $upload_path . $hashed_filename, $decoded );

		if( !function_exists( 'wp_handle_sideload' ) ) {
		  require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$file             = array();
		$file['error']    = '';
		$file['tmp_name'] = $upload_path . $hashed_filename;
		$file['name']     = $hashed_filename;
		$file['type']     = $image[ 'type' ];
		$file['size']     = filesize( $upload_path . $hashed_filename );

		// upload file to server
		// @new use $file instead of $image_upload
		$file_return = wp_handle_sideload( $file, array( 'test_form' => false ) );

		$attachment = array(
			'post_mime_type' => $file_return['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename($file_return['file'])),
			'post_content' => '',
			'post_status' => 'inherit',
			'guid' => $upload_dir['url'] . DIRECTORY_SEPARATOR . basename($file_return['file'])
		 );

		$attach_id = wp_insert_attachment( $attachment, $file_return['file'], $parent );

		require_once( ABSPATH . 'wp-admin/includes/image.php');

		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_return['file'] );

		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}

	public static function base64_encode_image( $file, $filetype ) {
		$img_binary = fread( fopen( $file, "r" ), filesize( $file ) );
		
		return 'data:' . $filetype['type'] . ';base64,' . base64_encode( $img_binary );
	}

	public static function change_upload_dir( $args ) {
		$new_folder = "/woo-bg";

		$args[ 'path' ] = $args['basedir'] . $new_folder;
		$args[ 'url' ] = $args['baseurl'] . $new_folder;
		$args[ 'subdir' ] = $new_folder;

		return $args;
	}
}