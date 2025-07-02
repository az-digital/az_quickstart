<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\blazy\Asset\LibrariesInterface;
use Drupal\blazy\Theme\Grid;
use Drupal\blazy\Utility\Arrays;
use Drupal\blazy\internals\Internals;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides common non-media/ generic methods across Blazy ecosystem to DRY.
 */
abstract class BlazyBase implements BlazyInterface {

  // Fixed for EB AJAX issue: #2893029.
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The blazy libraries service.
   *
   * @var \Drupal\blazy\Asset\LibrariesInterface
   */
  protected $libraries;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The main module namespace, kind of group name including their sub-modules.
   *
   * Unlike classes, slick_views, etc. will be under slick namespace with this.
   *
   * @var string
   * @see https://www.php.net/manual/en/reserved.keywords.php
   */
  protected static $namespace = 'blazy';

  /**
   * The item property to store image or media: content, slide, box, etc.
   *
   * @var string
   */
  protected static $itemId = 'content';

  /**
   * The item prefix for captions, e.g.: blazy__caption, slide__caption, etc.
   *
   * @var string
   */
  protected static $itemPrefix = 'blazy';

  /**
   * Constructs a BlazyBase object.
   */
  public function __construct(
    LibrariesInterface $libraries,
    EntityRepositoryInterface $entity_repository,
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    LanguageManagerInterface $language_manager,
  ) {
    $this->libraries         = $libraries;
    $this->root              = $libraries->root();
    $this->cache             = $libraries->cache();
    $this->configFactory     = $libraries->configFactory();
    $this->moduleHandler     = $libraries->moduleHandler();
    $this->entityRepository  = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer          = $renderer;
    $this->languageManager   = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('blazy.libraries'),
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function root() {
    return $this->root;
  }

  /**
   * {@inheritdoc}
   */
  public function entityRepository() {
    return $this->entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function libraries() {
    return $this->libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function moduleHandler() {
    return $this->moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function renderer() {
    return $this->renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function renderInIsolation(array &$elements) {
    // @todo call directly ::renderInIsolation() when min D10.3.
    return Blazy::backwardsCompatibleCall(
      deprecatedVersion: '10.3',
      // @phpstan-ignore-next-line
      currentCallable: fn() => $this->renderer->renderInIsolation($elements),
      // @phpstan-ignore-next-line
      deprecatedCallable: fn() => $this->renderer->renderPlain($elements),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function configFactory() {
    return $this->configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function cache() {
    return $this->cache;
  }

  /**
   * {@inheritdoc}
   */
  public function languageManager() {
    return $this->languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function routeMatch() {
    return $this->libraries->routeMatch();
  }

  /**
   * {@inheritdoc}
   */
  public function config($key = NULL, $group = 'blazy.settings') {
    return $this->libraries->config($key, $group);
  }

  /**
   * {@inheritdoc}
   */
  public function configMultiple($group = 'blazy.settings'): array {
    return $this->libraries->configMultiple($group);
  }

  /**
   * {@inheritdoc}
   */
  public function myConfig($key = NULL) {
    return $this->config($key, static::$namespace . '.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function myConfigMultiple(): array {
    return $this->configMultiple(static::$namespace . '.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function configSchemaInfoAlter(
    array &$definitions,
    $formatter = 'blazy_base',
    array $settings = [],
  ): void {
    BlazyAlter::configSchemaInfoAlter($definitions, $formatter, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function denied($entity): array {
    return Internals::denied($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function entityQuery($type, $conjunction = 'AND', $access = TRUE) {
    return $this->getStorage($type)->getQuery($conjunction)->accessCheck($access);
  }

  /**
   * {@inheritdoc}
   */
  public function getCachedData(
    $cid,
    array $data = [],
    array $info = [],
  ): array {
    return $this->getCachedOptions($cid, $data, FALSE, $info);
  }

  /**
   * {@inheritdoc}
   */
  public function getCachedOptions(
    $cid,
    array $data = [],
    $as_options = TRUE,
    array $info = [],
  ): array {
    return $this->libraries->getCachedData(
      $cid,
      $data,
      $as_options,
      $info
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMetadata(array $build): array {
    return $this->libraries->getCacheMetadata($build);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityAsOptions($entity_type): array {
    $options = [];
    if ($entities = $this->loadMultiple($entity_type)) {
      foreach ($entities as $entity) {
        $options[$entity->id()] = Html::escape($entity->label());
      }
      uasort($options, 'strnatcasecmp');
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getHtmlId($name = 'blazy', $id = ''): string {
    return Internals::getHtmlId($name, $id);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrariesPath($name, $base_path = FALSE): ?string {
    return $this->libraries->getPath($name, $base_path);
  }

  /**
   * {@inheritdoc}
   */
  public function getPath($type, $name, $absolute = FALSE): ?string {
    return Internals::getPath($type, $name, $absolute);
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage($type = 'media') {
    return $this->entityTypeManager->getStorage($type);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatedEntity($object, $langcode = NULL) {
    if ($object instanceof EntityInterface) {
      return $this->entityRepository->getTranslationFromContext($object, $langcode);
    }
    return $object;
  }

  /**
   * {@inheritdoc}
   */
  public function gridAttributes(array &$attrs, array $settings): void {
    Grid::attributes($attrs, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function gridCheckAttributes(
    array &$attrs,
    array &$content_attrs,
    $blazies,
    $root = FALSE,
  ): void {
    Grid::checkAttributes($attrs, $content_attrs, $blazies, $root);
  }

  /**
   * {@inheritdoc}
   */
  public function gridItemAttributes(
    array &$attrs,
    array &$content_attrs,
    array $settings,
  ): void {
    Grid::itemAttributes($attrs, $content_attrs, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function import(array $options): void {
    $this->libraries->import($options);
  }

  /**
   * {@inheritdoc}
   */
  public function initGrid(array $options): array {
    return Grid::initGrid($options);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id, $type = 'image_style') {
    if (strpos($type, '.settings') !== FALSE) {
      return $this->config($id, $type);
    }
    return $this->getStorage($type)->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple($type = 'image_style', $ids = NULL): array {
    return $this->getStorage($type)->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(
    array $values,
    $type = 'file',
    $access = TRUE,
    $conjunction = 'AND',
    $condition = 'IN',
  ): array {
    $storage = $this->getStorage($type);
    $query = $storage->getQuery($conjunction);

    $query->accessCheck($access);
    $this->buildPropertyQuery($query, $values, $condition);

    $result = $query->execute();
    return $result ? $storage->loadMultiple($result) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperty($porperty, $value, $type): ?object {
    $entity = NULL;
    if ($value && $entities = $this->loadByProperties([$porperty => $value], $type, TRUE)) {
      $entity = reset($entities) ?: NULL;
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUuid($uuid, $type = 'file'): ?object {
    return $this->entityRepository->loadEntityByUuid($type, $uuid);
  }

  /**
   * {@inheritdoc}
   */
  public function markdown($string, $help = TRUE, $sanitize = TRUE): string {
    return Internals::markdown($string, $help, $sanitize);
  }

  /**
   * {@inheritdoc}
   */
  public function merge(array $data, array $element, $key = NULL): array {
    return Arrays::merge($data, $element, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function mergeSettings($keys, array $defaults, array $configs): array {
    return Arrays::mergeSettings($keys, $defaults, $configs);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleExists($name): bool {
    return $this->moduleHandler->moduleExists($name);
  }

  /**
   * {@inheritdoc}
   */
  public function service($name): ?object {
    return Internals::service($name);
  }

  /**
   * {@inheritdoc}
   */
  public function settings(array $data = []): BlazySettings {
    return Internals::settings($data);
  }

  /**
   * {@inheritdoc}
   */
  public function toGrid($items, array $settings): array {
    return Grid::build($items, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function toHtml($content, $tag = 'div', $class = NULL): array {
    return Internals::toHtml($content, $tag, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function toOptions(array $options): array {
    return $this->libraries->toOptions($options);
  }

  /**
   * {@inheritdoc}
   */
  public function toSettings(
    array &$settings,
    array $data = [],
    $key = 'blazies',
    array $defaults = [],
  ): array {
    $object = Internals::reset($settings, $key, $defaults);
    if ($data) {
      // Ensures to merge to not nullify previous values.
      $object->set($data, NULL, TRUE);
    }

    if ($key == 'blazies') {
      Internals::count($object);
    }

    $settings[$key] = $object;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function verifySafely(array &$settings, $key = 'blazies', array $defaults = []) {
    return Internals::verify($settings, $key, $defaults);
  }

  /**
   * {@inheritdoc}
   */
  public function verifyItem(array &$element, $delta): void {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function view(array $data): array {
    $access   = $data['#access'] ?? FALSE;
    $entity   = $data['#entity'] ?? NULL;
    $settings = $this->toHashtag($data);
    $fallback = $data['fallback'] ?? '';

    if ($fallback && is_string($fallback)) {
      $markup   = '<span class="b-fallback">' . $fallback . '</span>';
      $fallback = ['#markup' => $markup];
    }

    if ($entity instanceof EntityInterface) {
      if (!$access && $denied = $this->denied($entity)) {
        return $denied;
      }

      $type      = $entity->getEntityTypeId();
      $langcode  = $entity->language()->getId();
      $view_mode = $settings['view_mode'] ?? 'default';
      $manager   = $this->entityTypeManager;

      // If entity has view_builder handler.
      if ($manager->hasHandler($type, 'view_builder')) {
        $builder = $manager->getViewBuilder($type);
        return $builder->view($entity, $view_mode, $langcode);
      }
      else {
        // If module implements own {entity_type}_view.
        // The "paragraphs_type" entity type did not specify a view_builder.
        // @todo remove due to being deprecated at D8.7, and after paragraphs.
        // See https://www.drupal.org/node/3033656.
        $view_hook = $type . '_view';
        if (is_callable($view_hook)) {
          return $view_hook($entity, $view_mode, $langcode);
        }
      }
    }
    return $fallback ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function withHashtag(array $data): array {
    return array_filter($data, fn($k) => strpos($k, '#') !== FALSE, ARRAY_FILTER_USE_KEY);
  }

  /**
   * Allows Blazy add return type hint to its attach() method after sub-modules.
   */
  protected function attachments(array &$load, array $attach, $blazies): void {
    // Do nothing for sub-modules to use.
  }

  /**
   * Builds an entity query.
   */
  private function buildPropertyQuery($query, array $values, $condition = 'IN'): void {
    foreach ($values as $name => $value) {
      // Cast scalars to array so we can consistently use an IN condition.
      $query->condition($name, (array) $value, $condition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hashtag(array &$data, $key = 'settings', $unset = FALSE): void {
    Internals::hashtag($data, $key, $unset);
  }

  /**
   * {@inheritdoc}
   */
  public function toHashtag(array $data, $key = 'settings', $default = []) {
    return Internals::toHashtag($data, $key, $default);
  }

}
