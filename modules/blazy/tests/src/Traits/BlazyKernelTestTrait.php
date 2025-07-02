<?php

namespace Drupal\Tests\blazy\Traits;

/**
 * A trait common for Kernel tests.
 */
trait BlazyKernelTestTrait {

  use BlazyUnitTestTrait;
  use BlazyCreationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The formatter display without data.
   *
   * @var object
   */
  protected $displayEmpty;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Setup common Kernel classes.
   */
  protected function setUpKernelInstall() {
    $this->installConfig([
      'field',
      'image',
      'responsive_image',
      'node',
      'views',
      'blazy',
    ]);

    $this->installSchema('user', ['users_data']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('file', ['file_usage']);

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    // @todo $this->installEntitySchema('entity_test');
  }

  /**
   * Setup common Kernel manager classes.
   */
  protected function setUpKernelManager() {
    $this->root                   = $this->container->getParameter('app.root');
    $this->fileSystem             = $this->container->get('file_system');
    $this->entityFieldManager     = $this->container->get('entity_field.manager');
    $this->fieldTypePluginManager = $this->container->get('plugin.manager.field.field_type');
    $this->formatterPluginManager = $this->container->get('plugin.manager.field.formatter');
    $this->blazyManager           = $this->container->get('blazy.manager');
    $this->blazyOembed            = $this->container->get('blazy.oembed');
    $this->blazyEntity            = $this->container->get('blazy.entity');
    $this->blazyMedia             = $this->container->get('blazy.media');
    $this->blazyFormatter         = $this->container->get('blazy.formatter');
    $this->blazyAdminFormatter    = $this->container->get('blazy.admin.formatter');
    $this->blazyAdmin             = $this->container->get('blazy.admin');
    $this->languageManager        = $this->container->get('language_manager');
    $this->libraries              = $this->container->get('blazy.libraries');
  }

}
