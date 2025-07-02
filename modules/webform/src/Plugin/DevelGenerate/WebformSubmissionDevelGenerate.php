<?php

namespace Drupal\webform\Plugin\DevelGenerate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Drupal\webform\Utility\WebformArrayHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a WebformSubmissionDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "webform_submission",
 *   label = @Translation("Webform submissions"),
 *   description = @Translation("Generate a given number of webform submissions. Optionally delete current submissions."),
 *   url = "webform",
 *   permission = "administer webform",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "entity-type" = NULL,
 *     "entity-id" = NULL,
 *   }
 * )
 */
class WebformSubmissionDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  use WebformEntityStorageTrait;

  /**
   * Track in webform submission are being generated.
   *
   * @var bool
   */
  protected static $generatingSubmissions = FALSE;

  /**
   * The current request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The database object.
   *
   * @var object
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The webform submission generation service.
   *
   * @var \Drupal\webform\WebformSubmissionGenerateInterface
   */
  protected $webformSubmissionGenerate;

  /**
   * The webform entity reference manager.
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->database = $container->get('database');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->messenger = $container->get('messenger');
    $instance->webformSubmissionGenerate = $container->get('webform_submission.generate');
    $instance->webformEntityReferenceManager = $container->get('webform.entity_reference_manager');
    $instance->time = $container->get('datetime.time');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('Please note that no emails will be sent while generating webform submissions.'),
      '#message_type' => 'warning',
    ];

    $options = [];
    foreach ($this->getWebformStorage()->loadMultiple() as $webform) {
      $options[$webform->id()] = $webform->label();
    }

    $webform_id = $this->request->get('webform_id');
    $source_entity_type = $this->request->get('entity_type');
    $source_entity_id = $this->request->get('entity_id');
    $source_entity = ($source_entity_type && $source_entity_id) ? $this->getEntityStorage($source_entity_type)->load($source_entity_id) : NULL;

    if ($webform_id && isset($options[$webform_id])) {
      $form['webform_ids'] = [
        '#type' => 'value',
        '#value' => [$webform_id => $webform_id],
      ];
      $form['webform'] = [
        '#type' => 'item',
        '#title' => $this->t('Webform', [], ['context' => 'form']),
        '#markup' => $options[$webform_id],
      ];
    }
    else {
      $form['webform_ids'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Webform', [], ['context' => 'form']),
        '#description' => $this->t('Restrict submissions to these webforms.'),
        '#required' => TRUE,
        '#options' => $options,
      ];
    }

    if ($source_entity) {
      $form['submitted'] = [
        '#type' => 'item',
        '#title' => $this->t('Submitted to'),
        '#markup' => $source_entity->toLink()->toString(),
      ];
      $form['entity-type'] = ['#type' => 'value', '#value' => $source_entity_type];
      $form['entity-id'] = ['#type' => 'value', '#value' => $source_entity_id];
    }
    elseif ($webform_id && isset($options[$webform_id])) {
      $form['entity-type'] = ['#type' => 'value', '#value' => ''];
      $form['entity-id'] = ['#type' => 'value', '#value' => ''];
    }
    else {
      $entity_types = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE);
      $form['submitted'] = [
        '#type' => 'item',
        '#title' => $this->t('Submitted to'),
      ];
      $form['submitted']['container'] = [
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>',
      ];
      $form['submitted']['container']['entity-type'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity type'),
        '#title_display' => 'invisible',
        '#empty_option' => $this->t('- None -'),
        '#options' => $entity_types,
        '#default_value' => $this->getSetting('entity-type'),
      ];
      $form['submitted']['container']['entity-id'] = [
        '#type' => 'number',
        '#title' => $this->t('Entity id'),
        '#title_display' => 'invisible',
        '#default_value' => $this->getSetting('entity-id'),
        '#min' => 1,
        '#size' => 10,
        '#states' => [
          'invisible' => [
            ':input[name="entity-type"]' => ['value' => ''],
          ],
        ],
      ];
    }

    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of submissions?'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $this->getSetting('num'),
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete existing submissions in specified webform before generating new submissions'),
      '#default_value' => $this->getSetting('kill'),
    ];

    $form['#validate'] = [[$this, 'validateForm']];
    return $form;
  }

  /**
   * Custom validation handler.
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $webform_ids = array_filter($form_state->getValue('webform_ids'));

    // Let default webform validation handle requiring webform ids.
    if (empty($webform_ids)) {
      return;
    }

    $entity_type = $form_state->getValue('entity-type');
    $entity_id = $form_state->getValue('entity-id');
    if ($entity_type) {
      if ($error = $this->validateEntity($webform_ids, $entity_type, $entity_id)) {
        $form_state->setErrorByName('entity_type', $error);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generateElements(array $values) {
    $this->generateSubmissions($values);
  }

  /**
   * Generates submissions for a list of given webforms.
   *
   * @param array $values
   *   The element values from the settings webform.
   */
  protected function generateSubmissions(array $values) {
    static::$generatingSubmissions = TRUE;
    if (!empty($values['kill'])) {
      $this->deleteWebformSubmissions($values['webform_ids'], $values['entity-type'], $values['entity-id']);
      $this->setMessage($this->t('Deleted existing submissions.'));
    }
    if (!empty($values['webform_ids'])) {
      $this->initializeGenerate($values);
      $start = $this->time->getRequestTime();
      for ($i = 1; $i <= $values['num']; $i++) {
        $this->generateSubmission($values);
        $feedback = isset($values['feedback']) && is_int($values['feedback']) ? $values['feedback'] : 1000;
        if ($i % $feedback === 0) {
          $now = $this->time->getCurrentTime();
          $dt_args = [
            '@feedback' => $feedback,
            '@rate' => ($feedback * 60) / ($now - $start),
          ];
          \Drupal::logger('webform')->notice('Completed @feedback submissions (@rate submissions/min)', $dt_args);
          $start = $now;
        }
      }
    }
    $this->setMessage($this->formatPlural($values['num'], '1 submissions created.', 'Finished creating @count submissions'));
    static::$generatingSubmissions = FALSE;
  }

  /**
   * Deletes all submissions of given webforms.
   *
   * @param array $webform_ids
   *   Array of webform ids.
   * @param string|null $entity_type
   *   A webform source entity type.
   * @param int|null $entity_id
   *   A webform source entity id.
   */
  protected function deleteWebformSubmissions(array $webform_ids, $entity_type = NULL, $entity_id = NULL) {
    $webforms = $this->getWebformStorage()->loadMultiple($webform_ids);
    $entity = ($entity_type && $entity_id) ? $this->getEntityStorage($entity_type)->load($entity_id) : NULL;
    foreach ($webforms as $webform) {
      $this->getSubmissionStorage()->deleteAll($webform, $entity);
    }
  }

  /**
   * Add 'users' that contains a list of uids.
   *
   * @param array $values
   *   The element values from the settings webform.
   */
  protected function initializeGenerate(array &$values) {
    // Set user id.$devel_generate_manager = \Drupal::service('plugin.manager.develgenerate')
    $users = $this->getUsers();
    $users = array_merge($users, ['0']);
    $values['users'] = $users;

    // Set created min and max.
    $values['created_min'] = strtotime('-1 month');
    $values['created_max'] = $this->time->getRequestTime();

    // Set entity type and id default value.
    $values += [
      'num' => 50,
      'entity-type' => '',
      'entity-id' => '',
    ];
  }

  /**
   * Create one node. Used by both batch and non-batch code branches.
   */
  protected function generateSubmission(&$results) {
    $webform_id = array_rand(array_filter($results['webform_ids']));
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getWebformStorage()->load($webform_id);

    $users = $results['users'];
    $uid = $users[array_rand($users)];
    $entity_type = $results['entity-type'];
    $entity_id = $results['entity-id'];

    // Get submission URL from source entity or webform.
    $url = $webform->toUrl();
    if ($entity_type && $entity_id) {
      $source_entity = $this->getEntityStorage($entity_type)->load($entity_id);
      if ($source_entity->hasLinkTemplate('canonical')) {
        $url = $source_entity->toUrl();
      }
    }

    $timestamp = rand($results['created_min'], $results['created_max']);
    $this->getSubmissionStorage()->create([
      'webform_id' => $webform_id,
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'uid' => $uid,
      'remote_addr' => mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255),
      'uri' => preg_replace('#^' . base_path() . '#', '/', $url->toString()),
      'data' => Yaml::encode($this->webformSubmissionGenerate->getData($webform)),
      'created' => $timestamp,
      'changed' => $timestamp,
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []) {
    $webform_id = array_shift($args);
    $webform_ids = [$webform_id => $webform_id];
    $values = [
      'webform_ids' => $webform_ids,
      'num' => array_shift($args) ?: 50,
      'kill' => empty($args['kill']) ? FALSE : TRUE,
    ];

    if (empty($webform_id)) {
      throw new \Exception(dt('Webform ida required'));
    }

    if (!$this->getWebformStorage()->load($webform_id)) {
      throw new \Exception(dt('Invalid webform name: @name', ['@name' => $webform_id]));
    }

    if ($this->isNumber($values['num']) === FALSE) {
      throw new \Exception(dt('Invalid number of submissions: @num', ['@num' => $values['num']]));
    }

    $entity_type = $args['entity-type'];
    $entity_id = $args['entity-id'];
    if ($entity_type || $entity_id) {
      if ($error = $this->validateEntity($webform_ids, $entity_type, $entity_id)) {
        throw new \Exception($error);
      }
      else {
        $values['entity-type'] = $entity_type;
        $values['entity-id'] = $entity_id;
      }
    }

    return $values;
  }

  /**
   * Retrieve 50 uids from the database.
   *
   * @return array
   *   An array of uids.
   */
  protected function getUsers() {
    $users = [];
    $result = $this->database->queryRange('SELECT uid FROM {users}', 0, 50);
    foreach ($result as $record) {
      $users[] = $record->uid;
    }
    return $users;
  }

  /**
   * Track if webform submissions are being generated.
   *
   * Used to block emails from being sent while using devel generate.
   *
   * @return bool
   *   TRUE if webform submissions are being generated.
   */
  public static function isGeneratingSubmissions() {
    return static::$generatingSubmissions;
  }

  /**
   * Validate webform source entity type and id.
   *
   * @param array $webform_ids
   *   An array webform ids.
   * @param string $entity_type
   *   An entity type.
   * @param int $entity_id
   *   An entity id.
   *
   * @return string
   *   An error message or NULL if there are no validation errors.
   */
  protected function validateEntity(array $webform_ids, $entity_type, $entity_id) {
    $t = function_exists('dt') ? 'dt' : 't';

    if (!$entity_type) {
      return $t('Entity type is required');
    }

    if (!$entity_id) {
      return $t('Entity id is required');
    }

    $dt_args = ['@entity_type' => $entity_type, '@entity_id' => $entity_id];

    $source_entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    if (!$source_entity) {
      return $t('Unable to load @entity_type:@entity_id', $dt_args);
    }

    $dt_args['@title'] = $source_entity->label();

    $webform_field_name = $this->webformEntityReferenceManager->getFieldName($source_entity);
    if (!$webform_field_name) {
      return $t("'@title' (@entity_type:@entity_id) does not have a 'webform' field.", $dt_args);
    }

    if (count($webform_ids) > 1) {
      return $t("'@title' (@entity_type:@entity_id) can only be associated with a single webform.", $dt_args);
    }

    $dt_args['@webform_ids'] = WebformArrayHelper::toString($webform_ids, $t('or'));
    if (!in_array($source_entity->webform->target_id, $webform_ids)) {
      return $t("'@title' (@entity_type:@entity_id) does not have a '@webform_ids' webform associated with it.", $dt_args);
    }

    return NULL;
  }

}
