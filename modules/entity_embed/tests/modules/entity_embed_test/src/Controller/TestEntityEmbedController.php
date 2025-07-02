<?php

declare(strict_types=1);

namespace Drupal\entity_embed_test\Controller;

use Drupal\embed\Controller\EmbedController;
use Drupal\filter\FilterFormatInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller to allow testing of error handling of Entity Embed in text editors.
 */
class TestEntityEmbedController extends EmbedController {

  /**
   * {@inheritdoc}
   */
  public function preview(Request $request, FilterFormatInterface $filter_format) {
    if (\Drupal::state()->get('entity_embed_test.preview.throw_error', FALSE)) {
      throw new NotFoundHttpException();
    }
    return parent::preview($request, $filter_format);
  }

}
