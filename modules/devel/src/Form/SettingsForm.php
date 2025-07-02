<?php

namespace Drupal\devel\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\devel\DevelDumperPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures devel settings.
 */
class SettingsForm extends ConfigFormBase {

  protected DevelDumperPluginManagerInterface $dumperManager;

  /**
   * The 'devel.settings' config object.
   */
  protected Config $config;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->dumperManager = $container->get('plugin.manager.devel_dumper');
    $instance->config = $container->get('config.factory')->getEditable('devel.settings');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'devel_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'devel.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL): array {
    $current_url = Url::createFromRequest($request);

    $form['page_alter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display $attachments array'),
      '#default_value' => $this->config->get('page_alter'),
      '#description' => $this->t('Display $attachments array from <a href="https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21theme.api.php/function/hook_page_attachments_alter/10">hook_page_attachments_alter()</a> in the messages area of each page.'),
    ];
    $form['raw_names'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display machine names of permissions and modules'),
      '#default_value' => $this->config->get('raw_names'),
      '#description' => $this->t('Display the language-independent machine names of the permissions in mouse-over hints on the <a href=":permissions_url">Permissions</a> page and the module base file names on the Permissions and <a href=":modules_url">Modules</a> pages.', [
        ':permissions_url' => Url::fromRoute('user.admin_permissions')->toString(),
        ':modules_url' => Url::fromRoute('system.modules_list')->toString(),
      ]),
    ];
    $form['rebuild_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rebuild the theme registry on every page load'),
      '#description' => $this->t('New templates, theme overrides, and changes to the theme.info.yml need the theme registry to be rebuilt in order to appear on the site.'),
      '#default_value' => $this->config->get('rebuild_theme'),
    ];

    $error_handlers = devel_get_handlers();
    $form['error_handlers'] = [
      '#type' => 'select',
      '#title' => $this->t('Error handlers'),
      '#options' => [
        DEVEL_ERROR_HANDLER_NONE => $this->t('None'),
        DEVEL_ERROR_HANDLER_STANDARD => $this->t('Standard Drupal'),
        DEVEL_ERROR_HANDLER_BACKTRACE_DPM => $this->t('Backtrace in the message area'),
        DEVEL_ERROR_HANDLER_BACKTRACE_KINT => $this->t('Backtrace above the rendered page'),
      ],
      '#multiple' => TRUE,
      '#default_value' => empty($error_handlers) ? DEVEL_ERROR_HANDLER_NONE : $error_handlers,
      '#description' => [
        [
          '#markup' => $this->t('Select the error handler(s) to use, in case you <a href=":choose">choose to show errors on screen</a>.', [':choose' => Url::fromRoute('system.logging_settings')->toString()]),
        ],
        [
          '#theme' => 'item_list',
          '#items' => [
            $this->t('<em>None</em> is a good option when stepping through the site in your debugger.'),
            $this->t('<em>Standard Drupal</em> does not display all the information that is often needed to resolve an issue.'),
            $this->t('<em>Backtrace</em> displays nice debug information when any type of error is noticed, but only to users with the %perm permission.', ['%perm' => $this->t('Access developer information')]),
          ],
        ],
        [
          '#markup' => $this->t('Depending on the situation, the theme, the size of the call stack and the arguments, etc., some handlers may not display their messages, or display them on the subsequent page. Select <em>Standard Drupal</em> <strong>and</strong> <em>Backtrace above the rendered page</em> to maximize your chances of not missing any messages.') . '<br />' .
          $this->t('Demonstrate the current error handler(s):') . ' ' .
          Link::fromTextAndUrl('notice', $current_url->setOption('query', ['demo' => 'notice']))->toString() . ', ' .
          Link::fromTextAndUrl('notice+warning', $current_url->setOption('query', ['demo' => 'warning']))->toString() . ', ' .
          Link::fromTextAndUrl('notice+warning+error', $current_url->setOption('query', ['demo' => 'error']))->toString() . ' (' .
          $this->t('The presentation of the @error is determined by PHP.', ['@error' => 'error']) . ')',
        ],
      ],
    ];

    $form['error_handlers']['#size'] = count($form['error_handlers']['#options']);
    if ($request->query->has('demo')) {
      if ($request->getMethod() === 'GET') {
        $this->demonstrateErrorHandlers($request->query->get('demo'));
      }

      $request->query->remove('demo');
    }

    $dumper = $this->config->get('devel_dumper');
    $default = $this->dumperManager->isPluginSupported($dumper) ? $dumper : $this->dumperManager->getFallbackPluginId('');

    $form['dumper'] = [
      '#type' => 'radios',
      '#title' => $this->t('Variables Dumper'),
      '#options' => [],
      '#default_value' => $default,
      '#description' => $this->t('Select the debugging tool used for formatting and displaying the variables inspected through the debug functions of Devel. <strong>NOTE</strong>: Some of these plugins require external libraries for to be enabled. Learn how install external libraries with <a href=":url">Composer</a>.', [
        ':url' => 'https://www.drupal.org/node/2404989',
      ]),
    ];

    foreach ($this->dumperManager->getDefinitions() as $id => $definition) {
      $form['dumper']['#options'][$id] = $definition['label'];

      $supported = $this->dumperManager->isPluginSupported($id);
      $form['dumper'][$id]['#disabled'] = !$supported;

      $form['dumper'][$id]['#description'] = [
        '#type' => 'inline_template',
        '#template' => '{{ description }}{% if not supported %}<div><small>{% trans %}<strong>Not available</strong>. You may need to install external dependencies for use this plugin.{% endtrans %}</small></div>{% endif %}',
        '#context' => [
          'description' => $definition['description'],
          'supported' => $supported,
        ],
      ];
    }

    // Allow custom debug filename for use in DevelDumperManager::debug()
    $default_file = $this->config->get('debug_logfile') ?: 'temporary://drupal_debug.txt';
    $form['debug_logfile'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Debug Log File'),
      '#description' => $this->t('This is the log file that Devel functions such as ddm() write to. Use temporary:// to represent your systems temporary directory. Save with a blank filename to revert to the default.'),
      '#default_value' => $default_file,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $this->config
      ->set('page_alter', $values['page_alter'])
      ->set('raw_names', $values['raw_names'])
      ->set('error_handlers', $values['error_handlers'])
      ->set('rebuild_theme', $values['rebuild_theme'])
      ->set('devel_dumper', $values['dumper'])
      ->set('debug_logfile', $values['debug_logfile'] ?: 'temporary://drupal_debug.txt')
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Demonstrates the capabilities of the error handler.
   *
   * @param string $severity
   *   The severity level for which demonstrate the error handler capabilities.
   */
  protected function demonstrateErrorHandlers(string $severity): void {
    switch ($severity) {
      case 'notice':
        trigger_error('This is an example notice', E_USER_NOTICE);
        break;

      case 'warning':
        trigger_error('This is an example notice', E_USER_NOTICE);
        trigger_error('This is an example warning', E_USER_WARNING);
        break;

      case 'error':
        trigger_error('This is an example notice', E_USER_NOTICE);
        trigger_error('This is an example warning', E_USER_WARNING);
        trigger_error('This is an example error', E_USER_ERROR);
    }
  }

}
