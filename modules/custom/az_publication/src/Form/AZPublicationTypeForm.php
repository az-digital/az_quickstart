<?php

declare(strict_types=1);

namespace Drupal\az_publication\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\az_publication\Entity\AZPublicationTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Publication Type add forms.
 *
 * @ingroup az_publication
 */
class AZPublicationTypeForm extends EntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\az_publication\Entity\AZPublicationTypeInterface $az_publication_type */
    $az_publication_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $az_publication_type->label(),
      '#description' => $this->t("Enter a descriptive label for the Publication Type. This label will be used as the identifier in lists and references throughout the system. Choose a name that clearly and concisely reflects the nature of the publication type. Keep it short yet descriptive."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $az_publication_type->id(),
      '#machine_name' => [
        'exists' => [$this->entityTypeManager->getStorage('az_publication_type'), 'load'],
      ],
      '#disabled' => !$az_publication_type->isNew(),
    ];

    $form['type'] = [
      '#required' => TRUE,
      '#type' => 'select',
      '#title' => $this->t('CSL Type Mapping'),
      '#options' => $az_publication_type->getMappableTypeOptions(),
      '#default_value' => $az_publication_type->get('type'),
      '#description' => $this->t('Select the type of publication this entry corresponds to. Each type aligns with the standards defined in the Citation Style Language (CSL). Your selection here will determine how the publication is formatted and displayed. For a detailed explanation of each type, refer to the CSL documentation: <a href=":csl-docs" target="_blank">CSL Types Specification</a>.', [
        ':csl-docs' => 'https://docs.citationstyles.org/en/stable/specification.html#appendix-iii-types',
      ]),
    ];

    $form['type'] += [
      '#required' => TRUE,
      '#prefix' => '<div id="mapping-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['status'] = [
      '#title' => $this->t('Enabled'),
      '#type' => 'checkbox',
      '#default_value' => $az_publication_type->get('status'),
      '#disabled' => !$az_publication_type->get('status'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\az_publication\Entity\AZPublicationTypeInterface $az_publication_type */
    $az_publication_type = $this->entity;
    // Ensure the entity is of the correct type.
    if (!$az_publication_type instanceof AZPublicationTypeInterface) {
      // Handle the case where $az_publication_type is not the expected type.
      throw new \UnexpectedValueException("Unexpected entity type.");
    }
    // Retrieve and set the 'type' data.
    $type = $form_state->getValue('type');
    $az_publication_type->set('type', $type);
    $status = $az_publication_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Publication Type.', [
          '%label' => $az_publication_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Publication Type.', [
          '%label' => $az_publication_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($az_publication_type->toUrl('collection'));
    return $status;
  }

}
