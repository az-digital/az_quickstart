<?php

/**
 * @file
 * Hooks and API provided by the Slick module.
 */

/**
 * @defgroup slick_api Slick API
 * @{
 * Information about the Slick usages as per blazy:2.17.
 *
 * Be sure to enable `Use theme_blazy()` option at /admin/config/media/blazy.
 * It will be enforced at blazy:3.x.
 *
 * Modules may implement any of the available hooks to interact with Slick.
 *
 * Slick may be configured using the web interface via sub-modules.
 * However below is a few sample coded ones. The simple API is to achieve
 * consistent markups working for various skins, and layouts for both coded
 * and sub-modules implementations.
 *
 * The expected parameters are:
 *   - items: A required array of slick contents: text, image or media.
 *   - #options: An optional array of key:value pairs of custom JS options.
 *   - #optionset: An optional optionset object to avoid multiple invocations.
 *   - #settings: An array of key:value pairs of HTML/layout related settings
 *     which may contain optionset ID if no optionset above is provided.
 *
 * @see \Drupal\slick\Plugin\Field\FieldFormatter\SlickImageFormatter
 * @see \Drupal\slick_views\Plugin\views\style\SlickViews
 * @see https://www.drupal.org/node/3384419
 * @see blazy/blazy.api.php
 *
 * @section sec_quick Quick sample #1
 *
 * Returns the renderable array of a slick instance.
 * @code
 * function my_module_render_slick() {
 *   // Invoke the manager service, or use a DI service container accordingly.
 *   $manager = \Drupal::service('slick.manager');
 *
 *   // Access the formatter service for image-related methods:
 *   $formatter = \Drupal::service('slick.formatter');
 *
 *   $build = [];
 *
 *   // Define any global settings relevant for theme_blazy() and theme_slick().
 *   // See \Drupal\blazy\BlazyDefault::imageSettings().
 *   // See \Drupal\slick\SlickDefault::imageSettings().
 *   $settings = [
 *     'background' => TRUE,
 *     // Assumes working with local image URI, since external URL won't apply.
 *     'image_style' => 'large',
 *     'ratio' => 'fluid',
 *   ];
 *
 *   // Captions key contains: alt, description, data, link, overlay, title.
 *   // The image.uri is the only required by theme_blazy(). This $info is
 *   // optional/ removable if using the second approach below.
 *   // Option setter #1, add image.alt, image.title, etc. as needed:
 *   $info = ['image.uri' => 'https://drupal.org/files/One.gif'];
 *
 *   // Option setter #2:
 *   // $formatter::toSettings() initialize `blazies` object with added $info,
 *   // and reset per item, be sure to repeat the call per item.
 *   // You can move it up here to access `blazies` object for more works.
 *   // Notice $info was left out, and use the blazies setter instead:
 *   // $settings = $formatter->toSettings($settings);
 *   // $blazies = $settings['blazies'];
 *   // Now do anything with $blazies setter:
 *   // $blazies->set('image.uri', 'BLAH')
 *   //   ->set('image.alt', 'BLAH')
 *   //   ->set('image.title', 'BLAH');
 *
 *   // Each item contains: #delta, #settings, and optional captions.
 *   // This is the simplest way to build a slide via theme_blazy().
 *   // If you need to modify slide attributes or classes, just split them into
 *   // regular slide, and #settings, excluding captions which are already
 *   // included in theme_blazy(), e.g:
 *   // $items[] = [
 *   // 'slide' => $formatter->getBlazy($content),
 *   // '#attributes' => ['class' => ['slide--custom-class']],
 *    // ];
 *   $items[] = $formatter->getBlazy([
 *     '#delta' => 0,
 *     '#settings' => $formatter->toSettings($settings, $info),
 *     'captions' =>  ['title' => ['#markup' => t('Description #1')]],
 *   ];
 *
 *   $info = ['image.uri' => 'https://drupal.org/files/Two.gif'];
 *
 *   $items[] = $formatter->getBlazy([
 *     '#delta' => 1,
 *     '#settings' => $formatter->toSettings($settings, $info),
 *     'captions' =>  ['title' => ['#markup' => t('Description #2')]],
 *   ];
 *
 *   $info = ['image.uri' => 'https://drupal.org/files/Three.gif'];
 *
 *   $items[] = $formatter->getBlazy([
 *     '#delta' => 2,
 *     '#settings' => $formatter->toSettings($settings, $info),
 *     'captions' =>  ['title' => ['#markup' => t('Description #3')]],
 *   ];
 *
 *   // Pass the $items to the array.
 *   $build['items'] = $items;
 *
 *   // Put the global settings in.
 *   $build['#settings'] = $settings;
 *
 *   // If no optionset name is provided via $build['#settings'], slick will
 *   // fallback to 'default'.
 *   // Optionally override 'default' optionset with custom JS options.
 *   $build['#options'] = [
 *     'autoplay' => TRUE,
 *     'dots'     => TRUE,
 *     'arrows'   => FALSE,
 *   ];
 *
 *   // Build the slick.
 *   $element = $manager->build($build);
 *
 *   // Prepare $variables to pass into a .twig.html file.
 *   $variables['slick'] = $element;
 *
 *   // Render the slick at a .twig.html file.
 *   // {{ slick }}
 *   // Or simply return the $element if a renderable array is expected.
 *   return $element;
 * }
 * @endcode
 * @see \Drupal\slick\SlickManager::build()
 * @see template_preprocess_slick_wrapper()
 * @see template_preprocess_slick()
 *
 * @section sec_detail Detailed sample #2
 *
 * This can go to some hook_preprocess() of a target html.twig, or any relevant
 * PHP file.
 *
 * The goal is to create any text, image, or media slide contents.
 * First, create an unformatted Views block, says 'Ticker' containing ~ 10
 * titles, or any data for the contents -- using EFQ, or static array will do.
 *
 * Returns the renderable array of a slick instance.
 * @code
 * function my_module_render_slick_detail() {
 *   // Invoke the manager service, or use a DI service container accordingly.
 *   $manager = \Drupal::service('slick.manager');
 *
 *   // Access the formatter service for image related methods:
 *   $formatter = \Drupal::service('slick.formatter');
 *
 *   $build = [];
 *
 *   // 1.
 *   // Optional $settings, can be removed.
 *   // Provides HTML settings with optionset name and ID, none of JS related.
 *   // To add JS key:value pairs, use #options below instead.
 *   // @see \Drupal\slick\SlickDefault for most supported settings.
 *   $build['#settings'] = [
 *     // Optional optionset name, otherwise fallback to default.
 *     // 'optionset' => 'blog',
 *     // Optional skin name fetched from hook_slick_skins_info(), else none.
 *     // 'skin' => 'fullwidth',
 *
 *     // Define cache max-age, default to -1 (Cache::PERMANENT) to permanently
 *     // cache the results. Hence a 1 hour is passed. Be sure it is an integer!
 *     'cache' => 3600,
 *   ];
 *
 *   // 2.
 *   // Obligatory #items, as otherwise empty slick.
 *   // Prepare #items, $rows can be text only, image/audio/video, or a
 *   // combination of both. Or simply a rendered entity.
 *   // To add caption/overlay, use 'captions' key with the supported sub-keys:
 *   // alt, description, data, link, overlay, title for complex content.
 *   // Sanitize each sub-key content accordingly.
 *   // @see template_preprocess_slick_slide() for more info.
 *   $items = [];
 *   foreach ($rows as $delta => $row) {
 *     // Since blazy:2.17, slide and captions are merged into ::getBlazy() via
 *     // Blazy UI option named `Use theme_blazy()`, enforced at 3.x. Be sure
 *     // to enable the Blazy UI option to work like the following:
 *     // Each item has keys: content, captions, #delta, #settings.
 *     $sets = $build['#settings'];
 *
 *     // Optional slide settings to manipulate layout, can be removed.
 *     // Individual slide supports some useful settings like layout, classes,
 *     // etc.
 *     // Meaning each slide can have different layout, or classes.
 *     // Optionally add a custom layout, can be a static uniform value, or
 *     // dynamic one based on the relevant field value.
 *     $sets['layout'] = 'bottom';
 *
 *     // Optionally add a custom class, can be a static uniform class, or
 *     // dynamic one based on the relevant field value.
 *     $sets['class'] = 'slide--custom-class--' . $delta;
 *
 *     // If $row is a workable Media, pass to blazy.oembed service far below.
 *     // If $row is a plain old image, extract URI from it, says $row['#item']
 *     // is a property of Image formatter theme_image_style().
 *     // If $row is text, or any vanilla ouput, pass them to `content`. No URI
 *     // is required in this case.
 *     // The first is for Views rows output, the last field formatters.
 *     // Use Devel dpm($row) to figure out what it contains, assumptions:
 *     $item = $row['rendered']['#item'] ?? $row['#item'] ?? NULL;
 *     $uri = \Drupal\blazy\Blazy::uri($item);
 *
 *     // If working with Media, defer to blazy.oembed below, ignore URI.
 *     // If working with text or vanilla, pass it to `content`, ignore URI.
 *     // If working with images, pass URI directly to theme_blazy():
 *     $info = [
 *       'image' => [
 *         // URI is the only theme_blazy() requirement, ignorable for Vanilla:
 *         'uri' => $uri,
 *         // Add more as needed: title, alt, etc.
 *       ],
 *     ];
 *
 *     $content = [
 *       // $delta for galleries, or LCP like Loading priority: slider, etc.
 *       '#delta' => $delta,
 *
 *       // If working with Media, be sure to pass the #entity for blazy.oembed.
 *       // '#entity' => $media,
 *
 *       // If you have \Drupal\image\Plugin\Field\FieldType\ImageItem,
 *       // deprecated at blazy:3.x for $info.image array above:
 *       // '#item' => $item,
 *
 *       // $formatter::toSettings() initialize `blazies` object with $info.
 *       // You can move it up to access `blazies` object if needed.
 *       '#settings' => $formatter->toSettings($sets, $info),
 *
 *       // Only if non-media or media that theme_blazy() does not understand:
 *       // texts, theme_BLAH(), etc. or vanilla output, put it into `content`.
 *       // 'content' => $row,
 *
 *       // Optional captions: alt, description, data, link, overlay, title.
 *       // If having more complex caption data, use 'data' key instead.
 *       'captions' => ['title' => ['some caption render array']],
 *     ];
 *
 *     // If working with Media/ OEmbed/ VEF, other than plain old images:
 *     // $formatter->service('blazy.oembed')->build($content);
 *
 *     // Since blazy:2.17, pass slide and captions to theme_blazy() directly.
 *     // Vanilla or non-vanilla are accepted, as long as above-done correctly.
 *     $items[] = $formatter->getBlazy($content);
 *   }
 *
 *   // Pass the $items to the array.
 *   $build['items'] = $items;
 *
 *   // 3.
 *   // Optional specific JS options, to re-use one optionset, can be removed.
 *   // Play with speed and options to achieve desired result.
 *   // @see config/install/slick.optionset.default.yml
 *   $build['#options'] = [
 *     'arrows'    => FALSE,
 *     'autoplay'  => TRUE,
 *     'vertical'  => TRUE,
 *     'draggable' => FALSE,
 *   ];
 *
 *   // 4.
 *   // Build the slick with the arguments as described above.
 *   $element = $manager->build($build);
 *
 *   // Prepare $variables to pass into a .twig.html file.
 *  $variables['slick'] = $element;
 *
 *   // Render the slick at a .twig.html file.
 *   // {{ slick }}
 *   // Or simply return the $element if a renderable array is expected.
 *   return $element;
 * }
 * @endcode
 * @see \Drupal\slick\SlickManager::build()
 * @see template_preprocess_slick_wrapper()
 *
 * @section sec_asnavfor AsNavFor sample #3
 *
 * The only requirement for asNavFor is optionset and optionset_thumbnail IDs:
 * @code
 * $build['#settings']['optionset'] = 'optionset_name';
 * $build['#settings']['optionset_thumbnail'] = 'optionset_thumbnail_name';
 * @endcode
 *
 * The rest are optional, and will fallback to default:
 *   - $build['#settings']['optionset_thumbnail'] = 'optionset_thumbnail_name';
 *     Defined at the main settings.
 *
 *   - $build['#settings']['id'] = 'slick-asnavfor';
 *     Only main display ID is needed. The thumbnail ID will be
 *     automatically created: 'slick-asnavfor-thumbnail', including the content
 *     attributes accordingly. If none provided, will fallback to incremented
 *     ID.
 *
 * See the HTML structure below to get a clear idea.
 *
 * 1. Main slider:
 * @code
 *   <div id="slick-asnavfor" class="slick">
 *     <div class="slick__slider slick-initialized slick-slider">
 *       <div class="slick__slide"></div>
 *     </div>
 *   </div>
 * @endcode
 * 2. Thumbnail slider:
 * @code
 *   <div id="slick-asnavfor-thumbnail" class="slick">
 *     <div class="slick__slider slick-initialized slick-slider">
 *       <div class="slick__slide"></div>
 *     </div>
 *   </div>
 * @endcode
 * The asnavfor targets are the 'slick-initialized' attributes, and managed by
 * the module automatically when using SlickManager::build().
 *
 * Returns the renderable array of slick instances.
 * @code
 * function my_module_render_slick_asnavfor() {
 *   // Invoke the manager service, or use a DI service container accordingly.
 *   $manager = \Drupal::service('slick.manager');
 *
 *   // Access the formatter service for image related methods:
 *   $formatter = \Drupal::service('slick.formatter');
 *
 *   $build = [];
 *
 *   // 1. Main slider ---------------------------------------------------------
 *   // Add the main display items.
 *   $build['items'] = [];
 *
 *   $images = [1, 2, 3, 4, 6, 7];
 *   foreach ($images as $key) {
 *
 *     // Each item has keys: #delta, #settings, captions.
 *     $info = ['image.uri' => 'public://image-' . $delta . '.jpg'];
 *     $sets = $build['#settings'];
 *
 *     $content = [
 *       '#delta' => $delta,
 *       '#settings' => $formatter->toSettings($sets, $info),
 *
 *       // Thumbnail caption accepts direct markup or custom renderable array
 *       // without any special key to be simple as much as complex.
 *       // Think Youtube playlist with scrolling nav: thumbnail, text, etc.
 *       'captions' => ['title' => ['#markup' => 'Description #' . $delta]],
 *     ];
 *
 *     $build['items'][] = $formatter->getBlazy($content);
 *   }
 *
 *   // Optionally override the optionset.
 *   $build['#options'] = [
 *     'arrows'        => FALSE,
 *     'centerMode'    => TRUE,
 *     'centerPadding' => '',
 *   ];
 *
 *   // Satisfy the asnavfor main settings.
 *   // @see \Drupal\slick\SlickDefault for most supported settings.
 *   $build['#settings'] = [
 *     // The only required is 'optionset_thumbnail'.
 *     // The thumbnail_style is only required if using image, otherwise will
 *     // use tabs-like navigation from captions. One of them is required.
 *     // Define both main and thumbnail optionset names at the main display.
 *     'optionset' => 'optionset_main_name',
 *     'optionset_thumbnail' => 'optionset_thumbnail_name',
 *     'thumbnail_style' => 'thumbnail',
 *
 *     // The rest is optional, just FYI.
 *     'skin' => 'skin-main-name',
 *     'skin_thumbnail' => 'skin-thumbnail-name',
 *   ];
 *
 *   // 2. Thumbnail slider ----------------------------------------------------
 *   // The thumbnail array is grouped by 'thumb', yet has the same structured
 *   // array as the main display: items, #options, #optionset, #settings.
 *   $build['thumb'] = [];
 *   foreach ($images as $key) {
 *
 *     // URI and thumbnail_style above are the only required for thumbnail
 *     // navigation. If left empty, will use the captions below for tabs-like
 *     // navigation. One of them is required.
 *     $info = ['thumbnail.uri' => 'public://image-' . $delta . '.jpg'];
 *     $sets = $formatter->toSettings($build['#settings'], $info);
 *
 *     // Only if you need to add more info here:
 *     $blazies = $sets['blazies'];
 *     $blazies->set('image.alt', t('Preview'));
 *
 *     // Only if thumbnail captions are needed:
 *     $captions = ['#markup' => t('Slide #' .$delta)];
 *
 *     $build['nav']['items'][] = $formatter->getThumbnail($sets, NULL, $captions);
 *   }
 *
 *   // Optionally override 'optionset_thumbnail_name' with custom JS options.
 *   $build['thumb']['#options'] = [
 *     'arrows'        => TRUE,
 *     'centerMode'    => TRUE,
 *     'centerPadding' => '10px',
 *
 *     // Be sure to have multiple slides for the thumbnail, otherwise nonsense.
 *     'slidesToShow'  => 5,
 *   ];
 *
 *   // Build the slick once.
 *   $element = $manager->build($build);
 *
 *   // Prepare variables to pass into a .twig.html file.
 *   $variables['slick'] = $element;
 *
 *   // Render the slick at a .twig.html file.
 *   // {{ slick }}
 *   // Or simply return the $element if a renderable array is expected.
 *   return $element;
 * }
 * @endcode
 * @see \Drupal\slick\SlickManager::build()
 * @see template_preprocess_slick_wrapper()
 *
 * @section sec_skin Registering Slick skins
 *
 * To register a skin, copy \Drupal\slick\Plugin\slick\SlickSkin into your
 * module /src/Plugin/slick directory. Adjust everything accordinngly: rename
 * the file, change SlickSkin ID and label, change class name and its namespace,
 * define skin name, and its CSS and JS assets.
 *
 * The SlickSkin object has 3 supported methods: ::setSkins(), ::setDots(),
 * ::setArrows() to have skin options for main/thumbnail/overlay displays, dots,
 * and arrows skins respectively.
 * The declared skins will be available for custom coded, or UI selections.
 *
 * @see \Drupal\slick\SlickSkinPluginInterface
 * @see \Drupal\slick_example\Plugin\slick\SlickExampleSkin
 * @see \Drupal\slick_extras\Plugin\slick\SlickExtrasSkin
 * @see \Drupal\slick_test\Plugin\slick\SlickSkin for the most complete samples
 *
 * Add the needed methods accordingly.
 * This can be used to register skins for the Slick. Skins will be
 * available when configuring the Optionset, Field formatter, or Views style,
 * or custom coded slicks.
 *
 * Slick skins get a unique CSS class to use for styling, e.g.:
 * If your skin name is "my_module_slick_carousel_rounded", the CSS class is:
 * slick--skin--my-module-slick-carousel-rounded
 *
 * A skin can specify CSS and JS files to include when Slick is displayed,
 * except for a thumbnail skin which accepts CSS only.
 *
 * Each skin supports a few keys:
 * - name: The human readable name of the skin.
 * - description: The description about the skin, for help and manage pages.
 * - css: An array of CSS files to attach.
 * - js: An array of JS files to attach, e.g.: image zoomer, reflection, etc.
 * - group: A string grouping the current skin: main, thumbnail, arrows, dots.
 * - dependencies: Similar to how core library dependencies constructed.
 * - provider: A module name registering the skins.
 * - options: Extra JavaScript (Slicebox, 3d carousel, etc) options merged into
 *     existing [data-slick] attribute to be consumed by custom JS.
 *
 * @section sec_skins Defines the Slick main and thumbnail skins
 *
 * @code
 * protected function setSkins() {
 *   // If you copy this file, be sure to add base_path() before any asset path
 *   // (css or js) as otherwise failing to load the assets. Your module can
 *   // register paths pointing to a theme. Almost similar to library.
 *   $theme_path = $this->getPath('theme', 'my_theme');
 *
 *   return [
 *     'skin_name' => [
 *       // Human readable skin name.
 *       'name' => 'Skin name',
 *
 *       // Description of the skin.
 *       'description' => $this->t('Skin description.'),
 *
 *       // Group skins to reduce confusion on form selection: main, thumbnail.
 *       'group' => 'main',
 *
 *       // Optional module name to prefix the library name.
 *       'provider' => 'my_module',
 *
 *       // Custom assets to be included within a skin, e.g.: Zoom, Reflection,
 *       // Slicebox, etc.
 *       'css' => [
 *         'theme' => [
 *           // Full path to a CSS file to include with the skin.
 *           $theme_path . '/css/my-theme--slider.css' => [],
 *           $theme_path . '/css/my-theme--carousel.css' => [],
 *         ],
 *       ],
 *       'js' => [
 *         // Full path to a JS file to include with the skin.
 *         $theme_path . '/js/my-theme--slider.js' => [],
 *         $theme_path . '/js/my-theme--carousel.js' => [],
 *         // To act on afterSlick event, or any other slick events,
 *         // put a lighter weight before slick.load.min.js (0).
 *         $theme_path . '/js/slick.skin.menu.min.js' => ['weight' => -2],
 *       ],
 *
 *       // Alternatively, add extra library dependencies for re-usable
 *       // libraries. These must be registered as module libraries first.
 *       // Use above CSS and JS directly if reluctant to register libraries.
 *       'dependencies' => [
 *         'my_module/d3',
 *         'my_module/slicebox',
 *         'my_module/zoom',
 *       ],
 *
 *       // Add custom options to be merged into [data-slick] attribute.
 *       // Below is a sample of Slicebox options merged into Slick options.
 *       // These options later can be accessed in the custom JS acccordingly.
 *       'options' => [
 *         'orientation'     => 'r',
 *         'cuboidsCount'    => 7,
 *         'maxCuboidsCount' => 7,
 *         'cuboidsRandom'   => TRUE,
 *         'disperseFactor'  => 30,
 *         'itemAnimation'   => TRUE,
 *         'perspective'     => 1300,
 *         'reflection'      => TRUE,
 *         'effect'          => ['slicebox', 'zoom'],
 *       ],
 *     ],
 *   ];
 * }
 * @endcode
 *
 * @section sec_dots Defines Slick dot skins
 *
 * The provided dot skins will be available at sub-module UI form.
 * A skin dot named 'hop' will have a class 'slick-dots--hop' for the UL.
 *
 * The array is similar to the self::setSkins(), excluding group, JS.
 * @code
 * protected function setDots() {
 *   // Create an array of dot skins.
 *   return [];
 * }
 * @endcode
 *
 * @section sec_arrows Defines Slick arrow skins
 *
 * The provided arrow skins will be available at sub-module UI form.
 * A skin arrow 'slit' will have a class 'slick__arrow--slit' for the NAV.
 *
 * The array is similar to the self::setSkins(), excluding group, JS.
 *
 * @return array
 *   The array of the arrow skins.
 * @code
 * protected function setArrows() {
 *   // Create an array of arrow skins.
 *   return [];
 * }
 * @endcode
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modifies overridable options at admin UI to re-use one optionset.
 *
 * Only accepts boolean values as these are displayed as checkboxes under
 * `Override main optionset` form field at Slick formatter/ Slick Views forms.
 *
 * @see \Drupal\slick\Form\SlickAdmin::getOverridableOptions()
 * @see config/install/slick.optionset.default.yml
 *
 * @ingroup slick_api
 */
