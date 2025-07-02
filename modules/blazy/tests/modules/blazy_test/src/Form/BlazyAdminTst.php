<?php

namespace Drupal\blazy_test\Form;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\blazy\Form\BlazyAdminInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides resusable admin functions or form elements.
 */
class BlazyAdminTst implements BlazyAdminTstInterface {

  use StringTranslationTrait;

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   */
  protected $blazyAdmin;

  /**
   * The blazy_test manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * Constructs a GridStackAdmin object.
   */
  public function __construct(BlazyAdminInterface $blazy_admin, BlazyManagerInterface $manager) {
    $this->blazyAdmin = $blazy_admin;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('blazy.admin.formatter'),
      $container->get('blazy.manager')
    );
  }

  /**
   * Returns the blazy admin.
   */
  public function blazyAdmin() {
    return $this->blazyAdmin;
  }

  /**
   * Returns the blazy_test manager.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns all settings form elements.
   */
  public function buildSettingsForm(array &$form, array $definition): void {
    $definition += [
      'namespace'  => 'blazy',
      'optionsets' => [],
      'skins'      => [],
      'grid_form'  => TRUE,
      'style'      => TRUE,
    ];

    $keys = ['background', 'caches', 'fieldable_form', 'id', 'vanilla'];
    foreach ($keys as $key) {
      $definition[$key] = TRUE;
    }

    $definition['layouts'] = isset($definition['layouts']) ? array_merge($this->getLayoutOptions(), $definition['layouts']) : $this->getLayoutOptions();

    $this->openingForm($form, $definition);
    $this->mainForm($form, $definition);
    $this->closingForm($form, $definition);
  }

  /**
   * Returns the opening form elements.
   */
  public function openingForm(array &$form, array &$definition): void {
    $this->blazyAdmin->openingForm($form, $definition);
  }

  /**
   * Returns the main form elements.
   */
  public function mainForm(array &$form, array $definition): void {
    if (!empty($definition['image_style_form'])) {
      $this->blazyAdmin->imageStyleForm($form, $definition);
    }

    if (!empty($definition['media_switch_form'])) {
      $this->blazyAdmin->mediaSwitchForm($form, $definition);
    }

    if (!empty($definition['grid_form'])) {
      $this->blazyAdmin->gridForm($form, $definition);
    }

    if (!empty($definition['fieldable_form'])) {
      $this->blazyAdmin->fieldableForm($form, $definition);
    }
  }

  /**
   * Returns the closing form elements.
   */
  public function closingForm(array &$form, array $definition): void {
    $this->blazyAdmin->closingForm($form, $definition);
  }

  /**
   * Returns default layout options for the core Image, or Views.
   */
  public function getLayoutOptions(): array {
    return [
      'bottom' => $this->t('Caption bottom'),
      'center' => $this->t('Caption center'),
      'top'    => $this->t('Caption top'),
    ];
  }

  /**
   * Return the field formatter settings summary.
   */
  public function getSettingsSummary(array $definition): array {
    return $this->blazyAdmin->getSettingsSummary($definition);
  }

  /**
   * Returns available fields for select options.
   */
  public function getFieldOptions(
    array $target_bundles = [],
    array $allowed_field_types = [],
    $entity_type_id = 'media',
    $target_type = '',
  ): array {
    return $this->blazyAdmin->getFieldOptions($target_bundles, $allowed_field_types, $entity_type_id, $target_type);
  }

  /**
   * Returns re-usable logic, styling and assets across fields and Views.
   */
  public function finalizeForm(array &$form, array $definition): void {
    $this->blazyAdmin->finalizeForm($form, $definition);
  }

}
