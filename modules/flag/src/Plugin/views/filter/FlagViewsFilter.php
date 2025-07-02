<?php

namespace Drupal\flag\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Filters content by its flagging status in a view.
 *
 * @ViewsFilter("flag_filter")
 */
class FlagViewsFilter extends BooleanOperator {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['value'] = ['default' => 1];
    $options['relationship'] = ['default' => 'flag_relationship'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['value']['#type'] = 'radios';
    $form['value']['#title'] = $this->t('Status');
    $form['value']['#options'] = [
      'All' => $this->t('All'),
      1 => $this->t('Flagged'),
      0 => $this->t('Not flagged'),
    ];
    $form['value']['#default_value'] = $this->options['value'] ?? 0;
    $form['value']['#description'] = '<p>' . $this->t('This filter is only needed if the relationship used has the "Include only flagged content" option <strong>unchecked</strong>. Otherwise, this filter is useless, because all records are already limited to flagged content.') . '</p><p>' . $this->t('By choosing <em>Not flagged</em>, it is possible to create a list of content <a href="@unflagged-url">that is specifically not flagged</a>.', ['@unflagged-url' => 'http://drupal.org/node/299335']) . '</p>';

    // Workaround for bug in Views: $no_operator class property has no effect.
    // @todo remove when https://www.drupal.org/node/2869191 is fixed.
    unset($form['operator']);
    unset($form['expose']['use_operator']);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $operator = $this->value ? 'IS NOT' : 'IS';
    $operator .= ' NULL';

    // @phpstan-ignore-next-line
    $this->query->addWhere($this->options['group'], "$this->tableAlias.uid", NULL, $operator);
  }

}
