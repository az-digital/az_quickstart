<?php

namespace Drupal\embed\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Error;
use Drupal\embed\EmbedType\EmbedTypeManager;
use Drupal\embed\Entity\EmbedButton;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for embed button forms.
 */
class EmbedButtonForm extends EntityForm {

  /**
   * The embed type plugin manager.
   *
   * @var \Drupal\embed\EmbedType\EmbedTypeManager
   */
  protected $embedTypeManager;

  /**
   * Constructs a new EmbedButtonForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\embed\EmbedType\EmbedTypeManager $embed_type_manager
   *   The embed type plugin manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, EmbedTypeManager $embed_type_manager) {
    $this->setModuleHandler($module_handler);
    $this->setEntityTypeManager($entity_type_manager);
    $this->embedTypeManager = $embed_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.embed.type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\embed\EmbedButtonInterface $button */
    $button = $this->entity;
    $form_state->setTemporaryValue('embed_button', $button);

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $button->label(),
      '#description' => $this->t('The human-readable name of this embed button. This text will be displayed when the user hovers over the CKEditor button. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $button->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$button->isNew(),
      '#machine_name' => [
        'exists' => [EmbedButton::class, 'load'],
      ],
      '#description' => $this->t('A unique machine-readable name for this embed button. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['type_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Embed type'),
      '#options' => $this->embedTypeManager->getDefinitionOptions(),
      '#default_value' => $button->getTypeId(),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateTypeSettings',
        'effect' => 'fade',
      ],
      '#disabled' => !$button->isNew(),
    ];
    if (empty($form['type_id']['#options'])) {
      $this->messenger()->addWarning($this->t('No embed types found.'));
    }

    // Add the embed type plugin settings.
    $form['type_settings'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="embed-type-settings-wrapper">',
      '#suffix' => '</div>',
    ];

    try {
      if ($plugin = $button->getTypePlugin()) {
        $form['type_settings'] = $plugin->buildConfigurationForm($form['type_settings'], $form_state);
      }
    }
    catch (PluginNotFoundException $exception) {
      $this->messenger()->addError($exception->getMessage());
      Error::logException($this->logger('embed'), $exception);
      $form['type_id']['#disabled'] = FALSE;
    }

    $config = $this->config('embed.settings');
    $upload_location = $config->get('file_scheme') . '://' . $config->get('upload_directory') . '/';

    $has_ckeditor5 = $this->moduleHandler->moduleExists('ckeditor5');

    $validate_extensions = $has_ckeditor5 ? 'svg' : 'gif png jpg jpeg svg';
    // First parameter is maximum dimensions, second is minimum dimensions.
    $validate_dimensions = $has_ckeditor5 ? [0, 0] : ['32x32', '16x16'];

    $validators = [];
    if (version_compare(\Drupal::VERSION, '10.2', '<')) {
      $validators['file_validate_extensions'] = [$validate_extensions];
      $validators['file_validate_image_resolution'] = $validate_dimensions;
    }
    else {
      $validators['FileExtension']['extensions'] = $validate_extensions;
      $validators['FileImageDimensions']['maxDimensions'] = $validate_dimensions[0];
      $validators['FileImageDimensions']['minDimensions'] = $validate_dimensions[1];
    }

