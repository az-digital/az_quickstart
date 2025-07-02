<?php

declare(strict_types=1);

namespace Drupal\imagemagick;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

/**
 * Manage execution of ImageMagick/GraphicsMagick commands.
 */
class ImagemagickExecManager implements ImagemagickExecManagerInterface {

  use StringTranslationTrait;

  /**
   * Whether we are running on Windows OS.
   */
  protected bool $isWindows;

  /**
   * The execution timeout.
   */
  protected int $timeout = 60;

  /**
   * Constructs an ImagemagickExecManager object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param string $appRoot
   *   The app root.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\imagemagick\ImagemagickFormatMapperInterface $formatMapper
   *   The format mapper service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    #[Autowire(service: 'logger.channel.image')]
    protected readonly LoggerInterface $logger,
    protected readonly ConfigFactoryInterface $configFactory,
    #[Autowire(param: 'app.root')]
    protected readonly string $appRoot,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ImagemagickFormatMapperInterface $formatMapper,
    protected readonly MessengerInterface $messenger,
  ) {
    $this->isWindows = substr(PHP_OS, 0, 3) === 'WIN';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatMapper(): ImagemagickFormatMapperInterface {
    return $this->formatMapper;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeout(int $timeout): static {
    $this->timeout = $timeout;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageSuite(?string $package = NULL): PackageSuite {
    if ($package === NULL) {
      $package = $this->configFactory->get('imagemagick.settings')->get('binaries');
    }
    return PackageSuite::from($package);
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageSuiteVersion(?PackageSuite $packageSuite = NULL): string {
    $packageSuite = $packageSuite ?: $this->getPackageSuite();
    return match ($packageSuite) {
      PackageSuite::Imagemagick => $this->configFactory->get('imagemagick.settings')->get('imagemagick_version') ?? 'v6',
      PackageSuite::Graphicsmagick => 'v1',
    };
  }

  /**
   * {@inheritdoc}
   */
  public function checkPath(string $path, ?PackageSuite $packageSuite = NULL, ?string $packageSuiteVersion = NULL): array {
    $status = [
      'output' => '',
      'errors' => [],
    ];

    // Execute gm, convert or magick based on settings.
    $packageSuite ??= $this->getPackageSuite();
    $packageSuiteVersion ??= $this->getPackageSuiteVersion($packageSuite);
    $binary = match ($packageSuite) {
      PackageSuite::Imagemagick => match ($packageSuiteVersion) {
        'v7' => 'magick',
        default => 'convert',
      },
      PackageSuite::Graphicsmagick => 'gm',
    };
    $executable = $this->getExecutable($binary, $path);

    // If a path is given, we check whether the binary exists and can be
    // invoked.
    if (!empty($path)) {
      // Check whether the given file exists.
      if (!is_file($executable)) {
        $status['errors'][] = $this->t('The @suite executable %file does not exist.', [
          '@suite' => $packageSuite->label(),
          '%file' => $executable,
        ]);
      }
      // If it exists, check whether we can execute it.
      elseif (!is_executable($executable)) {
        $status['errors'][] = $this->t('The @suite file %file is not executable.', [
          '@suite' => $packageSuite->label(),
          '%file' => $executable,
        ]);
      }
    }

    // In case of errors, check for open_basedir restrictions.
    if ($status['errors'] && ($open_basedir = ini_get('open_basedir'))) {
      $status['errors'][] = $this->t('The PHP <a href=":php-url">open_basedir</a> security restriction is set to %open-basedir, which may prevent to locate the @suite executable.', [
        '@suite' => $packageSuite->label(),
        '%open-basedir' => $open_basedir,
        ':php-url' => 'http://php.net/manual/en/ini.core.php#ini.open-basedir',
      ]);
    }

    // Unless we had errors so far, try to invoke convert.
    if (!$status['errors']) {
      $error = '';
      $returnCode = $this->runProcess([$executable, '-version'], $packageSuite->value, $status['output'], $error);
      if (empty($error) && $returnCode === 127) {
        $error = $executable . ': command not found.';
      }
      if ($error !== '') {
        $status['errors'][] = $error;
      }
    }

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(PackageCommand $command, ImagemagickExecArguments $arguments, string &$output, string &$error, ?string $path = NULL): bool {
    $packageSuite = $this->getPackageSuite();

    $cmdline = [];

    $binary = match ($packageSuite) {
      PackageSuite::Imagemagick => match ($this->getPackageSuiteVersion(PackageSuite::Imagemagick)) {
        'v7' => 'magick',
        default => $command->value,
      },
      PackageSuite::Graphicsmagick => 'gm',
    };

    $cmd = $this->getExecutable($binary, $path);
    $cmdline[] = $cmd;

    if ($source_path = $arguments->getSourceLocalPath()) {
      if (($source_frames = $arguments->getSourceFrames()) !== NULL) {
        $source_path .= $source_frames;
      }
    }

    if ($destination_path = $arguments->getDestinationLocalPath()) {
      // If the format of the derivative image has to be changed, concatenate
      // the new image format and the destination path, delimited by a colon.
      // @see http://www.imagemagick.org/script/command-line-processing.php#output
      if (($format = $arguments->getDestinationFormat()) !== '') {
        $destination_path = $format . ':' . $destination_path;
      }
    }

    if ($command === PackageCommand::Identify) {
      // ImageMagick v6 syntax: identify [arguments] source.
      // ImageMagick v7 syntax: magick identify [arguments] source.
      // GraphicsMagick syntax: gm identify [arguments] source.
      if ($binary !== 'identify') {
        $cmdline[] = 'identify';
      }
      array_push($cmdline, ...$arguments->toArray(ArgumentMode::PreSource));
      $cmdline[] = $source_path;
    }
    elseif ($command === PackageCommand::Convert) {
      $args = match ($packageSuite) {
        PackageSuite::Imagemagick => $this->buildImagemagickConvertCommand($arguments, $source_path, $destination_path),
        PackageSuite::Graphicsmagick => $this->buildGraphicsmagickConvertCommand($arguments, $source_path, $destination_path),
      };
      array_push($cmdline, ...$args);
    }

    $return_code = $this->runProcess($cmdline, $packageSuite->value, $output, $error);

    if ($return_code !== FALSE) {
      // If the executable returned a non-zero code, log to the watchdog.
      if ($return_code != 0) {
        if ($error === '') {
          // If there is no error message, and allowed in config, log a
          // warning.
          if ($this->configFactory->get('imagemagick.settings')->get('log_warnings') === TRUE) {
            $this->logger->warning("@suite returned with code @code [command: @command @cmdline]", [
              '@suite' => $this->getPackageSuite()->label(),
              '@code' => $return_code,
              '@command' => $cmd,
              '@cmdline' => '[' . implode('] [', $cmdline) . ']',
            ]);
          }
        }
        else {
          // Log $error with context information.
          $this->logger->error("@suite error @code: @error [command: @command @cmdline]", [
            '@suite' => $this->getPackageSuite()->label(),
            '@code' => $return_code,
            '@error' => $error,
            '@command' => $cmd,
            '@cmdline' => '[' . implode('] [', $cmdline) . ']',
          ]);
        }
        // Executable exited with an error code, return FALSE.
        return FALSE;
      }

      // The shell command was executed successfully.
      return TRUE;
    }
    // The shell command could not be executed.
    return FALSE;
  }

  /**
   * Builds a convert command for Imagemagick.
   *
   * ImageMagick v6 syntax: convert input [arguments] output.
   * ImageMagick v7 syntax: magick convert input [arguments] output.
   *
   * @param \Drupal\imagemagick\ImagemagickExecArguments $arguments
   *   An ImageMagick execution arguments object.
   * @param string $sourcePath
   *   The source image file path.
   * @param string $destinationPath
   *   The destination image file path.
   *
   * @return string[]
   *   The command to be executed.
   *
   * @see http://www.imagemagick.org/Usage/basics/#cmdline
   */
  private function buildImagemagickConvertCommand(ImagemagickExecArguments $arguments, string $sourcePath, string $destinationPath): array {
    $cmdline = match ($this->getPackageSuiteVersion(PackageSuite::Imagemagick)) {
      'v7' => ['convert'],
      default => [],
    };
    if (($pre = $arguments->toArray(ArgumentMode::PreSource)) !== []) {
      array_push($cmdline, ...$pre);
    }
    if ($sourcePath) {
      $cmdline[] = $sourcePath;
    }
    array_push($cmdline, ...$arguments->toArray(ArgumentMode::PostSource));
    $cmdline[] = $destinationPath;
    return $cmdline;
  }

  /**
   * Builds a convert command for Graphicsmagick.
   *
   * GraphicsMagick syntax: gm convert [arguments] input output.
   *
   * @param \Drupal\imagemagick\ImagemagickExecArguments $arguments
   *   An ImageMagick execution arguments object.
   * @param string $sourcePath
   *   The source image file path.
   * @param string $destinationPath
   *   The destination image file path.
   *
   * @return string[]
   *   The command to be executed.
   *
   * @see http://www.graphicsmagick.org/GraphicsMagick.html
   */
  private function buildGraphicsmagickConvertCommand(ImagemagickExecArguments $arguments, string $sourcePath, string $destinationPath): array {
    $cmdline = ['convert'];
    if (($pre = $arguments->toArray(ArgumentMode::PreSource)) !== []) {
      array_push($cmdline, ...$pre);
    }
    array_push($cmdline, ...$arguments->toArray(ArgumentMode::PostSource));
    if ($sourcePath) {
      $cmdline[] = $sourcePath;
    }
    $cmdline[] = $destinationPath;
    return $cmdline;
  }

  /**
   * {@inheritdoc}
   */
  public function runProcess(array $command, string $id, string &$output, string &$error): int|bool {
    $command_line = '[' . implode('] [', $command) . ']';
    $output = '';
    $error = '';

    Timer::start('imagemagick:runOsShell');
    $process = new Process($command, $this->appRoot);
    $process->setTimeout($this->timeout);
    try {
      $process->run();
      $output = $process->getOutput();
      $error = $process->getErrorOutput();
      $return_code = $process->getExitCode();
    }
    catch (\Exception $e) {
      $error = $e->getMessage();
      $return_code = $process->getExitCode() ? $process->getExitCode() : 1;
    }
    $execution_time = Timer::stop('imagemagick:runOsShell')['time'];

    // Process debugging information if required.
    if ($this->configFactory->get('imagemagick.settings')->get('debug')) {
      $packageSuite = PackageSuite::tryFrom($id);
      $this->debugMessage('@suite command: <pre>@raw</pre> executed in @execution_timems', [
        '@suite' => $packageSuite ? $packageSuite->label() : $id,
        '@raw' => $command_line,
        '@execution_time' => $execution_time,
      ]);
      if ($output !== '') {
        $this->debugMessage('@suite output: <pre>@raw</pre>', [
          '@suite' => $packageSuite ? $packageSuite->label() : $id,
          '@raw' => print_r($output, TRUE),
        ]);
      }
      if ($error !== '') {
        $this->debugMessage('@suite error @return_code: <pre>@raw</pre>', [
          '@suite' => $packageSuite ? $packageSuite->label() : $id,
          '@return_code' => $return_code,
          '@raw' => print_r($error, TRUE),
        ]);
      }
    }

    return $return_code;
  }

  /**
   * Logs a debug message, and shows it on the screen for authorized users.
   *
   * @param string $message
   *   The debug message.
   * @param array{'@suite': string|\Drupal\Core\StringTranslation\TranslatableMarkup, '@raw': string, '@return_code'?: int, '@execution_time'?: array} $context
   *   Context information.
   */
  public function debugMessage(string $message, array $context): void {
    $this->logger->debug($message, $context);
    if ($this->currentUser->hasPermission('administer site configuration')) {
      // Strips raw text longer than 10 lines to optimize displaying.
      $raw = explode("\n", $context['@raw']);
      if (count($raw) > 10) {
        $tmp = [];
        for ($i = 0; $i < 9; $i++) {
          $tmp[] = $raw[$i];
        }
        $tmp[] = (string) $this->t('[Further text stripped. The watchdog log has the full text.]');
        $context['@raw'] = implode("\n", $tmp);
      }
      // @codingStandardsIgnoreLine
      $this->messenger->addMessage($this->t($message, $context), 'status', TRUE);
    }
  }

  /**
   * Returns the full path to the executable.
   *
   * @param string $binary
   *   The program to execute, typically 'convert', 'identify' or 'gm'.
   * @param string $path
   *   (optional) A custom path to the folder of the executable. When left
   *   empty, the setting imagemagick.settings.path_to_binaries is taken.
   *
   * @return string
   *   The full path to the executable.
   */
  protected function getExecutable(string $binary, ?string $path = NULL): string {
    // $path is only passed from the validation of the image toolkit form, on
    // which the path to convert is configured. @see ::checkPath()
    if (!isset($path)) {
      $path = $this->configFactory->get('imagemagick.settings')->get('path_to_binaries');
    }

    $executable = $binary;
    if ($this->isWindows) {
      $executable .= '.exe';
    }

    return $path . $executable;
  }

}
