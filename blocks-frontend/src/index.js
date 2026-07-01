/* eslint-disable import/no-unresolved */
import {
	registerCheckoutFilters,
	ExperimentalOrderMeta,
} from '@woocommerce/blocks-checkout';
/* eslint-enable import/no-unresolved */
import { registerPlugin } from '@wordpress/plugins';
import { createElement, useEffect } from '@wordpress/element';
import { useSelect, select, subscribe } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import './index.css';

const { locale = '', rate: EUR_RATE = 1.95583, debug = false } =
	window.wooBgBlocksCheckout || {};

function log( ...args ) {
	if ( debug ) {
		// eslint-disable-next-line no-console
		console.log( '[woo-bg-blocks-checkout]', ...args );
	}
}

log( 'init', { locale, EUR_RATE } );

function minorUnitsToFloat( value, decimalPlaces ) {
	return parseInt( value, 10 ) / Math.pow( 10, parseInt( decimalPlaces || 2, 10 ) );
}

function fmt( n ) {
	return n.toFixed( 2 ).replace( '.', ',' );
}

// Mirrors Woo_BG\Front_End\Multi_Currency::display_price_in_multiple_currencies():
// BGN stores always get a EUR secondary price; EUR stores only get a BGN
// secondary price when the site locale is bg_BG.
function dualFormat( value, rawPrice, code, decimalPlaces ) {
	if ( code === 'BGN' ) {
		const eur = minorUnitsToFloat( rawPrice, decimalPlaces ) / EUR_RATE;
		const result = `<price/> / ${ fmt( eur ) } €`;
		log( 'dualFormat BGN→EUR', { rawPrice, decimalPlaces, eur, result } );
		return result;
	}
	if ( code === 'EUR' && locale === 'bg_BG' ) {
		const bgn = minorUnitsToFloat( rawPrice, decimalPlaces ) * EUR_RATE;
		const result = `<price/> / ${ fmt( bgn ) } лв.`;
		log( 'dualFormat EUR→BGN', { rawPrice, decimalPlaces, bgn, result } );
		return result;
	}
	return value;
}

// Resolve currency code from cartItem (Store API) or fall back to PHP value.
function getCurrencyCode( cartItem ) {
	return cartItem?.prices?.currency_code || '';
}

// Synchronous store read for filter callbacks (which cannot use React hooks).
function getCartTotalsSync() {
	return select( 'wc/store/cart' )?.getCartTotals?.() || null;
}

registerCheckoutFilters( 'woo-bg-blocks-checkout', {
	// Per-item unit price in the cart/checkout/mini-cart table.
	cartItemPrice: ( value, _ext, args ) => {
		const { cartItem } = args || {};
		log( 'cartItemPrice filter', { value, cartItem, args } );
		if ( ! cartItem?.prices ) {
			return value;
		}
		return dualFormat(
			value,
			cartItem.prices.price,
			getCurrencyCode( cartItem ),
			cartItem.prices.currency_minor_unit
		);
	},

	// Per-item subtotal in the cart/checkout/mini-cart table.
	// Note: this filter is NOT called for the order summary Subtotal row or the
	// mini cart footer subtotal — those have no filter API; they are handled by
	// DOM injection below.
	subtotalPriceFormat: ( value, _ext, args ) => {
		const { cartItem } = args || {};
		log( 'subtotalPriceFormat filter', { value, cartItem, args } );
		if ( ! cartItem?.prices || ! cartItem?.totals ) {
			return value;
		}
		return dualFormat(
			value,
			cartItem.totals.line_subtotal,
			getCurrencyCode( cartItem ),
			cartItem.prices.currency_minor_unit
		);
	},

	// Total row in the order summary sidebar.
	totalValue: ( value, _ext, args ) => {
		log( 'totalValue filter', { value, args } );
		const totals = getCartTotalsSync();
		if ( ! totals ) {
			return value;
		}
		return dualFormat(
			value,
			totals.total_price,
			totals.currency_code,
			totals.currency_minor_unit
		);
	},
} );

// --- Shared DOM injection helpers ---

function buildSecondaryText( totals ) {
	if ( ! totals ) return null;
	const { currency_code: code, currency_minor_unit: minorUnit, total_items: raw } = totals;
	if ( code === 'BGN' ) {
		return `/ ${ fmt( minorUnitsToFloat( raw, minorUnit ) / EUR_RATE ) } €`;
	}
	if ( code === 'EUR' && locale === 'bg_BG' ) {
		return `/ ${ fmt( minorUnitsToFloat( raw, minorUnit ) * EUR_RATE ) } лв.`;
	}
	return null;
}

