<?php

namespace Drupal\Tests\workbench_access\Kernel;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\UiHelperTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests workbench access with inline entity form.
 *
 * @group workbench_access
 *
 * @requires module inline_entity_form
 */
class InlineEntityFormTest extends KernelTestBase implements FormInterface {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use StringTranslationTrait;
  use UiHelperTrait;
  use UserCreationTrait;
  use WorkbenchAccessTestTrait;

  /**
   * Access vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * Entity access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'workbench_access',
    'entity_test',
    'taxonomy',
    'options',
    'user',
    'system',
    'node',
    'filter',
    'field',
    'text',
    'inline_entity_form',
  ];

  /**
   * Sets up the tZest.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig(['filter', 'node', 'workbench_access', 'system']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('section_association');
    $this->installSchema('system', ['sequences']);
    $node_type = $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);
    $this->vocabulary = $this->setUpVocabulary();
    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('node');
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $this->vocabulary->id());
    $this->scheme = $this->setUpTaxonomyScheme($node_type, $this->vocabulary);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_workbench_access_inline_entity_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['inline_entity_form'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'node',
      '#bundle' => 'page',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Tests that workbench_access integrates with inline_entity_form.
   */
  public function testInlineEntityForm() {
    // Get uid 1 out of the way.
    $this->createUser();
    // Set up an editor and log in as them.
    $editor = $this->setUpEditorUser();
    $this->container->get('current_user')->setAccount($editor);

    // Set up some roles and terms for this test.
    // Create terms and roles.
    $staff_term = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();
    $super_staff_term = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Super staff',
    ]);
    $super_staff_term->save();
    $base_term = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Editor',
    ]);
    $base_term->save();
    $editor->{WorkbenchAccessManagerInterface::FIELD_NAME} = 'editorial_section:' . $base_term->id();
    $editor->save();

    $staff_rid = $this->createRole([], 'staff');
    $super_staff_rid = $this->createRole([], 'super_staff');
    // Set the role -> term mapping.
    $this->container->get('workbench_access.role_section_storage')->addRole($this->scheme, $staff_rid, [$staff_term->id()]);
    $this->container->get('workbench_access.role_section_storage')->addRole($this->scheme, $super_staff_rid, [$super_staff_term->id()]);

    $markup = $this->getRenderedFormAsCrawler();

    // Assert we can't see the options yet.
    $this->assertNotContains($staff_term->getName(), $markup->filter('option')->extract(['_text']));
    $this->assertNotContains($super_staff_term->getName(), $markup->filter('option')->extract(['_text']));

    // Add the staff role and check the option exists.
    $editor->addRole($staff_rid);
    $editor->save();
    // We need to forcefully clear the user section storage cache.
    $user_section = $this->container->get('workbench_access.user_section_storage');
    $reflection = new \ReflectionClass($user_section);
    $property = $reflection->getProperty('userSectionCache');
    $property->setAccessible(TRUE);
    $property->setValue($user_section, []);
    $markup = $this->getRenderedFormAsCrawler();
    $this->assertContains($staff_term->getName(), $markup->filter('option')->extract(['_text']));
  }

  /**
   * Gets rendered form as crawler.
   *
   * @return \Symfony\Component\DomCrawler\Crawler
   *   Crawler wrapping the rendered form.
   */
  protected function getRenderedFormAsCrawler() {
    $form_builder = $this->container->get('form_builder');
    $form = $form_builder->getForm($this);

    $markup = $this->container->get('renderer')->renderInIsolation($form);
    return new Crawler((string) $markup);
  }

  /**
   * Stub function for the getEntity() method of an entity form.
   */
  public function getEntity() {
    // Just create a dummy page node.
    return $this->createNode();
  }

}
