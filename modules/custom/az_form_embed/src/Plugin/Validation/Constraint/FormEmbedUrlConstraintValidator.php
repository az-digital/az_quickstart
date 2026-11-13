<?php

declare(strict_types=1);

namespace Drupal\az_form_embed\Plugin\Validation\Constraint;

use Drupal\az_form_embed\FormEmbedVendorManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates a Form embed link against the turned-on vendors.
 *
 * Drupal runs this whenever a Form embed item is validated, for example when
 * an editor clicks Add in the Media Library or saves the item's edit form.
 *
 * The message names the vendor the link was meant for, where it can. For
 * example, a Slate link with a person parameter gets Slate's reason and a
 * Slate example, not a list of every vendor's examples.
 *
 * This is the save-time check. The formatter checks the link again before it
 * renders, because a link can be saved without validation, for example by a
 * migration.
 */
class FormEmbedUrlConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a FormEmbedUrlConstraintValidator.
   *
   * @param \Drupal\az_form_embed\FormEmbedVendorManager $vendorManager
   *   Finds the turned-on vendors.
   */
  public function __construct(
    protected FormEmbedVendorManager $vendorManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.az_form_embed_vendor'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    assert($constraint instanceof FormEmbedUrlConstraint);
    $media = $value->getEntity();
    if (!$media instanceof MediaInterface) {
      return;
    }
    $url = $media->getSource()->getSourceFieldValue($media);
    // If the link is empty, stop. Rationale: the field is required, so that
    // check already reports it.
    if (!is_string($url) || $url === '') {
      return;
    }

    $vendors = $this->vendorManager->getVendors();
    if ($vendors === []) {
      $this->context->addViolation($constraint->noVendorsMessage);
      return;
    }

    $claiming = $this->vendorManager->getClaimingVendor($url);
    if ($claiming === NULL) {
      $first = reset($vendors);
      $this->context->addViolation($constraint->unknownMessage, [
        '@vendors' => implode(' or ', array_map(fn ($vendor) => $vendor->label(), $vendors)),
        '@example' => $first->examples()[0] ?? '',
      ]);
      return;
    }

    $reason = NULL;
    if ($claiming->resolve($url, $reason) === NULL) {
      $this->context->addViolation($constraint->refusedMessage, [
        '@vendor' => $claiming->label(),
        '@reason' => $claiming->explain($reason ?? ''),
        '@example' => $claiming->examples()[0] ?? '',
      ]);
    }
  }

}
