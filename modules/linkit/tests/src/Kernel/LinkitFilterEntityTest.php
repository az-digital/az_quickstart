<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\filter\FilterPluginCollection;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Tests the Linkit filter.
 *
 * @coversDefaultClass \Drupal\linkit\Plugin\Filter\LinkitFilter
 *
 * @group linkit
 */
class LinkitFilterEntityTest extends LinkitKernelTestBase {

  use AssertLinkitFilterTrait;
  use PathAliasTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'filter',
    'entity_test',
    'path',
    'path_alias',
    'language',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('file');

    // Add Swedish, Danish and Finnish.
    ConfigurableLanguage::createFromLangcode('sv')->save();
    ConfigurableLanguage::createFromLangcode('da')->save();
    ConfigurableLanguage::createFromLangcode('fi')->save();

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $manager */
    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filter = $bag->get('linkit');
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    // Undo what the parent did, to allow testing path aliases in kernel tests.
    $container->getDefinition('path_alias.path_processor')
      ->addTag('path_processor_inbound')
      ->addTag('path_processor_outbound');
  }

  /**
   * Tests the linkit filter for entities with different access.
   */
  public function testFilterEntityAccess() {
    // Create an entity that no one have access to.
    $entity_no_access = EntityTest::create(['name' => 'forbid_access']);
    $entity_no_access->save();

    // Create an entity that is accessible.
    $entity_with_access = EntityTest::create(['name' => $this->randomMachineName()]);
    $entity_with_access->save();

    // Automatically set the title.
    $this->filter->setConfiguration(['settings' => ['title' => 1]]);

    // Make sure the title is not included.
    $input = '<a data-entity-type="' . $entity_no_access->getEntityTypeId() . '" data-entity-uuid="' . $entity_no_access->uuid() . '">Link text</a>';
    $this->assertFalse(strpos($this->process($input)->getProcessedText(), 'title'), 'The link does not contain a title attribute.');

    $this->assertLinkitFilterWithTitle($entity_with_access);
  }

  /**
   * Tests the linkit filter for entities with translations.
   */
  public function testFilterEntityTranslations() {
    // Create an entity and add translations to that.
    /** @var \Drupal\entity_test\Entity\EntityTestMul $entity */
    $entity = EntityTestMul::create(['name' => $this->randomMachineName()]);
    $entity->addTranslation('sv', ['name' => $this->randomMachineName(), 'langcode' => 'sv']);
    $entity->addTranslation('da', ['name' => $this->randomMachineName(), 'langcode' => 'da']);
    $entity->addTranslation('fi', ['name' => $this->randomMachineName(), 'langcode' => 'fi']);
    $entity->save();

    $url = $entity->toUrl()->toString();

    // Add url aliases.
    $this->createPathAlias($url, '/' . $this->randomMachineName(), 'en');
    $this->createPathAlias($url, '/' . $this->randomMachineName(), 'sv');
    $this->createPathAlias($url, '/' . $this->randomMachineName(), 'da');
    $this->createPathAlias($url, '/' . $this->randomMachineName(), 'fi');

    // Disable the automatic title attribute.
    $this->filter->setConfiguration(['settings' => ['title' => 0]]);
    /** @var \Drupal\Core\Language\Language $language */
    foreach ($entity->getTranslationLanguages() as $language) {
      $this->assertLinkitFilter($entity->getTranslation($language->getId()), $language->getId());
    }

    // Enable the automatic title attribute.
    $this->filter->setConfiguration(['settings' => ['title' => 1]]);
    /** @var \Drupal\Core\Language\Language $language */
    foreach ($entity->getTranslationLanguages() as $language) {
      $this->assertLinkitFilterWithTitle($entity->getTranslation($language->getId()), $language->getId());
    }
  }

  /**
   * Tests the linkit filter for file entities.
   */
  public function testFilterFileEntity() {
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();

    // Disable the automatic title attribute.
    $this->filter->setConfiguration(['settings' => ['title' => 0]]);
    $this->assertLinkitFilter($file);

    // Automatically set the title.
    $this->filter->setConfiguration(['settings' => ['title' => 1]]);
    $this->assertLinkitFilterWithTitle($file);
  }

  /**
   * Tests that the linkit filter do not overwrite provided title attributes.
   */
  public function testTitleOverwritten() {
    // Create an entity.
    $entity = EntityTest::create(['name' => $this->randomMachineName()]);
    $entity->save();

    // Automatically set the title.
    $this->filter->setConfiguration(['settings' => ['title' => 1]]);

    // Make sure the title is not overwritten.
    $input = '<a data-entity-type="' . $entity->getEntityTypeId() . '" data-entity-uuid="' . $entity->uuid() . '" title="Do not override">Link text</a>';
    $this->assertTrue(strpos($this->process($input)->getProcessedText(), 'Do not override') !== FALSE, 'The filer is not overwrite the provided title attribute value.');
  }

  /**
   * Tests that the linkit filter do not overwrite provided fragment and query.
   */
  public function testQueryAndFragments() {
    // Create an entity.
    $entity = EntityTest::create(['name' => $this->randomMachineName()]);
    $entity->save();

    // Make sure original query and fragment are preserved.
    $input = '<a data-entity-type="' . $entity->getEntityTypeId() . '" data-entity-uuid="' . $entity->uuid() . '" href="unimportant/1234?query=string#fragment">Link text</a>';
    $this->assertStringContainsString('?query=string', $this->process($input)->getProcessedText());
    $this->assertStringContainsString('#fragment', $this->process($input)->getProcessedText());
  }

}
