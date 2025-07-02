<?php

namespace Drupal\ctools\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\TypedDataResolver;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolver Relatinoship Delete Form.
 */
abstract class ResolverRelationshipDelete extends ConfirmFormBase {

  /**
   * Tempstore Factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * The resolver service.
   *
   * @var \Drupal\ctools\TypedDataResolver
   */
  protected $resolver;

  /**
   * Tempstore ID.
   *
   * @var string
   */
  protected $tempstore_id;

  /**
   * Machine name of the relationship.
   *
   * @var string
   */
  protected $machine_name;

  /**
   * Resolver ID.
   *
   * @var string
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('tempstore.shared'), $container->get('ctools.typed_data.resolver'));
  }

  /**
   * Resolver Relationship Delete Form Constructor.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $tempstore
   *   The shared tempstore.
   * @param \Drupal\ctools\TypedDataResolver $resolver
   *   The typed data resolver.
   */
  public function __construct(SharedTempStoreFactory $tempstore, TypedDataResolver $resolver) {
    $this->tempstore = $tempstore;
    $this->resolver = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ctools_resolver_relationship_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion($id = NULL, $cached_values = []) {
    $context = $this->getContexts($cached_values)[$id];
    return $this->t('Are you sure you want to delete the @label relationship?', [
      '@label' => $context->getContextDefinition()->getLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getCancelUrl($cached_values = []);

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL, $tempstore_id = NULL, $machine_name = NULL) {
    $this->tempstore_id = $tempstore_id;
    $this->machine_name = $machine_name;
    $this->id = $id;

    $cached_values = $this->tempstore->get($this->tempstore_id)->get($this->machine_name);
    $form['#title'] = $this->getQuestion($id, $cached_values);

    $form['#attributes']['class'][] = 'confirmation';
    $form['description'] = ['#markup' => $this->getDescription()];
    $form[$this->getFormName()] = ['#type' => 'hidden', '#value' => 1];

    // By default, render the form using theme_confirm_form().
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }
    $form['actions'] = ['#type' => 'actions'];
    $form['actions'] += $this->actions($form, $form_state, $cached_values);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $this->tempstore->get($this->tempstore_id)->get($this->machine_name);
    $form_state->setRedirectUrl($this->getCancelUrl($cached_values));
  }

  /**
   * A custom form actions method.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param $cached_values
   *   The current wizard cached values.
   *
   * @return array
   *   Actions to call.
   */
  protected function actions(array $form, FormStateInterface $form_state, $cached_values) {
    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->getConfirmText(),
        '#validate' => [
          [$this, 'validate'],
        ],
        '#submit' => [
          [$this, 'submitForm'],
        ],
      ],
      'cancel' => ConfirmFormHelper::buildCancelLink($this, $this->getRequest()),
    ];
  }

  /**
   * Extract contexts from the cached values.
   *
   * @param array $cached_values
   *   The cached values.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   Contexts from the cached values.
   */
  abstract public function getContexts(array $cached_values);

}
