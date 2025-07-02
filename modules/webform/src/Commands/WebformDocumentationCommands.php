<?php

namespace Drupal\webform\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\webform\WebformHelpManagerInterface;

/**
 * Webform documentation related commands for Drush 9.x and 10.x.
 */
class WebformDocumentationCommands extends WebformCommandsBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

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
   * The webform element validator.
   *
   * @var \Drupal\webform\WebformEntityElementsValidatorInterface
   */
  protected $elementsValidator;

  /**
   * The webform help manager.
   *
   * @var \Drupal\webform\WebformHelpManagerInterface
   */
  protected $helpManager;

  /**
   * WebformDocumentationCommands constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\webform\WebformHelpManagerInterface $help_manager
   *   The webform help manager.
   */
  public function __construct(FileSystemInterface $file_system, ModuleHandlerInterface $module_handler, RendererInterface $renderer, WebformHelpManagerInterface $help_manager) {
    parent::__construct();
    $this->fileSystem = $file_system;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->helpManager = $help_manager;
  }

  /* ************************************************************************ */
  // Docs.
  /* ************************************************************************ */

  /**
   * Make sure Tidy dependency is installed.
   *
   * @hook validate webform:docs
   */
  public function docsValidate(CommandData $commandData) {
    if (!class_exists('\tidy')) {
      throw new \Exception(dt('The HTML tidy PHP addon is required to generate HTML documentation.'));
    }
  }

  /**
   * Generates HTML documentation.
   *
   * @command webform:docs
   *
   * @usage webform:docs
   *   Generates HTML documentation used by the Webform module's documentation pages.
   *
   * @aliases wfd,webform-docs
   */
  public function docs() {
    $html_directory_path = __DIR__ . '/../../html';
    $images_directory_path = "$html_directory_path/images";

    // Create the /html directory.
    if (!file_exists($html_directory_path)) {
      $this->fileSystem->mkdir($html_directory_path);
    }
    if (!file_exists($images_directory_path)) {
      $this->fileSystem->mkdir($images_directory_path);
    }

    // Generate docs from WebformHelpManager.
    $help = [
      'videos' => $this->helpManager->buildVideos(TRUE),
      'addons' => $this->helpManager->buildAddOns(TRUE),
      'libraries' => $this->helpManager->buildLibraries(TRUE),
      'comparison' => $this->helpManager->buildComparison(TRUE),
    ];

    $index_html = '<h1>Webform Help</h1><ul>';
    foreach ($help as $help_name => $help_section) {
      $help_html = $this->renderer->renderPlain($help_section);
      $help_html = $this->tidyDocs($help_html);

      if ($help_name === 'videos') {
        // Download YouTube thumbnails so that they can be updated to
        // https://www.drupal.org/files/
        preg_match_all('#https://img.youtube.com/vi/([^/]+)/0.jpg#', $help_html, $matches);
        foreach ($matches[0] as $index => $image_uri) {
          $file_name = 'webform-youtube-' . $matches[1][$index] . '.jpg';
          copy($image_uri, "$images_directory_path/$file_name");
          $help_html = str_replace($image_uri, "https://www.drupal.org/files/$file_name", $help_html);
        }
      }

      file_put_contents("$html_directory_path/webform-$help_name.html", $help_html);
      $index_html .= "<li><a href=\"webform-$help_name.html\">webform-$help_name.html</a></li>";
    }
    $index_html .= '</ul>';
    file_put_contents("$html_directory_path/index.html", $this->tidyDocs($index_html));

    $this->output()->writeln("Documents generated to '/$html_directory_path'.");
  }

  /**
   * Tidy an HTML string.
   *
   * @param string $html
   *   HTML string to be tidied.
   *
   * @return string
   *   A tidied HTML string.
   */
  protected function tidyDocs($html) {
    // Configuration.
    // - http://us3.php.net/manual/en/book.tidy.php
    // - http://tidy.sourceforge.net/docs/quickref.html#wrap
    $config = ['show-body-only' => TRUE, 'wrap' => '10000'];

    $tidy = new \tidy();
    $tidy->parseString($html, $config, 'utf8');
    $tidy->cleanRepair();
    $html = tidy_get_output($tidy);

    // Convert URLs.
    $html = str_replace('"https://www.drupal.org/', '"/', $html);

    // Remove <code> tag nested within <pre> tag.
    $html = preg_replace('#<pre><code>\s*#', "<code>\n", $html);
    $html = preg_replace('#\s*</code></pre>#', "\n</code>", $html);

    // Fix code in webform-libraries.html.
    $html = str_replace(' &gt; ', ' > ', $html);

    // Remove space after <br> tags.
    $html = preg_replace('/(<br[^>]*>)\s+/', '\1', $html);

    // Convert <pre> to <code>.
    $html = preg_replace('#<hr>\s*<pre>([^<]+)</pre>\s+<hr>\s*<br>#s', '<p><code>\1</code></p>' . PHP_EOL, $html);

    // Append footer to HTML document.
    $html .= '<hr />' . PHP_EOL . '<p><em>This documentation was generated by the Webform module and <b>MUST</b> be updated using the `drush webform-docs` command.</em></p>';

    // Add play icon.
    $html = str_replace('>Watch video</a>', ' class="link-button">▶ Watch video</a>', $html);

    return $html;
  }

}
