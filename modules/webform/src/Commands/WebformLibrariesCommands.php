<?php

namespace Drupal\webform\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\webform\Utility\WebformObjectHelper;
use Drupal\webform\WebformLibrariesManagerInterface;
use Drush\Drush;
use Drush\Exceptions\UserAbortException;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Webform libraries related commands for Drush 9.x and 10.x.
 */
class WebformLibrariesCommands extends WebformCommandsBase {

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The webform libraries manager.
   *
   * @var \Drupal\webform\WebformLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The path of composer.json.
   *
   * @var string
   */
  protected $composer_json;

  /**
   * The directory of composer.json.
   *
   * @var string
   */
  protected $composer_directory;

  /**
   * Constructs WebformLibrariesCommand.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The webform libraries manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ClientInterface $http_client, WebformLibrariesManagerInterface $libraries_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct();
    $this->httpClient = $http_client;
    $this->librariesManager = $libraries_manager;
    $this->moduleHandler = $module_handler;
  }

  /* ************************************************************************ */
  // Libraries status.
  /* ************************************************************************ */

  /**
   * Displays the status of third party libraries required by the Webform module.
   *
   * @command webform:libraries:status
   *
   * @usage webform:libraries:status
   *   Displays the status of third party libraries required by the Webform module.
   *
   * @aliases wfls,webform-libraries-status
   */
  public function librariesStatus() {
    $this->moduleHandler->loadInclude('webform', 'install');

    $requirements = $this->librariesManager->requirements();
    $description = $requirements['webform_libraries']['description'];
    $description = strip_tags($description, '<dt><dd><dl>');
    $description = MailFormatHelper::htmlToText($description);

    $this->output()->writeln($description);
  }

  /* ************************************************************************ */
  // Libraries composer.
  /* ************************************************************************ */

  /**
   * Generates the Webform module's composer.json with libraries as repositories.
   *
   * @command webform:libraries:composer
   *
   * @option disable-tls If set to true all HTTPS URLs will be tried with HTTP instead and no network level encryption is performed.
   *
   * @usage webform:libraries:composer
   *   Generates the Webform module's composer.json with libraries as repositories.
   *
   * @aliases wflc,webform-libraries-composer
   */
  public function librariesComposer(array $options = ['disable-tls' => FALSE]) {
    // Load existing composer.json file and unset certain properties.
    $composer_path = __DIR__ . '/../../composer.json';
    $json = file_get_contents($composer_path);
    $data = json_decode($json, FALSE, static::JSON_ENCODE_FLAGS);
    $data = (array) $data;
    unset($data['extra'], $data['require-dev']);
    $data = (object) $data;

    // Set disable tls.
    $this->setComposerDisableTls($data);

    // Set libraries.
    $data->repositories = (object) [];
    $data->require = (object) [];
    $this->setComposerLibraries($data->repositories, $data->require);
    // Remove _webform property.
    foreach ($data->repositories as &$repository) {
      unset($repository['_webform']);
    }
    $this->output()->writeln(json_encode($data, static::JSON_ENCODE_FLAGS));
  }

  /* ************************************************************************ */
  // Libraries download.
  /* ************************************************************************ */

  /**
   * Download third party libraries required by the Webform module.
   *
   * @command webform:libraries:download
   *
   * @usage webform:libraries:download
   *   Download third party libraries required by the Webform module.
   *
   * @aliases wfld,webform-libraries-download
   */
  public function librariesDownload() {
    // Remove all existing libraries (including excluded).
    if ($this->librariesRemove(FALSE)) {
      $this->output()->writeln(dt('Removing existing libraries…'));
    }

    $file_system = new Filesystem();
    $temp_dir = drush_tempdir();

    $libraries = $this->librariesManager->getLibraries(TRUE);
    foreach ($libraries as $library_name => $library) {
      // Skip libraries installed by other modules.
      if (!empty($library['module'])) {
        continue;
      }

      $download_location = DRUPAL_ROOT . "/libraries/$library_name";

      $download_url = $library['download_url']->toString();

      if (preg_match('/\.zip$/', $download_url)) {
        $download_type = 'zip';
      }
      elseif (preg_match('/\.tgz$/', $download_url)) {
        $download_type = 'tar';
      }
      else {
        $download_type = 'file';
      }

      // Download archive to temp directory.
      $this->output()->writeln("Downloading $download_url");

      if ($download_type === 'file') {
        $file_system->mkdir($download_location);
        $download_filepath = $download_location . '/' . basename($download_url);
        $this->downloadFile($download_url, $download_filepath);
      }
      else {
        $temp_filepath = $temp_dir . '/' . basename(current(explode('?', $download_url, 2)));
        $this->downloadFile($download_url, $temp_filepath);

        // Extract ZIP archive.
        $this->output()->writeln("Extracting to $download_location");

        // Extract to temp location.
        $temp_location = drush_tempdir();
        if (!$this->extractTarball($temp_filepath, $temp_location)) {
          throw new \Exception("Unable to extract $library_name");
        }

        // Move files and directories from temp location to download location.
        // using rename.
        $files = scandir($temp_location);
        // Remove directories (. ..)
        unset($files[0], $files[1]);
        if ((count($files) === 1) && is_dir($temp_location . '/' . current($files))) {
          $temp_location .= '/' . current($files);
        }
        $file_system->rename($temp_location, $download_location, TRUE);

        // Remove the tarball.
        if (file_exists($temp_filepath)) {
          $file_system->remove($temp_filepath);
        }
      }
    }

    drupal_flush_all_caches();
  }

