<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\blazy\Field\BlazyField;
use Drupal\blazy\Traits\PluginScopesTrait;
use Drupal\blazy\internals\Internals;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Trait common for all blazy formatters.
 */
trait BlazyFormatterTrait {

  use PluginScopesTrait;
  use BlazyFormatterViewTrait;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyFormatterInterface
   */
  protected $formatter;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * The blazy-related manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The blazy entity service.
   *
   * @var \Drupal\blazy\BlazyEntityInterface
   */
  protected $blazyEntity;

  /**
   * The blazy oembed service.
   *
   * @var \Drupal\blazy\Media\BlazyOEmbedInterface
   */
  protected $blazyOembed;

  /**
   * The blazy media service.
   *
   * @var \Drupal\blazy\Media\BlazyMediaInterface
   */
  protected $blazyMedia;

  /**
   * Returns the blazy formatter manager.
   *
   * @todo remove at 3.x, hardly called outside the formatters, except tests.
   */
  public function formatter() {
    return $this->formatter;
  }

  /**
   * Returns the blazy manager.
   *
   * @todo remove at 3.x, hardly called outside the formatters, except tests.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * Returns any blazy-related manager.
   *
   * @todo remove at 3.x, hardly called outside the formatters, except tests.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns the blazy entity manager.
   *
   * @todo remove at 3.x, hardly called outside the formatters, except tests.
   */
  public function blazyEntity() {
    return $this->blazyEntity;
  }

  /**
   * Returns the blazy oembed manager.
   *
   * @todo remove at 3.x, hardly called outside the formatters, except tests.
   */
  public function blazyOembed() {
    return $this->blazyOembed;
  }

  /**
   * Returns the blazy admin service.
   */
  public function admin() {
    return \Drupal::service('blazy.admin.formatter');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return $this->admin()->getSettingsSummary($this->getScopedFormElements());
  }

  /**
   * Builds the settings.
   */
  public function buildSettings() {
    $settings = array_merge($this->getCommonFieldDefinition(), $this->getSettings());
    $blazies  = $settings['blazies'];
    $multiple = $this->isMultiple();
    $is_grid  = !empty($settings['style']) && !empty($settings['grid']);

    // Since 2.17, the item array was to replace all sub-modules theme_ITEM() by
    // theme_blazy() for easy improvements at 3.x, optional via Blazy UI.
    $namespace = static::$namespace;
    $blazies->set('is.grid', $is_grid && $multiple)
      ->set('is.multiple', $multiple)
      ->set('item.id', static::$itemId)
      ->set('item.prefix', static::$itemPrefix)
      ->set('item.caption', static::$captionId)
      ->set('namespace', $blazies->get('namespace', $namespace));

    $this->pluginSettings($blazies, $settings);

    return $settings;
  }

  /**
   * Defines the scope for the form elements.
   *
   * Since 2.10 sub-modules can forget this, and use self::getPluginScopes().
   */
  public function getScopedFormElements() {
    // Containing settings, blazies which must be intact, and the rest, which
    // can be removed after migrations, are merged into scopes object.
    $commons = $this->getCommonScopedFormElements();

    // Compat for BVEF till updated to adopt Blazy 2.10 BlazyVideoFormatter.
    $scopes = method_exists($this, 'getPluginScopes')
      ? $this->getPluginScopes() : [];

    // @todo remove `$scopes +` at Blazy 3.x, leaving only settings + blazies.
    $definitions = $scopes + $commons;
    $definitions['scopes'] = $this->toPluginScopes($scopes + $commons);
    return $definitions;
  }

  /**
   * Injects DI services.
   */
  protected static function injectServices($instance, ContainerInterface $container, $type = '') {
    // Blazy has sequential inheritance, its sub-modules deviate.
    $instance->formatter = $instance->blazyManager = $instance->manager = $container->get('blazy.formatter');
    $instance->loggerFactory = $instance->loggerFactory ?? $container->get('logger.factory');

    // Provides optional services.
    if ($type == 'entity') {
      $instance->blazyEntity = $instance->blazyEntity ?? $container->get('blazy.entity');
      $instance->blazyOembed = $instance->blazyOembed ?? $instance->blazyEntity->oembed();
      $instance->blazyMedia  = $instance->blazyMedia ?? $instance->blazyOembed->blazyMedia();
    }

    return $instance;
  }

  /**
   * Defines the common scope for both front and admin.
   */
  protected function getCommonFieldDefinition() {
    $field = $this->fieldDefinition;
    $settings = [
      'namespace' => static::$namespace,
    ];

    // Exposes few basic formatter settings w/o use_field.
    $data = [
      'label_display' => $this->label,
      'plugin_id'     => $this->getPluginId(),
      'third_party'   => $this->getThirdPartySettings(),
      'view_mode'     => $this->viewMode,
      'formatter'     => array_filter($this->getSettings()),
    ];

    return BlazyField::settings($settings, $field, $data);
  }

  /**
   * Defines the common scope for the form elements.
   */
  protected function getCommonScopedFormElements() {
    return ['settings' => $this->getSettings()]
      + $this->getCommonFieldDefinition();
  }

  /**
   * Returns Views delta_limit option.
   */
  protected function getViewLimit(array $settings): int {
    $blazies = $settings['blazies'];
    return Internals::getViewLimit($blazies);
  }

  /**
   * Returns TRUE if a multi-value field.
   *
   * @return bool
   *   TRUE if a multivalue field, else FALSE.
   */
  protected function isMultiple(): bool {
    return $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->isMultiple();
  }

  /**
   * Alias for BlazyField::getString().
   */
  protected function getString($entity, $field_name, $langcode, $clean = TRUE): string {
    return BlazyField::getString($entity, $field_name, $langcode, $clean);
  }

  /**
   * Alias for BlazyField::view().
   */
  protected function viewField($entity, $field_name, $view_mode, $multiple = TRUE): array {
    return BlazyField::view($entity, $field_name, $view_mode, $multiple);
  }

}
