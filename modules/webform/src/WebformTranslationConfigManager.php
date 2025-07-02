<?php

namespace Drupal\webform;

use Drupal\block\Entity\Block;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformYaml;

/**
 * Defines a class to translate webform config.
 */
class WebformTranslationConfigManager implements WebformTranslationConfigManagerInterface {

  use StringTranslationTrait;

  /**
   * A unsaved webform used to get element properties by element type.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * An associative array used to cache element properties by element type.
   *
   * @var array
   */
  protected $elementProperties = [];

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform translation manager.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $translationManager;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Constructs a WebformTranslationConfigManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformTranslationManagerInterface $translation_manager
   *   The webform translation manager.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, FormBuilderInterface $form_builder, WebformElementManagerInterface $element_manager, WebformTranslationManagerInterface $translation_manager, TypedConfigManagerInterface $typed_config_manager = NULL) {
    $this->formBuilder = $form_builder;
    $this->moduleHandler = $module_handler;
    $this->elementManager = $element_manager;
    $this->translationManager = $translation_manager;
    // @todo [Webform 7.x] Require the typed config manager.
    $this->typedConfigManager = $typed_config_manager ?: \Drupal::service('config.typed');
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    foreach ($form['config_names'] as $config_name => &$config_element) {
      if ($config_name === 'webform.settings') {
        $this->alterConfigSettingsForm($config_name, $config_element);
      }
      elseif (strpos($config_name, 'block.block.') === 0) {
        $this->alterConfigBlockForm($config_name, $config_element);
      }
      elseif (strpos($config_name, 'field.field.') === 0) {
        $this->alterConfigFieldForm($config_name, $config_element);
      }
      elseif (strpos($config_name, 'webform.webform_options.') === 0) {
        $this->alterConfigOptionsForm($config_name, $config_element);
      }
      elseif (strpos($config_name, 'webform_options_custom.webform_options_custom.') === 0) {
        $this->alterConfigOptionsCustomForm($config_name, $config_element);
      }
      elseif (strpos($config_name, 'webform_image_select.webform_image_select_images.') === 0) {
        $this->alterConfigImageSelectForm($config_name, $config_element);
      }
      elseif (strpos($config_name, 'webform.webform.') === 0) {
        $this->alterConfigWebformForm($config_name, $config_element, $form, $form_state);
      }
    }
  }

  /**
   * Alter the webform settings configuration form.
   *
   * @param string $config_name
   *   The webform settings configuration name.
   * @param array $config_element
   *   The webform settings configuration element.
   */
  protected function alterConfigSettingsForm($config_name, array &$config_element) {
    $this->alterTypedConfigElements($config_element, "webform.settings");
  }

  /**
   * Alter the webform block configuration form.
   *
   * @param string $config_name
   *   The webform block configuration name.
   * @param array $config_element
   *   The webform block configuration element.
   */
  protected function alterConfigBlockForm($config_name, array &$config_element) {
    $block = Block::load(str_replace('block.block.', '', $config_name));
    if (!$block || $block->getPluginId() !== 'webform_block') {
      return;
    }

    $this->alterTypedConfigElements($config_element['settings'], "block.settings.webform_block");
  }

  /**
   * Alter the webform field configuration form.
   *
   * @param string $config_name
   *   The webform field configuration name.
   * @param array $config_element
   *   The webform field configuration element.
   */
  protected function alterConfigFieldForm($config_name, array &$config_element) {
    if (!isset($config_element['default_value'])) {
      return;
    }

    if (!preg_match('/^field\.field\.([_a-z0-9]+)\.([_a-z0-9]+)\.([_a-z0-9]+)$/', $config_name, $match)) {
      return;
    }

    $field = FieldConfig::loadByName($match[1], $match[2], $match[3]);
    if (!$field->getType() === 'webform') {
      return;
    }

    foreach (Element::children($config_element['default_value']) as $key) {
      if (isset($config_element['default_value'][$key]['default_data'])) {
        $this->alterTextareaElement($config_element['default_value'][$key]['default_data']);
      }
    }
  }

  /**
   * Alter the webform options configuration form.
   *
   * @param string $config_name
   *   The webform options configuration name.
   * @param array $config_element
   *   The webform options configuration element.
   */
  protected function alterConfigOptionsForm($config_name, array &$config_element) {
    $this->alterTypedConfigElements($config_element, "webform.webform_options.*");
  }

  /**
   * Alter the webform options custom configuration form.
   *
   * @param string $config_name
   *   The webform options configuration name.
   * @param array $config_element
   *   The webform options configuration element.
   */
  protected function alterConfigOptionsCustomForm($config_name, array &$config_element) {
    $this->alterTypedConfigElements($config_element, "webform_options_custom.webform_options_custom.*");
  }

  /**
   * Alter the webform image select configuration form.
   *
   * @param string $config_name
   *   The webform image select configuration name.
   * @param array $config_element
   *   The webform image select configuration element.
   */
  protected function alterConfigImageSelectForm($config_name, array &$config_element) {
    $this->alterTypedConfigElements($config_element, "webform_image_select.webform_image_select_images.*");
  }

  /**
   * Alter the webform configuration form.
   *
   * @param string $config_name
   *   The webform configuration name.
   * @param array $config_element
   *   The webform configuration element.
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function alterConfigWebformForm($config_name, array &$config_element, array &$form, FormStateInterface $form_state) {
    $this->alterConfigWebformFormElements($config_name, $config_element, $form, $form_state);
    $this->alterConfigWebformFormHandlers($config_name, $config_element, $form, $form_state);

    $this->alterTypedConfigElements($config_element, "webform.webform.*");

    $webform = $this->loadWebform($config_name);
    $source_elements = $this->translationManager->getSourceElements($webform);

    $form_state->set('webform_config_name', $config_name);
    $form_state->set('webform_source_elements', $source_elements);

    // Tweak form elements.
    $element_alterations = [
      // Form settings.
      'title' => ['#maxlength' => NULL],
      // Submission settings.
      'submission_label' => ['#maxlength' => NULL],
      // Email handler.
      // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler
      'subject' => ['#maxlength' => NULL],
      'from_name' => ['#maxlength' => 255],
      'sender_name' => ['#maxlength' => 255],
      // Confirmation settings.
      'confirmation_url' => ['#maxlength' => NULL],
    ];
    $this->alterElements($config_element, $element_alterations);

    $form['#validate'][] = [get_called_class(), 'validateWebformForm'];
  }

  /**
   * {@inheritdoc}
   */
  public static function validateWebformForm(array &$form, FormStateInterface $form_state) {
    $source_elements = $form_state->get('webform_source_elements');
    if ($form_state::hasAnyErrors() || empty($source_elements)) {
      return;
    }

    $values = $form_state->getValues();

    $config_name = $form_state->get('webform_config_name');

    $translation_elements = $values['translation']['config_names'][$config_name]['elements'];
    foreach ($translation_elements as $key => $element) {
      $translation_elements[$key] = WebformArrayHelper::addPrefix($element);
      // Handle composite elements.
      if (isset($translation_elements[$key]['#element'])) {
        foreach ($translation_elements[$key]['#element'] as $composite_key => $composite_element) {
          $translation_elements[$key]['#element'][$composite_key] = WebformArrayHelper::addPrefix($composite_element);
        }
      }
      // Handle 'text_format' elements.
      elseif (isset($translation_elements[$key]['#text'])
        && isset($translation_elements[$key]['#text']['value'])
        && isset($translation_elements[$key]['#text']['format'])) {
        $translation_elements[$key]['#text'] = $translation_elements[$key]['#text']['value'];
      }
    }

    /** @var \Drupal\webform\WebformTranslationConfigManagerInterface $translation_config_manager */
    $translation_config_manager = \Drupal::service('webform.translation_config_manager');

    // Remove any translation property that has not been translated.
    $translation_config_manager->mergeTranslationAndSourceElementsProperties($translation_elements, $source_elements);

    // Update webform value.
    $values['translation']['config_names'][$config_name]['elements'] = ($translation_elements) ? Yaml::encode($translation_elements) : '';
    $form_state->setValues($values);
  }

  /* ************************************************************************ */
  // Handler alteration methods.
  /* ************************************************************************ */

  /**
   * Alter the webform configuration form handlers.
   *
   * @param string $config_name
   *   The webform configuration name.
   * @param array $config_element
   *   The webform configuration element.
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function alterConfigWebformFormHandlers($config_name, array &$config_element, array &$form, FormStateInterface $form_state) {
    $handlers = &$config_element['handlers'];
    // Verify if the webform has any handler.
    if (!isset($handlers)) {
      return;
    }

    $webform = $this->loadWebform($config_name);
    foreach (Element::children($handlers) as $handler_id) {
      $handler = $webform->getHandler($handler_id);
      if (!$handler) {
        continue;
      }

      // Apply custom logic to email body which can be twig, html, or text.
      if ($handler instanceof EmailWebformHandler) {
        $body_element =& NestedArray::getValue($config_element, ['handlers', $handler_id, 'settings', 'body']);
        if ($body_element) {
          $configuration = $handler->getConfiguration();

          $default_value = (string) ($body_element['translation']['#default_value'] ?? '');
          if (preg_match('/^(_default|\[[^]]+\])$/', $default_value)) {
            // Don't alter the body element if the value '_default' or a token.
          }
          elseif (!empty($configuration['settings']['twig'])) {
            $this->alterTextareaElement($body_element, 'twig');
            $body_element['translation']['#access'] = WebformTwigExtension::hasEditTwigAccess();
          }
          elseif (!empty($configuration['settings']['html'])) {
            $this->alterHtmlEditorElement($body_element);
          }
          else {
            $this->alterTextareaElement($body_element, 'text');
          }
        }
      }
    }
  }

  /* ************************************************************************ */
  // Form and element alteration methods.
  /* ************************************************************************ */

  /**
   * Alter the webform configuration form elements.
   *
   * @param string $config_name
   *   The webform configuration name.
   * @param array $config_element
   *   The webform configuration element.
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function alterConfigWebformFormElements($config_name, array &$config_element, array &$form, FormStateInterface $form_state) {
    $webform = $this->loadWebform($config_name);

    $translation_langcode = $form_state->get('config_translation_language')->getId();
    $source_elements = $this->translationManager->getSourceElements($webform);
    $translation_elements = $this->translationManager->getTranslationElements($webform, $translation_langcode);

    $elements = &$config_element['elements'];

    // Remove the #theme and source properties so that just the
    // translation details element is rendered.
    unset($elements['#theme'], $elements['source']);

    // Get translation element parents.
    $translation_parents = $elements['translation']['#parents'];

    // Replace the translation element with a details element.
    $elements['translation'] = [
      '#type' => 'details',
      '#title' => $this->t('Webform elements'),
      '#open' => TRUE,
      '#parents' => $translation_parents,
    ];
    foreach ($translation_elements as $element_key => $translation_element) {
      $source_element = $source_elements[$element_key];
      $element = $webform->getElement($element_key);
      $translation_element_parents = array_merge($translation_parents, [$element_key]);

      $elements['translation'][$element_key] = $this->buildConfigWebformFormElements($element, $translation_element, $source_element, $translation_element_parents);
    }
  }

  /**
   * Build config webform form elements.
   *
   * @param array $element
   *   The Webform element.
   * @param array $translation_element
   *   The Webform element's translated properties.
   * @param array $source_element
   *   The Webform element's source properties.
   * @param array $parents
   *   The Webform element's parents.
   *
   * @return array
   *   A render array containing config webform form elements.
   */
  protected function buildConfigWebformFormElements(array $element, array $translation_element, array $source_element, array $parents) {
    $webform_element = $this->elementManager->getElementInstance($element);
    $element_properties = $this->getWebformElementProperties($webform_element->getPluginId());

    $elements = [
      '#type' => 'details',
      '#title' => $this->t('@title (@type)', [
        '@title' => $element['#title'] ?? '',
        '@type' => $webform_element->getPluginId(),
      ]),
      '#open' => TRUE,
    ];
    foreach ($translation_element as $property_name => $property_value) {
      $property_key = ltrim($property_name, '#');
      $property_parents = array_merge($parents, [$property_key]);
      $property_type = (isset($element_properties[$property_key]) && isset($element_properties[$property_key]['#type']))
        ? $element_properties[$property_key]['#type']
        : NULL;

      // NOTE: It is possible that all the below code could be moved into
      // the WebformElement plugin but this would create more abstraction.
      // For now, it is easier to keep all the logic in this one class/service.
      if (is_array($property_value) && !WebformArrayHelper::isMultidimensional($property_value) && !WebformElementHelper::properties($property_value)) {
        // Options.
        $elements[$property_key] = $this->buildConfigWebformFormOptionsPropertyElement(
          $element,
          $translation_element,
          $source_element,
          $property_parents
        );
      }
      elseif ($property_type === 'webform_image_select_element_images' && $property_name === '#images') {
        // Images.
        $elements[$property_key] = $this->buildConfigWebformFormImageSelectPropertyElement(
          $element,
          $translation_element,
          $source_element,
          $property_parents
        );
      }
      elseif ($property_type === 'webform_element_composite' && $property_name === '#element') {
        // Composite.
        $elements[$property_key] = $this->buildConfigWebformFormCompositePropertyElement(
          $element,
          $translation_element,
          $source_element,
          $property_parents
        );
      }
      else {
        // Default.
        $elements[$property_key] = $this->buildConfigWebformFormDefaultPropertyElement(
          $element,
          $translation_element,
          $source_element,
          $property_parents
        );
      }
    }
    return $elements;
  }

  /**
   * Build config webform form options property element.
   *
   * @param array $element
   *   The Webform element.
   * @param array $translation_element
   *   The Webform element's translated properties.
   * @param array $source_element
   *   The Webform element's source properties.
   * @param array $property_parents
   *   The Webform element's parents.
   *
   * @return array
   *   A render array containing config webform form options property element.
   */
  protected function buildConfigWebformFormOptionsPropertyElement(array $element, array $translation_element, array $source_element, array $property_parents) {
    $property_key = end($property_parents);
    $property_name = '#' . $property_key;

    $webform_element = $this->elementManager->getElementInstance($element);
    $element_property = $this->getWebformElementProperty($webform_element->getPluginId(), $property_name);

    $property_value = $translation_element[$property_name];
    $property_title = $element_property['#title'] ?? $property_name;

    // Options (key/value pairs).
    $translation_options = $property_value;
    $source_options = $source_element[$property_name];

    $t_args = ['@label' => isset($element['#label']) ? Unicode::ucfirst($element['#label']) : $this->t('Options')];
    if (!empty($element['#options_description'])) {
      $options_title = $this->t('@label text', $t_args);
    }
    else {
      $options_title = $this->t('@label text -- description', $t_args);
    }

    // Header.
    $header = [
      'source' => ['data' => $options_title, 'width' => '50%'],
      'translation' => ['data' => $options_title, 'width' => '50%'],
    ];

    // Rows.
    $rows = [];
    foreach ($translation_options as $option_value => $option_text) {
      $t_args = [
        '@value' => $option_value,
        '@text' => $source_options[$option_value],
      ];
      $row = [
        'source' => [
          '#markup' => $this->t('@text (@value)', $t_args),
        ],
        'translation' => [
          '#type' => 'textfield',
          '#title' => $this->t('@text (@value)', $t_args),
          '#title_display' => 'invisible',
          '#maxlength' => NULL,
          '#default_value' => $option_text,
          '#parents' => array_merge($property_parents, [$option_value]),
        ],
      ];
      $rows[] = $row;
    }

    return [
      '#type' => 'item',
      '#title' => $property_title,
      'options' => [
        '#type' => 'table',
        '#header' => $header,
      ] + $rows,
    ];
  }

  /**
   * Build config webform form image select property element.
   *
   * @param array $element
   *   The Webform element.
   * @param array $translation_element
   *   The Webform element's translated properties.
   * @param array $source_element
   *   The Webform element's source properties.
   * @param array $property_parents
   *   The Webform element's parents.
   *
   * @return array
   *   A render array containing config webform form image select property element.
   */
  protected function buildConfigWebformFormImageSelectPropertyElement(array $element, array $translation_element, array $source_element, array $property_parents) {
    $property_key = end($property_parents);
    $property_name = '#' . $property_key;

    $webform_element = $this->elementManager->getElementInstance($element);
    $element_property = $this->getWebformElementProperty($webform_element->getPluginId(), $property_name);

    $property_value = $translation_element[$property_name];
    $property_title = $element_property['#title'] ?? $property_name;

    // Images.
    $translation_images = $property_value;
    $source_images = $source_element[$property_name];

    // Header.
    $header = [
      'source' => ['data' => $this->t('Image text/src'), 'width' => '50%'],
      'translation' => ['data' => $this->t('Image text/src'), 'width' => '50%'],
    ];

    // Rows.
    $rows = [];
    foreach ($translation_images as $image_value => $image) {
      $t_args = [
        '@value' => $image_value,
        '@text' => $source_images[$image_value]['text'],
        '@src' => $source_images[$image_value]['src'],
      ];

      $image_src = $source_images[$image_value]['src'];
      try {
        $image_url = Url::fromUri($source_images[$image_value]['src']);
      }
      catch (\Exception $exception) {
        $image_url = NULL;
      }
      $row = [
        'source' => [
          'text' => [
            '#markup' => $this->t('@text (@value)', $t_args),
            '#suffix' => '<br/>',
          ],
          'src' => $image_url
            ? ['#type' => 'link', '#url' => $image_url, '#title' => $image_src]
            : ['#markup' => $image_src],
        ],
        'translation' => [
          'text' => [
            '#type' => 'textfield',
            '#title' => $this->t('Image text (@value)', $t_args),
            '#title_display' => 'invisible',
            '#default_value' => $image['text'],
            '#parents' => array_merge($property_parents, [$image_value, 'text']),
          ],
          'src' => [
            '#type' => 'textfield',
            '#title' => $this->t('Image src (@value)', $t_args),
            '#title_display' => 'invisible',
            '#default_value' => $image['src'],
            '#parents' => array_merge($property_parents, [$image_value, 'src']),
          ],
        ],
      ];
      if (function_exists('imce_process_url_element')) {
        imce_process_url_element($row['translation']['src'], 'link');
      }
      $rows[] = $row;
    }

    $element = [
      '#type' => 'item',
      '#title' => $property_title,
      'options' => [
        '#type' => 'table',
        '#header' => $header,
      ] + $rows,
    ];
    if (function_exists('imce_process_url_element')) {
      $element['#attached']['library'][] = 'webform/imce.input';
    }
    return $element;
  }

  /**
   * Build config webform form composite property element.
   *
   * @param array $element
   *   The Webform element.
   * @param array $translation_element
   *   The Webform element's translated properties.
   * @param array $source_element
   *   The Webform element's source properties.
   * @param array $property_parents
   *   The Webform element's parents.
   *
   * @return array
   *   A render array containing config webform form composite property element.
   */
  protected function buildConfigWebformFormCompositePropertyElement(array $element, array $translation_element, array $source_element, array $property_parents) {
    $property_key = end($property_parents);
    $property_name = '#' . $property_key;

    /** @var \Drupal\webform\Plugin\WebformElement\WebformCustomComposite $webform_element */
    $webform_element = $this->elementManager->getElementInstance($element);
    $webform_element->initializeCompositeElements($element);
    $composite_elements = $element['#webform_composite_elements'];

    $property_value = $translation_element[$property_name];

    $property_element = [
      '#type' => 'details',
      '#title' => $this->t('Composite elements'),
      '#open' => TRUE,
    ];

    foreach ($property_value as $composite_element_key => $translation_composite_element) {
      $composite_element = $composite_elements[$composite_element_key];
      $composite_parents = array_merge($property_parents, [$composite_element_key]);
      $source_composite_element = $source_element[$property_name][$composite_element_key];
      $property_element[$composite_element_key] = $this->buildConfigWebformFormElements(
        $composite_element,
        $translation_composite_element,
        $source_composite_element,
        $composite_parents
      );
    }

    return $property_element;
  }

  /**
   * Build config webform form default property element.
   *
   * @param array $element
   *   The Webform element.
   * @param array $translation_element
   *   The Webform element's translated properties.
   * @param array $source_element
   *   The Webform element's source properties.
   * @param array $property_parents
   *   The Webform element's parents.
   *
   * @return array
   *   A render array containing config webform form default property element.
   */
  protected function buildConfigWebformFormDefaultPropertyElement(array $element, array $translation_element, array $source_element, array $property_parents) {
    $property_key = end($property_parents);
    $property_name = '#' . $property_key;

    $webform_element = $this->elementManager->getElementInstance($element);
    $element_property = $this->getWebformElementProperty($webform_element->getPluginId(), $property_name);

    $property_value = $translation_element[$property_name];
    $property_title = $element_property['#title'] ?? $property_name;
    $property_type = $element_property['#type'] ?? NULL;

    $property_translation_element = [
      '#title' => $property_title,
      '#default_value' => $property_value,
      '#parents' => $property_parents,
    ];

    if (is_array($element_property) && array_key_exists("#maxlength", $element_property)) {
      $property_translation_element += [
        '#maxlength' => $element_property['#maxlength'],
      ];
    }

    if (is_array($property_value)) {
      $property_translation_element += [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
      ];
    }
    elseif ($property_type === 'text_format') {
      $format = $element['#format'] ?? $element_property['#format'];
      $property_translation_element += [
        '#type' => 'text_format',
        '#title_display' => 'hidden',
        '#format' => $format,
        '#allowed_formats' => [$format],
      ];
    }
    elseif ($property_type) {
      $property_translation_element += [
        '#type' => $property_type,
      ];
    }
    else {
      $property_translation_element += [
        '#type' => 'textarea',
        '#rows' => 1,
      ];
    }

    // Property source element.
    $property_source_element = [
      '#title' => $property_title,
    ];
    if (is_array($source_element[$property_name])) {
      $property_source_element += [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#default_value' => $source_element[$property_name],
        '#disabled' => TRUE,
        '#attributes' => ['readonly' => TRUE],
      ];
    }
    elseif ($property_translation_element['#type'] === 'webform_html_editor') {
      $property_source_element += [
        '#type' => 'item',
        'html' => WebformHtmlEditor::checkMarkup($source_element[$property_name]),
      ];
    }
    elseif ($property_translation_element['#type'] === 'text_format') {
      $property_source_element += [
        '#type' => 'processed_text',
        '#text' => $source_element[$property_name],
        '#format' => $element_property['#format'],
      ];
    }
    else {
      $property_source_element += [
        '#type' => 'item',
        '#plain_text' => $source_element[$property_name],
      ];
    }

    return [
      '#theme' => 'config_translation_manage_form_element',
      'source' => $property_source_element,
      'translation' => $property_translation_element,
    ];
  }

  /**
   * Alter form elements recursively.
   *
   * @param array $elements
   *   An associative array of form elements.
   * @param array $element_alterations
   *   An associative array of element alterations.
   */
  protected function alterElements(array &$elements, array $element_alterations) {
    foreach ($elements as $key => &$element) {
      // Make sure the element key is a string.
      $key = (string) $key;
      if (Element::property($key) || !is_array($element)) {
        continue;
      }

      // Override/alter translation element.
      if (array_key_exists($key, $element_alterations)
        && isset($element['translation'])
        && isset($element['translation']['#type'])) {
        $element['translation'] = $element_alterations[$key] + $element['translation'];
      }

      $this->alterElements($element, $element_alterations);
    }
  }

  /* ************************************************************************ */

  /**
   * Alter the webform configuration form using type config schema.
   *
   * @param array $elements
   *   An array of form elements.
   * @param string $plugin_id
   *   A plugin id.
   */
  protected function alterTypedConfigElements(array &$elements, $plugin_id) {
    // DEBUG: List typed config.
    // dsm($this->typedConfigManager->getDefinitions());
    $definition = $this->typedConfigManager->getDefinition($plugin_id);
    $this->alterSchemaElementsRecursive($elements, $definition['mapping']);
  }

  /**
   * Alter schema elements.
   *
   * @param array $elements
   *   An array of form elements.
   * @param array $schema_mapping
   *   Schema mapping.
   */
  protected function alterSchemaElementsRecursive(array &$elements, array $schema_mapping) {
    foreach (Element::children($elements) as $element_key) {
      if (!isset($schema_mapping[$element_key])) {
        continue;
      }

      $element = &$elements[$element_key];
      $schema = &$schema_mapping[$element_key];

      if (isset($schema['type']) && $schema['type'] === 'mapping') {
        $this->alterSchemaElementsRecursive($element, $schema['mapping']);
      }
      elseif (isset($schema['webform_type'])) {
        switch ($schema['webform_type']) {
          case 'html':
            $this->alterHtmlEditorElement($element);
            break;

          case 'yaml':
          case 'twig':
          case 'text':
            $this->alterTextareaElement($element, $schema['webform_type']);
            break;
        }
      }
    }
  }

  /**
   * Alter text area element and convert it to an HTML editor.
   *
   * @param array $element
   *   A element containing 'source' and 'translation'.
   */
  protected function alterHtmlEditorElement(array &$element) {
    // Undo nl2br() so that the HTML markup's spacing is correct.
    // @see \Drupal\config_translation\FormElement\FormElementBase::getSourceElement
    // @see https://stackoverflow.com/questions/2494754/opposite-of-nl2br-is-it-str-replace
    $element['source']['#markup'] = preg_replace("#<br />$#m", "", (string) $element['source']['#markup']);
    $element['translation']['#type'] = 'webform_html_editor';
  }

  /**
   * Alter text area element and convert it to a Codemirror editor.
   *
   * @param array $element
   *   A element containing 'source' and 'translation'.
   * @param string $mode
   *   Codemirror editor mode. Default to 'yaml'.
   */
  protected function alterTextareaElement(array &$element, $mode = 'yaml') {
    // Source.
    $source_value = trim((string) $element['source']['#markup']);
    $source_value = preg_replace('#^<span lang="[^"]+">(.*)</span>#ims', '\1', $source_value);

    // Translation.
    $translation_value = $element['translation']['#default_value'];

    // Alter source and translation values based on the mode.
    switch ($mode) {
      case 'twig':
        $source_value = preg_replace('#<br />#s', '', $source_value);
        break;

      case 'yaml':
        $source_value = strip_tags($source_value);
        $source_value = ($source_value) ? trim(WebformYaml::tidy($source_value)) : '';
        $translation_value = ($translation_value) ? trim(WebformYaml::tidy($translation_value)) : '';
        break;
    }

    // Source.
    $element['source']['#wrapper_attributes']['class'][] = 'webform-translation-source';
    $element['source']['value'] = [
      '#type' => 'webform_codemirror',
      '#mode' => $mode,
      '#value' => $source_value,
      '#disabled' => TRUE,
      '#skip_validation' => TRUE,
      '#attributes' => ['readonly' => TRUE],
    ];
    unset($element['source']['#markup']);

    // Translation.
    $element['translation']['#type'] = 'webform_codemirror';
    $element['translation']['#mode'] = $mode;
    $element['translation']['#default_value'] = $translation_value;
    $element['#attached']['library'][] = 'webform/webform.admin.translation';
  }

  /* ************************************************************************ */
  // Utility methods.
  /* ************************************************************************ */

  /**
   * Flatten a nested array of elements.
   *
   * @param array $elements
   *   An array of elements.
   *
   * @return array
   *   A flattened array of elements.
   */
  protected function getElementsFlattened(array $elements) {
    $flattened_elements = [];
    foreach ($elements as $key => &$element) {
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }
      if (isset($element['#type']) && !in_array($element['#type'], ['container', 'details', 'fieldset'])) {
        $flattened_elements[$key] = WebformElementHelper::getProperties($element);
      }
      $flattened_elements += $this->getElementsFlattened($element);
    }
    return $flattened_elements;
  }

  /**
   * Get flattened webform element properties from the webform_ui.module.
   *
   * @param string $type
   *   The webform element type.
   * @param string $property_name
   *   The webform element property name.
   *
   * @return array
   *   The webform element type's properties from as a flattened
   *   associative array key by property names.
   */
  protected function getWebformElementProperty($type, $property_name) {
    $property_key = ltrim($property_name, '#');
    $properties = $this->getWebformElementProperties($type);
    return $properties[$property_key] ?? NULL;

  }

  /**
   * Get flattened webform element properties from the webform_ui.module.
   *
   * @param string $type
   *   The webform element type.
   *
   * @return array
   *   The webform element type's properties from as a flattened
   *   associative array key by property names.
   */
  protected function getWebformElementProperties($type) {
    if (isset($this->elementProperties[$type])) {
      return $this->elementProperties[$type];
    }

    $this->elementProperties[$type] = [];
    if ($this->moduleHandler->moduleExists('webform_ui')) {
      if (!isset($this->webform)) {
        $this->webform = Webform::create();
      }
      try {
        $form = $this->formBuilder->getForm('\Drupal\webform_ui\Form\WebformUiElementAddForm', $this->webform, NULL, NULL, $type);
        $this->elementProperties[$type] = $this->getElementsFlattened($form['properties']);
      }
      catch (\Exception $exception) {
        // If the element type does not exist, do nothing.
      }
    }

    return $this->elementProperties[$type];
  }

  /**
   * Merge translation and source element properties.
   *
   * @param array $translation_elements
   *   An array of elements.
   * @param array $source_elements
   *   An array of elements to be merged.
   */
  protected function mergeTranslationAndSourceElementsProperties(array &$translation_elements, array $source_elements) {
    foreach ($translation_elements as $key => &$translation_element) {
      if (!isset($source_elements[$key])) {
        continue;
      }
      $source_element = $source_elements[$key];
      if ($translation_element === $source_element) {
        unset($translation_elements[$key]);
      }
      elseif (is_array($translation_element) && is_array($source_element)) {
        $this->mergeTranslationAndSourceElementsProperties($translation_element, $source_element);
        if (empty($translation_element)) {
          unset($translation_elements[$key]);
        }
      }
    }
  }

  /**
   * Load a configuration name's associated webform.
   *
   * @param string $config_name
   *   Configuration name.
   *
   * @return \Drupal\webform\WebformInterface
   *   The webform associated with the configuration name.
   */
  protected function loadWebform($config_name) {
    $webform_id = str_replace('webform.webform.', '', $config_name);
    return Webform::load($webform_id);
  }

}
