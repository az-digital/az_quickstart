<?php

namespace Drupal\config_split\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The form for exporting a split.
 */
class ConfigSplitExportForm extends FormBase {

  use ConfigImportFormTrait;
  use StorageCopyTrait;

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
    $comparer = new StorageComparer($this->manager->singleExportPreview($split), $this->manager->singleExportTarget($split));
    $options = [
      'route' => [
        'config_split' => $split->getName(),
        'operation' => 'export',
      ],
      'operation label' => $this->t('Export to split storage'),
    ];
    return $this->buildFormWithStorageComparer($form, $form_state, $comparer, $options, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $split = $this->getSplit();
    $target = $this->manager->singleExportTarget($split);
    self::replaceStorageContents($this->manager->singleExportPreview($split), $target);
    $this->redirect('entity.config_split.collection');
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
