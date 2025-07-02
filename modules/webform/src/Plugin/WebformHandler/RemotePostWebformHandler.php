<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformElement\BooleanBase;
use Drupal\webform\Plugin\WebformElement\NumericBase;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Plugin\WebformElementInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformMessageManagerInterface;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission remote post handler.
 *
 * @WebformHandler(
 *   id = "remote_post",
 *   label = @Translation("Remote post"),
 *   category = @Translation("External"),
 *   description = @Translation("Posts webform submissions to a URL."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class RemotePostWebformHandler extends WebformHandlerBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * The webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The DrupalKernel instance used in the test.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * List of unsupported webform submission properties.
   *
   * The below properties will not being included in a remote post.
   *
   * @var array
   */
  protected $unsupportedProperties = [
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->httpClient = $container->get('http_client');
    $instance->tokenManager = $container->get('webform.token_manager');
    $instance->messageManager = $container->get('webform.message_manager');
    $instance->elementManager = $container->get('plugin.manager.webform.element');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->requestStack = $container->get('request_stack');
    $instance->kernel = $container->get('kernel');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $settings = $this->getSettings();

    if (!$this->isResultsEnabled()) {
      $settings['updated_url'] = '';
      $settings['deleted_url'] = '';
    }
    if (!$this->isDraftEnabled()) {
      $settings['draft_created_url'] = '';
      $settings['draft_updated_url'] = '';
    }
    if (!$this->isConvertEnabled()) {
      $settings['converted_url'] = '';
    }

    return [
      '#settings' => $settings,
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // We can't inject the entity field manager dependency because
    // RemotePostWebformHandler::defaultConfiguration() is called with in
    // RemotePostWebformHandler::create().
    // @see \Drupal\webform\Plugin\WebformHandlerBase::create
    // @see https://www.drupal.org/project/webform/issues/3285846
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $field_names = array_keys($entity_field_manager->getBaseFieldDefinitions('webform_submission'));
    $excluded_data = array_combine($field_names, $field_names);
    return [
      'method' => 'POST',
      'type' => 'x-www-form-urlencoded',
      'excluded_data' => $excluded_data,
      'custom_data' => '',
      'custom_options' => '',
      'file_data' => TRUE,
      'cast' => FALSE,
      'debug' => FALSE,
      // States.
      'completed_url' => '',
      'completed_custom_data' => '',
      'updated_url' => '',
      'updated_custom_data' => '',
      'deleted_url' => '',
      'deleted_custom_data' => '',
      'draft_created_url' => '',
      'draft_created_custom_data' => '',
      'draft_updated_url' => '',
      'draft_updated_custom_data' => '',
      'converted_url' => '',
      'converted_custom_data' => '',
      // Custom response messages.
      'message' => '',
      'messages' => [],
      // Custom response redirect URL.
      'error_url' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $webform = $this->getWebform();

    // States.
    $states = [
      WebformSubmissionInterface::STATE_COMPLETED => [
        'state' => $this->t('completed'),
        'label' => $this->t('Completed'),
        'description' => $this->t('Post data when <b>submission is completed</b>.'),
        'access' => TRUE,
      ],
      WebformSubmissionInterface::STATE_UPDATED => [
        'state' => $this->t('updated'),
        'label' => $this->t('Updated'),
        'description' => $this->t('Post data when <b>submission is updated</b>.'),
        'access' => $this->isResultsEnabled(),
      ],
      WebformSubmissionInterface::STATE_DELETED => [
        'state' => $this->t('deleted'),
        'label' => $this->t('Deleted'),
        'description' => $this->t('Post data when <b>submission is deleted</b>.'),
        'access' => $this->isResultsEnabled(),
      ],
      WebformSubmissionInterface::STATE_DRAFT_CREATED => [
        'state' => $this->t('draft created'),
        'label' => $this->t('Draft created'),
        'description' => $this->t('Post data when <b>draft is created.</b>'),
        'access' => $this->isDraftEnabled(),
      ],
      WebformSubmissionInterface::STATE_DRAFT_UPDATED => [
        'state' => $this->t('draft updated'),
        'label' => $this->t('Draft updated'),
        'description' => $this->t('Post data when <b>draft is updated.</b>'),
        'access' => $this->isDraftEnabled(),
      ],
      WebformSubmissionInterface::STATE_CONVERTED => [
        'state' => $this->t('converted'),
        'label' => $this->t('Converted'),
        'description' => $this->t('Post data when anonymous <b>submission is converted</b> to authenticated.'),
        'access' => $this->isConvertEnabled(),
      ],
    ];
    foreach ($states as $state => $state_item) {
      $state_url = $state . '_url';
      $state_custom_data = $state . '_custom_data';
      $t_args = [
        '@state' => $state_item['state'],
        '@title' => $state_item['label'],
        '@url' => 'https://www.mycrm.com/form_' . $state . '_handler.php',
      ];
      $form[$state] = [
        '#type' => 'details',
        '#open' => ($state === WebformSubmissionInterface::STATE_COMPLETED),
        '#title' => $state_item['label'],
        '#description' => $state_item['description'],
        '#access' => $state_item['access'],
      ];
      $form[$state][$state_url] = [
        '#type' => 'url',
        '#title' => $this->t('@title URL', $t_args),
        '#description' => $this->t('The full URL to POST to when an existing webform submission is @state. (e.g. @url)', $t_args),
        '#required' => ($state === WebformSubmissionInterface::STATE_COMPLETED),
        '#maxlength' => NULL,
        '#default_value' => $this->configuration[$state_url],
      ];
      $form[$state][$state_custom_data] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('@title custom data', $t_args),
        '#description' => $this->t('Enter custom data that will be included when a webform submission is @state.', $t_args),
        '#states' => ['visible' => [':input[name="settings[' . $state_url . ']"]' => ['filled' => TRUE]]],
        '#default_value' => $this->configuration[$state_custom_data],
      ];
      if ($state === WebformSubmissionInterface::STATE_COMPLETED) {
        $form[$state]['token'] = [
          '#type' => 'webform_message',
          '#message_message' => $this->t('Response data can be passed to the submission data using [webform:handler:{machine_name}:{state}:{key}] tokens. (i.e. [webform:handler:remote_post:completed:confirmation_number])'),
          '#message_type' => 'info',
        ];
      }
    }

    // Additional.
    $form['additional'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additional settings'),
    ];
    $form['additional']['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#description' => $this->t('The <b>POST</b> request method requests that a web server accept the data enclosed in the body of the request message. It is often used when uploading a file or when submitting a completed webform. In contrast, the HTTP <b>GET</b> request method retrieves information from the server.'),
      '#required' => TRUE,
      // phpcs:disable DrupalPractice.General.OptionsT.TforValue
      '#options' => [
        'POST' => 'POST',
        'PUT' => 'PUT',
        'PATCH' => 'PATCH',
        'GET' => 'GET',
      ],
      // phpcs:enable DrupalPractice.General.OptionsT.TforValue
      '#default_value' => $this->configuration['method'],
    ];
    $form['additional']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Post type'),
      '#description' => $this->t('Use x-www-form-urlencoded if unsure, as it is the default format for HTML webforms. You also have the option to post data in <a href="http://www.json.org/">JSON</a> format.'),
      '#options' => [
        'x-www-form-urlencoded' => $this->t('x-www-form-urlencoded'),
        'json' => $this->t('JSON'),
      ],
      '#states' => [
        '!visible' => [':input[name="settings[method]"]' => ['value' => 'GET']],
        '!required' => [':input[name="settings[method]"]' => ['value' => 'GET']],
      ],
      '#default_value' => $this->configuration['type'],
    ];
    $form['additional']['file_data'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include files as Base64 encoded post data'),
      '#description' => $this->t('If checked, uploaded and attached file data will be included using Base64 encoding.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['file_data'],
      '#access' => $this->getWebform()->hasAttachments(),
    ];
    $form['additional']['cast'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cast posted element value and custom data'),
      '#description' => $this->t('If checked, posted element values will be cast to integers, floats, and booleans as needed. Custom data can be cast by placing the desired type in parentheses before the value or token. (i.e. "(int) [webform_submission:value:total]" or "(int) 100")') .
        '<br/>' .
        '<br/>' .
        $this->t('For custom data, the casts allowed are:') .
        '<ul>' .
        '<li>' . $this->t('@cast - cast to @type', ['@cast' => '(int), (integer)', '@type' => 'integer']) . '</li>' .
        '<li>' . $this->t('@cast - cast to @type', ['@cast' => '(float), (double), (real)', '@type' => 'float']) . '</li>' .
        '<li>' . $this->t('@cast - cast to @type', ['@cast' => '(bool), (boolean)', '@type' => 'boolean']) . '</li>' .
        '</ul>',
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['cast'],
    ];
    $form['additional']['custom_data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom data'),
      '#description' => $this->t('Enter custom data that will be included in all remote post requests.'),
      '#default_value' => $this->configuration['custom_data'],
    ];
    $form['additional']['custom_options'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom options'),
      '#description' => $this->t('Enter custom <a href=":href">request options</a> that will be used by the Guzzle HTTP client. Request options can include custom headers.', [':href' => 'https://docs.guzzlephp.org/en/stable/request-options.html']),
      '#default_value' => $this->configuration['custom_options'],
    ];
    $form['additional']['message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Custom error response message'),
      '#description' => $this->t('This message is displayed when the response status code is not 2xx.') . '<br/><br/>' . $this->t('Defaults to: %value', ['%value' => $this->messageManager->render(WebformMessageManagerInterface::SUBMISSION_EXCEPTION_MESSAGE)]),
      '#default_value' => $this->configuration['message'],
    ];
    $form['additional']['messages_token'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('Response data can be passed to response message using [webform:handler:{machine_name}:{key}] tokens. (i.e. [webform:handler:remote_post:message])'),
      '#message_type' => 'info',
    ];
    $form['additional']['messages'] = [
      '#type' => 'webform_multiple',
      '#title' => $this->t('Custom response messages'),
      '#description' => $this->t('Enter custom response messages for specific status codes.'),
      '#empty_items' => 0,
      '#no_items_message' => $this->t('No error response messages entered. Please add messages below.'),
      '#add' => FALSE,
      '#element' => [
        'code' => [
          '#type' => 'webform_select_other',
          '#title' => $this->t('Response status code'),
          '#options' => [
            '200' => $this->t('200 OK'),
            '201' => $this->t('201 Created'),
            '204' => $this->t('204 No Content'),
            '400' => $this->t('400 Bad Request'),
            '401' => $this->t('401 Unauthorized'),
            '403' => $this->t('403 Forbidden'),
            '404' => $this->t('404 Not Found'),
            '444' => $this->t('444 No Response'),
            '500' => $this->t('500 Internal Server Error'),
            '502' => $this->t('502 Bad Gateway'),
            '503' => $this->t('503 Service Unavailable'),
            '504' => $this->t('504 Gateway Timeout'),
          ],
          '#other__type' => 'number',
          '#other__description' => $this->t('<a href="https://en.wikipedia.org/wiki/List_of_HTTP_status_codes">List of HTTP status codes</a>.'),
        ],
        'message' => [
          '#type' => 'webform_html_editor',
          '#title' => $this->t('Response message'),
        ],
      ],
      '#default_value' => $this->configuration['messages'],
    ];
    $form['additional']['error_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom error response redirect URL'),
      '#description' => $this->t('The URL or path to redirect to when a remote fails.'),
      '#default_value' => $this->configuration['error_url'],
      '#pattern' => '(https?:\/\/|\/).+',
    ];

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, posted submissions will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    // Submission data.
    $form['submission_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission data'),
    ];
    // Display warning about file uploads.
    if ($this->getWebform()->hasManagedFile()) {
      $form['submission_data']['managed_file_message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('Upload files will include the file\'s id, name, uri, and data (<a href=":href">Base64</a> encode).', [':href' => 'https://en.wikipedia.org/wiki/Base64']),
        '#message_type' => 'warning',
        '#message_close' => TRUE,
        '#message_id' => 'webform_node.references',
        '#message_storage' => WebformMessage::STORAGE_SESSION,
        '#states' => [
          'visible' => [
            ':input[name="settings[file_data]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['submission_data']['managed_file_message_no_data'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t("Upload files will include the file's id, name and uri."),
        '#message_type' => 'warning',
        '#message_close' => TRUE,
        '#message_id' => 'webform_node.references',
        '#message_storage' => WebformMessage::STORAGE_SESSION,
        '#states' => [
          'visible' => [
            ':input[name="settings[file_data]"]' => ['checked' => FALSE],
          ],
        ],
      ];
    }
    $form['submission_data']['excluded_data'] = [
      '#type' => 'webform_excluded_columns',
      '#title' => $this->t('Posted data'),
      '#title_display' => 'invisible',
      '#webform_id' => $webform->id(),
      '#required' => TRUE,
      '#default_value' => $this->configuration['excluded_data'],
    ];

    $this->elementTokenValidate($form);

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);
    if ($this->configuration['method'] === 'GET') {
      $this->configuration['type'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    $this->remotePost($state, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->remotePost(WebformSubmissionInterface::STATE_DELETED, $webform_submission);
  }

  /**
   * Execute a remote post.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   */
  protected function remotePost($state, WebformSubmissionInterface $webform_submission) {
    $state_url = $state . '_url';
    if (empty($this->configuration[$state_url])) {
      return;
    }

    $this->messageManager->setWebformSubmission($webform_submission);

    $request_url = $this->configuration[$state_url];
    $request_url = $this->replaceTokens($request_url, $webform_submission);
    $request_method = (!empty($this->configuration['method'])) ? $this->configuration['method'] : 'POST';
    $request_type = ($request_method !== 'GET') ? $this->configuration['type'] : NULL;

    // Get request options with tokens replaced.
    $request_options = (!empty($this->configuration['custom_options'])) ? Yaml::decode($this->configuration['custom_options']) : [];
    $request_options = $this->replaceTokens($request_options, $webform_submission);

    try {
      if ($request_method === 'GET') {
        // Append data as query string to the request URL.
        $query = $this->getRequestData($state, $webform_submission);
        $request_url = Url::fromUri($request_url, ['query' => $query])->toString();
        $response = $this->httpClient->get($request_url, $request_options);
      }
      else {
        $method = strtolower($request_method);
        $request_options[($request_type === 'json' ? 'json' : 'form_params')] = $this->getRequestData($state, $webform_submission);
        $response = $this->httpClient->$method($request_url, $request_options);
      }
    }
    catch (RequestException $request_exception) {
      $response = $request_exception->getResponse();

      // Encode HTML entities to prevent broken markup from breaking the page.
      $message = $request_exception->getMessage();
      $message = nl2br(htmlentities($message));

      $this->handleError($state, $message, $request_url, $request_method, $request_type, $request_options, $response);
      return;
    }

    // Display submission exception if response code is not 2xx.
    if ($this->responseHasError($response)) {
      $t_args = ['@status_code' => $this->getStatusCode($response)];
      $message = $this->t('Remote post request return @status_code status code.', $t_args);
      $this->handleError($state, $message, $request_url, $request_method, $request_type, $request_options, $response);
      return;
    }
    else {
      $this->displayCustomResponseMessage($response, FALSE);
    }

    // If debugging is enabled, display the request and response.
    $this->debug($this->t('Remote post successful!'), $state, $request_url, $request_method, $request_type, $request_options, $response, 'warning');

    // Replace [webform:handler] tokens in submission data.
    // Data structured for [webform:handler:remote_post:completed:key] tokens.
    $submission_data = $webform_submission->getData();
    $submission_has_token = (strpos(print_r($submission_data, TRUE), '[webform:handler:' . $this->getHandlerId() . ':') !== FALSE) ? TRUE : FALSE;
    if ($submission_has_token) {
      $response_data = $this->getResponseData($response);
      $token_data = ['webform_handler' => [$this->getHandlerId() => [$state => $response_data]]];
      $submission_data = $this->replaceTokens($submission_data, $webform_submission, $token_data);
      $webform_submission->setData($submission_data);
      // Resave changes to the submission data without invoking any hooks
      // or handlers.
      if ($this->isResultsEnabled()) {
        $webform_submission->resave();
      }
    }
  }

  /**
   * Get a webform submission's request data.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   *
   * @return array
   *   A webform submission converted to an associative array.
   */
  protected function getRequestData($state, WebformSubmissionInterface $webform_submission) {
    // Get submission and elements data.
    $data = $webform_submission->toArray(TRUE);

    // Remove unsupported properties from data.
    // These are typically added by other module's like metatag.
    $unsupported_properties = array_combine($this->unsupportedProperties, $this->unsupportedProperties);
    $data = array_diff_key($data, $unsupported_properties);

    // Flatten data and prioritize the element data over the
    // webform submission data.
    $element_data = $data['data'];
    unset($data['data']);
    $data = $element_data + $data;

    // Excluded selected submission data.
    $data = array_diff_key($data, $this->configuration['excluded_data']);

    // Append uploaded file name, uri, and base64 data to data.
    $webform = $this->getWebform();
    foreach ($data as $element_key => $element_value) {
      // Ignore empty and not equal to zero values.
      // @see https://stackoverflow.com/questions/732979/php-whats-an-alternative-to-empty-where-string-0-is-not-treated-as-empty
      if (empty($element_value) && $element_value !== 0 && $element_value !== '0') {
        continue;
      }

      $element = $webform->getElement($element_key);
      if (!$element) {
        continue;
      }

      // Cast markup to string. This only applies to computed Twig values.
      // @see \Drupal\webform\Element\WebformComputedTwig::computeValue
      if ($element_value instanceof MarkupInterface) {
        $data[$element_key] = $element_value = (string) $element_value;
      }

      $element_plugin = $this->elementManager->getElementInstance($element);

      if ($element_plugin instanceof WebformManagedFileBase) {
        if ($element_plugin->hasMultipleValues($element)) {
          foreach ($element_value as $fid) {
            $data['_' . $element_key][] = $this->getRequestFileData($fid);
          }
        }
        else {
          $data['_' . $element_key] = $this->getRequestFileData($element_value);
        }
      }
      elseif (!empty($this->configuration['cast'])) {
        // Cast value.
        $data[$element_key] = $this->castRequestValues($element, $element_plugin, $element_value);
      }
    }

    // Replace tokens.
    $data = $this->replaceTokens($data, $webform_submission);

    // Append custom data.
    if (!empty($this->configuration['custom_data'])) {
      $custom_data = Yaml::decode($this->configuration['custom_data']);
      // Replace tokens.
      $custom_data = $this->replaceTokens($custom_data, $webform_submission);
      // Cast custom data.
      $custom_data = $this->castCustomData($custom_data);
      $data = $custom_data + $data;
    }

    // Append state custom data.
    if (!empty($this->configuration[$state . '_custom_data'])) {
      $state_custom_data = Yaml::decode($this->configuration[$state . '_custom_data']);
      // Replace tokens.
      $state_custom_data = $this->replaceTokens($state_custom_data, $webform_submission);
      // Cast custom data.
      $state_custom_data = $this->castCustomData($state_custom_data);
      $data = $state_custom_data + $data;
    }

    return $data;
  }

  /**
   * Cast request values.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\Plugin\WebformElementInterface $element_plugin
   *   The element's webform plugin.
   * @param mixed $value
   *   The element's value.
   *
   * @return mixed
   *   The element's values cast to boolean or float when appropriate.
   */
  protected function castRequestValues(array $element, WebformElementInterface $element_plugin, $value) {
    $element_plugin->initialize($element);
    if ($element_plugin->hasMultipleValues($element)) {
      foreach ($value as $index => $item) {
        $value[$index] = $this->castRequestValue($element, $element_plugin, $item);
      }
      return $value;
    }
    else {
      return $this->castRequestValue($element, $element_plugin, $value);
    }
  }

  /**
   * Cast request value.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\Plugin\WebformElementInterface $element_plugin
   *   The element's webform plugin.
   * @param mixed $value
   *   The element's value.
   *
   * @return mixed
   *   The element's value cast to boolean or float when appropriate.
   */
  protected function castRequestValue(array $element, WebformElementInterface $element_plugin, $value) {
    if ($element_plugin instanceof BooleanBase) {
      return (boolean) $value;
    }
    elseif ($element_plugin instanceof NumericBase) {
      return (float) $value;
    }
    elseif ($element_plugin instanceof WebformCompositeBase) {
      $composite_elements = (isset($element['#element']))
        ? $element['#element']
        : $element_plugin->getCompositeElements();
      foreach ($composite_elements as $key => $composite_element) {
        if (isset($value[$key])) {
          $composite_element_plugin = $this->elementManager->getElementInstance($composite_element);
          $value[$key] = $this->castRequestValue($composite_element, $composite_element_plugin, $value[$key]);
        }
      }
      return $value;
    }
    else {
      return $value;
    }
  }

  /**
   * Cast custom data.
   *
   * @param array $data
   *   Custom data.
   *
   * @return array
   *   The custom data with value casted
   */
  protected function castCustomData(array $data) {
    if (empty($this->configuration['cast'])) {
      return $data;
    }

    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $data[$key] = $this->castCustomData($value);
      }
      elseif (is_string($value) && preg_match('/^\((int|integer|bool|boolean|float|double|real)\)\s*(.+)$/', $value, $match)) {
        $type_cast = $match[1];
        $type_value = $match[2];
        switch ($type_cast) {
          case 'int':
          case 'integer':
            $data[$key] = (int) $type_value;
            break;

          case 'bool':
          case 'boolean';
            $data[$key] = (bool) $type_value;
            break;

          case 'float':
          case 'double':
          case 'real':
            $data[$key] = (float) $type_value;
            break;
        }
      }
    }
    return $data;
  }

  /**
   * Get request file data.
   *
   * @param int $fid
   *   A file id.
   * @param string|null $prefix
   *   A prefix to prepended to data.
   *
   * @return array
   *   An associative array containing file data (name, uri, mime, and data).
   */
  protected function getRequestFileData($fid, $prefix = '') {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fid);
    if (!$file) {
      return [];
    }

    $data = [];
    $data[$prefix . 'id'] = (int) $file->id();
    $data[$prefix . 'name'] = $file->getFilename();
    $data[$prefix . 'uri'] = $file->getFileUri();
    $data[$prefix . 'mime'] = $file->getMimeType();
    $data[$prefix . 'uuid'] = $file->uuid();
    if ($this->configuration['file_data']) {
      $data[$prefix . 'data'] = base64_encode(file_get_contents($file->getFileUri()));
    }
    return $data;
  }

  /**
   * Get response data.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response returned by the remote server.
   *
   * @return array|string
   *   An array of data, parse from JSON, or a string.
   */
  protected function getResponseData(ResponseInterface $response) {
    $body = (string) $response->getBody();
    $data = json_decode($body, TRUE);
    return (json_last_error() === JSON_ERROR_NONE) ? $data : $body;
  }

  /**
   * Get webform handler tokens from response data.
   *
   * @param mixed $data
   *   Response data.
   * @param array $parents
   *   Webform handler token parents.
   *
   * @return array
   *   A list of webform handler tokens.
   */
  protected function getResponseTokens($data, array $parents = []) {
    $tokens = [];
    if (is_array($data)) {
      foreach ($data as $key => $value) {
        $tokens = array_merge($tokens, $this->getResponseTokens($value, array_merge($parents, [$key])));
      }
    }
    else {
      $tokens[] = '[' . implode(':', $parents) . ']';
    }
    return $tokens;
  }

  /**
   * Determine if saving of results is enabled.
   *
   * @return bool
   *   TRUE if saving of results is enabled.
   */
  protected function isResultsEnabled() {
    return ($this->getWebform()->getSetting('results_disabled') === FALSE);
  }

  /**
   * Determine if saving of draft is enabled.
   *
   * @return bool
   *   TRUE if saving of draft is enabled.
   */
  protected function isDraftEnabled() {
    return $this->isResultsEnabled() && ($this->getWebform()->getSetting('draft') !== WebformInterface::DRAFT_NONE);
  }

  /**
   * Determine if converting anonymous submissions to authenticated is enabled.
   *
   * @return bool
   *   TRUE if converting anonymous submissions to authenticated is enabled.
   */
  protected function isConvertEnabled() {
    return $this->isDraftEnabled() && ($this->getWebform()->getSetting('form_convert_anonymous') === TRUE);
  }

  /* ************************************************************************ */
  // Debug and exception handlers.
  /* ************************************************************************ */

  /**
   * Display debugging information.
   *
   * @param string $message
   *   Message to be displayed.
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param string $request_url
   *   The remote URL the request is being posted to.
   * @param string $request_method
   *   The method of remote post.
   * @param string $request_type
   *   The type of remote post.
   * @param string $request_options
   *   The requests options including the submission data.
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   * @param string $type
   *   The type of message to be displayed to the end use.
   */
  protected function debug($message, $state, $request_url, $request_method, $request_type, $request_options, ResponseInterface $response = NULL, $type = 'warning') {
    if (empty($this->configuration['debug'])) {
      return;
    }

    $build = [
      '#type' => 'details',
      '#title' => $this->t('Debug: Remote post: @title [@state]', ['@title' => $this->label(), '@state' => $state]),
    ];

    // State.
    $build['state'] = [
      '#type' => 'item',
      '#title' => $this->t('Submission state/operation:'),
      '#markup' => $state,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];

    // Request.
    $build['request'] = ['#markup' => '<hr />'];
    $build['request_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Request URL'),
      '#markup' => $request_url,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];
    $build['request_method'] = [
      '#type' => 'item',
      '#title' => $this->t('Request method'),
      '#markup' => $request_method,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];
    $build['request_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Request type'),
      '#markup' => $request_type,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];
    $build['request_options'] = [
      '#type' => 'item',
      '#title' => $this->t('Request options'),
      '#wrapper_attributes' => ['style' => 'margin: 0'],
      'data' => [
        '#markup' => Html::escape(Yaml::encode($request_options)),
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ],
    ];

    // Response.
    $build['response'] = ['#markup' => '<hr />'];
    if ($response) {
      $build['response_code'] = [
        '#type' => 'item',
        '#title' => $this->t('Response status code:'),
        '#markup' => $response->getStatusCode(),
        '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
      ];
      $build['response_header'] = [
        '#type' => 'item',
        '#title' => $this->t('Response header:'),
        '#wrapper_attributes' => ['style' => 'margin: 0'],
        'data' => [
          '#markup' => Html::escape(Yaml::encode($response->getHeaders())),
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ],
      ];
      $build['response_body'] = [
        '#type' => 'item',
        '#wrapper_attributes' => ['style' => 'margin: 0'],
        '#title' => $this->t('Response body:'),
        'data' => [
          '#markup' => Html::escape($response->getBody()),
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ],
      ];
      $response_data = $this->getResponseData($response);
      if ($response_data) {
        $build['response_data'] = [
          '#type' => 'item',
          '#wrapper_attributes' => ['style' => 'margin: 0'],
          '#title' => $this->t('Response data:'),
          'data' => [
            '#markup' => Html::escape(Yaml::encode($response_data)),
            '#prefix' => '<pre>',
            '#suffix' => '</pre>',
          ],
        ];
      }
      if ($tokens = $this->getResponseTokens($response_data, ['webform', 'handler', $this->getHandlerId(), $state])) {
        asort($tokens);
        $build['response_tokens'] = [
          '#type' => 'item',
          '#wrapper_attributes' => ['style' => 'margin: 0'],
          '#title' => $this->t('Response tokens:'),
          'description' => ['#markup' => $this->t('Below tokens can ONLY be used to insert response data into value and hidden elements.')],
          'data' => [
            '#plain_text' => implode(PHP_EOL, $tokens),
            '#prefix' => '<pre>',
            '#suffix' => '</pre>',
          ],
        ];
      }
    }
    else {
      $build['response_code'] = [
        '#markup' => $this->t('No response. Please see the recent log messages.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }

    // Message.
    $build['message'] = ['#markup' => '<hr />'];
    $build['message_message'] = [
      '#type' => 'item',
      '#wrapper_attributes' => ['style' => 'margin: 0'],
      '#title' => $this->t('Message:'),
      '#markup' => $message,
    ];

    $this->messenger()->addMessage($this->renderer->renderPlain($build), $type);
  }

  /**
   * Handle error by logging and display debugging and/or exception message.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param string $message
   *   Message to be displayed.
   * @param string $request_url
   *   The remote URL the request is being posted to.
   * @param string $request_method
   *   The method of remote post.
   * @param string $request_type
   *   The type of remote post.
   * @param string $request_options
   *   The requests options including the submission data.
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   */
  protected function handleError($state, $message, $request_url, $request_method, $request_type, $request_options, $response) {
    global $base_url, $base_path;

    // If debugging is enabled, display the error message on screen.
    $this->debug($message, $state, $request_url, $request_method, $request_type, $request_options, $response, 'error');

    // Log error message.
    $context = [
      '@form' => $this->getWebform()->label(),
      '@state' => $state,
      '@type' => $request_type,
      '@url' => $request_url,
      '@message' => $message,
      'webform_submission' => $this->getWebformSubmission(),
      'handler_id' => $this->getHandlerId(),
      'operation' => 'error',
      'link' => $this->getWebform()
        ->toLink($this->t('Edit'), 'handlers')
        ->toString(),
    ];
    $this->getLogger('webform_submission')
      ->error('@form webform remote @type post (@state) to @url failed. @message', $context);

    // Display custom or default exception message.
    if (!$this->displayCustomResponseMessage($response, TRUE)) {
      $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_EXCEPTION_MESSAGE, 'error');
    }

    // Redirect the current request to the error url.
    $error_url = $this->replaceTokens($this->configuration['error_url'], $this->getWebformSubmission());
    if ($error_url && PHP_SAPI !== 'cli') {
      // Convert error path to URL.
      if (strpos($error_url, '/') === 0) {
        $error_url = $base_url . preg_replace('#^' . $base_path . '#', '/', $error_url);
      }

      $request = $this->requestStack->getCurrentRequest();

      // Build Ajax redirect or trusted redirect response.
      $wrapper_format = $request->get(MainContentViewSubscriber::WRAPPER_FORMAT);
      $is_ajax_request = ($wrapper_format === 'drupal_ajax');
      if ($is_ajax_request) {
        $response = new AjaxResponse();
        $response->addCommand(new RedirectCommand($error_url));
        $response->setData($response->getCommands());
      }
      else {
        $response = new TrustedRedirectResponse($error_url);
      }
      // Save the session so things like messages get saved.
      $request->getSession()->save();
      $response->prepare($request);
      // Make sure to trigger kernel events.
      $this->kernel->terminate($request, $response);
      $response->send();
      // Only exit, an Ajax request to prevent headers from being overwritten.
      if ($is_ajax_request) {
        exit;
      }
    }
  }

  /**
   * Get custom response message.
   *
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   * @param bool $default
   *   Display the default message. Defaults to TRUE.
   *
   * @return string
   *   A custom response message.
   */
  protected function getCustomResponseMessage($response, $default = TRUE) {
    if (!empty($this->configuration['messages'])) {
      $status_code = $this->getStatusCode($response);
      foreach ($this->configuration['messages'] as $message_item) {
        if ((int) $message_item['code'] === (int) $status_code) {
          return $this->replaceTokens($message_item['message'], $this->getWebformSubmission());
        }
      }
    }
    return (!empty($this->configuration['message']) && $default)
      ? $this->replaceTokens($this->configuration['message'], $this->getWebformSubmission())
      : '';
  }

  /**
   * Display custom response message.
   *
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   * @param bool $default
   *   Display the default message. Defaults to TRUE.
   *
   * @return bool
   *   TRUE if custom response message is displayed.
   */
  protected function displayCustomResponseMessage($response, $default = TRUE) {
    $custom_response_message = $this->getCustomResponseMessage($response, $default);
    if (!$custom_response_message) {
      return FALSE;
    }

    $token_data = [];

    if ($response instanceof ResponseInterface) {
      $token_data = [
        'webform_handler' => [
          $this->getHandlerId() => $this->getResponseData($response),
        ],
      ];
    }

    $build_message = [
      '#markup' => $this->replaceTokens($custom_response_message, $this->getWebform(), $token_data),
    ];
    $message = \Drupal::service('renderer')->renderPlain($build_message);
    $type = ($this->responseHasError($response)) ? MessengerInterface::TYPE_ERROR : MessengerInterface::TYPE_STATUS;
    $this->messenger()->addMessage($message, $type);
    return TRUE;
  }

  /**
   * Determine if response has an error status code.
   *
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   *
   * @return bool
   *   TRUE if response status code reflects an unsuccessful value.
   */
  protected function responseHasError($response) {
    $status_code = $this->getStatusCode($response);
    return $status_code < 200 || $status_code >= 300;
  }

  /**
   * Gets the response status code.
   *
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   *
   * @return int
   *   The response status code. Defaults to 444 if there is no response.
   */
  protected function getStatusCode($response) {
    return ($response instanceof ResponseInterface)
      ? $response->getStatusCode()
      : 444;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildTokenTreeElement(array $token_types = ['webform', 'webform_submission'], $description = NULL) {
    $description = $description ?: $this->t('Use [webform_submission:values:ELEMENT_KEY:raw] to get plain text values.');
    return parent::buildTokenTreeElement($token_types, $description);
  }

}
