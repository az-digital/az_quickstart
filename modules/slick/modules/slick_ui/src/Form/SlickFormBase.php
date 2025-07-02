<?php

namespace Drupal\slick_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\Form\BlazyEntityFormBase;
use Drupal\blazy\Traits\EasingTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base form for a slick instance configuration form.
 */
abstract class SlickFormBase extends BlazyEntityFormBase {

  use EasingTrait;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\slick\Form\SlickAdminInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * The form elements.
   *
   * @var array
   */
  protected $formElements;

  /**
   * The JS easing options.
   *
   * @var array
   */
  protected $jsEasingOptions;

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

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->admin = $container->get('slick.admin');
    $instance->manager = $container->get('slick.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $admin_css = $this->manager->config('admin_css', 'blazy.settings');

    // Attach Slick admin library.
    if ($admin_css) {
      $form['#attached']['library'][] = 'slick_ui/slick.admin.vtabs';
    }

    return parent::form($form, $form_state);
  }

}
