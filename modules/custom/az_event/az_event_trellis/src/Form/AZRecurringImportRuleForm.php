<?php

declare(strict_types=1);

namespace Drupal\az_event_trellis\Form;

use Drupal\az_event_trellis\Entity\AZRecurringImportRule;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Trellis Event Import form.
 */
final class AZRecurringImportRuleForm extends EntityForm {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity repository.
   *
   * @var \Drupal\az_event_trellis\TrellisHelper
   */
  protected $trellisHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->entityRepository = $container->get('entity.repository');
    $instance->trellisHelper = $container->get('az_event_trellis.trellis_helper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {

    $form = parent::form($form, $form_state);
    /** @var \Drupal\az_event_trellis\Entity\AZRecurringImportRule $entity */
    $entity = $this->entity;
    // Fetch the list of attributes mapped by the API.
    $mappings = $this->trellisHelper->getAttributeMappings();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [AZRecurringImportRule::class, 'load'],
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $entity->status(),
    ];

    $form['owner'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Owner'),
      '#maxlength' => 255,
      '#default_value' => $entity->get('owner'),
    ];

    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#maxlength' => 255,
      '#default_value' => $entity->get('host'),
    ];

    $form['keyword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword'),
      '#maxlength' => 255,
      '#default_value' => $entity->get('keyword'),
    ];

    $form['attributes']['#tree'] = TRUE;

    // Get the different attributes available.
    $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->accessCheck(TRUE)
      ->addTag('taxonomy_term_access')
      ->condition('vid', 'az_enterprise_attributes')
      ->condition('parent', 0)
      ->sort('name')
      // Only fetch attributes that have an API mapping.
      ->condition('field_az_attribute_key', array_keys($mappings), 'IN');
    $attributes = $query->execute();
    $attributes = Term::loadMultiple($attributes);

    // Build attribute select lists.
    foreach ($attributes as $attribute) {
      $options = [];
      $key = $mappings[$attribute->field_az_attribute_key->value];
      $id = $attribute->id();

      // Find the options the attribute has, in order.
      $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
        ->accessCheck(TRUE)
        ->addTag('taxonomy_term_access')
        ->condition('vid', 'az_enterprise_attributes')
        ->condition('parent', $id)
        ->sort('name')
        ->condition('field_az_attribute_key', '', '<>');
      $terms = $query->execute();
      $terms = Term::loadMultiple($terms);
      foreach ($terms as $term) {
        $options[$term->field_az_attribute_key->value] = $this->entityRepository->getTranslationFromContext($term)->label();
      }

      // Build the select element for the attribute.
      $form['attributes'][$key] = [
        '#type' => 'select',
        '#options' => $options,
        '#empty_option' => $this->t('- Any -'),
        '#empty_value' => '',
        '#title' => $this->entityRepository->getTranslationFromContext($attribute)->label(),
        '#required' => FALSE,
        '#default_value' => $entity->get('attributes')[$key] ?? NULL,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $this->messenger()->addStatus(
      match($result) {
        \SAVED_NEW => $this->t('Created new Trellis event import settings %label.', $message_args),
        \SAVED_UPDATED => $this->t('Updated new Trellis event import settings %label.', $message_args),
      }
    );
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
