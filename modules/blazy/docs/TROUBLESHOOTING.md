
***
## <a name="troubleshooting"></a>TROUBLESHOOTING
* Masonry (Flexbox and or Native Grid) are messed up, try uninstalling BigPipe.
  Before 2.17, we tried hard to be BigPipe-compatible, but it broke things like
  Masonry on infinite pager VIS/ IO, etc. Not always, but applicable if any
  other BigPipe-related JS/ CSS issues as seen at Slick/ Splide, etc.
* Any javascript-related issues might no longer be valid when
  `No JavaScript lazy` enabled. Unless the exceptions, things that Native
  doesn't support (Blur, BG, Video, etc.) are met, or for those who still
  support old IEs, and cannot ditch lazyloader script, yet.
* Switch to core themes for a mo, in case your custom theme is the culprit.
* Blazy and its sub-modules -- Slick, GridStack, etc. are tightly coupled.
  Be sure to have the latest release date or matching versions in the least.
  DEV for DEV, Beta for Beta, etc. Mismatched versions may lead to errors
  especially before having RCs. Mismatched branches will surely be errors,
  unless clearly declared as supported or required.
* Resizing is not supported. Just reload the page. **The main reason**:
  When being resized, the browser gave no data about pixel ratio from desktop
  to mobile, not vice versa. Unless delayed for 4s+, not less, which is of
  course unacceptable.
* Press F12 at any browser, and see the errors at the browser console. Any JS
  error will prevent Blazy from working identified by eternal blue loaders.
