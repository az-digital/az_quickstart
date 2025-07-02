<?php

namespace Drupal\metatag;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\token\TokenEntityMapperInterface;

/**
 * Token handling service. Uses core token service or contributed Token.
 */
class MetatagToken {

  use StringTranslationTrait;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Token entity type mapper service.
   *
   * @var \Drupal\token\TokenEntityMapperInterface
   */
  protected $tokenEntityMapper;

  /**
   * Constructs a new MetatagToken object.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   * @param \Drupal\token\TokenEntityMapperInterface $token_entity_mapper
   *   The token entity type mapper service.
   */
  public function __construct(Token $token, TokenEntityMapperInterface $token_entity_mapper) {
    $this->token = $token;
    $this->tokenEntityMapper = $token_entity_mapper;
  }

  /**
   * Wrapper for the Token module's string parsing.
   *
   * @param string $string
   *   The string to parse.
   * @param array $data
   *   Arguments for token->replace().
   * @param array $options
   *   Any additional options necessary.
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleable_metadata
   *   (optional) An object to which static::generate() and the hooks and
   *   functions that it invokes will add their required bubbleable metadata.
   *
   * @return string
   *   The processed string.
   */
  public function replace($string, array $data = [], array $options = [], BubbleableMetadata $bubbleable_metadata = NULL): string {
    // Set default requirements for metatag unless options specify otherwise.
    $options = $options + [
      'clear' => TRUE,
    ];

    $replaced = $this->token->replace($string, $data, $options, $bubbleable_metadata);

    // Ensure that there are no double-slash sequences due to empty token
    // values.
    $replaced = preg_replace('/(?<!:)(?<!)\/+\//', '/', $replaced);

    return $replaced;
  }

  /**
   * Gatekeeper function to direct to either the core or contributed Token.
   *
   * @param array $token_types
   *   The token types to filter the tokens list by. Defaults to an empty array.
   * @param bool $image_help
   *   Whether to include an extra message about how image field tokens should
   *   be processed.
   *
   * @return array
   *   If token module is installed, a popup browser plus a help text. If not
   *   only the help text.
   */
  public function tokenBrowser(array $token_types = [], $image_help = FALSE): array {
    $form = [];

    $form['intro_text'] = [
      '#markup' => '<p>' . $this->t('Use tokens to avoid redundant meta data and search engine penalization. For example, a \'keyword\' value of "example" will be shown on all content using this configuration, whereas using the [node:field_keywords] automatically inserts the "keywords" values from the current entity (node, term, etc).') . '</p>',
      // Define a specific weight.
      '#weight' => -10,
    ];
    if ($image_help) {
      $form['image_help'] = [
        '#markup' => '<p>' . $this->t('To use tokens to image fields, the image field on that entity bundle (content type, term, etc) must have the "Token" display settings enabled, the image field must not be hidden, and it must be set to output as an image, e.g. using the "Thumbnail" field formatter. It is also recommended to use an appropriate image style that resizes the image rather than output the original image; see individual meta tag descriptions for size recommendations.') . '</strong></p>',
        '#weight' => -9,
      ];
    }

    // Normalize token types.
    if (!empty($token_types)) {
      $token_types = array_map(function ($value) {
        return $this->tokenEntityMapper->getTokenTypeForEntityType($value, TRUE);
      }, $token_types);
    }

    $form['tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => $token_types,
      '#global_types' => TRUE,
      '#show_nested' => FALSE,
    ];

    return $form;
  }

}
