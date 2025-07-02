<?php

namespace Drupal\Tests\blazy\Kernel;

// @todo use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormState;
use Drupal\blazy\Blazy;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Tests the Blazy image formatter.
 *
 * @coversDefaultClass \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyImageFormatter
 *
 * @group blazy
 */
class BlazyFormatterTest extends BlazyKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $data['fields'] = [
      // 'field_video' => 'image',
      'field_image_multiple' => 'image',
      'field_id' => 'text',
    ];

    // Create contents.
    $bundle = $this->bundle;
    $this->setUpContentTypeTest($bundle, $data);

    $data['settings'] = $this->getFormatterSettings();
    $this->display = $this->setUpFormatterDisplay($bundle, $data);

    $this->setUpContentWithItems($bundle);
    $this->setUpRealImage();

    $this->formatterInstance = $this->getFormatterInstance();
  }

  /**
   * Tests the Blazy formatter buid methods.
   */
  public function testBlazyFormatterCache() {
    // Tests type definition.
    $this->typeDefinition = $this->blazyAdminFormatter
      ->getTypedConfig()
      ->getDefinition('blazy.settings');

    $this->assertEquals('Blazy settings', $this->typeDefinition['label']);

    // Tests cache.
    $entity = $this->entity;
    $build = $this->display->build($entity);

    $this->assertInstanceOf('\Drupal\Core\Field\FieldItemListInterface', $this->testItems, 'Field implements interface.');
    $this->assertInstanceOf('\Drupal\blazy\BlazyManagerInterface', $this->formatterInstance->blazyManager(), 'BlazyManager implements interface.');

    // Tests cache tags matching entity ::getCacheTags().
    /* @phpstan-ignore-next-line */
    $item = $entity->get($this->testFieldName);
    $field = $build[$this->testFieldName];

    // Verify it is a theme_field().
    /*
    // No longer relevant for D10.
    $this->assertArrayHasKey('#blazy', $field);
     */
    $this->assertArrayHasKey('#build', $field[0]);

    // Verify it is not a theme_item_list() grid.
    $this->assertArrayNotHasKey('#build', $field);

    $settings0 = $this->blazyManager->toHashtag($field[0]['#build']);
    $blazies0 = $settings0['blazies'];
    $file0 = $item[0]->entity;
    $tag0 = $blazies0->get('cache.metadata.tags');
    $this->assertContains($file0->getCacheTags()[0], $tag0, 'First image cache tags is as expected');

    /*
    // @fixme empty $tag1 since 2.19, only on tests, not real life.
    $settings1 = $this->blazyManager->toHashtag($field[1]['#build']);
    $blazies1 = $settings1['blazies'];
    $file1 = $item[1]->entity;
    $tag1 = $blazies1->get('cache.metadata.tags');
    $this->assertContains($file1->getCacheTags()[0], $tag1, 'Second image cache
    tags is as expected');

    foreach (Element::children($field) as $key) {
    $settings = $this->blazyManager->toHashtag($field[$key]['#build']);
    $blazies = $settings['blazies']->reset($settings);
    $file = $item[$key]->entity;
    $tags = $blazies->get('cache.metadata.tags');
    $this->assertContains($file->getCacheTags()[0], $tags, 'Image cache tags is
    as expected');
    }
     */

    $render = $this->blazyManager->renderer()->renderRoot($build);
    $this->assertNotEmpty($render);
    $this->assertStringContainsString('data-blazy', $render);
  }

  /**
   * Tests the Blazy formatter settings form.
   */
  public function testBlazySettingsForm() {
    // Tests ::settingsForm.
    $form = [];

    // Check for setttings form.
    $form_state = new FormState();
    $elements = $this->formatterInstance->settingsForm($form, $form_state);
    $this->assertArrayHasKey('opening', $elements);
    $this->assertArrayHasKey('closing', $elements);
  }

  /**
   * Tests the Blazy formatter view display.
   */
  public function testFormatterViewDisplay() {
    $formatter_settings = $this->formatterInstance->buildSettings();
    $this->assertArrayHasKey('blazies', $formatter_settings);

    $blazies = $formatter_settings['blazies'];

    $this->assertArrayHasKey('field', $blazies->storage());
    $this->assertEquals($this->testPluginId, $blazies->get('field.plugin_id'));

    // 1. Tests formatter settings.
    $build = $this->display->build($this->entity);

    /* @phpstan-ignore-next-line */
    $result = $this->entity->get($this->testFieldName)
      ->view(['type' => 'blazy']);

    $this->assertEquals('blazy', $result[0]['#theme']);

    $component = $this->display->getComponent($this->testFieldName);

    $this->assertEquals($this->testPluginId, $component['type']);
    $this->assertEquals($this->testPluginId, $build[$this->testFieldName]['#formatter']);

    $format['#settings'] = array_merge($this->getFormatterSettings(), $formatter_settings);

    $settings = &$format['#settings'];

    $this->assertArrayHasKey('blazies', $settings);

    $blazies = $settings['blazies'];

    // 2. Test theme_field(), no grid.
    $settings['grid']            = 0;
    $settings['background']      = TRUE;
    $settings['thumbnail_style'] = 'thumbnail';
    $settings['ratio']           = 'fluid';
    $settings['image_style']     = 'blazy_crop';

    $blazies->set('is.blazy', TRUE)
      ->set('lazy.id', 'blazy')
      ->set('entity.bundle', $this->bundle)
      ->set('is.vanilla', FALSE);

    $this->blazyFormatter->preBuildElements($format, $this->testItems);

    // Blazy uses theme_field() output.
    $this->assertEquals($this->testFieldName, $blazies->get('field.name'));

    // No longer relevant for D10.
    /* $this->assertArrayHasKey('#blazy', $build[$this->testFieldName]); */
    $options = $this->blazyAdminFormatter->getOptionsetOptions('image_style');
    $this->assertArrayHasKey('large', $options);

    // 3. Tests grid.
    $new_settings = $this->getFormatterSettings();

    $new_settings['grid']         = '4';
    $new_settings['grid_medium']  = '3';
    $new_settings['grid_small']   = '2';
    $new_settings['media_switch'] = 'blazy_test';
    $new_settings['style']        = 'column';
    $new_settings['image_style']  = 'blazy_crop';

    $this->display->setComponent($this->testFieldName, [
      'type'     => $this->testPluginId,
      'settings' => $new_settings,
      'label'    => 'hidden',
    ]);

    $build = $this->display->build($this->entity);

    // Verify theme_field() is taken over by Grid::build().
    $this->assertArrayNotHasKey('#blazy', $build[$this->testFieldName]);
  }

  /**
   * Tests \Drupal\blazy\Media\BlazyMedia::view().
   *
   * @param mixed|string|bool $input_url
   *   Input URL, else empty.
   * @param bool $expected
   *   The expected output.
   *
   * @covers ::view
   * @dataProvider providerTestBlazyMedia
   */
  public function testBlazyMedia($input_url, $expected) {
    // Attempts to fix undefined DRUPAL_TEST_IN_CHILD_SITE for PHP 8 at 9.1.x.
    // The middleware test.http_client.middleware calls drupal_generate_test_ua
    // which checks the DRUPAL_TEST_IN_CHILD_SITE constant, that is not defined
    // in Kernel tests.
    try {
      if (!defined('DRUPAL_TEST_IN_CHILD_SITE')) {
        define('DRUPAL_TEST_IN_CHILD_SITE', FALSE);
      }

      $entity = $this->entity;

      $settings = [
        'view_mode'       => 'default',
        'thumbnail_style' => 'thumbnail',
        'uri'             => $this->uri,
      ] + Blazy::init();

      $blazies = $settings['blazies'];
      $info = [
        'bundle'       => $this->bundle,
        'input_url'    => $input_url,
        'source_field' => $this->testFieldName,
        'source'       => 'remote_video',
        'view_mode'    => 'default',
      ];

      $blazies->set('media', $info)
        ->set('image.uri', $this->uri);

      $build = $this->display->build($entity);

      $data = [
        '#entity' => $entity,
        '#settings' => $settings,
      ];

      $render = $this->blazyMedia->view($data);

      if ($expected && $render) {
        $this->assertNotEmpty($render);

        $render = $this->blazyManager->renderer()->renderRoot($build[$this->testFieldName]);
        $this->assertStringContainsString('data-blazy', $render);
      }
      else {
        $this->assertEmpty($render);
      }
    }
    catch (GuzzleException $e) {
      // Ignore any HTTP errors.
    }
  }

  /**
   * Provide test cases for ::testBlazyMedia().
   *
   * @return array
   *   An array of tested data.
   */
  public static function providerTestBlazyMedia() {
    return [
      ['', TRUE],
      ['https://xyz123.com/x/123', FALSE],
      ['user', TRUE],
    ];
  }

}
