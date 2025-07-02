<?php

namespace Drupal\config_split\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The form for activating a split.
 */
class ConfigSplitActivateForm extends FormBase {

  use ConfigImportFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_split_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $split = $this->getSplit();
    $comparer = new StorageComparer($this->manager->singleActivate($split, !$split->get('status')), $this->activeStorage);
    $options = [
      'route' => [
        'config_split' => $split->getName(),
        'operation' => 'activate',
      ],
      'operation label' => $this->t('Import all'),
    ];
    $form = $this->buildFormWithStorageComparer($form, $form_state, $comparer, $options);

    if (!$split->get('status')) {
      $locallyDeactivated = $this->statusOverride->getSplitOverride($split->getName()) === FALSE;
      $form['activate_local_only'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Activate locally only'),
        '#description' => $this->t('If this is set, the split config will not be made active by default but instead it will be locally overwritten to be active.'),
        '#default_value' => !$locallyDeactivated,
      ];

      if ($locallyDeactivated) {
        $form['deactivation_notice'] = [
          '#type' => 'markup',
          '#markup' => $this->t('The local inactivation state override will be removed'),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $split = $this->getSplit();
    $activate = !$split->get('status');
    $override = NULL;
    if ($activate) {
      $override = 'none';
      if ($form_state->getValue('activate_local_only')) {
        $activate = FALSE;
        $override = 'active';
      }
    }

    $storage = $this->manager->singleActivate($split, $activate);
    $this->launchImport($storage, $override);
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param string $config_split
   *   The split name form the route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, string $config_split) {
    $split = $this->manager->getSplitConfig($config_split);
    return AccessResult::allowedIfHasPermission($account, 'administer configuration split')
      ->andIf(AccessResult::allowedIf(!$split->get('status')))
      ->addCacheableDependency($split);
  }

}
