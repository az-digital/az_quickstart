<?php

namespace Drupal\linkit\Plugin\Linkit\Matcher;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\Utility\LinkitXss;

/**
 * Provides specific linkit matchers for the taxonomy_term entity type.
 *
 * @Matcher(
 *   id = "entity:taxonomy_term",
 *   label = @Translation("Taxonomy term"),
 *   target_entity = "taxonomy_term",
 *   provider = "taxonomy"
 * )
 */
class TermMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + [
      'module' => ['taxonomy'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $this->insertTokenList($form, ['term']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDescription(EntityInterface $entity) {
    $description = \Drupal::token()->replace($this->configuration['metadata'], ['term' => $entity], []);
    return LinkitXss::descriptionFilter($description);
  }

}
