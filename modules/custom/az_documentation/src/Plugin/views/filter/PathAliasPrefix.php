<?php

declare(strict_types=1);

namespace Drupal\az_documentation\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filters nodes by their current path alias prefix.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter('az_path_alias_prefix')]
class PathAliasPrefix extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['value'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path alias prefix'),
      '#default_value' => is_array($this->value)
        ? ($this->value['value'] ?? '')
        : ($this->value ?? ''),
      '#description' => $this->t('Enter a leading-slash path prefix, e.g. /admin/documentation'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Normalize input.
    $raw = is_array($this->value) ? ($this->value['value'] ?? '') : ($this->value ?? '');
    $prefix = trim((string) $raw);
    if ($prefix === '') {
      // No-op if empty.
      return;
    }
    if ($prefix[0] !== '/') {
      $prefix = '/' . $prefix;
    }

    // Ensure we have the base node table alias.
    // Usually node_field_data.
    $base = $this->ensureMyTable();

    // Escape prefix for LIKE (portable / minimal manual escaping).
    $escaped = strtr(
      $prefix,
      [
        '%' => '\\%',
        '_' => '\\_',
        '\\' => '\\\\',
      ]
    ) . '%';

    // EXISTS + CONCAT is portable (MySQL/MariaDB/PostgreSQL).
    // Alternate (PostgreSQL): '/node/' || $base.nid if CONCAT() unavailable.
    $where = "EXISTS (SELECT 1 FROM {path_alias} pa"
      . " WHERE pa.path = CONCAT('/node/', $base.nid)"
      . " AND pa.alias LIKE :az_path_prefix)";
    // @phpstan-ignore-next-line dynamic properties provided by base class.
    $this->query->addWhereExpression(
      $this->options['group'],
      $where,
      [
        ':az_path_prefix' => $escaped,
      ]
    );
  }

}
