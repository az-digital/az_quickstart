<?php

declare(strict_types=1);

namespace Drupal\az_zoom\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'az_zoom_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "az_zoom_field_formatter",
 *   label = @Translation("Image Zoom"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class AzZoomFieldFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The image style storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The responsive image style storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $responsiveImageStyleStorage;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs an AzZoomFieldFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param object $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   Image style storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   *   Responsive image style storage.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    $current_user,
    EntityStorageInterface $image_style_storage,
    EntityStorageInterface $responsive_image_style_storage,
    FileUrlGeneratorInterface $file_url_generator,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
    $this->responsiveImageStyleStorage = $responsive_image_style_storage;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('entity_type.manager')->getStorage('responsive_image_style'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_zoom_factor' => '250%',
      'image_smoother' => TRUE,
      'image_width' => '100%',
      'image_height' => '66.7%',
      'display_loc' => FALSE,
      'display_zoom' => FALSE,
      'zoom_on_scroll' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * Settings form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array.
   *
   * @return mixed
   *   Returns mixed data.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    $element['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];

    $element['zoomed_image_style'] = [
      '#title' => $this->t('Zoomed Image Style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('zoomed_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];

    $responsive_image_options = [];
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
    uasort($responsive_image_styles, '\Drupal\responsive_image\Entity\ResponsiveImageStyle::sort');
    if (!empty($responsive_image_styles)) {
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    $element['responsive_image_style'] = [
      '#title' => $this->t('Responsive image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('responsive_image_style') ?: NULL,
      '#options' => $responsive_image_options,
      '#description' => [
        '#access' => $this->currentUser->hasPermission('administer responsive image styles'),
      ],
    ];
    $element = parent::settingsForm($form, $form_state);

    $element['image_zoom_factor'] = [
      '#title' => $this->t('Image Zoom Factor'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('image_zoom_factor'),
      '#description' => $this->t('Define the zoom factor, e.g., 250%.'),
    ];

    $element['image_smoother'] = [
      '#title' => $this->t('Smoother Zoom'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('image_smoother'),
      '#description' => $this->t('Enable to make the zoom effect smoother.'),
    ];

    $element['display_loc'] = [
      '#title' => $this->t('Display Location HUD'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_loc'),
      '#description' => $this->t('Enable to display location heads-up display.'),
    ];

    $element['display_zoom'] = [
      '#title' => $this->t('Display Zoom HUD'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_zoom'),
      '#description' => $this->t('Enable to display zoom level heads-up display.'),
    ];

    $element['zoom_on_scroll'] = [
      '#title' => $this->t('Zoom on Scroll'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('zoom_on_scroll'),
      '#description' => $this->t('Allow zooming with mouse scroll.'),
    ];
    $element['image_zoom_factor'] = [
      '#title' => $this->t('Image Zoom Factor'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('image_zoom_factor'),
      '#description' => $this->t('Define the zoom factor, e.g., 250%.'),
    ];

    $element['image_smoother'] = [
      '#title' => $this->t('Smoother Zoom'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('image_smoother'),
      '#description' => $this->t('Enable to make the zoom effect smoother.'),
    ];

    $element['display_loc'] = [
      '#title' => $this->t('Display Location HUD'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_loc'),
      '#description' => $this->t('Enable to display location heads-up display.'),
    ];

    $element['display_zoom'] = [
      '#title' => $this->t('Display Zoom HUD'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_zoom'),
      '#description' => $this->t('Enable to display zoom level heads-up display.'),
    ];

    $element['zoom_on_scroll'] = [
      '#title' => $this->t('Zoom on Scroll'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('zoom_on_scroll'),
      '#description' => $this->t('Allow zooming with mouse scroll.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    unset($image_styles['']);
    $image_style_setting = $this->getSetting('image_style');
    $zoomed_image_style_setting = $this->getSetting('zoomed_image_style');

    // Styles could be lost because of enabled/disabled modules that define
    // their styles in code.
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = $this->t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = $this->t('Original image');
    }
    if (isset($image_styles[$zoomed_image_style_setting])) {
      $summary[] = $this->t('Zoomed image style: @style', ['@style' => $image_styles[$zoomed_image_style_setting]]);
    }
    else {
      $summary[] = $this->t('Zoomed: Original image');
    }

    // Responsive image style.
    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    if ($responsive_image_style) {
      $summary[] = $this->t('Responsive image style: @responsive_image_style', ['@responsive_image_style' => $responsive_image_style->label()]);
    }

    $summary[] = $this->t('Zoom style: @value', ['@value' => $this->getSetting('image_zoom_style')]);
    $summary[] = $this->t('Touchscreen Compatible: @value', ['@value' => $this->getSetting('image_touchscreen_compatible')]);
    $summary[] = $this->t('Image Magnify: @value', ['@value' => $this->getSetting('image_magnify')]);
    $summary[] = $this->t('Fade Duration: @value', ['@value' => $this->getSetting('image_fade_duration')]);

    if ($this->getSetting('image_zoom_factor')) {
      $summary[] = $this->t('Image Zoom Factor: @factor', ['@factor' => $this->getSetting('image_zoom_factor')]);
    }

    $summary[] = $this->t('Smoother Zoom: @smoother', ['@smoother' => $this->getSetting('image_smoother') ? $this->t('Yes') : $this->t('No')]);

    if ($this->getSetting('display_loc')) {
      $summary[] = $this->t('Display Location HUD: @display', ['@display' => $this->getSetting('display_loc') ? $this->t('Yes') : $this->t('No')]);
    }

    if ($this->getSetting('display_zoom')) {
      $summary[] = $this->t('Display Zoom HUD: @display', ['@display' => $this->getSetting('display_zoom') ? $this->t('Yes') : $this->t('No')]);
    }

    $summary[] = $this->t('Zoom on Scroll: @zoomOnScroll', ['@zoomOnScroll' => $this->getSetting('zoom_on_scroll') ? $this->t('Yes') : $this->t('No')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $field_name = $items->getFieldDefinition()->getName();
    $elements = [];

    $files = $this->getEntitiesToView($items, $langcode);
    $images = [];
    $original_urls = [];
    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }
    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    // Get cache tags for image style.
    $image_style_setting = $this->getSetting('image_style');
    if (!empty($image_style_setting) && empty($this->getSetting('responsive_image_style'))) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }
    $zoomed_image_style_setting = $this->getSetting('zoomed_image_style');
    if (!empty($zoomed_image_style_setting)) {
      $zoomed_image_style = $this->imageStyleStorage->load($zoomed_image_style_setting);
      $cache_tags = Cache::mergeTags($cache_tags, $zoomed_image_style->getCacheTags());
    }
    // Get cache tags for responsive image style.
    $responsive_image_style_setting = $this->getSetting('responsive_image_style');
    $responsive_image_style = NULL;
    if (!empty($responsive_image_style_setting)) {
      $responsive_image_style = $this->responsiveImageStyleStorage->load($responsive_image_style_setting);
      if ($responsive_image_style) {
        $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style->getCacheTags());
        $image_styles_to_load = $responsive_image_style->getImageStyleIds();
        $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);
        foreach ($image_styles as $image_style) {
          $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
        }
      }
    }
    // Loop over files.
    foreach ($files as $delta => $file) {
      $cache_contexts = [];
      // Handle responsive image formatting.
      if ($responsive_image_style) {
        $image_uri = $file->getFileUri();
        $image_uri = $this->fileUrlGenerator->generateAbsoluteString($image_uri);

        // Extract field item attributes for the theme function, and unset them
        // from the $item so that the field template does not re-render them.
        $image_uri = $file->getFileUri();
        $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

        // Extract field item attributes for the theme function, and unset them
        // from the $item so that the field template does not re-render them.
        $item = $file->_referringItem;
        if (!empty($item->_attributes)) {
          $item_attributes = $item->_attributes;
        }
        $image_target_id = $item->getValue()['target_id'];

        $image_uri = $this->fileUrlGenerator->generateAbsoluteString($image_uri);
        // Use style image as origin if set.
        if (isset($zoomed_image_style)) {
          $image_uri = $zoomed_image_style->buildUrl($file->uri->value);
        }
        $image_uri = parse_url($image_uri);
        $original_urls[$image_target_id] = $image_uri['path'];
        // Add image style parameters.
        if (isset($image_uri['query'])) {
          $original_urls[$image_target_id] .= '?' . $image_uri['query'];
        }
        // Adding custom attributes to the img.
        $item_attributes['class'][] = 'original-image';
        $item_attributes['fid'] = $image_target_id;
        unset($item->_attributes);

        $images[$delta] = [
          '#theme' => 'responsive_image_formatter',
          '#item' => $item,
          '#item_attributes' => $item_attributes,
          '#responsive_image_style_id' => $responsive_image_style->id(),
          '#prefix' => '<span class="image-zoom">',
          '#suffix' => '</span>',
          '#cache' => [
            'tags' => $cache_tags,
          ],
        ];

      }
      else {
        // Handle image formatting.
        $cache_contexts[] = 'url.site';
        $image_uri = $file->getFileUri();
        $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

        // Extract field item attributes for the theme function, and unset them
        // from the $item so that the field template does not re-render them.
        $item = $file->_referringItem;
        if (!empty($item->_attributes)) {
          $item_attributes = $item->_attributes;
        }
        $image_target_id = $item->getValue()['target_id'];

        $image_uri = $this->fileUrlGenerator->generateAbsoluteString($image_uri);
        // Use style image as origin if set.
        if (isset($zoomed_image_style)) {
          $image_uri = $zoomed_image_style->buildUrl($file->uri->value);
        }
        $image_uri = parse_url($image_uri);
        $original_urls[$image_target_id] = $image_uri['path'];
        // Add image style parameters.
        if (isset($image_uri['query'])) {
          $original_urls[$image_target_id] .= '?' . $image_uri['query'];
        }

        // Adding custom attributes to the img.
        $item_attributes['class'][] = 'original-image';
        $item_attributes['fid'] = $image_target_id;
        unset($item->_attributes);

        $images[$delta] = [
          '#theme' => 'image_formatter',
          '#item' => $item,
          '#item_attributes' => $item_attributes,
          '#image_style' => $image_style_setting,
          '#prefix' => '<span class="image-zoom">',
          '#suffix' => '</span>',
          '#cache' => [
            'tags' => $cache_tags,
            'contexts' => $cache_contexts,
          ],
        ];
      }
    }
    $new_zoom = [
      '#theme' => 'az_zoom',
      '#images' => $images,
      'field_name' => [$field_name],
      '#attached' => [
        'drupalSettings' => [
          'AZZoom' => [
            'image_zoom_style' => $this->getSetting('image_zoom_style'),
            'image_touchscreen_compatible' => $this->getSetting('image_touchscreen_compatible'),
            'image_magnify' => $this->getSetting('image_magnify'),
            'image_fade_duration' => $this->getSetting('image_fade_duration'),
            'image_urls' => $original_urls,
          ],
        ],
      ],
    ];

    return $new_zoom;
  }

}
