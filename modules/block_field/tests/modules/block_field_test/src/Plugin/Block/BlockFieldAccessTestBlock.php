<?php

namespace Drupal\block_field_test\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Block field test access' block.
 *
 * @Block(
 *   id = "block_field_test_access",
 *   admin_label = @Translation("Block field test access"),
 *   category = @Translation("Block field test")
 * )
 */
class BlockFieldAccessTestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'access' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Access'),
      '#default_value' => $this->configuration['access'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['access'] = $form_state->getValue('access');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'markup',
      '#markup' => '<div> Custom access tag. </div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($this->configuration['access'])->addCacheTags(['custom_tag']);
  }

}
