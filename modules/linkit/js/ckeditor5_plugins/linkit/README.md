This plugin is largely based on CKEditor 5's [block plugin widget tutorial](https://ckeditor.com/docs/ckeditor5/latest/framework/guides/tutorials/implementing-a-block-widget.html),
but with added documentation to facilitate better understanding of CKEditor 5
plugin development and other minor changes.

Within `/src` are the multiple files that will be used by the build process to
become a CKEditor 5 plugin in `/build`. Technically, everything in these files
could be in a single `index.js` - the only file the MUST be present is
`/src/index.js`. However, splitting the plugin into concern-specific files has
maintainability benefits.
