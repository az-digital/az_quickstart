<?php

namespace Drupal\redirect\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Plugin implementation of the 'link' widget for the redirect module.
 *
 * Note that this field is meant only for the source field of the redirect
 * entity as it drops validation for non existing paths.
 *
 * @FieldWidget(
 *   id = "redirect_source",
 *   label = @Translation("Redirect source"),
 *   field_types = {
 *     "link"
 *   },
 *   settings = {
 *     "placeholder_url" = "",
 *     "placeholder_title" = ""
 *   }
 * )
 */
class RedirectSourceWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_url_value = $items[$delta]->path;
    if ($items[$delta]->query) {
      $default_url_value .= '?' . http_build_query($items[$delta]->query);
    }
    $element['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      '#default_value' => $default_url_value,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
      // Add a trailing slash to make it more clear that a redirect should not
      // start with a leading slash.
      '#field_prefix' => \Drupal::request()->getSchemeAndHttpHost() . '/',
      '#attributes' => ['data-disable-refocus' => 'true'],
    ];

    // If creating new URL add checks.
    if ($items->getEntity()->isNew()) {
      $element['status_box'] = [
        '#prefix' => '<div id="redirect-link-status">',
        '#suffix' => '</div>',
      ];

      $source_path = $form_state->getValue(['redirect_source', 0, 'path']);
      if ($source_path) {
        $source_path = trim($source_path);

        // Warning about creating a redirect from a valid path.
        // @todo Hmm... exception driven logic. Find a better way how to
        //   determine if we have a valid path.
        try {
          \Drupal::service('router')->match('/' . $form_state->getValue(['redirect_source', 0, 'path']));

          $url = Url::fromRoute('entity.path_alias.add_form');
          if ($url->access()) {
            $element['status_box'][]['#markup'] = '<div class="messages messages--warning">' . $this->t('The source path %path is likely a valid path. It is preferred to <a href="@url-alias">create URL aliases</a> for existing paths rather than redirects.', [
              '%path' => $source_path,
              '@url-alias' => $url->toString(),
            ]) . '</div>';
          }
        }
        catch (ResourceNotFoundException $e) {
          // Do nothing, expected behaviour.
        }
        catch (AccessDeniedHttpException $e) {
          // @todo Source lookup results in an access denied, deny access?
        }

        // Warning about the path being already redirected.
        $parsed_url = UrlHelper::parse($source_path);
        $path = $parsed_url['path'] ?? NULL;
        if (!empty($path)) {
          /** @var \Drupal\redirect\RedirectRepository $repository */
          $repository = \Drupal::service('redirect.repository');
          $redirects = $repository->findBySourcePath($path);
          if (!empty($redirects)) {
            $redirect = array_shift($redirects);
            $element['status_box'][]['#markup'] = '<div class="messages messages--warning">' . $this->t('The base source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?', ['%source' => $source_path, '@edit-page' => $redirect->toUrl('edit-form')->toString()]) . '</div>';
          }
        }
      }

      $element['path']['#ajax'] = [
        'callback' => 'redirect_source_link_get_status_messages',
        'wrapper' => 'redirect-link-status',
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    // It is likely that the url provided for this field is not existing and
    // so the logic in the parent method did not set any defaults. Just run
    // through all url values and add defaults.
    foreach ($values as &$value) {
      if (!empty($value['path'])) {
        // In case we have query process the url.
        if (strpos($value['path'], '?') !== FALSE) {
          $url = UrlHelper::parse($value['path']);
          $value['path'] = $url['path'];
          $value['query'] = $url['query'];
        }
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    $entity_type = $field_definition->getTargetEntityTypeId();
    return $entity_type === 'redirect';
  }

}
