<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform CodeMirror element.
 *
 * @group webform
 */
class WebformElementCodeMirrorTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_codemirror'];

  /**
   * Tests CodeMirror element.
   */
  public function testCodeMirror() {
    $assert_session = $this->assertSession();

    /* ********************************************************************** */
    // code:text.
    /* ********************************************************************** */

    // Check Text.
    $this->drupalGet('/webform/test_element_codemirror');
    $assert_session->responseContains('<label for="edit-text-basic">text_basic</label>');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-text-basic" class="js-webform-codemirror webform-codemirror text form-textarea" data-webform-codemirror-mode="text/plain" id="edit-text-basic" name="text_basic" rows="5" cols="60">Hello</textarea>');

    // Check Text with no wrap.
    $this->drupalGet('/webform/test_element_codemirror');
    $assert_session->responseContains('<label for="edit-text-basic-no-wrap">text_basic_no_wrap</label>');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-text-basic-no-wrap" wrap="off" class="js-webform-codemirror webform-codemirror text form-textarea" data-webform-codemirror-mode="text/plain" id="edit-text-basic-no-wrap" name="text_basic_no_wrap" rows="5" cols="60">');

    /* ********************************************************************** */
    // code:yaml.
    /* ********************************************************************** */

    // Check YAML.
    $this->drupalGet('/webform/test_element_codemirror');
    $assert_session->responseContains('<label for="edit-yaml-basic">yaml_basic</label>');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-yaml-basic" class="js-webform-codemirror webform-codemirror yaml form-textarea" data-webform-codemirror-mode="text/x-yaml" id="edit-yaml-basic" name="yaml_basic" rows="5" cols="60">test: hello</textarea>');

    // Check default value decoding.
    $this->drupalGet('/webform/test_element_codemirror');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("yaml_basic: 'test: hello'
yaml_array:
  one: One
  two: Two
  three: Three
yaml_indexed_array:
  - one
yaml_indexed_associative_array:
  - one: One
yaml_decode_value:
  test: hello");

    // Check invalid YAML.
    $this->drupalGet('/webform/test_element_codemirror');
    $edit = ['yaml_basic' => "'not: valid"];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">yaml_basic</em> is not valid.');

    // Check valid YAML.
    $this->drupalGet('/webform/test_element_codemirror');
    $edit = ['yaml_basic' => 'is: valid'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('<em class="placeholder">yaml_basic</em> is not valid.');

    /* ********************************************************************** */
    // code:html.
    /* ********************************************************************** */

    // Check HTML.
    $this->drupalGet('/webform/test_element_codemirror');
    $assert_session->responseContains('<label for="edit-html-basic">html_basic</label>');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-html-basic" class="js-webform-codemirror webform-codemirror html form-textarea" data-webform-codemirror-mode="text/html" id="edit-html-basic" name="html_basic" rows="5" cols="60">&lt;b&gt;Hello&lt;/b&gt;</textarea>');

    // Check invalid HTML.
    $this->drupalGet('/webform/test_element_codemirror');
    $edit = ['html_basic' => "<b>bold</bold>"];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">html_basic</em> is not valid.');

    // Check valid HTML.
    $this->drupalGet('/webform/test_element_codemirror');
    $edit = ['html_basic' => '<b>bold</b>'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('<em class="placeholder">html_basic</em> is not valid.');

    /* ********************************************************************** */
    // code:twig.
    /* ********************************************************************** */

    // Check disabled Twig editor.
    $this->drupalGet('/webform/test_element_codemirror');
    $assert_session->responseContains('<label for="edit-twig-basic">twig_basic</label>');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-twig-basic" disabled="disabled" class="js-webform-codemirror webform-codemirror twig form-textarea" data-webform-codemirror-mode="twig" id="edit-twig-basic" name="twig_basic" rows="5" cols="60">
{% set value = &quot;Hello&quot; %}
{{ value }}
</textarea>');

    // Login and enable Twig editor.
    $this->drupalLogin($this->rootUser);

    // Check enabled Twig editor.
    $this->drupalGet('/webform/test_element_codemirror');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-twig-basic" class="js-webform-codemirror webform-codemirror twig form-textarea" data-webform-codemirror-mode="twig" id="edit-twig-basic" name="twig_basic" rows="5" cols="60">
{% set value = &quot;Hello&quot; %}
{{ value }}
</textarea>');

    // Check that enabled Twig editor can be updated.
    $this->drupalGet('/webform/test_element_codemirror');
    $edit = ['twig_basic' => 'Can edit Twig template.'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('Can edit Twig template.');

    // Check invalid Twig syntax.
    $this->drupalGet('/webform/test_element_codemirror');
    $edit = ['twig_basic' => "{{ value "];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">twig_basic</em> is not valid.');
    $assert_session->responseContains('Unclosed &quot;variable&quot; in');
  }

}
