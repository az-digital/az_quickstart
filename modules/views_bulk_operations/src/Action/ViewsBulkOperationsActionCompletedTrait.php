<?php

namespace Drupal\views_bulk_operations\Action;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines action completion logic.
 */
trait ViewsBulkOperationsActionCompletedTrait {

  /**
   * Set message function wrapper.
   *
   * @see \Drupal\Core\Messenger\MessengerInterface
   */
  public static function message($message = NULL, $type = 'status', $repeat = TRUE): void {
    \Drupal::messenger()->addMessage($message, $type, $repeat);
  }

  /**
   * Translation function wrapper.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface:translate()
   */
  public static function translate($string, array $args = [], array $options = []): TranslatableMarkup {
    return \Drupal::translation()->translate($string, $args, $options);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Was the process successful?
   * @param array $results
   *   Batch process results array.
   * @param array $operations
   *   Performed operations array.
   */
  public static function finished($success, array $results, array $operations): ?RedirectResponse {
    if ($success) {
      foreach ($results['operations'] as $item) {
        if (\strpos($item['message'], '@count') !== FALSE) {
          $message = new FormattableMarkup($item['message'], [
            '@count' => $item['count'],
          ]);
        }
        else {
          $message = new TranslatableMarkup('@message (@count)', [
            '@message' => $item['message'],
            '@count' => $item['count'],
          ]);
        }
        static::message($message, $item['type']);
      }
    }
    else {
      $message = static::translate('Finished with an error.');
      static::message($message, 'error');
    }
    return NULL;
  }

}
