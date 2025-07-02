<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Field\BlazyDependenciesTrait;
use Drupal\blazy\Field\BlazyElementTrait;
use Drupal\blazy\Media\BlazyImage;
use Drupal\blazy\Utility\Sanitize;
use Drupal\blazy\internals\Internals;
use Drupal\field\FieldConfigInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for blazy ecosystem image, and file ER formatters.
 *
 * Defines one base class to extend for both image and file ER formatters as
 * otherwise different base classes: ImageFormatterBase or FileFormatterBase.
 * All blazy sub-modules image/file related formatters extend this class.
 *
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFormatter.
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatter.
 *
 * @todo remove no longer in use: ImageFactory at blazy:3.x.
 */
abstract class BlazyFileFormatterBase extends FileFormatterBase {

  use BlazyFormatterTrait {
    getScopedFormElements as traitGetScopedFormElements;
  }

  use BlazyDependenciesTrait;
  use BlazyElementTrait;
  use BlazyFormatterEntityTrait;

  /**
   * The main module namespace.
   *
   * @var string
   * @see https://www.php.net/manual/en/reserved.keywords.php
   */
  protected static $namespace = 'blazy';

  /**
   * The item property to store image or media: content, slide, box, etc.
   *
   * Prioritize sub-modules in case mismatched versions.
   *
   * @var string
   */
  protected static $itemId = 'slide';

  /**
   * The item prefix for captions, e.g.: blazy__caption, slide__caption, etc.
   *
   * @var string
   */
  protected static $itemPrefix = 'slide';

  /**
   * The caption property to store captions.
   *
   * @var string
   */
  protected static $captionId = 'caption';

  /**
   * Tne navigation ID.
   *
   * @var string
   */
  protected static $navId = 'thumb';

  /**
   * The fake field type identifier for service DI, e.g: entity, image, text.
   *
   * @var string
   */
  protected static $fieldType = 'image';

  /**
   * Whether displaying a single item by index, or not.
   *
   * @var bool
   */
  protected static $byDelta = FALSE;

  /**
   * Whether using the OEmbed service.
   *
   * @var bool
   */
  protected static $useOembed = FALSE;

  /**
   * Whether using the SVG.
   *
   * @var bool
   */
  protected static $useSvg = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->svgManager = $container->get('blazy.svg');
    return static::injectServices($instance, $container, static::$fieldType);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::imageSettings() + BlazyDefault::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element    = [];
    $definition = $this->getScopedFormElements();

