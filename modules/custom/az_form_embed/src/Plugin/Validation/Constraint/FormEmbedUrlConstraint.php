<?php

declare(strict_types=1);

namespace Drupal\az_form_embed\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks that a Form embed link is one a turned-on vendor accepts.
 *
 * @see \Drupal\az_form_embed\Plugin\media\Source\FormEmbed::getSourceFieldConstraints()
 */
#[Constraint(
  id: 'AzFormEmbedUrl',
  label: new TranslatableMarkup('Form embed link', [], ['context' => 'Validation']),
  type: ['string'],
)]
class FormEmbedUrlConstraint extends SymfonyConstraint {

  /**
   * Constructs a FormEmbedUrlConstraint.
   *
   * @param mixed $options
   *   The options (as associative array) or the value for the default option.
   * @param string $noVendorsMessage
   *   The message when no vendor is turned on.
   * @param string $refusedMessage
   *   The message when a vendor claims the link but refuses it.
   * @param string $unknownMessage
   *   The message when no turned-on vendor claims the link.
   * @param array|null $groups
   *   An array of validation groups.
   * @param mixed $payload
   *   Domain-specific data attached to a constraint.
   */
  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $noVendorsMessage = 'This site can\'t embed forms yet, because no form provider is turned on. Ask a site administrator to turn one on, such as Slate.',
    public $refusedMessage = 'This @vendor link can\'t be used because @reason. A link that works looks like this: @example',
    public $unknownMessage = 'This site can\'t embed a form from this link. It accepts forms from @vendors. A link that works looks like this: @example',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