  /**
   * Download a file.
   *
   * @param string $url
   *   File URL.
   * @param string $destination
   *   File destination.
   *
   * @return string
   *   File destination.
   */
  protected function downloadFile($url, $destination) {
    $destination_tmp = drush_tempnam('download_file');
    $this->httpClient->get($url, ['sink' => $destination_tmp]);
    if (!drush_file_not_empty($destination_tmp) && $file = @file_get_contents($url)) {
      @file_put_contents($destination_tmp, $file);
    }
    if (!drush_file_not_empty($destination_tmp)) {
      // Download failed.
      throw new \Exception(dt("The URL !url could not be downloaded.", ['!url' => $url]));
    }
    if ($destination) {
      $fs = new Filesystem();
      $fs->rename($destination_tmp, $destination, TRUE);
      return $destination;
    }
    return $destination_tmp;
  }

  /**
   * Extract tarball.
   *
   * @param string $path
   *   Tarball path.
   * @param string $destination
   *   Tarball destination.
   *
   * @return bool
   *   TRUE if extraction is successful.
   */
  protected function extractTarball($path, $destination) {
    $file_system = new Filesystem();
    $file_system->mkdir($destination);

    $cwd = getcwd();
    if (preg_match('/\.tgz$/', $path)) {
      drush_op('chdir', dirname($path));
      $process = Drush::process(['tar', '-xvzf', $path, '-C', $destination]);
      $process->run();
      $return = $process->isSuccessful();
      drush_op('chdir', $cwd);

      if (!$return) {
        throw new \Exception(dt('Unable to extract @filename to @destination.<br /><pre>@process_output</pre>', [
          '@filename' => $path,
          '@destination' => $destination,
          '@process_output' => print_r($process->getOutput(), TRUE),
        ]));
      }
    }
    else {
      drush_op('chdir', dirname($path));
      $process = Drush::process(['unzip', $path, '-d', $destination]);
      $process->run();
      $return = $process->isSuccessful();
      drush_op('chdir', $cwd);

      if (!$return) {
        throw new \Exception(dt('Unable to extract @filename to @destination.<br /><pre>@process_output</pre>', [
          '@filename' => $path,
          '@destination' => $destination,
          '@process_output' => print_r($process->getOutput(), TRUE),
        ]));
      }
    }
    return $return;
  }

  /* ************************************************************************ */
  // :ibraries remove.
  /* ************************************************************************ */

  /**
   * Removes all downloaded third party libraries required by the Webform module.
   *
   * @command webform:libraries:remove
   *
   * @usage webform:libraries:remove
   *   Removes all downloaded third party libraries required by the Webform module.
   *
   * @aliases wflr,webform-libraries-remove
   */
  public function librariesRemove($status = NULL) {
    $status = ($status !== FALSE);
    if ($status) {
      $this->output()->writeln(dt('Beginning to remove libraries…'));
    }
    $removed = FALSE;

    $file_system = new Filesystem();

    $libraries = $this->librariesManager->getLibraries();
    // Manually add deleted libraries, so that they will always be removed.
    $libraries['jquery.word-and-character-counter'] = 'jquery.word-and-character-counter';
    foreach ($libraries as $library_name => $library) {
      $library_path = '/libraries/' . $library_name;
      $library_exists = (file_exists(DRUPAL_ROOT . $library_path)) ? TRUE : FALSE;
      if ($library_exists) {
        $file_system->remove(DRUPAL_ROOT . $library_path);
        $removed = TRUE;
        if ($status) {
          $t_args = [
            '@name' => $library_name,
            '@path' => $library_path,
          ];
          $this->output()->writeln(dt('@name removed from @path…', $t_args));
        }
      }
    }

    if ($removed) {
      drupal_flush_all_caches();
    }
    return $removed;
  }

  /* ************************************************************************ */
  // Composer update.
  /* ************************************************************************ */

