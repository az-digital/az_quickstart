<?php

namespace Drupal\Tests\webform\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformMessageManager;
use Drupal\webform\WebformMessageManagerInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests webform message manager.
 *
 * @group webform
 */
class WebformMessageManagerTest extends UnitTestCase {

  /**
   * Test webform message manager.
   */
  public function testMessageManager() {
    // Mock webform.
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('getSettings')
      ->will($this->returnCallback(function () {
        return [
          WebformMessageManagerInterface::DRAFT_PENDING_SINGLE => '{single}',
          WebformMessageManagerInterface::DRAFT_PENDING_MULTIPLE => '[none]',
        ];
      }));

    // Mock url.
    $url = $this->createMock('\Drupal\Core\Url');
    $url->method('toString')
      ->willReturn('http://example.com/');

    /* ********************************************************************** */

    // Mock current user.
    $current_user = $this->createMock(AccountInterface::class);

    // Mock config factory.
    $config_factory = $this->getConfigFactoryStub(
      [
        'webform.settings' => [
          'html_editor.tidy' => TRUE,
          'html_editor.element_format' => '',
          'element.allowed_tags' => 'p',
        ],
      ]
    );

    // Mock webform submission storage.
    $webform_submission_storage = $this->createMock(WebformSubmissionStorageInterface::class);

    // Mock entity type manager.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->willReturnMap([
        ['webform_submission', $webform_submission_storage],
      ]);

    // Mock logger.
    $logger = $this->createMock(LoggerInterface::class);

    // Mock renderer.
    $renderer = $this->createMock(RendererInterface::class);

    // Mock messenger.
    $messenger = $this->createMock(MessengerInterface::class);

    // Mock webform request handler.
    $request_handler = $this->createMock(WebformRequestInterface::class);
    $request_handler->method('getUrl')
      ->willReturn($url);

    // Mock webform token manager.
    $token_manager = $this->createMock(WebformTokenManagerInterface::class);
    $token_manager->method('replace')
      ->will($this->returnCallback(function ($text) {
        return $text;
      }));

    // Mock Drupal's container.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('config.factory', $config_factory);
    \Drupal::setContainer($container);

    // Create webform message manager.
    $message_manager = new WebformMessageManager(
      $current_user,
      $config_factory,
      $entity_type_manager,
      $logger,
      $renderer,
      $messenger,
      $request_handler,
      $token_manager);

    // Set message manager mock webform.
    $message_manager->setWebform($webform);

    /* ********************************************************************** */

    // Check custom single message.
    $expected = [
      '#theme' => 'webform_html_editor_markup',
      '#markup' => '{single}',
      '#allowed_tags' => [0 => 'p'],
    ];
    $result = $message_manager->get(WebformMessageManagerInterface::DRAFT_PENDING_SINGLE);
    $this->assertEquals($expected, $result);

    // Check [none] for multiple message returns an empty string.
    $result = $message_manager->get(WebformMessageManagerInterface::DRAFT_PENDING_MULTIPLE);
    $this->assertFalse($result);
  }

}
