const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	externals: {
		...defaultConfig.externals,
		// @woocommerce/* packages aren't handled by @wordpress/dependency-extraction-webpack-plugin,
		// so we map the import to the runtime global and add wc-blocks-checkout to PHP deps manually.
		'@woocommerce/blocks-checkout': [ 'wc', 'blocksCheckout' ],
	},
};
