<?php

namespace Drupal\devel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\devel\DevelDumperManagerInterface;
use Drupal\path_alias\PathAliasStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for devel entity debug.
 *
 * @see \Drupal\devel\Routing\RouteSubscriber
 * @see \Drupal\devel\Plugin\Derivative\DevelLocalTask
 */
class EntityDebugController extends ControllerBase {

  /**
   * The dumper service.
   */
  protected DevelDumperManagerInterface $dumper;

  /**
   * The translation manager.
   */
  protected TranslationManager $translationManager;

  /**
   * The alias storage.
   */
  protected PathAliasStorage $aliasStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $entityTypeManager = $container->get('entity_type.manager');
    $instance = parent::create($container);
    $instance->dumper = $container->get('devel.dumper');
    $instance->entityTypeManager = $entityTypeManager;
    $instance->translationManager = $container->get('string_translation');
    $instance->aliasStorage = $entityTypeManager->getStorage('path_alias');

    return $instance;
  }

  /**
   * Returns the entity type definition of the current entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function entityTypeDefinition(RouteMatchInterface $route_match): array {
    $entity = $this->getEntityFromRouteMatch($route_match);
    if (!$entity instanceof EntityInterface) {
      return [];
    }

    return $this->dumper->exportAsRenderable($entity->getEntityType());
  }

  /**
   * Returns the loaded structure of the current entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function entityLoad(RouteMatchInterface $route_match): array {
    $output = [];

    $entity = $this->getEntityWithFieldDefinitions($route_match);

    if ($entity instanceof EntityInterface) {
      // Field definitions are lazy loaded and are populated only when needed.
      // By calling ::getFieldDefinitions() we are sure that field definitions
      // are populated and available in the dump output.
      // @see https://www.drupal.org/node/2311557
      if ($entity instanceof FieldableEntityInterface) {
        $entity->getFieldDefinitions();
      }

      $output = $this->dumper->exportAsRenderable($entity);
    }

    return $output;
  }

  /**
   * Returns the loaded structure of the current entity with references.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function entityLoadWithReferences(RouteMatchInterface $route_match): array {
    $entity = $this->getEntityWithFieldDefinitions($route_match);
    if (!$entity instanceof EntityInterface) {
      return [];
    }

    return $this->dumper->exportAsRenderable($entity, NULL, NULL, TRUE);
  }

  /**
   * Returns the render structure of the current entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function entityRender(RouteMatchInterface $route_match): array {
    $output = [];

    $entity = $this->getEntityFromRouteMatch($route_match);

    if ($entity instanceof EntityInterface) {
      $entity_type_id = $entity->getEntityTypeId();
      $view_hook = $entity_type_id . '_view';

      $build = [];
      // If module implements own {entity_type}_view() hook use it, otherwise
      // fallback to the entity view builder if available.
      if (function_exists($view_hook)) {
        $build = $view_hook($entity);
      }
      elseif ($this->entityTypeManager->hasHandler($entity_type_id, 'view_builder')) {
        $build = $this->entityTypeManager->getViewBuilder($entity_type_id)->view($entity);
      }

      $output = $this->dumper->exportAsRenderable($build);
    }

    return $output;
  }

  /**
   * Return definitions for any related path aliases.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function pathAliases(RouteMatchInterface $route_match): array {
    $entity = $this->getEntityFromRouteMatch($route_match);
    if ($entity === NULL) {
      return [];
    }

    $path = sprintf('/%s/%s', $entity->getEntityTypeId(), $entity->id());
    $aliases = $this->aliasStorage->loadByProperties(['path' => $path]);
    $aliasCount = count($aliases);
    if ($aliasCount > 0) {
      $message = $this->translationManager->formatPlural(
        $aliasCount,
        'Found 1 alias with path "@path."',
        'Found @count aliases with path "@path".',
        ['@path' => $path]
      );
    }
    else {
      $message = $this->t('Found no aliases with path "@path".', ['@path' => $path]);
    }

    $build['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $message,
    ];

    // Add alias dump to the response.
    $build['aliases'] = [];
    foreach ($aliases as $alias) {
      $build['aliases'][] = $this->dumper->exportAsRenderable($alias);
    }

    return $build;
  }

  /**
   * Retrieves entity from route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object as determined from the passed-in route match.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('_devel_entity_type_id');

    return $route_match->getParameter($parameter_name);
  }

  /**
   * Returns an entity with field definitions from the given route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object with field definitions as determined from the
   *   passed-in route match.
   */
  protected function getEntityWithFieldDefinitions(RouteMatchInterface $route_match): ?EntityInterface {
    $entity = $this->getEntityFromRouteMatch($route_match);
    if (!$entity instanceof EntityInterface) {
      return NULL;
    }

    // Field definitions are lazy loaded and are populated only when needed.
    // By calling ::getFieldDefinitions() we are sure that field definitions
    // are populated and available in the dump output.
    // @see https://www.drupal.org/node/2311557
    if ($entity instanceof FieldableEntityInterface) {
      $entity->getFieldDefinitions();
    }

    return $entity;
  }

}
