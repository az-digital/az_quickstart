
# <a name="top"> </a>CONTENTS OF THIS FILE

 * [Introduction](#introduction)
 * [Broken vs. working library versions](#broken)
 * [Requirements](#requirements)
 * [Recommended modules](#recommended-modules)
 * [Features](#features)
 * [Installation](#installation)
 * [Uninstallation](#uninstallation)
 * [Configuration](#configuration)
 * [Slick Formatters](#formatters)
 * [Troubleshooting](#troubleshooting)
 * [FAQ](#faq)
 * [Contribution](#contribution)
 * [Maintainers](#maintainers)

***
## <a name="introduction"></a>INTRODUCTION

Visit **/admin/help/slick_ui** once Slick UI installed to read this in comfort.

Slick is a powerful and performant slideshow/carousel solution leveraging Ken
Wheeler's [Slick Carousel](https://kenwheeler.github.io/slick).

Slick has gazillion options, please start with the very basic working
samples from [Slick Example](https://drupal.org/project/slick_extras) only if
trouble to build slicks. Spending 5 minutes or so will save you hours in
building more complex slideshows.

Slick library v2.x was out 2015/9/21, and is not supported now, 2023/09.

***
## <a name="first"> </a>FIRST THINGS FIRST!
Read more at:
* [Github](https://git.drupalcode.org/project/blazy/-/blob/3.0.x/docs/README.md#first-things-first)
* [Blazy UI](/admin/help/blazy_ui#first)


## <a name="broken"> </a>BROKEN VS. WORKING LIBRARY VERSIONS.
+ **Supported versions**: Slick library **>= 1.6 && <= 1.8.0**.
+ **1.8.0 has double misleading versions** aka breaking change found in 2021/10:
  be sure versions in Slick package.json matches the version written in
  slick.js. The reason, release 1.8.1 with package.json 1.8.1 has misleading
  version 1.8.0 written in slick.js. If they don't match, they are not supported
  by this module aka broken, only fixable with hilarious elaborate works aka
  headaches.  
  **What breaks**: dots, nested divities, out of sync navigation given less
  slides, etc.
+ **Battle-tested version**: 1.6.0. If you see problems with later versions
  above, 1.6.0 is the only least problematic one. It lacks of new
  not-so-essential features, but also lacks of problems.


***
## <a name="requirements"> </a>REQUIREMENTS
1. Slick library:

   **Standard version**

   * Download Slick archive **>= 1.6 && <= 1.8.0** from
     [Slick releases](https://github.com/kenwheeler/slick/releases)
   * Master branch (1.9.0 but in code as 1.8.1) is not supported, and had been
     removed from official repo 2019. Instead download, etract and rename one of
     the official slick releases to `slick`, so the assets are at:
     + **/libraries/slick/slick/slick.css**
     + **/libraries/slick/slick/slick-theme.css** (optional)
     + **/libraries/slick/slick/slick.min.js**
     + **/libraries/slick/package.json**
     + Or any path supported by core library finder as per Drupal 8.9+.
     **Important!**: The version in **package.json** must match with
     **slick.min.js** and at most **1.8.0**. If not, they are not supported,
     and will break dots, markups, etc.
   * If using composer the library will be downloaded to the directory
     `slick-carousel`; this is fine, the module will still be able to find the
     library, it does not have to be moved or renamed.
   * Slick v1.6.0 is the only version that is fully supported - it is
     battle-tested and has fewer issues, it only lacks some newer features such
     as extra lazy-load which was deprecated in Slick:2.10 anyway.

   **Accessible version**

   * Download the Accessible Slick archive **>= 1.0.1** from
     [Accessible Slick releases](https://github.com/Accessible360/accessible-slick/releases)
   * Extract and rename the folder to `accessible-slick`, so the
     assets are at:
     + **/libraries/accessible-slick/slick/slick.css**
     + **/libraries/accessible-slick/slick/slick-theme.css** (optional)
     + **/libraries/accessible-slick/slick/slick.min.js**
     + Or any path supported by core library finder as per Drupal 8.9+.
   * **Warning!** This library was based on broken 1.8.1 version, so it
     inherits the above-mentioned problems. A workaround was provided, but it
     demands your attentions on few specific options as prompted when saving
     the Optionset forms: `rows`, `slidesPerRow`, `slidesToShow`, etc.

2. [Download jqeasing](https://github.com/gdsmith/jquery.easing), so available:  

   **/libraries/easing/jquery.easing.min.js**

   This is CSS easing fallback for non-supporting browsers.

3. [Blazy](https://drupal.org/project/blazy)

   To reduce DRY stuffs, and as a bonus, advanced lazyloading such as delay
   lazyloading for below-fold sliders, iframe, (fullscreen) CSS background
   lazyloading, breakpoint dependent multi-serving images, lazyload ahead for
   smoother UX. Check out Blazy installation guides!


***
## <a name="installation"> </a>INSTALLATION
Be sure to read the entire docs and form descriptions before working with
Slick to avoid headaches for just ~15-minute read.

1. **MANUAL:**

   Install the module as usual, more info can be found on:

   [Installing Drupal 8 Modules](https://drupal.org/node/1897420)

2. **COMPOSER:**

   ```
   $ composer require npm-asset/slick-carousel:1.8.0 \
   npm-asset/jquery-mousewheel \
   npm-asset/jquery.easing \
   drupal/blazy \
   drupal/slick
   ```
   See [Blazy composer](/admin/help/blazy_ui#composer) for details.

***
## <a name="uninstallation"> </a>UNINSTALLATION
Please check out below for solutions:  

* [Slick 7.x](https://www.drupal.org/project/slick/issues/3261726#comment-14406766)
* [Slick 8.x+](https://www.drupal.org/project/slick/issues/3257390)


***
## <a name="configuration"> </a>CONFIGURATION
Visit the following to configure Slick:

1. `/admin/config/media/slick`

   Enable Slick UI sub-module first, otherwise regular **Access denied**.

2. Visit any entity types:

  + `/admin/structure/types`
  + `/admin/structure/block/block-content/types`
  + `/admin/structure/paragraphs_type`
  + etc.

   Use Slick as a formatter under **Manage display** for multi-value fields:
   Image, Media, Paragraphs, Entity reference, or even Text.
   Check out [SLICK FORMATTERS](#formatters) section for details.

3. `/admin/structure/views`

   Use Slick as standalone blocks, or pages.


***
## <a name="recommended-modules"> </a>RECOMMENDED MODULES
Slick supports enhancements and more complex layouts.

### OPTIONAL
* [Media](https://drupal.org/project/media), to have richer contents: image,
  video, or a mix of em. Included in core since D8.6+.
* [Colorbox](https://drupal.org/project/colorbox), to have grids/slides that
   open up image/ video in overlay.
* [Picture](https://drupal.org/project/picture) for more robust responsive
  image. Included in core as Responsive Image since D8.
* [Paragraphs](https://drupal.org/project/paragraphs), to get more complex
  slides at field level.
* [Field Collection](https://drupal.org/project/field_collection), idem ditto.
* [Mousewheel](https://github.com/brandonaaron/jquery-mousewheel) at:
  + **/libraries/mousewheel/jquery.mousewheel.min.js**


### SUB-MODULES
The Slick module has several sub-modules:
* Slick UI, included, to manage optionsets, can be uninstalled at production.

* Slick Media, included as a plugin since Slick 2.x.

* [Slick Views](https://drupal.org/project/slick_views)
  to get the most complex slides you can imagine.

* [Slick Paragraphs](https://drupal.org/project/slick_paragraphs)
  to get more complex slides at field level.

* [Slick Lightbox](https://drupal.org/project/slick_lightbox)
  to get Slick within lightbox for modern features: responsive, swipes, etc.

* [Slick Entityreference](https://drupal.org/project/slick_entityreference)
  to get Slick for entityreference and entityreference revisions.

* [ElevateZoom Plus](https://drupal.org/project/elevatezoomplus)
  to get ElevateZoom Plus with Slick Carousel and lightboxes, commerce ready.

* [Slick Example](https://drupal.org/project/slick_extras)
  to get up and running Slick quickly.

***
## <a name="features"></a>FEATURES
* Fully responsive. Scales with its container.
* Uses CSS3 when available. Fully functional when not.
* Swipe enabled. Or disabled, if you prefer.
* Desktop mouse dragging.
* Fully accessible with arrow key navigation.
* Built-in lazyLoad, and multiple breakpoint options.
* Random, autoplay, pagers, arrows, dots/text/tabs/thumbnail pagers etc...
* Supports pure text, responsive image, iframe, video carousels with
  aspect ratio. No extra jQuery plugin FitVids is required. Just CSS.
* Works with Views, core and contrib fields: Image, Media Entity.
* Optional and modular skins, e.g.: Carousel, Classic, Fullscreen, Fullwidth,
  Split, Grid or a multi row carousel.
* Various slide layouts are built with pure CSS goodness.
* Nested sliders/overlays, or multiple slicks within a single Slick via Views.
* Some useful hooks and drupal_alters for advanced works.
* Modular integration with various contribs to build carousels with multimedia
  lightboxes or inline multimedia.
* Media switcher: Image linked to content, Image to iframe, Image to colorbox,
  Image to photobox.
* Cacheability + lazyload = light + fast.
