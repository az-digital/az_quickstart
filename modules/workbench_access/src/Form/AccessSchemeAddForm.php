<?php

namespace Drupal\workbench_access\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for adding access schemes.
 */
class AccessSchemeAddForm extends EntityForm {

  /**
   * Access scheme entity.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $entity;

  /**
   * Plugin manager.
   *
   * @var \Drupal\workbench_access\WorkbenchAccessManagerInterface
   */
  protected $pluginManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new AccessSchemeAddForm object.
   *
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface $manager
   *   Plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The core messenger service.
   */
  public function __construct(WorkbenchAccessManagerInterface $manager, MessengerInterface $messenger) {
    $this->pluginManager = $manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workbench_access.scheme'),
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

    $form['scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('Access scheme'),
      '#options' => array_map(function (array $definition) {
        return $definition['label'];
      }, $this->pluginManager->getDefinitions()),
      '#description' => $this->t('Select the access scheme provider to use.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $access_scheme = $this->entity;
    $access_scheme->save();

    $this->messenger->addMessage($this->t('Saved the %label Access scheme.', [
      '%label' => $access_scheme->label(),
    ]));
    $form_state->setRedirectUrl($access_scheme->toUrl('edit-form'));
    return $access_scheme;
  }

}
