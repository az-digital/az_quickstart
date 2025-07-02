<?php

declare(strict_types=1);

namespace Drupal\file_mdm\Plugin\FileMetadata;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\Exception\FileNotExistsException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file_mdm\FileMetadataException;
use Drupal\file_mdm\FileMetadataInterface;
use Drupal\file_mdm\Plugin\FileMetadataPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract implementation of a base File Metadata plugin.
 */
abstract class FileMetadataPluginBase extends PluginBase implements FileMetadataPluginInterface {

  /**
   * The URI of the file.
   */
  protected string $uri;

  /**
   * The local filesystem path to the file.
   *
   * This is used to allow accessing local copies of files stored remotely, to
   * minimise remote calls and allow functions that cannot access remote stream
   * wrappers to operate locally.
   */
  protected string $localTempPath;

  /**
   * The hash used to reference the URI.
   */
  protected string $hash;

  /**
   * The metadata of the file.
   */
  protected mixed $metadata = NULL;

  /**
   * The metadata loading status.
   */
  protected int $isMetadataLoaded = FileMetadataInterface::NOT_LOADED;

  /**
   * Track if metadata has been changed from version on file.
   */
  protected bool $hasMetadataChangedFromFileVersion = FALSE;

  /**
   * Track if file metadata on cache needs update.
   */
  protected bool $hasMetadataChangedFromCacheVersion = FALSE;