    $form['icon_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Button icon'),
      '#upload_location' => $upload_location,
      '#upload_validators' => $validators,
    ];

    if (!$button->isNew()) {
      $form['icon_reset'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Reset to default icon'),
        '#access' => !empty($button->icon),
      ];

      $form['icon_preview'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Current icon preview'),
      ];
      $form['icon_preview']['image'] = [
        '#theme' => 'image',
        '#uri' => $button->getIconUrl(),
        '#alt' => $this->t('Preview of @label button icon', ['@label' => $button->label()]),
        '#height' => 32,
        '#width' => 32,
      ];

      if ($this->moduleHandler->moduleExists('ckeditor') && !$has_ckeditor5) {
        // Show an even nicer preview with CKEditor4 being used.
        $form['icon_preview']['image']['#prefix'] = '<div data-toolbar="active" role="form" class="ckeditor-toolbar ckeditor-toolbar-active clearfix"><ul class="ckeditor-active-toolbar-configuration" role="presentation" aria-label="CKEditor toolbar and button configuration."><li class="ckeditor-row" role="group" aria-labelledby="ckeditor-active-toolbar"><ul class="ckeditor-toolbar-groups clearfix js-sortable"><li class="ckeditor-toolbar-group" role="presentation" data-drupal-ckeditor-type="group" data-drupal-ckeditor-toolbar-group-name="Embed button icon preview" tabindex="0"><h3 class="ckeditor-toolbar-group-name" id="ckeditor-toolbar-group-aria-label-for-formatting">Embed button icon preview</h3><ul class="ckeditor-buttons ckeditor-toolbar-group-buttons js-sortable" role="toolbar" data-drupal-ckeditor-button-sorting="target" aria-labelledby="ckeditor-toolbar-group-aria-label-for-formatting"><li data-drupal-ckeditor-button-name="Bold" class="ckeditor-button"><a href="#" role="button" title="' . $button->label() . '" aria-label="' . $button->label() . '"><span class="cke_button_icon">';
        $form['icon_preview']['image']['#suffix'] = '</span></a></li></ul></li></ul></div>';
        $form['icon_preview']['#attached']['library'][] = 'ckeditor/drupal.ckeditor.admin';
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\embed\EmbedButtonInterface $button */
    $button = $this->entity;

    // Run embed type plugin validation.
    if ($plugin = $button->getTypePlugin()) {
      $plugin_form_state = clone $form_state;
      $plugin_form_state->setValues($button->getTypeSettings());
      $plugin->validateConfigurationForm($form['type_settings'], $plugin_form_state);
      if ($errors = $plugin_form_state->getErrors()) {
        foreach ($errors as $name => $error) {
          $form_state->setErrorByName($name, $error);
        }
      }
      $form_state->setValue('type_settings', $plugin_form_state->getValues());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\embed\EmbedButtonInterface $button */
    $button = $this->entity;

    // Run embed type plugin submission.
    $plugin = $button->getTypePlugin();
    $plugin_form_state = clone $form_state;
    $plugin_form_state->setValues($button->getTypeSettings());
    $plugin->submitConfigurationForm($form['type_settings'], $plugin_form_state);
    $form_state->setValue('type_settings', $plugin->getConfiguration());
    $button->set('type_settings', $plugin->getConfiguration());

    // If a file was uploaded to be used as the icon, get an encoded URL to be
    // stored in the config entity.
    $icon_fid = $form_state->getValue(['icon_file', '0']);
    if (!empty($icon_fid) && $file = $this->entityTypeManager->getStorage('file')->load($icon_fid)) {
      $file->setPermanent();
      $file->save();
      $button->set('icon', EmbedButton::convertImageToEncodedData($file->getFileUri()));
    }
    elseif ($form_state->getValue('icon_reset')) {
      $button->set('icon', []);
    }

    $status = $button->save();

    $t_args = ['%label' => $button->label()];

    if ($status === SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The embed button %label has been updated.', $t_args));
      $this->logger('embed')->info('Updated embed button %label.', $t_args);
    }
    elseif ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The embed button %label has been added.', $t_args));
      $this->logger('embed')->info('Added embed button %label.', $t_args);
    }

    $form_state->setRedirectUrl($button->toUrl());
    return $status;
  }

  /**
   * Ajax callback to update the form fields which depend on embed type.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with updated options for the embed type.
   */
  public function updateTypeSettings(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Update options for entity type bundles.
    $response->addCommand(new ReplaceCommand(
      '#embed-type-settings-wrapper',
      $form['type_settings']
    ));

    return $response;
  }

}
