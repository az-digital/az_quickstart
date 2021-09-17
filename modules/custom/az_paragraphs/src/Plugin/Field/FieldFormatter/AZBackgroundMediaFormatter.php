<?php

namespace Drupal\az_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Field\FormatterBase;
use Drupal\media\MediaInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Component\Utility\Xss;

/**
 * Plugin implementation of the 'az_background_media_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "az_background_media_formatter",
 *   label = @Translation("Background Media"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AZBackgroundMediaFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * A style element render array for the background image.
   */
  protected $renderableStyleElement;

  /**
   * A style element render array for the background image.
   */
  protected $preprocessedBackgroundImage;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
      ContainerInterface $container,
      array $configuration,
      $plugin_id,
      $plugin_definition
    ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCssSelector($item) {
    $parent = $item->getEntity();
    $css_selector = '';
      // Get entity keys from parent paragraph.
      if (!empty($parent)) {
        if ($parent instanceof ParagraphInterface) {
          $referencing_paragraph_id = $parent->id();
          $referencing_paragraph_bundle = $parent->getType();
          $css_selector = "#" . $referencing_paragraph_bundle . "-" . $referencing_paragraph_id;
        }
    }

    return $css_selector;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'css_settings' => [
        'bg_image_selector' => 'body',
        'bg_image_color' => '#FFFFFF',
        'bg_image_x' => 'left',
        'bg_image_y' => 'top',
        'bg_image_attachment' => 'scroll',
        'bg_image_repeat' => 'no-repeat',
        'bg_image_background_size' => '',
        'bg_image_background_size_ie8' => 0,
        'bg_image_gradient' => '',
        'bg_image_media_query' => 'all',
        'bg_image_important' => 1,
        'bg_image_z_index' => 'auto',
        'bg_image_path_format' => 'absolute',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $responsive_image_style_options = $this->getResponsiveImageStyles(TRUE);
    $form['image_style'] = [
      '#title' => $this->t('Responsive image style.'),
      '#description' => $this->t(
        'Select <a href="@href_image_style">the responsive image style</a> to use.',
        [
          '@href_image_style' => Url::fromRoute('entity.responsive_image_style.collection')->toString(),
        ]
      ),
      '#type' => 'select',
      '#options' => $responsive_image_style_options,
      '#default_value' => $this->getSetting('image_style'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $options = $this->getResponsiveImageStyles();

    if (isset($options[$settings['image_style']])) {
      $summary[] = $this->t('URL for image style: @style', ['@style' => $options[$settings['image_style']]]);
    } else {
      $summary[] = $this->t('Original image style');
    }

    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    // $settings = $this->getSettings();
    // dpm($settings);
    $settings = $this->getAllSettings($items);
    dpm($settings);

    $elements = [];
    // $defaults = self::defaultSettings();

    $media_items = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($media_items)) {
      return $elements;
    }

    /** @var \Drupal\media\MediaInterface[] $media_items */
    foreach ($media_items as $delta => $media) {

      $uri = $this->getMediaURI($media);

      // Add cacheability of each item in the field.
      // $this->renderer->addCacheableDependency($elements[$delta], $media);
      $preprocessed_background_image = [
        'uri' => $uri,
        'responsive_image_style_id' => $settings['image_style'],
      ];

      $this->renderableStyleElement = $this->getResponsiveBackgroundImageStyleElement($preprocessed_background_image, $settings);
      $elements[$delta] = [
        'style' => [
          '#type' => 'inline_template',
          '#template' => "<style type='text/css'>{{responsive_css}}</style>",
          '#context' => [
            'responsive_css' => $this->renderableStyleElement,
            'filepath' => file_create_url($uri),
            'id' => 'test',
          ],
        ],
      ];

      // $elements[$delta] = $this->renderableStyleElement;

    }

    return $elements;
  }

  /**
   * Get the possible responsive image styles.
   *
   * @param bool $withNone
   *   True to include the 'None' option, false otherwise.
   *
   * @return array
   *   The select options.
   */
  protected function getResponsiveImageStyles($withNone = FALSE) {
    $styles = ResponsiveImageStyle::loadMultiple();
    $options = [];

    if ($withNone && empty($styles)) {
      $options[''] = t('- Defined None -');
    }

    foreach ($styles as $name => $style) {
      $options[$name] = $style->label();
    }

    return $options;
  }

  /**
   * Get the URI for the media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item.
   *
   * @return \Drupal\Core\Url|null
   *   The URL object for the media item or null if we don't want to add
   *   a link.
   */
  protected function getMediaURI(MediaInterface $media) {

    $uri = NULL;
    $file = $media->getSource();
    $uri = $file->getMetadata($media,'thumbnail_uri');

    return $uri;
  }

  /**
   * Get CSS that conforms to an installed breakpoint set.
   *
   * @param array $preprocessedBackgroundImage
   *   Settings for the background image.
   *
   * @return mixed
   *   Render array.
   */
  protected function getResponsiveBackgroundImageStyleElement($preprocessedBackgroundImage, $paragraphSettings) {
    $css_settings = $paragraphSettings['css_settings'];
    template_preprocess_responsive_image($preprocessedBackgroundImage);
    // Split each source into multiple rules.

    foreach (array_reverse($preprocessedBackgroundImage['sources']) as $source_i => $source) {

      $attr = $source->toArray();

      $srcset = explode(', ', $attr['srcset']);

      foreach ($srcset as $src_i => $src) {

        list($src, $res) = explode(' ', $src);

        $media = isset($attr['media']) ? $attr['media'] : '';

        // Add "retina" to media query if this is a 2x image.
        if ($res && $res === '2x' && !empty($media)) {
          $media = "{$media} and (-webkit-min-device-pixel-ratio: 2), {$media} and (min-resolution: 192dpi)";
        }

        // Correct a bug in template_preprocess_responsive_image which
        // generates an invalid media rule "screen (max-width)" when no
        // min-width is specified. If this bug gets fixed, this replacement
        // will deactivate.
        $media = str_replace('screen (max-width', 'screen and (max-width', $media);

        $css = $this->getBackgroundImageCss($src, $css_settings);
        // $css_settings['bg_image_selector'] probably needs to be sanitized.
        $with_media_query = sprintf('%s { background-image: url(%s);}', $css_settings['bg_image_selector'], $preprocessedBackgroundImage['img_element']['#uri']);
        $with_media_query .= sprintf('@media %s {', $media);
        $with_media_query .= sprintf($css['data']);
        $with_media_query .= '}';
        $css['attributes']['media'] = $media;
        $css['data'] = $with_media_query;

        $style_elements[] = [
          'style' => [
            '#type' => 'inline_template',
            '#template' => "{{ css }}",
            '#context' => [
              'css' => Markup::create($css['data']),
            ],
            '#attributes' => [
                'media' => $css['attributes']['media'],
            ],
          ],
        ];
      }
    }

    return $style_elements;
  }

  /**
   * Function taken from the module 'bg_image'.
   *
   * Adds a background image to the page using the
   * css 'background' property.
   *
   * @param string $image_path
   *   The path of the image to use. This can be either
   *      - A relative path e.g. sites/default/files/image.png
   *      - A uri: e.g. public://image.png.
   * @param array $css_settings
   *   An array of css settings to use. Possible values are:
   *      - bg_image_selector: The css selector to use
   *      - bg_image_color: The background color
   *      - bg_image_x: The x offset
   *      - bg_image_y: The y offset
   *      - bg_image_attachment: The attachment property (scroll or fixed)
   *      - bg_image_repeat: The repeat settings
   *      - bg_image_background_size: The background size property if necessary
   *    Default settings will be used for any values not provided.
   * @param string $image_style
   *   Optionally add an image style to the image before applying it to the
   *   background.
   *
   * @return array
   *   The array containing the CSS.
   */
  public function getBackgroundImageCss($image_path, array $css_settings = [], $image_style = NULL) {

    // If important is true, we turn it into a string for css output.
    if ($important) {
      $important = '!important';
    }
    else {
      $important = '';
    }

    // Handle the background size property.
    $bg_size = '';
    $ie_bg_size = '';

    if ($background_size) {
      // CSS3.
      $bg_size = sprintf('background-size: %s %s;', $background_size, $important);
      // Let's cover ourselves for other browsers as well...
      $bg_size .= sprintf('-webkit-background-size: %s %s;', $background_size, $important);
      $bg_size .= sprintf('-moz-background-size: %s %s;', $background_size, $important);
      $bg_size .= sprintf('-o-background-size: %s %s;', $background_size, $important);
      // IE filters to apply the cover effect.
      if ($background_size === 'cover' && $background_size_ie8) {
        $ie_bg_size = sprintf(
          "filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='%s', sizingMethod='scale');",
          $image_path
        );
        $ie_bg_size .= sprintf(
          "-ms-filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='%s', sizingMethod='scale');",
          $image_path
        );
      }
    }

    // Add the css if we have everything we need.
    if ($selector && $image_path) {
      $style = sprintf('%s {', $selector);

      if ($bg_color) {
        $style .= sprintf('background-color: %s %s;', $bg_color, $important);
      }
      $style .= sprintf("background-image: %s url('%s') %s;", $background_gradient, $image_path, $important);

      if ($repeat) {
        $style .= sprintf('background-repeat: %s %s;', $repeat, $important);
      }

      if ($attachment) {
        $style .= sprintf('background-attachment: %s %s;', $attachment, $important);
      }

      if ($bg_x && $bg_y) {
        $style .= sprintf('background-position: %s %s %s;', $bg_x, $bg_y, $important);
      }

      if ($z_index) {
        $style .= sprintf('z-index: %s;', $z_index);
      }
      $style .= $bg_size;
      $style .= $background_size_ie8 ? $ie_bg_size : '';
      $style .= '}';

      return [
        'data' => $style,
        'media' => !empty($media_query) ? $media_query : 'all',
        'group' => CSS_THEME,
      ];
    }

    return [];
  }

  protected function getAllSettings(FieldItemListInterface $items) {
    $defaultSettings = $this->defaultSettings();
    $allSettings = $defaultSettings;
    $parent = $items->getEntity();
    // Get settings from parent paragraph.
    if (!empty($parent)) {
      if ($parent instanceof ParagraphInterface) {
        $allSettings['css_settings']['bg_image_selector'] = $this->getCssSelector($items);
        // Get the behavior settings for the parent.
        $parent_config = $parent->getAllBehaviorSettings();
        $allSettings += $parent_config;
        // See if the parent behavior defines some background settings.
        if (!empty($parent_config['az_text_media_paragraph_behavior'])) {
          $az_text_media_defaults = $parent_config['az_text_media_paragraph_behavior'];
        }

      }
    }
    return $allSettings;
  }

}
