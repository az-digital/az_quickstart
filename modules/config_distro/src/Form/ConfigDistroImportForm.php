<?php

namespace Drupal\config_distro\Form;

use Drupal\config\Form\ConfigSync;
use Drupal\Core\Config\NullStorage;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Construct the storage changes in a configuration synchronization form.
 *
 * @phpstan-ignore-next-line */
class ConfigDistroImportForm extends ConfigSync {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $class = parent::create($container);
    // Substitute our storage for the default one.
    $class->syncStorage = $container->get('config_distro.storage.distro');
    // Prevent snapshot messages by using a storage that
    // won't have core.extension.
    // @see ConfigSync::buildForm().
    $class->snapshotStorage = new NullStorage();
    return $class;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_distro_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $storage_comparer = new StorageComparer($this->syncStorage, $this->activeStorage);
    // Store the comparer for use in the submit.
    $form_state->set('storage_comparer', $storage_comparer);

    foreach ($storage_comparer->getAllCollectionNames() as $collection) {
      if (isset($form[$collection])) {
        foreach (array_keys($form[$collection]) as $config_change_type) {
          if (isset($form[$collection][$config_change_type]['list'])) {
            foreach ($form[$collection][$config_change_type]['list']['#rows'] as &$row) {
              $config_name = $row['name'];
              if ($config_change_type == 'rename') {
                $names = $storage_comparer->extractRenameNames($config_name);
                $route_options = [
                  'source_name' => $names['old_name'],
                  'target_name' => $names['new_name'],
                ];
              }
              else {
                $route_options = ['source_name' => $config_name];
              }
              if ($collection != StorageInterface::DEFAULT_COLLECTION) {
                $route_name = 'config_distro.diff_collection';
                $route_options['collection'] = $collection;
              }
              else {
                $route_name = 'config_distro.diff';
              }
              // Set the diff url to our own.
              $row['operations']['data']['#links']['view_diff']['url'] = Url::fromRoute($route_name, $route_options);
            }
          }
        }
      }
    }

    return $form;
  }

}
