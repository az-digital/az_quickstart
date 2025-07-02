## Contributing to paragraphs CSS code

Paragraphs are currently using Gulp and SASS tools for more efficient CSS
development. For the people that wants to contribute to paragraphs CSS code you
have two options:

1. If you want to propose CSS improvement but do not want to use our Gulp/SASS
   toolchain then just change the compiled CSS and create an issue with a patch from
   it. When the patch is accepted we will then transfer your changes to SASS and
   recompile CSS files.
2. Instead of manually changing CSS files, the recommended way is to reuse our
   Gulp/SASS process and do changes in appropriate SASS files and then recompile
   it to CSS.


## Preparing your development environment for Gulp/SASS toolchain

If you want to do __step 2.__ but do not have needed Gulp/SASS experience do not
worry, the process is not that difficult and is explained in next steps:

- First thing you need to have is nodejs server on your machine. Please check
  https://nodejs.org/en/download/package-manager/ and follow the steps of nodejs
  server installation for your operating system.

- Then change the directory to paragraphs CSS folder

  `$ cd paragraphs/css`

- Before compiling SASS files with gulp you need to install required
  dependencies with node package manager tool. In the same folder execute

  `$ npm install`

  The list of dependencies are defined in `paragraphs/css/package.json` JSON
  file.

- You are now able to compile paragraphs CSS from our SASS source files. In the
  same folder execute

  `$ npx gulp`

If you did not get any errors your local machine is now ready and with last
command you already compiled paragraphs SASS files to CSS.

For a closer look at our Gulp configuration and tasks check
paragraphs/css/gulpfile.js.


## Doing changes in CSS over SASS

Now you are ready to do the necessary changes to paragraphs CSS. First locate the
CSS selector rule you want to change in CSS and then locate this rule in the
appropriate SASS file. Do the change in the SASS file, save it and just execute
again `$ npx gulp` from your console.

When you are satisfied with the result in CSS files, create a Drupal paragraphs 
issue and a patch in standard way.


## Making sure that your changes are aligned with CSS code standards

If you are getting any warnings when executing `$ npx gulp` that are coming from
stylelint do not worry.
These warnings are coming from stylelint postcss plugin which is doing statical
checking of generated CSS files and this simply means that generated CSS code is
not compatible with paragraphs CSS coding standards.

Generally before accepting any SASS/CSS changes you need to be sure that all warnings
are fixed.
But in some cases warnings can not be avoided, in that case please use turning
rules off from SASS like explained in https://github.com/stylelint/stylelint/blob/master/docs/user-guide/configuration.md#turning-rules-off-from-within-your-css. Note that you can use also `//`
comment syntax instead of `/* ... */`

You can also just run gulp sass lint task: 

`$ npx gulp sass:lint`


## Resources

SASS is a very powerful tool and its always a good option to know your tools
better. Please check http://sass-lang.com/guide for more information on SASS
syntax and it features.
