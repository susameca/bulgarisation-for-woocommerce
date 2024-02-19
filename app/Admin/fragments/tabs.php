<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slugs = wp_list_pluck( $tabs, 'tab_slug' );
$first_tab = $slugs[0];

$current_tab    = ! empty( $_GET['tab'] ) && in_array( $_GET['tab'], $slugs ) ? sanitize_title( $_GET['tab'] ) : $first_tab;

$current_tab_key = array_search( $current_tab, $slugs );

$current_tab_object = $tabs[ $current_tab_key ];
?>
<div class="wrap woocommerce">
	<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
		<?php
		foreach ( $tabs as $tab ) {
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=woo-bg&tab=' . urlencode( $tab->tab_slug ) ) ) . '" class="nav-tab ';
			if ( $current_tab == $tab->tab_slug ) {
				echo 'nav-tab-active';
			}
			echo '">' . esc_html( $tab->get_name() ) . '</a>';
		}
		?>
	</nav>

	<?php echo wp_kses_post( $current_tab_object->render_tab_html() ) ?>
</div>
