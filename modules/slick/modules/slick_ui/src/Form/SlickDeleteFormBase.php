<?php

namespace Drupal\slick_ui\Form;

use Drupal\blazy\Form\BlazyDeleteFormBase;

/**
 * Builds the form to delete a Slick optionset.
 */
abstract class SlickDeleteFormBase extends BlazyDeleteFormBase {

  /**
   * Defines the nice anme.
   *
   * @var string
   */
  protected static $niceName = 'Slick';

  /**
   * Defines machine name.
   *
   * @var string
   */
  protected static $machineName = 'slick';

}
