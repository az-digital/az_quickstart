<?php

namespace Drupal\workbench_access\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the access scheme form.
 */
class AccessSchemeForm extends EntityForm {

  /**
   * Access scheme entity.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $entity;

  /**
   * The plugin form factory.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Creates an instance of WorkflowStateEditForm.
   *
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $pluginFormFactory
   *   The plugin form factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The core messenger service.
   */
  public function __construct(PluginFormFactoryInterface $pluginFormFactory, MessengerInterface $messenger) {
    $this->pluginFormFactory = $pluginFormFactory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin_form.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $access_scheme = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $access_scheme->label(),
      '#description' => $this->t("Label for the Access scheme."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $access_scheme->id(),
      '#machine_name' => [
        'exists' => '\Drupal\workbench_access\Entity\AccessScheme::load',
      ],
      '#disabled' => !$access_scheme->isNew(),
    ];

    $form['plural_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plural Label'),
      '#maxlength' => 255,
      '#default_value' => $access_scheme->getPluralLabel(),
      '#description' => $this->t("Plural Label for the Access scheme."),
      '#required' => TRUE,
    ];

    if ($access_scheme->getAccessScheme()->hasFormClass('configure')) {
      $form['scheme_settings'] = [
        '#tree' => TRUE,
      ];
      $subform_state = SubformState::createForSubform($form['scheme_settings'], $form, $form_state);
      $form['scheme_settings'] += $access_scheme->getAccessScheme()
        ->buildConfigurationForm($form['scheme_settings'], $subform_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $access_scheme = $this->entity;
    if ($access_scheme->getAccessScheme()->hasFormClass('configure')) {
      $subform_state = SubformState::createForSubform($form['scheme_settings'], $form, $form_state);
      $access_scheme->getAccessScheme()
        ->validateConfigurationForm($form['scheme_settings'], $subform_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $access_scheme = $this->entity;

    if ($access_scheme->getAccessScheme()->hasFormClass('configure')) {
      $subform_state = SubformState::createForSubform($form['scheme_settings'], $form, $form_state);
      $access_scheme->getAccessScheme()
        ->submitConfigurationForm($form['scheme_settings'], $subform_state);
    }
    $this->messenger->addMessage($this->t('Saved the %label Access scheme.', [
      '%label' => $access_scheme->label(),
    ]));

    return $access_scheme->save();
  }

}
