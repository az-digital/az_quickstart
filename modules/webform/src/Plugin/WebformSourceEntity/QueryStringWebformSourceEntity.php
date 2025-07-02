<?php

namespace Drupal\webform\Plugin\WebformSourceEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\webform\Plugin\WebformSourceEntityBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Detect source entity by examining query string.
 *
 * @WebformSourceEntity(
 *   id = "query_string",
 *   label = @Translation("Query string"),
 *   weight = 0
 * )
 */
class QueryStringWebformSourceEntity extends WebformSourceEntityBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The webform entity reference manager.
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->languageManager = $container->get('language_manager');
    $instance->webformEntityReferenceManager = $container->get('webform.entity_reference_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity(array $ignored_types) {
    // Note: We deliberately discard $ignored_types because through query string
    // any arbitrary entity can be injected as a source.
    $webform = $this->routeMatch->getParameter('webform');
    if (!$webform) {
      return NULL;
    }

    // Get and check source entity type.
    $source_entity_type = $this->request->query->get('source_entity_type');
    if (!$source_entity_type || !$this->entityTypeManager->hasDefinition($source_entity_type)) {
      return NULL;
    }

    // Get and check source entity id.
    $source_entity_id = $this->request->query->get('source_entity_id');
    if (!$source_entity_id) {
      return NULL;
    }

    // Get and check source entity.
    $source_entity = $this->entityTypeManager->getStorage($source_entity_type)->load($source_entity_id);
    if (!$source_entity) {
      return NULL;
    }

    // Get translated source entity.
    if ($source_entity instanceof TranslatableInterface && $source_entity->hasTranslation($this->languageManager->getCurrentLanguage()->getId())) {
      $source_entity = $source_entity->getTranslation($this->languageManager->getCurrentLanguage()->getId());
    }

    // Check source entity access.
    if (!$source_entity->access('view')) {
      return NULL;
    }

    // Check that the webform is referenced by the source entity.
    if (!$webform->getSetting('form_prepopulate_source_entity')) {
      // Get source entity's webform field.
      $webform_field_names = $this->webformEntityReferenceManager->getFieldNames($source_entity);
      foreach ($webform_field_names as $webform_field_name) {
        // Check that source entity's reference webform is the
        // current webform.
        foreach ($source_entity->$webform_field_name as $item) {
          if ($item->target_id === $webform->id()) {
            return $source_entity;
          }
        }
      }

      return NULL;
    }

    return $source_entity;
  }

  /**
   * Get source entity route options query string parameters.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   An entity.
   *
   * @return array
   *   An associative array contains a source entity's route options
   *   query string parameters.
   */
  public static function getRouteOptionsQuery(EntityInterface $entity = NULL) {
    if (!$entity) {
      return [];
    }
    else {
      return [
        'query' => [
          'source_entity_type' => $entity->getEntityTypeId(),
          'source_entity_id' => $entity->id(),
        ],
      ];
    }
  }

}
