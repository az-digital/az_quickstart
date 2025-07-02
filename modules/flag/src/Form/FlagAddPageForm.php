<?php

namespace Drupal\flag\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\flag\FlagType\FlagTypePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the flag add page.
 *
 * Flags are created in a two step process. This form provides a simple form
 * that allows the administrator to select key values that are necessary to
 * initialize the flag entity. Most importantly, this includes the FlagType.
 *
 * @see \Drupal\flag\FlagType\FlagTypeBase
 */
class FlagAddPageForm extends FormBase {

  use RedirectDestinationTrait;

  /**
   * The flag type plugin manager.
   *
   * @var \Drupal\flag\FlagType\FlagTypePluginManager
   */
  protected $flagTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new form.
   *
   * @param \Drupal\flag\FlagType\FlagTypePluginManager $flag_type_manager
   *   The link type plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(FlagTypePluginManager $flag_type_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->flagTypeManager = $flag_type_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.flag.flagtype'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flag_add_page';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['flag_entity_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Flag Type'),
      '#required' => TRUE,
      '#description' => $this->t('Type of item to reference. This cannot be changed once the flag is created.'),
      '#default_value' => 'entity:node',
      '#options' => $this->flagTypeManager->getAllFlagTypes(),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.flag.add_form', [
      'entity_type' => $form_state->getValue('flag_entity_type'),
    ]);
  }

  /**
   * Determines if the flag already exists.
   *
   * @param string $id
   *   The flag ID.
   *
   * @return bool
   *   TRUE if the flag exists, FALSE otherwise.
   */
  public function exists($id) {
    // @todo Make this injected like ActionFormBase::exists().
    return $this->entityTypeManager->getStorage('flag')->load($id);
  }

}
