<?php

namespace Drupal\blazy\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\blazy\BlazySettings;

/**
 * A blazy admin Trait to declutter, and focus more on form elements.
 */
trait TraitAdminOptions {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public function getCacheOptions(): array {
    $period = [
      0,
      60,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];

    $period = array_map([$this->dateFormatter, 'formatInterval'],
      array_combine($period, $period));
    $period[0] = '<' . $this->t('No caching') . '>';
    return $period + [Cache::PERMANENT => $this->t('Permanent')];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityAsOptions($entity_type): array {
    return $this->blazyManager->getEntityAsOptions($entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @todo deprecated for BlazyFormatterEntityTrait::getFieldOptionsWithBundles().
   */
  public function getFieldOptions(
    array $target_bundles = [],
    array $allowed_field_types = [],
    $entity_type = 'media',
    $target_type = '',
  ): array {
    $options = [];

    // Fix for Views UI not recognizing Media bundles, unlike Formatters.
    if (empty($target_bundles)) {
      if ($service = $this->blazyManager->service('entity_type.bundle.info')) {
        $target_bundles = $service->getBundleInfo($entity_type);
      }
    }

    // Declutters options from less relevant options.
    $excludes = $this->getExcludedFieldOptions();

    foreach ($target_bundles as $bundle => $label) {
      if ($fields = $this->blazyManager->loadByProperties([
        'entity_type' => $entity_type,
        'bundle' => $bundle,
      ], 'field_config', FALSE)) {
        foreach ((array) $fields as $field) {
          if (in_array($field->getName(), $excludes)) {
            continue;
          }
          if (empty($allowed_field_types)) {
            $options[$field->getName()] = $field->getLabel();
          }
          elseif (in_array($field->getType(), $allowed_field_types)) {
            $options[$field->getName()] = $field->getLabel();
          }

          if (!empty($target_type)
            && ($field->getSetting('target_type') == $target_type)) {
            $options[$field->getName()] = $field->getLabel();
          }
        }
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getLightboxCaptionOptions(): array {
    return [
      'auto'         => $this->t('Automatic'),
      'alt'          => $this->t('Alt text'),
      'title'        => $this->t('Title text'),
      'alt_title'    => $this->t('Alt and Title'),
      'title_alt'    => $this->t('Title and Alt'),
      'entity_title' => $this->t('Content title'),
      'custom'       => $this->t('Custom'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsetOptions($entity_type): array {
    return $this->getEntityAsOptions($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModeOptions($target_type): array {
    $view_modes = $this->entityDisplayRepository->getViewModeOptions($target_type) ?: [];
    return $this->toOptions($view_modes);
  }

  /**
   * {@inheritdoc}
   */
  public function getResponsiveImageOptions(): array {
    $options = [];
    if ($this->blazyManager->moduleExists('responsive_image')) {
      $image_styles = $this->blazyManager->loadMultiple('responsive_image_style');
      if (!empty($image_styles)) {
        foreach ($image_styles as $name => $image_style) {
          if ($image_style->hasImageStyleMappings()) {
            $options[$name] = Html::escape($image_style->label());
          }
        }
        uasort($options, 'strnatcasecmp');
      }
    }
    return $options;
  }

  /**
   * Get one of the pre-defined states used in this form.
   *
   * Thanks to SAM152 at colorbox.module for the little sweet idea.
   *
   * @param string $state
   *   The state to get that matches one of the state class constants.
   * @param \Drupal\blazy\BlazySettings $scopes
   *   The current scopes.
   *
   * @return array
   *   A corresponding form API state.
   */
  protected function getState($state, BlazySettings $scopes): array {
    $lightboxes = [];

    // @todo remove the second after complete migrations.
    // @todo $options = $scopes->data('lightboxes')
    // ?: $this->blazyManager->getLightboxes();
    $options = ['content', 'link', 'media', 'rendered', ''];

    // @fixme this appears to be broken at some point of Drupal, seen
    // inconsistent between field formatters and Views field UI. Works at field
    // formatters, but broken at Views field UI.
    foreach ($options as $key => $lightbox) {
      $lightboxes[$key]['!value'] = $lightbox;
    }

    $states = [
      static::STATE_RESPONSIVE_IMAGE_STYLE_DISABLED => [
        'visible' => [
          'select[name$="[responsive_image_style]"]' => ['value' => ''],
        ],
      ],
      static::STATE_LIGHTBOX_ENABLED => [
        'visible' => [
          'select[name*="[media_switch]"]' => $lightboxes,
        ],
      ],
      static::STATE_LIGHTBOX_CUSTOM => [
        'visible' => [
          'select[name$="[media_switch]"]' => ['!value' => ''],
          'select[name$="[box_caption]"]' => ['value' => 'custom'],
          // @fixme 'select[name*="[media_switch]"]' => $lightboxes,
        ],
      ],
      static::STATE_IFRAME_ENABLED => [
        'visible' => [
          'select[name$="[media_switch]"]' => ['value' => 'media'],
        ],
      ],
      static::STATE_THUMBNAIL_STYLE_ENABLED => [
        'visible' => [
          'select[name$="[thumbnail_style]"]' => ['!value' => ''],
        ],
      ],
      static::STATE_IMAGE_RENDERED_ENABLED => [
        'visible' => [
          'select[name$="[media_switch]"]' => ['!value' => 'rendered'],
        ],
      ],
    ];
    return $states[$state];
  }

  /**
   * Declutters options from less relevant options, specific to captions.
   *
   * @todo deprecated for BlazyFormatterEntityTrait::getExcludedFieldOptions().
   */
  protected function getExcludedFieldOptions(): array {
    // @todo figure out a more efficient way than blacklisting.
    // Do not exclude field_media_image as needed for Main stage.
    // Do not exclude field_media_ as needed for Overlays.
    $fields = 'media_document media_pinterest media_svg document_size media_file id media_in_library mime_type source media_twitter tweet_author tweet_id tweet_url media_video_embed_field instagram_shortcode instagram_url media_oembed_instagram media_soundcloud media_oembed_video media_audio_file media_video_file media_facebook media_flickr file_url external_thumbnail local_thumbnail local_thumbnail_uri media_unsplash';
    $fields = array_map('trim', explode(' ', $fields));

    $excludes = [];
    foreach ($fields as $exclude) {
      $excludes['field_' . $exclude] = 'field_' . $exclude;
    }

    $this->blazyManager->moduleHandler()->alter('blazy_excluded_field_options', $excludes);
    return $excludes;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitleClasses(array $options = [], $flatten = FALSE) {
    $classes = [
      'form__title',
      'form-item',
      'form-item--subheader',
      'form-item--fullwidth',
    ];
    if ($options) {
      foreach ($options as $class) {
        $classes[] = 'form__title--' . $class;
      }
    }
    return $flatten ? implode(' ', $classes) : [
      'class' => $classes,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTooltipClasses(array $options = [], $flatten = FALSE): array {
    $classes = ['form-item--tooltip-bottom'];
    if ($options) {
      foreach ($options as $class) {
        $classes[] = 'form-item--' . $class;
      }
    }
    return $flatten ? implode(' ', $classes) : [
      'class' => $classes,
    ];
  }

}
