<?php

namespace Drupal\masquerade\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\masquerade\Form\MasqueradeForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Masquerade' block.
 *
 * @Block(
 *   id = "masquerade",
 *   admin_label = @Translation("Masquerade"),
 *   category = @Translation("Forms"),
 * )
 */
class MasqueradeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The masquerade service.
   *
   * @var \Drupal\masquerade\Masquerade
   */
  protected $masquerade;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->formBuilder = $container->get('form_builder');
    $instance->masquerade = $container->get('masquerade');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'show_unmasquerade_link' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['show_unmasquerade_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show unmasquerade link in block'),
      '#description' => $this->t('If checked, this block will show a "Switch back" link when the user is masquerading.'),
      '#default_value' => $this->configuration['show_unmasquerade_link'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['show_unmasquerade_link'] = $form_state->getValue('show_unmasquerade_link');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      // Do not allow masquerade as anonymous user, use private browsing.
      return AccessResult::forbidden();
    }
    if ($this->masquerade->isMasquerading()) {
      $access = $this->configuration['show_unmasquerade_link']
        ? AccessResult::allowed()
        : AccessResult::forbidden();
      return $access->addCacheContexts(['session.is_masquerading']);
    }
    // Display block for all users that has any of masquerade permissions.
    return AccessResult::allowedIfHasPermissions($account, $this->masquerade->getPermissions(), 'OR')
      ->addCacheContexts(['session.is_masquerading']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['session.is_masquerading']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->configuration['show_unmasquerade_link'] && $this->masquerade->isMasquerading()) {
      return [
        [
          '#lazy_builder' => ['masquerade.callbacks:renderSwitchBackLink', []],
          '#create_placeholder' => TRUE,
        ],
      ];
    }
    return $this->formBuilder->getForm(MasqueradeForm::class);
  }

}
