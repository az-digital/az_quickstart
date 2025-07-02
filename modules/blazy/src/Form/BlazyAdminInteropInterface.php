<?php

namespace Drupal\blazy\Form;

/**
 * Defines interop form methods for different or competing sub-modules.
 *
 * So that Slick can be interchanged with Splide at 3.+, etc. by 3rd-tiers.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 */
interface BlazyAdminInteropInterface {

  /**
   * Modifies the main form elements.
   */
  public function buildSettingsForm(array &$form, array $definition): void;

  /**
   * Modifies the opening form elements.
   */
  public function openingForm(array &$form, array &$definition): void;

  /**
   * Modifies the image formatter form elements.
   */
  public function mediaSwitchForm(array &$form, array $definition): void;

  /**
   * Modifies the image formatter form elements.
   */
  public function imageStyleForm(array &$form, array $definition): void;

  /**
   * Modifies re-usable fieldable formatter form elements.
   */
  public function fieldableForm(array &$form, array $definition): void;

  /**
   * Modifies re-usable grid elements across Slick field formatter and Views.
   */
  public function gridForm(array &$form, array $definition): void;

  /**
   * Modifies the closing ending form elements.
   */
  public function closingForm(array &$form, array $definition): void;

  /**
   * Modifies re-usable logic, styling and assets across fields and Views.
   */
  public function finalizeForm(array &$form, array $definition): void;

}
