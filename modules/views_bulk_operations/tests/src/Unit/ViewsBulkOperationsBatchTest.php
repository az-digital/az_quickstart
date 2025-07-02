<?php

namespace Drupal\Tests\views_bulk_operations\Unit;

use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Tests\UnitTestCase;
use Drupal\views_bulk_operations\ViewsBulkOperationsBatch;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\views_bulk_operations\ViewsBulkOperationsBatch
 * @group views_bulk_operations
 */
class ViewsBulkOperationsBatchTest extends UnitTestCase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static array $modules = ['node'];

  /**
   * Messages storage.
   *
   * @var string[]|null
   */
  private $messages = NULL;

  /**
   * Messenger service.
   */
  protected Messenger $messenger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();

    // Mock translation manager.
    $translation_manager = $this->createPartialMock(TranslationManager::class, ['translateString']);
    $translation_manager->expects($this->any())
      ->method('translateString')
      ->willReturnCallback(function (TranslatableMarkup $translated_string) {
        return \strtr($translated_string->getUntranslatedString(), $translated_string->getOptions());
      });

    $container->set('string_translation', $translation_manager);

    // Mock messanger.
    $this->messenger = $this->createMock(Messenger::class);
    $this->messenger->expects($this->any())
      ->method('addMessage')
      ->willReturnCallback(function ($message, $type, $repeat) {
        if ($this->messages === NULL) {
          $this->messages = (string) $message;
        }
        else {
          $this->messages .= ' | ' . (string) $message;
        }
      });
    $this->messenger->expects($this->any())
      ->method('all')
      ->willReturnCallback(function () {
        $messages = $this->messages;
        $this->messages = NULL;
        return $messages;
      });

    $container->set('messenger', $this->messenger);

    \Drupal::setContainer($container);
  }

  /**
   * Tests the getBatch() method.
   *
   * @covers ::getBatch
   */
  public function testGetBatch(): void {
    $data = [
      'list' => [[0, 'en', 'node', 1]],
      'some_data' => [],
      'action_label' => '',
      'finished_callback' => [ViewsBulkOperationsBatch::class, 'finished'],
    ];
    $batch = ViewsBulkOperationsBatch::getBatch($data);
    $this->assertArrayHasKey('title', $batch);
    $this->assertArrayHasKey('operations', $batch);
    $this->assertArrayHasKey('finished', $batch);
  }

  /**
   * Tests the finished() method.
   *
   * @covers ::finished
   */
  public function testFinished(): void {
    $results = [
      'operations' => [
        [
          'message' => 'Some operation',
          'type' => 'status',
          'count' => 2,
        ],
      ],
    ];
    ViewsBulkOperationsBatch::finished(TRUE, $results, []);
    $this->assertEquals('Some operation (2)', $this->messenger->all());

    $results = [
      'operations' => [
        [
          'message' => 'Some operation1',
          'type' => 'status',
          'count' => 1,
        ],
        [
          'message' => 'Some operation2',
          'type' => 'status',
          'count' => 1,
        ],
      ],
    ];

    ViewsBulkOperationsBatch::finished(TRUE, $results, []);
    $this->assertEquals('Some operation1 (1) | Some operation2 (1)', $this->messenger->all());
  }

}
