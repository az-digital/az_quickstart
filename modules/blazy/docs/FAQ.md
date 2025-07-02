
***
## <a name="faq"></a>FAQ

### CURRENT DEVELOPMENT STATUS
A full release should be reasonable after proper feedback from the community,
some code cleanup, and optimization where needed. Patches are very much welcome.


### PROGRAMATICALLY
[blazy.api.php](https://git.drupalcode.org/project/blazy/blob/3.0.x/blazy.api.php)


### BLAZY VS. B-LAZY
`blazy` is the module namespace. `b-lazy` is the default CSS class to lazy load.

* The `blazy` class is applied to the **top level container**, e,g.. `.field`,
  `.view`, `.item-list`, etc., those which normally contain item collection.
  In this container, you can feed any `bLazy` script options into `[data-blazy]`
  attribute to override existing behaviors per particular page, only if needed.
* The `b-lazy` class is applied to the **target item** to lazy load, normally
  the children of `.blazy`, but not always. This can be IMG, VIDEO, DIV, etc.

### BLAZY:DONE VS. BIO:DONE EVENTS
The `blazy:done` event is for individual lazy-loaded elements, while `bio:done`
is for the entire collections.

Since 2.17, you can namespace colonized events like so: `blazy:done.MYMODULE`
which was problematic with dot `blazy.done`. That is why `blazy.done` is
deprecated for `blazy:done`. The dotted event names like `blazy.done` will
continue working till 3.x. Changing them to colonized `blazy:done` is strongly
recommended to pass 3.x. Newly added events will only use colons.

### WHAT `BLAZY` CSS CLASS IS FOR?
Aside from the fact that a module must reserve its namespace including for CSS
classes, the `blazy` is actually used to limit the scope to scan document.
Rather than scanning the entire DOM, you limit your work to a particular
`.blazy` container, and these can be many, no problem. This also allows each
`.blazy` container to have unique features, such as ones with multi-breakpoint
images, others with regular images; ones with a lightbox, others with
image to iframe; ones with CSS background, others with regular images; etc.
right on the same page. This is only possible and efficient within the `.blazy`
scope.

### WHY NOT `BLAZY__LAZY` FOR `B-LAZY`?
`b-lazy` is the default CSS class reserved by JS script. Rather than recreating
a new one, respecting the defaults is better. Following BEM standard is not
crucial for most JS generated CSS classes. Uniqueness matters.

### NATIVE LAZY LOADING
Blazy library last release was v1.8.2 (2016/10/25). 3 years later,
Native lazy loading is supported by Chrome 76+ as of 01/2019. Blazy or IO will
be used as fallback for other browsers instead. Currently the offset/ threshold
before loading is hard-coded to [8000px at Chrome](https://cs.chromium.org/chromium/src/third_party/blink/renderer/core/frame/settings.json5?l=971-1003&rcl=e8f3cf0bbe085fee0d1b468e84395aad3ebb2cad),
so it might only be good for super tall pages for now, be aware.
[Read more](https://web.dev/native-lazy-loading/)

This also may trick us to think lazy load not work, check out browsers' Network
tab to verify that it still does work.

**UPDATE 2020-04-24**: Added a delay to only lazy load once the first found is
  loaded, see [#3120696](https://drupal.org/node/3120696)

**UPDATE 2022-01-22**:
With bIO as the main lazyloader, the game changed, quoted from:
https://developer.mozilla.org/en-US/docs/Learn/HTML/Howto/Author_fast-loading_HTML_pages
> Note that lazily-loaded images may not be available when the load event is
fired. You can determine if a given image is loaded by checking to see if
the value of its Boolean complete property is true.  

Old bLazy relies on `onload`, meaning too early loaded decision for Native,
the reason for our previous deferred invocation, not `decoding` like what bIO
did which is more precise as suggested by the quote.

Assumed, untested, fine with combo IO + `decoding` checks before blur spits.

Shortly we are in the right direction to cope with Native vs. `data-[SRC]`.
See `bio.js ::natively` for more contextual info.  
[x] Todo recheck IF wrong so to put back https://drupal.org/node/3120696.

**UPDATE 2022-03-03**: The above is almost not wrong as proven by no `b-loaded`
class and no `blur` is triggered earlier, but 8000px threshold rules. Meaning
the image is immediately requested 8000px before entering viewport.
Added back a delay to only lazy load once the first found is loaded at field
formatter level via `Loading priority: defer`, see
[#3120696](https://drupal.org/node/3120696)

### ANIMATE.CSS INTEGRATION
Blazy container (`.media`) can be animated using
[animate.css](https://github.com/daneden/animate.css). The container is chosen
to be the animated element so to support various use cases:
CSS background, picture, image, or rich media contents.

See [GridStack](https://drupal.org/project/gridstack) 2.6+ for the `animate.css`
samples at Layout Builder pages.

To replace **Blur** effect with `animate.css` thingies, implements two things:  
1. **Globally**: `hook_blazy_image_effects_alter` and add `animate.css` classes
   to make them available for select options at Blazy UI.  
2. **Fine grained**: `hook_blazy_settings_alter`, and replace a setting named
   `fx` with one of `animate.css` CSS classes, adjust conditions based settings.

#### Requirements:

* The `animate.css` library included in your theme, or via `animate_css` module.
* Data attributes: `data-animation`, with optional: `data-animation-duration`,
  `data-animation-delay` and `data-animation-iteration-count`, as seen below.

```
function MYTHEME_preprocess_blazy(&$variables) {
  $settings = &$variables['settings'];
  $attributes = &$variables['attributes'];
  $blazies = $settings['blazies'];

  // Be sure to limit the scope, only animate for particular conditions.
  if ($blazies->get('entity.id') == 123
    && $blazies->get('field.name') == 'field_media_animated')  {
    $fx = $blazies->get('fx');

    // This was taken care of by feeding $fx, or hard-coded here.
    // Since 2.17, `data-animation` is deprecated for `data-b-animation`.
    $prefix = $blazies->use('data_b') ? 'data-b-' : 'data-';
    $attributes[$prefix . 'animation'] = $fx ?: 'wobble';

    // The following can be defined manually.
    $attributes[$prefix . 'animation-duration'] = '3s';
    $attributes[$prefix . 'animation-delay'] = '.3s';
    // Iteration can be any number, or infinite.
    $attributes[$prefix . 'animation-iteration-count'] = 'infinite';
  }
}
```
