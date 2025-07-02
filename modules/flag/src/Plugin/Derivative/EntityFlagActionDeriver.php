<?php

namespace Drupal\flag\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generate flag/unflag action plugins for each flag.
 */
class EntityFlagActionDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Constructs the flag action deriver.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   */
  public function __construct(FlagServiceInterface $flag_service) {
    $this->flagService = $flag_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('flag'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->flagService->getAllFlags() as $flag_id => $flag) {
      foreach (['flag', 'unflag'] as $action) {
        $this->derivatives[$flag_id . '_' . $action] = [
          'id' => $flag_id . '_' . $action,
          'flag_id' => $flag_id,
          'flag_action' => $action,
          'label' => $flag->getShortText($action),
          'type' => $flag->getFlaggableEntityTypeId(),
        ] + $base_plugin_definition;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
