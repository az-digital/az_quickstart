<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\TableSelect;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformHandlerManager;
use Drupal\webform\Plugin\WebformVariantManager;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformYaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base webform admin settings form.
 */
abstract class WebformAdminConfigBaseForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');

    _webform_config_update($config);

    // Normalizing the data.
    $data = $config->getRawData();
    WebformYaml::normalize($data);
    $config->setData($data);

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Build bulk operation settings for webforms and submissions.
   *
   * @param array $settings
   *   Webform settings.
   * @param string $entity_type_id
   *   The entity type id. (webform or webform_submission)
   *
   * @return array
   *   Bulk operation settings.
   */
  protected function buildBulkOperations(array $settings, $entity_type_id) {
    $element = [
      '#type' => 'details',
      '#title' => ($entity_type_id === 'webform_submission')
        ? $this->t('Submissions bulk operations settings')
        : $this->t('Form bulk operations settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    // Enable.
    $settings += [
      $entity_type_id . '_bulk_form' => TRUE,
    ];
    $element[$entity_type_id . '_bulk_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled webform bulk operations'),
      '#description' => ($entity_type_id === 'webform_submission')
        ? $this->t('If checked, bulk operations will be displayed on the submission results page.')
        : $this->t('If checked, bulk operations will be displayed on the form manager page.'),
      '#return_value' => TRUE,
      '#default_value' => $settings[$entity_type_id . '_bulk_form'],
    ];

    // Actions.
    $options = [];
    $default_actions = [];
    /** @var \Drupal\system\ActionConfigEntityInterface[] $actions */
    $actions = $this->entityTypeManager->getStorage('action')->loadMultiple();
    foreach ($actions as $action) {
      if ($action->getType() === $entity_type_id) {
        $options[$action->id()] = ['label' => $action->label()];
        $default_actions[] = $action->id();
      }
    }
    $settings += [
      $entity_type_id . '_bulk_form_actions' => $default_actions,
    ];
    $element[$entity_type_id . '_bulk_form_actions'] = [
      '#type' => 'webform_tableselect_sort',
      '#title' => ($entity_type_id === 'webform_submission')
        ? $this->t('Submissions selected actions')
        : $this->t('Form selected actions'),
      '#header' => ['label' => $this->t('Selected actions')],
      '#options' => $options,
      '#default_value' => array_combine(
        $settings[$entity_type_id . '_bulk_form_actions'],
        $settings[$entity_type_id . '_bulk_form_actions']
      ),
      '#states' => [
        'visible' => [
          ':input[name="bulk_form_settings[' . $entity_type_id . '_bulk_form]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="bulk_form_settings[' . $entity_type_id . '_bulk_form]"]' => ['checked' => TRUE],
        ],
      ],
      '#element_validate' => [[get_class($this), 'validateBulkFormActions']],
    ];
    WebformElementHelper::fixStatesWrapper($element[$entity_type_id . '_bulk_form_actions']);

    return $element;
  }

  /**
   * Form API callback. Validate bulk form actions.
   */
  public static function validateBulkFormActions(array &$element, FormStateInterface $form_state) {
    $actions_value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    $enabled_parents = $element['#parents'];
    $enabled_parents[1] = str_replace('_actions', '', $enabled_parents[1]);
    $enabled_value = NestedArray::getValue($form_state->getValues(), $enabled_parents);

    if (!empty($enabled_value) && empty($actions_value)) {
      $form_state->setErrorByName(NULL, t('@name field is required.', ['@name' => $element['#title']]));
    }

    // Convert actions associative array of values to an indexed array.
    $actions_value = array_values($actions_value);
    $element['#value'] = $actions_value;
    $form_state->setValueForElement($element, $actions_value);
  }

  /* ************************************************************************ */
  // Exclude plugins.
  /* ************************************************************************ */

  /**
   * Build excluded plugins element.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   A webform element, handler, or exporter plugin manager.
   * @param array $excluded_ids
   *   An array of excluded ids.
   *
   * @return array
   *   A table select element used to excluded plugins by id.
   */
  protected function buildExcludedPlugins(PluginManagerInterface $plugin_manager, array $excluded_ids) {
    $header = [
      'title' => ['data' => $this->t('Title')],
      'id' => ['data' => $this->t('Name'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      'description' => ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
    ];

    $ids = [];
    $options = [];
    $plugins = $this->getPluginDefinitions($plugin_manager);
    foreach ($plugins as $id => $plugin_definition) {
      $ids[$id] = $id;

      $description = [
        'data' => [
          'content' => ['#markup' => $plugin_definition['description']],
        ],
      ];
      if (!empty($plugin_definition['deprecated'])) {
        $description['data']['deprecated'] = [
          '#type' => 'webform_message',
          '#message_message' => $plugin_definition['deprecated_message'],
          '#message_type' => 'warning',
        ];
      }
      $options[$id] = [
        'title' => $plugin_definition['label'],
        'id' => $plugin_definition['id'],
        'description' => $description,
      ];
    }

    $element = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#required' => TRUE,
      '#sticky' => TRUE,
      '#default_value' => array_diff($ids, $excluded_ids),
    ];
    TableSelect::setProcessTableSelectCallback($element);
    return $element;
  }

  /**
   * Convert included ids returned from table select element to excluded ids.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   A webform element, handler, or exporter plugin manager.
   * @param array $included_ids
   *   An array of included_ids.
   *
   * @return array
   *   An array of excluded ids.
   *
   * @see \Drupal\webform\Form\WebformAdminSettingsForm::buildExcludedPlugins
   */
  protected function convertIncludedToExcludedPluginIds(PluginManagerInterface $plugin_manager, array $included_ids) {
    $ids = [];
    $plugins = $this->getPluginDefinitions($plugin_manager);
    foreach ($plugins as $id => $plugin) {
      $ids[$id] = $id;
    }

    $excluded_ids = array_diff($ids, array_filter($included_ids));
    ksort($excluded_ids);
    return $excluded_ids;
  }

  /**
   * Get plugin definitions.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   A webform element, handler, or exporter plugin manager.
   *
   * @return array
   *   Plugin definitions.
   */
  protected function getPluginDefinitions(PluginManagerInterface $plugin_manager) {
    $plugins = $plugin_manager->getDefinitions();
    $plugins = $plugin_manager->getSortedDefinitions($plugins);
    if ($plugin_manager instanceof WebformElementManagerInterface) {
      unset($plugins['webform_element']);
    }
    elseif ($plugin_manager instanceof WebformHandlerManager || $plugin_manager instanceof WebformVariantManager) {
      unset($plugins['broken']);
    }
    return $plugins;
  }

}
