<?php

namespace Drupal\smart_date_recur\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\smart_date_recur\Entity\SmartDateRule;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates a rule's instances.
 *
 * @QueueWorker(
 *   id = "smart_date_recur_rules",
 *   title = @Translation("Smart Date Recur rules refresh"),
 *   cron = {"time" = 60}
 * )
 */
class RecurRuleUpdate extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Definition of form entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a smart date instance removal confirmation form.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    // If we don't have rules or an entity, there's nothing to do.
    if (empty($item->data) || empty($item->entity_id)) {
      return;
    }
    $entity_storage = $this->entityTypeManager->getStorage($item->entity_type);

    $entity = $entity_storage
      ->load($item->entity_id);
    // If we can't find the entity, there's nothing to do.
    if (empty($entity)) {
      return;
    }

    $rules_processed = [];
    foreach ($item->data as $field_name => $rules) {
      $field_values = $entity->get($field_name)->getValue();
      $processed = [];
      // Go through identified rules to see if new instances are needed.
      foreach ($rules as $rrid) {
        $rule = SmartDateRule::load($rrid);
        // @see makeRuleInstances() method for better understanding.
        $new_instances = $rule->getNewInstances()?->toArray();
        if (empty($new_instances)) {
          // No instances to add, so no need to process this rule.
          unset($rules[$rrid]);
          continue;
        }
        $instances = $rule->getStoredInstances();
        $template = end($instances);
        foreach ($new_instances as $new_instance) {
          $template['value'] = $new_instance->getStart()->getTimestamp();
          $template['end_value'] = $new_instance->getEnd()->getTimestamp();
          $instances[] = $template;
        }
        // @todo check for expired instances. Possible to keep indexes the same?
        $rule->set('instances', ['data' => $instances]);
        $rule->save();
        $rules[$rrid] = $instances;
      }
      foreach ($field_values as $row) {
        // Skip if this instance isn't in a rule or in one we've ruled out.
        if (empty($row['rrule']) || !isset($rules[$row['rrule']])) {
          // Add directly to our array.
          $processed[] = $row;
          continue;
        }
        if (isset($rules_processed[$row['rrule']])) {
          // Already handled this rule, so skip this row.
          continue;
        }
        $instances = $rules[$row['rrule']];
        foreach ($instances as $rrule_index => $instance) {
          $row['value'] = $instance['value'];
          $row['end_value'] = $instance['end_value'];
          $row['rrule_index'] = $rrule_index;
          $processed[] = $row;
        }
        // Track that this rule has been processed.
        $rules_processed[$row['rrule']] = $row['rrule'];
      }
      // Update the entity with our new values.
      $entity->set($field_name, $processed);
    }
    if (!empty($rules_processed)) {
      $entity->save();
    }
  }

}