function hook_slick_overridable_options_info_alter(&$options) {
  // Adds RTL option to Slick field formatters, or Slick Views UI forms.
  $options['rtl'] = t('RTL');
}

/**
 * Modifies Slick optionset before being passed to preprocess, or templates.
 *
 * @param \Drupal\slick\Entity\Slick $slick
 *   The Slick object being modified.
 * @param array $settings
 *   The contextual settings related to UI and HTML layout settings.
 *
 * @see \Drupal\slick\SlickManager::preRenderSlick()
 *
 * @ingroup slick_api
 */
function hook_slick_optionset_alter(Slick &$slick, array $settings) {
  if ($slick->id() == 'x_slick_nav') {
    // Overrides the main settings of navigation with optionset ID x_slick_nav.
    // To see available options, see config/install/slick.optionset.default.yml.
    // Disable arrows.
    $slick->setSetting('arrows', FALSE);

    // Checks if we have defined responsive settings.
    if ($responsives = $slick->getResponsiveOptions()) {
      foreach ($responsives as $key => $responsive) {
        if ($responsive['breakpoint'] == 481) {
          // If Optimized option is enabled, only those different from default
          // settings will be displayed at $responsive array. To poke around
          // available settings, see config/install/slick.optionset.default.yml
          // See what we have here.
          // dpr($responsive);
          // Overrides responsive settings.
          $values = $responsive['settings'];
          $values['centerPadding'] = '40px';
          $values['slidesToShow'] = 1;

          // Assign the new settings values.
          $slick->setResponsiveSettings($values, $key);
          // Verify responsive settings updated.
          // dpr($slick->getResponsiveOptions());
        }
      }
    }
  }
}

