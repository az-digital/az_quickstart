/**
 * @file
 * Copy files for JS/CSS vendor dependencies from node_modules to this
 * profile's libraries folder, so they can be committed to version control
 * instead of resolved from an external registry at composer-install time.
 *
 * Files land in the same folder structure the consuming library definitions
 * already expect at /libraries/... (e.g. drupal/slick's own library expects
 * /libraries/slick/slick/slick.min.js). Drupal core's
 * LibrariesDirectoryFileFinder resolves /libraries/... paths by searching
 * sites/default/libraries, the webroot libraries/ folder, and the install
 * profile's own libraries/ folder (see
 * https://www.drupal.org/node/3099614) - so nothing needs to point at this
 * profile specifically; it's found automatically as long as az_quickstart
 * is the active install profile.
 *
 * Modeled on Drupal core's core/scripts/js/vendor-update.js.
 *
 * IMPORTANT: this overwrites libraries/slick/slick/slick.min.js with the raw
 * npm release, which is NOT jQuery 4 compatible (upstream issue:
 * https://github.com/kenwheeler/slick/issues/4350). After running this
 * script, re-apply the jQuery 4 compatibility fix (drupal.org issue
 * #3467129) to that file before committing:
 *   https://www.drupal.org/files/issues/2025-02-17/compatibility_jQuery_4.patch
 * (patch -p2 libraries/slick/slick/slick.min.js < that-file)
 * Check whether a newer slick-carousel release has fixed this upstream
 * before reapplying, in case the patch is no longer needed.
 */

/* eslint-disable no-console -- CLI script; console output is the point. */

const path = require('node:path');
const { copyFile, mkdir } = require('node:fs').promises;

const rootFolder = path.resolve(__dirname, '../../');
const packageFolder = `${rootFolder}/node_modules`;
const librariesFolder = `${rootFolder}/libraries`;

/**
 * Structure of the object defining a library to copy to libraries/.
 *
 * @typedef VendorAsset
 *
 * @prop {string} pack
 *   The name of the npm package (the folder name inside node_modules).
 * @prop {string} [folder]
 *   The folder under libraries/ to copy files into. Defaults to `pack`.
 * @prop {Array<string|{from: string, to: string}>} files
 *   Files to copy. A string if the source/destination filename and relative
 *   path match; otherwise an object with `from` (relative to the package
 *   folder) and `to` (relative to the destination folder).
 */

/** @type {VendorAsset[]} */
const ASSET_LIST = [
  {
    pack: 'slick-carousel',
    // drupal/slick's own library definition expects /libraries/slick/slick/...
    folder: 'slick',
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
  {
    pack: 'vanilla-calendar-pro',
    folder: 'vanilla-calendar-pro',
    files: [
      'styles/layout.css',
      'styles/themes/light.css',
      'index.js',
      'utils/index.js',
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
  const destFile = `${librariesFolder}/${folder}/${file.to}`;

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
