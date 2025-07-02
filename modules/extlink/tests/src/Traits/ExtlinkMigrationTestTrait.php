<?php

namespace Drupal\Tests\extlink\Traits;

use Drupal\Core\Database\Database;

/**
 * Functions common to migration tests.
 */
trait ExtlinkMigrationTestTrait {

  /**
   * Set up a D6 variable to be migrated.
   *
   * @param string $name
   *   The name of the variable to be set.
   * @param mixed $value
   *   The value of the variable to be set.
   */
  protected function setUpD6D7Variable(string $name, mixed $value): void {
    $this->assertIsString($name, 'Name must be a string');

    Database::getConnection('default', 'migrate')
      ->upsert('variable')
      ->key('name')
      ->fields(['name', 'value'])
      ->values([
        'name' => $name,
        'value' => serialize($value),
      ])
      ->execute();
  }

  /**
   * Return a random boolean value.
   *
   * @return bool
   *   A random boolean value.
   */
  protected function randomBoolean(): bool {
    return boolval(mt_rand(0, 1));
  }

  /**
   * Returns a random regular expression.
   *
   * The regular expression is essentially just a random string that has been
   * regex-escaped.
   *
   * @param string $delimiter
   *   The regex delimiter to use.
   * @param int $length
   *   The length of the regular expression, excluding the delimiter. Note the
   *   result will be at least 2 characters longer than this number (i.e.:
   *   because it has the delimiter at the start and end); possibly more if any
   *   regular-expression control characters appear in the string (i.e.: because
   *   those will be escaped).
   *
   * @return string
   *   A random regular expression.
   */
  protected function randomRegex(string $delimiter = '/', int $length = 8): string {
    return $delimiter . preg_quote($this->randomString($length), $delimiter) . $delimiter;
  }

  /**
   * Return a space-separated list of random words.
   *
   * @param int $wordCount
   *   The number of words to return.
   * @param int $wordMinLength
   *   The minimum length of words.
   * @param int $wordMaxLength
   *   The maximum length of words.
   *
   * @return string
   *   A space-separated list of words.
   */
  protected function randomSpaceSeparatedWords(int $wordCount = 5, int $wordMinLength = 2, int $wordMaxLength = 10): string {
    $answerArray = [];

    for ($i = 0; $i <= $wordCount; $i++) {
      $answerArray[] = $this->getRandomGenerator()->word(mt_rand($wordMinLength, $wordMaxLength));
    }

    return implode(' ', $answerArray);
  }

}