  /**
   * Constructs a FileMetadataPluginBase plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager service.
   */
  final public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected readonly CacheBackendInterface $cache,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly StreamWrapperManagerInterface $streamWrapperManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.file_mdm'),
      $container->get(ConfigFactoryInterface::class),
      $container->get(StreamWrapperManagerInterface::class),
    );
  }

  public static function defaultConfiguration(): array {
    return [
      'cache' => [
        'override' => FALSE,
        'settings' => [
          'enabled' => TRUE,
          'expiration' => 172800,
          'disallowed_paths' => [],
        ],
      ],
    ];
  }

  /**
   * Gets the configuration object for this plugin.
   *
   * @param bool $editable
   *   If TRUE returns the editable configuration object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|\Drupal\Core\Config\Config
   *   The ImmutableConfig of the Config object for this plugin.
   */
  protected function getConfigObject(bool $editable = FALSE): ImmutableConfig|Config {
    $plugin_definition = $this->getPluginDefinition();
    $config_name = $plugin_definition['provider'] . '.file_metadata_plugin.' . $plugin_definition['id'];
    return $editable ? $this->configFactory->getEditable($config_name) : $this->configFactory->get($config_name);
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override main caching settings'),
      '#default_value' => $this->configuration['cache']['override'],
    ];
    $form['cache_details'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#collapsible' => FALSE,
      '#title' => $this->t('Metadata caching'),
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="' . $this->getPluginId() . '[override]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['cache_details']['settings'] = [
      '#type' => 'file_mdm_caching',
      '#default_value' => $this->configuration['cache']['settings'],
    ];

    return $form;
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @codingStandardsIgnoreStart
    $this->configuration['cache']['override'] = (bool) $form_state->getValue([$this->getPluginId(), 'override']);
    $this->configuration['cache']['settings'] = $form_state->getValue([$this->getPluginId(), 'cache_details', 'settings']);
    // @codingStandardsIgnoreEnd

    $config = $this->getConfigObject(TRUE);
    $config->set('configuration', $this->configuration);
    if ($config->getOriginal('configuration') != $config->get('configuration')) {
      $config->save();
    }
  }

  public function setUri(string $uri): static {
    if (!$uri) {
      throw new FileMetadataException('Missing $uri argument', $this->getPluginId(), __FUNCTION__);
    }
    $this->uri = $uri;
    return $this;
  }

  public function getUri(): string {
    return $this->uri;
  }

  public function setLocalTempPath(string $temp_path): static {
    $this->localTempPath = $temp_path;
    return $this;
  }

  public function getLocalTempPath(): string {
    return $this->localTempPath;
  }

  public function setHash(string $hash): static {
    $this->hash = $hash;
    return $this;
  }

  public function isMetadataLoaded(): int|bool {
    return $this->isMetadataLoaded;
  }

  public function loadMetadata(mixed $metadata): bool {
    $this->metadata = $metadata;
    $this->hasMetadataChangedFromFileVersion = TRUE;
    $this->hasMetadataChangedFromCacheVersion = TRUE;
    $this->deleteCachedMetadata();
    if ($this->metadata === NULL) {
      $this->isMetadataLoaded = FileMetadataInterface::NOT_LOADED;
    }
    else {
      $this->isMetadataLoaded = FileMetadataInterface::LOADED_BY_CODE;
      $this->saveMetadataToCache();
    }
    return (bool) $this->metadata;
  }

  public function loadMetadataFromFile(): bool {
    if (!file_exists($this->getLocalTempPath())) {
      throw new FileNotExistsException("Could not load metadata from '{$this->getLocalTempPath()}' because it does not exist.");
    }

    $this->hasMetadataChangedFromFileVersion = FALSE;
    if (($this->metadata = $this->doGetMetadataFromFile()) === NULL) {
      $this->isMetadataLoaded = FileMetadataInterface::NOT_LOADED;
      $this->deleteCachedMetadata();
    }
    else {
      $this->isMetadataLoaded = FileMetadataInterface::LOADED_FROM_FILE;
      $this->saveMetadataToCache();
    }
    return (bool) $this->metadata;
  }

  /**
   * Gets file metadata from the file at URI/local path.
   *
   * @return mixed
   *   The metadata retrieved from the file.
   *
   * @throws \Drupal\file_mdm\FileMetadataException
   *   In case there were significant errors reading from file.
   */
  abstract protected function doGetMetadataFromFile(): mixed;

  public function loadMetadataFromCache(): bool {
    $plugin_id = $this->getPluginId();
    $this->hasMetadataChangedFromFileVersion = FALSE;
    $this->hasMetadataChangedFromCacheVersion = FALSE;
    if ($this->isUriFileMetadataCacheable() !== FALSE && ($cache = $this->cache->get("hash:{$plugin_id}:{$this->hash}"))) {
      $this->metadata = $cache->data;
      $this->isMetadataLoaded = FileMetadataInterface::LOADED_FROM_CACHE;
    }
    else {
      $this->metadata = NULL;
      $this->isMetadataLoaded = FileMetadataInterface::NOT_LOADED;
    }
    return (bool) $this->metadata;
  }

  /**
   * Checks if file metadata should be cached.
   *
   * @return array|bool
   *   The caching settings array retrieved from configuration if file metadata
   *   is cacheable, FALSE otherwise.
   */
  protected function isUriFileMetadataCacheable(): array|bool {
    // Check plugin settings first, if they override general settings.
    if ($this->configuration['cache']['override']) {
      $settings = $this->configuration['cache']['settings'];
      if (!$settings['enabled']) {
        return FALSE;
      }
    }

    // Use general settings if they are not overridden by plugin.
    if (!isset($settings)) {
      $settings = $this->configFactory->get('file_mdm.settings')->get('metadata_cache');
      if (!$settings['enabled']) {
        return FALSE;
      }
    }

    // URIs without valid scheme, and temporary:// URIs are not cached.
    if (!$this->streamWrapperManager->isValidUri($this->getUri()) || $this->streamWrapperManager->getScheme($this->getUri()) === 'temporary') {
      return FALSE;
    }

    // URIs falling into disallowed paths are not cached.
    foreach ($settings['disallowed_paths'] as $pattern) {
      $p = "#^" . strtr(preg_quote($pattern, '#'), ['\*' => '.*', '\?' => '.']) . "$#i";
      if (preg_match($p, $this->getUri())) {
        return FALSE;
      }
    }

    return $settings;
  }

  public function getMetadata(mixed $key = NULL): mixed {
    if (!$this->getUri()) {
      throw new FileMetadataException("No URI specified", $this->getPluginId(), __FUNCTION__);
    }
    if (!$this->hash) {
      throw new FileMetadataException("No hash specified", $this->getPluginId(), __FUNCTION__);
    }
    if ($this->metadata === NULL) {
      // Metadata has not been loaded yet. Try loading it from cache first.
      $this->loadMetadataFromCache();
    }
    if ($this->metadata === NULL && $this->isMetadataLoaded !== FileMetadataInterface::LOADED_FROM_FILE) {
      // Metadata has not been loaded yet. Try loading it from file if URI is
      // defined and a read attempt was not made yet.
      $this->loadMetadataFromFile();
    }
    return $this->doGetMetadata($key);
  }

  /**
   * Gets a metadata element.
   *
   * @param mixed $key
   *   A key to determine the metadata element to be returned. If NULL, the
   *   entire metadata will be returned.
   *
   * @return mixed
   *   The value of the element specified by $key. If $key is NULL, the entire
   *   metadata. If no metadata is available, return NULL.
   */
  abstract protected function doGetMetadata(mixed $key = NULL): mixed;

  public function setMetadata(mixed $key, mixed $value): bool {
    if ($key === NULL) {
      throw new FileMetadataException("No metadata key specified for file at '{$this->getUri()}'", $this->getPluginId(), __FUNCTION__);
    }
    if (!$this->metadata && !$this->getMetadata()) {
      throw new FileMetadataException("No metadata loaded for file at '{$this->getUri()}'", $this->getPluginId(), __FUNCTION__);
    }
    if ($this->doSetMetadata($key, $value)) {
      $this->hasMetadataChangedFromFileVersion = TRUE;
      if ($this->isMetadataLoaded === FileMetadataInterface::LOADED_FROM_CACHE) {
        $this->hasMetadataChangedFromCacheVersion = TRUE;
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Sets a metadata element.
   *
   * @param mixed $key
   *   A key to determine the metadata element to be changed.
   * @param mixed $value
   *   The value to change the metadata element to.
   *
   * @return bool
   *   TRUE if metadata was changed successfully, FALSE otherwise.
   */
  abstract protected function doSetMetadata(mixed $key, mixed $value): bool;

  public function removeMetadata(mixed $key): bool {
    if ($key === NULL) {
      throw new FileMetadataException("No metadata key specified for file at '{$this->getUri()}'", $this->getPluginId(), __FUNCTION__);
    }
    if (!$this->metadata && !$this->getMetadata()) {
      throw new FileMetadataException("No metadata loaded for file at '{$this->getUri()}'", $this->getPluginId(), __FUNCTION__);
    }
    if ($this->doRemoveMetadata($key)) {
      $this->hasMetadataChangedFromFileVersion = TRUE;
      if ($this->isMetadataLoaded === FileMetadataInterface::LOADED_FROM_CACHE) {
        $this->hasMetadataChangedFromCacheVersion = TRUE;
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Removes a metadata element.
   *
   * @param mixed $key
   *   A key to determine the metadata element to be removed.
   *
   * @return bool
   *   TRUE if metadata was removed successfully, FALSE otherwise.
   */
  abstract protected function doRemoveMetadata(mixed $key): bool;

  public function isSaveToFileSupported(): bool {
    return FALSE;
  }

  public function saveMetadataToFile(): bool {
    if (!$this->isSaveToFileSupported()) {
      throw new FileMetadataException('Write metadata to file is not supported', $this->getPluginId(), __FUNCTION__);
    }
    if ($this->metadata === NULL) {
      return FALSE;
    }
    if ($this->hasMetadataChangedFromFileVersion) {
      // Clears cache so that next time metadata will be fetched from file.
      $this->deleteCachedMetadata();
      return $this->doSaveMetadataToFile();
    }
    return FALSE;
  }

  /**
   * Saves metadata to file at URI.
   *
   * @return bool
   *   TRUE if metadata was saved successfully, FALSE otherwise.
   */
  protected function doSaveMetadataToFile(): bool {
    return FALSE;
  }

  public function saveMetadataToCache(array $tags = []): bool {
    if ($this->metadata === NULL) {
      return FALSE;
    }
    if (($cache_settings = $this->isUriFileMetadataCacheable()) === FALSE) {
      return FALSE;
    }
    if ($this->isMetadataLoaded !== FileMetadataInterface::LOADED_FROM_CACHE || $this->hasMetadataChangedFromCacheVersion) {
      $tags = Cache::mergeTags($tags, $this->getConfigObject()->getCacheTags());
      $tags = Cache::mergeTags($tags, $this->configFactory->get('file_mdm.settings')->getCacheTags());
      $expire = $cache_settings['expiration'] === -1 ? Cache::PERMANENT : time() + $cache_settings['expiration'];
      $this->cache->set("hash:{$this->getPluginId()}:{$this->hash}", $this->getMetadataToCache(), $expire, $tags);
      $this->hasMetadataChangedFromCacheVersion = FALSE;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Gets metadata to save to cache.
   *
   * @return mixed
   *   The metadata to be cached.
   */
  protected function getMetadataToCache(): mixed {
    return $this->metadata;
  }

  public function deleteCachedMetadata(): bool {
    if ($this->isUriFileMetadataCacheable() === FALSE) {
      return FALSE;
    }
    $plugin_id = $this->getPluginId();
    $this->cache->delete("hash:{$plugin_id}:{$this->hash}");
    $this->hasMetadataChangedFromCacheVersion = FALSE;
    return TRUE;
  }

}
