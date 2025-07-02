<?php

/**
 * @file
 * Hooks and API provided by the Blazy module.
 *
 * @todo needs updating by the new decoupled lazy script options.
 */

/**
 * @defgroup blazy_api Blazy API
 * @{
 * Information about the Blazy usages.
 *
 * Modules may implement any of the available hooks to interact with Blazy.
 * Blazy may be configured using the web interface using formatters, or Views.
 * However below is a few sample coded ones.
 *
 * Since blazy:2.6, non-configurable settings were moved into settings.blazies
 * as the instance of \Drupal\blazy\BlazySettings. Should you need to build it
 * from the scratch with theme_blazy(), not using the provided API, please
 * create the object like so:
 *
 * @code
 * $settings = \Drupal\blazy\Blazy::init();
 * $blazies = $settings['blazies'];
 * @endcode
 *
 * Now you can access settings.blazies, and set anything as needed.
 *
 * @section sec_quick Quick sample #1
 * A single image sample.
 *
 * If you need to work with lightbox, linkable content, media, grid, captions,
 * and other featured, please jump from your window to sample #2. This one is
 * more useful for individual item and basic understanding of theme_blazy().
 *
 * @code
 * function my_module_render_blazy() {
 *   // Old behaviors will be very minimally preserved till 3.x.
 *   // Put the namespaces into `use` directives, e.g.: use Drupal\blazy\Blazy;
 *   // The ::init() contains empty blazies object for convenience, and optional
 *   // initial settings data parameter to override defaults.
 *   $settings = \Drupal\blazy\Blazy::init();
 *
 *   // Pass configurable settings directly into $settings. These can also be
 *   // moved into ::init() method argument above instead.
 *   // See more in \Drupal\blazy\BlazyDefault::imageSettings():
 *   $settings['image_style'] = 'thumbnail';
 *
 *   // Pass non-configurable ones into settings.blazies object:
 *   $blazies = $settings['blazies'];
 *
 *   // For multiple items, be sure to set delta in the loop accordingly:
 *   $blazies->set('delta', $delta)
 *     // While the invalid URI is just printed, only valid URI can have image
 *     // styles, or at least using a normal public URL: /sites/default/files/:
 *     ->set('image.uri', 'public://logo.png')
 *
 *     // ->set('image.url', '/logo.png') // <= image.url alone won't work!
 *
 *     // If you have no valid URI, simply change `url` to `uri` like below,
 *     // invalid URI, including external/ sister site url, is just printed:
 *     // ->set('image.uri', '/logo.png')
 *     // ->set('image.uri', 'https://example.com/logo.png')
 *
 *     ->set('image.alt', t('Preview'))
 *
 *     // If you don't set `image_style`, provide a dimension in the least.
 *     ->set('image.width', 140);
 *
 *   // Passing width/height/alt/title to #item_attributes was deprecated since
 *   // 2.6 when RDF was deprecated from D9. Use settings.blazies above instead.
 *   // The #item_attributes will be finally removed at 3.x.
 *   // Use blazies.image.attributes or blazies.iframe.attributes for anything
 *   // other than basic image attributes (width/height/alt/title) instead.
 *   // You are on your own other than the above-mentioned supported attributes.
 *   // Supported means, it won't mess up the provided image_style, etc.
 *   // On your own means, you can XSS attack your own site, it's all yours.
 *   // Since 2.6, theme_blazy() looks dead simple, yet more robust:
 *   $build = [
 *     '#theme'    => 'blazy',
 *     '#settings' => $settings,
 *   ];
 *
 *   // Optionally attach the supported libraries, or include/ merge it into a
 *   // parent container:
 *   $build['#attached'] = ['library' => ['blazy/load']];
 *
 *   // Or more robust with BlazyManager::attach() as required:
 *   $build['#attached'] = blazy()->attach($settings);
 *
 *   // Or for defaults, affected by Blazy UI, simply leave it empty.
 *   // Or even remove this line completely, we got you covered:
 *   $build['#attached'] = blazy()->attach();
 *
 *   return $build;
 * }
 * @endcode
 * @see \Drupal\blazy\Theme\BlazyTheme::blazy()
 * @see \Drupal\blazy\BlazyDefault::imageSettings()
 * @see \Drupal\gridstack_ui\Controller\GridStackListBuilder::buildRow()
 * @see template_preprocess_blazy()
 *
 * @section sec_detail Detailed sample #2
 *
 * A multiple image sample.
 *
 * For advanced usages with multiple images, and a few Blazy features such as
 * lightboxes, lazyloaded images, or iframes, including CSS background and
 * aspect ratio, etc. depending on field types or vanilla/ rendered entity, etc:
 *   o Invoke blazy.manager, and or blazy.formatter, services.
 *   o Use \Drupal\blazy\BlazyManager::getBlazy() method to work with any
 *     content (texts, images/media, Views rows, vanilla) and pass relevant
 *     settings which request for particular Blazy features accordingly.
 *   o Use \Drupal\blazy\BlazyManager::attach() to load relevant libraries.
 * @code
 * function my_module_render_blazy_multiple() {
 *   // Invoke the manager service, or use a DI service container accordingly.
 *   // Specific to Blazy, $manager and $formatter have straight inheritance.
 *   // Using $formatter for Blazy specifically is the best bet.
 *   // For sub-modules, use their $manager if calling ::build().
 *   // However sub-modules deviate, and must call the correct servive.
 *   // $manager = \Drupal::service('blazy.manager');
 *   $manager = blazy();
 *   $formatter = \Drupal::service('blazy.formatter');
 *
 *   // Option init #1 at container level:
 *   // The ::init() contains empty blazies object for convenience, and optional
 *   // initial settings data parameter to override defaults.
 *   $settings = \Drupal\blazy\Blazy::init();
 *
 *   // Option init #2 at item level:
 *   // $parent_settings is the first settings setup as above, here in a loop.
 *   // $settings = $manager->toSettings($parent_settings, $info); to have
 *   // initial $info which should be stored within blazies object initially.
 *   // Basically 3 tasks: reset blazies object per item, merging initial parent
 *   // $settings along with the initial $info for item-level blazies object.
 *
 *   // Supported media switcher options dependent on available modules:
 *   // colorbox, media (Image to iframe), etc. These can also be moved into
 *   // ::init() method argument above instead. These settings are normally
 *   // seen at Field formatter/ Views Style UI form items, and defined in
 *   // Drupal\blazy\BlazyDefault, or any similar extending classes.
 *   $settings['media_switch'] = 'media';
 *   $settings['image_style'] = 'large';
 *   $settings['ratio'] = 'fluid';
 *
 *   // If adding grid, lightbox, and other features seen at formatters:
 *   // $formatter->preSettings($settings);
 *   // If having FieldItemListInterface, ignore the above line, and use:
 *   // $formatter->preElements($build, $items, $entities);
 *
 *   // Build contents, assumed inside a loop here.
 *   // Captions key contains: alt, description, data, link, overlay, title.
 *   // The image.uri is the only required by theme_blazy(). This $info is
 *   // optional/ removable if using the second approach below.
 *
 *   // Option setter #1, add image.alt, image.title, etc. as needed:
 *   $info = ['image.uri' => 'https://drupal.org/files/One.gif'];
 *
 *   // Option setter #2:
 *   // $manager::toSettings() initialize `blazies` object with added $info, and
 *   // reset per item, be sure to repeat the call per item.
 *   // You can move it up here to access `blazies` object for more works.
 *   // Notice $info was left out, and use the blazies setter instead:
 *   // $settings = $manager->toSettings($settings);
 *   // $blazies = $settings['blazies'];
 *   // Now do anything with $blazies setter:
 *   // $blazies->set('image.uri', 'BLAH')
 *   //   ->set('image.alt', 'BLAH')
 *   //   ->set('image.title', 'BLAH');
 *
 *   // The required are #delta and #settings. Captions, etc. is optional.
 *   $content = [
 *     // Delta is for galleries, or LCP like Loading priority: slider, etc.
 *     '#delta' => 0,
 *
 *     // If using Option setter #1:
 *     '#settings' => $manager->toSettings($settings, $info),
 *
 *     // If using Option setter #2:
 *     // '#settings' => $settings,
 *
 *     'captions' => ['title' => ['#markup' => t('Description #1')]],
 *
 *      // Only if non-media or media that theme_blazy() does not understand:
 *      // texts, theme_BLAH(), etc. or vanilla output, put it into `content`.
 *      // See Options below before giving up here.
 *      // 'content' => $rendered_entity,
 *
 *      // If working with Media, Paragraphs, etc, be sure to pass the #entity
 *      // for blazy.oembed to extract relevant ImageItem, and media data.
 *      // '#entity' => $media,
 *
 *      // If you have \Drupal\image\Plugin\Field\FieldType\ImageItem,
 *      // deprecated at blazy:3.x for $info.image array above:
 *      // '#item' => $item,
 *   ];
 *
 *   // Options #1 with Media entity or VEF, not expecting vanilla:
 *   // If working with Media/ OEmbed/ VEF, other than plain old images,
 *   // do not set `content` early above, blazy.oembed will do:
 *   // $manager->service('blazy.oembed')->build($content);
 *
 *   // Pass $content to theme_blazy() after working with any Media/ VEF.
 *   $items[] = $manager->getBlazy($content);
 *
 *   // Options #2 with any entities, File, Media, etc., for (non-)vanilla:
 *   // Do not set `content` early above, blazy.entity will do, including
 *   // passing it to ::getBlazy().
 *   // Normally outside formatters with very minimal entity field info.
 *   // If workable, it will output like Options #1, else fallback to Vanilla.
 *   // This is more optimistic than Options #3 below.
 *   // See \Drupal\blazy\Plugin\views\field\BlazyViewsFieldFile
 *   // See \Drupal\blazy\Plugin\views\field\BlazyViewsFieldMedia
 *   // See \Drupal\io_browser\Plugin\EntityBrowser\FieldWidgetDisplay
 *   // See \Drupal\slick_browser\Plugin\EntityBrowser\FieldWidgetDisplay
 *   // $items[] = $manager->service('blazy.entity')->build($content);
 *
 *   // Options #3 with any entities, File, Media, etc., for vanilla.
 *   // Do not set `content` early, blazy.entity will do.
 *   // This is more pessimistic and opportunistic than Option #2, expecting
 *   // more for vanilla aka rendered entity, but will output non-vanilla if
 *   // workable:.
 *   // $items[] = $manager->service('blazy.entity')->view($content);
 *
 *   // Options #4 with any entities, and expecting just plain vanilla aka
 *   // rendered entity. Normally needed if rendered entities are to be
 *   // placed inside grids. While Options #2 and #3 will work out first for
 *   // non-vanilla before giving up to this rendered entity, this one is indeed
 *   // expecting a rendered entity. The only reason it is called is Blazy grid.
 *   // $items[] = $manager->view($content);
 *
 *   // Alternatively put it into `content` as mentioned above for non-grid:
 *   // $content['content'] = $manager->view($content);
 *   // $items[] = $content;
 *
 *   // See below ...Formatter::buildElements() for consistent samples.
 *   // Since 2.17, items are stored in `items` key to match sub-modules.
 *   // And extracted as needed depending on the parent themes -- theme_field()
 *   // and theme_item_list() requirements.
 *   // Both items and indicies will continue working till the end of the day.
 *   // The correct one for theme_field() is indices as we did all along, but we
 *   // gotta be trendy with sub-modules for interchangeability and easy swap.
 *   // Some have been established before blazy, cannot argue with the ancient.
 *   // No biggies, we do not always deal with fields, might be Views rows, etc.
 *   $build['items'] = $items;
 *
 *   // Finally attach libraries as requested via $settings.
 *   $build['#attached'] = $manager->attach($settings);
 *
 *   // Options return #1, expecting a Blazy grid display, or theme_field():
 *   // return $manager->build($build);
 *
 *   // Options return #2, passing to any sub-modules' managers, not formatters,
 *   // requires their relevant settings setup first as above-mentioned. See
 *   // their BLAH.api.php if available, \Drupal\blah\BlahDefault, or go
 *   // directly to their ::build() method if not. There might be some slight
 *   // difference in requirements, but overall look pretty much similar:
 *   // return slick()->build($build);
 *   // return splide()->build($build);
 *   // return gridstack()->build($build);
 *   // return outlayer()->build($build);
 *   // return mason()->build($build);
 *
 *   // Options return #3, passing to Twig at any template_preprocess:
 *   // $variables['content'] = $manager->build($build);
 *   // At Twig: {{ content }}
 *
 *   // Options return #4, expecting your own render array display:
 *   return $build;
 * }
 * @endcode
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFormatterBlazy::buildElements()
 * @see \Drupal\gridstack\Plugin\Field\FieldFormatter\GridStackFileFormatterBase::buildElements()
 * @see \Drupal\slick\Plugin\Field\FieldFormatter\SlickFileFormatterBase::buildElements()
 * @see \Drupal\blazy\BlazyManager::getBlazy()
 * @see \Drupal\blazy\BlazyDefault::imageSettings()
 * @see hook_blazy_alter()
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters Blazy attachments to add own library, drupalSettings, and JS template.
 *
 * @param array $load
 *   The array of loaded library being modified.
 * @param array $settings
 *   The available array of settings.
 *
 * @ingroup blazy_api
 */
