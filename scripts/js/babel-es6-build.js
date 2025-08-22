/**
 * @file
 *
 * Provides the build:js command to compile *.es6.js files to ES5.
 *
 * Run build:js with --file to only parse a specific file. Using the --check
 * flag build:js can be run to check if files are compiled correctly.
 * @example <caption>Only process misc/drupal.es6.js and misc/drupal.init.es6.js</caption
 * yarn run build:js -- --file misc/drupal.es6.js --file misc/drupal.init.es6.js
 * @example <caption>Check if all files have been compiled correctly</caption
 * yarn run build:js -- --check
 *
 * @internal Remove this when Drupal core js ES6 transpiler supports contrib.
 */

'use strict';

const glob = require('glob');
const argv = require('minimist')(process.argv.slice(2));
const changeOrAdded = require('./changeOrAdded');
const check = require('./check');
const log = require('./log');

// Match only on .es6.js files.
const fileMatch = './**/*.es6.js';
// Ignore everything in node_modules
const globOptions = {
  ignore: './node_modules/**'
};
const processFiles = (error, filePaths) => {
  if (error) {
    process.exitCode = 1;
  }
  // Process all the found files.
  let callback = changeOrAdded;
  if (argv.check) {
    callback = check;
  }
  filePaths.forEach(callback);
};

if (argv.file) {
  processFiles(null, [].concat(argv.file));
}
else {
  // Use glob sync API for compatibility with glob v10+
  try {
    const filePaths = glob.sync(fileMatch, globOptions);
    processFiles(null, filePaths);
  } catch (error) {
    processFiles(error, []);
  }
}
process.exitCode = 0;
