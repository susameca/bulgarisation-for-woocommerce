<?php
namespace Woo_BG\Client;
use Woo_BG\File;
use Woo_BG\Cron\Stats;

defined( 'ABSPATH' ) || exit;

class BoxNow {
	const LIVE_URL = 'https://api-production.boxnow.bg';
    const DEMO_URL = 'https://api-stage.boxnow.bg';
    const AUTH_ENDPOINT = '/api/v1/auth-sessions';
    const CREATE_LABELS_ENDPOINT = '/api/v1/delivery-requests';

	const CACHE_FOLDER = File::CACHE_FOLDER . 'boxnow' . DIRECTORY_SEPARATOR;

	private $env = '';
	private $client_id = '';
	private $client_secret = '';
	private $base_endpoint;
	private $access_token;

	public function __construct() {
		$this->set_env( woo_bg_get_option( 'boxnow', 'env' ) );
		$this->load_client_id();
		$this->load_client_secret();
		$this->load_base_endpoint();

		$this->load_access_token( true );
	}

	public function api_call( $endpoint, $args, $method = 'POST', $return_plain = false ) {
		if ( ! $this->get_access_token() ) {
			return;
		}

		$url = $this->get_base_endpoint() . $endpoint;
		
		$request_args = array(
			'timeout' => 15,
			'headers' => array(
				'content-type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->get_access_token(),
			),
			'body' => wp_json_encode( $args ),
		);

		if ( $method === 'GET' ) {
			unset( $request_args['body'] );

			$request = wp_remote_get( add_query_arg( $args, $url ), $request_args );
		} else {
			$request = wp_remote_post( $url, $request_args );
		}

		if ( $return_plain ) {
			return wp_remote_retrieve_body( $request );
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), 1 );

		if ( isset( $response['code'] ) ) {
			$response['message'] = self::get_message( $response['code'] );

			if ( !empty( $response['jsonSchemaErrors'] ) ) {
				$response['message'] .= " " . implode('. ', $response['jsonSchemaErrors'] );
			}
		}

