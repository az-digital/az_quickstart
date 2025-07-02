<?php

namespace Drupal\slick_ui\Form;

use Drupal\Core\Url;

/**
 * Builds the form to delete a Slick optionset.
 */
class SlickDeleteForm extends SlickDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.slick.collection');
  }

}
