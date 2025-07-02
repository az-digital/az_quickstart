<?php

namespace Drupal\webform_test_ajax\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'webform_test_block_context' block.
 *
 * @Block(
 *   id = "webform_test_ajax_block",
 *   admin_label = @Translation("Webform Ajax"),
 *   category = @Translation("Webform Test")
 * )
 */
class WebformTestAjaxBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
    $instance->redirectDestination = $container->get('redirect.destination');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $webforms = Webform::loadMultiple();

    // Ajax links.
    $ajax_links = [];
    foreach ($webforms as $webform_id => $webform) {
      if (strpos($webform_id, 'test_ajax') !== 0 && $webform_id !== 'test_form_wizard_long_100') {
        continue;
      }

      if (!in_array($webform_id, ['test_ajax_confirmation_page', 'test_ajax_confirmation_url', 'test_ajax_confirmation_url_msg'])) {
        // Add destination to Ajax webform that don't redirect to confirmation
        // page or URL.
        $route_options = ['query' => $this->redirectDestination->getAsArray()];
      }
      else {
        $route_options = [];
      }

      $ajax_links[$webform_id] = [
        'title' => $this->t('Open @webform_id', ['@webform_id' => $webform_id]),
        'url' => $webform->toUrl('canonical', $route_options),
        'attributes' => [
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 800,
          ]),
          'class' => [
            'use-ajax',
          ],
        ],
      ];
    }

    // Inline links.
    $webform = Webform::load('contact');
    $inline_links = [];
    $inline_links['webform'] = [
      'title' => $this->t('Open Contact'),
      'url' => $webform->toUrl('canonical'),
      'attributes' => [
        'class' => ['webform-dialog', 'webform-dialog-normal'],
      ],
    ];
    $inline_links['source_entity'] = [
      'title' => $this->t('Open Contact with Source Entity'),
      'url' => $webform->toUrl('canonical', ['query' => ['source_entity_type' => 'ENTITY_TYPE', 'source_entity_id' => 'ENTITY_ID']]),
      'attributes' => [
        'class' => ['webform-dialog', 'webform-dialog-normal'],
      ],
    ];
    $inline_links['javascript'] = [
      'title' => "Drupal.webformOpenDialog('" . $webform->toUrl('canonical')->toString() . "', 'webform-dialog-normal'); return false;",
      'url' => Url::fromRoute('<none>'),
      'attributes' => [
        'onclick' => "Drupal.webformOpenDialog('" . $webform->toUrl('canonical')->toString() . "', 'webform-dialog-normal'); return false;",
      ],
    ];

    // Dialog links.
    $dialog_links = [];
    $webform_style_guide = Webform::load('example_style_guide');
    if ($webform_style_guide) {
      $dialog_links['style_guide'] = [
        'title' => $this->t('Open style guide'),
        'url' => $webform_style_guide->toUrl('canonical'),
        'attributes' => [
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
          'data-dialog-options' => Json::encode([
            'width' => 600,
            'dialogClass' => 'ui-dialog-off-canvas webform-off-canvas',
          ]),
          'class' => [
            'use-ajax',
          ],
        ],
      ];
    }
    $build = [];
    if ($ajax_links) {
      $build['ajax'] = [
        '#prefix' => '<h3>' . $this->t('Ajax links') . '</h3>',
        '#theme' => 'links',
        '#links' => $ajax_links,
      ];
    }
    if ($inline_links) {
      $build['inline'] = [
        '#prefix' => '<h3>' . $this->t('Inline (Global) links') . '</h3>',
        '#theme' => 'links',
        '#links' => $inline_links,
      ];
    }
    if ($dialog_links) {
      $build['dialog'] = [
        '#prefix' => '<h3>' . $this->t('Dialog/Offcanvas links') . '</h3>',
        '#theme' => 'links',
        '#links' => $dialog_links,
      ];
    }
    $build['#attached']['library'][] = 'webform/webform.dialog';
    $build['#attached']['drupalSettings']['webform']['dialog']['options'] = $this->configFactory->get('webform.settings')->get('settings.dialog_options');
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
