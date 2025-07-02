<?php

namespace Drupal\xmlsitemap;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\State\StateInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * XmlSitemap link storage service class.
 */
class XmlSitemapLinkStorage implements XmlSitemapLinkStorageInterface {

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The anonymous user object.
   *
   * @var \Drupal\Core\Session\AnonymousUserSession
   */
  protected $anonymousUser;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a XmlSitemapLinkStorage object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(StateInterface $state, ModuleHandlerInterface $module_handler, Connection $connection, FileUrlGeneratorInterface $file_url_generator, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->state = $state;
    $this->moduleHandler = $module_handler;
    $this->anonymousUser = new AnonymousUserSession();
    $this->connection = $connection;
    $this->fileUrlGenerator = $file_url_generator;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function create(EntityInterface $entity) {
    if (!isset($entity->xmlsitemap) || !is_array($entity->xmlsitemap)) {
      $entity->xmlsitemap = [];
      if ($entity->id() && $link = $this->load($entity->getEntityTypeId(), $entity->id())) {
        $entity->xmlsitemap = $link;
      }
    }

    $settings = xmlsitemap_link_bundle_load($entity->getEntityTypeId(), $entity->bundle());
    $entity->xmlsitemap += [
      'type' => $entity->getEntityTypeId(),
      'id' => (string) $entity->id(),
      'subtype' => $entity->bundle(),
      'status' => (int) $settings['status'],
      'status_default' => (int) $settings['status'],
      'status_override' => 0,
      'priority' => $settings['priority'],
      'priority_default' => $settings['priority'],
      'priority_override' => 0,
      'changefreq' => isset($settings['changefreq']) ? $settings['changefreq'] : 0,
    ];

    if ($entity instanceof EntityChangedInterface) {
      $entity->xmlsitemap['lastmod'] = $entity->getChangedTime();
    }

    // The following values must always be checked because they are volatile.
    try {
      // @todo Could we move this logic to some kind of handler on the menu link entity class?
      if ($entity instanceof MenuLinkContentInterface) {
        $url = $entity->getUrlObject();
        if ($url->isRouted()) {
          if ($url->getRouteName() === '<nolink>') {
            $loc = '';
          }
          else {
            $loc = $url->getInternalPath();
          }
        }
        else {
          // Attempt to transform this to a relative URL.
          $loc = $this->fileUrlGenerator->transformRelative($url->toString());
          // If it could not be transformed into a relative path, disregard it
          // since we cannot store external URLs in the sitemap.
          if (UrlHelper::isExternal($loc)) {
            $loc = '';
          }
        }
        $access = $url->access($this->anonymousUser);
      }
      else {
        $loc = ($entity->id() && $entity->hasLinkTemplate('canonical')) ? $entity->toUrl()->getInternalPath() : '';
        $access = $entity->access('view', $this->anonymousUser);
      }
    }
    catch (RouteNotFoundException $e) {
      $loc = '';
    }
    $entity->xmlsitemap['loc'] = '/' . ltrim($loc, '/');
    $entity->xmlsitemap['access'] = $loc && $access;
    $language = $entity->language();
    $entity->xmlsitemap['language'] = !empty($language) ? $language->getId() : LanguageInterface::LANGCODE_NOT_SPECIFIED;

    return $entity->xmlsitemap;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $link, array $context = []) {
    $link += [
      'access' => 1,
      'status' => 1,
      'status_override' => 0,
      'lastmod' => 0,
      'priority' => XMLSITEMAP_PRIORITY_DEFAULT,
      'priority_override' => 0,
      'changefreq' => 0,
      'changecount' => 0,
      'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];

    // Allow other modules to alter the link before saving.
    $this->moduleHandler->alter('xmlsitemap_link', $link, $context);

    // Temporary validation checks.
    // @todo Remove in final?
    if ($link['priority'] < 0 || $link['priority'] > 1) {
      trigger_error("The XML Sitemap link for {$link['type']} {$link['id']} has an invalid priority of {$link['priority']}.<br/>" . var_export($link, TRUE), E_USER_ERROR);
    }
    if ($link['changecount'] < 0) {
      trigger_error("The XML Sitemap link for {$link['type']} {$link['id']} has a negative changecount value. Please report this to https://www.drupal.org/node/516928.<br/>" . var_export($link, TRUE), E_USER_ERROR);
      $link['changecount'] = 0;
    }

    // Throw an error with the link does not start with a slash.
    // @see \Drupal\Core\Url::fromInternalUri()
    if ($link['loc'][0] !== '/') {
      trigger_error("The XML Sitemap link path {$link['loc']} for {$link['type']} {$link['id']} is invalid because it does not start with a slash.", E_USER_ERROR);
    }

    // Check if this is a changed link and set the regenerate flag if necessary.
    if (!$this->state->get('xmlsitemap_regenerate_needed')) {
      $this->checkChangedLink($link, NULL, TRUE);
    }

    $queryStatus = $this->connection->merge('xmlsitemap')
      ->keys([
        'type' => $link['type'],
        'id' => $link['id'],
        'language' => $link['language'],
      ])
      ->fields([
        'loc' => $link['loc'],
        'subtype' => $link['subtype'],
        'access' => (int) $link['access'],
        'status' => (int) $link['status'],
        'status_override' => $link['status_override'],
        'lastmod' => $link['lastmod'],
        'priority' => $link['priority'],
        'priority_override' => $link['priority_override'],
        'changefreq' => $link['changefreq'],
        'changecount' => $link['changecount'],
      ])
      ->execute();

    switch ($queryStatus) {
      case Merge::STATUS_INSERT:
        $this->moduleHandler->invokeAll('xmlsitemap_link_insert', [$link, $context]);
        break;

      case Merge::STATUS_UPDATE:
        $this->moduleHandler->invokeAll('xmlsitemap_link_update', [$link, $context]);
        break;
    }

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function checkChangedLink(array $link, array $original_link = NULL, $flag = FALSE) {
    $changed = FALSE;

    if ($original_link === NULL) {
      // Load only the fields necessary for data to be changed in the sitemap.
      $original_link = $this->connection->queryRange("SELECT loc, access, status, lastmod, priority, changefreq, changecount, language FROM {xmlsitemap} WHERE type = :type AND id = :id", 0, 1, [':type' => $link['type'], ':id' => $link['id']])->fetchAssoc();
    }

    if (!$original_link) {
      if ($link['access'] && $link['status']) {
        // Adding a new visible link.
        $changed = TRUE;
      }
    }
    else {
      if (!($original_link['access'] && $original_link['status']) && $link['access'] && $link['status']) {
        // Changing a non-visible link to a visible link.
        $changed = TRUE;
      }
      elseif ($original_link['access'] && $original_link['status'] && array_diff_assoc($original_link, $link)) {
        // Changing a visible link.
        $changed = TRUE;
      }
    }

    if ($changed && $flag) {
      $this->state->set('xmlsitemap_regenerate_needed', TRUE);
    }

    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function checkChangedLinks(array $conditions = [], array $updates = [], $flag = FALSE) {
    // If we are changing status or access, check for negative current values.
    $conditions['status'] = (!empty($updates['status']) && empty($conditions['status'])) ? 0 : 1;
    $conditions['access'] = (!empty($updates['access']) && empty($conditions['access'])) ? 0 : 1;

    $query = $this->connection->select('xmlsitemap');
    $query->addExpression('1');
    foreach ($conditions as $field => $value) {
      $operator = is_array($value) ? 'IN' : '=';
      $query->condition($field, $value, $operator);
    }
    $query->range(0, 1);
    $changed = $query->execute()->fetchField();

    if ($changed && $flag) {
      $this->state->set('xmlsitemap_regenerate_needed', TRUE);
    }

    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($entity_type, $entity_id, $langcode = NULL) {
    $conditions = ['type' => $entity_type, 'id' => $entity_id];
    if ($langcode) {
      $conditions['language'] = $langcode;
    }
    return $this->deleteMultiple($conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $conditions) {
    if (!$this->state->get('xmlsitemap_regenerate_needed')) {
      $this->checkChangedLinks($conditions, [], TRUE);
    }

    // @todo Add a hook_xmlsitemap_link_delete() hook invoked here.
    $query = $this->connection->delete('xmlsitemap');
    foreach ($conditions as $field => $value) {
      $operator = is_array($value) ? 'IN' : '=';
      $query->condition($field, $value, $operator);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function updateMultiple(array $updates = [], array $conditions = [], $check_flag = TRUE) {
    // If we are going to modify a visible sitemap link, we will need to set
    // the regenerate needed flag.
    if ($check_flag && !$this->state->get('xmlsitemap_regenerate_needed')) {
      $this->checkChangedLinks($conditions, $updates, TRUE);
    }

    // Process updates.
    $query = $this->connection->update('xmlsitemap');
    $query->fields($updates);
    foreach ($conditions as $field => $value) {
      $operator = is_array($value) ? 'IN' : '=';
      $query->condition($field, $value, $operator);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function load($entity_type, $entity_id) {
    $link = $this->loadMultiple(['type' => $entity_type, 'id' => $entity_id]);
    return $link ? reset($link) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $conditions = []) {
    $query = $this->connection->select('xmlsitemap');
    $query->fields('xmlsitemap');

    foreach ($conditions as $field => $value) {
      $operator = is_array($value) ? 'IN' : '=';
      $query->condition($field, $value, $operator);
    }

    $links = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityLinkQuery(string $entity_type_id, array $bundles = []): SelectInterface {
    $query = $this->connection->select('xmlsitemap', 'x');
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $definitions */
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    $id_definition = $definitions[$entity_type->getKey('id')];
    if ($id_definition->getType() === 'integer') {
      $types = [
        'mysql' => 'UNSIGNED',
        'pgsql' => 'BIGINT',
      ];
      $type = $types[\Drupal::database()->databaseType()] ?? 'INTEGER';
      $query->addExpression("CAST(x.id AS $type)", 'id');
    }
    else {
      $query->addField('x', 'id');
    }
    $query->condition('type', $entity_type_id);
    if (!empty($bundles)) {
      $query->condition('subtype', $bundles, 'IN');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityQuery(string $entity_type_id, array $bundles = [], SelectInterface $subquery = NULL, string $subquery_operator = 'IN'): QueryInterface {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity_type = $storage->getEntityType();
    $query = $storage->getQuery();
    $id_field = $entity_type->getKey('id');

    if ($bundles && $bundle_key = $entity_type->getKey('bundle')) {
      $query->condition($bundle_key, $bundles, 'IN');
    }

    // Access for entities is checked individually for the anonymous user
    // when each item is processed. We can skip the access check for the
    // query.
    $query->accessCheck(FALSE);

    if (!isset($subquery)) {
      $subquery = $this->getEntityLinkQuery($entity_type_id, $bundles);
    }

    // If the storage for this entity type is not using a SQL backend, then
    // we need to convert our subquery into an actual array of values since we
    // cannot perform a direct subquery with our entity query.
    if (!($query instanceof Query)) {
      $subquery = $subquery->execute()->fetchCol();
    }
    $query->condition(
      $id_field,
      $subquery,
      $subquery_operator
    );

    return $query;
  }

}
