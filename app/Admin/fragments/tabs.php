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
	<div class="notice notice-success">
		<h3>Интеграция с BOX NOW</h3>
		<p>Възползвайте се от бърза и лесна интеграция с <strong>BOX NOW</strong>. <strong>BOX NOW</strong> е най-зелената (пряко и преносно) куриерска услуга в България.</p>
		<p><strong>BOX NOW</strong> предлагат: Мрежа от 700+ автомата в 150 населени места. 24/7 достъп до автоматите. Фиксирана цена на доставка, без скрити такси. Доставка в същата вечер в рамките на София и до 24 часа за цялата страна. Съботни доставки без оскъпяване на куриерската услуга. Безплатно връщане на невзети пратки. Включено безплатно покритие до 800лв. на пратка. Безплатно известяване на клиентите (SMS, Viber, E-mail, BOX NOW app)</p>
		<p> <a class="button button-primary" href="https://boxnow.bg/e-shops" target="_blank">Станете техен партньор сега</a> ( След сключване на договор активирайте интеграцията от таб "Настройки" )</p>
</div>

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

	<?php 
	if ( $current_tab_object->render_tab_html() ) {
		echo wp_kses_post( $current_tab_object->render_tab_html() );
	}
	?>
</div>
