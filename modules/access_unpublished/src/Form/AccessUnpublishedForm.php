<?php

namespace Drupal\access_unpublished\Form;

use Drupal\access_unpublished\AccessUnpublished;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alter the entity form to add access unpublished elements.
 */
class AccessUnpublishedForm implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Access unpublished config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * AccessUnpublishedForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->config = $configFactory->get('access_unpublished.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Alter the entity form to add access unpublished elements.
   */
  public function formAlter(&$form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof EntityForm) {
      return;
    }

    // Determines if the given form operation is add or edit. Prevents the
    // access unpublished form from being displayed on other random entity
    // forms, for example, the delete form.
    if (!in_array($form_object->getOperation(), ['edit', 'default'], TRUE)) {
      return;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_object->getEntity();

    if (AccessUnpublished::applicableEntityType($entity->getEntityType()) && !$entity->isPublished() && !$entity->isNew()) {

      /** @var \Drupal\Core\Entity\EntityListBuilder $list_builder */
      $list_builder = $this->entityTypeManager->getHandler('access_token', 'entity_form_list_builder');

      $form['access_unpublished_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Temporary unpublished access'),
        '#weight' => 35,
        '#attributes' => [
          'class' => ['access-unpublished-form'],
          'id' => 'edit-access-unpublished-settings',
        ],
        '#optional' => FALSE,
        '#group' => 'advanced',
      ];

      $form['access_unpublished_settings'] += $list_builder->render($entity);

      $form['access_unpublished_settings']['duration'] = [
        '#type' => 'select',
        '#title' => $this->t('Lifetime'),
        '#options' => static::getDurationOptions(),
        '#default_value' => $this->config->get('duration'),
        '#attributes' => [
          'id' => 'edit-duration',
        ],
      ];

      $form['access_unpublished_settings']['generate_token'] = [
        '#type' => 'button',
        '#value' => $this->t('Generate token'),
        '#ajax' => [
          'callback' => [__CLASS__, 'generateToken'],
        ],
        '#attributes' => [
          'id' => 'edit-generate-token',
        ],
      ];
    }
  }

  /**
   * Submit callback to generate a token.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response with the new form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function generateToken(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\access_unpublished\Entity\AccessToken $access_token */
    $access_token = \Drupal::entityTypeManager()->getStorage('access_token')->create(
      [
        'entity_type' => $entity->getEntityType()->id(),
        'entity_id' => $entity->id(),
        'expire' => $form_state->getValue('duration') > 0 ? \Drupal::time()->getRequestTime() + $form_state->getValue('duration') : -1,
      ]
    );
    $access_token->save();

    /** @var \Drupal\Core\Entity\EntityListBuilder $list_builder */
    $list_builder = \Drupal::service('entity_type.manager')->getHandler('access_token', 'entity_form_list_builder');

    $form = $list_builder->render($access_token->getHost());

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('[data-drupal-selector="access-token-list"]', $form['table']));
    return $response;
  }

  /**
   * Get options for the duration form.
   *
   * @return array
   *   The form values.
   */
  public static function getDurationOptions() {
    $dateFormatter = \Drupal::service('date.formatter');
    $period = [86400, 172800, 345600, 604800, 1209600];
    $period = array_map([$dateFormatter, 'formatInterval'], array_combine($period, $period));
    $period[-1] = t('Unlimited');
    return $period;
  }

}
