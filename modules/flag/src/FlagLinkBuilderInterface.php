<?php

namespace Drupal\flag;

/**
 * Provides a lazy builder for flag links.
 */
interface FlagLinkBuilderInterface {

  /**
   * Lazy builder callback for displaying a flag action link.
   *
   * @param string $entity_type_id
   *   The entity type ID for which the link should be shown.
   * @param string|int $entity_id
   *   The entity ID for which the link should be shown.
   * @param string $flag_id
   *   The flag ID for which the link should be shown.
   * @param string|null $view_mode
   *   (optional) The view mode.
   *
   * @return array
   *   A render array for the action link, empty if the user does not have
   *   access.
   */
  public function build($entity_type_id, $entity_id, $flag_id, $view_mode = NULL);

}
