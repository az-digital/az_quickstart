<?php

namespace Drupal\extlink\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the extlink settings form.
 */
class ExtlinkAdminSettingsForm extends ConfigFormBase {

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, protected RendererInterface $renderer) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'extlink_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('extlink.settings');
    $renderer = $this->renderer;

    $form['extlink_exclude_admin_routes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable on admin routes.'),
      '#default_value' => $config->get('extlink_exclude_admin_routes'),
      '#description' => $this->t('Whether the extlink module should be disabled on admin routes.'),
    ];

    $form['extlink_use_external_js_file'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load exclusions and inclusions externally.'),
      '#default_value' => $config->get('extlink_use_external_js_file'),
      '#description' => $this->t('Whether the extlink JS settings should be added to the page via an external settings file. In the case of a large number of patterns, this will reduce the amount of markup added to each page.'),
    ];

    $form['warning'] = [
      '#type' => 'markup',
      '#markup' => 'The \'ext\' class will be auto added to all external links.',
    ];

    $form['extlink_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place an icon next to external links.'),
      '#return_value' => 'ext',
      '#default_value' => $config->get('extlink_class'),
      '#description' => $this->t('Places an <span class="ext"> </span>&nbsp; icon next to external links.'),
    ];

    $form['extlink_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('external link label'),
      '#default_value' => $config->get('extlink_label'),
      '#states' => [
        'visible' => [
          ':input[name="extlink_class"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_mailto_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place an icon next to mailto links.'),
      '#return_value' => 'mailto',
      '#default_value' => $config->get('extlink_mailto_class'),
      '#description' => $this->t('Places an <span class="mailto"> </span>&nbsp; icon next to mailto links.'),
    ];

    $form['extlink_mailto_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('mailto: label'),
      '#default_value' => $config->get('extlink_mailto_label'),
      '#states' => [
        'visible' => [
          ':input[name="extlink_mailto_class"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_tel_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place an icon next to tel: links.'),
      '#return_value' => 'tel',
      '#default_value' => $config->get('extlink_tel_class'),
      '#description' => $this->t('Places an <span class="tel"> </span>&nbsp; icon next to telephone links.'),
    ];

    $form['extlink_tel_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('tel: Label'),
      '#default_value' => $config->get('extlink_tel_label'),
      '#states' => [
        'visible' => [
          ':input[name="extlink_tel_class"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_img_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place an icon next to image links.'),
      '#default_value' => $config->get('extlink_img_class'),
      '#description' => $this->t('If checked, images wrapped in an anchor tag will be treated as external links.'),
    ];

    $form['extlink_use_font_awesome'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Font Awesome icons instead of images.'),
      '#default_value' => $config->get('extlink_use_font_awesome'),
      '#description' => $this->t('Add Font Awesome classes to the link as well as an "i" tag rather than images. <strong>Warning for this to work font awesome needs to be present.</strong>'),
    ];

    $form['extlink_font_awesome_classes'] = [
      '#type' => 'details',
      '#title' => $this->t('Font Awesome icon classes'),
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="extlink_use_font_awesome"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_font_awesome_classes']['links'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font Awesome External Links Classes'),
      '#default_value' => $config->get('extlink_font_awesome_classes.links') ?: 'fa fa-external-link',
      '#states' => [
        'visible' => [
          ':input[name="extlink_class"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_font_awesome_classes']['mailto'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font Awesome mailto Links Classes'),
      '#default_value' => $config->get('extlink_font_awesome_classes.mailto') ?: 'fa fa-envelope-o',
      '#states' => [
        'visible' => [
          ':input[name="extlink_mailto_class"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_font_awesome_classes']['tel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font Awesome Tel Class'),
      '#default_value' => $config->get('extlink_font_awesome_classes.tel') ?: 'fa fa-phone',
      '#states' => [
        'visible' => [
          ':input[name="extlink_tel_class"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_icon_placement'] = [
      '#type' => 'select',
      '#title' => $this->t('Where to place icon in reference to link.'),
      '#options' => [
        'before' => $this->t('Before'),
        'prepend' => $this->t('Prepend'),
        'append' => $this->t('Append'),
        'after' => $this->t('After'),
      ],
      '#return_value' => 'prepend',
      '#default_value' => $config->get('extlink_icon_placement'),
      '#description' => $this->t('Choose the location of the external link icon relative to the link. Before and after will place the graphic outside the link, while append and prepend will place the graphic inside the link.'),
    ];

    $form['extlink_prevent_orphan'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent text wrapping separating the last word from the icon.'),
      '#default_value' => $config->get('extlink_prevent_orphan'),
      '#description' => $this->t('This is done by wrapping the last word and the symbol into a non-breaking span. Note: This can have unwanted side effects, depending on your CSS.'),
      '#states' => [
        'visible' => [
          ':input[name="extlink_icon_placement"]' => ['value' => 'append'],
        ],
      ],
    ];

    $form['extlink_subdomains'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude links with the same primary domain.'),
      '#default_value' => $config->get('extlink_subdomains'),
      '#description' => $this->t("For example, a link from 'www.example.com' to the subdomain of 'my.example.com' would be excluded."),
    ];

    $form['extlink_target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open external links in a new window or tab.'),
      '#default_value' => $config->get('extlink_target'),
      '#description' => $this->t('A link will open in a new window or tab (depending on which web browser is used and how it is configured).'),
    ];

    $form['extlink_target_no_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not alter links with a default target value'),
      '#default_value' => $config->get('extlink_target_no_override'),
      '#description' => $this->t("A link that specifies target='_self' will not be changed to target='_blank'."),
      '#states' => [
        'visible' => [
          ':input[name="extlink_target"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_title_no_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not alter the title attribute on links.'),
      '#default_value' => $config->get('extlink_title_no_override'),
      '#description' => $this->t("If enabled, the title attribute will not be altered on external links."),
    ];

    $form['extlink_noreferrer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Tag external links as "noreferrer".'),
      '#default_value' => $config->get('extlink_noreferrer'),
    ];

    $form['extlink_nofollow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Tag external links as "no follow".'),
      '#default_value' => $config->get('extlink_nofollow'),
    ];

    $form['extlink_follow_no_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not override rel="follow", if set'),
      '#default_value' => $config->get('extlink_follow_no_override'),
      '#states' => [
        'visible' => [
          ':input[name="extlink_nofollow"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_alert'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display a pop-up warning when any external link is clicked.'),
      '#default_value' => $config->get('extlink_alert'),
    ];

    $form['extlink_alert_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text to display in the pop-up warning box.'),
      '#rows' => 3,
      '#default_value' => $config->get('extlink_alert_text'),
      '#description' => $this->t('Text to display in the pop-up external link warning box.'),
      '#wysiwyg' => FALSE,
      '#states' => [
        // Only show this field when user opts to display a pop-up warning.
        'visible' => [
          ':input[name="extlink_alert"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_hide_icons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide decorative icons from screen readers.'),
      '#default_value' => $config->get('extlink_hide_icons'),
      '#description' => $this->t("Enable it if the icon is redundant and used solely as visual progressive enhancement, this adds the aria-hidden='true' tag."),
    ];

    $form['extlink_additional_link_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional Link CSS Classes'),
      '#default_value' => $config->get('extlink_additional_link_classes'),
      '#description' => $this->t('Custom CSS classes that will be added onto every external link.'),
    ];

    $form['extlink_additional_mailto_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional MailTo CSS Classes'),
      '#default_value' => $config->get('extlink_additional_mailto_classes'),
      '#description' => $this->t('Custom CSS classes that will be added onto every external mailto link.'),
    ];

    $form['extlink_additional_tel_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional Tel CSS Classes'),
      '#default_value' => $config->get('extlink_additional_tel_classes'),
      '#description' => $this->t('Custom CSS classes that will be added onto every external tel link.'),
    ];

    $form['whitelisted_domains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Whitelisted domains.'),
      '#maxlength' => NULL,
      '#default_value' => implode(PHP_EOL, (array) $config->get('whitelisted_domains')),
      '#description' => $this->t('Enter a line-separated list of whitelisted domains (ie "example.com").'),
    ];

    $patterns = [
      '#theme' => 'item_list',
      '#items' => [
        ['#markup' => '<code>(example\.com)</code> ' . $this->t('Matches example.com.')],
        ['#markup' => '<code>(example\.com)|(example\.net)</code> ' . $this->t('Multiple patterns can be strung together by using a pipe. Matches example.com OR example.net.')],
        ['#markup' => '<code>(links/goto/[0-9]+/[0-9]+)</code> ' . $this->t('Matches links that go through the <a target="Links-module" href="https://drupal.org/project/links">Links module</a> redirect.')],
      ],
    ];

    $wildcards = [
      '#theme' => 'item_list',
      '#items' => [
        ['#markup' => '<code>.</code> ' . $this->t('Matches any character.')],
        ['#markup' => '<code>?</code> ' . $this->t('The previous character or set is optional.')],
        ['#markup' => '<code>\d</code> ' . $this->t('Matches any digit (0-9).')],
        ['#markup' => '<code>[a-z]</code> ' . $this->t('Brackets may be used to match a custom set of characters. This matches any alphabetic letter.')],
      ],
    ];

    $form['patterns'] = [
      '#type' => 'details',
      '#title' => $this->t('Pattern matching.'),
      '#description' =>
      '<p>' . $this->t('External links uses patterns (regular expressions) to match the "href" property of links.') . '</p>' .
      $this->t('Here are some common patterns.') .
      $renderer->render($patterns) .
      $this->t('Common special characters:') .
      $renderer->render($wildcards) .
      '<p>' . $this->t('All special characters (<code>@characters</code>) must also be escaped with backslashes. Patterns are not case-sensitive. Any <a target="pattern supported by JavaScript" href="https://www.javascriptkit.com/javatutors/redev2.shtml">pattern supported by JavaScript</a> may be used.', ['@characters' => '^ $ . ? ( ) | * +']) . '</p>',
      '#open' => FALSE,
    ];

    $form['patterns']['extlink_exclude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude links matching the pattern.'),
      '#maxlength' => NULL,
      '#default_value' => $config->get('extlink_exclude'),
      '#description' => $this->t('Enter a regular expression for links that you wish to exclude from being considered external.'),
    ];

    $form['patterns']['extlink_include'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Include links matching the pattern.'),
      '#maxlength' => NULL,
      '#default_value' => $config->get('extlink_include'),
      '#description' => $this->t('Enter a regular expression for internal links that you wish to be considered external.'),
    ];

    $form['patterns']['extlink_exclude_noreferrer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude links matching the pattern from having "noreferrer" applied.'),
      '#maxlength' => NULL,
      '#default_value' => $config->get('extlink_exclude_noreferrer'),
      '#description' => $this->t('Enter a regular expression for links that you wish to exclude from having the "noreferrer" attribute added to.'),
      '#states' => [
        'visible' => [
          ':input[name="extlink_noreferrer"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['css_matching'] = [
      '#tree' => FALSE,
      '#type' => 'fieldset',
      '#title' => $this->t('CSS Matching.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' =>
      '<p>' . $this->t('Use CSS selectors to exclude entirely or only look inside explicitly specified classes and IDs for external links.') . '</p>',
    ];

    $form['css_matching']['extlink_css_exclude'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude links inside these CSS selectors.'),
      '#maxlength' => NULL,
      '#default_value' => $config->get('extlink_css_exclude'),
      '#description' => $this->t('Enter a comma-separated list of CSS selectors (ie "#block-block-2 .content, ul.menu").'),
    ];

    $form['css_matching']['extlink_css_include'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Include links inside these CSS selectors.'),
      '#maxlength' => NULL,
      '#default_value' => $config->get('extlink_css_include', ''),
      '#description' => $this->t('Enter a comma-separated list of CSS selectors (ie "#block-block-2 .content, ul.menu").'),
    ];

    $form['css_matching']['extlink_css_explicit'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Only look for links inside these CSS selectors.'),
      '#maxlength' => NULL,
      '#default_value' => $config->get('extlink_css_explicit'),
      '#description' => $this->t('Enter a comma-separated list of CSS selectors (ie "#block-block-2 .content, ul.menu").'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    $whitelisted_domains = explode(PHP_EOL, $values['whitelisted_domains']);
    $whitelisted_domains = array_map('trim', $whitelisted_domains);
    $whitelisted_domains = array_filter($whitelisted_domains, function ($value) {
      return !empty($value);
    });

    $this->config('extlink.settings')
      ->set('extlink_use_external_js_file', $values['extlink_use_external_js_file'])
      ->set('extlink_exclude_admin_routes', $values['extlink_exclude_admin_routes'])
      ->set('extlink_include', $values['extlink_include'])
      ->set('extlink_exclude', $values['extlink_exclude'])
      ->set('extlink_alert_text', $values['extlink_alert_text'])
      ->set('extlink_alert', $values['extlink_alert'])
      ->set('extlink_hide_icons', $values['extlink_hide_icons'])
      ->set('extlink_target', $values['extlink_target'])
      ->set('extlink_target_no_override', $values['extlink_target_no_override'])
      ->set('extlink_title_no_override', $values['extlink_title_no_override'])
      ->set('extlink_nofollow', $values['extlink_nofollow'])
      ->set('extlink_noreferrer', $values['extlink_noreferrer'])
      ->set('extlink_additional_link_classes', $values['extlink_additional_link_classes'])
      ->set('extlink_additional_mailto_classes', $values['extlink_additional_mailto_classes'])
      ->set('extlink_additional_tel_classes', $values['extlink_additional_tel_classes'])
      ->set('extlink_follow_no_override', $values['extlink_follow_no_override'])
      ->set('extlink_subdomains', $values['extlink_subdomains'])
      ->set('extlink_mailto_class', $values['extlink_mailto_class'])
      ->set('extlink_mailto_label', $values['extlink_mailto_label'])
      ->set('extlink_tel_class', $values['extlink_tel_class'])
      ->set('extlink_tel_label', $values['extlink_tel_label'])
      ->set('extlink_img_class', $values['extlink_img_class'])
      ->set('extlink_class', $values['extlink_class'])
      ->set('extlink_label', $values['extlink_label'])
      ->set('extlink_css_exclude', $values['extlink_css_exclude'])
      ->set('extlink_css_include', $values['extlink_css_include'])
      ->set('extlink_css_explicit', $values['extlink_css_explicit'])
      ->set('extlink_use_font_awesome', $values['extlink_use_font_awesome'])
      ->set('extlink_icon_placement', $values['extlink_icon_placement'])
      ->set('extlink_prevent_orphan', $values['extlink_prevent_orphan'])
      ->set('extlink_use_font_awesome', $values['extlink_use_font_awesome'])
      ->set('extlink_font_awesome_classes.links', $values['extlink_font_awesome_classes']['links'])
      ->set('extlink_font_awesome_classes.mailto', $values['extlink_font_awesome_classes']['mailto'])
      ->set('extlink_font_awesome_classes.tel', $values['extlink_font_awesome_classes']['tel'])
      ->set('whitelisted_domains', $whitelisted_domains)
      ->set('extlink_exclude_noreferrer', $values['extlink_exclude_noreferrer'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(): array {
    return ['extlink.settings'];
  }

}