function hook_blazy_attach_alter(array &$load, array $settings) {
  // Since 2.6, non-configurable settings are mostly grouped under `blazies`.
  // For pre 2.6, please use $settings['NAME'] directly.
  $blazies = $settings['blazies'];

  // Attach additional libraries or drupalSettings if meeting a condition:
  if ($blazies->get('photoswipe')) {
    $load['library'][] = 'my_module/load';

    $template = ['#theme' => 'photoswipe_container'];
    $load['drupalSettings']['photoswipe'] = [
      'options' => blazy()->config('options', 'photoswipe.settings'),
      'container' => blazy()->renderInIsolation($template),
    ];
  }
}

/**
 * Alters available lightboxes for Media switch select option at Blazy UI.
 *
 * @param array $lightboxes
 *   The array of lightbox options being modified.
 *
 * @see https://www.drupal.org/project/blazy_photoswipe
 *
 * @ingroup blazy_api
 */
function hook_blazy_lightboxes_alter(array &$lightboxes) {
  $lightboxes[] = 'photoswipe';
}

/**
 * Alters Blazy individual item output to support a custom lightbox.
 *
 * Or better use hook_preprocess_blazy() for simple needs.
 *
 * @param array $build
 *   The renderable array of image/ video iframe being modified.
 * @param array $settings
 *   The available array of settings.
 *
 * @ingroup blazy_api
 */
