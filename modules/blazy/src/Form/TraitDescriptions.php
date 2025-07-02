<?php

namespace Drupal\blazy\Form;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * A description Trait to declutter, and focus more on form elements.
 */
trait TraitDescriptions {

  use StringTranslationTrait;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * {@inheritdoc}
   */
  public function nativeGridDescription() {
    $lb = $this->isAdminLb();
    return $this->t('<br><br>Accepted format for any below is a space separated value with a pair of <code>WIDTHxHEIGHT</code> or <code>WIDTH-HEIGHT</code>, or just single numbers. Use linebreak per 100% or 12 columns for reability. <br><br><b>Flexbox</b>, not <em>Flexbox Masonry</em>: <ol><li><b>Fixed/uniform width</b> with column amount: 1 to 12.</li><li><b>Variable width</b> with percentage: <br><code>10 15 20 25 30 33 34 40 50 55 60 75 77 80 100</code><br>Each row must amount to 100%, e.g.: <br><code>25 50 25</code><br><code>33 34 33</code><br><code>30 20 20 30</code></li><li><b>Variable width and fixed height</b>: To have a min-height specify in the format where WIDTH is the percentage, and HEIGHT is one of <br><code>x2s xxs xs sm md lg xl xxl x2l x3l x4l x5l</code>, e.g: <br><code>100-xxl</code><br><code>50-md 50-md</code><br><code>30-xs 20-xs 20-xs 30-xs</code><br>To add yours, see <code>css/blazy.style.css</code>.</li></ol><b>Native Grid</b>: <ol><li><b>One-dimensional</b>: Input a single numeric column grid, acting as Masonry, e.g.: <br><code>4</code> or <code>4x4</code><br>The first will be auto-height, the last fixed height. <em>Best with</em>: scaled images.</li><li><b>Two-dimensional</b>: Input the format pair based on the amount of columns/ rows, at max 12, e.g.: <br><code>4x4 4x3 2x2 2x4 2x2 2x3 2x3 4x2 4x2</code> <br>This will resemble GridStack optionset <b>Tagore</b>. Any single value e.g.: <code>4x4</code> will repeat uniformly like one-dimensional. <br><em>Best with</em>: <ul><li><b>Use CSS background</b> ON.</li><li>Exact item amount or better more designated grids than lacking. Use a little math with the exact item amount to have gapless grids.</li><li>Disabled image aspect ratio to use grid ratio instead.</li></ul></li></ol>@lb', [
      '@lb' => $lb ? '' : $this->t('This requires any grid-related <b>Display style</b>. Unless required, leave empty to DIY, or to not build grids.'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function baseDescriptions(): array {
    $scopes = $this->scopes;
    $namespace = static::$namespace;
    $help = '/admin/help/blazy_ui';
    $ui_url = '/admin/config/media/blazy';
    $lb = $this->isAdminLb();

    if ($this->blazyManager->moduleExists('help')) {
      $help = Url::fromUri('internal:/admin/help/blazy_ui')->toString();
    }

    if ($this->blazyManager->moduleExists('blazy_ui')) {
      $ui_url = Url::fromRoute('blazy.settings')->toString();
    }

    $view_mode = $this->t('Required to grab the fields, or to have custom entity display as fallback display. If it has fields, be sure the selected "View mode" is enabled, and the enabled fields here are not hidden there.');
    if ($this->blazyManager->moduleExists('field_ui')) {
      $view_mode .= ' ' . $this->t('Manage view modes on the <a href=":view_modes">View modes page</a>.', [':view_modes' => Url::fromRoute('entity.entity_view_mode.collection')->toString()]);
    }

    return [
      'background' => $this->background(),
      'preload' => $this->t("Preload to optimize the loading of late-discovered resources. Normally large or hero images below the fold. By preloading a resource, you tell the browser to fetch it sooner than the browser would otherwise discover it before Native lazy or lazyloader JavaScript kicks in, or starts its own preload or decoding. The browser caches preloaded resources so they are available immediately when needed. Nothing is loaded or executed at preloading stage. <br>Just a friendly heads up: do not overuse this option, because not everything are critical, <a href=':url'>read more</a>.", [
        ':url' => 'https://www.drupal.org/node/3262804',
      ]),
      'link' => $this->t('<strong>Supported types</strong>: Link or plain Text containing URL. Link to content: Read more, View Case Study, etc. If an entity, be sure its formatter is linkable strings like ID or Label. <strong>Two behaviors</strong>: <ol><li>If <strong>Media switcher &gt; Image linked by Link field</strong> is available as an option and selected, it will be gone to serve as a wrapping link of the image, only if its formatter/ output is plain text URL.</li><li>As opposed to <strong>Caption fields</strong> if available, it will be positioned and wrapped with a dedicated class: <strong>@class</strong>.</li></ol>', [
        '@class' => $namespace == 'blazy' ? 'blazy__caption--link' : $namespace . '__link',
      ]),
      'loading' => $this->t("Decide the `loading` attribute affected by the above fold aka onscreen critical contents aka <a href=':lcp'>LCP</a>. <ul><li>`lazy`, the default: defers loading below fold or offscreen images and iframes until users scroll near them.</li><li>`auto`: browser determines whether or not to lazily load. Only if uncertain about the above fold boundaries given different devices. </li><li>`eager`: loads right away. Similar effect like without `loading`, included for completeness. Good for above fold.</li><li>`defer`: trigger native lazy after the first row is loaded. Will disable global `No JavaScript: lazy` option on this particular field, <a href=':defer'>read more</a>.</li><li>`unlazy`: explicitly removes loading attribute enforced by core. Also removes old `data-[SRC|SRCSET|LAZY]` if `No JavaScript` is disabled. Best for the above fold.</li><li>`slider`, if applicable: will `unlazy` the first visible, and leave the rest lazyloaded. Best for sliders (one visible at a time), not carousels (multiple visible slides at once).</li></ul><b>Note</b>: lazy loading images/ iframes for the above fold is anti-pattern, avoid, <a href=':more'>read more</a>, even <a href=':webdev'>more</a>.", [
        ':lcp' => 'https://web.dev/lcp/',
        ':more' => 'https://www.drupal.org/node/3262724',
        ':defer' => 'https://drupal.org/node/3120696',
        ':webdev' => 'https://web.dev/browser-level-image-lazy-loading/#avoid-lazy-loading-images-that-are-in-the-first-visible-viewport',
      ]),
      'image_style' => $this->t('The content image style. This will be treated as the fallback image to override the global option <a href=":url">Responsive image 1px placeholder</a>, which is normally smaller, if Responsive image are provided. Shortly, leave it empty to make Responsive image fallback respected. Otherwise this is the only image displayed. This image style is also used to provide dimensions not only for image/iframe but also any media entity like local video, where no images are even associated with, to have the designated dimensions in tandem with aspect ratio as otherwise no UI to customize for.', [':url' => $ui_url]),
      'responsive_image_style' => $this->resimageDescriptions(),
      'media_switch' => $this->t('Clear cache if lightboxes do not appear here due to being permanently cached. <ol><li><b>Link to content/ by Link field</b>: for aggregated small media contents -- slicks, splides, grids, etc.</li><li><b>Image to iframe</b>: video is hidden below image until toggled, otherwise iframe is always displayed, and draggable fails. Aspect ratio applies.</li><li><b>(Quasi-)lightboxes</b>: Colorbox, ElevateZoomPlus, Intense, Splidebox, PhotoSwipe, Magnific Popup, Slick Lightbox, Splidebox, Zooming, etc. Depends on the enabled supported modules, or has known integration with Blazy. See docs or <em>/admin/help/blazy_ui</em> for details.</li>@rendered</ol> @lb', [
        '@rendered' => $scopes->form('fieldable') ? $this->t('<li><b>Image rendered by its formatter</b>: image-related settings here will be ignored: breakpoints, image style, CSS background, aspect ratio, lazyload, etc. Only choose if needing a special image formatter such as Image Link Formatter.</li>') : '',
        '@lb' => $lb ? '' : $this->t('Add <em>Thumbnail style</em> if using Splidebox, Slick, or others which may need it. Try selecting "<strong>- None -</strong>" first before changing if trouble with this complex form states.'),
      ]),
      'box_style' => $this->t('Only relevant for lightboxes under Media switcher. Supports both Responsive and regular images.'),
      'box_media_style' => $this->t('Allows different lightbox video dimensions. Or can be used to have a swipable video if <a href=":photoswipe">Blazy PhotoSwipe</a>, or <a href=":slick">Slick Lightbox</a>, or <a href=":splidebox">Splidebox</a> installed.', [
        ':photoswipe' => 'https://drupal.org/project/blazy_photoswipe',
        ':slick' => 'https://drupal.org/project/slick_lightbox',
        ':splidebox' => 'https://drupal.org/project/splidebox',
      ]),
      'box_caption' => $this->t('Automatic will search for Alt text first, then Title text.'),
      'box_caption_custom' => $this->t('Multi-value rich text field will be mapped to each image by its delta.'),
      'ratio' => $this->t('Aspect ratio to get consistently responsive images and iframes. Coupled with Image style. And to fix layout reflow, excessive height issues, whitespace below images, collapsed container, no-js users, etc. <a href=":dimensions" target="_blank">Image styles and video dimensions</a> must <a href=":follow" target="_blank">follow the aspect ratio</a>. If not, images will be distorted. <a href=":link" target="_blank">Learn more</a>. <ul><li><b>Fixed ratio:</b> all images use the same aspect ratio mobile up. Use it to avoid JS works, or if it fails Responsive image. </li><li><b>Fluid:</b> aka dynamic, dimensions are calculated. First specific for non-responsive images, using PHP for pure CSS if any matching the fixed ones (1:1, 2:3, etc.), <a href=":ratio">read more</a>. If none found, JS works are attempted to fix it.</li><li><b>Leave empty:</b> to DIY (such as using CSS mediaquery), or when working with gapless grids like GridStack, or Blazy Native Grid.</li></ul>', [
        ':dimensions'  => '//size43.com/jqueryVideoTool.html',
        ':follow'      => '//en.wikipedia.org/wiki/Aspect_ratio_%28image%29',
        ':link'        => '//www.smashingmagazine.com/2014/02/27/making-embedded-content-work-in-responsive-design/',
        ':ratio'       => $help . '#aspect-ratio',
      ]),
      'view_mode' => $view_mode,
      'thumbnail_style' => $this->t('Usages: <ol><li>Placeholder replacement for image effects (blur, etc.)</li><li>Splidebox/PhotoSwipe thumbnail</li><li>Custom works with thumbnails.</li></ol> Be sure to have similar aspect ratio for the best blur effect. Leave empty to not use thumbnails.'),
      'image' => $this->t('<strong>Required for</strong>: <ul><li>image attribute translation,</li><li>lightboxes as image triggers,</li><li>(remote|local) video high-res or poster image.</li><li>thumbnail/ slider navigation association, etc.</li></ul>Main background/stage/poster image field with the only supported field types: <b>Image</b> or <b>Media</b> containing an Image field. Add a new Image field to this entity, if not the Image bundle. Reuse the exact same image field (normally <strong>field_media_image</strong>) across various entitiy types (Image, Remote video, Local audio/video, etc.) within this particular entity (says, Media). This exact same field is also used for bundle <b>Image</b> to have a mix of videos and images if this entity is Media. Leaving it empty will fallback to the video provider thumbnails, and may cause issues due to failing requirements above.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function gridDescriptions(): array {
    $scopes = $this->scopes;
    $lb = $this->isAdminLb();
    $description = $this->t('@lbGrid is boxy. Column is stacky in flexbox or CSS column. They mean to be the same thing here on. Unless otherwise specified below, it must be a number denoting the amount of columns (1 - 12, or empty).', [
      '@lb' => $lb ? '' : 'Empty the value first if trouble with changing form states. ',
    ]);
    if ($scopes->is('slider')) {
      $description .= $this->t('<br /><strong>Requires</strong>:<ol><li>Any grid-related Display style,</li><li>Visible items,</li><li>Skin Grid for starter,</li><li>A reasonable amount of contents.</li></ol>');
    }
    return [
      'grid' => $description,
      'grid_medium' => $this->t('Only accepts uniform columns (1 - 12, or empty) for medium devices 40.063em - 64em (641px - 1024px) up. Since 3.0.7, specific for native grid (two-dimensional) and Flexbox (non-masonry), it supports values like Grid large aka multi-breakpoint grids, updated via JS. Be sure the amount of WIDTH-HEIGHT pairs matches Grid large.'),
      'grid_small' => $this->t('Only accepts uniform columns (1 - 2, or empty) for small devices 0 - 40em (640px) up due to small real estate. Below this value, always one column.'),
      'visible_items' => $this->t('How many items per display at a time.'),
      'preserve_keys' => $this->t('If checked, keys will be preserved. Default is FALSE which will reindex the grid chunk numerically.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function gridHeaderDescription() {
    return $this->t('Depends on the <strong>Display style</strong>.');
  }

  /**
   * {@inheritdoc}
   */
  public function openingDescriptions(): array {
    $lb = $this->isAdminLb();
    return [
      // @todo remove after sub-modules.
      'background' => $this->background(),
      'by_delta' => $this->t('Display a single item by delta, starting from 0. Leave it -1 to display all. Useful to display a multi-value field when broken down into a single display like Layout Builder blocks so that one field can occupy multiple regions simply by using its delta. More efficient than creating different single fields for the same image or media. Almost similar to Views <strong>Display all values in the same row (DAVISR)</strong>, except only designated to display a single value beyond Views UI. If embedded inside Views, this option is not available for more robust Views DAVISR. Be sure to disable Display style and grid options since it will show one item only.'),
      'caption' => $this->t('Enable any of the following fields as captions. These fields are treated and wrapped as captions.'),
      'layout' => $this->t('Requires a skin. The builtin layouts affects the entire items uniformly. Leave empty to DIY.'),
      'skin' => $this->t('Skins allow various layouts with just CSS. Some options below depend on a skin. Leave empty to DIY. Or use the provided hook_info() and implement the skin interface to register ones.'),
      'style' => $this->t('Unless otherwise specified, it requires <strong>Grid</strong>:<ul><li><strong>Columns</strong> is best with irregular image sizes (scale width, empty height), affects the natural order of grid items, top-bottom, not left-right, free height.</li><li><strong>Foundation</strong> with regular cropped ones, left-right, fixed height.</li><li><strong>Flexbox</strong> with limited non-repeatable non-gapless 3-4 columns, left-right flow, free or configurable [min-]height@lb.</li> <li><strong>Flex Masonry</strong> (@deprecated due to an epic failure) uses Flexbox, supports (ir)-regular, left-right flow, requires aspect ratio fluid to layout correctly, free height.</li><li><strong>Native Grid</strong> supports both one and two dimensional grids, left-right, free height for masonry, or fixed height for boxy grid.</li></ul> Unless required, leave empty to use default formatter, or style. Save for <b>Grid Foundation</b>, the rest are experimental!', [
        '@lb' => $lb ? '' : $this->t(', see Blazy Layout sub-module for Layout Builder'),
      ]),
    ];
  }

  /**
   * Returns formatter base descriptions.
   */
  protected function resimageDescriptions(): string {
    $scopes = $this->scopes;
    if (!$scopes->is('responsive_image')) {
      return '';
    }
    $url = Url::fromRoute('entity.responsive_image_style.collection')->toString();
    $description = $this->t('Responsive image style for the main stage image is more reasonable for large images. Works with multi-serving IMG, or PICTURE element. Leave empty to disable. <a href=":url" target="_blank">Manage responsive image styles</a>.', [
      ':url' => $url,
    ]);
    if ($this->blazyManager->moduleExists('blazy_ui')) {
      $description .= ' ' . $this->t('<a href=":url2">Enable lazyloading Responsive image</a>.', [
        ':url2' => Url::fromRoute('blazy.settings')->toString(),
      ]);
    }
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function svgDescriptions(): array {
    $sanitizer = 'https://github.com/darylldoyle/svg-sanitizer';
    return [
      'inline' => $this->t('If checked, SVG is not embedded in the IMG tag. Be sure to disable CSS background option. Only enable for CSS and JavaScript manipulations, and trusted users, due to <a href=":url1">inline SVG security</a>. Required <a href=":url2">SVG Sanitizer</a>.', [
        ':url1' => 'https://www.w3.org/wiki/SVG_Security',
        ':url2' => $sanitizer,
      ]),
      'sanitize' => $this->t('Sanitize the SVG XML code to prevent XSS attacks. Required <a href=":url">SVG Sanitizer</a>.', [
        ':url' => $sanitizer,
      ]),
      'sanitize_remote' => $this->t('Remove attributes that reference remote files, this will stop HTTP leaks but will add an overhead to the sanitizer.'),
      'fill' => $this->t('Force the fill to currentColor to allow the SVG inherit coloring from the enclosing tag, such as a link tag.'),
      'hide_caption' => $this->t('Unlike images, SVG has no ALT and TITLE attributes, except for SVG Image Field, or core file Description field. This option will hide captions, and put them into image attributes instead. Relevant if Inline option is disabled aka using IMG tag. Be sure to enable them under the Caption fields.'),
      'attributes' => $this->t('Input one of SVG dimension sources: <code>none, image_style, or WIDTHxHEIGHT</code>. To disable, input: <strong>none</strong>, and will also disable Aspect ratio option. The <strong>image_style</strong> ansich will use the provided Image style, useful to get consistent heights within carousels, or rigid grids. The <strong>WIDTHxHEIGHT</strong>, e.g.: 800x400, for custom defined dimensions. Default or fallback to extract from SVG attributes, unless <strong>none</strong> is set. Only width and height are supported. Affected by Aspect ratio option.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function closingDescriptions(): array {
    $lb = $this->isAdminLb();

    return [
      'use_theme_field' => $this->t('Wrap Blazy field output into regular field markup (field.html.twig). Vanilla output otherwise. @lb', [
        '@lb' => $lb ? $this->t('If enabled, it may break CSS background due to extra divities. Backgrounds require very minimal divities.') : '',
      ]),
    ];
  }

  /**
   * Returns background description, due to dups till sub-module updates.
   */
  private function background(): string {
    $lb = $this->isAdminLb();
    return $this->t('Check this to turn the image into CSS background. This opens up the goodness of CSS, such as background cover, fixed attachment, etc. <br /><strong>Important!</strong> Requires an Aspect ratio, otherwise collapsed containers. Unless explicitly removed such as for GridStack which manages its own problem, or a min-height is added using grid min-height (see Blazy layout sub-module Grid option), or manually to <strong>.b-bg</strong> selector. @lb', [
      '@lb' => $lb ? $this->t('<br><strong>Note!</strong> Must disable <strong>Use field template</strong> (if provided, default to FALSE) for background to work.') : '',
    ]);
  }

}
