<?php

namespace Drupal\block_field_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Block field test content' block.
 *
 * @Block(
 *   id = "block_field_test_content",
 *   admin_label = @Translation("Block field test content"),
 *   category = @Translation("Block field test")
 * )
 */
class BlockFieldTestContentBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content' => $this->t('A default value. This block was created at @time', ['@time' => date('Y-m-d h:i:sa')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['content'] = [
      '#type' => 'textarea',
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
  public function build() {
    return [
      '#type' => 'markup',
      '#markup' => $this->configuration['content'],
      '#attributes' => [
        'class' => ['block-field-test-content-block--custom-class'],
        'data-custom-attr' => 'block-field-test-content-block--custom-data-attribute',
      ],
    ];
  }

}
