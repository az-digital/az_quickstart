<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'Slick Image' formatter.
 *
 * @FieldFormatter(
 *   id = "slick_image",
 *   label = @Translation("Slick Image"),
 *   description = @Translation("Display the images as a Slick carousel."),
 *   field_types = {"image"},
 *   quickedit = {"editor" = "disabled"}
 * )
 */
class SlickImageFormatter extends SlickFileFormatterBase {}