/**
 * Modifies Slick options before being passed to preprocess, or templates.
 *
 * Alternative to hook_slick_optionset_alter modify options directly.
 *
 * @param array $options
 *   The modified options related to JavaScript options.
 * @param array $settings
 *   The contextual settings related to UI and HTML layout settings.
 * @param \Drupal\slick\Entity\Slick $slick
 *   The Slick object being modified.
 *
 * @see \Drupal\slick\SlickManager::preRenderSlick()
 *
 * @ingroup slick_api
 */
function hook_slick_options_alter(array &$options, array $settings, Slick $slick) {
  // Change options as needed based on the given settings.
}

/**
 * Modifies Slick HTML settings before being passed to preprocess, or templates.
 *
 * If you need to override globally to be inherited by all blazy-related
 * modules: slick, gridstack, mason, etc., consider hook_blazy_settings_alter().
 *
 * @param array $build
 *   The array containing: item, content, settings, or optional captions.
 * @param object $items
 *   The \Drupal\Core\Field\FieldItemListInterface items.
 *
 * @see \Drupal\blazy\BlazyFormatter::buildSettings()
 * @see \Drupal\slick\SlickFormatter::buildSettings()
 *
 * @ingroup slick_api
 */
function hook_slick_settings_alter(array &$build, $items) {
  // Since blazy:2.17, this may be replaced with just hook_blazy_settings_alter
  // for the entire blazy ecosytem instead.
  // Most configurable settings are put as direct key-value pairs.
  // Before blazy:2.17, the key is plain.
  // $settings = &$build['settings'];
  // Since blazy:2.17, the key is hashed to avoid leaks/ render errors.
  $settings = &$build['#settings'];

  // See blazy_blazy_settings_alter() at blazy.module for existing samples.
  // First check the $settings array. Slick Views may have different array.
  if (isset($settings['entity_id'])) {
    // Change skin if meeting a particular criteria.
    if ($settings['optionset'] == 'x_slick_for') {
      $settings['skin'] = $settings['entity_id'] == 54 ? 'fullwidth' : $settings['skin'];
    }

    // Swap optionset at particular pages.
    if (in_array($settings['entity_id'], [54, 64, 74])) {
      $settings['optionset'] == 'my_slick_pages';
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
