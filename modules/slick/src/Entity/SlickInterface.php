<?php

namespace Drupal\slick\Entity;

/**
 * Provides an interface defining a Slick entity.
 */
interface SlickInterface extends SlickBaseInterface {

  /**
   * Returns the number of breakpoints.
   *
   * @return int
   *   The number of the provided breakpoints.
   */
  public function getBreakpoints(): int;

  /**
   * Returns the group this optioset instance belongs to for easy selections.
   *
   * @return string
   *   The name of the optionset group.
   */
  public function getGroup(): string;

  /**
   * Returns the Slick skin.
   *
   * @return string
   *   The name of the Slick skin.
   */
  public function getSkin(): string;

  /**
   * Returns whether to optimize the stored options, or not.
   *
   * @return bool
   *   If true, the stored options will be cleaned out from defaults.
   */
  public function optimized(): bool;

  /**
   * Defines the dependent options.
   *
   * @return array
   *   The dependent options.
   */
  public static function getDependentOptions(): array;

  /**
   * Returns the Splide responsive settings.
   *
   * @return array
   *   The responsive options.
   */
  public function getResponsiveOptions(): array;

  /**
   * Sets the Splide responsive settings.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setResponsiveSettings($values, $delta = 0, $key = 'settings'): self;

  /**
   * Removes wasted dependent options, even if not empty.
   */
  public function removeWastedDependentOptions(array &$js): void;

  /**
   * Strip out options containing default values so to have real clean JSON.
   *
   * @return array
   *   The cleaned out settings.
   */
  public function toJson(array $js): array;

}
