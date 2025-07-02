<?php

declare(strict_types=1);

namespace Drupal\migmag\Utility;

/**
 * Utility for array manipulation.
 */
class MigMagArrayUtility {

  /**
   * Inserts a new key into an array in front of the specified key.
   *
   * E.g. With this method we can add a new process pipeline to a migration
   * plugin definition in front of the specified destination property.
   *
   * @param array $array_to_modify
   *   The array to modify.
   * @param string $reference_key
   *   The reference key.
   * @param string $new_key
   *   The new key.
   * @param mixed $new_value
   *   The value for the new array key.
   * @param bool $overwrite
   *   Whether a preexisting value should be updated or not. Defaults to FALSE.
   */
  public static function insertInFrontOfKey(array &$array_to_modify, string $reference_key, string $new_key, $new_value, bool $overwrite = FALSE): void {
    $index_of_reference = array_search($reference_key, array_keys($array_to_modify));

    if ($index_of_reference === FALSE) {
      throw new \LogicException(
        sprintf(
          "The reference key '%s' cannot be found in the array.",
          $reference_key
        )
      );
    }

    // Check if the specified new destination property is already available.
    $index_of_new_key = array_search($new_key, array_keys($array_to_modify));
    // The specified new destination property is already available.
    if ($index_of_new_key !== FALSE) {
      if ($overwrite) {
        $array_to_modify[$new_key] = $new_value;
      }

      // The new process is already in the right position.
      if ($index_of_reference > $index_of_new_key) {
        return;
      }

      $new_value = $array_to_modify[$new_key];
      unset($array_to_modify[$new_key]);
    }

    $array_to_modify = array_merge(
      array_slice($array_to_modify, 0, $index_of_reference, TRUE),
      [$new_key => $new_value],
      array_slice($array_to_modify, $index_of_reference, NULL, TRUE)
    );
  }

  /**
   * Inserts a new key into an array after the specified key.
   *
   * With this method we can add a new process pipeline to a migration plugin
   * definition after the specified destination property.
   *
   * @param array $array_to_modify
   *   The array to modify.
   * @param string $reference_key
   *   The reference key.
   * @param string $new_key
   *   The new key.
   * @param mixed $new_value
   *   The value for the new array key.
   * @param bool $overwrite
   *   Whether a preexisting value should be updated or not. Defaults to FALSE.
   */
  public static function insertAfterKey(array &$array_to_modify, string $reference_key, string $new_key, $new_value, bool $overwrite = FALSE): void {
    $index_of_reference = array_search($reference_key, array_keys($array_to_modify));

    if ($index_of_reference === FALSE) {
      throw new \LogicException(
        sprintf(
          "The reference key '%s' cannot be found in the array.",
          $reference_key
        )
      );
    }

    // Check if the specified new destination property is already available.
    $index_of_new_key = array_search($new_key, array_keys($array_to_modify));
    // The specified new destination property is already available.
    if ($index_of_new_key !== FALSE) {
      if ($overwrite) {
        $array_to_modify[$new_key] = $new_value;
      }

      // The new process is already in the right position.
      if ($index_of_reference < $index_of_new_key) {
        return;
      }

      $new_value = $array_to_modify[$new_key];
      unset($array_to_modify[$new_key]);
    }

    $array_to_modify = array_merge(
      array_slice($array_to_modify, 0, $index_of_reference + 1, TRUE),
      [$new_key => $new_value],
      array_slice($array_to_modify, $index_of_reference + 1, NULL, TRUE)
    );
  }

  /**
   * Moves a key-value pair of an array in front of the specified reference key.
   *
   * @param array $array_to_modify
   *   The array to modify.
   * @param string $reference_key
   *   The reference key.
   * @param string $moved_key
   *   The key to be moved.
   */
  public static function moveInFrontOfKey(array &$array_to_modify, string $reference_key, string $moved_key): void {
    $index_of_reference = array_search($reference_key, array_keys($array_to_modify));
    $index_of_moved = array_search($moved_key, array_keys($array_to_modify));
    $exception_message = [];

    if ($index_of_reference === FALSE) {
      $exception_message[] = sprintf(
        "The given reference key '%s' cannot be found in the array",
        $reference_key
      );
    }
    if ($index_of_moved === FALSE) {
      $exception_message[] = sprintf(
        "The given key '%s' to move cannot be found in the array",
        $moved_key
      );
    }

    if ($index_of_reference !== FALSE && $index_of_reference === $index_of_moved) {
      $exception_message[] = 'The reference and the moved key cannot be the same';
    }

    if (!empty($exception_message)) {
      throw new \LogicException(implode("\n", $exception_message));
    }

    if ($index_of_reference > $index_of_moved) {
      return;
    }

    $moved_value = $array_to_modify[$moved_key];
    unset($array_to_modify[$moved_key]);
    $index_of_reference = array_search($reference_key, array_keys($array_to_modify));

    $array_to_modify = array_merge(
      array_slice($array_to_modify, 0, $index_of_reference, TRUE),
      [$moved_key => $moved_value],
      array_slice($array_to_modify, $index_of_reference, NULL, TRUE)
    );
  }

  /**
   * Moves a key-value pair of an array after the specified reference key.
   *
   * @param array $array_to_modify
   *   The array to modify.
   * @param string $reference_key
   *   The reference key.
   * @param string $moved_key
   *   The key to be moved.
   */
  public static function moveAfterKey(array &$array_to_modify, string $reference_key, string $moved_key): void {
    $index_of_reference = array_search($reference_key, array_keys($array_to_modify));
    $index_of_moved = array_search($moved_key, array_keys($array_to_modify));
    $exception_message = [];

    if ($index_of_reference === FALSE) {
      $exception_message[] = sprintf(
        "The given reference key '%s' cannot be found in the array",
        $reference_key
      );
    }
    if ($index_of_moved === FALSE) {
      $exception_message[] = sprintf(
        "The given key '%s' to move cannot be found in the array",
        $moved_key
      );
    }

    if ($index_of_reference !== FALSE && $index_of_reference === $index_of_moved) {
      $exception_message[] = 'The reference and the moved key cannot be the same';
    }

    if (!empty($exception_message)) {
      throw new \LogicException(implode("\n", $exception_message));
    }

    if ($index_of_reference < $index_of_moved) {
      return;
    }

    $moved_value = $array_to_modify[$moved_key];
    unset($array_to_modify[$moved_key]);
    $index_of_reference = array_search($reference_key, array_keys($array_to_modify));

    $array_to_modify = array_merge(
      array_slice($array_to_modify, 0, $index_of_reference + 1, TRUE),
      [$moved_key => $moved_value],
      array_slice($array_to_modify, $index_of_reference + 1, NULL, TRUE)
    );
  }

  /**
   * Adds the specified suffix to the specified values of an array.
   *
   * @param string[] $haystack
   *   List of strings to search in between.
   * @param string[] $needles
   *   The list of strings which should get the specified suffix.
   * @param string $suffix_to_add
   *   The suffix to add.
   */
  public static function addSuffixToArrayValues(array &$haystack, array $needles, string $suffix_to_add): void {
    foreach ($needles as $original_dependency_id) {
      $dependency_key = array_search($original_dependency_id, $haystack, TRUE);
      if ($dependency_key !== FALSE) {
        $haystack[$dependency_key] .= $suffix_to_add;
      }
    }
  }

}
