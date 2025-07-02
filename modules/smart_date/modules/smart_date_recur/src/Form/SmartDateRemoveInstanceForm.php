<?php

namespace Drupal\smart_date_recur\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\smart_date_recur\Controller\Instances;
use Drupal\smart_date_recur\Entity\SmartDateOverride;
use Drupal\smart_date_recur\Entity\SmartDateRule;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an instance cancellation confirmation form for Smart Date.
 */
class SmartDateRemoveInstanceForm extends ConfirmFormBase {
  /**
   * The class resolver service.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * ID of the rrule being used.
   *
   * @var \Drupal\smart_date_recur\Entity\SmartDateRule
   */
  protected $rrule;

  /**
   * Index of the instance to delete.
   *
   * @var int
   */
  protected $index;

  /**
   * ID of an existing override.
   *
   * @var int
   */
  protected $oid;

  /**
   * Definition of form entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a smart date instance removal confirmation form.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(ClassResolverInterface $classResolver, EntityTypeManagerInterface $entityTypeManager) {
    $this->classResolver = $classResolver;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('class_resolver'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "smart_date_recur_remove_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?SmartDateRule $rrule = NULL, ?string $index = NULL, $ajax = FALSE) {
    $this->rrule = $rrule;
    $this->index = $index;
    $query = $this->entityTypeManager->getStorage('smart_date_override')->getQuery();
    $query->condition('rrule', $rrule->id())
      ->condition('rrule_index', $index);
    $result = $query->accessCheck(TRUE)->execute();
    if ($result && $override = SmartDateOverride::load(array_pop($result))) {
      $this->oid = $override->id();
    }
    $form = parent::buildForm($form, $form_state);
    if ($ajax) {
      $this->addAjaxWrapper($form);
      $form['actions']['cancel']['#attributes']['class'][] = 'use-ajax';
      $form['actions']['cancel']['#url']->setRouteParameter('modal', TRUE);
      $form['actions']['submit']['#ajax'] = ['callback' => '::ajaxSubmit'];
    }
    return $form;
  }

  /**
   * Ajax submit function.
   *
   * @param array $form
   *   The form values being submitted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state being submitted.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response from the AJAX form submit.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->disableRedirect();
    /** @var \Drupal\smart_date_recur\Controller\Instances $instanceController */
    $instanceController = $this->classResolver->getInstanceFromDefinition(Instances::class);
    $instanceController->setSmartDateRule($this->rrule);
    $instanceController->setUseAjax(TRUE);
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#manage-instances', $instanceController->listInstancesOutput()));
    return $response;
  }

  /**
   * Adding a wrapper to the form, for ajax targeting.
   *
   * @param array $form
   *   The form array to be enclosed.
   */
  protected function addAjaxWrapper(array &$form) {
    $form['#prefix'] = '<div id="manage-instances">';
    $form['#suffix'] = '</div>';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $question = $this
      ->t('Are you sure you want to remove this instance?');
    if ($this->oid) {
      $question .= ' ' . $this
        ->t('Your existing overridden data will be deleted.');
    }
    return $question;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $rrule = $this->rrule->id();
    return new Url('smart_date_recur.instances', ['rrule' => $rrule]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this
      ->t('Remove Instance');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this
      ->t('You will be able to restore the instance if necessary.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\smart_date_recur\Controller\Instances $instanceController */
    $instanceController = $this->classResolver->getInstanceFromDefinition(Instances::class);
    $instanceController->setSmartDateRule($this->rrule);
    $instanceController->removeInstance($this->index, $this->oid);

    if (!isset($form['actions']['cancel'])) {
      /** @var \Drupal\smart_date_recur\Controller\Instances $instanceController */
      $instanceController = $this->classResolver->getInstanceFromDefinition(Instances::class);
      // Force refresh of parent entity.
      $instanceController->applyChanges($this->rrule);
      // Output message about operation performed.
      $this->messenger()->addMessage($this->t('The instance has been removed.'));
    }
    $form_state
      ->setRedirectUrl($this
        ->getCancelUrl());
  }

}
