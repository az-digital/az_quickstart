/**
 * @file
 * Copy files for JS/CSS vendor dependencies from node_modules to the
 * assets/vendor folder, so they can be committed to version control instead
 * of resolved from an external registry at composer-install time.
 *
 * Modeled on Drupal core's core/scripts/js/vendor-update.js.
 *
 * IMPORTANT: this overwrites assets/vendor/slick-carousel/slick/slick.min.js
 * with the raw npm release, which is NOT jQuery 4 compatible. After running
 * this script, re-apply the jQuery 4 compatibility fix (drupal.org issue
 * #3467129) to that file before committing:
 *   https://www.drupal.org/files/issues/2025-02-17/compatibility_jQuery_4.patch
 * (patch -p2 assets/vendor/slick-carousel/slick/slick.min.js < that-file)
 * Check whether a newer slick-carousel release has fixed this upstream
 * before reapplying, in case the patch is no longer needed.
 */

/* eslint-disable no-console -- CLI script; console output is the point. */

const path = require('node:path');
const { copyFile, mkdir } = require('node:fs').promises;

const rootFolder = path.resolve(__dirname, '../../');
const packageFolder = `${rootFolder}/node_modules`;
const assetsFolder = `${rootFolder}/assets/vendor`;

/**
 * Structure of the object defining a library to copy to assets/vendor/.
 *
 * @typedef VendorAsset
 *
 * @prop {string} pack
 *   The name of the npm package (the folder name inside node_modules).
 * @prop {string} [folder]
 *   The folder under assets/vendor/ to copy files into. Defaults to `pack`.
 * @prop {Array<string|{from: string, to: string}>} files
 *   Files to copy. A string if the source/destination filename and relative
 *   path match; otherwise an object with `from` (relative to the package
 *   folder) and `to` (relative to the destination folder).
 */

/** @type {VendorAsset[]} */
const ASSET_LIST = [
  {
    pack: '@easepick/bundle',
    folder: 'easepick--bundle',
    files: [
      { from: 'dist/index.umd.js', to: 'dist/index.umd.js' },
      { from: 'dist/index.css', to: 'dist/index.css' },
    ],
  },
  {
    pack: 'slick-carousel',
    folder: 'slick-carousel',
    files: [
      'slick/slick.min.js',
      'slick/slick.css',
      'slick/slick-theme.css',
      'slick/ajax-loader.gif',
      'slick/fonts/slick.eot',
      'slick/fonts/slick.svg',
      'slick/fonts/slick.ttf',
      'slick/fonts/slick.woff',
    ],
  },
];

function normalizeFile(file) {
  return typeof file === 'string' ? { from: file, to: file } : file;
}

/**
 * Copy a single vendored file, creating its destination folder as needed.
 */
async function copyVendorFile({ pack, folder, file }) {
  const sourceFile = `${packageFolder}/${pack}/${file.from}`;
  const destFile = `${assetsFolder}/${folder}/${file.to}`;

  await mkdir(path.dirname(destFile), { recursive: true });
  console.log(`Copy ${pack}/${file.from} to ${folder}/${file.to}`);
  await copyFile(sourceFile, destFile);
}

const copyTasks = ASSET_LIST.flatMap(({ pack, files, folder = pack }) =>
  files.map(normalizeFile).map((file) => ({ pack, folder, file })),
);

Promise.all(copyTasks.map(copyVendorFile)).catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
