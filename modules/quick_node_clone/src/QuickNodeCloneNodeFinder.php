<?php

namespace Drupal\quick_node_clone;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Helper class.
 */
class QuickNodeCloneNodeFinder {

  /**
   * Request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Path Alias Manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;
  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * QuickNodeCloneNodeFinder constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   Alias manager.
   */
  public function __construct(RequestStack $requestStack, EntityTypeManagerInterface $entityTypeManager, ?AliasManagerInterface $aliasManager) {
    $this->requestStack = $requestStack;
    $this->aliasManager = $aliasManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Derive node data from the current path.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Either returns an entity, or null if none found.
   */
  public function findNodeFromCurrentPath() {
    $path = $this->requestStack->getCurrentRequest()->getRequestUri();
    $path_data = explode('/', $path);

    if ($this->currentPathIsValidClonePath()) {
      // By this point, we should be on a quick node clone path.
      $node_path = '/node/' . $path_data[2];

      return $this->findNodeFromPath($node_path);
    }
    return NULL;
  }

  /**
   * Derive node data from a given path.
   *
   * @param string $path
   *   The drupal path, e.g. /node/2.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Either returns an entity, or null if none found.
   */
  public function findNodeFromPath($path) {
    $entity = NULL;

    $type = 'node';

    // Check that the route pattern is an entity template.
    $parts = explode('/', $path);
    $i = 0;
    foreach ($parts as $part) {
      if (!empty($part)) {
        $i += 1;
      }
      if ($part == $type) {
        break;
      }
    }
    $i += 1;
    if ($this->aliasManager) {
      // Get entity path if alias.
      $path = $this->aliasManager->getPathByAlias($path);
    }

    // Look! We're using arg() in Drupal 8 because we have to.
    $args = explode('/', $path);

    if (isset($args[$i])) {
      $entity = $this->entityTypeManager->getStorage($type)->load($args[$i]);
    }
    if (isset($args[$i - 1]) && $args[$i - 1] != 'node') {
      $entity = $this->entityTypeManager->getStorage($type)->load($args[$i - 1]);
    }
    return $entity;
  }

  /**
   * Get entity links, given an entity type.
   *
   * @param string $type
   *   The entity type.
   *
   * @return array|null
   *   An array of link templates, or null.
   */
  public function getLinksByType($type) {
    $entity_type = $this->entityTypeManager->getDefinition($type);
    return $entity_type->getLinkTemplates();
  }

  /**
   * Determine if the current page path is a valid quick node clone path.
   *
   * @return bool
   *   TRUE if valid, FALSE if invalid.
   */
  public function currentPathIsValidClonePath() {
    $path = $this->requestStack->getCurrentRequest()->getRequestUri();
    $path_data = explode('/', $path);

    if (!isset($path_data[1]) || $path_data[1] != 'clone') {
      return FALSE;
    }
    if (!isset($path_data[2])) {
      return FALSE;
    }

    if (!isset($path_data[3]) || $path_data[3] != 'quick_clone') {
      return FALSE;
    }
    return TRUE;
  }

}
