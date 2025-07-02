<?php

namespace Drupal\blazy;

/**
 * Defines re-usable media-related methods specific for blazy plugins.
 *
 * Sub-modules should implement/ extend BlazyManagerBaseInterface, not
 * BlazyManagerInterface to have their own unique render methods.
 */
interface BlazyManagerInterface extends BlazyManagerBaseInterface {

  /**
   * Returns the enforced rich media content, or media using theme_blazy().
   *
   * @param array $build
   *   The array containing: item, content, settings, or optional captions.
   *
   * @return array
   *   The alterable and renderable array of enforced content, or theme_blazy().
   *
   * @todo remove/ unify ImageItem, or fake one, as plain array at 3.x.
   */
  public function getBlazy(array $build): array;

  /**
   * Builds the Blazy image as a structured array ready for ::renderer().
   *
   * @param array $element
   *   The pre-rendered element.
   *
   * @return array
   *   The renderable array of pre-rendered element.
   */
  public function preRenderBlazy(array $element): array;

  /**
   * Returns the contents using theme_field(), or theme_item_list().
   *
   * Blazy outputs can be formatted using either flat list via theme_field(), or
   * a grid of Field items or Views rows via theme_item_list().
   *
   * @param array $build
   *   The array containing: settings, children elements, or optional items.
   *
   * @return array
   *   The alterable and renderable array of contents.
   */
  public function build(array $build): array;

  /**
   * Builds the Blazy outputs as a structured array ready for ::renderer().
   *
   * @param array $element
   *   The pre-rendered element.
   *
   * @return array
   *   The renderable array of pre-rendered element.
   */
  public function preRenderBuild(array $element): array;

  /**
   * Returns drupalSettings for IO.
   *
   * @param array $attach
   *   The settings which determine what library to attach, empty for defaults.
   *
   * @return object
   *   The supported IO drupalSettings.
   */
  public function getIoSettings(array $attach = []): object;

}
