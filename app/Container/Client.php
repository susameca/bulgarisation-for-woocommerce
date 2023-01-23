<?php
namespace Woo_BG\Container;

//Econt Classes
use Woo_BG\Client\Econt;
use Woo_BG\Client\Econt\Profile as Econt_Profile;
use Woo_BG\Client\Econt\Countries as Econt_Countries;
use Woo_BG\Client\Econt\Cities as Econt_Cities;
use Woo_BG\Client\Econt\Offices as Econt_Offices;
use Woo_BG\Client\Econt\Streets as Econt_Streets;
use Woo_BG\Client\Econt\Quarters as Econt_Quarters;

//Speedy Classes
use Woo_BG\Client\Speedy;
use Woo_BG\Client\Speedy\Profile as Speedy_Profile;
use Woo_BG\Client\Speedy\Cities as Speedy_Cities;
use Woo_BG\Client\Speedy\Offices as Speedy_Offices;
use Woo_BG\Client\Speedy\Streets as Speedy_Streets;
use Woo_BG\Client\Speedy\Quarters as Speedy_Quarters;

//CVC Classes
use Woo_BG\Client\CVC;
use Woo_BG\Client\CVC\Cities as CVC_Cities;
use Woo_BG\Client\CVC\Profile as CVC_Profile;
use Woo_BG\Client\CVC\Countries as CVC_Countries;
use Woo_BG\Client\CVC\Streets as CVC_Streets;
use Woo_BG\Client\CVC\Quarters as CVC_Quarters;
use Woo_BG\Client\CVC\Offices as CVC_Offices;
use Woo_BG\Client\CVC\Hubs as CVC_Hubs;

use Pimple\Container;

class Client extends Provider {
	const ECONT            = 'client.econt';
	const ECONT_PROFILE    = 'client.econt.profile';
	const ECONT_COUNTRIES  = 'client.econt.countries';
	const ECONT_CITIES     = 'client.econt.cities';
	const ECONT_OFFICES    = 'client.econt.offices';
	const ECONT_STREETS    = 'client.econt.streets';
	const ECONT_QUARTERS   = 'client.econt.quarters';

	const SPEEDY           = 'client.speedy';
	const SPEEDY_PROFILE   = 'client.speedy.profile';
	const SPEEDY_CITIES   = 'client.speedy.cities';
	const SPEEDY_OFFICES   = 'client.speedy.offices';
	const SPEEDY_STREETS   = 'client.speedy.streets';
	const SPEEDY_QUARTERS   = 'client.speedy.quarters';

	const CVC              = 'client.cvc';
	const CVC_PROFILE      = 'client.cvc.profile';
	const CVC_CITIES       = 'client.cvc.cities';
	const CVC_COUNTRIES    = 'client.cvc.countries';
	const CVC_STREETS      = 'client.cvc.streets';
	const CVC_QUARTERS     = 'client.cvc.quarters';
	const CVC_OFFICES      = 'client.cvc.offices';
	const CVC_HUBS         = 'client.cvc.hubs';

	public function register( Container $container ) {
		$container[ self::ECONT ] = function ( Container $container ) {
			return new Econt();
		};

		$container[ self::ECONT_PROFILE ] = function ( Container $container ) {
			return new Econt_Profile( $container );
		};
		
		$container[ self::ECONT_COUNTRIES ] = function ( Container $container ) {
			return new Econt_Countries( $container );
		};

		$container[ self::ECONT_CITIES ] = function ( Container $container ) {
			return new Econt_Cities( $container );
		};

		$container[ self::ECONT_OFFICES ] = function ( Container $container ) {
			return new Econt_Offices( $container );
		};

		$container[ self::ECONT_STREETS ] = function ( Container $container ) {
			return new Econt_Streets( $container );
		};

		$container[ self::ECONT_QUARTERS ] = function ( Container $container ) {
			return new Econt_Quarters( $container );
		};

		$container[ self::SPEEDY ] = function ( Container $container ) {
			return new Speedy();
		};

		$container[ self::SPEEDY_PROFILE ] = function ( Container $container ) {
			return new Speedy_Profile( $container );
		};

		$container[ self::SPEEDY_CITIES ] = function ( Container $container ) {
			return new Speedy_Cities( $container );
		};

		$container[ self::SPEEDY_OFFICES ] = function ( Container $container ) {
			return new Speedy_Offices( $container );
		};

		$container[ self::SPEEDY_STREETS ] = function ( Container $container ) {
			return new Speedy_Streets( $container );
		};

		$container[ self::SPEEDY_QUARTERS ] = function ( Container $container ) {
			return new Speedy_Quarters( $container );
		};
		
		$container[ self::CVC ] = function ( Container $container ) {
			return new CVC();
		};

		$container[ self::CVC_CITIES ] = function ( Container $container ) {
			return new CVC_Cities( $container );
		};

		$container[ self::CVC_PROFILE ] = function ( Container $container ) {
			return new CVC_Profile( $container );
		};

		$container[ self::CVC_COUNTRIES ] = function ( Container $container ) {
			return new CVC_Countries( $container );
		};

		$container[ self::CVC_STREETS ] = function ( Container $container ) {
			return new CVC_Streets( $container );
		};
		
		$container[ self::CVC_QUARTERS ] = function ( Container $container ) {
			return new CVC_Quarters( $container );
		};
		
		$container[ self::CVC_OFFICES ] = function ( Container $container ) {
			return new CVC_Offices( $container );
		};

		$container[ self::CVC_HUBS ] = function ( Container $container ) {
			return new CVC_Hubs( $container );
		};
	}
}
