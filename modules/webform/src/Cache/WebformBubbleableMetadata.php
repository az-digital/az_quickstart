<?php

namespace Drupal\webform\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Value object used for bubbleable rendering metadata for webforms.
 */
class WebformBubbleableMetadata extends BubbleableMetadata {

  /**
   * Appends the values of this bubbleable metadata object to a render array.
   *
   * We can't use \Drupal\Core\Render\BubbleableMetadata::applyTo because it
   * replaces all existing cache contexts and tags.
   *
   * @param array $build
   *   A render array.
   *
   * @see \Drupal\Core\Render\BubbleableMetadata::applyTo
   * @see \Drupal\webform\WebformSubmissionForm::buildForm
   * @see \Drupal\webform\Plugin\WebformElementBase::replaceTokens
   */
  public function appendTo(array &$build) {
    $contexts = $this->getCacheContexts();
    $tags = $this->getCacheTags();
    $max_age = $this->getCacheMaxAge();
    $attachments = $this->getAttachments();

    // Make sure cache metadata has been set.
    if (empty($contexts)
      && empty($tags)
      && empty($attachments)
      && $max_age === Cache::PERMANENT) {
      return;
    }

    // The below code is copied from Renderer::mergeBubbleableMetadata.
    // @see \Drupal\Core\Render\Renderer::mergeBubbleableMetadata
    $meta_a = BubbleableMetadata::createFromRenderArray($build);
    $meta_b = BubbleableMetadata::createFromRenderArray([
      '#cache' => [
        'contexts' => $contexts,
        'tags' => $tags,
        'max-age' => $max_age,
        'attachments' => $attachments,
      ],
    ]);
    $meta_a->merge($meta_b)->applyTo($build);
  }

}
