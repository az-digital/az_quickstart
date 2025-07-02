<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Field\BlazyDependenciesTrait;
use Drupal\media\Entity\MediaType;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for blazy oembed formatter.
 *
 * @FieldFormatter(
 *   id = "blazy_oembed",
 *   label = @Translation("Blazy OEmbed"),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long",
 *   }
 * )
 *
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatterBase
 * @see \Drupal\media\Plugin\Field\FieldFormatter\OEmbedFormatter
 */
class BlazyOEmbedFormatter extends FormatterBase {

  use BlazyDependenciesTrait;
  use BlazyFormatterTrait;

  /**
   * The module namespace.
   *
   * @var string
   * @see https://www.php.net/manual/en/reserved.keywords.php
   */
  protected static $namespace = 'blazy';

  /**
   * The item id: blazy, slide, box, etc.
   *
   * @var string
   */
  protected static $itemId = 'content';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'blazy';

  /**
   * The caption id.
   *
   * @var string
   */
  protected static $captionId = 'captions';

  /**
   * {@inheritdoc}
   */
  protected static $fieldType = 'entity';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return static::injectServices($instance, $container, static::$fieldType);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::baseImageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return $this->commonViewElements($items, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $definition = $this->getScopedFormElements();
    $definition['_views'] = isset($form['field_api_classes']);

    $this->admin()->buildSettingsForm($element, $definition);

    // Makes options look compact.
    // @todo phpstan bug doesn't catch altered $element:
    /* @phpstan-ignore-next-line */
    if (isset($element['background'])) {
      $element['background']['#weight'] = -99;
    }
    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }

    if ($media_type = $field_definition->getTargetBundle()) {
      $media_type = MediaType::load($media_type);
      return $media_type && $media_type->getSource() instanceof OEmbedInterface;
    }

    return FALSE;
  }

  /**
   * Provides the blazy elements.
   */
  protected function buildElements(array &$build, $items, $langcode) {
    $settings = $build['#settings'];
    $limit    = $this->getViewLimit($settings);

    foreach ($this->getElements($build, $items) as $delta => $element) {
      // If a Views display, bail out if more than Views delta_limit.
      // @todo figure out why Views delta_limit doesn't stop us here.
      if ($limit > 0 && $delta > $limit - 1) {
        break;
      }

      $build['items'][] = $element;
    }
  }

  /**
   * Generates the Blazy elements.
   */
  protected function getElements(array &$build, $items): \Generator {
    $settings   = &$build['#settings'];
    $field_name = $this->fieldDefinition->getName();
    $entity     = $items->getParent()->getEntity();

    foreach ($items as $delta => $item) {
      $element = [];

      if ($item instanceof FieldItemInterface) {
        $class    = get_class($item);
        $property = $class::mainPropertyName();
        $sets     = $settings;

        if ($value = $item->{$property}) {
          $media = $this->blazyMedia->fromField($entity, $field_name, $value);

          $info = [
            'delta' => $delta,
            'media.input_url' => $value,
          ];

          $data = [
            '#delta'    => $delta,
            '#item'     => NULL,
            '#settings' => $this->formatter->toSettings($sets, $info),
          ];

          if ($media) {
            $data['#entity'] = $media;
            $data['#parent'] = $entity;

            $this->blazyOembed->build($data);
          }

          // Media OEmbed with lazyLoad and lightbox supports.
          $element = $this->formatter->getBlazy($data);
        }
      }

      yield $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    return [
      'image_style_form'  => TRUE,
      'background'        => TRUE,
      'media_switch_form' => TRUE,
      'multimedia'        => TRUE,
      'no_preload'        => TRUE,
      'responsive_image'  => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function postSettings(array &$settings, $langcode): void {
    $blazies = $settings['blazies'];
    $blazies->set('language.code', $langcode);
    // The form is not loaded at views UI, provides the minimum.
    // @todo remove when the form is loaded at Views UI.
    if ($blazies->get('view.embedded')
      && $defaults = $blazies->get('media.defaults', [])) {
      $settings = array_merge($settings, $defaults);
      $blazies->set('libs.media', TRUE);
    }
  }

}