function injectIntoValueEl( valueEl, secondary ) {
	const existing = valueEl.querySelector( '.woo-bg-subtotal-secondary' );
	if ( existing?.textContent === ` ${ secondary }` ) return;
	if ( existing ) existing.remove();
	const span = document.createElement( 'span' );
	span.className = 'woo-bg-subtotal-secondary';
	span.textContent = ` ${ secondary }`;
	valueEl.appendChild( span );
}

// --- Cart/checkout order summary Subtotal row injection (via React hook) ---
// subtotalPriceFormat is per-item only and never called for the Subtotal summary
// row. Inject via useEffect + MutationObserver that re-runs on React re-renders.

function useSubtotalInjection( cartTotals ) {
	useEffect( () => {
		if ( ! cartTotals ) return;

		const secondary = buildSecondaryText( cartTotals );
		if ( ! secondary ) return;

		log( 'useSubtotalInjection', { secondary } );

		// Guard against triggering the observer on our own DOM writes.
		let injecting = false;

		const inject = () => {
			if ( injecting ) return;
			injecting = true;

			document
				.querySelectorAll( '.wc-block-components-totals-wrapper' )
				.forEach( ( wrapper ) => {
					// The Subtotal row is the first totals-item that is not the footer.
					const row = wrapper.querySelector(
						'.wc-block-components-totals-item:not(.wc-block-components-totals-footer-item)'
					);
					if ( ! row ) return;

					const valueEl = row.querySelector(
						'.wc-block-components-totals-item__value'
					);
					if ( ! valueEl ) return;

					injectIntoValueEl( valueEl, secondary );
				} );

			injecting = false;
		};

		inject();
		const raf = requestAnimationFrame( inject );

		const observer = new MutationObserver( inject );
		document
			.querySelectorAll( '.wc-block-components-totals-wrapper' )
			.forEach( ( wrapper ) => {
				observer.observe( wrapper, { childList: true, subtree: true } );
			} );

		return () => {
			cancelAnimationFrame( raf );
			observer.disconnect();
		};
	}, [ cartTotals ] );
}

// --- Mini cart footer subtotal injection (standalone, no React lifecycle) ---
// The mini cart footer uses .wc-block-mini-cart__footer-subtotal. No filter API
// exists for it. Triggered by two signals:
//   1. store subscription — cart totals changed (quantity update in open drawer)
//   2. MutationObserver on body — mini cart drawer added to DOM on first open

function runMiniCartSubtotalInjection() {
	const totals = getCartTotalsSync();
	const secondary = buildSecondaryText( totals );
	if ( ! secondary ) return;

	document
		.querySelectorAll( '.wc-block-mini-cart__footer-subtotal' )
		.forEach( ( el ) => {
			const valueEl = el.querySelector( '.wc-block-components-totals-item__value' );
			if ( ! valueEl ) return;
			injectIntoValueEl( valueEl, secondary );
		} );
}

let prevTotalItems = null;
subscribe( () => {
	const totals = getCartTotalsSync();
	const totalItems = totals?.total_items;
	if ( totalItems === prevTotalItems ) return;
	prevTotalItems = totalItems;
	log( 'mini cart subscribe re-inject', { totalItems } );
	runMiniCartSubtotalInjection();
} );

new MutationObserver( () => {
	if ( document.querySelector( '.wc-block-mini-cart__footer-subtotal' ) ) {
		runMiniCartSubtotalInjection();
	}
} ).observe( document.body, { childList: true, subtree: true } );

function RateNote() {
	const cartTotals = useSelect(
		( s ) => s( 'wc/store/cart' ).getCartTotals(),
		[]
	);

	useSubtotalInjection( cartTotals );

	log( 'RateNote render', { cartTotals } );

	if ( ! cartTotals ) return null;

	const { currency_code: code } = cartTotals;
	if ( code !== 'BGN' && ! ( code === 'EUR' && locale === 'bg_BG' ) ) return null;

	return createElement(
		'p',
		{ className: 'woo-bg-dual-totals__rate' },
		__( 'Rate: 1 EUR = 1.95583 BGN', 'bulgarisation-for-woocommerce' )
	);
}

function OrderMetaFill() {
	return createElement( ExperimentalOrderMeta, null, createElement( RateNote, null ) );
}

registerPlugin( 'woo-bg-blocks-checkout', {
	render: OrderMetaFill,
	scope: 'woocommerce-checkout',
} );

registerPlugin( 'woo-bg-blocks-cart', {
	render: OrderMetaFill,
	scope: 'woocommerce-cart',
} );

log( 'filters and plugins registered' );