function hook_blazy_alter(array &$build, array $settings) {
  if (!empty($settings['media_switch']) && $settings['media_switch'] == 'photoswipe') {
    // Full blown overrides, and must also implement trusted callback:
    $build['#pre_render'][] = 'my_module_pre_render';
  }
}

/**
 * Alters Blazy outputs entirely to support a custom (quasy-)lightbox.
 *
 * In a case of ElevateZoom Plus, it adds a prefix large image preview before
 * the Blazy Grid elements by adding an extra #theme_wrappers via #pre_render
 * element.
 *
 * @param array $build
 *   The renderable array of the entire Blazy output being modified.
 * @param array $settings
 *   The available array of settings.
 *
 * @ingroup blazy_api
 */
function hook_blazy_build_alter(array &$build, array $settings) {
  // Since 2.6, non-configurable settings are mostly grouped under `blazies`.
  // For pre 2.6, please use $settings['NAME'] directly.
  $blazies = $settings['blazies'];

  // All (quasi-)lightboxes are put directly under $blazies for being unique.
  // This also allows a quasi-lightbox like ElevateZoomPlus inject its optionset
  // as its value: elevatezoomplus: responsive, etc.
  if ($blazies->get('colorbox') || $blazies->get('zooming')) {
    // Full blown overrides, and must also implement trusted callback:
    $build['#pre_render'][] = 'my_module_pre_render_build';
  }
}

