
***
## <a name="faq"></a>FAQ

### PROGRAMATICALLY
[**slick.api.php**](https://git.drupalcode.org/project/slick/blob/3.0.x/slick.api.php)


### QUICK PERFORMANCE TIPS
* Slick lazyloads are **deprecated in 2.10**, use Blazy for anything instead.
* Tick **Optimized** option on the top right of Slick optionset edit page.
* Use image style with regular sizes containing effect **crop** in the name.
  This way all images will inherit dimensions calculated once.
* Disable core library **slick-theme.css** as it contains font **slick** which
  may not be in use when using own icon font at:
  **/admin/config/media/slick/ui**
* Uninstall Slick UI at production.
* Enable Drupal cache, and CSS/ JS assets aggregation.


### OPTIONSETS
To create optionsets, go to:

  [Slick UI](/admin/config/media/slick)

Enable Slick UI sub-module first, otherwise regular **Access denied**.
They will be available at field formatter "Manage display", and Views UI.

### VIEWS AND FIELDS
Slick works with Views and as field display formatters.
Slick Views is available as a style plugin included at slick_views.module.
Slick Fields formatters included as a plugin which supports:
Image, Media, Field Collection, Paragraphs, Text.


### NESTED SLICKS
Nested slick is a parent Slick containing slides which contain individual child
slick per slide. The child slicks are basically regular slide overlays like
a single video over the large background image, only with nested slicks it can
be many videos displayed as a slideshow as well.
Use Slick Fields with Field Collection or Paragraphs or Views to build one.
Supported multi-value fields for nested slicks: Image, Text, Media.


### <a name="skins"></a>SKINS
The main purpose of skins are to demonstrate that often some CSS lines are
enough to build fairly variant layouts. No JS needed. Unless, of course, for
more sophisticated slider like spiral 3D carousel which is beyond what CSS can
do. But more often CSS will do.

Skins allow swapable layouts like next/prev links, split image or caption, etc.
with just CSS. However a combination of skins and options may lead to
unpredictable layouts, get yourself dirty. Use the provided samples to see
the working skins.

Some default complex layout skins applied to desktop only, adjust for the mobile
accordingly. The provided skins are very basic to support the necessary layouts.
It is not the module job to match your awesome design requirements.

#### Registering Slick skins:
[**slick.api.php**](https://git.drupalcode.org/project/slick/blob/3.0.x/slick.api.php#L337)

1. Copy `\Drupal\slick\Plugin\slick\SlickSkin` into your module
  `/src/Plugin/slick directory`.
2. Adjust everything accordingly: rename the file, change SlickSkin ID and
  label, change class name and its namespace, define skin name, and its CSS and
  JS assets.

The SlickSkin object has 3 supported methods: `::setSkins()`, `::setDots()`,
`::setArrows()` to have skin options for main/thumbnail/overlay displays, dots,
and arrows skins respectively.

The declared skins will be available for custom coded, or UI selections.
Be sure to clear cache since skins are permanently cached!

#### Optional skins:
* **None**

  It is all about DIY.
  Doesn't load any extra CSS other than the basic styles required by slick.
  Skins at the optionset are ignored, only useful to fetch description and
  your own custom work when not using the sub-modules, nor plugins.
  If using individual slide layout, do the layouts yourself.

* **Classic**

  Adds dark background color over white caption, only good for slider (single
  slide visible), not carousel (multiple slides visible), where small captions
  are placed over images, and animated based on their placement.

* **Full screen**

  Works best with 1 slidesToShow. Use z-index layering > 8 to position elements
  over the slides, and place it at large regions. Currently only works with
  Slick fields, use Views to make it a block. Use Slick Paragraphs to
  have more complex contents inside individual slide, and assign it to Slide
  caption fields.

* **Full width**

  Adds additional wrapper to wrap overlay video and captions properly.
  This is designated for large slider in the header or spanning width to window
  edges at least 1170px width for large monitor. To have a custom full width
  skin, simply prefix your skin with "full", e.g.: fullstage, fullwindow, etc.

* **Split**

  Caption and image/media are split half, and placed side by side. This requires
  any layout containing "split", otherwise useless.

* **Grid**

  Only reasonable if you have considerable amount of slides.
  Uses the Foundation 5.5 b-grid, and disabled if you choose your own skin
  not named Grid. Otherwise overrides skin Grid accordingly.

  **Requires:**

  `Visible slides`, `Skin Grid` for starter, A reasonable amount of slides,
  Optionset with Rows and slidesPerRow = 1.

  Avoid `variableWidth` and `adaptiveHeight`. Use consistent dimensions.
  This is module feature, older than core `Rows`, and offers more flexibility.
  Available at `slick_views`, and configurable via Views UI.

If you want to attach extra 3rd libraries, e.g.: image reflection, image zoomer,
more advanced 3d carousels, etc, simply put them into js array of the target
skin. Be sure to add proper weight, if you are acting on existing slick events,
normally < 0 (`slick.load.min.js`) is the one.

See [**slick.api.php**](https://git.drupalcode.org/project/slick/blob/3.0.x/slick.api.php#L337)
for more info on skins, including registering skins.

Other skins are available at
[Slick Extras](https://drupal.org/project/slick_extras).
Some extra skins are WIP which may not work as expected.
Use them as starters, not final products!


### GRID
To create Slick grid or multiple rows carousel, there are 3 options:

1. **One row grid managed by library:**

   [/admin/config/media/slick](/admin/config/media/slick)

   Edit the current optionset, and set

   `slidesToShow > 1, and Rows and slidesperRow = 1`

2. **Multiple rows grid managed by library:**

   [/admin/config/media/slick](/admin/config/media/slick)

   Edit current optionset, and set

   `slidesToShow = 1, Rows > 1 and slidesPerRow > 1`

3. **Multiple rows grid managed by module:**

   [Grid sample](/admin/structure/views/view/slick_x/edit/block_grid)
   from `slick_example`. Be sure to install the Slick example sub-module first.
   Requires:

   + skin **Grid**
   + `slidesToShow, Rows and slidesPerRow = 1`

The first 2 are supported by core library using pure JS approach.
The last is the Module feature using pure `CSS Foundation` b-grid.

**The key is:**

The total amount of Views results must be bigger than `Visible slides`,
otherwise broken Grid, see skin Grid above for more details.


### <a name="html-structure"></a>HTML STRUCTURE
Note, non-BEM classes are added by JS.

```html
<div class="slick">
  <div class="slick__slider slick-initialized slick-slider">
    <div class="slick__slide"> </div>
  </div>
  <nav class="slick__arrow" > </nav>
</div>
```

`asNavFor` should target `slick-initialized` ID attributes. This is taken care
of by the module if using the provided formatters, or Views styles.


### CURRENT DEVELOPMENT STATUS
A full release should be reasonable after proper feedback from the community,
some code cleanup, and optimization where needed. Patches are very much welcome.

Alpha, Beta, DEV releases are for developers only. Beware of possible breakage.

However if it is broken, unless an update is explicitly required, clearing cache
should fix most issues during DEV phases. Prior to any update, always visit:

**[/admin/config/development/performance](/admin/config/development/performance)**

And hit **Clear all caches** button once the new Slick is in place.
Regenerate CSS and JS as the latest fixes may contain changes to the assets.
Have the latest or similar release Blazy to avoid trouble in the first place.


### ROADMAP
* Bug fixes, code cleanup, optimization, and full release.

### HOW CAN YOU HELP?
Please consider helping in the issue queue, provide improvement, or helping with
documentation.

If you find this module helpful, please help back spread the love. Thanks.
