<?php

namespace Drupal\config_views\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Views filter for strings to work with config entities.
 *
 * @ViewsFilter("config_entity_string")
 */
class StringEntity extends StringFilter {

  /**
   * The query.
   *
   * @var \Drupal\config_views\Plugin\views\query\ConfigEntityQuery
   */
  public $query;

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = [
      '=' => [
        'title' => $this->t('Is equal to'),
        'short' => $this->t('='),
        'method' => 'opSimple',
        'values' => 1,
      ],
      '<>' => [
        'title' => $this->t('Is not equal to'),
        'short' => $this->t('<>'),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'CONTAINS' => [
        'title' => $this->t('Contains'),
        'short' => $this->t('contains'),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'STARTS_WITH' => [
        'title' => $this->t('Starts with'),
        'short' => $this->t('begins'),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'ENDS_WITH' => [
        'title' => $this->t('Ends with'),
        'short' => $this->t('ends'),
        'method' => 'opSimple',
        'values' => 1,
      ],
    ];
    // If the definition allows for the empty operator, add it.
    if (!empty($this->definition['allow empty'])) {
      $operators += [
        'IS NULL' => [
          'title' => $this->t('Is empty (NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('empty'),
          'values' => 0,
        ],
        'IS NOT NULL' => [
          'title' => $this->t('Is not empty (NOT NULL)'),
          'method' => 'opNotEmpty',
          'short' => $this->t('not empty'),
          'values' => 0,
        ],
      ];
    }

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $field = $this->realField;

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

  /**
   * Simple operation.
   */
  public function opSimple($field) {
    $this->query->condition($this->options['group'], $field, $this->value, $this->operator);
  }

  /**
   * {@inheritdoc}
   */
  protected function opEmpty($field) {
    $this->query->exists($this->options['group'], $field);
  }

  /**
   * Not empty operation.
   */
  protected function opNotEmpty($field) {
    $this->query->notExists($this->options['group'], $field);
  }

}
