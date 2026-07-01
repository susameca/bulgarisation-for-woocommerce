# blocks-frontend

Dual BGN/EUR price display for the WooCommerce Cart and Checkout blocks
(`Woo_BG\Front_End\Checkout\Blocks`). Separate from `resources/` because it
targets `@woocommerce/blocks-checkout` and React, which the plugin's main
webpack 4 / Vue pipeline doesn't build.

```
npm install
npm run build   # writes to build/, committed and shipped in releases
npm run start   # watch mode for development
```
