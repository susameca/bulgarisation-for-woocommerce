<?php
namespace Woo_BG;

defined( 'ABSPATH' ) || exit;

class Cache {
	const BASE_FOLDER = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'woo-bg' . DIRECTORY_SEPARATOR;

	public static function put_to_file( $file, $data ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		WP_Filesystem();
		global $wp_filesystem;

		$wp_filesystem->put_contents(
			$file,
			$data,
			FS_CHMOD_FILE
		);
	}

	public static function get_file( $file ) {
		$data = '';

		if ( file_exists( $file ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			WP_Filesystem();
			global $wp_filesystem;

			$data = $wp_filesystem->get_contents( $file );
		}

		return $data;
	}
}