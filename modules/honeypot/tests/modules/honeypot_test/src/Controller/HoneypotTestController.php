<?php

namespace Drupal\honeypot_test\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for honeypot_test routes.
 */
class HoneypotTestController implements ContainerInjectionInterface {

  /**
   * The form_builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a HoneypotTestController.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Page that triggers a programmatic form submission.
   *
   * Returns the validation errors triggered by the form submission as json.
   */
  public function submitFormPage() {
    $form_state = new FormState();
    $values = [
      // cspell:ignore robo
      'name' => 'robo-user',
      'mail' => 'robouser@example.com',
      'op' => 'Submit',
    ];
    $form_state->setValues($values);
    $this->formBuilder->submitForm('\Drupal\user\Form\UserPasswordForm', $form_state);

    return new JsonResponse($form_state->getErrors());
  }

}
