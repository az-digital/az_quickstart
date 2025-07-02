<?php

namespace Drupal\blazy;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Provides base blazy utility methods.
 */
interface BlazyInterface extends ContainerInjectionInterface {

  /**
   * Returns the app root.
   *
   * @return string
   *   The app root.
   */
  public function root();

  /**
   * Returns the entity repository service.
   *
   * @return \Drupal\Core\Entity\EntityRepositoryInterface
   *   The entity repository.
   */
  public function entityRepository();

  /**
   * Returns the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function entityTypeManager();

  /**
   * Returns the libraries service.
   *
   * @return \Drupal\blazy\Asset\LibrariesInterface
   *   The libraries service.
   */
  public function libraries();

  /**
   * Returns the module handler service.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function moduleHandler();

  /**
   * Returns the renderer service.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function renderer();

  /**
   * Returns the config factory service.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  public function configFactory();

  /**
   * Returns the cache service.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The app root.
   */
  public function cache();

  /**
   * Returns the language manager service.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  public function languageManager();

  /**
   * Retrieves the currently active route match object.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The currently active route match object.
   */
  public function routeMatch();

  /**
   * Returns any config, or keyed by the $setting_name.
   *
   * @param string $key
   *   The setting key.
   * @param string $group
   *   The settings object group key.
   *
   * @return mixed
   *   The config value(s), or empty.
   */
  public function config($key = NULL, $group = 'blazy.settings');

  /**
   * Returns any config by the $group, alternative to ugly NULL key.
   *
   * @param string $group
   *   The settings object group key.
   *
   * @return array
   *   The config values, or empty array.
   */
  public function configMultiple($group = 'blazy.settings'): array;

  /**
   * Returns any config based on "self::$namespace.settings" convension.
   *
   * @param string $key
   *   The setting key.
   *
   * @return mixed
   *   The config value(s), or empty.
   */
  public function myConfig($key = NULL);

  /**
   * Returns any config by "self::$namespace.settings" convension.
   *
   * @return array
   *   The config values, or empty array.
   */
  public function myConfigMultiple(): array;

  /**
   * Implements hook_config_schema_info_alter().
   */
  public function configSchemaInfoAlter(
    array &$definitions,
    $formatter = 'blazy_base',
    array $settings = [],
  ): void;

  /**
   * Alias for Internals::denied() for sub-modules.
   *
   * @param object $entity
   *   The expected entity interface object to check for its view access.
   *
   * @return array
   *   The renderable array of the minimal denial info, or empty if accessible.
   */
  public function denied($entity): array;

  /**
   * Returns the entity query object for this entity type.
   *
   * @param string $type
   *   The entity type.
   * @param string $conjunction
   *   The operator for the query.
   * @param bool $access
   *   Whether checked with access, or not.
   *
   * @return object
   *   The entity query object.
   */
  public function entityQuery($type, $conjunction = 'AND', $access = TRUE);

  /**
   * Returns cached data identified by its cache ID, normally alterable data.
   *
   * @param string $cid
   *   The cache ID, als used for the hook_alter.
   * @param array $data
   *   The given data to cache, accepting empty array to trigger hook_alter.
   * @param array $info
   *   The optional info containing:
   *   - reset: Whether to bypass cache.
   *   - alter: key for the hook_alter, otherwise $cid.
   *   - context: additional data or contextual info for the hook_alter.
   *
   * @return array
   *   The cache data.
   */
  public function getCachedData(
    $cid,
    array $data = [],
    array $info = [],
  ): array;

  /**
   * Returns cached options identified by its cache ID, normally alterable data.
   *
   * @param string $cid
   *   The cache ID, als used for the hook_alter.
   * @param array $data
   *   The given data to cache, accepting empty array to trigger hook_alter.
   * @param bool $as_options
   *   Whether to use it for select options.
   * @param array $info
   *   The optional info containing:
   *   - reset: Whether to bypass cache,
   *   - alter: key for the hook_alter, otherwise $cid.
   *   - context: additional data or contextual info for the hook_alter.
   *
   * @return array
   *   The cache data/ options.
   */
  public function getCachedOptions(
    $cid,
    array $data = [],
    $as_options = TRUE,
    array $info = [],
  ): array;

  /**
   * Returns the cache metadata common for all blazy-related modules.
   *
   * @param array $build
   *   The provided build info.
   *
   * @return array
   *   The cache metadata.
   */
  public function getCacheMetadata(array $build): array;

  /**
   * Returns available entities for select options.
   *
   * To get all entities of an entity_type, use self::loadMultiple() instead.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   The entity types
   */
  public function getEntityAsOptions($entity_type): array;