/**
 * Alters blazy-related formatter form options to make site-builders happier.
 *
 * A less robust alternative to third party settings to pass the options to
 * blazy-related formatters within the designated compact form.
 * While third party settings offer more fine-grained control over a specific
 * formatter, this offers a swap to various blazy-related formatters at one go.
 * Any class extending \Drupal\blazy\BlazyDefault will be capable
 * to modify both form and UI options at one go.
 *
 * This requires 4 things: option definitions (this alter), schema, extended
 * forms, and front-end implementation of the provided options which can be done
 * via regular hook_preprocess().
 *
 * Accordingly update the schema via core hook_config_schema_info_alter(), or
 * regular module.schema.yml file to have a valid schema.
 * @code
 * function hook_config_schema_info_alter(array &$definitions) {
 *   $settings = ['color' => '', 'arrowpos' => '', 'dotpos' => ''];
 *   blazy()->configSchemaInfoAlter($definitions,
 *     'slick_base', SlickDefault::extendedSettings() + $settings);
 * }
 * @endcode
 *
 * In addition to the schema, implement hook_blazy_form_element_alter()
 * to provide the actual extended forms, see far below. And lastly, implement
 * the options at front-end via hook_preprocess().
 *
 * @param array $settings
 *   The settings being modified.
 * @param array $context
 *   The array containing class which defines or limit the scope of the options.
 *
 * @ingroup blazy_api
 */
