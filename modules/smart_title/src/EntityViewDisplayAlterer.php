<?php

namespace Drupal\smart_title;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity view display form alterer class for Smart Title.
 */
class EntityViewDisplayAlterer implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an EntityViewDisplayAlterer.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Adds Smart Title to the entity form.
   *
   * @param array $form
   *   The renderable array of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function addSmartTitle(array &$form, FormStateInterface $form_state) {
    if (!($entity = static::getViewDisplayEntityFromFormState($form_state))) {
      return;
    }

    if ($entity->getThirdPartySetting('layout_builder', 'enabled')) {
      return;
    }

    $smart_title_config = $this->configFactory->get('smart_title.settings')->get('smart_title');
    $target_entity_type_id = $entity->getTargetEntityTypeId();
    $target_entity_bundle = $entity->getTargetBundle();

    // Add Smart Title checkbox to the entity view display form.
    if ($smart_title_config && in_array("$target_entity_type_id:$target_entity_bundle", $smart_title_config)) {
      $this->addSmartTitleBuilder($form, $form_state);
    }
    else {
      return;
    }

    // Hide the extra field if smart title isn't used on this view display.
    if (!$entity->getThirdPartySetting('smart_title', 'enabled', FALSE)) {
      unset($form['#extra'][array_search('smart_title', $form['#extra'])]);
      unset($form['fields']['smart_title']);

      return;
    }

    $provide_form = !empty($form_state->getStorage()['plugin_settings_edit']) && $form_state->getStorage()['plugin_settings_edit'] === 'smart_title';
    $smart_title = &$form['fields']['smart_title'];
    $smart_title['plugin']['settings_edit_form'] = [];

    if ($smart_title['region']['#default_value'] !== 'hidden') {
      // Extra field is set to be visible.
      // Getting our settings: the active config, or if we have temporary
      // then those.
      $smart_title_settings = $form_state->get('smart_title_temp_values') ?:
        $entity->getThirdPartySetting('smart_title', 'settings', _smart_title_defaults($entity->getTargetEntityTypeId(), TRUE));

      if ($provide_form) {
        unset($smart_title['settings_summary']);
        unset($smart_title['settings_edit']);

        $smart_title['#attributes']['class'][] = 'field-plugin-settings-editing';
        $smart_title['plugin']['#cell_attributes'] = ['colspan' => 3];
        $smart_title['plugin']['settings_edit_form'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['field-plugin-settings-edit-form']],
          '#parents' => [
            'fields',
            'smart_title',
            'settings_edit_form',
          ],
          'label' => [
            '#markup' => $this->t('Format settings:'),
          ],
          'settings' => $this->getSettingsInputsFromSettings($smart_title_settings, $entity),
          'third_party_settings' => [],
          'actions' => [
            '#type' => 'actions',
            'save_settings' => [
              '#submit' => [
                [get_class($this), 'multistepSubmit'],
                '::multistepSubmit',
              ],
              '#ajax' => [
                'callback' => '::multistepAjax',
                'wrapper' => 'field-display-overview-wrapper',
                'effect' => 'fade',
              ],
              '#field_name' => 'smart_title',
              '#type' => 'submit',
              '#button_type' => 'primary',
              '#name' => 'smart_title_plugin_settings_update',
              '#value' => $this->t('Update'),
              '#op' => 'update',
            ],
            'cancel_settings' => [
              '#submit' => [
                [get_class($this), 'multistepSubmit'],
                '::multistepSubmit',
              ],
              '#ajax' => [
                'callback' => '::multistepAjax',
                'wrapper' => 'field-display-overview-wrapper',
                'effect' => 'fade',
              ],
              '#field_name' => 'smart_title',
              '#type' => 'submit',
              '#name' => 'smart_title_plugin_settings_cancel',
              '#value' => $this->t('Cancel'),
              '#op' => 'cancel',
              '#limit_validation_errors' => [
                [
                  'fields',
                  'smart_title',
                  'type',
                ],
              ],
            ],
          ],
        ];

        $smart_title['plugin']['settings_edit_form']['label']['#markup'] .= ' <span class="plugin-name">Smart Title</span>';
      }

      if (!$provide_form) {
        $smart_title['settings_summary'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="field-plugin-summary">{{ summary|safe_join("<br />") }}</div>',
          '#context' => [
            'summary' => $this->getSummaryFromSettings($smart_title_settings, $entity),
          ],
          '#cell_attributes' => ['class' => ['field-plugin-summary-cell']],
        ];

        $smart_title['settings_edit'] = [
          '#submit' => [
            [get_class($this), 'multistepSubmit'],
            '::multistepSubmit',
          ],
          '#ajax' => [
            'callback' => '::multistepAjax',
            'wrapper' => 'field-display-overview-wrapper',
            'effect' => 'fade',
          ],
          '#field_name' => 'smart_title',
          '#type' => 'image_button',
          '#name' => 'smart_title_settings_edit',
          '#src' => 'core/misc/icons/787878/cog.svg',
          '#attributes' => [
            'class' => ['field-plugin-settings-edit'],
            'alt' => $this->t('Edit'),
          ],
          '#op' => 'edit',
          '#limit_validation_errors' => [['fields', 'smart_title', 'type']],
          '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
          '#suffix' => '</div>',
        ];
      }
    }

    // Add smart title form submit handler.
    if (!empty($form['actions']) && is_array($form['actions'])) {
      $element_keys = Element::children($form['actions']);
      foreach ($element_keys as $element_key) {
        if (
          !isset($form['actions'][$element_key]['#type']) ||
          $form['actions'][$element_key]['#type'] !== 'submit'
        ) {
          continue;
        }

        $submit_callbacks = $form['actions'][$element_key]['#submit'] ?? [];
        array_unshift($submit_callbacks,
          [get_class($this), 'submitSmartTitleForm']
        );

        $form['actions'][$element_key]['#submit'] = $submit_callbacks;
      }
    }
    $submit_callbacks = $form['#submit'] ?? [];
    array_unshift($submit_callbacks, [get_class($this), 'submitSmartTitleForm']);
    $form['#submit'] = $submit_callbacks;
  }

  /**
   * Submit callback for saving the smart title configuration.
   *
   * @param array $form
   *   The renderable array of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function submitSmartTitleForm(array &$form, FormStateInterface $form_state) {
    if (!($entity = static::getViewDisplayEntityFromFormState($form_state))) {
      return;
    }

    // Check that Smart Title is/should be enabled.
    if ((bool) $form_state->getValue('smart_title__enabled')) {
      $settings_to_save = (bool) $form_state->get('smart_title_temp_values') ?
        $form_state->get('smart_title_temp_values') :
        $entity->getThirdPartySetting('smart_title', 'settings', []);
      $field_values = $form_state->getValue('fields', ['smart_title' => []]);

      // If format settings form was opened when the view display form was asked
      // to save its config, we want to save values from that format settings
      // subform.
      if (!empty($field_values['smart_title']['settings_edit_form'])) {
        $settings_to_save = (bool) $field_values['smart_title']['settings_edit_form']['settings'] ?
          $field_values['smart_title']['settings_edit_form']['settings'] : [];
        $settings_to_save['smart_title__classes'] = array_values(array_filter(explode(' ', $settings_to_save['smart_title__classes'])));
      }

      // If field is hidden, remove our settings.
      if (!empty($field_values['smart_title']['region']) && $field_values['smart_title']['region'] === 'hidden') {
        $entity->unSetThirdPartySetting('smart_title', 'settings');
      }
      else {
        $settings_to_save += _smart_title_defaults($entity->getTargetEntityTypeId(), TRUE);
        // Save the (possibly new) config.
        $entity->setThirdPartySetting('smart_title', 'settings', $settings_to_save);
      }
    }
  }

  /**
   * Multi step submit callback for saving the temporary Smart Title config.
   *
   * @param array $form
   *   The renderable array of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function multistepSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $field_values = $form_state->getValue('fields', [
      'smart_title' => ['settings_edit_form' => []],
    ]);

    if (
      $trigger['#op'] === 'update' &&
      !empty($field_values['smart_title']['settings_edit_form'])
    ) {
      $settings_to_save = !empty($field_values['smart_title']['settings_edit_form']['settings']) ?
        $field_values['smart_title']['settings_edit_form']['settings'] : [];

      if (isset($settings_to_save['smart_title__classes'])) {
        $settings_to_save['smart_title__classes'] = array_values(array_filter(explode(' ', $settings_to_save['smart_title__classes'])));
      }
      $form_state->set('smart_title_temp_values', $settings_to_save);
    }
  }

  /**
   * Add Smart Title checkbox to the entity view display form.
   *
   * @param array $form
   *   The renderable array of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   */
  protected function addSmartTitleBuilder(array &$form, FormStateInterface $form_state) {
    if (!($form_state->getFormObject() instanceof EntityFormInterface)) {
      return;
    }

    $entity = $form_state->getFormObject()->getEntity();
    if (!($entity instanceof EntityViewDisplayInterface)) {
      return;
    }

    $form['smart_title'] = [
      '#type' => 'details',
      '#title' => $this->t('Smart Title'),
      '#group' => 'additional_settings',
    ];

    $form['smart_title']['smart_title__enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make entity title configurable'),
      '#description' => $this->t('Check this box if you would like a configurable entity label for this view mode.'),
      '#default_value' => $entity->getThirdPartySetting('smart_title', 'enabled', FALSE),
    ];

    $form['#entity_builders'][] = [get_class($this), 'smartTitleBuilder'];
  }

  /**
   * Entity builder for Smart Title.
   *
   * @param string $entity_type_id
   *   The id of the entity views display config entity type. Here, this will be
   *   always 'entity_view_display'.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display
   *   The entity view display config entity.
   * @param array $form
   *   The renderable array of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   */
  public static function smartTitleBuilder($entity_type_id, EntityViewDisplayInterface $entity_view_display, array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('smart_title__enabled')) {
      $entity_view_display->setThirdPartySetting('smart_title', 'enabled', TRUE);
    }
    else {
      $entity_view_display
        ->setThirdPartySetting('smart_title', 'enabled', FALSE)
        ->unsetThirdPartySetting('smart_title', 'settings')
        ->removeComponent('smart_title');
    }
  }

  /**
   * Returns the summary of Smart Title.
   *
   * @param string[] $smart_title_settings
   *   The active smart title settings.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity
   *   The view display config entity.
   *
   * @return string[]
   *   The summary (each array value is a line).
   */
  protected function getSummaryFromSettings(array $smart_title_settings, EntityViewDisplayInterface $entity) {
    $summary = [];
    foreach ($smart_title_settings as $key => $value) {
      if ($key === 'smart_title__link') {
        if ((bool) $value) {
          $summary[] = _smart_title_defaults('', NULL, 'smart_title__link')['label'];
        }
        continue;
      }

      if ($key === 'smart_title__classes') {
        $value = empty($smart_title_settings['smart_title__tag']) ? FALSE : implode(', ', $value);
      }

      if ((bool) $value) {
        $summary[] = _smart_title_defaults('', NULL, $key)['label'] . ': ' . $value;
      }
    }

    return $summary;
  }

  /**
   * Returns the input elements for smart title.
   *
   * @param string[] $smart_title_settings
   *   The active smart title settings.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity
   *   The view display config entity.
   *
   * @return array
   *   The form input fields as renderable array.
   */
  protected function getSettingsInputsFromSettings(array $smart_title_settings, EntityViewDisplayInterface $entity) {
    return [
      'smart_title__tag' => [
        '#type' => 'select',
        '#title' => _smart_title_defaults('', NULL, 'smart_title__tag')['label'],
        '#options' => _smart_title_tag_options(),
        '#default_value' => $smart_title_settings['smart_title__tag'],
        '#empty_value' => '',
      ],
      'smart_title__classes' => [
        '#type' => 'textfield',
        '#title' => _smart_title_defaults('', NULL, 'smart_title__classes')['label'],
        '#default_value' => implode(' ', $smart_title_settings['smart_title__classes']),
        '#states' => [
          'invisible' => [
            ':input[name="fields[smart_title][settings_edit_form][settings][smart_title__tag]"]' => [
              'value' => '',
            ],
          ],
        ],
      ],
      'smart_title__link' => [
        '#type' => 'checkbox',
        '#title' => _smart_title_defaults('', NULL, 'smart_title__link')['label'],
        '#default_value' => $smart_title_settings['smart_title__link'],
      ],
    ];
  }

  /**
   * Gets the entity view display from the given form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null
   *   The entity view display entity from the form state, or NULL if cannot be
   *   retrieved one.
   */
  private static function getViewDisplayEntityFromFormState(FormStateInterface $form_state) {
    if (!($form_state->getFormObject() instanceof EntityFormInterface)) {
      return NULL;
    }

    $entity = $form_state->getFormObject()->getEntity();

    return $entity instanceof EntityViewDisplayInterface ? $entity : NULL;
  }

}
