<?php

namespace Drupal\az_publication\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Views filter for boolean to work with config entities.
 *
 * @ViewsFilter("config_entity_boolean")
 */
class BooleanEntity extends BooleanOperator {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $field = $this->realField;

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      call_user_func([$this, $info[$this->operator]['method']], $field, $info[$this->operator]['query_operator']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function queryOpBoolean($field, $query_operator = self::EQUAL) {
    $this->query->condition($this->options['group'], $field, $this->value, $this->operator);
  }

}
