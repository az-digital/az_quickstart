<?php

namespace Drupal\media_library_form_element;

use Drupal\media_library\MediaLibraryOpenerInterface;
use Drupal\media_library\MediaLibraryState;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The media library opener for form elements.
 */
class MediaLibraryFormElementOpener implements MediaLibraryOpenerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MediaLibraryFormElementOpener constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(MediaLibraryState $state, AccountInterface $account) {
    $process_result = function ($result) {
      if ($result instanceof RefinableCacheableDependencyInterface) {
        $result->addCacheContexts(['url.query_args']);
      }
      return $result;
    };

    return $process_result(AccessResult::allowed());
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionResponse(MediaLibraryState $state, array $selected_ids) {
    $response = new AjaxResponse();

    $parameters = $state->getOpenerParameters();

    // Create a comma-separated list of media IDs, insert them in the hidden
    // field of the widget, and trigger the field update via the hidden submit
    // button.
    $widget_id = $parameters['field_widget_id'];
    $ids = implode(',', $selected_ids);

    $response
      ->addCommand(new InvokeCommand(NULL, 'setMediaUploadFieldValue', [$ids, "[data-media-library-form-element-value=\"$widget_id\"]"]))
      ->addCommand(new InvokeCommand("[data-media-library-form-element-update=\"$widget_id\"]", 'trigger', ['mousedown']))
      ->addCommand(new CloseModalDialogCommand(TRUE, '#modal-media-library'));

    return $response;
  }

}
