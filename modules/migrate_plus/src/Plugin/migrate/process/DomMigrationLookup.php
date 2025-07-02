<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * String replacements on a source dom based on migration lookup.
 *
 * Meant to be used after dom process plugin.
 *
 * Available configuration keys:
 * - mode: What to modify. Possible values:
 *   - attribute: One element attribute.
 * - xpath: XPath query expression that will produce the \DOMNodeList to walk.
 * - attribute_options: A map of options related to the attribute mode. Required
 *   when mode is attribute. The keys can be:
 *   - name: Name of the attribute to match and modify.
 * - search: Regular expression to use. It should contain at least one
 *   parenthesized subpattern which will be used as the ID passed to
 *   migration_lookup process plugin.
 * - replace: Default value to use for replacements on migrations, if not
 *   specified on the migration. It should contain the '[mapped-id]' string
 *   where the looked-up migration value will be placed.
 * - migrations: A map of options indexed by migration machine name. Possible
 *   option values are:
 *   - replace: See replace option lines above.
 * - no_stub: If TRUE, then do not create stub entities during migration lookup.
 *   Optional, defaults to TRUE.
 *
 * Example:
 *
 * @code
 * process:
 *   'body/value':
 *     -
 *       plugin: dom
 *       method: import
 *       source: 'body/0/value'
 *     -
 *       plugin: dom_migration_lookup
 *       mode: attribute
 *       xpath: '//a'
 *       attribute_options:
 *         name: href
 *       search: '@/user/(\d+)@'
 *       replace: '/user/[mapped-id]'
 *       migrations:
 *         users:
 *           replace: '/user/[mapped-id]'
 *         people:
 *           replace: '/people/[mapped-id]'
 *     -
 *       plugin: dom
 *       method: export
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "dom_migration_lookup"
 * )
 */
class DomMigrationLookup extends DomStrReplace implements ContainerFactoryPluginInterface {

  protected MigrationInterface $migration;
  protected MigratePluginManagerInterface $processPluginManager;

  /**
   * Parameters passed to transform method, except the first, value.
   *
   * This helps to pass values to another process plugin.
   */
  protected array $transformParameters = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigratePluginManagerInterface $process_plugin_manager) {
    $configuration += ['no_stub' => TRUE];
    $default_replace_missing = empty($configuration['replace']);
    if ($default_replace_missing) {
      $configuration['replace'] = 'prevent-requirement-fail';
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if ($default_replace_missing) {
      unset($this->configuration['replace']);
    }
    $this->migration = $migration;
    $this->processPluginManager = $process_plugin_manager;
    if (empty($this->configuration['migrations'])) {
      throw new InvalidPluginDefinitionException(
        $this->getPluginId(),
        "Configuration option 'migration' is required."
      );
    }
    if (!is_array($this->configuration['migrations'])) {
      throw new InvalidPluginDefinitionException(
        $this->getPluginId(),
        "Configuration option 'migration' should be a keyed array."
      );
    }
    // Add missing values if possible.
    $default_replace = $this->configuration['replace'] ?? NULL;
    foreach ($this->configuration['migrations'] as $migration_name => $configuration_item) {
      if (!empty($configuration_item['replace'])) {
        continue;
      }
      if (is_null($default_replace)) {
        throw new InvalidPluginDefinitionException(
          $this->getPluginId(),
          "Please define either a global replace for all migrations, or a specific one for 'migrations.$migration_name'."
        );
      }
      $this->configuration['migrations'][$migration_name]['replace'] = $default_replace;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): \DOMDocument {
    $this->init($value, $destination_property);
    $this->transformParameters = [
      'migrate_executable' => $migrate_executable,
      'row' => $row,
      'destination_property' => $destination_property,
    ];

    foreach ($this->xpath->query($this->configuration['xpath']) as $html_node) {
      $subject = $this->getSubject($html_node);
      if (empty($subject)) {
        // Could not find subject, skip processing.
        continue;
      }
      $search = $this->getSearch();
      if (!preg_match($search, $subject, $matches)) {
        // No match found, skip processing.
        continue;
      }
      $id = $matches[1];
      // Walk through defined migrations looking for a map.
      foreach ($this->configuration['migrations'] as $migration_name => $configuration) {
        $mapped_id = $this->migrationLookup($id, $migration_name);
        if (!is_null($mapped_id)) {
          // Not using getReplace(), since this implementation depends on the
          // migration.
          $replace = str_replace('[mapped-id]', $mapped_id, $configuration['replace']);
          $this->doReplace($html_node, $search, $replace, $subject);
          break;
        }
      }
    }

    return $this->document;
  }

  /**
   * {@inheritdoc}
   */
  protected function doReplace(\DOMElement $html_node, $search, $replace, $subject): void {
    $new_subject = preg_replace($search, $replace, $subject);
    $this->postReplace($html_node, $new_subject);
  }

  /**
   * Lookup the migration mapped ID on one migration.
   *
   * @param mixed $id
   *   The ID to search with migration_lookup process plugin.
   * @param string $migration_name
   *   The migration to look into machine name.
   *
   * @return string|null
   *   The found mapped ID, or NULL if not found on the provided migration.
   */
  protected function migrationLookup($id, $migration_name): ?string {
    $mapped_id = NULL;
    $parameters = [
      $id,
      $this->transformParameters['migrate_executable'],
      $this->transformParameters['row'],
      $this->transformParameters['destination_property'],
    ];
    $plugin_configuration = [
      'migration' => $migration_name,
      'no_stub' => $this->configuration['no_stub'],
    ];
    $migration_lookup_plugin = $this->processPluginManager
      ->createInstance('migration_lookup', $plugin_configuration, $this->migration);
    $mapped_id = call_user_func_array([$migration_lookup_plugin, 'transform'], $parameters);
    return $mapped_id;
  }

}
