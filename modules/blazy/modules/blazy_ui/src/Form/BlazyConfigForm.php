<?php

namespace Drupal\blazy_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\blazy\Blazy;
use Drupal\blazy\Form\BlazyConfigFormBase;

/**
 * Defines blazy admin config form.
 */
class BlazyConfigForm extends BlazyConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected $validatedOptions = [
    'placeholder',
    'unstyled_extensions',
    ['blazy', 'container'],
    ['blazy', 'offset'],
    ['blazy', 'saveViewportOffsetDelay'],
    ['blazy', 'validateDelay'],
    ['io', 'rootMargin'],
    ['io', 'threshold'],
    'extras',
  ];

  /**
   * {@inheritdoc}
   */
  protected $validatedPaths = [
    'placeholder',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blazy_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['blazy.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('blazy.settings');
    $doms = ['DOMPurify', 'dompurify'];
    $dom_exists = $exists = $this->manager->getLibrariesPath($doms);
    $svg_exists = $exists = Blazy::svgSanitizerExists();
    $svg_sanitizer = 'https://github.com/darylldoyle/svg-sanitizer';
    $class = $exists ? 'info' : 'warning';
    $hints = [];
    $help = '/admin/help/blazy_ui';

    if ($this->manager->moduleExists('help')) {
      $help = Url::fromUri('internal:/admin/help/blazy_ui')->toString();
    }

    // Adapted from Colorbox module, thanks.
    $dom_text = $dom_exists ?
      '[v] ' . $this->t('The DOMPurify library is installed to sanitize lightbox captions. Be sure to clear cache for library discoveries. [<a href=":ui">Blazy UI help</a>]', [
        ':ui' => $help . '#dompurify',
      ])
      :
      '[x] ' . $this->t('<strong>Warning!</strong> The <a href=":url">DOMPurify</a> library is not installed. It is necessary for HTML in lightbox captions. Without it, they are only sanitized server-side, or builtin. [<a href=":ui">Blazy UI help</a>].',
        [
          ':url' => 'https://github.com/cure53/DOMPurify/archive/main.zip',
          ':ui' => $help . '#dompurify',
        ]);

    $hints[] = [
      '#theme' => 'container',
      '#children'   => ['#markup' => $dom_text],
      // '#attributes' => [
      // 'class' => ['messages', 'messages--' .
      // ($dom_exists ? 'info' : 'warning')],
      // ],
    ];

    $svg_text = $svg_exists ?
      '[v] ' . $this->t('The SVG Sanitizer library is installed to sanitize inline SVG. [<a href=":ui">Blazy UI help</a>]', [
        ':ui' => $help . '#svg',
      ])
      :
      '[x] ' . $this->t('<strong>Warning!</strong> The <a href=":url">SVG Sanitizer</a> library is not installed. This library is necessary if you want to use SVG inline. Without it, the world would be ended. [<a href=":ui">Blazy UI help</a>].',
        [
          ':url' => $svg_sanitizer,
          ':ui' => $help . '#svg',
        ]);

    $hints[] = [
      '#theme' => 'container',
      '#children'   => ['#markup' => $svg_text],
      // '#attributes' => [
      // 'class' => ['messages', 'messages--' .
      // ($svg_exists ? 'info' : 'warning')],
      // ],
    ];

    $form['library_hints'] = [
      // '#theme' => 'item_list',
      // '#items' => $hints,
      '#type' => 'container',
      'items' => $hints,
      '#attributes' => [
        'class' => [
          'messages-list__item',
          'messages',
          'messages--' . $class,
        ],
      ],
      '#wrapper_attributes' => ['class' => ['messages-list']],
    ];

    $form['admin_css'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Admin CSS'),
      '#default_value' => $config->get('admin_css'),
      '#description'   => $this->t('Uncheck to disable blazy related admin compact form styling, only if not compatible with your admin theme.'),
    ];

    $form['use_oembed'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use oEmbed'),
      '#description'   => $this->t('Check to use oEmbed when available. Only relevant for VEF compatibility which already managed their embed codes. Irrelevant for core which already uses oEmbed. If checked, VEF embed will be converted into oEmbed if the provider is available, otherwise left as is. Be informed! Using oEmbed may require having App ID and secret credentials for some providers even for simple oEmbed read such as Instagram or Facebook, not Youtube, etc.'),
      '#default_value' => $config->get('use_oembed'),
    ];

    $form['lazy_html'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Lazy load HTML (Experimental)'),
      '#description'   => $this->t('When theme_blazy() does not understand a media output, it will print it as HTML as is. This HTML is normally a paragraph size. Check this to lazy load such HTML content (normally heavy third party contents, like oembed Instagram, Pinterest, etc.), no AJAX, otherwise printed as is. A new feature since 2.17, not battle-tested. Potential issues are with the attached libraries, and other various Blazy features. Please disable and report if any issues.'),
      '#default_value' => $config->get('lazy_html'),
    ];

    $form['use_encodedbox'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use encoding for HTML (Experimental)'),
      '#description'   => $this->t('If checked, and the lightbox supports this feature, the lightbox HTML (normally local audio/video, Picture/Responsive image, oembed Instagram, etc.) will be encoded. Also applies to CSS background. A minor byte saving. Please disable if any issues.'),
      '#default_value' => $config->get('use_encodedbox'),
    ];

    $nojs = $config->get('nojs');
    $form['nojs'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('No JavaScript'),
      '#empty_option'  => '- None -',
      '#options' => [
        'lazy' => $this->t('Lazyload'),
        'polyfill' => $this->t('Basic polyfills (ie9-ie11)'),
        'classlist' => $this->t('classList polyfill (ie9-ie11)'),
        'promise' => $this->t('Promise polyfill (ie11)'),
        'raf' => $this->t('requestAnimationFrame polyfill (ie9)'),
        'webp' => $this->t('webp fallback (ie9-ie11, old Safari)'),
      ],
      '#default_value' => !empty($nojs) ? array_values((array) $nojs) : [],
      '#description'   => $this->t("Enable to not load them if you don't support IEs and other oldies, or have polyfills at your theme globally. File sizes approximately in minified gzip. The plus (+) sign refers to dependencies like <code>dblazy.js (~4KB)</code>, etc. A few can be removed via this form. For illustration, Colorbox which is called very lightweight is 4.8KB + jQuery (31KB) = 35.8KB. Blazy never loads all its JavaScript at once, instead conditionally and carefully loads ones as required. Mostly based on your options including these ones. <ul><li><b>Lazyload</b>: remove libraries and loader/ initializer scripts (<code>blazy.js (original: 2.2KB, fork: 1.6KB+), blazy.load.js (1KB+), bio.js (1.7KB+)</code>.) for non-js Native lazy. <br><b>Note!</b> While the above is always valid, a <code>blazy/compat (<1KB + bio.js)</code> and or <code>blazy/dblazy</code> in the least is conditionally loaded as required if any js-dependent options are enabled: <ul><li>Image effect animation or Blur.</li><li>Dynamic multi-breakpoint aka Fluid aspect ratio (excluding fixed ones).</li><li>Dynamic multi-breakpoint (Responsive|Picture based), or static CSS background.</li><li>Local video.</li><li>Loading priority: defer.</li><li>Sub-module requirements. Slick, Splide, Ultimenu, Jumper, etc. might require <code>blazy/dblazy</code>, not a lazyload script, just common jQuery replacement methods for vanilla ones.</li></ul></li><li><b>Polyfills</b>: Only loaded (total 3.6KB) if the above conditions are met, and left unchecked. <b>Basic polyfills</b>: <code>Object.assign, closest, forEach, matches, startsWith, CustomEvent</code>. <b>webp falback</b>: FWIW, IE9, not tested against old Safari, works fine with core/picturefill w/o this fallback. For other polyfills, due to questionabe licenses, include them into your theme as needed such as <a href=':io'>IntersectionObserver</a>, etc. <br><b>Warning!</b> The polyfills may be deprecated and removed when <a href=':cash'>Cash DOM</a> module is available, and will be replaced by Cash accordingly.</li></ul>As of 2022/1, Native only supports IMG and IFRAME, the exceptions above cover BLUR, DIV, VIDEO, ratio FLUID, LOADING: defer, etc. Other JavaScript (Media Player, Lightbox, Non-css Masonry (Flexbox or Nativegrid), etc.) can already be disabled via Formatters since 1.x. jQuery (31KB) is only loaded for Colorbox (4.8KB) + blazy.colorbox.js (1.2KB+) and admin UIs. Details in blazy/js directory. <a href=':url'>Read more</a>.", [
        ':cash' => 'https://drupal.org/project/cash',
        ':url' => 'https://drupal.org/node/3257512',
        ':io' => 'https://github.com/w3c/IntersectionObserver',
      ]),
    ];

    $form['noscript'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Add noscript'),
      '#default_value' => $config->get('noscript'),
      '#description'   => $this->t('Enable noscript if you want to support <a href=":url">non-javascript users</a>.', [':url' => 'https://stackoverflow.com/questions/9478737']),
    ];

    $form['one_pixel'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Responsive image 1px placeholder'),
      '#default_value' => $config->get('one_pixel'),
      '#description'   => $this->t('By default a 1px Data URI image is the placeholder for lazyloaded (Responsive) image. Useful to perform a lot better. Uncheck to disable, and use Drupal-managed smallest/fallback image style instead. Be sure to add proper dimensions or at least min-height/min-width via CSS accordingly to avoid layout reflow, or choose an Aspect ratio via Blazy formatters. <br>Since <b>2.10</b>, disabling this will no longer result in downloading fallback image (double downloads). Thus, allows you to have non <code>empty image</code> for fallback at Responsive image style UI without extra HTTP requests, while using <code>empty image</code> (enforced now) for the SRC. Basically marrying those options. not negating each other anymore.'),
    ];

    $form['visible_class'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Add is-b-visible class'),
      '#default_value' => $config->get('visible_class'),
      '#description'   => $this->t('Add <code>is-b-visible</code> CSS class when entering the viewport. Only enable if any real use for animating anything, otherwise disable it. If enabled, IO is not destroyed so to keep watching the class changes.'),
    ];

    $form['wrapper_class'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Remove field/ view wrapper classes'),
      '#default_value' => $config->get('wrapper_class'),
      '#description'   => $this->t("Remove useful classes for DOM diets when you can get rid of Field, Block, Views, etc. wrappers so you have context for styling. Other required classes: lightbox, grid, etc. are intact if so-configured."),
    ];

    $form['placeholder'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Placeholder'),
      '#default_value' => $config->get('placeholder'),
      '#description'   => $this->t("Only useful if continuously using Views rewrite results, see <a href=':url2'>#2908861</a>. Inputting this means having trouble with `data:image` placeholder being stripped out by Views sanitization procedures causing 404. Will override global 1px `data:image` placeholder, including core Responsive image 1px `data:image` with this so to avoid 404, and independent from the previous `Responsive image 1px placeholder` option. Must be URL, e.g.: /blank.gif or /blank.svg. Be warned: unlike .svg, browsers have display issues with 1px .gif, see <a href=':url1'>#2795415</a>. Alternatively use <code>hook_blazy_settings_alter()</code> for more fine-grained control. Leave it empty to use default inline SVG or Data URI to avoid extra HTTP requests. If you have 100 images on a page, you will save hammering your server with 100 extra HTTP requests by leaving it empty. The <b>blank.svg</b> content sample if not using blank.gif: <br><code>&lt;svg xmlns='https://www.w3.org/2000/svg' viewBox='0 0 100 100'/&gt;</code><br>Save it at Drupal web root as <code>blank.svg</code>, and reference it as <code>/blank.svg</code> in the Placeholder field.", [
        ':url1' => 'https://drupal.org/node/2795415',
        ':url2' => 'https://drupal.org/node/2908861',
      ]),
    ];

    $form['unstyled_extensions'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Extensions without image styles'),
      '#default_value' => $config->get('unstyled_extensions'),
      '#description'   => $this->t('Extensions that should not use (Responsive) image style, space delimited without dot, e.g.: <code>gif apng</code> <br>Normally animated images. No way to distinguish animated from static gif, it is all or nothing. This means no thumbnail, no blur, nor features which makes use image style. Default to svg.'),
    ];

    $fx = $this->manager->getImageEffects();
    $form['fx'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Image effect'),
      '#empty_option'  => '- None -',
      '#options'       => array_combine($fx, $fx),
      '#default_value' => $config->get('fx'),
      '#description'   => $this->t("Choose the image effect. Will use Thumbnail style option at Blazy formatters for the placeholder with fallback to core Thumbnail style. For best results: use similar aspect ratio for both Thumbnail and Image styles; adjust Offset and or threshold; the smaller the better. Use <code>hook_blazy_image_effects_alter()</code> to add more effects -- curtain, fractal, slice, whatever. <b>Limitations</b>: Best with a proper Aspect ratio option as otherwise collapsed image. Be sure to add one. If not, add regular CSS <code>min-height</code> for each mediaquery. The Placeholder option is still respected. It is permanently cached, be sure to clear cache if your or a module's provided additional altered data do not appear here. <ul><li>To avoid complications, it will be disabled when being unlazy: no JS, iframe only, sandboxed, etc. since it requires lazy load as a trigger.</li></ul>"),
    ];

    $form['blur_client'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use client-side blur'),
      '#default_value' => $config->get('blur_client'),
      '#description'   => $this->t("Uncheck to preserve old behaviors data URI printed on the page server-side. Check to enable Blur client-side. <br><b>Pros:</b> Client-side doesn't add ugly data URI to the page till required, and automatically cleared when done, meaning lighter page weight at initial and end, and at the next pages if any stored data found (meaning cacheable unlike server-side), but not during runtime animation. It leverages lazy load mechanism. <br><b>Cons:</b> Client-side does a HTTP request initially. Can use localStorage option below to cache them."),
    ];

    $form['blur_storage'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Store blur in localStorage'),
      '#default_value' => $config->get('blur_storage'),
      '#description'   => $this->t('Check to cache Blur data URI in localStorage to save HTTP requests on the next requests. Uncheck if using localStorage for more custom important stuffs. Core localStorage providers vary 0.05KB - 150KB, to a tentative amount of 739.07 KB. This option will obviously hit the limit anytime (says 40KB x 100 images = 4000KB), can be larger given non-optimized or large Blur image style. However, configurable at <b>Thumbnail style</b> option, or Responsive image style fallback. That is why the smaller, file size and dimension, the more efficient. Will auto-clear, and recycle, when the quota (2-10MB) is exceeded.'),
    ];

    $form['blur_minwidth'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Blur min-width'),
      '#default_value' => $config->get('blur_minwidth') ?: 0,
      '#description'   => $this->t("Only enable Blur if the image style (not window) width is bigger than this value. Useful to disable it for mobile to avoid potential unverified OOM (Out of Memory) issues, or non-fancy listing thumbnails, says 767."),
      '#maxlength'     => 4,
      '#field_suffix'  => 'px',
    ];

    foreach (['client', 'storage', 'minwidth'] as $key) {
      $form['blur_' . $key]['#states'] = [
        'visible' => [
          'select[name="fx"]' => ['value' => 'blur'],
        ],
      ];
      if ($key == 'storage') {
        $form['blur_' . $key]['#states']['visible'][] = [
          'input[name="blur_client"]' => [
            'checked' => TRUE,
          ],
        ];
      }
    }

    $form['max_region_count'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Max region count'),
      '#default_value' => $config->get('max_region_count'),
      '#description'   => $this->t('Specific for Blazy layout, define the maximum amount of regions. Default to 20 if left 0 or below 9. Regions beyond this amount will be hidden. If changed, be sure to clear caches for regions to be re-registered.'),
      '#access'        => $this->manager->moduleExists('blazy_layout'),
    ];

    $form['blazy'] = [
      '#type'        => 'details',
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#title'       => $this->t('Blazy settings'),
      '#description' => $this->t('The following settings are related to old bLazy library.'),
    ];

    $form['blazy']['loadInvisible'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Load invisible'),
      '#default_value' => $config->get('blazy.loadInvisible'),
      '#description'   => $this->t('Check if you want to load invisible (hidden) elements. If any issues with tabs, accordions, or anything hidden, enable this.'),
    ];

    $form['blazy']['offset'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Offset'),
      '#default_value' => $config->get('blazy.offset'),
      '#description'   => $this->t("The offset controls how early you want the elements to be loaded before they're visible. Default is <strong>100</strong>, so 100px before an element is visible it'll start loading."),
      '#field_suffix'  => 'px',
      '#maxlength'     => 5,
      '#size'          => 10,
    ];

    $form['blazy']['saveViewportOffsetDelay'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Save viewport offset delay'),
      '#default_value' => $config->get('blazy.saveViewportOffsetDelay'),
      '#description'   => $this->t('Delay for how often it should call the saveViewportOffset function on resize. Default is <strong>50</strong>ms.'),
      '#field_suffix'  => 'ms',
      '#maxlength'     => 5,
      '#size'          => 10,
    ];

    $form['blazy']['validateDelay'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Set validate delay'),
      '#default_value' => $config->get('blazy.validateDelay'),
      '#description'   => $this->t('Delay for how often it should call the validate function on scroll/resize. Default is <strong>25</strong>ms.'),
      '#field_suffix'  => 'ms',
      '#maxlength'     => 5,
      '#size'          => 10,
    ];

    $form['blazy']['container'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Scrolling container'),
      '#default_value' => $config->get('blazy.container'),
      '#description'   => $this->t('If you put Blazy within a scrolling container, provide valid comma separated CSS selectors, except <code>#drupal-modal, .is-b-scroll</code>, e.g.: <code>#my-scrolling-container, .another-scrolling-container</code>. Known scrolling containers are <code>#drupal-modal</code> like seen at Media library, parallax containers with fixed height replacing default browser scrollbar. A scrolling modal with an iframe like Entity Browser has no issue since the scrolling container is the entire DOM. Must know <code>.blazy</code> parent container, or itself, which has CSS rules containing <code>overflow</code> with values anything but <code>hidden</code> such as <code>auto or scroll</code>. Press <code>F12</code> at any browser to inspect elements. IO does not need it, old bLazy does. Default to known <code>#drupal-modal, .is-b-scroll</code>. The <code>.is-b-scroll</code> is for modules which cannot reach this UI without extra legs. Symptons: eternal blue loader while should be loaded.'),
    ];

    $form['io'] = [
      '#type'        => 'details',
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#title'       => $this->t('Intersection Observer API (IO) settings'),
      '#description' => $this->t('Will fallback gracefully with Native support for old browsers using old bLazy fork, unless unloaded. Works absurdly fine at IE9. None essential features like Blur, etc. which require additional polyfills (dataset, etc.) not included above may not, and fail silently instead. <br>The following settings are related to <a href=":url">IntersectionObserver API</a>.', [':url' => 'https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API']),
    ];

    $form['io']['unblazy'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Unload bLazy'),
      '#default_value' => $config->get('io.unblazy'),
      '#description'   => $this->t("Check if you are happy with IO, and don't support IEs. This will not load the forked bLazy library. Forked bLazy is just ~1KB gzip. Clear caches!"),
    ];

    $form['io']['rootMargin'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('rootMargin'),
      '#default_value' => $config->get('io.rootMargin') ?: '0px',
      '#description'   => $this->t("Margin around the root. Can have values similar to the CSS margin property, e.g. <code>10px 20px 30px 40px</code> (top, right, bottom, left). The values can be percentages. This set of values serves to grow or shrink each side of the root element's bounding box before computing intersections. Defaults to all zeros."),
      '#maxlength'     => 120,
      '#size'          => 20,
    ];

    $form['io']['threshold'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('threshold'),
      '#default_value' => $config->get('io.threshold') ?: '0',
      '#description'   => $this->t("Either a single number or an array of numbers which indicate at what percentage of the target's visibility the observer's callback should be executed. If you only want to detect when visibility passes the 50% mark, you can use a value of 0.5. If you want the callback to run every time visibility passes another 25%, you would specify the array [<code>0, 0.25, 0.5, 0.75, 1</code>] (without brackets). The default is 0 (meaning as soon as even one pixel is visible, the callback will be run). A value of 1.0 means that the threshold isn't considered passed until every pixel is visible."),
      '#maxlength'     => 120,
      '#size'          => 20,
    ];

    $form['io']['disconnect'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Disconnect'),
      '#default_value' => $config->get('io.disconnect'),
      '#description'   => $this->t('Check if you want to disconnect IO once all images loaded. If you keep seeing eternal blue loader while an image should be already loaded, this means it is not working yet in all cases. Just uncheck this.'),
    ];

    // Allows sub-modules to provide its own settings.
    $form['extras'] = [
      '#type'   => 'details',
      '#open'   => FALSE,
      '#tree'   => TRUE,
      '#title'  => $this->t('Extra settings'),
      '#access' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('blazy.settings');
    $config
      ->set('admin_css', $form_state->getValue('admin_css'))
      ->set('lazy_html', $form_state->getValue('lazy_html'))
      ->set('nojs', $form_state->getValue('nojs'))
      ->set('fx', $form_state->getValue('fx'))
      ->set('blur_client', $form_state->getValue('blur_client'))
      ->set('blur_storage', $form_state->getValue('blur_storage'))
      ->set('blur_minwidth', $form_state->getValue('blur_minwidth'))
      ->set('noscript', $form_state->getValue('noscript'))
      ->set('one_pixel', $form_state->getValue('one_pixel'))
      ->set('visible_class', $form_state->getValue('visible_class'))
      ->set('wrapper_class', $form_state->getValue('wrapper_class'))
      ->set('placeholder', $form_state->getValue('placeholder'))
      ->set('unstyled_extensions', $form_state->getValue('unstyled_extensions'))
      ->set('use_encodedbox', $form_state->getValue('use_encodedbox'))
      ->set('use_oembed', $form_state->getValue('use_oembed'))
      ->set('max_region_count', $form_state->getValue('max_region_count'))
      ->set('blazy.loadInvisible', $form_state->getValue([
        'blazy',
        'loadInvisible',
      ]))
      ->set('blazy.offset', $form_state->getValue(['blazy', 'offset']))
      ->set('blazy.saveViewportOffsetDelay', $form_state->getValue([
        'blazy',
        'saveViewportOffsetDelay',
      ]))
      ->set('blazy.validateDelay', $form_state->getValue([
        'blazy',
        'validateDelay',
      ]))
      ->set('blazy.container', $form_state->getValue(['blazy', 'container']))
      ->set('io.unblazy', $form_state->getValue(['io', 'unblazy']))
      ->set('io.rootMargin', $form_state->getValue(['io', 'rootMargin']))
      ->set('io.threshold', $form_state->getValue(['io', 'threshold']))
      ->set('io.disconnect', $form_state->getValue(['io', 'disconnect']));

    if ($form_state->hasValue('extras')) {
      foreach ($form_state->getValue('extras') as $key => $value) {
        $config->set('extras.' . $key, $value);
      }
    }

    $config->save();

    // Invalidate the library discovery cache to update the responsive image.
    // @todo use LibraryDiscoveryCollector::clear() for D12.
    // $this->libraryDiscovery->clearCachedDefinitions();
    $this->configFactory->clearStaticCache();

    $this->messenger()->addMessage($this->t('Be sure to <a href=":clear_cache">clear the cache</a> if trouble to see the updated settings.', [
      ':clear_cache' => Url::fromRoute('system.performance_settings')->toString(),
    ]));

    parent::submitForm($form, $form_state);
  }

}
