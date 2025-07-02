<?php

namespace Drupal\linkit\Plugin\Linkit\Matcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Provides specific linkit matchers for the node entity type.
 *
 * @Matcher(
 *   id = "entity:node",
 *   label = @Translation("Content"),
 *   target_entity = "node",
 *   provider = "node"
 * )
 */
class NodeMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summery = parent::getSummary();

    $summery[] = $this->t('Include unpublished: @include_unpublished', [
      '@include_unpublished' => $this->configuration['include_unpublished'] ? $this->t('Yes') : $this->t('No'),
    ]);

    return $summery;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'include_unpublished' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + [
      'module' => ['node'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['unpublished_nodes'] = [
      '#type' => 'details',
      '#title' => $this->t('Unpublished nodes'),
      '#open' => TRUE,
    ];

    $form['unpublished_nodes']['include_unpublished'] = [
      '#title' => $this->t('Include unpublished nodes'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['include_unpublished'],
      '#description' => $this->t('In order to see unpublished nodes, users must also have permissions to do so.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['include_unpublished'] = $form_state->getValue('include_unpublished');
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($search_string) {
    $query = parent::buildEntityQuery($search_string);

    if ($this->configuration['include_unpublished'] == FALSE) {
      $query->condition('status', NodeInterface::PUBLISHED);
    }
    elseif (!$this->moduleHandler->hasImplementations('node_grants')) {
      if (($this->currentUser->hasPermission('bypass node access') || $this->currentUser->hasPermission('view any unpublished content'))) {
        // User can see all content, no check necessary.
      }
      elseif ($this->currentUser->hasPermission('view own unpublished content')) {
        // Users with "view own unpublished content" can see only their own.
        if ($this->configuration['include_unpublished'] == TRUE) {
          $or_condition = $query
            ->orConditionGroup()
            ->condition('status', NodeInterface::PUBLISHED)
            ->condition('uid', $this->currentUser->id());
          $query->condition($or_condition);
        }
      }
    }
    else {
      // All other users should only get published results.
      $query->condition('status', NodeInterface::PUBLISHED);
    }

    return $query;
  }

}
