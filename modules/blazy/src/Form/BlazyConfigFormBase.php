<?php

namespace Drupal\blazy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\Utility\Sanitize;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines blazy admin settings form base.
 */
abstract class BlazyConfigFormBase extends ConfigFormBase {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * The available options to check for.
   *
   * @var array
   */
  protected $validatedOptions = [];

  /**
   * The available paths to check for.
   *
   * @var array
   */
  protected $validatedPaths = [];

  /**
   * The allowed tags can be NULL for default, or array.
   *
   * @var mixed
   */
  protected $allowedTags = NULL;

  /**
   * Whether to allow tags.
   *
   * @var bool
   */
  protected $stripTags = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->libraryDiscovery = $container->get('library.discovery');
    $instance->manager = $container->get('blazy.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $paths = $this->validatedPaths;
    $options = $this->validatedOptions;
    $options = array_merge($options, $paths);

    if ($options) {
      foreach ($options as $option) {
        if ($form_state->hasValue($option)) {
          // Not effective, best is to validate output, yet better than misses.
          $value = $form_state->getValue($option);
          if ($value) {
            $info = [
              'paths' => $paths,
              'striptags' => $this->stripTags,
              'tags' => $this->allowedTags,
            ];
            $value = Sanitize::input($value, $option, $info);
          }
          $form_state->setValue($option, $value);
        }
      }
    }
  }

}
