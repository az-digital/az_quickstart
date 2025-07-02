<?php

namespace Drupal\schema_metatag\Plugin\metatag\Tag;

/**
 * All Schema.org image tags should extend this class.
 */
class SchemaImageObjectBase extends SchemaNameBase {

  /**
   * {@inheritdoc}
   *
   * We don't want to render any output if there is no url.
   */
  public function output(): array {
    $result = parent::output();
    if (empty($result['#attributes']['content']['url'])) {
      return [];
    }
    else {
      return $result;
    }
  }

}