  /**
   * Alias for Internals::getHtmlId() to get the trusted HTML ID.
   *
   * @param string $name
   *   The module name.
   * @param string $id
   *   The optional hardcoded ID.
   *
   * @return string
   *   The static CSS ID.
   */
  public function getHtmlId($name = 'blazy', $id = ''): string;

  /**
   * Alias for LibrariesInterface::getPath() to get libraries path.
   *
   * A few libraries have inconsistent namings, given different packagers:
   *   - splide x splidejs--splide
   *   - slick x slick-carousel
   *   - DOMPurify x dompurify, etc.
   *
   * @param array|string $name
   *   The library name(s), e.g.: 'colorbox', or ['DOMPurify', 'dompurify'].
   * @param bool $base_path
   *   Whether to prefix it with an a base path.
   *
   * @return string|null
   *   The first found path to the library, or NULL if not found.
   */
  public function getLibrariesPath($name, $base_path = FALSE): ?string;

  /**
   * Alias for Path::getPath() to get module or theme path.
   *
   * @param string $type
   *   The object type, can be module or theme.
   * @param string $name
   *   The object name.
   * @param bool $absolute
   *   Whether to return an absolute path.
   *
   * @return string|null
   *   The path to object, or NULL if not found.
   */
  public function getPath($type, $name, $absolute = FALSE): ?string;

  /**
   * Returns a shortcut for entity type storage.
   *
   * @param string $type
   *   The entity type.
   *
   * @return object|null
   *   The entity type storage object.
   */
  public function getStorage($type = 'media');

  /**
   * A shortcut for EntityRepositoryInterface::getTranslationFromContext().
   *
   * @param object $object
   *   The entity object.
   * @param string $langcode
   *   (optional) The language of the current context. Defaults to the current
   *   content language.
   *
   * @return object
   *   The translated entity, if available.
   */
  public function getTranslatedEntity($object, $langcode = NULL);

  /**
   * Alias for Grid::attributes().
   *
   * @param array $attrs
   *   The container attributes to add into .blazy, normally #attributes.
   * @param array $settings
   *   The settings defining the grids.
   */
  public function gridAttributes(array &$attrs, array $settings): void;

  /**
   * Alias for Grid::checkAttributes().
   *
   * @param array $attrs
   *   The container attributes to add into .grid, normally #attributes.
   * @param array $content_attrs
   *   The content attributes, if any to add into .grid__content.
   * @param object $blazies
   *   The settings.blazies object.
   * @param bool $root
   *   Whether to apply it for the root container, or item attributes.
   */
  public function gridCheckAttributes(
    array &$attrs,
    array &$content_attrs,
    $blazies,
    $root = FALSE,
  ): void;

  /**
   * Alias for Grid::itemAttributes().
   *
   * This method + self::initGrid() allows you to build grids with any themes
   * having just DIV > DIVs or UL > LIs like theme_field(), media_library, etc.,
   * without re-building it like self::toGrid() such as seen at Blazy
   * formatters, Views, Optionset forms, IO Browser/Slick Browser by simply
   * modifying existing attributes. The required:
   *   - $settings contains delta, count + self::initGrid() settings.
   *   - Delta is updated in the loop via blazies or directly at child settings.
   *
   * @param array $attrs
   *   The container attributes to add into .grid, normally #wrapper_attributes
   *   for form items.
   * @param array $content_attrs
   *   The content attributes, if any to add into .grid__content. Bootstrap
   *   CSS .card/ .well is best here.
   * @param array $settings
   *   The settings grabbed from self::initGrid() returned settings.
   *
   * @see \Drupal\blazy\Theme\Grid
   * @see \Drupal\io_browser\IoBrowserWidget::mediaLibraryItem()
   * @see \Drupal\blazy\Form\BlazyAdminBase
   * @see \Drupal\blazy\Form\BlazyEntityFormBase
   */
  public function gridItemAttributes(
    array &$attrs,
    array &$content_attrs,
    array $settings,
  ): void;

  /**
   * Import a config entity, and save it into database.
   *
   * @param array $options
   *   Containing:
   *     - module, the module name where config to be imported is stored.
   *     - basename, file name without .yml extension: slick.optionset.nav, etc.
   *     - folder, whether install, or optional.
   */
  public function import(array $options): void;

