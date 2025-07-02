<?php

namespace Drupal\webform\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Site\Settings;
use Drupal\webform\Utility\WebformYaml;
use Psr\Log\LogLevel;

/**
 * Webform utility commands for Drush 9.x and 10.x.
 */
class WebformUtilityCommands extends WebformCommandsBase {

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
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * WebformLibrariesCommands constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list service.
   */
  public function __construct(FileSystemInterface $file_system, ModuleHandlerInterface $module_handler, ModuleExtensionList $module_extension_list) {
    parent::__construct();
    $this->fileSystem = $file_system;
    $this->moduleHandler = $module_handler;
    $this->moduleExtensionList = $module_extension_list;
  }

  /* ************************************************************************ */
  // Tidy.
  /* ************************************************************************ */

  /**
   * Validate Tidy target.
   *
   * @hook validate webform:tidy
   */
  public function tidyValidate(CommandData $commandData) {
    global $config_directories;

    $arguments = $commandData->getArgsWithoutAppName();
    $target = $arguments['target'] ?? 'webform';

    if (empty(Settings::get('config_' . $target . '_directory', FALSE))
      && !(isset($config_directories) && isset($config_directories[$target]))
      && !($this->moduleHandler->moduleExists($target) && file_exists($this->moduleExtensionList->getPath($target) . '/config'))
      && !file_exists(realpath($target))) {
      $t_args = ['@target' => $target];
      throw new \Exception(dt("Unable to find '@target' module (config/install), config directory (sync), or path (/some/path/).", $t_args));
    }
  }

  /**
   * Tidy export webform configuration files.
   *
   * @param string $target
   *   The module (config/install), config directory (sync), or path
   *   (/some/path) that needs its YAML configuration files tidied.
   *   (Defaults to webform)
   * @param array $options
   *   (optional) An array of options.
   *
   * @command webform:tidy
   *
   * @option dependencies Add module dependencies to installed webform and options configuration entities.
   * @option prefix Prefix for file names to be tidied. (Defaults to webform)
   *
   * @usage drush webform:tidy webform
   *   Tidies YAML configuration files in 'webform/config' for the Webform module
   *
   * @aliases wft,webform-tidy
   */
  public function tidy($target = NULL, array $options = ['dependencies' => FALSE, 'prefix' => 'webform']) {
    global $config_directories;

    $target = $target ?: 'webform';
    $prefix = $options['prefix'];

    // [Drupal 8.8+] The sync directory is defined in $settings
    // and not $config_directories.
    // @see https://www.drupal.org/node/3018145
    $config_directory = Settings::get('config_' . $target . '_directory');
    if ($config_directory) {
      $file_directory_path = DRUPAL_ROOT . '/' . $config_directory;
      $dependencies = $options['dependencies'];
    }
    elseif (isset($config_directories) && isset($config_directories[$target])) {
      $file_directory_path = DRUPAL_ROOT . '/' . $config_directories[$target];
      $dependencies = $options['dependencies'];
    }
    elseif ($this->moduleHandler->moduleExists($target)) {
      $file_directory_path = $this->moduleExtensionList->getPath($target) . '/config';
      $dependencies = $options['dependencies'];
    }
    else {
      $file_directory_path = realpath($target);
      $dependencies = FALSE;
    }

    $files = $this->fileSystem->scanDirectory($file_directory_path, ($prefix) ? '/^' . preg_quote($prefix, '/.') . '.*\.yml$/' : '/.*\.yml$/');
    $this->output()->writeln(dt("Reviewing @count YAML configuration '@prefix.*' files in '@module'.", ['@count' => count($files), '@module' => $target, '@prefix' => $prefix]));

    $total = 0;
    foreach ($files as $file) {
      $original_yaml = file_get_contents($file->uri);
      $tidied_yaml = $original_yaml;

      try {
        $data = Yaml::decode($tidied_yaml);
      }
      catch (\Exception $exception) {
        $message = 'Error parsing: ' . $file->filename . PHP_EOL . $exception->getMessage();
        if (strlen($message) > 255) {
          $message = substr($message, 0, 255) . '…';
        }
        $this->logger()->log($message, LogLevel::ERROR);
        $this->output()->writeln($message);
        continue;
      }

      // Tidy elements.
      if (strpos($file->filename, 'webform.webform.') === 0 && isset($data['elements'])) {
        try {
          $elements = WebformYaml::tidy($data['elements']);
          $data['elements'] = $elements;
        }
        catch (\Exception $exception) {
          // Do nothing.
        }
      }

      // Add module dependency to exporter webform and webform options config entities.
      if ($dependencies && preg_match('/^(webform\.webform\.|webform\.webform_options\.)/', $file->filename)) {
        if (empty($data['dependencies']['enforced']['module']) || !in_array($target, $data['dependencies']['enforced']['module'])) {
          $this->output()->writeln(dt('Adding module dependency to @file…', ['@file' => $file->filename]));
          $data['dependencies']['enforced']['module'][] = $target;
        }
      }

      // Tidy and add new line to the end of the tidied file.
      $tidied_yaml = WebformYaml::encode($data) . PHP_EOL;
      if ($tidied_yaml !== $original_yaml) {
        $this->output()->writeln(dt('Tidying @file…', ['@file' => $file->filename]));
        file_put_contents($file->uri, $tidied_yaml);
        $total++;
      }
    }

    if ($total) {
      $this->output()->writeln(dt('@total YAML file(s) tidied.', ['@total' => $total]));
    }
    else {
      $this->output()->writeln(dt('No YAML files needed to be tidied.'));
    }
  }

}
