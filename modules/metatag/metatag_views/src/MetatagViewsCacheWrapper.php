<?php

namespace Drupal\metatag_views;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\metatag_views\Plugin\views\display_extender\MetatagDisplayExtender;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This class wraps a Views cache plugin.
 */
class MetatagViewsCacheWrapper extends CachePluginBase {

  /**
   * The cache type we are interested in.
   */
  const RESULTS = 'results';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\views\Plugin\views\cache\CachePluginBase
   */
  protected $plugin;

  /**
   * MetatagViewsCacheWrapper constructor.
   *
   * @param \Drupal\views\Plugin\views\cache\CachePluginBase $plugin
   *   The cache plugin being wrapped.
   */
  public function __construct(CachePluginBase $plugin) {
    $this->plugin = $plugin;
  }

  /**
   * Create a new object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container object.
   * @param array $configuration
   *   The configuration passed in.
   * @param string $plugin_id
   *   The ID the new plugin instance.
   * @param string $plugin_definition
   *   The configuration used on this plugin instance.
   *
   * @return \Drupal\metatag_views\MetatagViewsCacheWrapper
   *   An instance of this class.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return CachePluginBase::create($container, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function cacheSet($type) {
    if ($type === self::RESULTS) {
      $plugin = $this->plugin;
      $view = $plugin->view;
      $data = [
        'result' => $plugin->prepareViewResult($view->result),
        'total_rows' => $view->total_rows ?? 0,
        'current_page' => $view->getCurrentPage(),
        'first_row_tokens' => MetatagDisplayExtender::getFirstRowTokensFromStylePlugin($view),
      ];
      $cache_set_max_age = $this->cacheSetMaxAge('results');
      $expire = ($cache_set_max_age === Cache::PERMANENT) ? Cache::PERMANENT : (int) $view->getRequest()->server->get('REQUEST_TIME') + $cache_set_max_age;
      \Drupal::cache($plugin->resultsBin)
        ->set($plugin->generateResultsKey(), $data, $expire, $plugin->getCacheTags());
    }
    else {
      $this->plugin->cacheSet($type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cacheGet($type) {
    switch ($type) {
      case self::RESULTS:
        $cutoff = $this->plugin->cacheExpire($type);
        // Values to set: $view->result, $view->total_rows,
        // $view->current_page and pass row tokens to metatag display extender.
        if ($cache = \Drupal::cache($this->plugin->resultsBin)->get($this->plugin->generateResultsKey())) {
          if (!$cutoff || $cache->created > $cutoff) {
            $view = $this->plugin->view;
            $view->result = $cache->data['result'];
            // Load entities for each result.
            $view->query->loadEntities($view->result);
            $view->total_rows = $cache->data['total_rows'];
            $view->setCurrentPage($cache->data['current_page']);
            $extenders = $view->getDisplay()->getExtenders();
            if (isset($extenders['metatag_display_extender'])) {
              /** @var \Drupal\metatag_views\Plugin\views\display_extender\MetatagDisplayExtender $extenders['metatag_display_extender'] */
              $extenders['metatag_display_extender']->setFirstRowTokens($cache->data['first_row_tokens']);
            }
            return TRUE;
          }
        }
        return FALSE;

      default:
        return $this->plugin->cacheGet($type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResultsKey() {
    return $this->plugin->getResultsKey();
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->plugin->summaryTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function cacheFlush() {
    $this->plugin->cacheFlush();
  }

  /**
   * {@inheritdoc}
   */
  public function postRender(&$output) {
    $this->plugin->postRender($output);
  }

  /**
   * {@inheritdoc}
   */
  public function generateResultsKey() {
    return $this->plugin->generateResultsKey();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->plugin->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->plugin->getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public function alterCacheMetadata(CacheableMetadata $cache_metadata) {
    $this->plugin->alterCacheMetadata($cache_metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function getRowCacheTags(ResultRow $row) {
    return $this->plugin->getRowCacheTags($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getRowCacheKeys(ResultRow $row) {
    return $this->plugin->getRowCacheKeys($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getRowId(ResultRow $row) {
    return $this->plugin->getRowId($row);
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    $this->plugin->init($view, $display, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function filterByDefinedOptions(array &$storage) {
    $this->plugin->filterByDefinedOptions($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function unpackOptions(&$storage, $options, $definition = NULL, $all = TRUE, $check = TRUE) {
    $this->plugin->unpackOptions($storage, $options, $definition, $all, $check);
  }

  /**
   * {@inheritdoc}
   */
  public function destroy() {
    $this->plugin->destroy();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $this->plugin->buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array|string {
    return CachePluginBase::trustedCallbacks();
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $this->plugin->validateOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $this->plugin->submitOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->plugin->query();
  }

  /**
   * {@inheritdoc}
   */
  public function themeFunctions() {
    return $this->plugin->themeFunctions();
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return $this->plugin->validate();
  }

  /**
   * {@inheritdoc}
   */
  public function pluginTitle() {
    return $this->plugin->pluginTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function usesOptions() {
    return $this->plugin->usesOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function globalTokenReplace($string = '', array $options = []) {
    return $this->plugin->globalTokenReplace($string, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableGlobalTokens($prepared = FALSE, array $types = []) {
    return $this->plugin->getAvailableGlobalTokens($prepared, $types);
  }

  /**
   * {@inheritdoc}
   */
  public function globalTokenForm(&$form, FormStateInterface $form_state) {
    $this->plugin->globalTokenForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderAddFieldsetMarkup(array $form): array {
    return CachePluginBase::preRenderAddFieldsetMarkup($form);
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderFlattenData($form): array {
    return CachePluginBase::preRenderFlattenData($form);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->plugin->calculateDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->plugin->getProvider();
  }

  /**
   * {@inheritdoc}
   */
  public static function queryLanguageSubstitutions(): array {
    return CachePluginBase::queryLanguageSubstitutions();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseId() {
    return $this->plugin->getBaseId();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeId() {
    return $this->plugin->getDerivativeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return $this->plugin->getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigurable() {
    return $this->plugin->isConfigurable();
  }

  /**
   * {@inheritdoc}
   */
  public function setStringTranslation(TranslationInterface $translation) {
    return $this->plugin->setStringTranslation($translation);
  }

  /**
   * {@inheritdoc}
   */
  public function setMessenger(MessengerInterface $messenger): MessengerInterface {
    $this->plugin->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public function messenger() {
    return $this->plugin->messenger();
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    return $this->plugin->$name;
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    $this->plugin->$name = $value;
  }

}
