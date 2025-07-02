<?php

namespace Drupal\ctools;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\ctools\Context\EntityLazyLoadContext;

/**
 * Maps context configurations to context objects.
 */
class ContextMapper implements ContextMapperInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new ContextMapper.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextValues(array $context_configurations) {
    $contexts = [];
    foreach ($context_configurations as $name => $context_configuration) {
      if (strpos($context_configuration['type'], 'entity:') === 0) {
        $context_definition = new EntityContextDefinition($context_configuration['type'], $context_configuration['label'], TRUE, FALSE, $context_configuration['description']);
        $context = new EntityLazyLoadContext($context_definition, $this->entityRepository, $context_configuration['value']);
      }
      else {
        $context_definition = new ContextDefinition($context_configuration['type'], $context_configuration['label'], TRUE, FALSE, $context_configuration['description']);
        $context = new Context($context_definition, $context_configuration['value']);
      }
      $contexts[$name] = $context;
    }
    return $contexts;
  }

}
