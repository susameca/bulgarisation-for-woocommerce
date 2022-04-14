/**
 * The internal dependencies.
 */
const utils = require('../lib/utils');

const env = utils.detectEnv();

module.exports = {
  path: utils.distPath(),
  filename: `[name]-[hash]${env.filenameSuffix}.js`,
  chunkFilename: '[chunkhash].js',
};
