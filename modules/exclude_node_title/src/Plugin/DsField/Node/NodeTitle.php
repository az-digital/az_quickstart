<?php

namespace Drupal\exclude_node_title\Plugin\DsField\Node;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ds\Plugin\DsField\Field;
use Drupal\ds\Plugin\DsField\Node\NodeTitle as DsNodeTitle;
use Drupal\ds\Plugin\DsField\Title;

/**
 * Extended NodeTitle Display Suite plugin.
 */
class NodeTitle extends DsNodeTitle {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $settings = Title::settingsForm($form, $form_state);

    $config = $this->getConfiguration();
    $settings['exclude_node_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Use Exclude Node Title'),
      '#options' => ['No', 'Yes'],
      '#description' => $this->t('Use the settings for the Exclude Node Title module for the title. Set to "off" to always show title.'),
      '#default_value' => $config['exclude_node_title'],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $summary = Title::settingsSummary($settings);

    $config = $this->getConfiguration();
    if (!empty($config['exclude_node_title'])) {
      $summary[] = $this->t('Use Exclude Node Title: yes');
    }
    else {
      $summary[] = $this->t('Use Exclude Node Title: no');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = Title::defaultConfiguration();

    $configuration['exclude_node_title'] = 1;

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    if ($config['exclude_node_title']) {
      $exclude_manager = \Drupal::service('exclude_node_title.manager');
      if ($exclude_manager->isTitleExcluded($this->entity(), $this->viewMode())) {
        return [
          '#markup' => '',
        ];
      }
    }

    return Field::build();
  }

}
