//    ______   __    __  __        _______
//   /      \ /  |  /  |/  |      /       \
//  /$$$$$$  |$$ |  $$ |$$ |      $$$$$$$  |
//  $$ | _$$/ $$ |  $$ |$$ |      $$ |__$$ |
//  $$ |/    |$$ |  $$ |$$ |      $$    $$/
//  $$ |$$$$ |$$ |  $$ |$$ |      $$$$$$$/
//  $$ \__$$ |$$ \__$$ |$$ |_____ $$ |
//  $$    $$/ $$    $$/ $$       |$$ |
//   $$$$$$/   $$$$$$/  $$$$$$$$/ $$/
//

// Gulp and some tools
var gulp = require("gulp-help")(require("gulp"));
var gutil = require("gulp-util");
var notifier = require("terminal-notifier");
var minify = require('gulp-minify');
var multistream = require("gulp-multistream");
var gulpSequence = require("gulp-sequence");
var rename = require("gulp-rename");
var cleanCSS = require("gulp-clean-css");


// Sass
var sass = require("gulp-sass");
var sassGlob = require("gulp-sass-glob");
var prefix = require("gulp-autoprefixer");

// Babel
var babel = require('gulp-babel');

var resourcesFolder = "./../resources";
var assetsFolder = "./../assets";

// -----------------------------------------------------------------------------
// SASS -- https://www.npmjs.com/package/gulp-sass
// SASS GLOBBING -- https://www.npmjs.com/package/gulp-sass-glob
// -----------------------------------------------------------------------------
var taskGulpSass = function () {
  return gulp.src(resourcesFolder + "/scss/*.scss")
    .pipe(sassGlob())
    .pipe(sass({
      includePaths: [
        resourcesFolder + "/scss/"
      ],
      outputStyle: "expanded"
    }))
    .on("error", function (err) {
      gutil.log(gutil.colors.black.bgRed(" SASS ERROR", gutil.colors.red.bgBlack(" " + (err.message.split("  ")[2]))));
      gutil.log(gutil.colors.black.bgRed(" FILE:", gutil.colors.red.bgBlack(" " + (err.message.split("\n")[0]))));
      gutil.log(gutil.colors.black.bgRed(" LINE:", gutil.colors.red.bgBlack(" " + err.line)));
      gutil.log(gutil.colors.black.bgRed(" COLUMN:", gutil.colors.red.bgBlack(" " + err.column)));
      gutil.log(gutil.colors.black.bgRed(" ERROR:", gutil.colors.red.bgBlack(" " + err.formatted.split("\n")[0])));

      notifier(err.message.split("\n")[0], { title: "LINE " + err.line });

      return this.emit("end");
    })
    .pipe(prefix([
      "last 3 version",
      "> 2%"
    ]))
    .pipe(multistream(gulp.dest(assetsFolder + "/css")));
};

gulp.task("sass", "Compiles your SCSS files to CSS", function () {
  taskGulpSass();
});

// -----------------------------------------------------------------------------
// BABEL ES5 to ES6
// -----------------------------------------------------------------------------
var taskGulpBabel = function () {
  return gulp.src(resourcesFolder + "/js/*.js")
    .pipe(babel({
      presets: [
        [
          '@babel/env'
        ]
      ],
    }))
    .pipe(multistream(gulp.dest(assetsFolder + "/js")));
};

gulp.task("babel", "Transpiles your ES6 to ES5", function () {
  taskGulpBabel();
});

// -----------------------------------------------------------------------------
// JS MINIFY -- https://www.npmjs.com/package/gulp-minify
// CSS MINIFY -- https://www.npmjs.com/package/gulp-clean-css
// -----------------------------------------------------------------------------
var taskJsMinify = function() {
  return gulp.src(assetsFolder + "/js/media_library.*.js")
    .pipe(minify({
      ext: {
        src: ".js",
        min: ".min.js"
      },
      ignoreFiles: ["*.min.js"],
      mangle: false
    }))
    .pipe(gulp.dest(assetsFolder + "/js"))
};

var taskCssMinify = function () {
  return gulp.src(assetsFolder + "/css/*.css")
    .pipe(cleanCSS())
    .pipe(gulp.dest(assetsFolder + "/css"))
};

gulp.task("minify", "Minifies your CSS and JS files", function() {
  taskJsMinify();
  taskCssMinify();
});

// -----------------------------------------------------------------------------
// WATCH
// -----------------------------------------------------------------------------
var taskWatch = function() {
  gulp.watch(resourcesFolder + "/scss/*.scss", ["sass"]);
};

gulp.task("watch", "Watches your source files", function() {
  taskWatch();
});

// -----------------------------------------------------------------------------
// HELPER TASKS
// -----------------------------------------------------------------------------
gulp.task("compile", [
  "sass",
  "babel"
]);

gulp.task("serve", [
  "watch",
]);

// -----------------------------------------------------------------------------
// FINAL TASKS
// -----------------------------------------------------------------------------
gulp.task("dev", gulpSequence(
    "compile",
    "serve"
  )
);

gulp.task("prod", gulpSequence(
  "compile",
  "minify"
));
