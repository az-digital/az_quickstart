<?php

namespace Drupal\config_readonly\EventSubscriber;

use Drupal\config_readonly\ConfigReadonlyWhitelistTrait;
use Drupal\config_readonly\ReadOnlyFormEvent;
use Drupal\config_translation\Form\ConfigTranslationFormBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Check if the given form should be read-only.
 */
class ReadOnlyFormSubscriber implements EventSubscriberInterface {
  use ConfigReadonlyWhitelistTrait;

  /**
   * ReadOnlyFormSubscriber constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->setModuleHandler($module_handler);
  }

  /**
   * Form ids to mark as read only.
   *
   * @var array
   */
  protected $readOnlyFormIds = [
    'config_single_import_form',
    'system_modules',
    'system_modules_uninstall',
    'user_admin_permissions',
  ];

  /**
   * {@inheritdoc}
   */
  public function onFormAlter(ReadOnlyFormEvent $event) {
    // Check if the form is a ConfigFormBase or a ConfigEntityListBuilder.
    $form_object = $event->getFormState()->getFormObject();
    $raw_form = $event->getForm();
    $mark_form_read_only = $form_object instanceof ConfigFormBase || $form_object instanceof ConfigEntityListBuilder || $form_object instanceof ConfigTranslationFormBase;

    if (!$mark_form_read_only) {
      $mark_form_read_only = in_array($form_object->getFormId(), $this->readOnlyFormIds);
    }

    // Check if the form is an EntityFormInterface and entity is a config
    // entity.
    if (!$mark_form_read_only && $form_object instanceof EntityFormInterface) {
      $entity = $form_object->getEntity();
      $mark_form_read_only = $entity instanceof ConfigEntityInterface;
    }

    // Don't block particular patterns.
    if ($mark_form_read_only && $form_object instanceof EntityFormInterface) {
      $entity = $form_object->getEntity();
      $name = $entity->getConfigDependencyName();
      if ($this->matchesWhitelistPattern($name)) {
        $mark_form_read_only = FALSE;
      }
    }

    if ($mark_form_read_only && $form_object instanceof ConfigEntityListBuilder) {
      $entity_type = $form_object->getStorage()->getEntityType();
      $name = $entity_type->getConfigPrefix() . '.*';
      if ($this->matchesWhitelistPattern($name)) {
        $mark_form_read_only = FALSE;
      }
    }

    if ($mark_form_read_only && $form_object instanceof ConfigFormBase) {
      // Get the editable configuration names and config targets.
      $editable_config = array_merge($this->getEditableConfigNames($form_object), $this->getConfigTargetNames($raw_form));
      $event->setEditableConfigNames($editable_config);

      // If all editable config is in the whitelist, do not block the form.
      if ($editable_config == array_filter($editable_config, [$this, 'matchesWhitelistPattern'])) {
        $mark_form_read_only = FALSE;
      }
    }

    if ($mark_form_read_only && $form_object instanceof ConfigTranslationFormBase) {
      // Get the translatable configuration names.
      $translatable_config = $this->getTranslatableConfigNames($form_object);

      // If all translatable config is in the whitelist, do not block the form.
      if ($translatable_config == array_filter($translatable_config, [$this, 'matchesWhitelistPattern'])) {
        $mark_form_read_only = FALSE;
      }
    }

    if ($mark_form_read_only) {
      $event->markFormReadOnly();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[ReadOnlyFormEvent::NAME][] = ['onFormAlter', 200];
    return $events;
  }

  /**
   * Get the editable configuration names.
   *
   * @param \Drupal\Core\Form\ConfigFormBase $form
   *   The configuration form.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   *
   * @see \Drupal\Core\Form\ConfigFormBaseTrait::getEditableConfigNames()
   */
  protected function getEditableConfigNames(ConfigFormBase $form): array {
    // Use reflection to work around getEditableConfigNames() as protected.
    // @todo Review in 9.x for API change.
    // @see https://www.drupal.org/node/2095289
    $reflection = new \ReflectionMethod(get_class($form), 'getEditableConfigNames');
    $reflection->setAccessible(TRUE);
    return $reflection->invoke($form);
  }

  /**
   * Get the translatable configuration names.
   *
   * @param \Drupal\config_translation\Form\ConfigTranslationFormBase $form
   *   The configuration form.
   *
   * @return array
   *   An array of configuration object names that are translatable
   *   in this form.
   */
  protected function getTranslatableConfigNames(ConfigTranslationFormBase $form) {
    // Use reflection to work around baseConfigData() as protected.
    $reflection = new \ReflectionProperty(get_class($form), 'baseConfigData');
    $reflection->setAccessible(TRUE);
    return array_keys($reflection->getValue($form));
  }

  /**
   * Get the editable config targets.
   *
   * @param array $form
   *   The raw form.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getConfigTargetNames(array $form): array {
    $config_targets = [];
    foreach ($form as $name => $config) {
      if (isset($config['#config_target'])) {
        $config_targets[$config['#config_target']] = strstr($config['#config_target'], ':', TRUE);
      }
    }
    $config_forms = array_flip($config_targets);
    return array_keys($config_forms);
  }

}
