<?php

declare(strict_types=1);

namespace Drupal\az_publication\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
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
      '#description' => $this->t("Label for the Publication Type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $az_publication_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\az_publication\Entity\AZPublicationType::load',
      ],
      '#disabled' => !$az_publication_type->isNew(),
    ];

    $form['type'] = [
      '#required' => TRUE,
      '#type' => 'radios',
      '#title' => $this->t('Publication Type Mapping'),
      '#options' => $az_publication_type->getTypeOptions(),
      '#default_value' => $az_publication_type->getType(),
      '#description' => $this->t('The supported types @csl-docs', [
        '@csl-docs' => 'https://docs.citationstyles.org/en/stable/specification.html#appendix-iii-types',
      ]),
    ];

    $form['type'] += [
      '#required' => TRUE,
      '#prefix' => '<div id="mapping-wrapper">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $az_publication_type = $this->entity;
    // Retrieve and set the 'mapping' data.
    $type = $form_state->getValue('type');
    $az_publication_type->setType($type);
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