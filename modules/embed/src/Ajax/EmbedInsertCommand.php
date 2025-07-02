<?php

namespace Drupal\embed\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsTrait;

/**
 * AJAX command for inserting an embedded item in an editor.
 *
 * @ingroup ajax
 */
class EmbedInsertCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  use CommandWithAttachedAssetsTrait;

  /**
   * The content for the matched element(s).
   *
   * Either a render array or an HTML string.
   *
   * @var string|array
   */
  protected $content;

  /**
   * Constructs an EmbedInsertCommand object.
   *
   * @param string|array $content
   *   The content that will be inserted in the matched element(s), either a
   *   render array or an HTML string.
   */
  public function __construct($content) {
    $this->content = $content;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'embed_insert',
      'data' => $this->getRenderedContent(),
    ];
  }

}