  /**
   * Initialize Grid at any containers with DIV > DIVs without passing contents.
   *
   * @param array $options
   *   The options:
   *   - count, int: total items. Default: 1, must be overriden.
   *   - grid, string: 4x2 2x2 3x4, etc. Default: 6x1 (two columns).
   *   - grid_medium, int: 1-12 due to pure CSS. Default: 2.
   *   - grid_small, int: at max 2 from 1-12. Default: 1.
   *   - classes, string|array: classes to merge. Default: gapless + is_form.
   *   - gapless, bool: remove default gap 15px. Default: TRUE.
   *   - is_form, bool: for forms, requires blazy/admin.grid. Default: TRUE.
   *   - style, string: column, flex, grid, nativegrid. Default: nativegrid.
   *   - blazies, BlazySettings: If none, will create an empty object.
   *
   * @requires:
   *  - self::gridItemAttributes() for individual items.
   *  - Library attachments, any will do:
   *      - '#attached' => blazy()->attach($settings), at the container level,
   *        or merge with the existing ones.
   *      - blazy/nativegrid for frontend, or blazy/admin or blazy/admin.grid
   *        libraries for form usages.
   *      - `hook_blazy_settings_alter`: $blazies->set('libs.LIBRARY_NAME');
   *      See \Drupal\blazy\BlazyDefault::grids(), or blazy.libraries.yml, and
   *      load it `blazy/LIBRARY_NAME`.
   *
   * @return array
   *   - attributes: to apply/ merge into existing containers,
   *   - settings: to use for self::gridItemAttributes() last parameter.
   */
  public function initGrid(array $options): array;

  /**
   * Returns a shortcut for loading an entity: image_style, slick, etc.
   *
   * @param string $id
   *   The entity ID.
   * @param string $type
   *   The entity type, can be configuration object like blazy.settings.
   *
   * @return mixed
   *   The entity, or config values: string, bool, etc.
   */
  public function load($id, $type = 'image_style');

  /**
   * Returns a shortcut for loading multiple entities.
   *
   * @param string $type
   *   The entity type.
   * @param array|string $ids
   *   The entity ID(s) as filters.
   *
   * @return array
   *   The entities, or empty array.
   */
  public function loadMultiple($type = 'image_style', $ids = NULL): array;

  /**
   * Returns a shortcut for loading entity by its properties.
   *
   * The only difference from EntityStorageBase::loadByProperties() is the
   * explicit access TRUE specific for content entities, FALSE config ones.
   *
   * @see https://www.drupal.org/node/3201242
   */
  public function loadByProperties(
    array $values,
    $type = 'file',
    $access = TRUE,
    $conjunction = 'AND',
    $condition = 'IN',
  ): array;

  /**
   * Returns a single entity object by a property.
   *
   * @param string $porperty
   *   The entity porperty.
   * @param string|array $value
   *   The porperty value(s).
   * @param string $type
   *   The entity type.
   *
   * @return object|null
   *   The entity, else NULL.
   */
  public function loadByProperty($porperty, $value, $type): ?object;

  /**
   * Returns a shortcut for loading entity by its UUID.
   *
   * @param string $uuid
   *   The entity UUID.
   * @param string $type
   *   The entity type.
   *
   * @return object|null
   *   The entity, else NULL.
   */
  public function loadByUuid($uuid, $type = 'file'): ?object;

  /**
   * Provides a shortcut to parse the markdown string for better hook_help().
   *
   * @param string $string
   *   The markdown string.
   * @param bool $help
   *   True for admin help page.
   * @param bool $sanitize
   *   True, if the text should be sanitized.
   *
   * @return string
   *   The HTML string.
   */
  public function markdown($string, $help = TRUE, $sanitize = TRUE): string;

  /**
   * Merge data with a new one with an optional key.
   *
   * The parameters are reversed from regular merging methods.
   *
   * @param array $data
   *   A replacing array data, or defaults.
   * @param array $element
   *   A replaced element to be prepended to data, expected to be empty.
   * @param string $key
   *   An optional $element key.
   *
   * @return array
   *   The merged array.
   */
  public function merge(array $data, array $element, $key = NULL): array;

  /**
   * Merge multiple unique BlazySettings objects.
   *
   * It doesn't merge `blazies` with `gridstacks`, just old with new data of the
   * same instance.
   *
   * @param array|string $keys
   *   A string, or array of keys, e.g.: ['blazies', 'gridstacks', 'slicks'].
   * @param array $defaults
   *   An array containing old data.
   * @param array $configs
   *   An array containing new data.
   *
   * @return array
   *   The merged configuration inside $configs.
   */
  public function mergeSettings($keys, array $defaults, array $configs): array;

  /**
   * A D9-12 compat \Drupal\Core\Render\RendererInterface::renderInIsolation().
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  public function renderInIsolation(array &$elements);

  /**
   * A wrapper for \Drupal\Core\Extension\ModuleHandlerInterface::moduleExists.
   *
   * @param string $name
   *   The module name.
   *
   * @return bool
   *   Whether the module exists, or not.
   */
  public function moduleExists($name): bool;

  /**
   * An alias for Internals::service().
   *
   * @param string $name
   *   The service name.
   *
   * @return object|null
   *   The service if already initialized, or NULL.
   */
  public function service($name): ?object;

