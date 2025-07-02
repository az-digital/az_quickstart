<?php

namespace Drupal\config_split\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The form for de-activating a split.
 */
class ConfigSplitDeactivateForm extends FormBase {

  use ConfigImportFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_split_deactivate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $split = $this->getSplit();

    $comparer = new StorageComparer($this->manager->singleDeactivate($split, FALSE), $this->activeStorage);
    $options = [
      'route' => [
        'config_split' => $split->getName(),
        'operation' => 'deactivate',
      ],
      'operation label' => $this->t('Import all'),
    ];
    $form = $this->buildFormWithStorageComparer($form, $form_state, $comparer, $options);

    $locallyActivated = $this->statusOverride->getSplitOverride($split->getName()) === TRUE;
    $form['deactivate_local_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deactivate locally only'),
      '#description' => $this->t('If this is set, the split config will not be made inactive by default but instead it will be locally overwritten to be inactive.'),
      '#default_value' => !$locallyActivated,
    ];

    if ($locallyActivated) {
      $form['deactivation_notice'] = [
        '#type' => 'markup',
        '#markup' => $this->t('The local activation state override will be removed'),
      ];
    }

    $entity = $this->manager->getSplitEntity($split->getName());
    $form['export_before'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Export the config before deactivating.'),
      '#description' => $this->t('To manually export and see what is exported check <a href="@export-page">the export page</a>.', ['@export-page' => $entity->toUrl('export')->toString()]),
      '#default_value' => !$locallyActivated,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $split = $this->getSplit();

    $override = FALSE;
    if ($form_state->getValue('deactivate_local_only')) {
      $override = TRUE;
    }

    $storage = $this->manager->singleDeactivate($split, $form_state->getValue('export_before'), $override);
    $this->launchImport($storage, $override ? 'inactive' : 'none');
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
      ->andIf(AccessResult::allowedIf($split->get('status')))
      ->addCacheableDependency($split);
  }

}