function hook_blazy_base_settings_alter(array &$settings, array $context) {
  // One override for both various Slick field formatters and Slick views style.
  // SlickDefault extends BlazyDefault, hence capable to modify/ extend options.
  // These options will be available at many Slick formatters at one go.
  if ($context['class'] == 'Drupal\slick\SlickDefault') {
    $settings += ['color' => '', 'arrowpos' => '', 'dotpos' => ''];
  }

  // If you want to inject new settings into various sub-module formatters:
  $classes = [
    'Drupal\blazy\BlazyDefault',
    'Drupal\gridstack\GridStackDefault',
    'Drupal\slick\SlickDefault',
    'Drupal\splide\SplideDefault',
  ];
  if (in_array($context['class'], $classes)) {
    $settings += ['elevatezoomplus' => ''];
  }
}

/**
 * Alters blazy settings inherited by all child elements.
 *
 * @param array $build
 *   The array containing: #settings, or potential #optionset for sub-modules.
 * @param object|null $object
 *   Since 3.0.9, maybe one of these depending on the provider/ trigger:
 *     - Drupal\Core\Field\FieldItemListInterface for field formatters.
 *     - Drupal\views\ViewExecutable for Views fields.
 *     - NULL, such as from IO/ Slick Browser widget displays.
 *   This should make many sub-modules support their lightboxes at one go.
 *
 * @see \Drupal\blazy\BlazyFormatter
 * @see \Drupal\blazy\BlazyEntity
 * @see \Drupal\blazy\Plugin\views\field\BLAH
 * @see \Drupal\io_browser\Plugin\EntityBrowser\BLAH
 * @see \Drupal\slick_browser\Plugin\EntityBrowser\BLAH
 *
 * @ingroup blazy_api
 */
function hook_blazy_settings_alter(array &$build, $object) {
  $settings = &$build['#settings'];

  // Most configurable settings are put as direct key-value pairs.
  // Since 2.6, non-configurable settings are mostly grouped under `blazies`.
  // For pre 2.6, please use $settings['NAME'] directly.
  $blazies = $settings['blazies'];

  // Add more custom CSS aspect ratios, see /admin/help/blazy_ui#aspect-ratio.
  // You must provide your own CSS rules in accordance with css/components/
  // blazy.ratio.css convention, hence .media--ratio--78 {}, etc.
  // These will NOT be seen as Aspect ratio form item options, but taken into
  // account/ calculated automatically only if Aspect ratio Fluid is chosen.
  // The benefit is removing the need to use padding hack inline styles for
  // your own custom CSS rules so to have cleaner markups.
  $blazies->set('css.ratio', ['7:8', '6:5'], TRUE);

  // Overrides one pixel placeholder on particular pages relevant if using Views
  // rewrite results which may strip out Data URI.
  // See https://drupal.org/node/2908861.
  $id = $blazies->get('entity.id');
  if ($id && in_array($id, [45, 67])) {
    $blazies->set('ui.placeholder', '/blank.gif');
  }

  // Alternatively override views blocks identified by `view.view_mode` with
  // a blank SVG since 1px gif has issues with non-square sizes, see #2908861:
  // <svg xmlns='https://www.w3.org/2000/svg' viewBox='0 0 100 100'/>
  // Adjust plugin ID since Blazy has a few formatters, View style/ fields.
  // Since 2.6, plugin_id is put under: `field`, 'view', `filter` under blazies.
  // For pre 2.6 all plugin IDs are ignorantly put under settings.plugin_id
  // replacing each other -- while hardly an issue, likely due to no real/useful
  // usages, it was plain wrong. A valid reason for `blazies` as grouping.
  // Field formatters are grouped under $blazies->get('field.plugin_id'):
  // - `blazy` for plain old Image.
  // - `blazy_media` for Media.
  // - `blazy_oembed` for oEmbed, etc.
  // View fields and styles are grouped under $blazies->get('view.plugin_id'):
  // - `blazy` for BlazyGrid Views style.
  // - `blazy_file` for Views field File like plain image galleries.
  // - `blazy_media` for Views field Media like mixed Media libraries.
  // [Blazy|Splide|Slick]Filter are under $blazies->get('filter.plugin_id'):
  // - `blazy_filter` for BlazyFilter, supports both plain media and galleries.
  // - `slick_filter` for SlickFilter galleries.
  // - `splide_filter` for SplideFilter galleries.
  $expected_plugin_id = $blazies->get('view.plugin_id') == 'blazy';

  // Only concern with blocks having `Rewrite view resuts` to fix 404 due to
  // `data:image` placeholder is stripped out by Views sanitization procedure.
  // By default machine names are like block_1, or page_1, etc. till changed.
  $rewriten_blocks = ['block_categories', 'block_popular', 'block_related'];
  if ($expected_plugin_id && $view_mode = $blazies->get('view.view_mode')) {
    if (in_array($view_mode, $rewriten_blocks)) {
      $blazies->set('ui.placeholder', '/blank.svg');
    }
  }

  // Makes any image-based title as caption formatted as HTML caption where
  // BlazyTitleFormatter is not available. The image title like:
  // Awesome image: some short sub-title, will be formatted as:
  // Awesome image <small>some short sub-title</small>.
  // The <small> tag can be put on another line using CSS display:block, etc.
  // Useful for captions so to make Alt attribute is more for longer SEO stuff.
  // Add more conditional based on entities, etc. via blazies objects.
  // Available since Blazy:3.0.6:
  $blazies->set('format.title', [
    // Any following character will be treated as a delimiter.
    'delimiter' => '|,:,/,- , —',
    'tag' => 'small',
    'link_to_entity' => TRUE,
  ]);
}

