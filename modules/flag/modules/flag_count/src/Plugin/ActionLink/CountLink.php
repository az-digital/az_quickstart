<?php

namespace Drupal\flag_count\Plugin\ActionLink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\FlagInterface;
use Drupal\flag\Plugin\ActionLink\AJAXactionLink;

/**
 * Provides the Count link type.
 *
 * This class is an extension of the Ajax link type, but modified to
 * provide flag count.
 *
 * @ActionLinkType(
 *   id = "count_link",
 *   label = @Translation("Count link"),
 *   description = "An example AJAX action link which displays the count with
 * the flag."
 * )
 */
class CountLink extends AJAXactionLink {

  /**
   * {@inheritdoc}
   */
  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity, ?string $view_mode = NULL): array {
    // Get the render array.
    $build = parent::getAsFlagLink($flag, $entity, $view_mode);

    // Normally, you'd just override flag.html.twig in your site's theme. For
    // this example module, we do something more advanced: Provide a new.
    // @ActionLinkType that changes the default theme function.
    $build['#theme'] = 'flag_count';

    // Return the modified render array.
    return $build;
  }

}
