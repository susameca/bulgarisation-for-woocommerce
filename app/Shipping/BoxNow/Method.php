<?php
namespace Woo_BG\Shipping\BoxNow;
use Woo_BG\Container\Client;

defined( 'ABSPATH' ) || exit;

class Method extends \WC_Shipping_Method {
	const METHOD_ID = "woo_bg_boxnow";

	public $container, $cookie_data, $package, $cost_between_1, $cost_between_2, $cost_between_3, $cost_between_4, $free_shipping_over, $tax_status, $free_shipping = false;

	public function __construct( $instance_id = 0 ) {
		$this->container          = woo_bg()->container();
		$this->id                 = self::METHOD_ID; 
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Woo BG - BOX NOW', 'woo-bg' );  // Title shown in admin
		$this->method_description = __( 'Enables BOX NOW delivery.', 'woo-bg' ); // Description shown in admin
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);
		$this->enabled = 'yes';

		$this->init();
	}

	/**
	 * Init your settings
	 *
	 * @access public
	 * @return void
	 */
	function init() {
		// Load the settings API
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		$this->title                = $this->get_option( 'title' );
		$this->free_shipping_over   = woo_bg_get_option( 'boxnow_price', 'free_shipping_over' );
		$this->tax_status           = ( wc_tax_enabled() ) ? 'taxable' : 'none' ;

		// Save settings in admin if you have any defined
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @param mixed $package
	 * @return void
	 */
	public function calculate_shipping( $package = Array() ) {
		$this->cookie_data = self::get_cookie_data();
		$this->package = $package;
		$weight = $this->get_total_weight();
		$apm_size = self::get_allowed_apm_size();

		if ( $weight > 20 || $apm_size['has_oversize_item'] ) {
			return;
		}

		do_action( 'woo_bg/boxnow/rate/before_calculate', $this );

		$rate = array(
			'label' => $this->title,
			'cost' => 0,
		);

		if ( $this->free_shipping_over && woo_bg_get_package_total() >= $this->free_shipping_over ) {
			$rate['label'] = sprintf( __( '%s: Free shipping', 'woo-bg' ), $rate['label'] );
			$this->free_shipping = true;
		} else if ( $price_type = woo_bg_get_option( 'boxnow_price', 'price_type' ) ) {
			switch ( $price_type ) {
				case 'from_to_kg':
					$rate['cost'] = woo_bg_tax_based_price( $this->get_price_from_to_kg_option() );
					break;
				case 'from_to_order_total':
					$rate['cost'] = woo_bg_tax_based_price( $this->get_price_from_to_order_total_option() );
					break;
				case 'from_to_kg_and_order_total':
					$rate['cost'] = woo_bg_tax_based_price( $this->get_price_from_to_combined() );
					break;
			}
		}

		$rate['meta_data']['cookie_data'] = $this->cookie_data;
		$rate = apply_filters( 'woo_bg/boxnow/rate', $rate, $this );

		if ( $rate['cost'] === "0.00" && !$this->free_shipping ) {
			return;
		}

		// Register the rate
		$this->add_rate( $rate );
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title'            => array(
				'title'       => __( 'Title', 'woo-bg' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woo-bg' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
		);
	}

	public function is_available( $package ) {
		$countries = array( 'BG' );

		if ( in_array( $package['destination']['country'], $countries ) ) {
			return true;
		}
	}

	public function get_instance_form_fields() {
		return parent::get_instance_form_fields();
	}

	public static function get_cookie_data() {
		return ( isset( $_COOKIE[ 'woo-bg--boxnow-apm' ] ) ) ? json_decode( stripslashes( urldecode( $_COOKIE[ 'woo-bg--boxnow-apm' ] ) ), 1 ) : '';
	}

	public function get_total_weight() {
		$weight = 0;

		foreach ( $this->package[ 'contents' ] as $key => $item ) {
			if ( $item['data']->get_weight() ) {
				$weight += wc_get_weight( $item['data']->get_weight(), 'kg' ) * $item['quantity'];
			}

			$names[] = $item['data']->get_name();
		}

		if ( !$weight ) {
			$weight = apply_filters( 'woo_bg/boxnow/method/weight', 1, $this->package, $this );
		}

		return $weight;
	}

	public function get_price_from_to_kg_option() {
		$price = 0;
		$weight = $this->get_total_weight();

		if ( $from_to_kg = woo_bg_get_option( 'boxnow_price', 'from_to_kg' ) ) {
			$from_to_kg = json_decode( $from_to_kg, 1 );

			foreach ( $from_to_kg as $row ) {
				if ( !$row['from'] ) {
					$row['from'] = 0;
				}

				if ( !$row['to'] ) {
					$row['to'] = 20;
				}

				if ( $row['from'] <= $weight && $row['to'] > $weight ) {
					$price = $row['price'];

					break;
				}
			}
		}

		return $price;
	}

	public function get_price_from_to_order_total_option() {
		$price = 0;
		$total = woo_bg_get_package_total();

		if ( $from_to_total = woo_bg_get_option( 'boxnow_price', 'from_to_order_total' ) ) {
			$from_to_total = json_decode( $from_to_total, 1 );

			foreach ( $from_to_total as $row ) {
				if ( !$row['to'] ) {
					$row['to'] = 999999;
				}

				if ( !$row['from'] ) {
					$row['from'] = 0;
				}

				if ( $row['from'] <= $total && $row['to'] > $total ) {
					$price = $row['price'];

					break;
				}
			}
		}

		return $price;
	}

	public function get_price_from_to_combined() {
		$price = 0;
		$weight = $this->get_total_weight();
		$total = woo_bg_get_package_total();

		if ( $from_to = woo_bg_get_option( 'boxnow_price', 'from_to_kg_and_order_total' ) ) {
			$from_to = json_decode( $from_to, 1 );

			foreach ( $from_to as $row ) {
				if ( !$row['to_price'] ) {
					$row['to_price'] = 999999;
				}

				if ( !$row['from_price'] ) {
					$row['from_price'] = 0;
				}

				if ( !$row['to'] ) {
					$row['to'] = 20;
				}

				if ( !$row['from'] ) {
					$row['from'] = 0;
				}

				if (
					( $row['from'] <= $weight && $row['to'] > $weight ) &&
					( $row['from_price'] <= $total && $row['to_price'] > $total )
				) {
					$price = $row['price'];

					break;
				}
			}
		}

		return $price;
	}

	public static function validate_boxnow_method( $fields, $errors ) {
		if ( ! WC()->cart->needs_shipping() ) {
			return;
		}

		$chosen_shippings = WC()->session->get('chosen_shipping_methods');

		foreach ( $chosen_shippings as $key => $shipping ) {
			if ( strpos( $shipping, 'bg_boxnow' ) !== false ) {
				$data = WC()->session->get( 'shipping_for_package_' . $key )['rates'][ $shipping ];
				
				if ( $data->method_id === 'woo_bg_boxnow' ) {
					$cookie_data = self::get_cookie_data();

					if ( empty( $cookie_data['selectedApm'] ) ) {
						$errors->add( 'validation', __( 'Please choose a automat.', 'woo-bg' ) );
					}	
				}
			}
		}
	}

	public static function save_label_data_to_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( !empty( $order->get_items( 'shipping' ) ) ) {
			foreach ( $order->get_items( 'shipping' ) as $shipping ) {
				if ( $shipping['method_id'] === 'woo_bg_boxnow' ) {
					$cookie_data = '';

					foreach ( $shipping->get_meta_data() as $meta_data ) {
						$data = $meta_data->get_data();

						if ( $data['key'] == 'cookie_data' ) {
							$cookie_data = $data['value'];
						}
					}

					if ( $cookie_data ) {
						$order->update_meta_data( 'woo_bg_boxnow_cookie_data', $cookie_data );
					}

					$order->save();
					break;
				}
			}
		}
	}

	public static function get_allowed_apm_size() {
		$max_size = '0';
		$has_oversize_item = false;
		$total_volume = 0;
		$max_volume = 89320;

		foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
			$item_data = self::determine_item_size( $cart_item['data'] );

			if ( $item_data['volume'] ) {
				$total_volume += $item_data['volume'];
			}
			
			if ( $item_data['oversize'] ) {
				$has_oversize_item = true;
			}

			$max_size = ( $item_data['size'] > $max_size ) ? $item_data['size'] : $max_size;
		}

		if ( $total_volume > $max_volume ) {
			$has_oversize_item = true;
		}

		return [
			'max_size' => $max_size,
			'has_oversize_item' => $has_oversize_item,
		];
	}

	public static function determine_item_size( $product ) {
		$max_diagonal = 80.78;
		$dimensions = [
			[
				'box_size' => 3,
				'height' => 35,
				'width' => 44,
				'length' => 58,
			],
			[
				'box_size' => 2,
				'height' => 16,
				'width' => 44,
				'length' => 58,
			],
			[
				'box_size' => 1,
				'height' => 7,
				'width' => 44,
				'length' => 58,
			],
		];

		$item = [ 
			'oversize' => false,
			'volume' => 0,
			'size' => 0,
		];

		foreach ( $dimensions as $size ) {
			if ( 
				!is_numeric( $product->get_height() ) || 
				!is_numeric( $product->get_width() ) || 
				!is_numeric( $product->get_length() )
			) {
				$item['size'] = 2;
			} else if (
				$product->get_height() <= $size['height'] &&
				$product->get_width() <= $size['width'] &&
				$product->get_length() <= $size['length']
			) {
				$item['size'] = $size['box_size'];
				$item['volume'] = $product->get_length() * $product->get_width() * $product->get_height();
			} else if ( $size['box_size'] === 3 ) {
				$item['volume'] = $product->get_length() * $product->get_width() * $product->get_height();
				$item['max_side'] = max( $product->get_length(), $product->get_width(), $product->get_height() );
				$item['size'] = 3;
				
				if ( $item['max_side'] > $max_diagonal ) {
					$item[ 'oversize' ] = true;
				}
			}
		}

		return $item;
	}

	public static function enqueue_scripts() {
		wp_enqueue_script(
			'woo-bg-js-boxnow',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'boxnow-frontend.js' ),
			array( 'jquery' ), // deps
			null, // version -- this is handled by the bundle manifest
			true // in footer
		);

		wp_localize_script( 'woo-bg-js-boxnow', 'wooBg_boxnow', array(
			'i18n' => Apm::get_i18n(),
		) );

		wp_enqueue_style(
			'woo-bg-css-boxnow',
			woo_bg()->plugin_dir_url() . woo_bg_assets_bundle( 'boxnow-frontend.css' )
		);
	}

	public static function add_label_number_to_email( $order, $sent_to_admin, $plain_text, $email ) {
		$email_ids_to_send = [ 'customer_completed_order' ];

		if ( woo_bg_get_option( 'boxnow_send_from', 'label_after_checkout' ) === 'yes' ) {
			$email_ids_to_send[] = 'customer_processing_order';
			$email_ids_to_send[] = 'customer_on_hold_order';
		}
		
		$email_ids_to_send = apply_filters( 'woo_bg/boxnow/emails_to_send_label_number', $email_ids_to_send );

		if ( !in_array( $email->id, $email_ids_to_send ) ) {
			return;
		}

		$shipment_status = $order->get_meta( 'woo_bg_boxnow_shipment_status' );

		if ( !isset( $shipment_status ) || empty( $shipment_status['parcels'] ) ) {
			return;
		}

		$track_numbers = [];

		foreach ( $shipment_status['parcels'] as $parcel ) {
			$number = $parcel['id'];
			$url = 'https://boxnow.bg/en?track=' . $number;

			$track_numbers[] = sprintf( 
				__( 'Label number: %s. %s', 'woo-bg' ), 
				$number, 
				sprintf( '<a href="%s" target="_blank">%s</a>',
					$url,
					__( 'Track your order.' , 'woo-bg' )
				)
			);
		}

		$track_number_text = implode( '<br>', $track_numbers );
		$track_number_text = apply_filters( 'woo_bg/boxnow/track_number_text_in_email', $track_number_text, $order );

		echo wp_kses_post( wpautop( $track_number_text ) );
	}
}