/**
 * Alters blazy item settings, useful for mixed media contents.
 *
 * This is called before the mixed media elements being populated allowing you
 * to change the item output via its specific settings.
 *
 * @param array $settings
 *   The array settings being modified.
 * @param array $attributes
 *   The .media element attributes being modified.
 * @param array $item_attributes
 *   The IMG element attributes being modified.
 *
 * @ingroup blazy_api
 */
function hook_blazy_item_alter(array &$settings, array &$attributes, array &$item_attributes) {
  $blazies = $settings['blazies'];

  // If it has a media embed url and a lightbox with unwanted implementations,
  // replace the lightbox with an inline media player, and leave the rest of
  // images as lightboxes.
  // Be sure to require `blazy/media` library somewhere, if not already loaded,
  // says put $blazies->set('libs.media', TRUE); in hook_blazy_settings_alter(),
  // conditionally to not waste libraries.
  if ($blazies->get('colorbox') && $blazies->get('media.embed_url')) {
    // Set the Media switch option to Image to iframe, hence, media player.
    $blazies->set('switch', 'media')
      // The is.player is deprecated in 2.17 for use.player.
      // ->set('is.player', TRUE)
      ->set('use.player', TRUE)
      // Disable lightbox so to use the above inline media player.
      ->set('is.lightbox', FALSE);
  }

  // Convert Blazy image with caption to use FIGURE tag.
  // Excluding sub-modules which may require elaborate conditions due to more
  // complex image and caption structures such as sliders. Mason, GridStack,
  // etc. may severely break with this if not scoped to blazy namespace.
  // More conditions are available under blazies.is, such as
  // $blazies->is('captioned') or $blazies->is('multimedia') in case
  // captioned or not, or breaking multimedia or media player, etc.
  // If any display issues with grid, media player, etc., refine or remove this.
  // blazies.is[image|video_file|twitter, etc.] is related to Media sources.
  if ($blazies->get('namespace') == 'blazy' && $blazies->is('image')) {
    $blazies->set('is.figcaption', TRUE)
      ->set('item.wrapper_tag', 'figure')
      ->set('item.wrapper_attributes.class', ['blazy__content']);
  }

  // Change default caption title tag from H2 to H3.
  $blazies->set('item.title_tag', 'h3');

  // Since > 2.17-beta1, below is no longer needed, already merged.
  // Modifies IMG attributes, relevant for BlazyFilter here, see
  // https://www.drupal.org/project/blazy/issues/3374519:
  // - item.raw_attributes should not be used as not only raw, but also cause
  //   lazy load, aspect ratio, image style, etc. failed.
  // - item.safe_attributes are cleaned out from most troubles, yet, not fully.
  // $safe_attrs = $blazies->get('item.safe_attributes', []);
  // Override $item_attributes selectively to avoid unidentified troubles,
  // hence only when I need `usemap` badly:
  // if (isset($safe_attrs['usemap'])) {
  // The ::merge method reverses arguments from normal merge, be warned!
  // Hence prioritizing the module-managed $item_attributes as the replacer.
  // so that you can still have abused ALT and TITLE for captions, yet cleaned
  // out for attributes, having cakes and eat them too thingies. If reversed,
  // you can only choose one. No abuses recommended, just so well-informed.
  // You can have fieldable captions with core Media without any abuses.
  // $item_attributes = blazy()->merge($item_attributes, $safe_attrs);
  // }
  // Inline comments must end in full-stops, etc. If you forgot, BOOM!
}

