<?php
namespace Woo_BG\Container;

use Woo_BG\Client\Econt;
use Woo_BG\Client\Econt\Profile;
use Woo_BG\Client\Econt\Countries;
use Woo_BG\Client\Econt\Cities;
use Woo_BG\Client\Econt\Offices;
use Woo_BG\Client\Econt\Streets;
use Woo_BG\Client\Econt\Quarters;
use Pimple\Container;

class Client extends Provider {
	const ECONT  = 'client.econt';
	const ECONT_PROFILE  = 'client.econt.profile';
	const ECONT_COUNTRIES  = 'client.econt.countries';
	const ECONT_CITIES  = 'client.econt.cities';
	const ECONT_OFFICES  = 'client.econt.offices';
	const ECONT_STREETS  = 'client.econt.streets';
	const ECONT_QUARTERS  = 'client.econt.quarters';

	public function register( Container $container ) {
		$container[ self::ECONT ] = function ( Container $container ) {
			return new Econt();
		};

		$container[ self::ECONT_PROFILE ] = function ( Container $container ) {
			return new Profile( $container );
		};
		
		$container[ self::ECONT_COUNTRIES ] = function ( Container $container ) {
			return new Countries( $container );
		};

		$container[ self::ECONT_CITIES ] = function ( Container $container ) {
			return new Cities( $container );
		};

		$container[ self::ECONT_OFFICES ] = function ( Container $container ) {
			return new Offices( $container );
		};

		$container[ self::ECONT_STREETS ] = function ( Container $container ) {
			return new Streets( $container );
		};

		$container[ self::ECONT_QUARTERS ] = function ( Container $container ) {
			return new Quarters( $container );
		};
	}
}
