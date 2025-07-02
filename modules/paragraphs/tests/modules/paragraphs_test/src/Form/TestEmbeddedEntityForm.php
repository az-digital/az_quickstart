<?php

namespace Drupal\paragraphs_test\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;

/**
 * A class to build a form that embeds a content entity form.
 *
 * The logic of form processing is based on Layout Builder's InlineBlock
 * form processing.
 */
class TestEmbeddedEntityForm implements FormInterface {

  /**
   * The entity of the embedded form.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * TestEmbeddedEntityForm constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity of the embedded form.
   */
  public function __construct(ContentEntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Get the entity of this form object.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_embedded_entity_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Based on the logic of Layout Builder's InlineBlock form processing,
    // the entity form is being inserted via process callback.
    return [
      'embedded_entity_form' => [
        '#type' => 'container',
        '#process' => [[static::class, 'processEmbeddedEntityForm']],
        '#entity' => $this->entity,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $embedded_form = $form['embedded_entity_form'];
    $this->entity = $embedded_form['#entity'];

    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'default');
    $complete_form_state = $form_state instanceof SubformStateInterface ? $form_state->getCompleteFormState() : $form_state;
    $form_display->extractFormValues($this->entity, $embedded_form, $complete_form_state);
    $this->entity->save();
  }

  /**
   * Process callback to embed an entity form.
   *
   * @param array $element
   *   The containing element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The containing element, with the entity form inserted.
   */
  public static function processEmbeddedEntityForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $element['#entity'];
    EntityFormDisplay::collectRenderDisplay($entity, 'default')->buildForm($entity, $element, $form_state);
    return $element;
  }

}