/**
 * Alters blazy-related formatter form elements.
 *
 * This takes advantage of Blazy taking care of a few elements finalizations,
 * such as adding #empty_option, extra CSS classes, checkboxes, states, grid,
 * etc. The best place to add new form items. This is run before
 * hook_blazy_complete_form_element_alter().
 *
 * @param array $form
 *   The $form being modified.
 * @param array $definition
 *   The array defining the scope of form elements.
 * @param object $scopes
 *   The scopes shortcut extracted from $definition for convenience.
 *
 * @see \Drupal\blazy\Form\BlazyAdminBase::finalizeForm()
 *
 * @ingroup blazy_api
 */
function hook_blazy_form_element_alter(array &$form, array $definition, $scopes) {
  // For pre 2.6, please use $definition['NAME'] directly, removed at 3.x.
  $namespace = $definition['namespace'] ?? FALSE;

  // Since 2.6, non-configurable settings are mostly grouped under `blazies`,
  // and form scopes under `scopes`. Both are BlazySettings objects with some
  // temporary overlaps since `blazies` are also visible at front-end.
  // Prioritize on `blazies` if any dups as `scopes` subject to cleaning out
  // from dups during migration process while `blazies` will always be intact
  // as also required by front-end.
  // $blazies = $definition['blazies'] ?? NULL;
  // Inline comments must end in full-stops, etc. If you forgot, BOOM!
  $namespace = $scopes->get('namespace') ?: $namespace;

  // At forms, configurable settings are grouped under `settings` since 1.x.
  $settings = $definition['settings'] ?? [];

  // Scope to splide formatters, blazy, gridstack, slick, etc. Or swap em all.
  if ($namespace == 'splide' && isset($settings['BLAH'])) {
    // Skip Splide text formatter.
    if ($scopes && !$scopes->is('no_image_style')) {
      // Extend the formatter form elements as needed.
    }
  }
}

/**
 * Alters blazy-related formatter form elements.
 *
 * Modify anything Blazy forms output as you wish. If you see your added form
 * items break the Native grid, use the previous hook_blazy_form_element_alter()
 * instead. This is only useful for anything but adding new form items.
 * This is run after hook_blazy_form_element_alter().
 *
 * @param array $form
 *   The $form being modified.
 * @param array $definition
 *   The array defining the scope of form elements.
 * @param object $scopes
 *   The scopes shortcut extracted from $definition for convenience.
 *
 * @see \Drupal\blazy\Form\BlazyAdminBase::finalizeForm()
 *
 * @ingroup blazy_api
 */
function hook_blazy_complete_form_element_alter(array &$form, array $definition, $scopes) {
  // For pre 2.6, please use $definition['NAME'] directly, removed at 3.x.
  $namespace = $definition['namespace'] ?? FALSE;

  // Since 2.6, non-configurable settings are mostly grouped under `blazies`,
  // and form scopes under `scopes`. Both are BlazySettings objects with some
  // temporary overlaps since `blazies` are also visible at front-end.
  // Prioritize on `blazies` if any dups as `scopes` subject to cleaning out
  // from dups during migration process while `blazies` will always be intact
  // as also required by front-end.
  // $blazies = $definition['blazies'] ?? NULL;
  // Inline comments must end in full-stops, etc. If you forgot, BOOM!
  $namespace = $scopes->get('namespace') ?: $namespace;

  // At forms, configurable settings are grouped under `settings` since 1.x.
  $settings = $definition['settings'] ?? [];

  // Scope to splide formatters, blazy, gridstack, slick, etc. Or swap em all.
  if ($namespace == 'splide' && isset($settings['BLAH'])) {
    // Skip Splide text formatter.
    if ($scopes && !$scopes->is('no_image_style')) {
      // Overrides the formatter form elements as needed.
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
