<?php

namespace Drupal\metatag_custom_tags\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\metatag_custom_tags\MetaTagCustomTagInterface;

/**
 * Form handler for the MetaTag Custom Tag entity type.
 *
 * @package Drupal\metatag_custom_tags\Form
 */
class MetaTagCustomTagForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {

    $form = parent::form($form, $form_state);

    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#description' => $this->t('Specify the name of the custom tag.'),
      '#required' => TRUE,
      '#default_value' => $entity->label() ?? '',
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#description' => $this->t('Specify the description of the Custom tag.'),
      '#required' => TRUE,
      '#default_value' => $entity->get('description') ?? '',
    ];

    $form['htmlElement'] = [
      '#type' => 'select',
      '#title' => $this->t('HTML element'),
      '#options' => [
        'meta' => $this->t('Meta'),
        'link' => $this->t('Link'),
      ],
      '#description' => $this->t('Select the HTML element of the Custom tag.'),
      '#required' => TRUE,
      '#default_value' => $entity->get('htmlElement') ?? 'meta',
    ];

    $form['htmlNameAttribute'] = [
      '#type' => 'select',
      '#title' => $this->t('Name attribute'),
      '#options' => [
        'name' => $this->t('Name'),
        'property' => $this->t('Property'),
        'http-equiv' => $this->t('Http Equiv'),
        'itemprop' => $this->t('Item Prop'),
        'rel' => $this->t('Rel'),
      ],
      '#description' => $this->t('Select the Name attribute of the Custom tag.'),
      '#required' => TRUE,
      '#default_value' => $entity->get('htmlNameAttribute') ?? 'name',
    ];

    $form['htmlValueAttribute'] = [
      '#type' => 'select',
      '#title' => $this->t('Value attribute'),
      '#options' => [
        'content' => $this->t('Content'),
        'href' => $this->t('Href'),
      ],
      '#description' => $this->t('Select the Value attribute of the Custom tag.'),
      '#required' => TRUE,
      '#default_value' => $entity->get('htmlValueAttribute') ?? 'content',
    ];

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
        \SAVED_NEW => $this->t('Created %label Custom tag.', $message_args),
        \SAVED_UPDATED => $this->t('Updated %label Custom tag.', $message_args),
      }
    );
    \Drupal::service('plugin.manager.metatag.tag')->clearCachedDefinitions();
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

  /**
   * Helper function to check whether the configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('metatag_custom_tag')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\metatag_custom_tags\MetaTagCustomTagInterface $metatag_custom_tag
   *   Custom tag entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translated route title.
   */
  public function getTitle(MetaTagCustomTagInterface $metatag_custom_tag): TranslatableMarkup {
    return $this->t('Edit Custom tag @label', [
      '@label' => $metatag_custom_tag->label(),
    ]);
  }

}
