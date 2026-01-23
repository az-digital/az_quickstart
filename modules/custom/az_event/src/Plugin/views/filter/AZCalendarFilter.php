<?php

namespace Drupal\az_event\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\Date;
use Drupal\views\Views;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("az_calendar_filter")]
class AZCalendarFilter extends Date {

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    // Only add modifications if this is the exposed filter.
    if ($form_state->get('exposed')) {
      $filter_settings = [];

      // Only attempt to get cell data if we're not already, and not in Views UI.
      $is_views_ui = \Drupal::routeMatch()->getRouteName() === 'views_ui.form_display';
      if (empty($this->view->cellQuery) && !$is_views_ui) {
        $filter_settings[$this->options['expose']['identifier']] = $this->calendarCells();
      }

      $this->view->element['#attached']['library'][] = 'az_event/az_calendar_filter';
      $this->view->element['#attached']['drupalSettings']['azCalendarFilter'] = $filter_settings;
      $this->view->element['#cache']['max-age'] = 0;
      // Prepare a wrapper for the calendar JS to access.
      $calendar_element = [
        '#type' => 'container',
        '#attached' => [
          'library' => ['az_event/az_calendar_filter'],
          'drupalSettings' => ['azCalendarFilter' => $filter_settings],
        ],
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
  }

  /**
   * Clones the view and returns calendar cell preview.
   *
   * @return array
   *   The preview cells.
   */
  protected function calendarCells() {
    $cells = [];
    if (empty($this->view)) {
      return $cells;
    }
    $view = Views::getView($this->view->id());
    if (empty($view)) {
      return $cells;
    }
    $view = Views::executableFactory()->get($this->view->storage);
    // @phpstan-ignore-next-line
    $view->cellQuery = TRUE;

    $view->setDisplay($this->view->current_display);

    // Turn off the pager for the cell query.
    $pager = $view->display_handler->getOption('pager');
    $pager['type'] = 'none';
    $view->display_handler->setOption('pager', $pager);

    // Copy over exposed input.
    $input = $this->view->getExposedInput();
    $view->setExposedInput($input);

    // Copy over arguments.
    $args = $this->view->args;
    $view->setArguments($args);

    $view->execute();

    foreach ($view->result as $row) {
      if (!empty($row->az_calendar_filter_start) && !empty($row->az_calendar_filter_end)) {
        $cells[] = [
          $row->az_calendar_filter_start,
          $row->az_calendar_filter_end,
        ];
      }
    }

    $view->cellQuery = FALSE;
    $view->destroy();
    return $cells;
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
   * Filters by date overlap to determine if content overlaps calendar range.
   *
   * @param object $field
   *   The views field.
   */
  protected function opOverlap($field) {
    $offset = FALSE;
    if ($this->value['min'] === 'today') {
      $offset = TRUE;
    }

    // Add aliases to gather cell data.
    // @phpstan-ignore-next-line
    $this->query->addField($this->tableAlias, $this->realField . '_value', 'az_calendar_filter_start');
    // @phpstan-ignore-next-line
    $this->query->addField($this->tableAlias, $this->realField . '_end_value', 'az_calendar_filter_end');

    $field2 = "$this->tableAlias.$this->realField" . '_end_value';

    $a = intval(strtotime($this->value['min'] . ' 00:00:00', 0));
    $b = intval(strtotime($this->value['max'] . ' 23:59:59', 0));

    if (!empty($this->view->cellQuery)) {
      // Massage range by 3 months for cell preview query.
      // We need this because we need a grace window if the user is
      // Rapidly clicking through the calendar, as they will run out
      // of preview cells while AJAX is loading.
      $a -= 7889238;
      $b += 7889238;
    }

    if ($offset) {
      // Keep sign.
      $a = '***CURRENT_TIME***' . sprintf('%+d', $a);
      // Keep sign.
      $b = '***CURRENT_TIME***' . sprintf('%+d', $b);
    }
    // Compute date overlap between ranges.
    // This is safe because we are manually scrubbing the values.
    // It is necessary to do it this way since $a and $b might be formulae.
    // @phpstan-ignore-next-line
    $this->query->addWhereExpression($this->options['group'], "$field <= $b AND $field2 >= $a");
  }

}
