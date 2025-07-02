<?php

namespace Drupal\access_unpublished;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Calculates the permissions for access_unpublished.
 *
 * @package Drupal\access_unpublished
 */
class AccessUnpublishedPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AccessUnpublishedPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Permissions callback.
   *
   * @return array
   *   Returns permissions for all nodes.
   */
  public function permissions() {
    $permissions = [];
    $definitions = $this->entityTypeManager->getDefinitions();
    foreach ($definitions as $definition) {
      if (AccessUnpublished::applicableEntityType($definition)) {
        $permission = 'access_unpublished ' . $definition->id();
        if ($definition->get('bundle_entity_type')) {
          $bundles = $this->entityTypeManager->getStorage($definition->getBundleEntityType())->loadMultiple();
          foreach ($bundles as $bundle) {
            $permissions[$permission . ' ' . $bundle->id()] = [
              'title' => $this->t('Access unpublished %bundle @type', [
                '%bundle' => strtolower($bundle->label()),
                '@type' => $definition->getPluralLabel(),
              ]),
            ];
          }
        }
        else {
          $permissions[$permission] = [
            'title' => $this->t('Access unpublished @type', [
              '@type' => $definition->getPluralLabel(),
            ]),
          ];
        }

      }
    }
    return $permissions;
  }

}
