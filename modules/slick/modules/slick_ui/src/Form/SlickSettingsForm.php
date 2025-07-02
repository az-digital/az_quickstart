<?php

namespace Drupal\slick_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\Form\BlazyConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Slick admin settings form.
 */
class SlickSettingsForm extends BlazyConfigFormBase {

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->manager = $container->get('slick.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'slick_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['slick.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('slick.settings');

    $form['library'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Library to use'),
      '#description'   => $this->t('<a href=":url1">Slick</a> is the original library by Ken Wheeler. <a href=":url2">Accessible Slick</a> is a forked library with accessibility enhancements from Accessibility360. Be sure to clear cache if things broken when changing this. <b>Warning</b>! Accessible Slick has breaking changes, <a href=":url3">read more</a>.', [
        ':url1' => 'https://kenwheeler.github.io/slick/',
        ':url2' => 'https://accessible360.github.io/accessible-slick/',
        ':url3' => 'https://www.drupal.org/project/slick/issues/3196529',
      ]),
      '#options'       => [
        'slick' => $this->t('Slick'),
        'accessible-slick' => $this->t('Accessible Slick'),
      ],
      '#default_value' => $config->get('library'),
    ];

    $form['module_css'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable Slick module slick.theme.css'),
      '#description'   => $this->t('Uncheck to permanently disable the module slick.theme.css, normally included along with skins.'),
      '#default_value' => $config->get('module_css'),
      '#prefix'        => $this->t("Note! Slick doesn't need Slick UI to run. It is always safe to uninstall Slick UI once done with optionsets."),
    ];

    $form['slick_css'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable Slick library slick-theme.css'),
      '#description'   => $this->t('Uncheck to permanently disable the optional slick-theme.css, normally included along with skins.'),
      '#default_value' => $config->get('slick_css'),
    ];

    $form['sitewide'] = [
      '#type'         => 'select',
      '#title'        => $this->t('Load slick globally'),
      '#empty_option' => $this->t('- None -'),
      '#options'      => [
        1 => $this->t('With default initializer'),
        2 => $this->t('With vanilla initializer'),
        3 => $this->t('Without initializer'),
      ],
      '#description' => $this->t('Warning! <br>- Leave it empty if usages are for body texts only, please use shortcodes provided via Slick filter instead. <br>- Not compatible with BigPipe module due to assets re-ordering issue, see https://drupal.org/node/3211873. Meaning may break any stylings provided by this module.<ol><li><b>With default initializer</b> will include the module slick.load.min.js as the initializer normally used by the module formatters or views identified by <code>.slick</code> selector. Only if you need consistent styling/ skins, classes, media player, lightboxes, and markups. Works immediately at body texts.</li><li><b>With vanilla initializer</b> will include the module slick.vanilla.min.js as the minimal initializer identified by <code>.slick-vanilla</code> selector. Default skins, media player, lightboxes are unusable. Be sure to add CSS class <code>.slick-vanilla</code> to your Slick. Recommended to not interfere or co-exist with module formatters/ views. Works immediately at body texts.</li><li><b>Without initializer</b> will load only the main libraries. No module skins, no module JS. It is all yours -- broken unless you initialize it.</li></ol> This will include Slick anywhere except admin pages. Only do this if you need Slick where PHP or Twig is not available such as at body texts. Otherwise use the provided API instead. Implements <code>hook_slick_attach_alter</code> to include additional libraries such as skins, mousewheel, colorbox, etc. At any rate, you can inject options via <code>data-slick</code> attribute, or custom JavaScript. You can also include them at your theme, it is just a convenient way to avoid hard-coding at every theme changes. Check out slick.html.twig for more markups.'),
      '#default_value' => $config->get('sitewide'),
    ];

    $default = $config->get('sitewide') == 0 || $config->get('sitewide') == 1;
    $form['preview'] = $default ? $this->withInitializer() : $this->withoutInitializer();

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('slick.settings')
      ->set('library', $form_state->getValue('library'))
      ->set('slick_css', $form_state->getValue('slick_css'))
      ->set('module_css', $form_state->getValue('module_css'))
      ->set('sitewide', (int) $form_state->getValue('sitewide'))
      ->save();

    // Invalidate the library discovery cache to update new assets.
    // @todo update for D12 $this->libraryDiscovery->clearCachedDefinitions();
    $this->configFactory->clearStaticCache();

    parent::submitForm($form, $form_state);
  }

  /**
   * Provides sample with default Slick markups.
   */
  private function withInitializer() {
    $items = [];

    foreach (['One', 'Two', 'Three'] as $key) {
      $img = '<img src="https://drupal.org/files/' . $key . '.gif" />';
      $items[] = [
        'slide'   => ['#markup' => $img],
        'caption' => ['title' => $key],
      ];
    }

    $build = [
      'items' => $items,
      'settings' => ['skin' => 'classic', 'layout' => 'bottom'],
      'options' => ['arrows' => TRUE, 'dots' => TRUE],
    ];

    $content = $this->manager->build($build);
    return $this->preview($content);
  }

  /**
   * Provides sample without default Slick markups.
   */
  private function withoutInitializer() {
    $config  = $this->config('slick.settings');
    $vanilla = $config->get('sitewide') == 2;
    $items   = [];

    foreach (['One', 'Two', 'Three'] as $key) {
      $items[] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => '<img src="https://drupal.org/files/' . $key . '.gif" />',
      ];
    }

    $class  = $vanilla ? 'vanilla' : 'whatever';
    $config = "{'arrows': true, 'dots': true}";
    $prefix = 'class="slick-' . $class . '" data-slick="' . $config . '"';
    $suffix = "<blockquote><pre>&lt;div class=&quot;slick-" . $class . "&quot; data-slick=&quot;{'arrows': true, 'dots': true}&quot;&gt;
    &lt;div&gt;&lt;img src=&quot;https://drupal.org/files/One.gif&quot; /&gt;&lt;/div&gt;
    &lt;div&gt;&lt;img src=&quot;https://drupal.org/files/Two.gif&quot; /&gt;&lt;/div&gt;
    &lt;div&gt;&lt;img src=&quot;https://drupal.org/files/Three.gif&quot; /&gt;&lt;/div&gt;
&lt;/div&gt;</pre></blockquote>";

    return $this->preview($items, $prefix, $suffix);
  }

