<?php

namespace Drupal\smart_date_recur\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\smart_date_recur\Entity\SmartDateRule;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush command file.
 */
final class SmartDateRecurCommands extends DrushCommands {

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SmartDateRecurCommands object.
   */
  public function __construct(
    private readonly Token $token,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'smart_date_recur:prune-invalid-rules', aliases: ['prune-sd-rules'])]
  #[CLI\Usage(name: 'smart_date_recur:prune-invalid-rules prune-sd-rules', description: 'Remove invalid Smartdate Recur rules')]
  public function pruneRules() {

    $ids = $this->entityTypeManager->getStorage('smart_date_rule')->getRuleIdsToCheck();
    $deleted_ctr = 0;
    foreach (SmartDateRule::loadMultiple($ids) as $rule) {
      if (!$rule->validateRule()) {
        $rule->delete();
        $deleted_ctr++;
      }
    }
    $this->logger()->success(dt('Deleted ' . $deleted_ctr . ' rules.'));
  }

}
