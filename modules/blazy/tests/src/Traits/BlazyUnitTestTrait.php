<?php

namespace Drupal\Tests\blazy\Traits;

use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Traits\PluginScopesTrait;

/**
 * A Trait common for Blazy Unit tests.
 */
trait BlazyUnitTestTrait {

  use BlazyPropertiesTestTrait;
  use PluginScopesTrait;

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The type config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The formatter settings.
   *
   * @var array
   */
  protected $formatterSettings = [];

  /**
   * Returns sensible formatter settings for testing purposes.
   *
   * @return array
   *   The formatter settings.
   */
  protected function getFormatterSettings() {
    $defaults = [
      'box_caption'     => 'custom',
      'box_style'       => 'large',
      'cache'           => 0,
      'image_style'     => 'blazy_crop',
      'media_switch'    => 'blazy_test',
      'thumbnail_style' => 'thumbnail',
      'ratio'           => 'fluid',
      'caption'         => ['alt' => 'alt', 'title' => 'title'],
    ] + BlazyDefault::extendedSettings()
      + Blazy::init()
      + $this->getDefaultFieldDefinition();

    Blazy::entitySettings($defaults, $this->entity);

    return empty($this->formatterSettings) ? $defaults : array_merge($defaults, $this->formatterSettings);
  }

  /**
   * Sets formatter settings.
   *
   * @param array $settings
   *   The given settings.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  protected function setFormatterSettings(array $settings = []) {
    $this->formatterSettings = array_merge($this->getFormatterSettings(), $settings);
    return $this;
  }

  /**
   * Sets formatter setting.
   *
   * @param string $setting
   *   The given setting.
   * @param mixed|bool|string $value
   *   The given value.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  protected function setFormatterSetting($setting, $value) {
    $this->formatterSettings[$setting] = $value;
    return $this;
  }

  /**
   * Returns the default field definition.
   *
   * @return array
   *   The default field definition.
   */
  protected function getDefaultFieldDefinition() {
    return [
      'bundle'      => $this->bundle ?? 'bundle_test',
      'entity_type' => $this->entityType,
      'field_name'  => $this->testFieldName,
      'field_type'  => 'image',
    ];
  }

  /**
   * Returns the default field formatter definition.
   *
   * @return array
   *   The default field formatter settings.
   */
  protected function getCommonScopedFormElements() {
    return ['settings' => $this->getFormatterSettings()]
      + $this->getDefaultFieldDefinition();
  }

  /**
   * Defines the scope for the form elements.
   *
   * Since 2.10 sub-modules can forget this, and use self::getPluginScopes().
   */
  public function getScopedFormElements() {
    $commons = $this->getCommonScopedFormElements();
    $scopes = $this->getPluginScopes();

    // @todo remove `$scopes +` at Blazy 3.x.
    $definitions = $scopes + $commons;
    $definitions['scopes'] = $this->toPluginScopes($scopes + $commons);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    return [
      'background'        => TRUE,
      'captions'          => ['alt' => 'Alt', 'title' => 'Title'],
      'classes'           => ['field_class' => 'Classes'],
      'multimedia'        => TRUE,
      'images'            => [$this->testFieldName => $this->testFieldName],
      'layouts'           => ['top' => 'Top'],
      'links'             => ['field_link' => 'Link'],
      'namespace'         => 'blazy',
      'responsive_image'  => TRUE,
      'thumbnail_style'   => TRUE,
      'skins'             => ['classic' => 'Classic'],
      'style'             => 'grid',
      'target_type'       => 'file',
      'titles'            => ['field_text' => 'Text'],
      'view_mode'         => 'default',
      'grid_form'         => TRUE,
      'image_style_form'  => TRUE,
      'fieldable_form'    => TRUE,
      'media_switch_form' => TRUE,
    ];
  }

