<?php

namespace Drupal\devel\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\devel\SwitchUserListHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define an accessible form to switch the user.
 */
class SwitchUserPageForm extends FormBase {

  /**
   * The FormBuilder object.
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * A helper for creating the user list form.
   */
  protected SwitchUserListHelper $switchUserListHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->switchUserListHelper = $container->get('devel.switch_user_list_helper');
    $instance->formBuilder = $container->get('form_builder');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'devel_switchuser_page_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if ($accounts = $this->switchUserListHelper->getUsers()) {
      $form['devel_links'] = $this->switchUserListHelper->buildUserList($accounts);
      $form['devel_form'] = $this->formBuilder->getForm(SwitchUserForm::class);
    }
    else {
      $this->messenger->addStatus('There are no user accounts present!');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Nothing to do here. This is delegated to devel.switch via http call.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Nothing to do here. This is delegated to devel.switch via http call.
  }

}
