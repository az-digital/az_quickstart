<?php

namespace Drupal\google_tag\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Google tag manager module and default container settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected ContextRepositoryInterface $contextRepository;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected ConditionManager $conditionManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->contextRepository = $container->get('context.repository');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->conditionManager = $container->get('plugin.manager.condition');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_tag_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_tag.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['module'] = $this->moduleFieldset($form_state);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Fieldset builder for the module settings form.
   */
  public function moduleFieldset(FormStateInterface $form_state) {
    $config = $this->config('google_tag.settings');

    // Build form elements.
    $fieldset = [
      '#type' => 'fieldset',
      '#title' => $this->t('Module settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $google_tags = $this->entityTypeManager->getStorage('google_tag_container')->loadMultiple();

    $fieldset['use_collection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple Tag Containers'),
      '#description' => $this->t('For <strong>most</strong> users, only one tag container is required. Each tag container represents a set of visibility conditions and events, and represents one or more measurement IDs. You only need multiple tag containers if your config is different per set of measurement IDs.'),
      '#default_value' => $config->get('use_collection'),
      '#disabled' => !empty($google_tags) && count($google_tags) > 1 && $config->get('use_collection'),
    ];

    return $fieldset;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('google_tag.settings');

    // Set advanced collection option.
    $config->set('use_collection', $form_state->getValue('use_collection') ?? FALSE);

    $config->save();

    // Invalidate the local task menu so we get the correct new menu links.
    Cache::invalidateTags(['local_task']);

    parent::submitForm($form, $form_state);
  }

}
