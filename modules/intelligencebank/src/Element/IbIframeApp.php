<?php

namespace Drupal\ib_dam\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\Render\Attribute\FormElement;

/**
 * Provides a intelligencebank iframe app form element.
 */
#[FormElement('ib_dam_app')]
class IbIframeApp extends FormElementBase {

  const APP_URL  = 'https://ucprod.intelligencebank.com/app/';
  const TEST_URL = 'https://ucstaging.intelligencebank.com/app/';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = [
      '#input' => TRUE,
      '#markup' => '',
      '#process' => [[$class, 'processElement']],
      '#pre_render' => [[$class, 'preRenderElement']],
      '#theme_wrappers' => ['form_element'],
      '#file_extensions' => [],
      '#allow_embed' => FALSE,
      '#debug_response' => FALSE,
      '#submit_selector' => NULL,
      '#messages' => [],
      '#attached' => [
        'library' => ['ib_dam/browser'],
      ],
    ];
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function processElement(&$element) {
    $element['#tree'] = TRUE;

    // Embedded search.
    $element['browser'] = [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'id' => Html::getUniqueId('ib-dam-asset-browser'),
        'src' => '',
        'class' => 'ib-dam-app-browser',
        'width' => '100%',
        'frameborder' => 0,
        'allow' => 'clipboard-write; clipboard-read',
      ],
      '#prefix' => '<div class="ib-search-iframe-wrapper ib-dam-app-wrapper">',
      '#suffix' => '</div>',
    ];

    // Hold the response from the search.
    $element['response_items'] = [
      '#name' => 'ib_dam_app[response_items]',
      '#type' => 'hidden',
      '#default_value' => '',
    ];
    return $element;
  }

  /**
   * Add javascript settings for an element.
   */
  public static function preRenderElement(array $element) {
    $iframe_url = self::buildIframeUrl();

    $settings = [
      'host' => parse_url($iframe_url)['host'],
      'debug' => $element['#debug_response'],
      'allowEmbed' => $element['#allow_embed'],
      'submitSelector' => $element['#submit_selector'],
      'appUrl' => $iframe_url,
    ];
    $settings['fileExtensions'] = $element['#file_extensions'] ?? [];
    $settings['messages'] = $element['#messages'] ?? [];

    $element['#attached']['drupalSettings']['ib_dam']['browser'] = $settings;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $return['items'] = [];

    if ($input !== FALSE) {
      $user_input = NestedArray::getValue($form_state->getUserInput(), ['ib_dam_app']);

      if (!empty($user_input['response_items'])) {
        $return['items'] = [json_decode($user_input['response_items'])];
      }
      $form_state->setValueForElement($element, $return);
    }

    return $return;
  }

  /**
   * Check if test/staging mode is enabled.
   *
   * @return bool
   *   TRUE - if enabled, FALSE otherwise.
   */
  private static function isTestMode():bool {
    $config = \Drupal::config('ib_dam.settings');
    if ($config->get('staging')) {
      return TRUE;
    }
    return Settings::get('intelligencebank_is_test_mode', FALSE);
  }

  /**
   * Build iFrame URL.
   *
   * @return string
   *   iFrame URL.
   */
  private static function buildIframeUrl(): string {
    $query = \Drupal::request()->query->all();
    $config = \Drupal::config('ib_dam.settings');

    // Prepare list of params we want to pass within the URL.
    $params = [
      'app'                  => 'drupal',
      'enable_custom_url'    => $config->get('login_enable_custom_url') ? 'true' : 'false',
      'url'                  => $config->get('login_url'),
      'enable_browser_login' => $config->get('login_enable_browser_login') ? 'true' : 'false',
    ];

    if (!$config->get('allow_embedding')) {
      $params['app'] = 'drupal_no_public';
    }

    //dump($params['app'], $config->get('allow_embedding'), $query['no_public']);

    // Get base URL depending on mode.
    $url = self::isTestMode() ? static::TEST_URL : static::APP_URL;
    // Add query params.
    $url .= '?' . http_build_query($params);

    return $url;
  }

}