* Be sure to view browser console as anonymous, not only as admin users.
* Images are collapsed. Solution: choose one of the Aspect ratio.
* Images or videos aren't responsive. Solution: choose one of the Aspect ratio.
* Images are distorted. Solution: choose the correct Aspect ratio. If unsure,
  choose "fluid" to let the module calculate aspect ratio automatically.

  [Check out few aspect ratio samples](https://git.drupalcode.org/project/blazy/tree/docs/ASPECT_RATIO.md)


### 1. JavaScript Errors
Any references to bLazy library is no longer required for forked version at 2.6.
**Symptons**:
Blazy is not defined. Images are gone, only eternal blue loader is flipping like
a drunk butterfly.

**Solution**:
Ensure that there are no extra errors. Steps:

* Switch to core themes for a moment in case your theme is the culprit. Any
  theme JS errors might break Blazy. Press F12 at browsers to fix them one by
  one.
* Try disabling `Disconnect` option under IO.


### 2. BLAZY GRID WITH SINGLE VALUE FIELD (D7 ONLY)
This is no issue at D8. Blazy Grid formatter is designed for multi-value fields.
Unfortunately no handy way to disable formatters for single value at D7. So
the formatter is available even for single value, but not actually
functioning. Please ignore it till we can get rid of it at D7, if possible,
without extra legs.

### 3. MIN-WIDTH
If the images appear to be shrink within a **floating** container, add
some expected width or min-width to the parent container via CSS accordingly.
Non-floating image parent containers aren't affected.

### 4. MIN-HEIGHT
Add a min-height CSS to individual element to avoid layout reflow if not using
**Aspect ratio** or when **Aspect ratio** is not supported such as with
Native Grid, etc. Otherwise some collapsed image containers will defeat the
purpose of lazyloading. When using CSS background, the container may also be
collapsed.

#### SOLUTIONS
Both layout reflow and lazyloading delay issues are actually taken care of
if **Aspect ratio** option is enabled in the first place.

Adjust, and override blazy CSS/ JS files accordingly.

### 5. BLAZY FILTER
Blazy Filter must run after **Align/ Caption filters** as otherwise the required
CSS class `b-lazy` will be moved into `<figure>` elements and make Blazy fail
with JS error due to not finding the required `SRC` and `[data-src]` attributes.
**Align/ Caption filters** output are respected and moved into Blazy markups
accordingly when Blazy Filter runs after them.

Blazy Filter is useless and broken when you enable **Media embed** or
**Display embedded entities**. You can disable Blazy Filter in favor of Blazy
formatter embedded inside **Media embed** or **Display embedded entities**
instead. However it might be useful for User Generated Contents (UGC) where
Entity/Media Embed are likely more for privileged users, editors, admins, alike.
Or when Entity/Media Embed is disabled.

### 6. INTERSECTION OBSERVER API
This API will not be used if `No JavaScript lazy` option enabled unless the
exceptions, things that Native doesn't support (Blur, BG, Video, etc.) are met.
* **IntersectionObserver API** is not loading all images, try disabling
  **Disconnect** option at Blazy UI.
* **IntersectionObserver API** is not working with Slick `slidesToShow > 1`, try
  disabling Slick `centerMode`. If still failing, choose `slider` or `unlazy`
  under `Loading priority` formatter option.

**FYI:**
IO is also used for infinite pager and lazyloaded blocks like seen at IO.module.

### 7. BLUR IMAGE EFFECT
`/admin/config/media/blazy`

The `Image effect` Blur will override `Placeholder` option.
Will use `Thumbnail style` option at Blazy formatters for the placeholder with
fallback to core `Thumbnail` image style.

**For best results:**

* Choose `Aspect ratio` option, non-fluid is better;
* Use similar aspect ratio for both `Thumbnail style` and `Image style`;
* Adjust `Offset` and or `threshold`;
* The smaller the better.

Use `hook_blazy_image_effects_alter()` to add more effects -- curtain, fractal,
slice, whatever.

**Limitations**:
Currently only works with a proper `Aspect ratio` as otherwise collapsed image.
Be sure to add one. If not, add regular CSS `width: 100%` and `min-height` to
the blurred image if doable with your design.

### <a name="aspect-ratio"></a> 8. ASPECT RATIO
**UPDATE 18/07/2023**:

Aspect ratio **Fluid** will now calculate dimensions to match the fixed ones
(1:1, 2:3, etc.) automatically to avoid JS works specific for non-responsive
images. Useful if you are not sure. To add more aspect ratios:
+ Set yours via `blazies` object in the `hook_blazy_settings_alter`, like so:

  ``$blazies->set('css.ratio', ['7:8', '6:5'], TRUE);``

  The `TRUE` flag ensures to append, not nullify, the existing ones:
  ``['1:1', '3:2', '4:3', '8:5', '16:9']``

  See `blazy.api.php` for the available `hook_alter`. Always clear caches
  whenever adding or removing procedural functions.
+ Add the relevant CSS rules in your theme CSS using the convention as seen at
  `css/components/blazy.ratio.css`.
+ Create image styles that stick to these aspect ratios you defined:
  * [/admin/config/media/image-styles](/admin/config/media/image-styles)
  * [Aspect ratio template](#aspect-ratio-template)
+ Choose Aspect ratio **Fluid** so that your custom aspect ratios are
  automacally in use.


Relevant to make aspect ratio `Fluid` option prioritize these ratios for pure
CSS, and not using JavaScript. Only if a matching aspect ratio is found.
None of these options, other than defaults, will be visible at admin forms.
However they will be automatically picked up if matches are found.

#### What is the fuss about aspect ratio?
Aspect ratio fixes many issues with lazyloaded elements -- collapsed, distorted,
excessive height, layout reflow, etc., including making iframe fully responsive.
However it doesn't fix everything. Please bear with it.

**If you have display issues, the correct Aspect ratio is your first best bet.**

Depending on your particular issue, **enable or disable**, either way, is your
potential solution. One good sample when Aspect ratio makes no sense is
GridStack gapless grids, or Blazy `Native Grid`. Image sizes, hence aspect
ratio, cannot be applied to gapless grids. Aspect ratio is based on image sizes,
not grid sizes. The Native lazy load might not need aspect ratios, either,
except for iframes so to be responsive without installing jQuery fitVids, etc.
Using **Use CSS background** option is another solution for gapless grids, but
may not work for specific images which require 100% visibility due to being
cropped to fit the container.

**UPDATE 05/02/2020**:
Blazy RC7+ is 99% integrated with Responsive image, including
CSS background and the notorious aspect ratio **Fluid**. The remaining 1% is
some unknown glitches.

Aspect ratio was never supported for Responsive image till Blazy 2.rc7+, <s>not
fully though. One remaining issue is to make Aspect ratio `Fluid` work for:
CSS background + Picture element.</s>

Any **fixed** Aspect ratio (`4:3, 16:9`, etc) should immediately work as long as
you understand what it means.

Aspect ratio `Fluid` worked with
[**custom breakpoints**](https://www.drupal.org/node/3105243) (deprecated),
<s>not Responsive image, yet. If you want Aspect ratio for Responsive image,
choose anything but `Fluid`.</s>

Any **fixed** Aspect ratio (`4:3, 16:9`, etc), but `Fluid`, wants consistent
aspect ratio down to mobile, which means it won't work with art direction
technique, or Picture element. [Check out few aspect ratio samples](https://git.drupalcode.org/project/blazy/tree/docs/ASPECT_RATIO.md)

Temporary workaround is to add regular CSS `width: 100%` to the controlling
image if doable with your design. And a `min-height` per breakpoint via CSS
mediaqueries.


### 9. BLAZY WITHIN SCROLLING CONTAINER DOES NOT LOAD
`/admin/config/media/blazy`

**Note**: `IO` does not need it, old `bLazy` does.

If you put Blazy within a scrolling container, provide valid comma separated CSS
selectors, except `#drupal-modal, .is-b-scroll`, e.g.: `#my-scrolling-container,
.another-scrolling-container`.

A known scrolling container is `#drupal-modal` like seen at **Media library**.
A scrolling modal with an iframe like **Entity Browser** has no issue since the
scrolling container is the entire DOM. Must know `.blazy` parent container which
has CSS rules containing `overflow` with values anything but `hidden` such as
`auto` or `scroll`. Press `F12` at any browser to inspect elements.

Default to known `#drupal-modal, .is-b-scroll`.
The `.is-b-scroll` can be used when Blazy UI is unreachable without extra legs.

### 10. LINKED FIELD INTEGRATION
Under `Media switcher` option, only `Image to iFrame` makes sense. The rest like
`Image to lightboxes`, or `Image linked to content` will obviously be ignored
since these will output A tag just like what Linked Field does.
Alternatively leave `Media switcher` empty, if no videos are mixed with images.
With `Image to iFrame`, the good thing is video will be still playable, and the
image be linked as required. Best of Both Worlds for real.

### 11. VIEWS GOTCHAS
Blazy provides a simple Views field for File Entity, and Media. Also a Blazy
Grid views style plugin.

When using Blazy formatter within Views, check **Use field template** under
**Style settings**, if trouble with Blazy Formatter as a stand alone Views
output.

On the contrary, uncheck **Use field template**, when Blazy formatter
is embedded inside another module such as Slick so to pass the renderable
array to work with accordingly.

This is a Views common gotcha with field formatter, so be aware of it.
If confusing, just toggle **Use field template**, and see the output. You'll
know which works.

### 12. NATIVE GRID MASONRY
Q: _One dimensional vs. two-dimensional native grids?_

A: Under **Display style**, choose **Native Grid**. Under **Grid large** option,
   input any single grid column numbers, or input `WIDTHxHEIGHT` pairs.
   + The native grid masonry is a two-dimensional grid made one dimensional,
     which can be enabled by inputting any single number (2, 3, 4, etc.).
   + To have a two-dimensional grid, input any space delimited `WIDTHxHEIGHT`
     pairs, e.g.: `4x4 4x3 2x2 2x4 2x2 2x3 2x3 4x2 4x2`, or any combinations.

Q: _The native grid masonry doesn't have correct bottom gaps?_

A: It does. Your eyes are likely being tricked. **Solutions**:

   * Try adding background color to `.grid__content`. Notice even gaps. The
     problem is inner divities do not have 100%. Read more below.
   * If image and applicable, enable `CSS background` using Blazy formatter.
     If using Views, remove extra useless DIVs under `Style settings` by setting
     them all to `None` and or keep them with caution. And uncheck
     `Provide default field wrapper elements` under ` Show: Fields Settings`.
     So that Blazy `CSS background` fills in the gaps. Try `Aspect ratio: Fluid`
     to minimize reflow in case useful here.
   * If not, or still an issue, manually adjust the image and the inner DIVs of
     `.grid__content` heights to 100%.
   * For text contents, having light background color is enough.
   * Add enough min-height per breakpoint to the grid root container. See
     and override `blazy.nativegrid.css` to better suit your site needs.
   * If you don't want all these headaches, consider a more robust GridStack. It
     may take care these types of issues for you.

### 13. BLAZY IMAGES DO NOT LOAD
Images does not load within hidden tabs, or other hidden containers:

* `/admin/config/media/blazy`
* Enable `Load invisible` option.
* Specific for `Responsive image` within lightboxes under option
  `Lightbox image style`, be sure to not use `-empty image-` option under
  `Fallback image style`, edit them at
  [/admin/config/media/responsive-image-style](/admin/config/media/responsive-image-style)
  The reason, lightbox full size images are never lazy loaded by Blazy due to
  lightbox library requirements. Lightboxes are another kind of lazy loadings.

Only an issue with old bLazy, not IO, AFAIK. Other than that, be sure to read
back the topmost troubleshooting section.

### 14. OLIVERO SUB-THEMES
Carousels in Views like Splide and Slick are not compatible with Olivero. It
causes gargantuan width and height. However a workaround is provided. If however
you see gargantuan dimensions somewhere else, be sure to disable problematic
grid rules line `grid-template-rows: max-content;` at their ancestor selectors.
This rule reveals child width beyond boundaries, causing absurd gargantuan width
and height which is true to many carousels since they span their slides inline
beyond viewport, and only dislay some within viewport until that rule breaks it.
Adding `view--blazy` under **Advanced > Other > CSS** class should also work, if
any misses.

### 15. BROKEN MODULES
Alpha, Beta, DEV releases are for developers only. Beware of possible breakage.

However if it is broken, unless an update is provided, running `drush cr` during
DEV releases should fix most issues as we add new services, or change things.
If you don't drush, before any module update, always open:

[Performance](/admin/config/development/performance)

And so you are ready to hit **Clear all caches** if any issue.
Only at worst case, specific for D7, know how to run
https://www.drupal.org/project/registry_rebuild safely.

Check out [Update SOP](#updating) for details.