  /**
   * Returns the default field formatter definition.
   *
   * @return array
   *   The default field formatter settings.
   */
  protected function getDefaulEntityFormatterDefinition() {
    return [
      'nav'              => TRUE,
      'overlays'         => ['field_image' => 'Image'],
      'vanilla'          => TRUE,
      'optionsets'       => ['default' => 'Default'],
      'thumbnails'       => TRUE,
      'thumbnail_effect' => ['grid', 'hover'],
      'thumbnail_style'  => TRUE,
      'thumb_captions'   => ['field_text' => 'Text'],
      'thumb_positions'  => TRUE,
      'caches'           => TRUE,
    ];
  }

  /**
   * Returns the field formatter definition along with settings.
   *
   * @return array
   *   The field formatter settings.
   */
  protected function getFormatterDefinition() {
    $defaults = $this->getScopedFormElements();

    return empty($this->formatterDefinition)
      ? $defaults : array_merge($defaults, $this->formatterDefinition);
  }

  /**
   * Sets the field formatter definition.
   *
   * @param string $definition
   *   The key definition defining scope for form elements.
   * @param mixed|string|bool $value
   *   The defined value.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  protected function setFormatterDefinition($definition, $value) {
    $this->formatterDefinition[$definition] = $value;
    return $this;
  }

  /**
   * Pre render Blazy image.
   *
   * @param array $build
   *   The data containing: settings and image item.
   *
   * @return array
   *   The pre_render element.
   */
  protected function doPreRenderImage(array $build) {
    $settings = $this->blazyManager->toHashtag($build);
    $this->blazyManager->postSettings($settings);

    $image = $this->blazyManager->getBlazy($build);

    $image['#build']['#item'] = empty($image['#build']['#item'])
      ? $build['#item'] : $image['#build']['#item'];
    return $this->blazyManager->preRenderBlazy($image);
  }

  /**
   * Returns dummy fields for an entity reference.
   *
   * @return array
   *   A common field array for Blazy related entity reference formatter.
   */
  protected function getDefaultFields($select = FALSE) {
    $fields = [
      'field_class'  => 'text',
      'field_id'     => 'text',
      'field_image'  => 'image',
      'field_layout' => 'list_string',
      'field_link'   => 'link',
      'field_title'  => 'text',
      'field_teaser' => 'text',
    ];

    $options = [];
    foreach (array_keys($fields) as $key) {
      if (in_array($key, ['field_id', 'field_teaser'])) {
        continue;
      }
      $option = str_replace('field_', '', $key);
      $options[$option] = $key;
    }

    return $select ? $options : $fields;
  }

  /**
   * Set up Blazy variables.
   */
  protected function setUpVariables() {
    $this->entityType    = 'node';
    $this->bundle        = 'bundle_test';
    $this->testFieldName = 'field_image_multiple';
    $this->testFieldType = 'image';
    $this->testPluginId  = 'blazy';
    $this->maxItems      = 3;
    $this->maxParagraphs = 30;
  }

  /**
   * Setup the unit images.
   */
  protected function setUpUnitImages() {
    $item = new \stdClass();
    $item->uri = 'public://example.jpg';
    $item->entity = NULL;
    $item->alt = $this->randomMachineName();
    $item->title = $this->randomMachineName();

    $settings = $this->getFormatterSettings();

    $this->uri = $settings['uri'] = $item->uri;

    $this->data = [
      '#settings' => $settings,
      '#item' => $item,
    ];

    $this->testItem = $item;
  }

  /**
   * Setup the unit images.
   */
  protected function setUpMockImage() {
    $entity = $this->createMock('\Drupal\Core\Entity\ContentEntityInterface');

    /* @phpstan-ignore-next-line */
    $entity->expects($this->any())
      ->method('label')
      ->willReturn($this->randomMachineName());

    /* @phpstan-ignore-next-line */
    $entity->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('node'));

    $item = $this->createMock('\Drupal\Core\Field\FieldItemListInterface');

    /* @phpstan-ignore-next-line */
    $item->expects($this->any())
      ->method('getEntity')
      ->willReturn($entity);

    $this->setUpUnitImages();

    $this->testItem = $item;
    $this->data['#item'] = $item;
    $item->entity = $entity;
  }

}

namespace Drupal\blazy;

if (!function_exists('blazy')) {

  /**
   * Dummy function.
   */
  function blazy() {
    // Empty block to satisfy coder.
  }

}
