<?php

namespace Drupal\az_event\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Date;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("az_calendar_filter")
 */
class AZCalendarFilter extends Date {

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    $form['#attached']['library'][] = 'az_event/az_calendar_filter';
    // Prepare a wrapper for the calendar JS to access.
    $calendar_element = [
      '#type' => 'container',
      '#attached' => ['library' => ['az_event/az_calendar_filter']],
      '#attributes' => [
        'class' => [
          'az-calendar-filter-wrapper',
        ],
        // Communicate to the HTML DOM what the unique id of our filter is.
        'data-az-calendar-filter' => $this->options['expose']['identifier'],
      ],
    ];

    // Add the calendar markup into the widget.
    array_unshift($form['value'], $calendar_element);
    $form['value']['#attributes']['class'][] = 'views-widget-az-calendar-filter';
    $form['value']['#type'] = 'container';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField" . '_value';

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = [
      'overlaps' => [
        'title' => $this->t('Overlaps'),
        'method' => 'opOverlap',
        'short' => $this->t('overlaps'),
        'values' => 2,
      ],
    ];

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  protected function opOverlap($field) {
    $field2 = "$this->tableAlias.$this->realField" . '_end_value';

    $a = intval(strtotime($this->value['min'] . ' 00:00:00', 0));
    $b = intval(strtotime($this->value['max'] . ' 23:59:59', 0));

    if ($this->value['type'] === 'offset') {
      // Keep sign.
      $a = '***CURRENT_TIME***' . sprintf('%+d', $a);
      // Keep sign.
      $b = '***CURRENT_TIME***' . sprintf('%+d', $b);
    }
    // This is safe because we are manually scrubbing the values.
    // It is necessary to do it this way since $a and $b are might be formulae.
    $this->query->addWhereExpression($this->options['group'], "$field <= $b AND $field2 >= $a");
  }

}
