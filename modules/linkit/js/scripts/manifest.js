// CKEditor 5 plugins require a manifest file, which must be generated from
// CKEditor source, and typically requires several manual steps. That process is
// automated here.

const fs = require('fs');
const { exec } = require('child_process');

const manifestPath =
  './node_modules/ckeditor5/build/ckeditor5-dll.manifest.json';

if (!fs.existsSync(manifestPath)) {
  console.log(
    'CKEditor manifest not available. Generating one now. This takes a while, but should only need to happen once.',
  );
  exec(
    'yarn --cwd ./node_modules/ckeditor5 install',
    (error, stdout, stderr) => {
      if (error) {
        console.log(`error: ${error.message}`);
        return;
      }

      console.log(stdout);
      exec(
        'yarn --cwd ./node_modules/ckeditor5 dll:build',
        (error, stdout, stderr) => {
          if (error) {
            console.log(`error: ${error.message}`);
            return;
          }

          console.log(stdout);
          if (fs.existsSync(manifestPath)) {
            console.log(`Manifest created at  ${manifestPath}`);
          } else {
            console.log('error: Unable to create manifest.');
          }
        },
      );
    },
  );
} else {
  console.log(`Manifest present at ${manifestPath}`);
}
