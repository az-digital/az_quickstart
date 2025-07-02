
## <a name="top"> </a>CONTENTS OF THIS FILE

 * [Introduction](#introduction)
 * [Upgrading from 1.x](https://www.drupal.org/project/blazy#blazy-upgrade)
 * [Update SOP](#updating)
 * [Requirements](#requirements)
 * [Recommended modules](#recommended-modules)
 * [Installation](#installation)
 * [Installing libraries via Composer](#composer)
 * [Configuration](#configuration)
 * [theme_blazy()](#theme-blazy)
 * [Multimedia galleries](#galleries)
 * [Lightboxes](#lightboxes)
 * [SVG](#svg)
 * [WEBP](#webp)
 * [Features](#features)
 * [Troubleshooting](#troubleshooting)
 * [Aspect ratio](#aspect-ratio)
 * [Aspect ratio template](#aspect-ratio-template)
 * [Roadmap](#roadmap)
 * [FAQ](#faq)
 * [Contribution](#contribution)
 * [Maintainers](#maintainers)
 * [Notable changes](#changes)


***
## <a name="introduction"></a>INTRODUCTION
Provides integration with bLazy and or Intersection Observer API, or browser
native lazy loading to lazy load and multi-serve images to save bandwidth and
server requests. The user will have faster load times and save data usage if
they don't browse the whole page.

Check out [project home](https://www.drupal.org/project/blazy) for most updated
info.

***
## <a name="first"> </a>FIRST THINGS FIRST!
Blazy and its sub-modules are tightly coupled. Be sure to have matching versions
or the latest release date in the least. DEV for DEV, Beta for Beta/RC,
etc. Mismatched versions (DEV vs. Full release) may lead to errors, except for
minor versions like Beta vs. RC. Mismatched branches (1.x vs. 2.x) will surely
be errors, unless declared clearly as supported. What is `coupled`? Blazy
sub-modules are dependent on Blazy, just like Blazy depends on core Media.
If core Media is not installed, Blazy is not usable. In the case of Blazy, it is
a bit `tighter` since it also acts as a DRY buster aka boilerplate reducer for
many similar sub-modules with some degree of difference. If confusing, just
match the latest releases.
We tried to minimize this issue, but if that happens you are well informed.

***
## <a name="requirements"> </a>REQUIREMENTS
Core modules:
1. Media
2. Filter

***
## <a name="recommended-modules"> </a>RECOMMENDED LIBRARIES/ MODULES
For better admin help page, either way will do, ordered by recommendation:

* `composer require league/commonmark`
* `composer require michelf/php-markdown`
* [Markdown](https://www.drupal.org/project/markdown)

To make reading this README a breeze at [Blazy help](/admin/help/blazy_ui)


### MODULES THAT INTEGRATE WITH OR REQUIRE BLAZY
* [Ajaxin](https://www.drupal.org/project/ajaxin)
* [Intersection Observer](https://www.drupal.org/project/io)
* [Blazy PhotoSwipe](https://www.drupal.org/project/blazy_photoswipe)
* [GridStack](https://www.drupal.org/project/gridstack)
* [Outlayer](https://www.drupal.org/project/outlayer)
* [Intense](https://www.drupal.org/project/intense)
* [Mason](https://www.drupal.org/project/mason)
* [Slick](https://www.drupal.org/project/slick)
* [Slick Lightbox](https://www.drupal.org/project/slick_lightbox)
* [Slick Views](https://www.drupal.org/project/slick_views)
* [Slick Paragraphs](https://www.drupal.org/project/slick_paragraphs)
* [Slick Browser](https://www.drupal.org/project/slick_browser)
* [Splide](https://www.drupal.org/project/splide)
* [Splidebox](https://www.drupal.org/project/splidebox)
* [Jumper](https://www.drupal.org/project/jumper)
* [Zooming](https://www.drupal.org/project/zooming)
* [ElevateZoom Plus](https://www.drupal.org/project/elevatezoomplus)
* [Ultimenu](https://www.drupal.org/project/ultimenu)

Most duplication efforts from the above modules will be merged into
`\Drupal\blazy\Dejavu`, or anywhere else namespaces.


**What dups?**

The most obvious is the removal of formatters from Intense, Zooming,
Slick Lightbox, Blazy PhotoSwipe, and other (quasi-)lightboxes. Any lightbox
supported by Blazy can use Blazy, or Slick formatters if applicable instead.
We do not have separate formatters when its prime functionality is embedding
a lightbox, or superceded by Blazy.

Blazy provides a versatile and reusable formatter for a few known lightboxes
with extra advantages:

lazyloading, grid, multi-serving images, Responsive image,
CSS background, captioning, etc.

Including making those lightboxes available for free at Views Field for
File entity, Media and Blazy Filter for inline images.

If you are developing lightboxes and using Blazy, I would humbly invite you
to give Blazy a try, and consider joining forces with Blazy, and help improve it
for the above-mentioned advantages. We are also continuously improving and
solidifying the API to make advanced usages a lot easier, and DX friendly.
Currently, of course, not perfect, but have been proven to play nice with at
least 7 lightboxes, and likely more.


### SIMILAR MODULES
[Lazyloader](https://www.drupal.org/project/lazyloader)


***
## <a name="installation"> </a>INSTALLATION
1. **MANUAL:**

   Install the module as usual, more info can be found on:

   [Installing Drupal Modules](https://drupal.org/node/1897420)

2. **COMPOSER:**

   See [Composer](#composer) section below for details.


***
## <a name="configuration"> </a>CONFIGURATION
Visit the following to configure and make use of Blazy:

1. [/admin/config/media/blazy](/admin/config/media/blazy)

   Enable Blazy UI sub-module first, otherwise regular **404|403**.
   Contains few global options. Blazy UI can be uninstalled at production later
   without problems.

2. Visit any entity types:

   + [Content types](/admin/structure/types)
   + [Block types](/admin/structure/block/block-content/types)
   + `/admin/structure/paragraphs_type`
   + etc.

   Use Blazy as a formatter under **Manage display** for the supported fields:
   Image, Media, Entity reference, or even Text.

3. `/admin/structure/views`

   Use `Blazy Grid` as standalone blocks, or pages.


### <a name="theme-blazy"> </a>THEME_BLAZY()
Since 2.17, on your permissions at [Blazy UI Use theme_blazy()](/admin/help/blazy_ui),
`theme_blazy()` is now capable to replace sub-modules `theme_ITEM()` contents,
e.g.: `theme_slick_slide()`, `theme_splide_slide()`, `theme_mason_box()`, etc.
At 3.x, we'll no longer ask for permissions, please be sure to test it out to
spot the problems earlier, or migrate your overrides earlier.

The `theme_blazy()` has been used all along, the only difference is captions
which are now included as inherent part of `theme_blazy()` including thumbnail
captions seen at sliders.

Repeat, not replacing their established `theme_ITEM()`, just their contents when
we all have dups with IMAGE/MEDIA + CAPTIONS constructs. It is not a novel
thing, see `block.html.twig` with its variants, etc.
Not a sudden course of actions, it was carefully planned since
[2.x-RC1](https://git.drupalcode.org/project/blazy/-/blob/8.x-2.0-rc1/src/BlazyManager.php#L180), 4 years ago from 2023, and never made it till 2.17.

If you see no difference, nothing to do. If any, be sure it is not caused by
your non-updated overrides which should be updated prior to blazy:3.x.
Only report if this is caused by blazy's mistake. Kindly provide markup
comparison, or helpful screenshots, to spot the issues better.

#### Profits:
+ Tons of dups are reduced which is part of Blazy's job descriptions above.
+ Minimal maintenance for many of Blazy sub-modules.
+ More cool kid features like hoverable effects, etc. will be easier to apply.
+ When Blazy supports extra captions like File description for SVG, it will be
  available immediately to all once, rather than updating each modules to
  support it due their hard-coded natures.

#### Non-profits:
+ Overrides should be taken seriously from now on, or as always. Perhaps CSS
  overrides are the safest. Or at most a `hook_theme_suggestions_alter()`.
+ One blazy stupid mistake, including your override, kills em all. We'll work
  it out at Bugs reports if blazy's. It happens, and the world does not end yet.

#### Custom work migrations from theme_ITEM() into theme_blazy():
+ `THEME_preprocess_blazy()`
+ `hook_blazy_caption_alter(array &$element, array $settings, array $context)`
+ For more `hook_alter`: `grep -r ">alter(" ./blazy`, or see `blazy.api.php`
+ Use `settings.blazies` object to provoke HTML changes conditionally via the
  provided settings alters. Samples are in `blazy.api.php`, more in sub-modules.
+ If you can bear a headache, replace or decorate Blazy services.
+ As last resorts, override `blazy.html.twig`. Headaches are yours in the long
  run. FYI, even the author, me, never touch this file in any custom works.
  The above suffices at 100% own cases.

### <a name="galleries"> </a> USAGES: BLAZY FOR MULTIMEDIA GALLERY VIA VIEWS UI
#### Using **Blazy Grid**
1. Add a Views style **Blazy Grid** for entities containing Media or Image.
2. Add a Blazy formatter for the Media or Image field.
3. Add any lightbox under **Media switcher** option.
4. Limit the values to 1 under **Multiple field settings** > **Display**, if
   any multi-value field.

#### Without **Blazy Grid**
If you can't use **Blazy Grid** for a reason, maybe having a table, HTML list,
etc., try the following:

1. Add a CSS class under **Advanced > CSS class** for any reasonable supported/
   supportive lightbox in the format **blazy--LIGHTBOX-gallery**, e.g.:
   + **blazy--colorbox-gallery**
   + **blazy--flybox-gallery**
   + **blazy--intense-gallery**
   + **blazy--mfp-gallery** (Magnific Popup)
   + **blazy--photoswipe-gallery**
   + **blazy--slick-lightbox-gallery**
   + **blazy--splidebox-gallery**
   + **blazy--zooming-gallery**

  Note the double dashes BEM modifier "**--**", just to make sure we are on the
  same page that you are intentionally creating a blazy LIGHTBOX gallery.
  All this is taken care of if using **Blazy Grid** under **Format**.
  The View container will then have the following attributes:

  `class="blazy blazy--LIGHTBOX-gallery ..." data-blazy data-LIGHTBOX-gallery`

2. Add a Blazy formatter for the Media or Image field.
3. Add the relevant lightbox under **Media switcher** option based on the given
   CSS class at #1.

#### Bonus
* With [Splidebox](https://drupal.org/project/splidebox), this can be used to
  have simple profile, author, product, portfolio, etc. grids containing links
  to display them directly on the same page as ajaxified lightboxes.
* With [IO](https://drupal.org/project/io), this can be used to have simple
  and modern Views infinite pagers as grid displays.
* With the new 2.17 `theme_blazy()` as a replacement for sub-modules
  `theme_ITEM()` contents, it will be easier to have hoverable product effects
  like seen at many commercial e-commerce themes.


#### <a name="views-gotchas"> </a>VIEWS GOTCHAS
Be sure to leave `Use field template` under `Style settings` unchecked.
If checked, the gallery is locked to a single entity, that is no Views gallery,
but gallery per field. The same applies when using Blazy formatter with VIS/IO
pager, alike, or inside Slick Carousel, GridStack, etc. If confusing, just
toggle this option, and you'll know which works. Only checked if Blazy formatter
is a standalone output from Views so to use field template in this case.

Check out the relevant sub-module docs for details.

***
## <a name="lightboxes"> </a>LIGHTBOXES
All lightbox integrations are optional. Meaning if the relevant modules and or
libraries are not present, nothing will show up under `Media switch` option.
Except for the new default **Flybox** since 2.17.

Clear cache if they do not appear as options due to being permanently cached.

Most lightboxes, not all, supports (responsive) image, (local|remote) video.
Known lightboxes which has supports for Responsive image:
* Colorbox, Magnific popup, Slick Lightbox, Splidebox, Blazy PhotoSwipe.
* Magnific Popup/ Splidebox also supports picture.
* Splidebox also supports AJAX contents.
* Others might not.

### Blazy has two builtin minimal lightboxes:
* **Blazybox**, seen at Intense, IO Browser, Slick Browser, ElevateZoomPlus,
  etc. Normally used as a fallback when the lightbox doesn't support multimedia.
* **Flybox**, a non-disruptive lightbox aka picture in picture window, as an
  option under Media Switcher since 2.17. It was meant for (remote) video,
  audio, soundcloud, not images. Best with non grid elements to allow viewers
  browsing the rest of page while watching videos, or listening to audios, as in
  picture in picture mode. Please create an issue to sponsor the potentials.
  **Potentials**:
  + Auto-pop/flyout the Flybox when the element is visible like for ads, etc.
  + Merge Flybox with Zooming, ElevateZoomPlus, and other lightboxes.


### Lightbox requirements
* Colorbox, PhotoSwipe, etc. requires both modules and their libraries present.
* Magnific Popup, requires only libraries to be present:
  + `/libraries/magnific-popup/dist/jquery.magnific-popup.min.js`
  The reason for no modules are being required because no special settings, nor
  re-usable options to bother provided by them. Aside from the fact, Blazy has
  its own loader aka initializer for advanced features like multimedia (remote
  |local video), or (responsive|picture) image, fieldable captions, etc. which
  are not (fully) shipped/ supported by these modules.

### <a name="dompurify"> </a> Lightbox captions with DOMPurify
Install DOMPurify using composer, see [COMPOSER](#composer) section:

* `composer require npm-asset/dompurify`

* Or, if you prefer, you can download DOMPurify directly from:
  [DOMPurify](https://github.com/cure53/DOMPurify/releases/latest)

  From the above link, you can download a zip or tar.gz archive file.
  To avoid security issues, please only install the dist directory, and
  nothing else from the archive. The composer command above will install
  the whole package.

Blazy lightboxes allows you to place a caption within lightboxes.
If you wish to use HTML in your captions, you must install the DOMPurify
library. In your `libraries` folder, you will need, either one:
* `DOMPurify/dist/purify.min.js`
* `dompurify/dist/purify.min.js`

If using Colorbox module, be sure to use their supported path to avoid dup
folders. Blazy will pick up whichever available, no problem.

The DOMPurify library is optional. Without DOMPurify, Blazy (sub)-modules
will just sanitize all captions server-side, or the very basic ones.

***
## <a name="svg"> </a>SVG
Install the SVG Sanitizer using composer, see [COMPOSER](#composer) section:

`composer require enshrined/svg-sanitize`

[Read more](https://github.com/darylldoyle/svg-sanitizer)

Blazy does not want to ship it in its `composer.json` for serious reasons,
and will disable the option for Inline SVG if not installed.

Since 2.17, the formatter **Blazy Image with VEF (deprecated)** was re-purposed
to support SVG files, instead. The name is now **Blazy File**.
Core **Image** widget doesn't support SVG files, to upload SVG use **File**:
* [/admin/structure/types/manage/page/fields](/admin/structure/types/manage/page/fields)
  + *Add a new field > Reference > File* for simple needs.
  + Enable *Description field* for SVG captions.
  + Alternatively, choose *Reference > Other > File* for more complex needs.
* [/admin/structure/types/manage/page/fields](/admin/structure/types/manage/project/page)
  + Choose **Blazy File**, and adjust anything accordingly.

The **Blazy File** can also be used for Image when SVG extension is available,
otherwise just use **Blazy Image** instead. It is kept distinct so to have
relevant form items specific for SVG files.

This is the most basic SVG in core without installing another module, and
Blazy can display it just fine either as inline SVG, or embedded SVG in IMG.

For more robust solutions, consider: SVG Image Field, SVG Image, etc.

**FYI**
* The latter will override all core formatters and widgets which makes it hard
  to uninstall without deleting many things when you have images anywhere.
  Blazy works fine with this module all along.
* The SVG form options owe credits to `SVG Image Field` module. And to honor it,
  **Blazy File** provides supports for its field type so to have Grid, and
  various Blazy features, including SVG carousels, etc. It is still WIP, but
  just fine.
* The SVG title element owes credits to `SVG Formatter`.
* If the SVG is smaller than the expected, try adding `width: 100%` to it.

***
## <a name="webp"> </a>WEBP
Drupal 9.2 has supports for WEBP conversions at Image styles admin page via
**Convert WEBP**. Only if you are concerned about old browsers, Blazy supports
it via a polyfill at Blazy UI under **No JavaScript**, be sure to NOT check it.

**Benefits**:
* Modern browsers will continue using clean IMG without being forced to use
  unnecessary PICTURE for the entire WEBP extensions.
* Old browsers will have a PICTURE if they don't support WEBP.

***
## <a name="features"> </a>FEATURES
* Works absurdly fine at IE9 for Blazy 2.6.
* Works without JavaScript within/without JavaScript browsers.
* Works with AMP, or static/ archived sites, e.g.: Tome, HTTrack, etc.
* Supports modern Native lazyload since [incubation](https://drupal.org/node/3104542)
  before Firefox or core had it, or old `data-[src|srcset]` since eons. Must be
  noted very clearly due to some thought Blazy was retarded from core.
* Lightboxes: Colorbox, Magnific Popup, Splidebox, PhotoSwipe, etc. with
  multimedia lightboxes.
* Supports Image, Responsive image, (local|remote|iframe) videos, SVG, DIV
  either inline, fields, views, or within lightboxes.
* Supports Instagram, Pinterest, Twitter, Youtube, Vimeo, Soundcloud, Facebook
  within some lightboxes, since 2.17.
* Multi-serving lazyloaded images, including multi-breakpoint CSS backgrounds.
* Field formatters: Blazy with Media integration.
* Blazy Grid formatter and Views style for multi-value Image, Media and Text:
  CSS3 Columns, Grid Foundation, Flexbox, Native Grid.
* Supports inline galleries, and grid or CSS3 Masonry via Blazy Filter.
  Enable Blazy Filter at **/admin/config/content/formats**.
* Simple shortcodes for inline galleries, check out **/filter/tips**.
* Delay loading for below-fold images until 100px (configurable) before they are
  visible at viewport.
* A simple effortless CSS loading indicator.
* It doesn't take over all images, so it can be enabled as needed via Blazy
  formatter, or its supporting modules.


### OPTIONAL FEATURES
* Views fields for File Entity and Media integration, see:
  + [IO Browser](https://www.drupal.org/project/io)
  + [Slick Browser](https://www.drupal.org/project/slick_browser).
* Views style plugin `Blazy Grid` for CSS3 Columns, Grid Foundation, Flexbox,
  and Native Grid.

***
## <a name="maintainers"> </a>MAINTAINERS/CREDITS
* [Gaus Surahman](https://www.drupal.org/user/159062)
* [geek-merlin](https://www.drupal.org/u/geek-merlin)
* [sun](https://www.drupal.org/u/sun)
* [gambry](https://www.drupal.org/u/gambry)
* [Contributors](https://www.drupal.org/node/2663268/committers)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## READ MORE
See the project page on drupal.org for more updated info:

* [Blazy module](https://www.drupal.org/project/blazy)

See the bLazy docs at:

* [Blazy library](https://github.com/dinbror/blazy)
* [Blazy website](https://dinbror.dk/blazy/)
