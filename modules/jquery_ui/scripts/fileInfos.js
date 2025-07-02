/**
 * @file
 * Provides the various paths and the list of files to process or copy.
 */

const path = require('path');
const glob = require("glob");

const moduleFolder = path.resolve(__dirname, '../');
const packageFolder = `${moduleFolder}/node_modules`;
const assetsFolder = `${moduleFolder}/assets/vendor`;
const sourceFolder = 'jquery-ui';
const destFolder = 'jquery.ui';

module.exports = {
  moduleFolder,
  packageFolder,
  assetsFolder,
  sourceFolder,
  destFolder,
  filesToCopy: glob
    .sync(`${packageFolder}/${sourceFolder}/{themes,ui}/**/*.{css,png,js}`, { nodir: true })
    .map((absolutePath) => absolutePath.replace(`${packageFolder}/${sourceFolder}/`, ''))
    .filter((file) => !file.includes('/i18n/'))
};
