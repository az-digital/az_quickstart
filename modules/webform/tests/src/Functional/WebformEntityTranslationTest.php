<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Core\Serialization\Yaml;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform translation.
 *
 * @group webform
 */
class WebformEntityTranslationTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'webform', 'webform_ui', 'webform_test_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Place blocks.
    $this->placeBlocks();

    // Create filters.
    $this->createFilters();
  }

  /**
   * Tests settings translate.
   */
  public function testSettingsTranslate() {
    $assert_session = $this->assertSession();

    // Login admin user.
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/admin/structure/webform/config/translate/fr/add');

    // Check custom HTML source and translation.
    $mail_default_body_html = \Drupal::config('webform.settings')->get('mail.default_body_html');
    $assert_session->responseContains('<span lang="en">' . $mail_default_body_html . '</span>');
    $this->assertCssSelect('textarea[name="translation[config_names][webform.settings][settings][default_form_open_message][value][value]"]');

    // Check custom YAML source and translation.
    $this->assertCssSelect('#edit-source-config-names-webformsettings-test-types textarea.js-webform-codemirror.yaml');
    $this->assertCssSelect('textarea.js-webform-codemirror.yaml[name="translation[config_names][webform.settings][test][types]"]');

    // Check custom plain text source and translation.
    $this->assertCssSelect('#edit-source-config-names-webformsettings-mail-default-body-text textarea.js-webform-codemirror.text');
    $this->assertCssSelect('textarea.js-webform-codemirror.text[name="translation[config_names][webform.settings][mail][default_body_text]"]');
  }

  /**
   * Tests webform translate.
   */
  public function testWebformTranslate() {
    $assert_session = $this->assertSession();

    // Login admin user.
    $this->drupalLogin($this->rootUser);

    // Set [site:name] to 'Test Website' and translate it into Spanish.
    $this->drupalGet('/admin/config/system/site-information');
    $edit = ['site_name' => 'Test Website'];
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('/admin/config/system/site-information/translate/es/add');
    $edit = ['translation[config_names][system.site][name]' => 'Sitio web de prueba'];
    $this->submitForm($edit, 'Save translation');

    /** @var \Drupal\webform\WebformTranslationManagerInterface $translation_manager */
    $translation_manager = \Drupal::service('webform.translation_manager');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_translation');
    $elements_raw = \Drupal::config('webform.webform.test_translation')->get('elements');
    $elements = Yaml::decode($elements_raw);

    // Check translate tab.
    $this->drupalGet('/admin/structure/webform/manage/test_translation');
    $assert_session->responseContains('>Translate<');

    // Check translations.
    $this->drupalGet('/admin/structure/webform/manage/test_translation/translate');
    $assert_session->linkByHrefExists('/webform/test_translation');
    $assert_session->linkByHrefExists('/es/webform/test_translation');
    $assert_session->linkByHrefNotExists('/fr/webform/test_translation');
    $assert_session->linkByHrefExists('/admin/structure/webform/manage/test_translation/translate/es/edit');

    // Check Spanish translation.
    $this->drupalGet('/admin/structure/webform/manage/test_translation/translate/es/edit');
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][title]', 'Prueba: Traducción');

    // Check processed text translation.
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][processed_text][text][value]', '<p><strong>Algún texto</strong></p>');

    // Check textfield translation.
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][textfield][title]', 'Campo de texto');

    // Check select with options translation.
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][select_options][title]', 'Seleccione (opciones)');

    // Check select with custom options translation.
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][select_custom][title]', 'Seleccione (personalizado)');
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][select_custom][options][4]', 'Las cuatro');
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][select_custom][other__option_label]', 'Número personalizado…');

    // Check image select translation.
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][webform_image_select][title]', 'Seleccionar imagen');
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][webform_image_select][images][kitten_1][text]', 'Lindo gatito 1');
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][webform_image_select][images][kitten_1][src]', 'http://placekitten.com/220/200');

    // Check details translation.
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][details][title]', 'Detalles');

    // Check markup translation.
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][markup][markup][value][value]', 'Esto es un poco de marcado HTML.');

    // Check custom composite translation.
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][composite][title]', 'Compuesto');
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][composite][element][first_name][title]', 'Nombre');
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][composite][element][last_name][title]', 'Apellido');
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][composite][element][age][title]', 'Edad');
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][composite][element][age][field_suffix]', 'años. antiguo');

    // Check address translation.
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][address][title]', 'Dirección');
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][address][address__title]', 'Dirección');

    // Check computed token translation.
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][computed_token][title]', 'Computado (token)');

    // Check action translation.
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][actions][title]', 'Enviar botón (s)');
    $assert_session->fieldValueEquals('translation[config_names][webform.webform.test_translation][elements][actions][submit__label]', 'Enviar mensaje');

    // Check form builder is not translated.
    $this->drupalGet('/es/admin/structure/webform/manage/test_translation');
    $assert_session->linkExists('Text field');
    $assert_session->linkNotExists('Campo de texto');

    // Check form builder is not translated when reset.
    $this->drupalGet('/es/admin/structure/webform/manage/test_translation');
    $this->submitForm([], 'Reset');
    $assert_session->linkExists('Text field');
    $assert_session->linkNotExists('Campo de texto');

    // Check element edit form is not translated.
    $this->drupalGet('/es/admin/structure/webform/manage/test_translation/element/textfield/edit');
    $assert_session->fieldValueEquals('properties[title]', 'Text field');
    $assert_session->fieldValueNotEquals('properties[title]', 'Campo de texto');

    // Check translated webform options.
    $this->drupalGet('/es/webform/test_translation');
    $assert_session->responseContains('<label for="edit-textfield">Campo de texto</label>');
    $assert_session->responseContains('<option value="1">Uno</option>');
    $assert_session->responseContains('<option value="4">Las cuatro</option>');

    // Check translated webform custom composite.
    $this->drupalGet('/es/webform/test_translation');
    $assert_session->responseContains('<label>Compuesto</label>');
    $assert_session->responseContains('<th class="composite-table--first_name webform-multiple-table--first_name">Nombre</th>');
    $assert_session->responseContains('<th class="composite-table--last_name webform-multiple-table--last_name">Apellido</th>');
    $assert_session->responseContains('<th class="composite-table--age webform-multiple-table--age">Edad</th>');
    $assert_session->responseContains('<span class="field-suffix">años. antiguo</span>');

    // Check translated webform address.
    $this->drupalGet('/es/webform/test_translation');
    $assert_session->responseContains('<span class="visually-hidden fieldset-legend">Dirección</span>');
    $assert_session->responseContains('<label for="edit-address-address">Dirección</label>');
    $assert_session->responseContains('<label for="edit-address-address-2">Dirección 2</label>');
    $assert_session->responseContains('<label for="edit-address-city">Ciudad / Pueblo</label>');
    $assert_session->responseContains('<label for="edit-address-state-province">Estado / Provincia</label>');
    $assert_session->responseContains('<label for="edit-address-postal-code">ZIP / Código Postal</label>');
    $assert_session->responseContains('<label for="edit-address-country">Acciones de país</label>');

    // Check translated webform token.
    $assert_session->responseContains('Site name: Sitio web de prueba');

    // Check that webform is not translated into French.
    $this->drupalGet('/fr/webform/test_translation');
    $assert_session->responseContains('<label for="edit-textfield">Text field</label>');
    $assert_session->responseContains('<option value="1">One</option>');
    $assert_session->responseContains('<option value="4">Four</option>');
    $assert_session->responseContains('Site name: Test Website');

    // Check that French config elements returns the default languages elements.
    // Please note: This behavior might change.
    $translation_element = $translation_manager->getElements($webform, 'fr', TRUE);
    $this->assertEquals($elements, $translation_element);

    // Translate [site:name] into French.
    $this->drupalGet('/admin/config/system/site-information/translate/fr/add');
    $edit = ['translation[config_names][system.site][name]' => 'Site Web de test'];
    $this->submitForm($edit, 'Save translation');

    // Check default elements.
    $this->drupalGet('/admin/structure/webform/manage/test_translation/translate/fr/add');

    // Check email body's default textfield.
    $this->assertCssSelect('textarea[name="translation[config_names][webform.webform.test_translation][handlers][email_confirmation][settings][body]"]');

    // Enable set body to custom HTML.
    $handler = $webform->getHandler('email_confirmation');
    $configuration = $handler->getConfiguration();
    $configuration['settings']['body'] = '<strong>some HTML</strong>';
    $handler->setConfiguration($configuration);
    $webform->save();

    // Check default elements with HTML.
    $this->drupalGet('/admin/structure/webform/manage/test_translation/translate/fr/add');

    // Check custom HTML Editor.
    $this->assertCssSelect('textarea[name="translation[config_names][webform.webform.test_translation][description][value][value]"]');

    // Check email body's HTML Editor.
    $this->assertCssSelect('textarea[name="translation[config_names][webform.webform.test_translation][handlers][email_confirmation][settings][body][value][value]"]');

    // Enable twig.
    $handler = $webform->getHandler('email_confirmation');
    $configuration = $handler->getConfiguration();
    $configuration['settings']['twig'] = TRUE;
    $handler->setConfiguration($configuration);
    $webform->save();

    // Check email body's Twig Editor.
    $this->drupalGet('/admin/structure/webform/manage/test_translation/translate/fr/add');
    $this->assertCssSelect('textarea.js-webform-codemirror.twig[name="translation[config_names][webform.webform.test_translation][handlers][email_confirmation][settings][body]"]');

    // Check customized maxlengths.
    $this->assertCssSelect('input[name$="[title]"]');
    $this->assertNoCssSelect('input[name$="[title][maxlength"]');
    $this->assertCssSelect('input[name$="[submission_label]"]');
    $this->assertNoCssSelect('input[name$="[submission_label]"][maxlength]');

    // Create French translation.
    $this->drupalGet('/admin/structure/webform/manage/test_translation/translate/fr/add');
    $edit = ['translation[config_names][webform.webform.test_translation][elements][textfield][title]' => 'French'];
    $this->submitForm($edit, 'Save translation');

    // Check French translation.
    $this->drupalGet('/fr/webform/test_translation');
    $assert_session->responseContains('<label for="edit-textfield">French</label>');
    $assert_session->responseContains('Site name: Site Web de test');

    // Check translations.
    $this->drupalGet('/admin/structure/webform/manage/test_translation/translate');
    $assert_session->responseContains('<a href="' . base_path() . 'webform/test_translation"><strong>English (original)</strong></a>');
    $assert_session->responseContains('<a href="' . base_path() . 'es/webform/test_translation" hreflang="es">Spanish</a>');
    $assert_session->responseContains('<a href="' . base_path() . 'fr/webform/test_translation" hreflang="fr">French</a>');

    // Check French config elements only contains translated properties and
    // custom properties are removed.
    $translation_element = $translation_manager->getElements($webform, 'fr', TRUE);
    $this->assertEquals(['textfield' => ['#title' => 'French']], $translation_element);

    /* ********************************************************************** */
    // Submissions.
    /* ********************************************************************** */

    // Check English table headers are not translated.
    $this->drupalGet('/admin/structure/webform/manage/test_translation/results/submissions');
    $assert_session->responseContains('>Text field<');
    $assert_session->responseContains('>Select (options)<');
    $assert_session->responseContains('>Select (custom)<');
    $assert_session->responseContains('>Composite<');

    // Check Spanish table headers are translated.
    $this->drupalGet('/es/admin/structure/webform/manage/test_translation/results/submissions');
    $assert_session->responseContains('>Campo de texto<');
    $assert_session->responseContains('>Seleccione (opciones)<');
    $assert_session->responseContains('>Seleccione (personalizado)<');
    $assert_session->responseContains('>Compuesto<');

    // Create translated submissions.
    $this->drupalGet('/webform/test_translation');
    $edit = ['textfield' => 'English Submission'];
    $this->submitForm($edit, 'Send message');

    $this->drupalGet('/es/webform/test_translation');
    $edit = ['textfield' => 'Spanish Submission'];
    $this->submitForm($edit, 'Enviar mensaje');

    $this->drupalGet('/fr/webform/test_translation');
    $edit = ['textfield' => 'French Submission'];
    $this->submitForm($edit, 'Send message');

    // Check computed token is NOT translated for each language because only
    // one language can be loaded for a config translation.
    $this->drupalGet('/admin/structure/webform/manage/test_translation/results/submissions');
    $assert_session->responseContains('Site name: Test Website');
    $assert_session->responseNotContains('Site name: Sitio web de prueba');
    $assert_session->responseNotContains('Site name: Sitio web de prueba');

    /* ********************************************************************** */
    // Site wide language.
    /* ********************************************************************** */

    // Make sure the site language is English (en).
    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'en')->save();
    drupal_flush_all_caches();

    $language_manager = \Drupal::languageManager();

    $this->drupalGet('/webform/test_translation', ['language' => $language_manager->getLanguage('en')]);
    $assert_session->responseContains('<label for="edit-textfield">Text field</label>');

    // Check Spanish translation.
    $this->drupalGet('/webform/test_translation', ['language' => $language_manager->getLanguage('es')]);
    $assert_session->responseContains('<label for="edit-textfield">Campo de texto</label>');

    // Check French translation.
    $this->drupalGet('/webform/test_translation', ['language' => $language_manager->getLanguage('fr')]);
    $assert_session->responseContains('<label for="edit-textfield">French</label>');

    // Change site language to French (fr).
    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'fr')->save();

    // Check English translation.
    $this->drupalGet('/webform/test_translation', ['language' => $language_manager->getLanguage('en')]);
    $assert_session->responseContains('<label for="edit-textfield">Text field</label>');

    // Check Spanish translation.
    $this->drupalGet('/webform/test_translation', ['language' => $language_manager->getLanguage('es')]);
    $assert_session->responseContains('<label for="edit-textfield">Campo de texto</label>');

    // Check French translation.
    $this->drupalGet('/webform/test_translation', ['language' => $language_manager->getLanguage('fr')]);
    $assert_session->responseContains('<label for="edit-textfield">French</label>');

    /* ********************************************************************** */

    // Make sure the site language is English (en).
    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'en')->save();
    drupal_flush_all_caches();

    // Duplicate translated webform.
    $edit = [
      'title' => 'DUPLICATE',
      'id' => 'duplicate',
    ];
    $this->drupalGet('/admin/structure/webform/manage/test_translation/duplicate');
    $this->submitForm($edit, 'Save');

    // Check duplicate English translation.
    $this->drupalGet('/webform/duplicate', ['language' => $language_manager->getLanguage('en')]);
    $assert_session->responseContains('<label for="edit-textfield">Text field</label>');

    // Check duplicate Spanish translation.
    $this->drupalGet('/webform/duplicate', ['language' => $language_manager->getLanguage('es')]);
    $assert_session->responseContains('<label for="edit-textfield">Campo de texto</label>');

    // Check duplicate French translation.
    $this->drupalGet('/webform/duplicate', ['language' => $language_manager->getLanguage('fr')]);
    $assert_session->responseContains('<label for="edit-textfield">French</label>');

    // Check that add webform display langcode dropdown.
    $this->drupalGet('/admin/structure/webform/add');
    $assert_session->fieldValueEquals('langcode', 'en');

    // Check that add webform display langcode dropdown is NOT display when there is one language..
    ConfigurableLanguage::load('es')->delete();
    ConfigurableLanguage::load('fr')->delete();
    $this->drupalGet('/admin/structure/webform/add');
    $assert_session->fieldNotExists('langcode');
  }

  /**
   * Tests webform translate variants.
   */
  public function testTranslateVariants() {
    $assert_session = $this->assertSession();

    // Check English webform.
    $this->drupalGet('/webform/test_translation');
    $assert_session->responseContains('<label for="edit-textfield">Text field</label>');
    $assert_session->responseContains('<label for="edit-select-options">Select (options)</label>');

    // Check English webform with test variant.
    $this->drupalGet('/webform/test_translation', ['query' => ['variant' => 'test']]);
    $assert_session->responseContains('<label for="edit-textfield">Text field - Variant</label>');
    $assert_session->responseContains('<label for="edit-select-options">Select (options)</label>');

    // Check Spanish webform.
    $this->drupalGet('/es/webform/test_translation');
    $assert_session->responseContains('<label for="edit-textfield">Campo de texto</label>');
    $assert_session->responseContains('<label for="edit-select-options">Seleccione (opciones)</label>');

    // Check Spanish webform with test variant.
    $this->drupalGet('/es/webform/test_translation', ['query' => ['variant' => 'test']]);
    $assert_session->responseContains('<label for="edit-textfield">Campo de texto - Variante</label>');
    $assert_session->responseContains('<label for="edit-select-options">Seleccione (opciones)</label>');

    // Check French (not translated) webform.
    $this->drupalGet('/fr/webform/test_translation');
    $assert_session->responseContains('<label for="edit-textfield">Text field</label>');
    $assert_session->responseContains('<label for="edit-select-options">Select (options)</label>');

    // Check French (not translated) webform with test variant.
    $this->drupalGet('/fr/webform/test_translation', ['query' => ['variant' => 'test']]);
    $assert_session->responseContains('<label for="edit-textfield">Text field - Variant</label>');
    $assert_session->responseContains('<label for="edit-select-options">Select (options)</label>');

    // Remove variant element and variants.
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_translation');
    $variants = $webform->getVariants();
    foreach ($variants as $variant) {
      $webform->deleteWebformVariant($variant);
    }
    $webform->deleteElement('variant');
    $webform->save();

    // Check English webform.
    $this->drupalGet('/webform/test_translation');
    $assert_session->responseContains('<label for="edit-textfield">Text field</label>');
    $assert_session->responseContains('<label for="edit-select-options">Select (options)</label>');

    // Check English webform with test variant.
    $this->drupalGet('/webform/test_translation', ['query' => ['variant' => 'test']]);
    $assert_session->responseContains('<label for="edit-textfield">Text field</label>');
    $assert_session->responseContains('<label for="edit-select-options">Select (options)</label>');

    // Check Spanish webform.
    $this->drupalGet('/es/webform/test_translation');
    $assert_session->responseContains('<label for="edit-textfield">Campo de texto</label>');
    $assert_session->responseContains('<label for="edit-select-options">Seleccione (opciones)</label>');

    // Check Spanish webform with test variant.
    $this->drupalGet('/es/webform/test_translation', ['query' => ['variant' => 'test']]);
    $assert_session->responseContains('<label for="edit-textfield">Campo de texto</label>');
    $assert_session->responseContains('<label for="edit-select-options">Seleccione (opciones)</label>');

    // Check French (not translated) webform.
    $this->drupalGet('/fr/webform/test_translation');
    $assert_session->responseContains('<label for="edit-textfield">Text field</label>');
    $assert_session->responseContains('<label for="edit-select-options">Select (options)</label>');

    // Check French (not translated) webform with test variant.
    $this->drupalGet('/fr/webform/test_translation', ['query' => ['variant' => 'test']]);
    $assert_session->responseContains('<label for="edit-textfield">Text field</label>');
    $assert_session->responseContains('<label for="edit-select-options">Select (options)</label>');
  }

}
