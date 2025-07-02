<?php

namespace Drupal\flag\Plugin\Action;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Flag action for flagging/unflagging entities for a given flag.
 *
 * @Action(
 *   id = "flag_action",
 *   label = @Translation("Flag/unflag entities"),
 *   deriver = "Drupal\flag\Plugin\Derivative\EntityFlagActionDeriver"
 * )
 */
class FlagAction extends ActionBase implements ContainerFactoryPluginInterface, DependentPluginInterface {

  use DependencyTrait;

  /**
   * The flag operation (flag or unflag).
   *
   * @var string
   */
  protected $flagOperation;

  /**
   * The flag.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Constructs the flag action plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FlagServiceInterface $flag_service) {
    if (!isset($configuration['flag_id'], $configuration['flag_action'])) {
      // When not specified otherwise, use the information of the plugin
      // definition, as provided by the deriver.
      $configuration['flag_id'] = $plugin_definition['flag_id'];
      $configuration['flag_action'] = $plugin_definition['flag_action'];
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->flagService = $flag_service;
    $this->flag = $this->flagService->getFlagById($configuration['flag_id']);
    $this->flagOperation = $configuration['flag_action'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $this->flag->actionAccess($this->flagOperation, $account, $object);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      try {
        if ($this->flagOperation === 'unflag') {
          $this->flagService->unflag($this->flag, $entity);
        }
        else {
          $this->flagService->flag($this->flag, $entity);
        }
      }
      catch (\LogicException $e) {
        // @todo Error handling?
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    if ($this->flag) {
      $this->addDependency('config', $this->flag->getConfigDependencyName());
    }
    return $this->dependencies;
  }

}