  /**
   * An alias for Internals::settings().
   *
   * @param array $data
   *   The optional initial data array.
   *
   * @return \Drupal\blazy\BlazySettings
   *   The BlazySettings object.
   */
  public function settings(array $data = []): BlazySettings;

  /**
   * Returns items wrapped by theme_item_list(), can be a grid, or plain list.
   *
   * Alias for Blazy::grid() for sub-modules and easy organization later.
   * Unlike self::initGrid(), this requires item contents to process.
   *
   * @param array|\Generator $items
   *   The grid items.
   * @param array $settings
   *   The given settings.
   *
   * @return array
   *   The modified array of grid items.
   *
   * @see \Drupal\blazy\BlazyManager::preRenderBuild()
   * @see \Drupal\slick\SlickManager::buildGridItem()
   * @see \Drupal\slick_ui\Controller\SlickListBuilder::render()
   * @see \Drupal\splide\SplideManager::buildGridItem()
   * @see \Drupal\splide_ui\Controller\SplideListBuilder::render()
   */
  public function toGrid($items, array $settings): array;

  /**
   * Returns the common content item.
   *
   * @param array|string|null $content
   *   The content. If string will be put into #markup element.
   * @param string $tag
   *   The HTML tag.
   * @param string|array $class
   *   If provided, will be wrapped with #html_tag, else returned as is.
   *   It can a string of class, or an array of attributes.
   *
   * @return array
   *   The content to be wrapped with #html_tag, or as is if no class provided.
   */
  public function toHtml($content, $tag = 'div', $class = NULL): array;

  /**
   * Returns escaped options.
   *
   * @param array $options
   *   The given options.
   *
   * @return array
   *   The modified array of options suitable for select options.
   */
  public function toOptions(array $options): array;

  /**
   * Reset blazies object with the optional added data.
   *
   * @param array $settings
   *   The settings to add data.
   * @param array $data
   *   The data to be added into $key object.
   * @param string $key
   *   The key in the settings object.
   * @param array $defaults
   *   The defaults containing object other than blazies, if not initialized.
   *
   * @return array
   *   The modified settings.
   */
  public function toSettings(
    array &$settings,
    array $data = [],
    $key = 'blazies',
    array $defaults = [],
  ): array;

  /**
   * Verifies BlazySettings exists since few may be called outside the workflow.
   *
   * @param array $settings
   *   The settings being modified.
   * @param string $key
   *   The object key within the settings, normally stupid plural keys: blazies,
   *   gridstacks, masons, slicks, splides, etc. just to stay unique.
   *   If extending this class, it is imperative to leave it as is, and only
   *   override it within the method body, so to keep the default integrity.
   *   Non-default key is only useful when calling it anywhere, not extending.
   * @param array $defaults
   *   The default values to initialize the object.
   *
   * @return object
   *   The \Drupal\blazy\BlazySettings object identified by $key.
   *   We do not add return type BlazySettings for easy relocation at 3.x.
   */
  public function verifySafely(array &$settings, $key = 'blazies', array $defaults = []);

  /**
   * Verifies item settings.
   *
   * @param array $element
   *   The element being modified containing: #settings, #item, #entity, etc.
   * @param int $delta
   *   The current item delta.
   */
  public function verifyItem(array &$element, $delta): void;

  /**
   * A wrapper for the entity view aka vanilla view with access check.
   *
   * @param array $data
   *   The data containing: #entity, #settings, and fallback (string|array).
   *
   * @return array
   *   The renderable array of the view builder, fallback, or empty array.
   *
   * @see https://www.drupal.org/node/3033656
   */
  public function view(array $data): array;

  /**
   * Filter out renderable array from an array.
   *
   * @param array $data
   *   The source data.
   *
   * @return array
   *   The array without renderable.
   */
  public function withHashtag(array $data): array;

  /**
   * A helper to gradually convert things to #things to avoid render error.
   *
   * This helper is temporary, and should only be used for BC purposes. This
   * should be finally deprecated and put out of service once migrations are
   * done at 3.x.
   *
   * @param array $data
   *   The source data being modified.
   * @param string $key
   *   The given key.
   * @param bool $unset
   *   Whether to unset original data, default to FALSE till fully migrated.
   */
  public function hashtag(array &$data, $key = 'settings', $unset = FALSE): void;

  /**
   * A helper to gradually convert things to #things to avoid render error.
   *
   * This helper is temporary, and should only be used for BC purposes. This
   * should be finally deprecated and put out of service once migrations are
   * done at 3.x.
   *
   * @param array $data
   *   The source data.
   * @param string $key
   *   The given key.
   * @param array|bool|null|string $default
   *   The default value.
   *
   * @return mixed
   *   The checked value.
   */
  public function toHashtag(array $data, $key = 'settings', $default = []);

}
