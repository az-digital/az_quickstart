<?php

namespace Drupal\paragraphs;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface defining a paragraph behavior.
 *
 * A paragraph behavior plugin adds extra functionality to the paragraph such as
 * adding properties and attributes, it can also add extra classes to the render
 * elements so extra styling can be applied.
 */
interface ParagraphsConversionInterface extends PluginFormInterface, ConfigurableInterface {

  /**
   * Builds a conversion form to add extra settings to the conversion.
   *
   * This method is responsible for building the conversion form for each
   * Paragraph so the user can set special attributes and properties.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The fields build array that the plugin creates.
   */
  public function buildConversionForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state);

  /**
   * Validates the conversion fields form.
   *
   * This method is responsible for validating the data in the conversion fields
   * form and displaying validation messages.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateConversionForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state);

  /**
   * Submit the values taken from the form to store the values.
   *
   * This method is responsible for submitting the data and saving it in the
   * paragraphs entity.
   *
   * @param array $settings
   *   The conversion settings to be applied.
   * @param \Drupal\paragraphs\ParagraphInterface $original_paragraph
   *   The original paragraph to convert.
   * @param array $converted_paragraphs
   *   (optional) The array of converted paragraphs.
   */
  public function convert(array $settings, ParagraphInterface $original_paragraph, ?array $converted_paragraphs = NULL);

  /**
   * Check if the current plugin supports conversion for a paragraph.
   *
   * This method checks whether a plugin supports a paragraph type to be
   * converted.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph that will be checked is supported by the plugin.
   * @param array $parent_allowed_types
   *   (optional) The allowed paragraph types on the parent field.
   */
  public function supports(ParagraphInterface $paragraph, ?array $parent_allowed_types = NULL);

}
