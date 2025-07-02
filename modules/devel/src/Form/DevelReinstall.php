<?php

namespace Drupal\devel\Form;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Update\UpdateHookRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display a dropdown of installed modules with the option to reinstall them.
 */
class DevelReinstall extends FormBase {

  /**
   * The module installer.
   */
  protected ModuleInstallerInterface $moduleInstaller;

  /**
   * The module extension list.
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * The update hook registry service.
   */
  protected UpdateHookRegistry $updateHookRegistry;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->moduleInstaller = $container->get('module_installer');
    $instance->moduleExtensionList = $container->get('extension.list.module');
    $instance->updateHookRegistry = $container->get('update.update_hook_registry');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'devel_reinstall_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Get a list of all available modules.
    $modules = $this->moduleExtensionList->reset()->getList();

    $uninstallable = array_filter($modules, fn($module): bool => empty($modules[$module->getName()]->info['required'])
      && $this->updateHookRegistry->getInstalledVersion($module->getName()) > UpdateHookRegistry::SCHEMA_UNINSTALLED
      && $module->getName() !== 'devel');

    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];
    $form['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => $this->t('Enter module name'),
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '#devel-reinstall-form',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the module name or description to filter by.'),
      ],
    ];

    // Only build the rest of the form if there are any modules available to
    // uninstall.
    if ($uninstallable === []) {
      return $form;
    }

    $header = [
      'name' => $this->t('Name'),
      'description' => $this->t('Description'),
    ];

    $rows = [];

    foreach ($uninstallable as $module) {
      $name = $module->info['name'] ?: $module->getName();

      $rows[$module->getName()] = [
        'name' => [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '<label class="module-name table-filter-text-source">{{ module_name }}</label>',
            '#context' => ['module_name' => $name],
          ],
        ],
        'description' => [
          'data' => $module->info['description'],
          'class' => ['description'],
        ],
      ];
    }

    $form['reinstall'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#js_select' => FALSE,
      '#empty' => $this->t('No modules are available to uninstall.'),
    ];

    $form['#attached']['library'][] = 'system/drupal.system.modules';

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reinstall'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Form submitted, but no modules selected.
    if (array_filter($form_state->getValue('reinstall')) === []) {
      $form_state->setErrorByName('reinstall', $this->t('No modules selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      $modules = $form_state->getValue('reinstall');
      $reinstall = array_keys(array_filter($modules));
      $this->moduleInstaller->uninstall($reinstall, FALSE);
      $this->moduleInstaller->install($reinstall, FALSE);
      // @todo Revisit usage of DI once https://www.drupal.org/project/drupal/issues/2940148 is resolved.
      $this->messenger()->addMessage($this->t('Uninstalled and installed: %names.', ['%names' => implode(', ', $reinstall)]));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Unable to reinstall modules. Error: %error.', ['%error' => $e->getMessage()]));
    }
  }

}
