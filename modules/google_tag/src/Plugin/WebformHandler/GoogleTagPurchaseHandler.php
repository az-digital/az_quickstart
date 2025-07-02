<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\WebformHandler;

use Drupal\google_tag\EventCollectorInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fires event on webform purchase.
 *
 * @WebformHandler(
 *   id = "google_tag_webform_purchase",
 *   label = @Translation("Google Tag: Purchase event"),
 *   category = @Translation("Google Tag"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class GoogleTagPurchaseHandler extends WebformHandlerBase {

  /**
   * Collector.
   *
   * @var \Drupal\google_tag\EventCollectorInterface
   */
  private EventCollectorInterface $eventCollector;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->eventCollector = $container->get('google_tag.event_collector');
    return $instance;
  }

  /**
   * Fires an event on webform purchase.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission.
   * @param bool $update
   *   Update flag.
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    if ($state === WebformSubmissionInterface::STATE_COMPLETED) {
      // @todo this is where it feels kind of broken, because the handler would
      //   be configured in the webform and have the tokens.
      $this->eventCollector->addEvent('webform_purchase', [
        'submission' => $webform_submission,
      ]);
    }
  }

}
