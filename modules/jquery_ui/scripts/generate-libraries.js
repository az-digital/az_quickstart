/**
 * @file
 * This file generates the libraries.yml file automatically.
 *
 * Each JS file is declared as a library and the dependencies declared in the
 * file with the call to 'define' is picked up and automatically added to the
 * Drupal library as a dependency.
 *
 * Manual declarations are kept, only auto-generated declarations are replaced.
 *
 * There needs to be an empty line between the library declarations for this
 * script to work as intended.
 */

const path = require('path');
const { readFile, writeFile } = require('fs').promises;
const { moduleFolder, packageFolder, sourceFolder, filesToCopy } = require('./fileInfos');

(async () => {
  // Update the library version in core.libraries.yml with the version
  // from the npm package.
  const { version } = JSON.parse((await readFile(`${packageFolder}/${sourceFolder}/package.json`)).toString());

  // All generated libraries share this metadata. There is no need to use YAML
  // variables to simplify, a script is taking care of this.
  const libraryMetadata = {
    version,
    license: {
      name: 'Public Domain',
      url: `https://raw.githubusercontent.com/jquery/jquery-ui/${version}/LICENSE.txt`,
      'gpl-compatible': true,
    },
  };

  /**
   * Parse the JS file and extract the necessary dependencies.
   *
   * This relies on the AMD dependency declaration inside the source files.
   * The code looks for a call to `define( [...], factory )` or
   * `define( [...] )` and extract the first argument containing the list of
   * dependencies.
   *
   * @param {string} file
   *
   * @return {Promise<string[]>}
   *  The list of dependencies as Drupal libraries names.
   */
  async function getDependencies(file) {
    let out = [];
    const absolutePath = `${packageFolder}/${sourceFolder}/ui/${file}.js`;
    const folder = path.dirname(absolutePath);
    const depsRegex = /define\( ([^)]*) \)/s;
    const code = (await readFile(absolutePath)).toString();
    if (!depsRegex.test(code)) {
      return out;
    }
    const contents = code.replace(/, factory/g, '');
    // Get the array containing the dependencies.
    const deps = /define\( ([^)]*) \)/s.exec(contents);
    // Use eval instead of JSON.parse because there are comments in the
    // dependency list.
    out = eval(deps[1]);

    return out
      // only keep relative dependencies. We hardcode the dependency to jQuery.
      .filter((dep) => dep[0] === '.')
      .map((dep) => toDrupalDependency(path.resolve(folder, dep)));
  }

  /**
   * Transforms a jQueryUI dependency name into a Drupal library name.
   *
   * Applies some special rules for effects libraries
   *
   * @param {string} dependencyPath
   *
   * @return {string}
   */
  function toDrupalDependency(dependencyPath) {
    return `${assetModule(dependencyPath)}/${libraryName(dependencyPath)}`;
  }

  /**
   * Get the library name within the module.
   *
   * @param {string} dependencyPath
   *
   * @return {string}
   */
  function libraryName(dependencyPath) {
    let drupalName = dependencyPath.replace(`${packageFolder}/${sourceFolder}/ui/`, '');

    if (drupalName.startsWith('widgets/')) {
      return drupalName.replace('widgets/', '');
    } else if (drupalName === 'effect') {
      return 'core';
    } else if (drupalName.startsWith('effects/')) {
      return drupalName.replace('effects/effect-', '');
    } else if (drupalName === 'vendor/jquery-color/jquery.color') {
      return 'internal.vendor.jquery.color';
    } else if (['core', 'widget', 'position'].includes(drupalName)) {
      return drupalName;
    }

    return `internal.${drupalName}`;
  }

  function assetModule(dependencyPath) {
    let drupalName = dependencyPath.replace(`${packageFolder}/${sourceFolder}/ui/`, '');
    if (drupalName === 'widgets/mouse') {
      return 'jquery_ui';
    }
    if (drupalName.startsWith('widgets/')) {
      return drupalName.replace('widgets/', 'jquery_ui_');
    }
    if (drupalName.startsWith('effect')) {
      return 'jquery_ui_effects'
    }
    return 'jquery_ui';
  }

  /**
   * Make sure we set the right weights to the right file.
   *
   * @param file
   *
   * @return {object}
   */
  function getSettings(file) {
    const settings = { minified: true };
    if (file === 'effect') {
      settings.weight = -9;
    } else if (!file.startsWith('effects/')) {
      settings.weight = -11;
    }
    return settings;
  }

  // Build the list of widgets that needs a CSS file.
  const cssFiles = filesToCopy
    .filter((file) => path.extname(file) === '.css')
    .map((file) => path.basename(file, '.css'));

  const jsFiles = filesToCopy
    .filter((file) => path.extname(file) === '.js')
    .map((file) => file.replace('ui/', '').replace(/\.js$/, ''));

  // Create a top-level for all the jquery_ui modules library definition.
  const libraries = jsFiles.reduce((acc, file) => {
    acc[assetModule(file)] = {};
    return acc;
  }, {});

  // For widgets the CSS dependency is implicit, make it explicit for Drupal
  // libraries.
  libraries.jquery_ui['internal.widget-css'] = {
    ...libraryMetadata,
    css: {
      component: {
        'assets/vendor/jquery.ui/themes/base/core.css': {},
      },
      theme: {
        'assets/vendor/jquery.ui/themes/base/theme.css': {},
      },
    }
  }

  for (const file of jsFiles) {
    const asset = {
      ...libraryMetadata,
      js: {
        [`assets/vendor/jquery.ui/ui/${file}-min.js`]: getSettings(file),
      },
      dependencies: ['core/jquery'].concat(await getDependencies(file)),
    };

    // The core.js file is deprecated, remove the file but keep the
    // dependencies.
    if (file === 'core') {
      asset.js = {};
    }

    // All widgets have an implicit dependency on those css files.
    if (file.startsWith('widgets/') || ['widget', 'core'].includes(file)) {
      asset.dependencies.push('jquery_ui/internal.widget-css');
    }

    // Check if a CSS file specific to the widget exists.
    if (file.startsWith('widgets/')) {
      const basename = path.basename(file);
      if (cssFiles.includes(basename)) {
        asset.css = Object.assign(asset.css || {}, {
          component: {[`assets/vendor/jquery.ui/themes/base/${basename}.css`]: {} }
        });
      }
    }

    libraries[assetModule(file)][libraryName(file)] = asset;
  }

  const librariesPath = `${moduleFolder}/jquery_ui.libraries.data.json`;
  // Declare the auto-discovered libraries.
  await writeFile(librariesPath, JSON.stringify(libraries, null, 2));
})();
