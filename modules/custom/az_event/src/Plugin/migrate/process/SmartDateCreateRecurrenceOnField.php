<?php

namespace Drupal\az_event\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\smart_date_recur\Entity\SmartDateRule;

/**
 * Process plugin to create smart_date rule instances.
 *
 * @MigrateProcessPlugin(
 *   id = "smart_date_create_recurrence_on_field"
 * )
 *
 *  To create smart_date recurring date instances on the date field do
 *  the following.
 *
 * @code
 * field_az_event_date:
 *   plugin: smart_date_create_recurrence_on_field
 *   source: rid
 * @endcode
 */
class SmartDateCreateRecurrenceOnField extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    /*
     * @See https://git.drupalcode.org/project/smart_date/-/blob/3.4.x/modules/smart_date_recur/src/Controller/Instances.php#L351
     * @See https://git.drupalcode.org/project/smart_date/-/blob/3.4.x/modules/smart_date_recur/smart_date_recur.module#L547
     */
    if (!empty($value)) {
      $rrule = SmartDateRule::load($value);
      if ($rrule) {
        $values = [];
        $first_instance = [];
        $before = NULL;
        // Retrieve all instances for this rule, with overrides applied.
        if (empty($rrule->get('limit')->getString())) {
          $before = strtotime('+ 24 months');
        }
        $instances = $rrule->getRuleInstances($before);
        $rrule->set('instances', ['data' => $instances]);

        $rrule->save();
        $instancesAgain = $rrule->getRuleInstances($before);

        foreach ($instancesAgain as $rrule_index => $instance) {
          // Apply instance values to our template, and add to the field values.
          $first_instance['value'] = $instance['value'];
          $first_instance['end_value'] = $instance['end_value'];
          // Calculate the duration, since it isn't returned.
          $first_instance['duration'] = ($instance['end_value'] - $instance['value']) / 60;
          $first_instance['rrule_index'] = $rrule_index;
          $first_instance['rrule'] = $rrule->id();
          $first_instance['timezone'] = 'America/Phoenix';
          $values[] = $first_instance;
        }
        return $values;
      }
    }
    return $value;
  }

}
