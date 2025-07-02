<?php

namespace Drupal\xmlsitemap\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for XML Sitemap.
 */
class XmlSitemapCommands extends DrushCommands {

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module_handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Default database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * XmlSitemapCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module_handler service.
   * @param \Drupal\Core\Database\Connection $connection
   *   Default database connection.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ModuleHandlerInterface $moduleHandler, Connection $connection) {
    parent::__construct();
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->connection = $connection;
  }

  /**
   * Regenerate the XML Sitemap files.
   *
   * @validate-module-enabled xmlsitemap
   *
   * @command xmlsitemap:regenerate
   * @aliases xmlsitemap-regenerate
   */
  public function regenerate() {
    $batch = xmlsitemap_regenerate_batch();
    batch_set($batch);
    drush_backend_batch_process();
  }

  /**
   * Dump and re-process all possible XML Sitemap data, then regenerate files.
   *
   * @validate-module-enabled xmlsitemap
   *
   * @command xmlsitemap:rebuild
   * @aliases xmlsitemap-rebuild
   */
  public function rebuild() {
    // Build a list of rebuildable link types.
    $rebuild_types = xmlsitemap_get_rebuildable_link_types();
    if (empty($rebuild_types)) {
      $this->logger()->warning(dt('No link types are rebuildable.'));
    }

    $batch = xmlsitemap_rebuild_batch($rebuild_types, TRUE);
    batch_set($batch);
    drush_backend_batch_process();
  }

  /**
   * Process un-indexed XML Sitemap links.
   *
   * @param array $options
   *   An associative array of options obtained from cli, aliases, config, etc.
   *
   * @option limit
   *   The limit of links of each type to process.
   * @validate-module-enabled xmlsitemap
   *
   * @command xmlsitemap:index
   * @aliases xmlsitemap-index
   */
  public function index(array $options = ['limit' => NULL]) {
    $limit = (int) ($options['limit'] ?: $this->configFactory->get('xmlsitemap.settings')->get('batch_limit'));
    $count_before = $this->connection->select('xmlsitemap', 'x')->countQuery()->execute()->fetchField();

    $this->moduleHandler->invokeAll('xmlsitemap_index_links', ['limit' => $limit]);

    $count_after = $this->connection->select('xmlsitemap', 'x')->countQuery()->execute()->fetchField();

    if ($count_after == $count_before) {
      $this->output()->writeln(dt('No new XML Sitemap links to index.'));
    }
    else {
      $this->output()->writeln(dt('Indexed @count new XML Sitemap links.', ['@count' => $count_after - $count_before]));
    }
  }

}
