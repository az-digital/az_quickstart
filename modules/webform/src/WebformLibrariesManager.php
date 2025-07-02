<?php

namespace Drupal\webform;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformArrayHelper;

/**
 * Webform libraries manager.
 */
class WebformLibrariesManager implements WebformLibrariesManagerInterface {

  use StringTranslationTrait;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Libraries that provides additional functionality to the Webform module.
   *
   * @var array
   */
  protected $libraries;

  /**
   * Excluded libraries.
   *
   * @var array
   */
  protected $excludedLibraries;

  /**
   * Constructs a WebformLibrariesManager object.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, RendererInterface $renderer) {
    $this->libraryDiscovery = $library_discovery;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function requirements($cli = FALSE) {
    $cdn = $this->configFactory->get('webform.settings')->get('requirements.cdn') ?: FALSE;

    $libraries = $this->getLibraries();

    // Defined REQUIREMENT constants which may not be loaded.
    // @see ~/Sites/drupal_webform/mweb/core/includes/install.inc
    if (!defined('REQUIREMENT_OK')) {
      define('REQUIREMENT_INFO', -1);
      define('REQUIREMENT_OK', 0);
      define('REQUIREMENT_WARNING', 1);
      define('REQUIREMENT_ERROR', 2);
    }

    // Track stats.
    $severity = REQUIREMENT_OK;
    $stats = [
      '@total' => count($libraries),
      '@installed' => 0,
      '@excluded' => 0,
      '@missing' => 0,
    ];

    // Build library info array.
    $info = [
      '#prefix' => '<hr class="webform-hr"/><dl>',
      '#suffix' => '</dl>',
    ];

    foreach ($libraries as $library_name => $library) {
      // Excluded.
      if ($this->isExcluded($library_name)) {
        $stats['@excluded']++;
        continue;
      }

      $library_exists = $this->exists($library['name']);
      $library_path = ($library_exists) ? '/' . $this->find($library['name']) : '/libraries/' . $library['name'];

      $t_args = [
        '@title' => $library['title'],
        '@version' => $library['version'],
        '@path' => $library_path,
        ':homepage_href' => $library['homepage_url']->toString(),
        ':external_href' => 'https://www.drupal.org/docs/8/theming-drupal-8/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-theme#external',
        ':settings_libraries_href' => Url::fromRoute('webform.config.libraries')->toString(),
        ':settings_elements_href' => Url::fromRoute('webform.config.elements')->toString(),
      ];

      if (!empty($library['module'])) {
        // Installed by module.
        $t_args['@module'] = $library['module'];
        $t_args[':module_href'] = 'https://www.drupal.org/project/' . $library['module'];
        $stats['@installed']++;
        $title = $this->t('<strong>@title</strong> (Installed)', $t_args);
        $description = $this->t('The <a href=":homepage_href">@title</a> library is installed by the <b><a href=":module_href">@module</a></b> module.', $t_args);
      }
      elseif ($library_exists) {
        // Installed.
        $stats['@installed']++;
        $title = $this->t('<strong>@title @version</strong> (Installed)', $t_args);
        $description = $this->t('The <a href=":homepage_href">@title</a> library is installed in <b>@path</b>.', $t_args);
      }
      elseif ($cdn) {
        // Missing.
        $stats['@missing']++;
        $title = $this->t('<span class="color-warning"><strong>@title @version</strong> (CDN).</span>', $t_args);
        $description = $this->t('The <a href=":homepage_href">@title</a> library is not installed in <b>@path</b>.', $t_args);
        $severity = REQUIREMENT_ERROR;
      }
      else {
        // CDN.
        $stats['@missing']++;
        $title = $this->t('<strong>@title @version</strong> (CDN).', $t_args);
        $description = $this->t('The <a href=":homepage_href">@title</a> library is <a href=":external_href">externally hosted libraries</a> and loaded via a Content Delivery Network (CDN).', $t_args);
      }

      $info[$library_name] = [];
      $info[$library_name]['title'] = [
        '#markup' => $title,
        '#prefix' => '<dt>',
        '#suffix' => '</dt>',
      ];
      $info[$library_name]['description'] = [
        '#prefix' => '<dd>',
        '#suffix' => '</dd>',
      ];
      $info[$library_name]['description']['content'] = [
        '#markup' => $description,
      ];
      if (!empty($library['notes'])) {
        $info[$library_name]['description']['notes'] = [
          '#markup' => $library['notes'],
          '#prefix' => '<div><em>(',
          '#suffix' => '}</em></div>',
        ];
      }
      if (!empty($library['deprecated'])) {
        $info[$library_name]['description']['status'] = [
          '#markup' => $library['deprecated'],
          '#prefix' => '<div class="color-warning"><strong>',
          '#suffix' => '</strong></div>',
        ];
      }
    }

    // Description.
    $description = [];
    if (!$cli && $severity === REQUIREMENT_ERROR) {
      $t_args = [':href' => ($this->moduleHandler->moduleExists('help')) ? Url::fromRoute('help.page', ['name' => 'webform'], ['fragment' => 'libraries'])->toString() : 'https://www.drupal.org/docs/8/modules/webform/webform-libraries'];
      $description['download'] = [
        '#markup' => '<hr/>' .
          $this->t('Please download external libraries using one the <a href=":href">recommended methods</a>.', $t_args),
      ];
      $t_args = [':href' => Url::fromRoute('webform.config.advanced')->toString()];
      $description['cdn'] = [
        '#markup' => '<hr/>' .
          $this->t('Relying on a CDN for external libraries can cause unexpected issues with Ajax and BigPipe support. For more information see: <a href=":href">Issue #1988968</a>', [':href' => 'https://www.drupal.org/project/drupal/issues/1988968']) . '<br/>' .
          $this->t('<a href=":href">Disable CDN warning</a>', $t_args),
      ];
    }
    $description['info'] = $info;

    return [
      'webform_libraries' => [
        'title' => $this->t('Webform: External libraries'),
        'value' => $this->t('@total libraries (@installed installed; @excluded excluded; @missing CDN)', $stats),
        'description' => $this->renderer->renderPlain($description),
        'severity' => $severity,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    // @todo Inject dependency once Drupal 8.9.x is only supported.
    if (\Drupal::hasService('library.libraries_directory_file_finder')) {
      return \Drupal::service('library.libraries_directory_file_finder')->find($name) ? TRUE : FALSE;
    }
    else {
      return file_exists(DRUPAL_ROOT . '/libraries/' . $name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function find($name) {
    if (\Drupal::hasService('library.libraries_directory_file_finder')) {
      return \Drupal::service('library.libraries_directory_file_finder')->find($name);
    }
    else {
      return (file_exists('libraries/' . $name)) ? 'libraries/' . $name : FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary($name) {
    $libraries = $this->getLibraries();
    return $libraries[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries($included = NULL) {
    // Initialize libraries.
    if (!isset($this->libraries)) {
      $this->libraries = $this->initLibraries();
    }

    $libraries = $this->libraries;
    foreach ($libraries as $library_name => $library) {
      if ($included !== NULL
        && $this->isIncluded($library_name) !== $included) {
        unset($libraries[$library_name]);
      }
      if (isset($library['core'])
        && $library['core'] !== intval(\Drupal::VERSION)
        && !Settings::get('webform_libraries_ignore_core', FALSE)) {
        unset($libraries[$library_name]);
      }
    }
    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getExcludedLibraries() {
    // Initialize excluded libraries.
    if (!isset($this->excludedLibraries)) {
      $this->excludedLibraries = $this->initExcludedLibraries();
    }

    return $this->excludedLibraries;
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded($name) {
    $excluded_libraries = $this->getExcludedLibraries();
    if (empty($excluded_libraries)) {
      return FALSE;
    }

    if (isset($excluded_libraries[$name])) {
      return TRUE;
    }

    if (strpos($name, 'libraries.') !== 0 && strpos($name, 'webform/libraries.') !== 0) {
      return FALSE;
    }

    $parts = explode('.', preg_replace('#^(webform/)?libraries.#', '', $name));
    while ($parts) {
      if (isset($excluded_libraries[implode('.', $parts)])) {
        return TRUE;
      }
      array_pop($parts);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isIncluded($name) {
    return !$this->isExcluded($name);
  }

  /**
   * Initialize libraries.
   *
   * @return array
   *   An associative array containing libraries.
   */
  protected function initLibraries() {
    $libraries = [];
    $libraries['codemirror'] = [
      'title' => $this->t('Code Mirror'),
      'description' => $this->t('Code Mirror is a versatile text editor implemented in JavaScript for the browser.'),
      'notes' => $this->t('Code Mirror is used to provide a text editor for YAML, HTML, CSS, and JavaScript configuration settings and messages.'),
      'homepage_url' => Url::fromUri('https://codemirror.net/'),
      // Issue #3177233: CodeMirror 5.70.0 is displaying vertical scrollbar.
      'download_url' => Url::fromUri('https://github.com/components/codemirror/archive/refs/tags/5.65.12.zip'),
      'issues_url' => Url::fromUri('https://github.com/codemirror/codemirror/issues'),
      'version' => '5.65.12',
      'license' => 'MIT',
    ];
    $libraries['jquery.inputmask'] = [
      'title' => $this->t('jQuery: Input Mask'),
      'description' => $this->t('Input masks ensures a predefined format is entered. This can be useful for dates, numerics, phone numbers, etc…'),
      'notes' => $this->t('Input masks are used to ensure predefined and custom formats for text fields.'),
      'homepage_url' => Url::fromUri('https://robinherbots.github.io/Inputmask/'),
      'download_url' => Url::fromUri('https://github.com/RobinHerbots/jquery.inputmask/archive/refs/tags/5.0.8.zip'),
      'version' => '5.0.8',
      'license' => 'MIT',
    ];
    $libraries['jquery.intl-tel-input'] = [
      'title' => $this->t('jQuery: International Telephone Input'),
      'description' => $this->t("A jQuery plugin for entering and validating international telephone numbers. It adds a flag dropdown to any input, detects the user's country, displays a relevant placeholder and provides formatting/validation methods."),
      'notes' => $this->t('International Telephone Input is used by the Telephone element.'),
      'homepage_url' => Url::fromUri('https://github.com/jackocnr/intl-tel-input'),
      'download_url' => Url::fromUri('https://github.com/jackocnr/intl-tel-input/archive/refs/tags/v17.0.19.zip'),
      'version' => '17.0.19',
      'license' => 'MIT',
    ];
    $libraries['jquery.rateit'] = [
      'title' => $this->t('jQuery: RateIt'),
      'description' => $this->t("Rating plugin for jQuery. Fast, progressive enhancement, touch support, customizable (just swap out the images, or change some CSS), unobtrusive JavaScript (using HTML5 data-* attributes), RTL support. The Rating plugin supports as many stars as you'd like, and also any step size."),
      'notes' => $this->t('RateIt is used to provide a customizable rating element.'),
      'homepage_url' => Url::fromUri('https://github.com/gjunge/rateit.js'),
      'download_url' => Url::fromUri('https://github.com/gjunge/rateit.js/archive/refs/tags/1.1.5.zip'),
      'version' => '1.1.5',
      'elements' => ['webform_rating'],
      'license' => 'MIT',
    ];
    $libraries['jquery.textcounter'] = [
      'title' => $this->t('jQuery: Text Counter'),
      'description' => $this->t('A jQuery plugin for counting and limiting characters/words on text input, or textarea, elements.'),
      'notes' => $this->t('Word or character counting, with server-side validation, is available for text fields and text areas.'),
      'homepage_url' => Url::fromUri('https://github.com/ractoon/jQuery-Text-Counter'),
      'download_url' => Url::fromUri('https://github.com/ractoon/jQuery-Text-Counter/archive/refs/tags/0.9.1.zip'),
      'version' => '0.9.1',
      'license' => 'MIT',
    ];
    $libraries['jquery.timepicker'] = [
      'title' => $this->t('jQuery: Timepicker'),
      'description' => $this->t('A lightweight, customizable javascript timepicker plugin for jQuery, inspired by Google Calendar.'),
      'notes' => $this->t('Timepicker is used to provide a polyfill for HTML 5 time elements.'),
      'homepage_url' => Url::fromUri('https://github.com/jonthornton/jquery-timepicker'),
      'download_url' => Url::fromUri('https://github.com/jonthornton/jquery-timepicker/archive/refs/tags/1.14.0.zip'),
      'version' => '1.14.0',
      'license' => 'MIT',
    ];
    $libraries['progress-tracker'] = [
      'title' => $this->t('Progress Tracker'),
      'description' => $this->t("A flexible SASS component to illustrate the steps in a multi-step process e.g. a multi-step form, a timeline or a quiz."),
      'notes' => $this->t('Progress Tracker is used by multi-step wizard forms.'),
      'homepage_url' => Url::fromUri('https://nigelotoole.github.io/progress-tracker/'),
      'download_url' => Url::fromUri('https://github.com/NigelOToole/progress-tracker/archive/refs/tags/2.0.7.zip'),
      'version' => '2.0.7',
      'license' => 'MIT',
    ];
    $libraries['signature_pad'] = [
      'title' => $this->t('Signature Pad'),
      'description' => $this->t("Signature Pad is a JavaScript library for drawing smooth signatures. It is HTML5 canvas based and uses variable width Bézier curve interpolation. It works in all modern desktop and mobile browsers and doesn't depend on any external libraries."),
      'notes' => $this->t('Signature Pad is used to provide a signature element.'),
      'homepage_url' => Url::fromUri('https://github.com/szimek/signature_pad'),
      'download_url' => Url::fromUri('https://github.com/szimek/signature_pad/archive/refs/tags/v2.3.0.zip'),
      'version' => '2.3.0',
      'elements' => ['webform_signature'],
      'license' => 'MIT',
    ];
    $libraries['tabby'] = [
      'title' => $this->t('Tabby'),
      'description' => $this->t("Tabby provides lightweight, accessible vanilla JS toggle tabs."),
      'notes' => $this->t('Tabby is used to display tabs in the administrative UI'),
      'homepage_url' => Url::fromUri('https://github.com/cferdinandi/tabby'),
      'download_url' => Url::fromUri('https://github.com/cferdinandi/tabby/archive/refs/tags/v12.0.3.zip'),
      'version' => '12.0.3',
      'license' => 'MIT',
    ];
    $libraries['popperjs'] = [
      'title' => $this->t('Popper.js'),
      'description' => $this->t('Tippy.js a tiny, low-level library for creating "floating" elements like tooltips, popovers, dropdowns, menus, and more.'),
      'notes' => $this->t('Popper.js is used to provide tooltip behavior for elements.'),
      'homepage_url' => Url::fromUri('https://github.com/floating-ui/floating-ui'),
      'download_url' => Url::fromUri('https://registry.npmjs.org/@popperjs/core/-/core-2.11.6.tgz'),
      'version' => '2.11.6',
      'license' => 'MIT',
    ];
    $libraries['tippyjs'] = [
      'title' => $this->t('Tippy.js'),
      'description' => $this->t("Tippy.js is the complete tooltip, popover, dropdown, and menu solution for the web, powered by Popper."),
      'notes' => $this->t('Tippy.js is used to provide tooltip behavior for elements.'),
      'homepage_url' => Url::fromUri('https://github.com/atomiks/tippyjs'),
      'download_url' => Url::fromUri('https://registry.npmjs.org/tippy.js/-/tippy.js-6.3.7.tgz'),
      'version' => '6.3.7',
      'license' => 'MIT',
    ];
    $libraries['jquery.select2'] = [
      'title' => $this->t('jQuery: Select2'),
      'description' => $this->t('Select2 gives you a customizable select box with support for searching and tagging.'),
      'notes' => $this->t('Select2 is used to improve the user experience for select menus. Select2 is the recommended select menu enhancement library.'),
      'homepage_url' => Url::fromUri('https://select2.github.io/'),
      'download_url' => Url::fromUri('https://github.com/select2/select2/archive/refs/tags/4.0.13.zip'),
      'version' => '4.0.13',
      'module' => $this->moduleHandler->moduleExists('select2') ? 'select2' : '',
      'license' => 'MIT',
    ];
    $libraries['choices'] = [
      'title' => $this->t('Choices'),
      'description' => $this->t('Choices.js is a lightweight, configurable select box/text input plugin. Similar to Select2 and Selectize but without the jQuery dependency.'),
      'notes' => $this->t('Choices.js is used to improve the user experience for select menus. Choices.js is an alternative to Select2.'),
      'homepage_url' => Url::fromUri('https://choices-js.github.io/Choices/'),
      'download_url' => Url::fromUri('https://github.com/Choices-js/Choices/archive/refs/tags/v9.0.1.zip'),
      'version' => '9.0.1',
      'license' => 'MIT',
    ];
    $libraries['jquery.chosen'] = [
      'title' => $this->t('jQuery: Chosen'),
      'description' => $this->t('A jQuery plugin that makes long, unwieldy select boxes much more user-friendly.'),
      'notes' => $this->t('Chosen is used to improve the user experience for select menus. Chosen is an alternative to Select2.'),
      'homepage_url' => Url::fromUri('https://harvesthq.github.io/chosen/'),
      'download_url' => Url::fromUri('https://github.com/harvesthq/chosen/releases/download/v1.8.7/chosen_v1.8.7.zip'),
      'version' => '1.8.7',
      'module' => $this->moduleHandler->moduleExists('chosen') ? 'chosen' : '',
      'license' => 'MIT',
    ];

    // Add webform as the provider to all libraries.
    foreach ($libraries as $library_name => $library) {
      $libraries[$library_name] += [
        'optional' => TRUE,
        'provider' => 'webform',
        'license' => $this->t('N/A'),
      ];
    }

    // Allow other modules to define webform libraries.
    $this->moduleHandler->invokeAllWith('webform_libraries_info', function (callable $hook, string $module) use (&$libraries) {
      foreach ($hook() as $library_name => $library) {
        $libraries[$library_name] = $library + [
          'provider' => $module,
        ];
      }
    });

    // Allow other modules to alter webform libraries.
    $this->moduleHandler->alter('webform_libraries_info', $libraries);

    // Sort libraries by key.
    ksort($libraries);

    // Add name property to all libraries.
    foreach ($libraries as $library_name => $library) {
      $libraries[$library_name]['name'] = $library_name;
    }

    // Move deprecated libraries last.
    foreach ($libraries as $library_name => $library) {
      if (!empty($library['deprecated'])) {
        unset($libraries[$library_name]);
        $libraries[$library_name] = $library;
      }
    }

    return $libraries;
  }

  /**
   * Initialize excluded libraries.
   *
   * @return array
   *   A key array containing excluded libraries.
   */
  protected function initExcludedLibraries() {
    // Get excluded optional libraries.
    if ($excluded_libraries = $this->configFactory->get('webform.settings')->get('libraries.excluded_libraries')) {
      $excluded_libraries = array_combine($excluded_libraries, $excluded_libraries);
    }
    else {
      $excluded_libraries = [];
    }

    // Get excluded libraries based on excluded (element) types.
    $libraries = $this->getLibraries();
    foreach ($libraries as $library_name => $library) {
      if (!empty($library['elements']) && $this->areElementsExcluded($library['elements'])) {
        $excluded_libraries[$library_name] = $library_name;
      }
    }

    return $excluded_libraries;
  }

  /**
   * Determine if a library's elements are excluded.
   *
   * @param array $elements
   *   An array of element types.
   *
   * @return bool
   *   TRUE if a library's elements are excluded.
   */
  protected function areElementsExcluded(array $elements) {
    $excluded_elements = $this->configFactory->get('webform.settings')->get('element.excluded_elements');
    if (!$excluded_elements) {
      return FALSE;
    }
    return WebformArrayHelper::keysExist($excluded_elements, $elements);
  }

}
