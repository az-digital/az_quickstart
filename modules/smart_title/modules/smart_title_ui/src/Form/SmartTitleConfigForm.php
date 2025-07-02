<?php

namespace Drupal\smart_title_ui\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures smart title settings.
 */
class SmartTitleConfigForm extends ConfigFormBase {

  /**
   * Constructs a new UpdateSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    TypedConfigManagerInterface $typedConfigManager,
  ) {
    parent::__construct($configFactory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('config.typed'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['smart_title.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smart_title_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('smart_title.settings');
    $entity_type_definitions = $this->entityTypeManager->getDefinitions();

    // Collecting content entity types which have canonical link template.
    $content_entity_type_filter = function (EntityTypeInterface $entity_type_definition) {
      return ($entity_type_definition instanceof ContentEntityTypeInterface) &&
        $entity_type_definition->entityClassImplements(FieldableEntityInterface::class) &&
        $entity_type_definition->get('field_ui_base_route');
    };
    $valid_content_entity_types = array_filter($entity_type_definitions, $content_entity_type_filter);
    $entity_bundles = [];

    foreach (array_keys($valid_content_entity_types) as $entity_type_id) {
      $label_key = $valid_content_entity_types[$entity_type_id]->getKey('label');

      if ($label_key) {
        $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);

        if (!$base_field_definitions[$label_key]->isDisplayConfigurable('view')) {
          $entity_bundles[$entity_type_id]['label'] = $valid_content_entity_types[$entity_type_id]->getLabel();
          $entity_bundles[$entity_type_id]['bundles'] = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
        }
      }
    }

    $defaults = $config->get('smart_title') ?: [];

    foreach ($entity_bundles as $type => $definitions) {
      $options = [];
      $default = [];
      foreach ($definitions['bundles'] as $key => $info) {
        $options["$type:$key"] = $info['label'];
        if (in_array("$type:$key", $defaults)) {
          $default["$type:$key"] = "$type:$key";
        }
      }

      $form[$type . '_bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Smart Title for @entity-type', ['@entity-type' => $definitions['label']]),
        '#options' => $options,
        '#default_value' => $default,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();
    $smart_title_bundles_setting = $smart_title_bundles = [];

    foreach ($values as $key => $bundle_values) {
      if (strpos($key, '_bundles')) {
        foreach ($bundle_values as $bundle_key => $bundle_value) {
          if ($bundle_value) {
            $smart_title_bundles_setting[] = $bundle_key;
          }
          $smart_title_bundles[] = $bundle_key;
        }
      }
    }

    // Updating entity view displays:
    // Remove smart title where it's not available anymore.
    $display_storage = $this->entityTypeManager->getStorage('entity_view_display');
    $displays = $display_storage->loadMultiple();
    $not_smart_title_capable_bundles = array_diff($smart_title_bundles, $smart_title_bundles_setting);

    foreach ($displays as $display_id => $display) {
      assert($display instanceof EntityViewDisplayInterface);
      [$target_entity_type_id, $target_bundle] = explode('.', $display_id);
      if (in_array("$target_entity_type_id:$target_bundle", $not_smart_title_capable_bundles)) {
        $display->unsetThirdPartySetting('smart_title', 'enabled')
          ->unsetThirdPartySetting('smart_title', 'settings')
          ->save();
      }
    }

    $this->config('smart_title.settings')->set('smart_title', $smart_title_bundles_setting)->save();
    Cache::invalidateTags(['entity_field_info']);
  }

}
