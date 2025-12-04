<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * File Entity Item source plugin.
 *
 * @deprecated in az_quickstart:3.2.0 and is removed from az_quickstart:4.0.0.
 * There is no replacement.
 * 
 * Available configuration keys:
 * - type: (optional) If supplied, this will only return fields
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "az_file_entity",
 *   source_module = "file_entity",
 * )
 */
class AzFileEntity extends FieldableEntity {

  /**
   * Constructs a FileEntityItem instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    $configuration += [
      'type' => NULL,
      'scheme' => $configuration['uri_prefix'] ?? NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    [
      'type' => $type,
      'scheme' => $scheme,
    ] = $this->configuration;

    if ($scheme && (($pos = strpos($scheme, '://')) !== FALSE)) {
      $scheme = substr($scheme, 0, $pos);
    }
    $query = $this->getFileEntityBaseQuery(NULL, FALSE)
      ->fields('fm')
      ->orderBy('fm.timestamp');

    // Filter by type, if configured.
    if ($type) {
      $query->condition('fm.type', $type);
    }

    // Filter by URI prefix if specified.
    if ($scheme) {
      $query->where("{$this->getSchemeExpression()} = :scheme", [
        ':scheme' => $scheme,
      ]);
    }

    return $query;
  }

  /**
   * Returns the file extension expression for the current DB.
   *
   * @param \Drupal\Core\Database\Connection|null $connection
   *   Database connection of the source Drupal 7 instance.
   *
   * @return string
   *   The expression for getting the file extension.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the provided connection is invalid.
   */
  protected function getExtensionExpression($connection = NULL) {
    $db = $connection ?? $this->getDatabase();

    if (!($db instanceof Connection)) {
      throw new \InvalidArgumentException('Expected instance of \Drupal\Core\Database\Connection.');
    }

    return $this->dbIsSqLite($db)
      ? "REPLACE(fm.uri, RTRIM(fm.uri, REPLACE(fm.uri, '.', '')), '')"
      : "SUBSTRING(fm.uri FROM CHAR_LENGTH(fm.uri) - POSITION('.' IN REVERSE(fm.uri)) + 2)";
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareQuery() {
    parent::prepareQuery();

    $this->query->addTag('migrate__az_migration');
    $this->query->addTag('migrate__az_migration__file_entity');
    $this->query->addTag('migrate__az_migration__media_content');
    $this->query->addTag("migrate__az_migration__source__{$this->pluginId}");

    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    [
      'type' => $type,
    ] = $row->getSource();

    // Get Field API field values.
    $fields = $this->getFields('file', $type);
    $file_id = $row->getSourceProperty('fid');
    foreach (array_keys($fields) as $field_name) {
      $row->setSourceProperty($field_name, $this->getFieldValues('file', $field_name, $file_id));
    }

    $row->setSourceProperty('bundle', $type);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // Fields provided by file_admin module are only included here for developer
    // convenience so that they can be adjusted by altering the generated
    // migration plugins.
    $fields = [
      'fid' => $this->t('The file identifier'),
      'uid' => $this->t('The user identifier'),
      'filename' => $this->t('The file name'),
      'uri' => $this->t('The URI of the file'),
      'filemime' => $this->t('The file mimetype'),
      'filesize' => $this->t('The file size'),
      'status' => $this->t('The file status'),
      'timestamp' => $this->t('The time that the file was added'),
      'type' => $this->t('The file type'),
      'created' => $this->t('The created timestamp - (if file_admin module is present in Drupal 7)'),
      'published' => $this->t('The published timestamp - (if file_admin module is present in Drupal 7)'),
      'promote' => $this->t('The promoted flag - (if file_admin module is present in Drupal 7)'),
      'sticky' => $this->t('The sticky flag - (if file_admin module is present in Drupal 7)'),
      'vid' => $this->t('The vid'),
      'image_field_alt' => $this->t('The alternate text for the image (if this is a value of an image field)'),
      'image_field_text' => $this->t('The title text for the image (if this is a value of an image field)'),
    ];

    return $fields;
  }

  /**
   * Returns a base query for file entity types.
   *
   * @param \Drupal\Core\Database\Connection|null $connection
   *   Database connection of the source Drupal 7 instance.
   * @param bool $distinct
   *   Base query should use distinct.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The base query.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the provided connection is invalid.
   */
  protected function getFileEntityBaseQuery($connection = NULL, bool $distinct = TRUE) {
    $db = $connection ?? $this->getDatabase();
    if (!($db instanceof Connection)) {
      throw new \InvalidArgumentException('Expected instance of \\Drupal\\Core\\Database\\Connection.');
    }

    $options = [
      'fetch' => \PDO::FETCH_ASSOC,
    ];

    $query = $db->select('file_managed', 'fm', $options);
    if ($distinct) {
      $query->distinct();
    }

    $query->fields('fm', ['type'])
      ->condition('fm.status', TRUE)
      ->condition('fm.uri', 'temporary://%', 'NOT LIKE')
      ->condition('fm.type', 'undefined', '<>');
    $query->addExpression($this->getSchemeExpression($db), 'scheme');

    // Omit all files that are used solely for a user picture:
    // They do not belong in Drupal's media library.
    $query->condition('fm.fid', $this->getUserPictureOnlyFidsQuery($db), 'NOT IN');
    $query->condition('fm.fid', $this->getWebformOrUserPictureOnlyFidsQuery($db), 'NOT IN');

    return $query;
  }

  /**
   * Returns the expression for the DB for getting the URI scheme.
   *
   * @param \Drupal\Core\Database\Connection|null $connection
   *   Database connection of the source Drupal 7 instance.
   *
   * @return string
   *   The expression for the DB for getting the URI scheme.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the provided connection is invalid.
   */
  protected function getSchemeExpression($connection = NULL) {
    $db = $connection ?? $this->getDatabase();
    if (!($db instanceof Connection)) {
      throw new \InvalidArgumentException('Expected instance of \\Drupal\\Core\\Database\\Connection.');
    }

    return $this->dbIsSqLite($db)
      ? "SUBSTRING(fm.uri, 1, INSTR(fm.uri, '://') - 1)"
      : "SUBSTRING(fm.uri, 1, POSITION('://' IN fm.uri) - 1)";
  }

  /**
   * Returns the subquery for the user picture-only file IDs.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection of the source Drupal 7 instance.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The query to get the FIDs of files that are used only as a user picture.
   */
  protected function getUserPictureOnlyFidsQuery(Connection $connection) {
    $query = $connection->select('users', 'u');
    $query->leftJoin('file_usage', 'fu', 'fu.fid = u.picture');
    $query->where('u.picture > 0');
    $query->fields('fu', ['fid']);
    $query->groupBy('fu.fid');
    $concat_expression = $this->dbIsPostgresql($connection)
      ? "STRING_AGG(DISTINCT fu.type, ',')"
      : "GROUP_CONCAT(DISTINCT fu.type)";
    $query->having("$concat_expression = :allowed_value_user_only OR $concat_expression = :allowed_value_user_webform_only OR $concat_expression = :allowed_value_webform_user_only", [
      ':allowed_value_user_only' => 'user',
      ':allowed_value_user_webform_only' => 'user,webform',
      ':allowed_value_webform_user_only' => 'webform,user',
    ]);

    return $query;
  }

  /**
   * Subquery for FIDs used only in webform submissions and/or by user entities.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection of the source Drupal 7 instance.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Query that gets the FIDs of files used only in webform submissions.
   */
  protected function getWebformOrUserPictureOnlyFidsQuery(Connection $connection) {
    $query = $connection->select('file_usage', 'fu');
    $query->fields('fu', ['fid']);
    $query->groupBy('fu.fid');
    $wf_type_concat_expression = $this->dbIsPostgresql($connection)
      ? "STRING_AGG(DISTINCT fu.type, ',')"
      : "GROUP_CONCAT(DISTINCT fu.type)";
    $query->having("$wf_type_concat_expression = :allowed_type_submission_only OR $wf_type_concat_expression = :allowed_type_submission_user_only OR $wf_type_concat_expression = :allowed_type_user_submission_only", [
      ':allowed_type_submission_only' => 'submission',
      ':allowed_type_submission_user_only' => 'submission,user',
      ':allowed_type_user_submission_only' => 'user,submission',
    ]);

    return $query;
  }

  /**
   * Determines whether the connection is a PostgeSQL connection.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection to check.
   *
   * @return bool
   *   Whether the connection is a PostgeSQL connection.
   */
  protected function dbIsPostgresql(Connection $connection): bool {
    return ($connection->getConnectionOptions()['driver'] ?? NULL) === 'pgsql';
  }

  /**
   * Determines whether the connection is a SQLite connection.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection to check.
   *
   * @return bool
   *   Whether the connection is an SQLite connection.
   */
  protected function dbIsSqLite(Connection $connection): bool {
    $connection_options = $connection->getConnectionOptions();
    return ($connection_options['driver'] ?? NULL) === 'sqlite' ||
      // For in-memory connections.
      preg_match('/\bsqlite\b/', $connection_options['namespace'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    $ids['fid']['alias'] = 'fm';
    return $ids;
  }

}