  /**
   * Provides sample w/o default Slick markups.
   */
  private function preview($content, $prefix = '', $suffix = '') {
    $config = $this->config('slick.settings');
    $unload = $config->get('sitewide') == 2 || $config->get('sitewide') == 3;
    $attach = $this->manager->attach([
      '_unload'  => $unload,
      '_vanilla' => $config->get('sitewide') == 2,
    ]);

    if (empty($suffix)) {
      $suffix = "<blockquote><pre>&lt;div class=&quot;slick&quot; data-slick=&quot;{'arrows': true, 'dots': true}&quot;&gt;
  &lt;div class=&quot;slick__slider&quot;&gt;
    &lt;div class=&quot;slick__slide&quot;&gt;&lt;img src=&quot;https://drupal.org/files/One.gif&quot; /&gt;&lt;/div&gt;
    &lt;div class=&quot;slick__slide&quot;&gt;&lt;img src=&quot;https://drupal.org/files/Two.gif&quot; /&gt;&lt;/div&gt;
    &lt;div class=&quot;slick__slide&quot;&gt;&lt;img src=&quot;https://drupal.org/files/Three.gif&quot; /&gt;&lt;/div&gt;
  &lt;/div&gt;
  &lt;nav class=&quot;slick__arrow&quot; &gt; &lt;/nav&gt;
&lt;/div&gt;</pre></blockquote>";
    }

    return [
      '#type'     => 'inline_template',
      '#template' => '{{ prefix | raw }}{{ stage }}{{ suffix | raw }}',
      '#context'  => [
        'stage'  => $content,
        'prefix' => '<div style="background: rgb(52, 152, 219);"><div style="margin: 30px auto; max-width: 350px; min-height: 240px; text-align: center;" ' . $prefix . '>',
        'suffix' => '</div></div>' . $suffix,
      ],
      '#attached' => $attach,
    ];
  }

}
