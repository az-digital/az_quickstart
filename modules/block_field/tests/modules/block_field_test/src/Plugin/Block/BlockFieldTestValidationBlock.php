<?php

namespace Drupal\block_field_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Block field test validation' block.
 *
 * @Block(
 *   id = "block_field_test_validation",
 *   admin_label = @Translation("Block field test validation"),
 *   category = @Translation("Block field test")
 * )
 */
class BlockFieldTestValidationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['content'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content'),
      '#default_value' => $this->configuration['content'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['content'] = $form_state->getValue('content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($form_state->getValue('content') == 'error by name') {
      $form_state->setErrorByName('content', 'Come ere boi!');
    }
    if ($form_state->getValue('content') == 'error by element') {
      $form_state->setError($form['content'], 'Gimmie them toez!');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'markup',
      '#markup' => $this->configuration['content'],
    ];
  }

}
