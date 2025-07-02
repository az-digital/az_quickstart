<?php

namespace Drupal\migmag_predictable_uuid;

use Drupal\Component\Uuid\Php as DefaultGenerator;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;

// cspell:ignore fqcns

/**
 * A predictable UUID generator.
 */
class PredictableUuid extends DefaultGenerator {

  /**
   * Key of the state storing how many times a predictable UUID was generated.
   *
   * @const string
   */
  const LAST_SUFFIX_STATE_KEY = 'pathauto_test_uuid_generator.last';

  /**
   * Key of the state where the watches classes are stored.
   *
   * @const string
   */
  const WATCHED_CLASSES_STATE_KEY = 'pathauto_test_uuid_generator.watch';

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a UuidTestGenerator instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(StateInterface $state, FileSystemInterface $file_system) {
    $this->state = $state;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function generate() {
    if (empty($watch = $this->state->get(self::WATCHED_CLASSES_STATE_KEY, []))) {
      return parent::generate();
    }

    $trace_files = array_reduce(
      debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
      function (array $carry, array $trace) {
        // $trace['file'] might be empty if we have closures.
        if (!empty($trace['file'])) {
          $carry[] = $trace['file'];
        }
        return $carry;
      },
      []
    );

    foreach ($watch as $prefix => $watched_files_and_fqcns) {
      foreach ((array) $watched_files_and_fqcns as $watched_file_or_fqcn) {
        $reflection_class = NULL;
        $file = file_exists($watched_file_or_fqcn)
          ? $watched_file_or_fqcn
          : NULL;
        if (
          $file &&
          strpos($watched_file_or_fqcn, DRUPAL_ROOT) !== 0
        ) {
          $file = $this->fileSystem->realpath($watched_file_or_fqcn);
        }

        if ($file && !file_exists($file)) {
          continue;
        }

        if (!$file) {
          try {
            $reflection_class = new \ReflectionClass($watched_file_or_fqcn);
            $file = $reflection_class->getFileName();
          }
          catch (\ReflectionException $e) {
            continue;
          }
        }

        if (in_array($file, $trace_files, TRUE)) {
          return $this->generateFromTemplate($prefix);
        }
      }
    }

    return parent::generate();
  }

  /**
   * Generates a UUID with the given prefix or UUID template.
   *
   * UUID templates should end with 12 zeros and must look like a valid v4 UUID.
   *
   * @param string $template_or_prefix
   *   The uuid template or string to use.
   *
   * @return string
   *   A generated, predictable UUID.
   */
  protected function generateFromTemplate(string $template_or_prefix): string {
    $current = ($current = (int) $this->state->get(self::LAST_SUFFIX_STATE_KEY . '.' . $template_or_prefix, 0)) >= PHP_INT_MAX
      ? 0
      : $current + 1;
    $this->state->set(self::LAST_SUFFIX_STATE_KEY . '.' . $template_or_prefix, (int) $current);

    if (self::validateUuidMask($template_or_prefix)) {
      $hex_current = dechex($current);
      return substr($template_or_prefix, 0, strlen($hex_current) * -1) . (string) $hex_current;
    }

    return $template_or_prefix . $current;
  }

  /**
   * Checks whether the given string is a UUID template.
   *
   * UUID templates should end with 12 zeros and must look like a valid v4 UUID,
   * e.g '01234567-89ab-4cde-f012-00000000000' or
   * 'aaaaaaaa-bbbb-4ccc-dddd-000000000000' are valid templates, but 'foo',
   * 'aaaaaaa-bbb-cccc-dddd-00000000' or 'foo0bar0-baz0-foo0-bar0-baz0foo0bar'
   * aren't.
   *
   * @param string $template_or_prefix
   *   The string to check.
   *
   * @return bool
   *   Whether the given string is a UUID template.
   */
  protected static function validateUuidMask(string $template_or_prefix): bool {
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[0-9a-f]{4}-0{12}$/', $template_or_prefix);
  }

}
