<?php

namespace Drupal\chosen_lib\Commands;

use Drupal\Core\File\FileSystemInterface;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Psr\Log\LogLevel;

/**
 * The Chosen plugin URI.
 */
define('CHOSEN_DOWNLOAD_URI', 'https://github.com/JJJ/chosen/archive/refs/tags/2.2.1.zip');

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class ChosenLibCommands extends DrushCommands {

  /**
   * {@inheritdoc}
   */
  public function __construct(private FileSystemInterface $fileSystem) {
    parent::__construct();
  }

  /**
   * Download and install the Chosen plugin.
   *
   * @param string $path
   *   Optional. A path where to install the Chosen plugin. If omitted Drush
   *   will use the default location.
   *
   * @command chosen:plugin
   * @aliases chosenplugin,chosen-plugin
   *
   * @throws \Exception
   */
  public function plugin($path = '') {
    if (empty($path)) {
      $path = 'libraries';
    }

    // Create the path if it does not exist.
    if (!is_dir($path)) {
      drush_op('mkdir', $path);
      $this->drush_log(dt('Directory @path was created', ['@path' => $path]), 'notice');
    }

    // Set the directory to the download location.
    $olddir = getcwd();
    chdir($path);

    // Download the zip archive.
    if ($filepath = $this->drush_download_file(CHOSEN_DOWNLOAD_URI)) {
      $filename = basename($filepath);
      $dirname = basename($filepath, '.zip');

      // Remove any existing Chosen plugin directory.
      if (is_dir('chosen')) {
        $fileservice = $this->fileSystem;
        $fileservice->deleteRecursive('chosen');

        $this->drush_log(dt('A existing Chosen plugin was deleted from @path', ['@path' => $path]), 'notice');
      }

      // Decompress the zip archive.
      $this->drush_tarball_extract($filename, $dirname);

      // Change the directory name to "chosen" if needed.
      if ('chosen' !== $dirname) {
        $subdirname = $dirname . '/chosen-' . $dirname;
        if (is_dir($subdirname)) {
          $this->drush_move_dir($subdirname, 'chosen');
          $fileservice = $this->fileSystem;
          $fileservice->deleteRecursive($dirname);
        } else {
          $this->drush_move_dir($dirname, 'chosen');
        }
        $dirname = 'chosen';
      }

      unlink($filename);
    }

    if (is_dir($dirname)) {
      $this->drush_log(dt('Chosen plugin has been installed in @path', ['@path' => $path]), 'success');
    }
    else {
      $this->drush_log(dt('Drush was unable to install the Chosen plugin to @path', ['@path' => $path]), 'error');
    }

    // Set working directory back to the previous working directory.
    chdir($olddir);
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param string $message
   *   The log message.
   * @param mixed $type
   *   The log type.
   */
  public function drush_log($message, $type = LogLevel::INFO) {
    $this->logger()->log($type, $message);
  }

  /**
   * @param string $url
   *   The download url.
   * @param mixed $destination
   *   The destination path.
   * @return bool|string
   *   The destination file.
   * @throws \Exception
   */
  public function drush_download_file($url, $destination = FALSE) {
    // Generate destination if omitted.
    if (!$destination) {
      $file = basename(current(explode('?', $url, 2)));
      $destination = getcwd() . '/' . basename($file);
    }

    // Copied from: \Drush\Commands\SyncViaHttpCommands::downloadFile
    static $use_wget;
    if ($use_wget === NULL) {
      $process = Drush::process(['which', 'wget']);
      $process->run();
      $use_wget = $process->isSuccessful();
    }

    $destination_tmp = drush_tempnam('download_file');
    if ($use_wget) {
      $args = ['wget', '-q', '--timeout=30', '-O', $destination_tmp, $url];
    }
    else {
      $args = ['curl', '-s', '-L', '--connect-timeout', '30', '-o', $destination_tmp, $url];
    }
    $process = Drush::process($args);
    $process->mustRun();

    if (!drush_file_not_empty($destination_tmp) && $file = @file_get_contents($url)) {
      @file_put_contents($destination_tmp, $file);
    }
    if (!drush_file_not_empty($destination_tmp)) {
      // Download failed.
      throw new \Exception(dt("The URL !url could not be downloaded.", ['!url' => $url]));
    }
    if ($destination) {
      $fileservice = $this->fileSystem;
      $fileservice->move($destination_tmp, $destination, TRUE);
      return $destination;
    }
    return $destination_tmp;
  }

  /**
   * @param string $src
   *   The origin filename or directory.
   * @param string $dest
   *   The new filename or directory.
   * @return bool
   */
  public function drush_move_dir($src, $dest) {
    $fileservice = $this->fileSystem;
    $fileservice->move($src, $dest, TRUE);
    return TRUE;
  }

  /**
   * @param string $path
   *   The make directory path.
   * @return bool
   */
  public function drush_mkdir($path) {
    $fileservice = $this->fileSystem;
    $fileservice->mkdir($path);
    return TRUE;
  }

  /**
   * @param string $path
   *   The filename or directory.
   * @param bool $destination
   *   The destination path.
   * @return mixed
   * @throws \Exception
   */
  public function drush_tarball_extract($path, $destination = FALSE) {
    $this->drush_mkdir($destination);
    $cwd = getcwd();
    if (preg_match('/\.tgz$/', $path)) {
      drush_op('chdir', dirname($path));
      $process = Drush::process(['tar', '-xvzf', $path, '-C', $destination]);
      $process->run();
      $return = $process->isSuccessful();
      drush_op('chdir', $cwd);

      if (!$return) {
        throw new \Exception(dt('Unable to extract !filename.' . PHP_EOL . $process->getOutput(), ['!filename' => $path]));
      }
    }
    else {
      drush_op('chdir', dirname($path));
      $process = Drush::process(['unzip', $path, '-d', $destination]);
      $process->run();
      $return = $process->isSuccessful();
      drush_op('chdir', $cwd);

      if (!$return) {
        throw new \Exception(dt('Unable to extract !filename.' . PHP_EOL . $process->getOutput(), ['!filename' => $path]));
      }
    }

    return $return;
  }

}
