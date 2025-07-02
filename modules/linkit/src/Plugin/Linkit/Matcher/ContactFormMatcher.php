<?php

namespace Drupal\linkit\Plugin\Linkit\Matcher;

/**
 * Provides specific linkit matchers for contact forms.
 *
 * @Matcher(
 *   id = "entity:contact_form",
 *   label = @Translation("Contact form"),
 *   target_entity = "contact_form",
 *   provider = "contact"
 * )
 */
class ContactFormMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + [
      'module' => ['contact'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($search_string) {
    $query = parent::buildEntityQuery($search_string);

    // Remove the personal contact form from the suggestion list.
    $query->condition('id', 'personal', '<>');

    return $query;
  }

}
