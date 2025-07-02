<?php

declare(strict_types=1);

namespace Drupal\imagemagick;

/**
 * Provides an interface for ImageMagick execution managers.
 */
interface ImagemagickExecManagerInterface {

  /**
   * Returns the format mapper.
   *
   * @return \Drupal\imagemagick\ImagemagickFormatMapperInterface
   *   The format mapper service.
   */
  public function getFormatMapper(): ImagemagickFormatMapperInterface;

  /**
   * Sets the execution timeout (max. runtime).
   *
   * To disable the timeout, set this value to null.
   *
   * @param int $timeout
   *   The timeout in seconds.
   *
   * @return $this
   */
  public function setTimeout(int $timeout): static;

  /**
   * Gets the binaries package in use.
   *
   * @param string $package
   *   (optional) Force the graphics package suite.
   *
   * @return \Drupal\imagemagick\PackageSuite
   *   The package suite.
   */
  public function getPackageSuite(?string $package = NULL): PackageSuite;

  /**
   * Gets the version of the package in use.
   *
   * @param \Drupal\imagemagick\PackageSuite|null $packageSuite
   *   (optional) Force the graphics package suite.
   *
   * @return string
   *   The version of the package suite.
   */
  public function getPackageSuiteVersion(?PackageSuite $packageSuite = NULL): string;

  /**
   * Verifies file path of the executable binary by checking its version.
   *
   * @param string $path
   *   The user-submitted file path to the convert binary.
   * @param ?PackageSuite $package
   *   (optional) The graphics package to use.
   * @param ?string $packageSuiteVersion
   *   (optional) The graphics package version.
   *
   * @return array
   *   An associative array containing:
   *   - output: The shell output of 'convert -version', if any.
   *   - errors: A list of error messages indicating if the executable could
   *     not be found or executed.
   */
  public function checkPath(string $path, ?PackageSuite $package = NULL, ?string $packageSuiteVersion = NULL): array;

  /**
   * Executes the convert executable as shell command.
   *
   * @param \Drupal\imagemagick\PackageCommand $command
   *   The executable to run.
   * @param \Drupal\imagemagick\ImagemagickExecArguments $arguments
   *   An ImageMagick execution arguments object.
   * @param string &$output
   *   A variable to assign the shell STDOUT to, passed by reference.
   * @param string &$error
   *   A variable to assign the shell STDERR to, passed by reference.
   * @param string $path
   *   (optional) A custom file path to the executable binary.
   *
   * @return bool
   *   TRUE if the command succeeded, FALSE otherwise. The error exit status
   *   code integer returned by the executable is logged.
   */
  public function execute(PackageCommand $command, ImagemagickExecArguments $arguments, string &$output, string &$error, ?string $path = NULL): bool;

  /**
   * Executes a command on the operating system, via Symfony Process.
   *
   * @param string[] $command
   *   The command to run and its arguments listed as separate entries.
   * @param string $id
   *   An identifier for the process to be spawned on the operating system.
   * @param string &$output
   *   A variable to assign the shell STDOUT to, passed by reference.
   * @param string &$error
   *   A variable to assign the shell STDERR to, passed by reference.
   */
  public function runProcess(array $command, string $id, string &$output, string &$error): int|bool;

}
