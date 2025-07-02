<?php

namespace Drupal\devel;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = new self();
    $instance->currentUser = $container->get('current_user');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * Adds devel links to appropriate entity types.
   *
   * This is an alter hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types): void {
    foreach ($entity_types as $entity_type_id => $entity_type) {
      // Make devel-load and devel-load-with-references subtasks. The edit-form
      // template is used to extract and set additional parameters dynamically.
      // If there is no 'edit-form' template then still create the link using
      // 'entity_type_id/{entity_type_id}' as the link. This allows devel info
      // to be viewed for any entity, even if the url has to be typed manually.
      // @see https://gitlab.com/drupalspoons/devel/-/issues/377
      $entity_link = $entity_type->getLinkTemplate('edit-form') ?: $entity_type_id . sprintf('/{%s}', $entity_type_id);
      $this->setEntityTypeLinkTemplate($entity_type, $entity_link, 'devel-load', '/devel/' . $entity_type_id);
      $this->setEntityTypeLinkTemplate($entity_type, $entity_link, 'devel-load-with-references', '/devel/load-with-references/' . $entity_type_id);
      $this->setEntityTypeLinkTemplate($entity_type, $entity_link, 'devel-path-alias', '/devel/path-alias/' . $entity_type_id);

      // Create the devel-render subtask.
      if ($entity_type->hasViewBuilderClass() && $entity_type->hasLinkTemplate('canonical')) {
        // We use canonical template to extract and set additional parameters
        // dynamically.
        $entity_link = $entity_type->getLinkTemplate('canonical');
        $this->setEntityTypeLinkTemplate($entity_type, $entity_link, 'devel-render', '/devel/render/' . $entity_type_id);
      }

      // Create the devel-definition subtask.
      if ($entity_type->hasLinkTemplate('devel-render') || $entity_type->hasLinkTemplate('devel-load')) {
        // We use canonical or edit-form template to extract and set additional
        // parameters dynamically.
        $entity_link = $entity_type->getLinkTemplate('edit-form');
        if (empty($entity_link)) {
          $entity_link = $entity_type->getLinkTemplate('canonical');
        }

        $this->setEntityTypeLinkTemplate($entity_type, $entity_link, 'devel-definition', '/devel/definition/' . $entity_type_id);
      }
    }
  }

  /**
   * Sets entity type link template.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type.
   * @param string $entity_link
   *   Entity link.
   * @param string $devel_link_key
   *   Devel link key.
   * @param string $base_path
   *   Base path for devel link key.
   */
  protected function setEntityTypeLinkTemplate(EntityTypeInterface $entity_type, $entity_link, $devel_link_key, string $base_path) {
    // Extract all route parameters from the given template and set them to
    // the current template.
    // Some entity templates can contain not only entity id,
    // for example /user/{user}/documents/{document}
    // /group/{group}/content/{group_content}
    // We use canonical or edit-form templates to get these parameters and set
    // them for devel entity link templates.
    $path_parts = $this->getPathParts($entity_link);
    $entity_type->setLinkTemplate($devel_link_key, $base_path . $path_parts);
  }

  /**
   * Get path parts.
   *
   * @param string $entity_path
   *   Entity path.
   *
   * @return string
   *   Path parts.
   */
  protected function getPathParts($entity_path): string {
    $path = '';
    if (preg_match_all('/{\w*}/', $entity_path, $matches)) {
      foreach ($matches[0] as $match) {
        $path .= '/' . $match;
      }
    }

    return $path;
  }

  /**
   * Adds devel operations on entity that supports it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity): array {
    $operations = $parameters = [];
    if ($this->currentUser->hasPermission('access devel information')) {
      if ($entity->hasLinkTemplate('canonical')) {
        $parameters = $entity->toUrl('canonical')->getRouteParameters();
      }
      if ($entity->hasLinkTemplate('devel-load')) {
        $url = $entity->toUrl('devel-load');
        $operations['devel'] = [
          'title' => $this->t('Devel'),
          'weight' => 100,
          'url' => $parameters ? $url->setRouteParameters($parameters) : $url,
        ];
      }
      elseif ($entity->hasLinkTemplate('devel-render')) {
        $url = $entity->toUrl('devel-render');
        $operations['devel'] = [
          'title' => $this->t('Devel'),
          'weight' => 100,
          'url' => $parameters ? $url->setRouteParameters($parameters) : $url,
        ];
      }
    }

    return $operations;
  }

}