    $definition['_views'] = isset($form['field_api_classes']);
    $this->admin()->buildSettingsForm($element, $definition);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entities = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($entities)) {
      return [];
    }

    return $this->commonViewElements($items, $langcode, $entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildElements(array &$build, $files, $langcode) {
    foreach ($this->getElements($build, $files) as $element) {
      if ($element) {
        // Since 2.17, match sub-modules `items` for easy swap later to DRY.
        $build['items'][] = $element;

        $this->withOverride($build, $element);
      }
    }
  }

  /**
   * Returns the Blazy elements, also for sub-modules to re-use.
   */
  protected function getElements(array $build, $files): \Generator {
    $settings = &$build['#settings'];
    $blazies  = $settings['blazies'];
    $limit    = $this->getViewLimit($settings);
    $by_delta = $settings['by_delta'] ?? -1;
    $total    = $blazies->total();
    $valid    = $by_delta > -1 && $by_delta < $total;

    // Returns a single item by delta if so-configured.
    if ($valid && $entity = ($files[$by_delta] ?? NULL)) {
      Internals::updateCountByDelta($settings);
      yield $this->getElement($settings, $entity, $by_delta);
    }
    else {
      // Else a regular loop.
      foreach ($files as $delta => $file) {
        // If a Views display, bail out if more than Views delta_limit.
        // @todo figure out why Views delta_limit doesn't stop us here.
        if ($limit > 0 && $delta > $limit - 1) {
          yield [];
        }
        else {
          yield $this->getElement($settings, $file, $delta);
        }
      }
    }
  }

  /**
   * Returns the individual element.
   */
  protected function getElement(array $settings, $file, $delta): array {
    /** @var \Drupal\file\Plugin\Field\FieldType\FileItem $item */
    /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $item */
    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
    $item = $file->_referringItem;
    $sets = $settings;
    $uri  = $file->getFileUri();
    $info = [
      'delta'      => $delta,
      'media.type' => 'image',
    ];

    // Extracts ImageItem data early to help new SVG with its attributes.
    $image = ['uri' => $uri];
    if ($item instanceof ImageItem && $values = BlazyImage::toArray($item)) {
      foreach ($values as $key => $value) {
        $image[$key] = $value;
      }
      // @todo remove this pingpong at 3.x:
      $image['item'] = $item;
    }

    $info['image'] = $image;

    // Hashtags to avoid render errors with some potential leaks.
    $data = [
      '#delta'    => $delta,
      '#entity'   => $file,
      '#item'     => $item,
      '#settings' => $this->formatter->toSettings($sets, $info),
    ];

    // Provide parent context for fieldable captions with entity_reference.
    if ($item instanceof EntityReferenceItem) {
      $parent = $item->getParent();
      if ($parent && method_exists($parent, 'getEntity')) {
        $data['#parent'] = $parent->getEntity();
      }
    }

    // Build individual element, no real use here since VEF deprecated.
    // Except for SVG since 2.17.
    $this->withElement($data);

    // Build captions if so configured.
    $captions = $this->getCaptions($data);

    // Provides the relevant elements based on the configuration.
    return $this->toElement($sets['blazies'], $data, $captions);
  }

  /**
   * Returns the captions, if any.
   */
  protected function getCaptions(array $data): array {
    [
      '#settings' => $settings,
      '#item'     => $item,
    ] = $data;

    // At most cases, unless file entity is installed, the parent is the entity.
    $entity    = $data['#parent'] ?? NULL;
    $blazies   = $settings['blazies'];
    $options   = $settings['caption'] ?? [];
    $options   = array_filter($options);
    $display   = empty($settings['svg_hide_caption']);
    $type      = $blazies->get('field.type');
    $_link     = $settings['link'] ?? NULL;
    $_switch   = $settings['media_switch'] ?? NULL;
    $view_mode = $settings['view_mode'] ?? 'default';
    $captions  = [];
    $titlesets = $blazies->get('format.title', []);
    $formatted = !empty($titlesets['delimiter']);
    $url       = empty($titlesets['link_to_entity'])
      ? NULL : Internals::entityUrl($entity);

    if ($options) {
      // Provides default image captions.
      if ($item) {
        foreach ($options as $name) {
          if ($content = ($item->{$name} ?? NULL)) {
            $caption = Sanitize::caption($content);

            // SVG image field, or plain old image:
            // if ($name == 'alt' || $name == 'title') {
            // Conflict with sub-modules' markups, not blazy's.
            // @todo enable at 3.x when they use theme_blazy().
            // if ($caption  && $name == 'alt') {
            // $caption = '<p>' . $caption . '</p>';
            // }
            // }
            // File with description_field enabled, have description.
            // SVG image field, or plain old image have title and alt.
            if (in_array($name, ['alt', 'description', 'title'])) {
              $blazies->set('image.' . $name, $caption);
            }

            if ($display) {
              if ($formatted) {
                $captions[$name] = Internals::formatTitle($caption, $url, $titlesets);
              }
              else {
                $captions[$name] = ['#markup' => $caption];
              }
            }
          }
        }
      }

      // Provides fieldable captions.
      if ($type == 'entity_reference' && $entity) {
        foreach ($options as $name) {
          if ($markup = $this->viewField($entity, $name, [])) {
            $captions[$name] = $markup;
          }
        }
      }
    }

    // Link, if so configured.
    if ($_link && $entity && isset($entity->{$_link})) {
      $links = $this->viewField($entity, $_link, []);
      $formatter = $links['#formatter'] ?? 'x';

      // Only simplify markups for known formatters registered by link.module.
      if ($links && in_array($formatter, ['link'])) {
        $links = [];
        foreach ($entity->{$_link} as $link) {
          $links[] = $link->view($view_mode);
        }
      }

      $blazies->set('field.values.link', $links);

      // If linkable element is plain text, it is not worth a caption.
      if ($_switch == 'link' && $link = $links[0] ?? []) {
        if (Internals::emptyOrPlainTextLink($link)) {
          $links = [];
        }
      }

      $captions['link'] = $links;
    }

    return $captions ? array_filter($captions) : [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo move it into BlazyFileSvgFormatterBase after sub-modules.
   */
  protected function getEntityScopes(): array {
    return [
      'fieldable_form'   => TRUE,
      'multimedia'       => TRUE,
      'no_loading'       => TRUE,
      'no_preload'       => TRUE,
      'responsive_image' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $field    = $this->fieldDefinition;
    $multiple = $this->isMultiple();
    $type     = $field->getType();
    $is_image = $type == 'image' || $type == 'svg_image_field';
    $_links   = ['text', 'string', 'link'];
    $links    = $this->getFieldOptions($_links, $field->getTargetEntityTypeId());

    return [
      'background'        => TRUE,
      'by_delta'          => $multiple && static::$byDelta,
      'captions'          => $this->getCaptionOptions(),
      'grid_form'         => $multiple,
      'image_style_form'  => TRUE,
      'media_switch_form' => TRUE,
      'svg_form'          => static::$useSvg,
      'style'             => $multiple,
      'thumbnail_style'   => TRUE,
      'no_image_style'    => FALSE,
      'responsive_image'  => TRUE,
      'multiple'          => $multiple,
      'view_mode'         => $is_image ? NULL : $this->viewMode,
      'no_view_mode'      => $is_image,
      'links'             => $links,
    ];
  }

  /**
   * Returns available bundles.
   *
   * @todo move it into BlazyFileSvgFormatterBase after sub-modules.
   */
  protected function getAvailableBundles(): array {
    $field = $this->fieldDefinition;
    if (method_exists($field, 'get')) {
      $bundle = $field->get('bundle');
      return $bundle ? [$bundle => $bundle] : [];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo move some into BlazyFileSvgFormatterBase after sub-modules.
   */
  protected function getCaptionOptions() {
    $field    = $this->fieldDefinition;
    $type     = $field->getType();
    $_texts   = ['text', 'text_long', 'string', 'string_long', 'link'];
    $captions = [];

    if ($field->getSetting('description_field')) {
      $captions['description'] = $this->t('Description');
    }
    elseif ($type == 'image' || $type == 'svg_image_field') {
      $captions = 'default';
    }
    else {
      $captions = $this->getFieldOptions($_texts, $field->getTargetEntityTypeId());
    }
    return $captions;
  }

  /**
   * Returns fields as options. Passing empty array will return them all.
   *
   * @return array
   *   The available fields as options.
   *
   * @todo move it into BlazyFileSvgFormatterBase after sub-modules.
   */
  protected function getFieldOptions(array $names = [], $target_type = NULL): array {
    $field       = $this->fieldDefinition;
    $target_type = $target_type ?: $this->getFieldSetting('target_type');
    $bundles     = $this->getAvailableBundles();
    $entity_type = method_exists($field, 'get') ? $field->get('entity_type') : $field->getTargetEntityTypeId();

    if (!$bundles && $entity_type && $service = $this->formatter->service('entity_type.bundle.info')) {
      $bundles = $service->getBundleInfo($entity_type);
    }

    return $this->getFieldOptionsWithBundles($bundles, $names, $target_type);
  }

  /**
   * {@inheritdoc}
   *
   * One step back to have both image and file ER plugins extend this, because
   * EntityReferenceItem::isDisplayed() doesn't exist, except for ImageItem
   * which is always TRUE anyway for type image and file ER.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * {@inheritdoc}
   *
   * A clone of Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase so
   * to have one base class to extend for both image and file ER formatters.
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    // Add the default image if the type is image.
    if ($items->isEmpty() && $this->fieldDefinition->getType() === 'image') {
      $default_image = $this->getFieldSetting('default_image');
      $uuid = $default_image['uuid'] ?? NULL;

      // If we are dealing with a configurable field, look in both
      // instance-level and field-level settings.
      if (!$uuid && $this->fieldDefinition instanceof FieldConfigInterface) {
        $default_image = $this->fieldDefinition
          ->getFieldStorageDefinition()
          ->getSetting('default_image');
      }

      $uuid = $uuid ?: ($default_image['uuid'] ?? NULL);
      if ($uuid && $file = $this->formatter->loadByUuid($uuid, 'file')) {
        // Clone the FieldItemList into a runtime-only object for the formatter,
        // so that the fallback image can be rendered without affecting the
        // field values in the entity being rendered.
        $items = clone $items;
        $items->setValue([
          'target_id' => $file->id(),
          'alt' => $default_image['alt'],
          'title' => $default_image['title'],
          'width' => $default_image['width'],
          'height' => $default_image['height'],
          'entity' => $file,
          '_loaded' => TRUE,
          '_is_default' => TRUE,
        ]);
        $file->_referringItem = $items[0];
      }
    }

    return parent::getEntitiesToView($items, $langcode);
  }

  /**
   * Build item elements.
   */
  protected function withElement(array &$build): void {
    if (static::$useOembed) {
      // This basically associates file to media entity like seen at dep VEF.
      $this->blazyOembed->build($build);
    }
  }

  /**
   * Provides overrides for BC.
   */
  private function withOverride(array &$build, array $element): void {
    if (method_exists($this, 'withElementOverride')) {
      foreach (['delta', 'entity', 'settings'] as $key) {
        $default = $key == 'settings' ? [] : NULL;
        $build["#$key"] = $element["#$key"] ?? $build["#$key"] ?? $default;
      }
      $this->withElementOverride($build, $element);
    }
  }

}
