<?php

namespace Drupal\crop\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for crop type deletion.
 */
class CropTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * String translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translation;

  /**
   * Constructs a new CropTypeDeleteForm object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation manager.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->translation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the crop type %type?', ['%type' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('crop.overview_types');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $count = $this->entityTypeManager->getStorage('crop')->getQuery()
      ->condition('type', $this->entity->id())
      ->accessCheck(TRUE)
      ->count()
      ->execute();
    if ($count) {
      $form['#title'] = $this->getQuestion();
      $form['description'] = [
        '#prefix' => '<p>',
        '#markup' => $this->translation->formatPlural($count, '%type is used by 1 piece of content on your site. You can not remove this content type until you have removed all of the %type content.', '%type is used by @count pieces of content on your site. You may not remove %type until you have removed all of the %type content.', ['%type' => $this->entity->label()]),
        '#suffix' => '</p>',
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $t_args = ['%name' => $this->entity->label()];
    $this->messenger()->addMessage($this->t('The crop type %name has been deleted.', $t_args));
    $this->logger('crop')->notice('Deleted crop type %name.', $t_args);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
