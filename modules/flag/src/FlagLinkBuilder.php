<?php

namespace Drupal\flag;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a lazy builder for flag links.
 */
class FlagLinkBuilder implements FlagLinkBuilderInterface, TrustedCallbackInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FlagServiceInterface $flag_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->flagService = $flag_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['build'];
  }

  /**
   * {@inheritdoc}
   */
  public function build($entity_type_id, $entity_id, $flag_id, $view_mode = NULL) {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    $flag = $this->flagService->getFlagById($flag_id);

    $link_type_plugin = $flag->getLinkTypePlugin();
    return $link_type_plugin->getAsFlagLink($flag, $entity, $view_mode);
  }

}
