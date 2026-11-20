<?php

namespace Drupal\az_news_export\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;

/**
 * Filter by attribute key for enterprise attributes.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("az_attribute_tid")
 */
class AZAttributeTid extends TaxonomyIndexTid {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Default to the dropdown filter, unlike parent class.
    $options['type'] = ['default' => 'select'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    $vocabulary = $this->vocabularyStorage->load($this->options['vid']);
    if (empty($vocabulary) && $this->options['limit']) {
      $form['markup'] = [
        '#markup' => '<div class="js-form-item form-item">' . $this->t('An invalid vocabulary is selected. Change it in the options.') . '</div>',
      ];
      return;
    }
    $options = [];
    // Generate our list of attribute-key-based options.
    $query = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(TRUE)
      ->sort('weight')
      ->sort('name')
      ->addTag('taxonomy_term_access');
    if (!$this->currentUser->hasPermission('administer taxonomy')) {
      $query->condition('status', 1);
    }
    if ($this->options['limit']) {
      $query->condition('vid', $vocabulary->id());
    }
    $terms = Term::loadMultiple($query->execute());
    foreach ($terms as $term) {
      if ($term->hasField('field_az_attribute_key')) {
        $attribute_key = $term->field_az_attribute_key->value ?? '';
        if (!empty($attribute_key)) {
          $options[$attribute_key] = \Drupal::service('entity.repository')->getTranslationFromContext($term)->label();
        }
      }
    }
    $form['value']['#options'] = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildExtraOptionsForm($form, $form_state);
    // We don't use these elements of the parent class.
    unset($form['type']);
    unset($form['hierarchy']);
  }

  /**
   * {@inheritdoc}
   */
  protected function opHelper() {
    if (empty($this->value)) {
      return;
    }
    // Form API returns unchecked options in the form of option_id => 0. This
    // breaks the generated query for "is all of" filters so we remove them.
    $this->value = array_filter($this->value, [static::class, 'arrayFilterZero']);

    // We need to hang onto this value for the form default next time.
    $original = $this->value;
    $value = [];
    if (empty($original)) {
      return;
    }
    // Lookup the term ids of our attribute keys.
    $vocabulary = $this->vocabularyStorage->load($this->options['vid']);
    $query = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(TRUE)
      ->sort('weight')
      ->sort('name')
      ->addTag('taxonomy_term_access');
    $query->condition('field_az_attribute_key', array_values($original), 'IN');
    if (!$this->currentUser->hasPermission('administer taxonomy')) {
      $query->condition('status', 1);
    }
    if ($this->options['limit']) {
      $query->condition('vid', $vocabulary->id());
    }
    $terms = Term::loadMultiple($query->execute());
    foreach ($terms as $term) {
      $value[] = $term->id();
    }

    // Swap in translated values for the actual query.
    $this->value = $value;
    // Helper adds our actual query with term ids to the view.
    $this->helper->addFilter();

    // Put our original values back for the form build.
    $this->value = $original;
  }

}