		return $response;
	}

	public function load_access_token( $forced = false ) {
		if ( $forced ) {
			$this->load_client_id();
			$this->load_client_secret();
		}

		$request = wp_remote_post( $this->get_base_endpoint() . self::AUTH_ENDPOINT, array(
			'headers' => array( 
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( array(
				'grant_type' => 'client_credentials',
				'client_id' => $this->get_client_id(),
				'client_secret' => $this->get_client_secret(),
			) ),
		) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) !== 200 ) {
			$this->set_access_token( null );
			return;
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), true );
		$this->set_access_token( $response[ 'access_token' ] );
	}

	//Loaders
	public function load_client_id() {
		$this->set_client_id( woo_bg_get_option( 'boxnow', 'client_id' ) );
	}

	public function load_client_secret() {
		$this->set_client_secret( woo_bg_get_option( 'boxnow', 'client_secret' ) );
	}

	public function load_base_endpoint() {
		$base_endpoint = self::DEMO_URL;

		if ( $this->get_env() == 'live' ) {
			$base_endpoint = self::LIVE_URL;
		}

		$this->set_base_endpoint( $base_endpoint );
	}

	//Getters

	public function get_client_id() {
		return $this->client_id;
	}

	public function get_client_secret() {
		return $this->client_secret;
	}

	public function get_env() {
		return $this->env;
	}

	public function get_base_endpoint() {
		return $this->base_endpoint;
	}

	public function get_access_token() {
		return $this->access_token;
	}

	//Setters

	private function set_client_id( $user ) {
		$this->client_id = $user;
	}

	private function set_client_secret( $password ) {
		$this->client_secret = $password;
	}

	public function set_env( $env ) {
		$this->env = $env;
	}

	private function set_base_endpoint( $endpoint ) {
		$this->base_endpoint = $endpoint;
	}

	private function set_access_token( $access_token ) {
		$this->access_token = $access_token;
	}

	public static function clear_cache_folder() {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		WP_Filesystem();
		global $wp_filesystem;

		$files = $wp_filesystem->dirlist( self::CACHE_FOLDER );

		if ( !empty( $files ) ) {
			foreach ( $files as $file ) {
				$wp_filesystem->delete( self::CACHE_FOLDER . DIRECTORY_SEPARATOR . $file['name'] );
			}
		}

		Stats::submit_stats();
	}

	public static function get_message( $code ) {
		$message = "";

		switch ( strtolower( $code ) ) {
			case 'p400':
				$message = "Заявка с грешни данни. Уверете се, че пускате заявка според документацията.";
				break;
			case 'p401':
				$message = "Заявка с грешна начална точка на пратката. Уверете се, че ползвате валиден location ID \ ID на локацията от Origins и/или проверете дали адреса е правилен.";
				break;
			case 'p402':
				$message = "Невалидна крайна дестинация! Уверете се, че използвате правилното location ID \ ID на локацията от endpoint-a с крайните дестинации и че подаденият адрес е коректен.";
				break;
			case 'p403':
				$message = "Не Ви е позволено да ползвате доставки от типа AnyAPM - SameAPM. Обърнете се към поддръжката, ако считате ,че това е наша грешка.";
				break;
			case 'p404':
				$message = "Невалиден CSV импорт. Вижте съдържанието на грешката за повече информация.";
				break;
			case 'p405':
				$message = "Невалиден телефонен номер. Проверете дали изпращате телефона в подходящия интернационален формат, тоест +359 xx xxx xxxx.";
				break;
			case 'c404':
				$message = "Невалиден телефонен номер. Проверете дали изпращате телефона в подходящия интернационален формат, тоест +359 xx xxx xxxx.";
				break;
			case 'p406':
				$message = "Невалиден размер. Уверете се, че в заявката си пращате някой от необходимите размери 1, 2 или 3 (Малък, Среден или Голям). Размерът е задължителна опция дори когато изпращате от даден автомат директно.";
				break;
			case 'p407':
				$message = "Невалиден код за държавата. Уверете се, че изпращате коректен код за държава във формат по ISO 3166-1alpha-2. Примерно: BG. ";
				break;
			case 'p408':
				$message = "Невалидна amountToBeCollected сума. Уверете се, че изпращате сумата във валиден диапазон (0, 5000). ";
				break;
			case 'p409':
				$message = "Невалиден параметър за доставка на партньор. Уверете се, че подавате валиден partner ID параметър от endpoint-a с крайните дестинации.";
				break;
			case 'p410':
				$message = "Проблем с номера на поръчка. Опитвате се да създадете заявка за доставка за поръчка с ID, което вече е било създадено. Моля изберете друго order ID.";
				break;
			case 'p411':
				$message = "Не сте упълномощени да използвате Наложен платеж като метод за плащане. Моля изберете друг метод за плащане или се обърнете към нашия отдел за съдействие. ";
				break;
			case 'p412':
				$message = "Не сте упълномощени да създавате връщания от клиент. Моля обърнете се към нашия отдел за съдействие, ако смятате, че е допусната грешка.";
				break;
			case 'p413':
				$message = "Невалидна дестинация за връщане. Уверете се, че подавате валиден номер за склад (warehouse ID) от Origins endpoint-a или валиден адрес.";
				break;
			case 'p415':
				$message = "Не сте упълномощени да създавате връщания до адрес. Моля обърнете се към нашия отдел за съдействие, ако смятате, че е допусната грешка.";
				break;
			case 'p416':
				$message = "Не сте упълномощени да използвате Наложен платеж като метод за плащане до адрес. Моля обърнете се към нашия отдел за съдействие, ако смятате, че е допусната грешка.";
				break;
			case 'p420':
				$message = "Не е възможно отказването на пратката. Типа пратки, които можете да откажете, са от тип „new“, „undelivered“. Пратки, които не можете да откажете, са в състояние „returned“ или „lost“. Уверете се, че пратката е в процес на доставка и опитайте отново. ";
				break;
			case 'p430':
				$message = "Пратки, които не са готови за AnyAPM потвърждение. Най-вероятно пратката е потвърдена за доставка. Моля обърнете се към нашия отдел за съдействие, ако смятате, че е допусната грешка. ";
				break;
			case 'p440':
				$message = "Неясен партньор. Вашият профил е свързан с няколко партньора и не е ясно от името на кой искате да извършите тази операция. Изпратете X-PartnerID header с ID на партньора, който искате да използвате. Можете да изкарате лист с всички ID-та на партньорите от /entrusted-partners endpoint. ";
				break;
			case 'p441':
				$message = "Невалиден X-PartnerID header. Стойността, която сте подали за X-PartnerID header е невалидна или принадлежи на парньор, до който нямате достъп. Уверете се, че подавате ID от /entrusted-partners endpoint. ";
				break;
			case 'p442':
				$message = "Невалиден параметър за лимит на заявката. Лимитът на заявката за това API е надвишено. Моля намалете разбера на заявката (максималното позволено е 100). ";
				break;
			case 'x403':
				$message = "Деактивиран акаунт. Вашият акаунт е бил деактивиран, моля обърнете се към нашия отдел за съдействие.";
				break;
			case 'p414':
				$message = "Неоторизиран достъп да пратка. Опитвате се да достъпите информация за пратка, която не Ви принадлежи. Уверете се, че търсите информация за пратка, до която имате достъп.";
				break;
			case 'p600':
				$message = "Неуспешна връзка с lockerbridge. Възникна грешка при комуникация с lockerbridgе. Моля опитайте по-късно или се обърнете към нашия отдел за съдействие.";
				break;
			case 'p610':
				$message = "Неуспешна API геолокация. Възникна грешка при преобразуването на адресите в GPS координати. Моля опитайте по-късно или се обърнете към нашия отдел за съдействие.";
				break;
			default:
				$message = "Моля свържете се със съпорта на плъгина.";
				break;
		}


		return $message;
	}
}
