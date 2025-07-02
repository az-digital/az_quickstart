<?php

namespace Drupal\redirect\Plugin\Validation\Constraint;

use Drupal\link\LinkItemInterface;
use Drupal\Core\Url;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Validation constraint for links receiving data allowed by its settings.
 *
 * @Constraint(
 *   id = "RedirectSourceLinkType",
 *   label = @Translation("Link data valid for redirect source link type.", context = "Validation"),
 * )
 */
class SourceLinkTypeConstraint extends Constraint implements ConstraintValidatorInterface {

  public $message = 'The URL %url is not valid.';

  /**
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return get_class($this);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (isset($value)) {
      $url_is_valid = TRUE;
      /** @var \Drupal\link\LinkItemInterface $link_item */
      $link_item = $value;
      $link_type = $link_item->getFieldDefinition()->getSetting('link_type');
      $url_string = $link_item->url;
      // Validate the url property.
      if ($url_string !== '') {
        try {
          // @todo This shouldn't be needed, but massageFormValues() may not
          //   run.
          $parsed_url = UrlHelper::parse($url_string);

          if (!empty($parsed_url['path'])) {
            $url = Url::fromUri('internal:' . $parsed_url['path']);

            if ($url->isExternal() && !UrlHelper::isValid($url_string, TRUE)) {
              $url_is_valid = FALSE;
            }
            elseif ($url->isExternal() && !($link_type & LinkItemInterface::LINK_EXTERNAL)) {
              $url_is_valid = FALSE;
            }
          }
        }
        catch (NotFoundHttpException $e) {
          $url_is_valid = FALSE;
        }
        catch (ResourceNotFoundException $e) {
          // User is creating a redirect from non existing path. This is not an
          // error state.
          $url_is_valid = TRUE;
        }
        catch (ParamNotConvertedException $e) {
          $url_is_valid = FALSE;
        }
      }
      if (!$url_is_valid) {
        $this->context->addViolation($this->message, ['%url' => $url_string]);
      }
    }
  }

}
