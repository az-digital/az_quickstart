<?php

namespace Drupal\paragraphs_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for paragraphs admin.
 */
class ParagraphController extends ControllerBase {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new ParagraphController object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * Deletes paragraph content.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   Paragraph to be deleted.
   */
  public function deleteParagraph(ParagraphInterface $paragraph) {
    $pid = $paragraph->id();
    $paragraph->delete();
    $this->messenger->addMessage($this->t('Paragraph @pid deleted.', ['@pid' => $pid]));

    // Redirect back to view node page.
    return $this->redirect('view.paragraphs.page_admin_paragraphs');
  }

}
