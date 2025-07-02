<?php

namespace Drupal\linkit_test\Plugin\Linkit\Matcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\ConfigurableMatcherBase;
use Drupal\linkit\Suggestion\DescriptionSuggestion;
use Drupal\linkit\Suggestion\SuggestionCollection;

/**
 * Provides test linkit matchers for the configurable_dummy_matcher entity type.
 *
 * @Matcher(
 *   id = "configurable_dummy_matcher",
 *   label = @Translation("Configurable Dummy Matcher"),
 * )
 */
class ConfigurableDummyMatcher extends ConfigurableMatcherBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'dummy_setting' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['dummy_setting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dummy setting'),
      '#default_value' => $this->configuration['dummy_setting'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['dummy_setting'] = $form_state->getValue('dummy_setting');
  }

  /**
   * {@inheritdoc}
   */
  public function execute($string) {
    $suggestions = new SuggestionCollection();
    $suggestion = new DescriptionSuggestion();
    $suggestion->setLabel('Configurable Dummy Matcher title')
      ->setPath('http://example.com')
      ->setGroup('Configurable Dummy Matcher')
      ->setDescription('Configurable Dummy Matcher description');

    $suggestions->addSuggestion($suggestion);

    return $suggestions;
  }

}
