/**
 * @file
 * Copy files for JS vendor dependencies from node_modules to the assets/vendor
 * folder. JS files are minified and a sourcemap file is created in the process.
 */

const path = require('path');
const glob = require('glob');
const { copyFile, writeFile, readFile, mkdir } = require('fs').promises;
const jQueryUIProcess = require('./assets/process/jqueryui');
const { packageFolder, assetsFolder, sourceFolder, destFolder, filesToCopy } = require('./fileInfos');

const processCallbacks = {
  // This will automatically minify the files and update the destination
  // filename before saving.
  '.js': jQueryUIProcess,
};

filesToCopy.forEach(async (file) => {
  const sourceFile = `${packageFolder}/${sourceFolder}/${file}`;
  const destFile = `${assetsFolder}/${destFolder}/${file}`;
  const extension = path.extname(file);

  try {
    await mkdir(path.dirname(destFile), { recursive: true });
  } catch (e) {
    // Nothing to do if the folder already exists.
  }

  // There is a callback that transforms the file contents, we are not
  // simply copying a file from A to B.
  if (processCallbacks[extension]) {
    const contents = (await readFile(sourceFile)).toString();
    const results = await processCallbacks[extension]({ file: { from: file, to: file }, contents });

    console.log(`Process ${sourceFolder}/${file} and save ${results.length} files:\n  ${results.map(({ filename = file.to }) => filename).join(', ')}`);
    for (const { filename = file.to, contents } of results) {
      // The filename key can be used to change the name of the saved file.
      await writeFile(`${assetsFolder}/${destFolder}/${filename}`, contents);
    }
  } else {
    // There is no callback simply copy the file.
    console.log(`Copy ${sourceFolder}/${file} to ${destFolder}/${file}`);
    await copyFile(sourceFile, destFile);
  }
});
