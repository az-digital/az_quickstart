<?php

namespace Drupal\slick\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\blazy\Form\BlazyAdminInterface;
use Drupal\slick\SlickManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides resusable admin functions, or form elements.
 */
class SlickAdmin implements SlickAdminInterface {

  use StringTranslationTrait;

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   */
  protected $blazyAdmin;

  /**
   * The slick manager service.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * Constructs a SlickAdmin object.
   *
   * @param \Drupal\blazy\Form\BlazyAdminInterface $blazy_admin
   *   The blazy admin service.
   * @param \Drupal\slick\SlickManagerInterface $manager
   *   The slick manager service.
   */
  public function __construct(
    BlazyAdminInterface $blazy_admin,
    SlickManagerInterface $manager,
  ) {
    $this->blazyAdmin = $blazy_admin;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('blazy.admin.formatter'),
      $container->get('slick.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blazyAdmin() {
    return $this->blazyAdmin;
  }

  /**
   * {@inheritdoc}
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Modifies the main form elements.
   */
  public function buildSettingsForm(array &$form, array $definition): void {
    $definition['caches']           = $definition['caches'] ?? TRUE;
    $definition['namespace']        = 'slick';
    $definition['optionsets']       = $definition['optionsets'] ?? $this->getOptionsetsByGroupOptions('main');
    $definition['skins']            = $definition['skins'] ?? $this->getSkinsByGroupOptions('main');
    $definition['responsive_image'] = $definition['responsive_image'] ?? TRUE;
    $definition['grid_required']    = FALSE;
    $definition['no_grid_header']   = FALSE;
    $definition['slider']           = TRUE;
    $definition['grid_header_desc'] = $this->gridHeaderDescription();

    $effects = $definition['_thumbnail_effect'] ?? [];
    $defaults = [
      'hover' => $this->t('Hoverable'),
      'grid'  => $this->t('Static grid'),
    ];
    $definition['thumbnail_effect'] = $effects = array_merge($defaults, $effects);

    foreach (['optionsets', 'skins'] as $key) {
      if (isset($definition[$key]['default'])) {
        ksort($definition[$key]);
        $definition[$key] = ['default' => $definition[$key]['default']] + $definition[$key];
      }
    }

    // @todo remove post blazy:2.17.
    if (!empty($definition['thumb_captions'])) {
      if ($definition['thumb_captions'] == 'default') {
        $definition['thumb_captions'] = [
          'alt' => $this->t('Alt'),
          'title' => $this->t('Title'),
        ];
      }
    }

    if (empty($definition['no_layouts'])) {
      $definition['layouts'] = isset($definition['layouts']) ? array_merge($this->getLayoutOptions(), $definition['layouts'] ?: []) : $this->getLayoutOptions();
    }

    $this->openingForm($form, $definition);

    if (!empty($definition['image_style_form']) && !isset($form['image_style'])) {
      $this->imageStyleForm($form, $definition);
    }

    if (!empty($definition['media_switch_form']) && !isset($form['media_switch'])) {
      $this->mediaSwitchForm($form, $definition);
    }

    if (!empty($definition['grid_form']) && !isset($form['grid'])) {
      $this->gridForm($form, $definition);
    }

    if (!empty($definition['fieldable_form']) && !isset($form['image'])) {
      $this->fieldableForm($form, $definition);
    }

    if (!empty($definition['style']) && isset($form['style']['#description'])) {
      $form['style']['#description'] .= ' ' . $this->t('CSS3 Columns is best with adaptiveHeight, non-vertical. Will use regular carousel as default style if left empty. Yet, both CSS3 Columns and Grid Foundation are respected as Grid displays when <strong>Grid large</strong> option is provided.');
    }

    $this->closingForm($form, $definition);
  }

  /**
   * Modifies the opening form elements.
   */
  public function openingForm(array &$form, array &$definition): void {
    $path         = $this->manager->getPath('module', 'slick');
    $is_slick_ui  = $this->manager->moduleExists('slick_ui');
    $is_help      = $this->manager->moduleExists('help');
    $route_name   = ['name' => 'slick_ui'];
    $readme       = $is_slick_ui && $is_help ? Url::fromRoute('help.page', $route_name)->toString() : Url::fromUri('base:' . $path . '/docs/README.md')->toString();
    $readme_field = $is_slick_ui && $is_help ? Url::fromRoute('help.page', $route_name)->toString() : Url::fromUri('base:' . $path . '/docs/FORMATTER.md')->toString();
    $arrows       = $this->getSkinsByGroupOptions('arrows');
    $dots         = $this->getSkinsByGroupOptions('dots');

    $this->blazyAdmin->openingForm($form, $definition);

    if (isset($form['optionset'])) {
      $form['optionset']['#title'] = $this->t('Optionset main');

      if ($is_slick_ui) {
        $route_name = 'entity.slick.collection';
        $form['optionset']['#description'] = $this->t('Manage optionsets at <a href=":url" target="_blank">the optionset admin page</a>.', [':url' => Url::fromRoute($route_name)->toString()]);
      }
    }

    if (!empty($definition['nav']) || !empty($definition['thumbnails'])) {
      $form['optionset_thumbnail'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Optionset thumbnail'),
        '#options'     => $this->getOptionsetsByGroupOptions('thumbnail'),
        '#description' => $this->t('If provided, asNavFor aka thumbnail navigation applies. Leave empty to not use thumbnail navigation.'),
        '#weight'      => -108,
      ];

      $form['skin_thumbnail'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Skin thumbnail'),
        '#options'     => $this->getSkinsByGroupOptions('thumbnail'),
        '#description' => $this->t('Thumbnail navigation skin. See main <a href="@url" target="_blank">README</a> for details on Skins. Leave empty to not use thumbnail navigation.', ['@url' => $readme]),
        '#weight'      => -106,
      ];
    }

    if (count($arrows) > 0 && empty($definition['no_arrows'])) {
      $form['skin_arrows'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Skin arrows'),
        '#options'     => $arrows,
        '#enforced'    => TRUE,
        '#description' => $this->t('Check out slick.api.php to add your own skins.'),
        '#weight'      => -105,
      ];
    }

    if (count($dots) > 0 && empty($definition['no_dots'])) {
      $form['skin_dots'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Skin dots'),
        '#options'     => $dots,
        '#enforced'    => TRUE,
        '#description' => $this->t('Check out slick.api.php to add your own skins.'),
        '#weight'      => -105,
      ];
    }

    if (!empty($definition['thumb_positions'])) {
      $form['thumbnail_position'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Thumbnail position'),
        '#options' => [
          'left'       => $this->t('Left'),
          'right'      => $this->t('Right'),
          'top'        => $this->t('Top'),
          'over-left'  => $this->t('Overlay left'),
          'over-right' => $this->t('Overlay right'),
          'over-top'   => $this->t('Overlay top'),
        ],
        '#description' => $this->t('By default thumbnail is positioned at bottom. Hence to change the position of thumbnail. Only reasonable with 1 visible main stage at a time. Except any TOP, the rest requires Vertical option enabled for Optionset thumbnail, and a custom CSS height to selector <strong>.slick--thumbnail</strong> to avoid overflowing tall thumbnails, or adjust <strong>slidesToShow</strong> to fit the height. Further theming is required as usual. Overlay is absolutely positioned over the stage rather than sharing the space. See skin <strong>X VTabs</strong> for vertical thumbnail sample.'),
        '#states' => [
          'visible' => [
            'select[name*="[optionset_thumbnail]"]' => ['!value' => ''],
          ],
        ],
        '#weight'      => -99,
      ];
    }

    if ($captions = $definition['thumb_captions'] ?? []) {
      $captions += ['title' => $this->t('Image Title')];
      $form['thumbnail_caption'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Thumbnail caption'),
        '#options'     => $captions,
        '#description' => $this->t('Thumbnail caption maybe just title/ plain text. If Thumbnail image style is not provided, the thumbnail pagers will be just text like regular tabs.'),
        '#states' => [
          'visible' => [
            'select[name*="[optionset_thumbnail]"]' => ['!value' => ''],
          ],
        ],
        '#weight'      => 2,
      ];
    }

    if (isset($form['skin'])) {
      $form['skin']['#title'] = $this->t('Skin main');
      $form['skin']['#description'] = $this->t('Skins allow various layouts with just CSS. Some options below depend on a skin. However a combination of skins and options may lead to unpredictable layouts, get yourself dirty. E.g.: Skin Split requires any split layout option. Failing to choose the expected layout makes it useless. See <a href=":url" target="_blank">SKINS section at README</a> for details on Skins. Leave empty to DIY. Skins are permanently cached. Clear cache if new skins do not appear. Check out slick.api.php to add your own skins.', [':url' => $readme]);
    }

    if (isset($form['layout'])) {
      $form['layout']['#description'] = $this->t('Requires a skin. The builtin layouts affects the entire slides uniformly. Split half requires any skin Split. See <a href="@url" target="_blank">README</a> under "Slide layout" for more info. Leave empty to DIY.', ['@url' => $readme_field]);
    }

    $weight = -99;
    foreach (Element::children($form) as $key) {
      if (!isset($form[$key]['#weight'])) {
        $form[$key]['#weight'] = ++$weight;
      }
    }
  }

  /**
   * Modifies the image formatter form elements.
   */
  public function mediaSwitchForm(array &$form, array $definition): void {
    $this->blazyAdmin->mediaSwitchForm($form, $definition);
  }

  /**
   * Modifies the image formatter form elements.
   */
  public function imageStyleForm(array &$form, array $definition): void {
    $definition['thumbnail_style'] = $definition['thumbnail_style'] ?? TRUE;
    $definition['ratios'] = $definition['ratios'] ?? TRUE;

    if (!isset($form['image_style'])) {
      $this->blazyAdmin->imageStyleForm($form, $definition);
    }

    if (isset($form['thumbnail_style'])) {
      $form['thumbnail_style']['#description'] .= '<br><br>' . $this->t('Extra usages: <ol><li>If <em>Optionset thumbnail</em> provided, it is for asNavFor thumbnail navigation.</li><li>For <em>Thumbnail effect</em>.</li><li>Arrows with thumbnails, etc.</li></ol>.');
    }

    if (isset($form['background'])) {
      $form['background']['#description'] .= ' ' . $this->t('Works best with a single visible slide, skins full width/screen.');
    }
  }

  /**
   * Modifies re-usable fieldable formatter form elements.
   */
  public function fieldableForm(array &$form, array $definition): void {
    $this->blazyAdmin->fieldableForm($form, $definition);

    if (isset($form['thumbnail'])) {
      $form['thumbnail']['#description'] = $this->t("Needed if any are required/ provided: <ol><li><em>Optionset thumbnail</em>.</li><li><em>Dots thumbnail effect</em>.</li></ol> Maybe the same field as the main image, only different instance and image style. Company logos for thumbnails vs. company offices for the Main stage, author avatars for thumbnails vs. Slideshow for overlays with its Main stage, etc. Leave empty to not use thumbnail pager, or for tabs-like/ text only navigation.");
    }

    if (isset($form['overlay'])) {
      $form['overlay']['#title'] = $this->t('Overlay media/slicks');
      $form['overlay']['#description'] = $this->t('For audio/video, be sure the display is not image. For nested slicks, use the Slick carousel formatter for this field. Zebra layout is reasonable for overlay and captions.');
    }
  }

  /**
   * Modifies re-usable grid elements across Slick field formatter and Views.
   */
  public function gridForm(array &$form, array $definition): void {
    if (!isset($form['grid'])) {
      $this->blazyAdmin->gridForm($form, $definition);
    }

    $form['grid']['#description'] = $this->t('The amount of block grid columns for large monitors 64.063em - 90em. <br /><strong>Requires</strong>:<ol><li>Visible items,</li><li>Skin Grid for starter,</li><li>A reasonable amount of contents,</li><li>Optionset with Rows and slidesPerRow = 1.</li></ol>This is module feature, older than core Rows, and offers more flexibility. Leave empty to DIY, or to not build grids.');
  }

  /**
   * Returns grid header description.
   */
  protected function gridHeaderDescription() {
    return $this->t('An older alternative to core <strong>Rows</strong> option. Only works if the total items &gt; <strong>Visible slides</strong>. <br />block grid != slidesToShow option, yet both can work in tandem.<br />block grid = Rows option, yet the first is module feature, the later core.');
  }

  /**
   * Modifies the closing ending form elements.
   */
  public function closingForm(array &$form, array $definition): void {
    if (empty($definition['_views']) && !empty($definition['field_name'])) {
      $form['use_theme_field'] = [
        '#title'       => $this->t('Use field template'),
        '#type'        => 'checkbox',
        '#description' => $this->t('Wrap Slick field output into regular field markup (field.html.twig). Vanilla output otherwise.'),
        '#weight'      => -106,
      ];
    }

    $form['override'] = [
      '#title'       => $this->t('Override main optionset'),
      '#type'        => 'checkbox',
      '#description' => $this->t('If checked, the following options will override the main optionset. Useful to re-use one optionset for several different displays.'),
      '#weight'      => 112,
      '#enforced'    => TRUE,
    ];

    $form['overridables'] = [
      '#type'        => 'checkboxes',
      '#title'       => $this->t('Overridable options'),
      '#description' => $this->t("Override the main optionset to re-use one. Anything dictated here will override the current main optionset. Unchecked means FALSE"),
      '#options'     => $this->getOverridableOptions(),
      '#weight'      => 113,
      '#enforced'    => TRUE,
      '#states' => [
        'visible' => [
          ':input[name$="[override]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Bring in dots thumbnail effect normally used by Slick Image formatter.
    if (empty($definition['no_thumb_effects'])
      && $effects = $definition['thumbnail_effect'] ?? []) {
      $form['thumbnail_effect'] = [
        '#type'         => 'select',
        '#title'        => $this->t('Dots thumbnail effect'),
        '#options'      => $effects,
        '#empty_option' => $this->t('- None -'),
        '#description'  => $this->t('Dependent on a Skin, Dots and Thumbnail image options. No asnavfor/ Optionset thumbnail is needed. <ol><li><strong>Hoverable</strong>: Dots pager are kept, and thumbnail will be hidden and only visible on dot mouseover, default to min-width 120px.</li><li><strong>Static grid</strong>: Dots are hidden, and thumbnails are displayed as a static grid acting like dots pager.</li></ol>Alternative to asNavFor aka separate thumbnails as slider.'),
        '#weight'       => -100,
      ];
    }

    $this->blazyAdmin->closingForm($form, $definition);
  }

  /**
   * Returns overridable options to re-use one optionset.
   */
  public function getOverridableOptions(): array {
    $options = [
      'arrows'        => $this->t('Arrows'),
      'autoplay'      => $this->t('Autoplay'),
      'dots'          => $this->t('Dots'),
      'draggable'     => $this->t('Draggable'),
      'infinite'      => $this->t('Infinite'),
      'mouseWheel'    => $this->t('Mousewheel'),
      'randomize'     => $this->t('Randomize'),
      'variableWidth' => $this->t('Variable width'),
    ];

    $this->manager->moduleHandler()->alter('slick_overridable_options_info', $options);
    return $options;
  }

  /**
   * Returns default layout options for the core Image, or Views.
   */
  public function getLayoutOptions(): array {
    return [
      'bottom'      => $this->t('Caption bottom'),
      'top'         => $this->t('Caption top'),
      'right'       => $this->t('Caption right'),
      'left'        => $this->t('Caption left'),
      'center'      => $this->t('Caption center'),
      'center-top'  => $this->t('Caption center top'),
      'below'       => $this->t('Caption below the slide'),
      'stage-right' => $this->t('Caption left, stage right'),
      'stage-left'  => $this->t('Caption right, stage left'),
      'split-right' => $this->t('Caption left, stage right, split half'),
      'split-left'  => $this->t('Caption right, stage left, split half'),
      'stage-zebra' => $this->t('Stage zebra'),
      'split-zebra' => $this->t('Split half zebra'),
    ];
  }

  /**
   * Returns available slick optionsets by group.
   */
  public function getOptionsetsByGroupOptions($group = ''): array {
    $optionsets = $groups = $ungroups = [];
    $slicks = $this->manager->loadMultiple('slick');
    foreach ($slicks as $slick) {
      $name = Html::escape($slick->label());
      $id = $slick->id();
      $current_group = $slick->getGroup();
      if (!empty($group)) {
        if ($current_group) {
          if ($current_group != $group) {
            continue;
          }
          $groups[$id] = $name;
        }
        else {
          $ungroups[$id] = $name;
        }
      }
      $optionsets[$id] = $name;
    }

    return $group ? array_merge($ungroups, $groups) : $optionsets;
  }

  /**
   * Returns available slick skins for select options.
   */
  public function getSkinsByGroupOptions($group = ''): array {
    return $this->manager->skinManager()->getSkinsByGroup($group, TRUE);
  }

  /**
   * Return the field formatter settings summary.
   */
  public function getSettingsSummary(array $definition = []): array {
    return $this->blazyAdmin->getSettingsSummary($definition);
  }

  /**
   * Returns available fields for select options.
   */
  public function getFieldOptions(
    array $target_bundles = [],
    array $allowed_field_types = [],
    $entity_type = 'media',
    $target_type = '',
  ): array {
    return $this->blazyAdmin->getFieldOptions($target_bundles, $allowed_field_types, $entity_type, $target_type);
  }

  /**
   * Modifies re-usable logic, styling and assets across fields and Views.
   */
  public function finalizeForm(array &$form, array $definition): void {
    $this->blazyAdmin->finalizeForm($form, $definition);
  }

}
