<?php

namespace Drupal\blazy;

/**
 * Defines re-usable media-related methods across Blazy ecosystem to DRY.
 */
interface BlazyManagerBaseInterface extends BlazyInterface {

  /**
   * Warning! Do not override this method, use self::attachments() instead.
   *
   * So we can add return type at/ by 3.x without breaking your codes.
   * Returns array of needed assets suitable for #attached property.
   *
   * @param array $attach
   *   The settings which determine what library to attach, empty to defaults.
   *
   * @return array
   *   The supported libraries.
   */
  public function attach(array $attach = []): array;

  /**
   * Alias for Blazy::containerAttributes().
   *
   * @param array $attributes
   *   The container attributes being modified.
   * @param array $settings
   *   The given settings.
   */
  public function containerAttributes(array &$attributes, array $settings): void;

  /**
   * Returns the supported image effects.
   *
   * @return array
   *   The supported image effects.
   */
  public function getImageEffects(): array;

  /**
   * Gets the supported lightboxes.
   *
   * @return array
   *   The supported lightboxes.
   */
  public function getLightboxes(): array;

  /**
   * Provides alterable display styles.
   *
   * @return array
   *   The supported display styles.
   */
  public function getStyles(): array;

  /**
   * Alias for Thumbnail::view() to forget looking up unknown classes.
   *
   * @param array $settings
   *   The given settings.
   * @param object $item
   *   The optional image item.
   * @param array $captions
   *   The optional thumbnail captions.
   *
   * @return array
   *   The thumbnail image style, or empty.
   */
  public function getThumbnail(array $settings, $item = NULL, array $captions = []): array;

  /**
   * Checks for Image styles at container level once, except for multi-styles.
   *
   * Specific for lightbox, it can also be Responsive image, but not here.
   * The output is stored in blazies under each key of the provided styles
   * defined by the respective key under $settings, e.g.: image_style to
   * blazies.image.style, etc. Nothing is loaded if no setting is provided.
   *
   * @param array $settings
   *   The modified settings.
   * @param bool $multiple
   *   A flag for various Image styles: Blazy Filter, etc., old GridStack.
   *   While most field formatters can only have one image style per field.
   * @param array $styles
   *   The image styles, default to BlazyDefault::imageStyles().
   *   If more to be added, the convention is to not suffix it with _style,
   *   e.g.: image will be auto-suffixed as image_style, etc.
   *
   * @see \Drupal\blazy\BlazyDefault::imageStyles()
   */
  public function imageStyles(array &$settings, $multiple = FALSE, array $styles = []): void;

  /**
   * Checks for Blazy formatter such as from within a Views style plugin.
   *
   * Ensures the settings traverse up to the container where Blazy is clueless.
   * This allows Blazy Grid, or other Views styles, lacking of UI, to have
   * additional settings extracted from the first Blazy formatter found.
   * Such as media switch/ lightbox. This way the container can add relevant
   * attributes to its container, etc. Also applies to entity references where
   * Blazy is not the main formatter, instead embedded as part of the parent's.
   *
   * This fairly complex logic is intended to reduce similarly complex logic at
   * individual item. But rather than at individual item, it is executed once
   * at the container level. If you have 100 images, this method is executed
   * once, not 100x, as long as you have all image styles cropped, not scaled.
   *
   * Since 2.7 [data-blazy] is just identifier for blazy container, can be empty
   * or used to pass optional JavaScript settings. It used to store aspect
   * ratios, but hardly used, due to complication with Picture which may have
   * irregular aka art-direction aspect ratios.
   *
   * This still needs improvements and a little more simplified version.
   *
   * @param array $settings
   *   The settings being modified.
   * @param array $data
   *   The first data containing settings or item keys.
   *
   * @see \Drupal\blazy\BlazyManager::prepareBuild()
   * @see \Drupal\blazy\Field\BlazyEntityVanillaBase::buildElements()
   * @todo change the second param back to array at 3.x when BVEF is dropped.
   */
  public function isBlazy(array &$settings, array $data = []): void;

  /**
   * Checks for essential blazy features.
   *
   * @param array $build
   *   The build array being modified.
   * @param object $item
   *   The optional image item.
   *
   * @return \Drupal\blazy\BlazySettings
   *   The BlazySettings object.
   */
  public function preBlazy(array &$build, $item = NULL): BlazySettings;

  /**
   * Thumbnails are poorly-informed, provide relevant information.
   *
   * @param array $build
   *   The build array being modified.
   * @param array $blazy
   *   The blazy renderable array available after ::getBlazy() called.
   */
  public function postBlazy(array &$build, array $blazy): void;

  /**
   * Prepares shared data common between field formatter and views field.
   *
   * This is to overcome the limitation of self::postSettings().
   *
   * @param array $build
   *   The build data containing settings, entity, etc.
   */
  public function prepareData(array &$build): void;

  /**
   * Prepare base preliminary settings.
   *
   * The `fx` sequence: hook_alter > formatters (not implemented yet) > UI.
   * The `_fx` is a special flag such as to temporarily disable till needed.
   * Called by field formatters, views [styles|fields via BlazyEntity],
   * [blazy|splide|slick] filters.
   *
   * @param array $settings
   *   The settings being modified.
   */
  public function preSettings(array &$settings): void;

  /**
   * Modifies the post settings inherited down to each item.
   *
   * @param array $settings
   *   The settings being modified.
   */
  public function postSettings(array &$settings): void;

  /**
   * Overrides data massaged by [blazy|slick|splide, etc.]_settings_alter().
   *
   * @param array $settings
   *   The settings being modified.
   * @param object $entity
   *   The optional entity object.
   */
  public function postSettingsAlter(array &$settings, $entity = NULL): void;

  /**
   * Provides the third party formatters where full blown Blazy is not worthy.
   *
   * The module doesn't automatically convert the relevant theme to use Blazy,
   * however two attributes are provided: `data-b-lazy` and `data-b-preview`
   * which can be used to override a particular theme to use Blazy.
   *
   * The `data-b-lazy`is a flag indicating Blazy is enabled.
   * The `data-b-preview` is a flag indicating Blazy in CKEditor preview mode
   * via Entity/Media Embed which normally means Blazy should be disabled
   * due to CKEditor not supporting JS assets.
   *
   * @see \Drupal\blazy\Theme\BlazyTheme::blazy()
   * @see \Drupal\blazy\Theme\BlazyTheme::field()
   * @see \Drupal\blazy\Theme\BlazyTheme::fileVideo()
   * @see blazy_preprocess_file_video()
   */
  public function thirdPartyFormatters(): array;

  /**
   * Provides relevant attributes to feed into theme_blazy().
   *
   * To replace all sub-modules theme_ITEM() contents with theme_blazy() at 3.x.
   *
   * @param array $data
   *   The data being modified containing: #settings, #item, #entity, etc.
   *   What is needed is only to pass BlazyDefault::themeAttributes() to convert
   *   sub-modules' theme_ITEM() contents, e.g.: theme_splide_slide(),
   *   theme_slick_slide(), theme_gridstack_box(), etc. with theme_blazy() to
   *   minimize dups and have improvement at one go. Normally image/ media
   *   related. Repeat, only replace their contents, not their theme_ITEM().
   * @param array $captions
   *   The captions being modified.
   * @param int $delta
   *   The current delta for convenience.
   */
  public function toBlazy(array &$data, array &$captions, $delta): void;

  /**
   * Provides attachments and cache common for all blazy-related modules.
   */
  public function setAttachments(
    array &$element,
    array $settings,
    array $attachments = [],
  ): void;

}
