<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Form\WebformDialogFormTrait;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Plugin\WebformElementInterface;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a abstract element type webform for a webform element.
 */
abstract class WebformUiElementTypeFormBase extends FormBase {

  use WebformDialogFormTrait;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * A temp webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * A temp webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->elementManager = $container->get('plugin.manager.webform.element');
    $instance->currentUser = $container->get('current_user');
    $instance->userData = $container->get('user.data');
    $instance->webform = Webform::create(['id' => '_webform_ui_temp_form']);
    $instance->webformSubmission = WebformSubmission::create(['webform' => $instance->webform]);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL) {
    $form['#prefix'] = '<div id="webform-ui-element-type-ajax-wrapper">';
    $form['#suffix'] = '</div>';

    $form['#attached']['library'][] = 'webform/webform.admin';
    $form['#attached']['library'][] = 'webform/webform.form';
    $form['#attached']['library'][] = 'webform/webform.tooltip';
    $form['#attached']['library'][] = 'webform_ui/webform_ui';

    if (!$this->isOffCanvasDialog()) {
      $form['preview'] = [
        '#type' => 'submit',
        '#validate' => ['::noValidate'],
        '#limit_validation_errors' => [],
        '#value' => ($this->isPreviewEnabled()) ? $this->t('Hide preview') : $this->t('Show preview'),
        '#attributes' => [
          'class' => ['button--small'],
          'style' => 'float: right;',
        ],
        '#ajax' => [
          'callback' => '::submitAjaxForm',
          'event' => 'click',
          'progress' => [
            'type' => 'fullscreen',
          ],
        ],
      ];
    }

    $form['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by element name'),
      '#attributes' => [
        'class' => ['webform-form-filter-text'],
        'data-element' => '.webform-ui-element-type-table',
        'data-item-singlular' => $this->t('element'),
        'data-item-plural' => $this->t('elements'),
        'data-no-results' => '.webform-element-no-results',
        'title' => $this->t('Enter a part of the element name to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];

    // No results.
    $form['no_results'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('No elements found. Try a different search.'),
      '#message_type' => 'info',
      '#attributes' => ['class' => ['webform-element-no-results']],
      '#weight' => 1000,
    ];

    return $form;
  }

  /**
   * Never trigger validation.
   */
  public function noValidate(array &$form, FormStateInterface $form_state) {
    $form_state->clearErrors();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $preview = $this->userData->get('webform_ui', $this->currentUser->id(), 'element_type_preview') ?: FALSE;
    $this->userData->set('webform_ui', $this->currentUser->id(), 'element_type_preview', !$preview);

    $form_state->clearErrors();
    $form_state->setRebuild();
  }

  /**
   * Submit form #ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that display validation error messages or redirects
   *   to a URL
   */
  public function submitAjaxForm(array &$form, FormStateInterface $form_state) {
    // Remove #id from wrapper so that the form is still wrapped in a <div>
    // and triggerable.
    // @see js/webform.element.details.toggle.js
    $form['#prefix'] = '<div>';

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#webform-ui-element-type-ajax-wrapper', $form));
    return $response;
  }

  /* ************************************************************************ */
  // Table methods.
  /* ************************************************************************ */

  /**
   * Get table header.
   *
   * @return array
   *   An array containing table header.
   */
  protected function getHeader() {
    $header = [];
    $header['type'] = [
      'data' => $this->t('Type'),
      'width' => '140',
    ];
    if ($this->isPreviewEnabled()) {
      $header['preview'] = [
        'data' => $this->t('Preview'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }
    $header['operation'] = [
      'width' => '140',
    ];
    return $header;
  }

  /**
   * Build element type row.
   *
   * @param \Drupal\webform\Plugin\WebformElementInterface $webform_element
   *   Webform element plugin.
   * @param \Drupal\Core\Url $url
   *   A URL.
   * @param string $label
   *   Operation label.
   *
   * @return array
   *   A renderable array containing the element type row.
   */
  protected function buildRow(WebformElementInterface $webform_element, Url $url, $label) {
    $row = [];

    // Type.
    $row['type']['link'] = [
      '#type' => 'link',
      '#title' => $webform_element->getPluginLabel(),
      '#url' => $url,
      '#attributes' => WebformDialogHelper::getOffCanvasDialogAttributes($webform_element->getOffCanvasWidth()),
      '#prefix' => '<span class="webform-form-filter-text-source">',
      '#suffix' => '</span>',
    ];
    if ($this->config('webform.settings')->get('ui.description_help')) {
      $row['type']['help'] = [
        '#type' => 'webform_help',
        '#help' => $webform_element->getPluginDescription(),
        '#help_title' => $webform_element->getPluginLabel(),
      ];
    }
    else {
      $row['type']['help'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['description']],
        'description' => ['#markup' => $webform_element->getPluginDescription()],
      ];

    }
    // Preview.
    if ($this->isPreviewEnabled()) {
      $row['preview'] = $this->buildElementPreview($webform_element);
    }

    // Operation.
    $row['operation'] = [
      '#type' => 'link',
      '#title' => $label,
      // Must clone the URL object to prevent the above 'label' link attributes
      // (i.e. webform-tooltip-link) from being copied to 'operation' link.
      '#url' => clone $url,
      '#attributes' => WebformDialogHelper::getOffCanvasDialogAttributes($webform_element->getOffCanvasWidth(), ['button', 'button--primary', 'button--small']),
    ];

    // Issue #2741877 Nested modals don't work: when using CKEditor in a
    // modal, then clicking the image button opens another modal,
    // which closes the original modal.
    // @todo Remove the below workaround once this issue is resolved.
    if ($webform_element->getTypeName() === 'processed_text' && !WebformDialogHelper::useOffCanvas()) {
      unset($row['type']['#attributes']);
      unset($row['operation']['#attributes']);
      if (isset($row['operation'])) {
        $row['operation']['#attributes']['class'] = ['button', 'button--primary', 'button--small'];
      }
      $row['type']['#attributes']['class'][] = 'js-webform-tooltip-link';
      $row['type']['#attributes']['class'][] = 'webform-tooltip-link';
      $row['type']['#attributes']['title'] = $webform_element->getPluginDescription();
    }

    return $row;
  }

  /* ************************************************************************ */
  // Preview methods.
  /* ************************************************************************ */

  /**
   * Determine if webform element type preview is enabled.
   *
   * @return bool
   *   TRUE if webform element type preview is enabled.
   */
  protected function isPreviewEnabled() {
    if ($this->isOffCanvasDialog()) {
      return FALSE;
    }

    return $this->userData->get('webform_ui', $this->currentUser->id(), 'element_type_preview') ?: FALSE;
  }

  /**
   * Build and fully initialize and prepare a preview of a webform element.
   *
   * @param \Drupal\webform\Plugin\WebformElementInterface $webform_element
   *   A webform element plugin.
   *
   * @return array
   *   A fully initialized and prepared preview of a webform element.
   */
  protected function buildElementPreview(WebformElementInterface $webform_element) {
    $element = $webform_element->preview();
    if ($element) {
      $webform_element->initialize($element);
      $webform_element->prepare($element, $this->webformSubmission);

      if ($webform_element->hasProperty('title_display')
        && $webform_element->getDefaultProperty('title_display') !== 'after') {
        $element['#title_display'] = 'invisible';
      }
    }

    // Placeholders.
    switch ($webform_element->getTypeName()) {
      case 'container':
        $element = $this->buildElementPreviewPlaceholder($this->t('Displays an HTML container. (i.e. @div)', ['@div' => '<div>']));
        break;

      case 'hidden':
        $element = $this->buildElementPreviewPlaceholder($this->t('Hidden element (less secure, changeable via JavaScript)'));
        break;

      case 'label':
        $element = $this->buildElementPreviewPlaceholder($this->t('Displays a form label without any associated element. (i.e. @label)', ['@label' => '<label>']));
        break;

      case 'processed_text':
        $element = $this->buildElementPreviewPlaceholder($this->t('Advanced HTML markup rendered using a text format.'));
        break;

      case 'table':
        $element = $this->buildElementPreviewPlaceholder(
          $this->t('Displays a custom table. (i.e. @table).', ['@table' => '<table>']) .
          '<br/><em>' . $this->t('Requires understanding <a href=":href">how to build tables using render arrays</a>.', [':href' => $webform_element->getPluginApiUrl()->toString()]) . '</em>'
        );
        break;

      case 'value':
        $element = $this->buildElementPreviewPlaceholder($this->t('Secure value (changeable via server-side code and tokens).'));
        break;

      case 'webform_computed_token':
        $element = $this->buildElementPreviewPlaceholder($this->t('Allows value to be computed using [tokens].'));
        break;

      case 'webform_computed_twig':
        $element = $this->buildElementPreviewPlaceholder($this->t('Allows value to be computed using a {{ Twig }} template.'));
        break;

      case 'webform_markup':
        $element = $this->buildElementPreviewPlaceholder($this->t('Basic HTML markup.'));
        break;

      case 'webform_section':
        $default_section_title_tag = $this->config('webform.settings')->get('element.default_section_title_tag');
        $element = $this->buildElementPreviewPlaceholder($this->t('Displays a section container (i.e. @section) with a header (i.e. @header).', ['@section' => '<section>', '@header' => '<' . $default_section_title_tag . '>']));
        break;
    }

    // Disable all file uploads.
    if ($webform_element instanceof WebformManagedFileBase) {
      $element['#disabled'] = TRUE;
    }

    // Custom element type specific attributes.
    switch ($webform_element->getTypeName()) {
      case 'details':
      case 'fieldset':
      case 'webform_email_confirm':
        // Title needs to be displayed.
        unset($element['#title_display']);
        break;

      case 'textarea':
      case 'webform_codemirror':
      case 'webform_rating':
        // Notice: Undefined index: #value in template_preprocess_textarea()
        // (line 382 of core/includes/form.inc).
        $element['#value'] = '';
        break;

      case 'password':
        // https://stackoverflow.com/questions/15738259/disabling-chrome-autofill
        $element['#attributes']['autocomplete'] = 'new-password';
        break;

      case 'webform_actions':
        $element = [
          '#type' => 'button',
          '#value' => $this->t('Submit'),
          '#attributes' => ['onclick' => 'return false;'],
        ];
        break;

      case 'webform_email_multiple':
        // Notice: Undefined index: #description_display in
        // template_preprocess_form_element()
        // (line 476 of core/includes/form.inc).
        $element['#description_display'] = 'after';
        break;

      case 'webform_flexbox':
        $element['#type'] = 'webform_flexbox';
        $element += [
          'element_flex_1' => [
            '#type' => 'textfield',
            '#title' => $this->t('Flex: 1'),
            '#flex' => 1,
            '#prefix' => '<div class="webform-flex webform-flex--1"><div class="webform-flex--container">',
            '#suffix' => '</div></div>',
          ],
          'element_flex_2' => [
            '#type' => 'textfield',
            '#title' => $this->t('Flex: 2'),
            '#flex' => 2,
            '#prefix' => '<div class="webform-flex webform-flex--2"><div class="webform-flex--container">',
            '#suffix' => '</div></div>',
          ],
        ];
        break;

      case 'webform_toggles':
        $element['#options_display'] = 'side_by_side';
        break;

      case 'webform_terms_of_service':
        unset($element['#title_display']);
        break;

    }

    // Add placeholder for empty element.
    if (empty($element)) {
      $element = $this->buildElementPreviewPlaceholder($this->t('No preview available.'));
    }

    // Required attributes.
    $element['#id'] = $webform_element->getTypeName();
    $element['#webform_key'] = $webform_element->getTypeName();

    return $element;
  }

  /**
   * Build preview placeholder for webform element.
   *
   * @param string $text
   *   Placeholder text.
   *
   * @return array
   *   A preview placeholder for webform element.
   */
  protected function buildElementPreviewPlaceholder($text) {
    return [
      '#markup' => $text,
      '#prefix' => '<div class="webform-ui-element-type-placeholder">',
      '#suffix' => '</div>',
    ];
  }

  /* ************************************************************************ */
  // Helper methods.
  /* ************************************************************************ */

  /**
   * Gets the sorted definition of all WebformElement plugins.
   *
   * @return array
   *   An array of WebformElement plugin definitions. Keys are element types.
   */
  protected function getDefinitions() {
    $definitions = $this->elementManager->getDefinitions();
    $definitions = $this->elementManager->getSortedDefinitions($definitions, 'category');
    $definitions = $this->elementManager->removeExcludeDefinitions($definitions);
    $grouped_definitions = $this->elementManager->getGroupedDefinitions($definitions);

    $sorted_definitions = [];
    foreach ($grouped_definitions as $grouped_definition) {
      $sorted_definitions += $grouped_definition;
    }

    return $sorted_definitions;
  }

}
