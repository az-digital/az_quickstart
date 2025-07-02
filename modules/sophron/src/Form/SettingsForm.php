<?php

declare(strict_types=1);

namespace Drupal\sophron\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sophron\CoreExtensionMimeTypeGuesserExtended;
use Drupal\sophron\MimeMapManagerInterface;
use FileEye\MimeMap\MappingException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Main Sophron settings admin form.
 */
class SettingsForm extends ConfigFormBase {

  use SchemaCheckTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sophron_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sophron.settings',
    ];
  }

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\sophron\MimeMapManagerInterface $mimeMapManager
   *   The MIME map manager service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfig
   *   The typed config service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected MimeMapManagerInterface $mimeMapManager,
    protected TypedConfigManagerInterface $typedConfig,
  ) {
    parent::__construct($config_factory, $typedConfig);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get(MimeMapManagerInterface::class),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sophron.settings');

    // Vertical tabs.
    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#tree' => FALSE,
    ];

    // Guessing.
    $guessing_status = $this->mimeMapManager->requirements('runtime')['mime_type_guessing_sophron'];
    $form['guessing'] = [
      '#type' => 'details',
      '#title' => $guessing_status['title'],
      '#group' => 'tabs',
    ];
    $form['guessing']['info'] = [
      '#type' => 'item',
      '#title' => $guessing_status['value'],
      '#description' => $guessing_status['description'],
    ];

    // Mapping.
    $form['mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('Mapping'),
      '#description' => $this->t("Manage additional MIME types and mapping issues."),
      '#group' => 'tabs',
    ];
    $options = [
      MimeMapManagerInterface::DRUPAL_MAP => $this->t("<strong>Sophron map.</strong> Use this map for maximum compatibility with Drupal, still increasing the number of MIME types and file extensions identified."),
      MimeMapManagerInterface::DEFAULT_MAP => $this->t("<strong>MimeMap default map.</strong> Use this map for maximum compatibility with Apache and Freedesktop projects, accepting differences versus current Drupal mappings."),
      MimeMapManagerInterface::CUSTOM_MAP => $this->t("<strong>Custom map.</strong> Use this option to select a custom built mapping class."),
    ];
    $form['mapping']['map_option'] = [
      '#type' => 'radios',
      '#title' => $this->t('Map'),
      '#default_value' => $config->get('map_option'),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t("Select the map to use."),
    ];
    $form['mapping']['map_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class name'),
      '#description' => $this->t('A fully qualified PHP class name. The map class must extend from \FileEye\MimeMap\Map\AbstractMap.'),
      '#default_value' => $config->get('map_class'),
      '#states' => [
        'visible' => [
          ':radio[name="map_option"]' => ['value' => MimeMapManagerInterface::CUSTOM_MAP],
        ],
      ],
    ];

    $commands = $config->get('map_commands');
    $form['mapping']['map_commands'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mapping commands'),
      '#description' => $this->t("The commands below alter the default MIME type mapping. More information in the module's README.md file."),
      '#description_display' => 'before',
      '#rows' => 5,
      '#default_value' => empty($commands) ? '' : Yaml::dump($commands, 1),
    ];

    // Mapping errors.
    if ($errors = $this->mimeMapManager->getMappingErrors($this->mimeMapManager->getMapClass())) {
      $form['mapping']['mapping_errors'] = [
        '#type' => 'details',
        '#collapsible' => TRUE,
        '#open' => TRUE,
        '#title' => $this->t("Mapping errors"),
        '#description' => $this->t("The list below shows the errors occurring in applying mapping commands to the map. Correct them to clean up the list."),
      ];

      $rows = [];
      foreach ($errors as $error) {
        $rows[] = [
          $error['method'],
          "'" . implode("', '", $error['args']) . "'",
          $error['type'],
          $error['message'],
        ];
      }

      $form['mapping']['mapping_errors']['table'] = [
        '#type' => 'table',
        '#id' => 'sophron-mapping-errors-table',
        '#header' => [
          ['data' => $this->t('Method')],
          ['data' => $this->t('Arguments')],
          ['data' => $this->t('Error')],
          ['data' => $this->t('Description')],
        ],
        '#rows' => $rows,
      ];
    }

    // Mapping gaps.
    // @todo BC starts. Resolve in sophron:3.0.0.
    if (method_exists($this->mimeMapManager, 'determineMapGaps')) {
      $gaps = $this->mimeMapManager->determineMapGaps($this->mimeMapManager->getMapClass());
    }
    else {
      // @phpstan-ignore method.deprecated
      $gaps = $this->determineMapGaps();
    }
    // @todo BC ends.
    if ($gaps !== []) {
      $form['mapping']['gaps'] = [
        '#type' => 'details',
        '#collapsible' => TRUE,
        '#open' => FALSE,
        '#title' => $this->t("Mapping gaps"),
        '#description' => $this->t("The list below shows the gaps of the current map vs. Drupal's core MIME type mapping. Overcome the gaps by adding additional mapping commands."),
      ];
      $form['mapping']['gaps']['table'] = [
        '#type' => 'table',
        '#id' => 'sophron-mapping-gaps-table',
        '#header' => [
          ['data' => $this->t('File extension')],
          ['data' => $this->t('Drupal core MIME type')],
          ['data' => $this->t('Gap')],
        ],
        '#rows' => $gaps,
      ];
    }

    // Mime types.
    $form['types'] = [
      '#type' => 'details',
      '#title' => $this->t('MIME types'),
      '#description' => $this->t("List of MIME types and their file extensions."),
      '#group' => 'tabs',
    ];
    $rows = [];
    $i = 1;
    foreach ($this->mimeMapManager->listTypes() as $type_string) {
      $type = $this->mimeMapManager->getType($type_string);
      $rows[] = [
        $i++,
        $type_string,
        implode(', ', $type->getExtensions()),
        $type->hasDescription() ? $type->getDescription() : '',
        implode(', ', $type->getAliases()),
      ];
    }
    $form['types']['table'] = [
      '#type' => 'table',
      '#id' => 'sophron-mime-types-table',
      '#header' => [
        ['data' => $this->t('#')],
        ['data' => $this->t('MIME Type')],
        ['data' => $this->t('File extensions')],
        ['data' => $this->t('Description')],
        ['data' => $this->t('Aliases')],
      ],
      '#rows' => $rows,
    ];

    // File extensions.
    $form['extensions'] = [
      '#type' => 'details',
      '#title' => $this->t('File extensions'),
      '#description' => $this->t("List of file extensions and their MIME types."),
      '#group' => 'tabs',
    ];
    $rows = [];
    $i = 1;
    foreach ($this->mimeMapManager->listExtensions() as $ext) {
      $extension = $this->mimeMapManager->getExtension((string) $ext);
      $defaultExtensionType = $this->mimeMapManager->getType($extension->getDefaultType());
      $rows[] = [
        $i++,
        (string) $ext,
        implode(', ', $extension->getTypes()),
        $defaultExtensionType->hasDescription() ? $defaultExtensionType->getDescription() : '',
      ];
    }
    $form['extensions']['table'] = [
      '#type' => 'table',
      '#id' => 'sophron-extensions-table',
      '#header' => [
        ['data' => $this->t('#')],
        ['data' => $this->t('File extension')],
        ['data' => $this->t('MIME types')],
        ['data' => $this->t('Description')],
      ],
      '#rows' => $rows,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Custom map class.
    if ($form_state->getValue('map_option') == MimeMapManagerInterface::CUSTOM_MAP && !$this->mimeMapManager->isMapClassValid($form_state->getValue('map_class'))) {
      $form_state->setErrorByName('map_class', $this->t("The map class is invalid. Make sure the selected class is an extension of \FileEye\MimeMap\Map\AbstractMap."));
    }

    // Mapping commands.
    $mapCommands = trim($form_state->getValue('map_commands'));
    if ($mapCommands !== '') {
      try {
        $parsedCommands = Yaml::parse($mapCommands);
        $data = $this->configFactory->get('sophron.settings')->get();
        $data['map_commands'] = $parsedCommands;
        $schema_errors = $this->checkConfigSchema($this->typedConfig, 'sophron.settings', $data);
        if (is_array($schema_errors)) {
          $fail_items = [];
          foreach ($schema_errors as $key => $value) {
            $matches = [];
            if (preg_match('/sophron\.settings\:map\_commands\.(\d+)/', $key, $matches)) {
              $item = (int) $matches[1] + 1;
              $fail_items[$item] = $item;
            }
          }
          $form_state->setErrorByName('map_commands', $this->t("The items at line(s) @lines are wrongly typed. Make sure they follow the pattern '- {method: foo, arguments: [arg1, arg2, ...]}'.", [
            '@lines' => implode(', ', $fail_items),
          ]));
        }
      }
      catch (\Exception $e) {
        $form_state->setErrorByName('map_commands', $this->t("YAML syntax error: @error", ['@error' => $e->getMessage()]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('sophron.settings');

    try {
      $config->set('map_option', $form_state->getValue('map_option'));
      $config->set('map_class', $form_state->getValue('map_class'));
      $commands = Yaml::parse($form_state->getValue('map_commands'));
      $config->set('map_commands', $commands ?: []);
      $config->save();
    }
    catch (\Exception $e) {
      // Do nothing.
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns an array of gaps of current map vs Drupal's core mapping.
   *
   * @return array
   *   An array of simple arrays, each having a file extension, its Drupal MIME
   *   type guess, and a gap information.
   *
   * @deprecated in sophron:2.2.0 and is removed from sophron:3.0.0. Use
   *   MimeMapManager::determineMapGaps() instead.
   *
   * @see https://www.drupal.org/project/sophron/issues/3494318
   */
  protected function determineMapGaps(): array {
    @trigger_error(__METHOD__ . '() is deprecated in sophron:2.2.0 and is removed from sophron:3.0.0. Use MimeMapManager::determineMapGaps() instead. See https://www.drupal.org/project/sophron/issues/3494318', E_USER_DEPRECATED);

    $core_extended_guesser = new CoreExtensionMimeTypeGuesserExtended();

    $extensions = $core_extended_guesser->listExtensions();
    sort($extensions);

    $rows = [];
    foreach ($extensions as $ext) {
      $drupal_mime_type = $core_extended_guesser->guessMimeType('a.' . (string) $ext);

      $extension = $this->mimeMapManager->getExtension((string) $ext);
      try {
        $mimemap_mime_type = $extension->getDefaultType();
      }
      catch (MappingException $e) {
        $mimemap_mime_type = '';
      }

      $gap = '';
      if ($mimemap_mime_type === '') {
        $gap = $this->t('No MIME type mapped to this file extension.');
      }
      elseif (mb_strtolower($drupal_mime_type) != mb_strtolower($mimemap_mime_type)) {
        $gap = $this->t("File extension mapped to '@type' instead.", ['@type' => $mimemap_mime_type]);
      }

      if ($gap !== '') {
        $rows[] = [(string) $ext, $drupal_mime_type, $gap];
      }
    }

    return $rows;
  }

}