  /**
   * Confirms user wants to execute this command.
   *
   * @hook validate webform:composer:update
   */
  public function composerUpdateValidate(CommandData $commandData) {
    $msg = dt('THIS IS AN EXPERIMENTAL DRUSH COMMAND.') . PHP_EOL .
      dt('PLEASE MAKE SURE TO BACKUP YOUR COMPOSER.JSON FILE.') . PHP_EOL .
      dt("Are you sure you want update your Drupal installation's composer.json file?");
    if (!$this->io()->confirm($msg)) {
      throw new UserAbortException();
    }

    $drupal_root = Drush::bootstrapManager()->getRoot();
    if (file_exists($drupal_root . '/composer.json')) {
      $composer_json = $drupal_root . '/composer.json';
      $composer_directory = '';
    }
    elseif (file_exists(dirname($drupal_root) . '/composer.json')) {
      // The "Composer template for Drupal projects" install Drupal in /web'.
      // @see https://github.com/drupal-composer/drupal-project/blob/8.x/composer.json
      $composer_json = dirname($drupal_root) . '/composer.json';
      $composer_directory = basename($drupal_root) . '/';
    }
    else {
      throw new \Exception(dt('Unable to locate composer.json'));
    }

    $this->composer_json = $composer_json;
    $this->composer_directory = $composer_directory;
  }

  /**
   * Updates the Drupal installation's composer.json to include the Webform module's selected libraries as repositories.
   *
   * @command webform:composer:update
   *
   * @option disable-tls If set to true all HTTPS URLs will be tried with HTTP instead and no network level encryption is performed.
   *
   * @usage webform:composer:update
   *   Updates the Drupal installation's composer.json to include the Webform
   *   module's selected libraries as repositories.
   *
   * @aliases wfcu,webform-composer-update
   */
  public function composerUpdate(array $options = ['disable-tls' => FALSE]) {
    $composer_json = $this->composer_json;
    $composer_directory = $this->composer_directory;

    $json = file_get_contents($composer_json);
    $data = json_decode($json, FALSE, static::JSON_ENCODE_FLAGS);
    if (!isset($data->repositories)) {
      $data->repositories = (object) [];
    }
    if (!isset($data->require)) {
      $data->require = (object) [];
    }

    // Add drupal-library to installer paths.
    if (strpos($json, 'type:drupal-library') === FALSE) {
      $library_path = $composer_directory . 'libraries/{$name}';
      $data->extra->{'installer-paths'}->{$library_path}[] = 'type:drupal-library';
    }

    // Get repositories and require.
    $repositories = &$data->repositories;
    $require = &$data->require;

    // Remove all existing _webform repositories.
    foreach ($repositories as $repository_name => $repository) {
      if (!empty($repository->_webform)) {
        $package_name = $repositories->{$repository_name}->package->name;
        unset($repositories->{$repository_name}, $require->{$package_name});
      }
    }

    // Set disable tls.
    $this->setComposerDisableTls($data);

    // Set libraries.
    $this->setComposerLibraries($repositories, $require);

    file_put_contents($composer_json, json_encode($data, static::JSON_ENCODE_FLAGS));

    $this->output()->writeln("$composer_json updated.");
    $this->output()->writeln('Make sure to run `composer update --lock`.');
  }

  /**
   * Set composer disable tls.
   *
   * This is needed when CKEditor's HTTPS server's SSL is not working properly.
   *
   * @param object $data
   *   Composer JSON data.
   */
  protected function setComposerDisableTls(&$data) {
    // Remove disable-tls config.
    if (isset($data->config) && isset($data->config->{'disable-tls'})) {
      unset($data->config->{'disable-tls'});
    }
    if ($this->input()->getOption('disable-tls')) {
      $data->config->{'disable-tls'} = TRUE;
    }
  }

  /* ************************************************************************ */
  // Composer helpers methods.
  /* ************************************************************************ */

  /**
   * Set composer libraries.
   *
   * @param object $repositories
   *   Composer repositories.
   * @param object $require
   *   Composer require.
   */
  protected function setComposerLibraries(&$repositories, &$require) {
    $libraries = $this->librariesManager->getLibraries(TRUE);
    foreach ($libraries as $library_name => $library) {
      // Never overwrite existing repositories.
      if (isset($repositories->{$library_name})) {
        continue;
      }

      // Skip libraries installed by other modules.
      if (!empty($library['module'])) {
        continue;
      }

      $dist_url = $library['download_url']->toString();

      if (preg_match('/\.zip$/', $dist_url)) {
        $dist_type = 'zip';
      }
      elseif (preg_match('/\.tgz$/', $dist_url)) {
        $dist_type = 'tar';
      }
      else {
        $dist_type = 'file';
      }

      $package_version = $library['version'];

      if (strpos($library_name, '/') !== FALSE) {
        $package_name = $library_name;
      }
      elseif (strpos($library_name, '.') !== FALSE) {
        $package_name = str_replace('.', '/', $library_name);
      }
      else {
        $package_name = "$library_name/$library_name";
      }

      $repositories->$library_name = [
        '_webform' => TRUE,
        'type' => 'package',
        'package' => [
          'name' => $package_name,
          'version' => $package_version,
          'type' => 'drupal-library',
          'extra' => [
            'installer-name' => $library_name,
          ],
          'dist' => [
            'url' => $dist_url,
            'type' => $dist_type,
          ],
          'license' => $library['license'] ?: 'N/A',
        ],
      ];

      $require->$package_name = '*';
    }
    $repositories = WebformObjectHelper::sortByProperty($repositories);
    $require = WebformObjectHelper::sortByProperty($require);
  }

}
