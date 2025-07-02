<?php

namespace Drupal\devel_generate\Plugin\DevelGenerate;

use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Random;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\path_alias\PathAliasStorage;
use Drupal\user\RoleStorageInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ContentDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "content",
 *   label = @Translation("content"),
 *   description = @Translation("Generate a given number of content. Optionally delete current content."),
 *   url = "content",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "max_comments" = 0,
 *     "title_length" = 4,
 *     "add_type_label" = FALSE
 *   },
 *   dependencies = {
 *     "node",
 *   },
 * )
 */
class ContentDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The node storage.
   */
  protected NodeStorageInterface $nodeStorage;

  /**
   * The node type storage.
   */
  protected EntityStorageInterface $nodeTypeStorage;

  /**
   * The user storage.
   */
  protected UserStorageInterface $userStorage;

  /**
   * The url generator service.
   */
  protected UrlGeneratorInterface $urlGenerator;

  /**
   * The alias storage.
   */
  protected PathAliasStorage $aliasStorage;

  /**
   * The date formatter service.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Provides system time.
   */
  protected TimeInterface $time;

  /**
   * Database connection.
   */
  protected Connection $database;

  /**
   * The extension path resolver service.
   */
  protected ExtensionPathResolver $extensionPathResolver;

  /**
   * The role storage.
   */
  protected RoleStorageInterface $roleStorage;

  /**
   * The comment manager service.
   */
  protected ?CommentManagerInterface $commentManager;

  /**
   * The content translation manager.
   */
  protected ?ContentTranslationManagerInterface $contentTranslationManager;

  /**
   * The Drush batch flag.
   */
  protected bool $drushBatch = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $entity_type_manager = $container->get('entity_type.manager');

    // @phpstan-ignore ternary.alwaysTrue (False positive)
    $comment_manager = $container->has('comment.manager') ? $container->get('comment.manager') : NULL;

    // @phpstan-ignore ternary.alwaysTrue (False positive)
    $content_translation_manager = $container->has('content_translation.manager') ? $container->get('content_translation.manager') : NULL;

    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->nodeTypeStorage = $entity_type_manager->getStorage('node_type');
    $instance->nodeStorage = $entity_type_manager->getStorage('node');
    $instance->userStorage = $entity_type_manager->getStorage('user');
    $instance->urlGenerator = $container->get('url_generator');
    $instance->aliasStorage = $entity_type_manager->getStorage('path_alias');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->time = $container->get('datetime.time');
    $instance->database = $container->get('database');
    $instance->extensionPathResolver = $container->get('extension.path.resolver');
    $instance->roleStorage = $entity_type_manager->getStorage('user_role');
    $instance->commentManager = $comment_manager;
    $instance->contentTranslationManager = $content_translation_manager;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $types = $this->nodeTypeStorage->loadMultiple();

    if (empty($types)) {
      $create_url = $this->urlGenerator->generateFromRoute('node.type_add');
      $this->setMessage($this->t('You do not have any content types that can be generated. <a href=":create-type">Go create a new content type</a>', [':create-type' => $create_url]), 'error');
      return [];
    }

    $options = [];

    foreach ($types as $type) {
      $options[$type->id()] = [
        'type' => ['#markup' => $type->label()],
      ];
      if ($this->commentManager instanceof CommentManagerInterface) {
        $comment_fields = $this->commentManager->getFields('node');
        $map = [$this->t('Hidden'), $this->t('Closed'), $this->t('Open')];

        $fields = [];
        foreach ($comment_fields as $field_name => $info) {
          // Find all comment fields for the bundle.
          if (in_array($type->id(), $info['bundles'])) {
            $instance = FieldConfig::loadByName('node', $type->id(), $field_name);
            $default_value = $instance->getDefaultValueLiteral();
            $default_mode = reset($default_value);
            $fields[] = new FormattableMarkup('@field: @state', [
              '@field' => $instance->label(),
              '@state' => $map[$default_mode['status']],
            ]);
          }
        }

        // @todo Refactor display of comment fields.
        if ($fields !== []) {
          $options[$type->id()]['comments'] = [
            'data' => [
              '#theme' => 'item_list',
              '#items' => $fields,
            ],
          ];
        }
        else {
          $options[$type->id()]['comments'] = $this->t('No comment fields');
        }
      }
    }

    $header = [
      'type' => $this->t('Content type'),
    ];
    if ($this->commentManager instanceof CommentManagerInterface) {
      $header['comments'] = [
        'data' => $this->t('Comments'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ];
    }

    $form['node_types'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Delete all content</strong> in these content types before generating new content.'),
      '#default_value' => $this->getSetting('kill'),
    ];
    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('How many nodes would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];

    $options = [1 => $this->t('Now')];
    foreach ([3600, 86400, 604800, 2592000, 31536000] as $interval) {
      $options[$interval] = $this->dateFormatter->formatInterval($interval, 1) . ' ' . $this->t('ago');
    }

    $form['time_range'] = [
      '#type' => 'select',
      '#title' => $this->t('How far back in time should the nodes be dated?'),
      '#description' => $this->t('Node creation dates will be distributed randomly from the current time, back to the selected time.'),
      '#options' => $options,
      '#default_value' => 604800,
    ];

    $form['max_comments'] = [
      '#type' => $this->moduleHandler->moduleExists('comment') ? 'number' : 'value',
      '#title' => $this->t('Maximum number of comments per node.'),
      '#description' => $this->t('You must also enable comments for the content types you are generating. Note that some nodes will randomly receive zero comments. Some will receive the max.'),
      '#default_value' => $this->getSetting('max_comments'),
      '#min' => 0,
      '#access' => $this->moduleHandler->moduleExists('comment'),
    ];
    $form['title_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of words in titles'),
      '#default_value' => $this->getSetting('title_length'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 255,
    ];
    $form['skip_fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fields to leave empty'),
      '#description' => $this->t('Enter the field names as a comma-separated list. These will be skipped and have a default value in the generated content.'),
      '#default_value' => NULL,
    ];
    $form['base_fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base fields to populate'),
      '#description' => $this->t('Enter the field names as a comma-separated list. These will be populated.'),
      '#default_value' => NULL,
    ];
    $form['add_type_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prefix the title with the content type label.'),
      '#description' => $this->t('This will not count against the maximum number of title words specified above.'),
      '#default_value' => $this->getSetting('add_type_label'),
    ];
    $form['add_alias'] = [
      '#type' => 'checkbox',
      '#disabled' => !$this->moduleHandler->moduleExists('path'),
      '#description' => $this->t('Requires path.module'),
      '#title' => $this->t('Add an url alias for each node.'),
      '#default_value' => FALSE,
    ];
    $form['add_statistics'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add statistics for each node (node_counter table).'),
      '#default_value' => TRUE,
      '#access' => $this->moduleHandler->moduleExists('statistics'),
    ];

    // Add the language and translation options.
    $form += $this->getLanguageForm('nodes');

    // Add the user selection checkboxes.
    $author_header = [
      'id' => $this->t('User ID'),
      'user' => $this->t('Name'),
      'role' => $this->t('Role(s)'),
    ];

    $num_users = $this->database->select('users')
      ->countQuery()
      ->execute()
      ->fetchField();
    $author_form_limit = 50;
    $query = $this->database->select('users', 'u')
      ->fields('u', ['uid'])
      ->range(0, $author_form_limit)
      ->orderBy('uid');
    $uids = $query->execute()->fetchCol();

    $author_rows = [];
    foreach ($uids as $uid) {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->userStorage->load($uid);
      $author_rows[$user->id()] = [
        'id' => ['#markup' => $user->id()],
        'user' => ['#markup' => $user->getAccountName()],
        'role' => ['#markup' => implode(", ", $user->getRoles())],
      ];
    }

    $form['authors-wrap'] = [
      '#type' => 'details',
      '#title' => $this->t('Users'),
      '#open' => FALSE,
      '#description' => $this->t('Select users for randomly assigning as authors of the generated content.')
      . ($num_users > $author_form_limit ? ' ' . $this->t('The site has @num_users users, only the first @$author_form_limit are shown and selectable here.', ['@num_users' => $num_users, '@$author_form_limit' => $author_form_limit]) : ''),
    ];

    $form['authors-wrap']['authors'] = [
      '#type' => 'tableselect',
      '#header' => $author_header,
      '#options' => $author_rows,
    ];

    $role_rows = [];
    $roles = array_map(static fn($role): string => $role->label(), $this->roleStorage->loadMultiple());
    foreach ($roles as $role_id => $role_name) {
      $role_rows[$role_id] = [
        'id' => ['#markup' => $role_id],
        'role' => ['#markup' => $role_name],
      ];
    }

    $form['authors-wrap']['roles'] = [
      '#type' => 'tableselect',
      '#header' => [
        'id' => $this->t('Role ID'),
        'role' => $this->t('Role Description'),
      ],
      '#options' => $role_rows,
      '#prefix' => $this->t('Specify the roles that randomly selected authors must have.'),
      '#suffix' => $this->t('You can select users and roles. Authors will be randomly selected that match at least one of the criteria. Leave <em>both</em> selections unchecked to use a random selection of @$author_form_limit users, including Anonymous.', ['@$author_form_limit' => $author_form_limit]),
    ];

    $form['#redirect'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate(array $form, FormStateInterface $form_state): void {
    if (array_filter($form_state->getValue('node_types')) === []) {
      $form_state->setErrorByName('node_types', $this->t('Please select at least one content type'));
    }

    $skip_fields = is_null($form_state->getValue('skip_fields')) ? [] : self::csvToArray($form_state->getValue('skip_fields'));
    $base_fields = is_null($form_state->getValue('base_fields')) ? [] : self::csvToArray($form_state->getValue('base_fields'));
    $form_state->setValue('skip_fields', $skip_fields);
    $form_state->setValue('base_fields', $base_fields);
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values): void {
    if ($this->isBatch($values['num'], $values['max_comments'])) {
      $this->generateBatchContent($values);
    }
    else {
      $this->generateContent($values);
    }
  }

  /**
   * Generate content when not in batch mode.
   *
   * This method is used when the number of elements is under 50.
   */
  private function generateContent(array $values): void {
    $values['node_types'] = array_filter($values['node_types']);
    if (!empty($values['kill']) && $values['node_types']) {
      $this->contentKill($values);
    }

    if ($values['node_types'] !== []) {
      // Generate nodes.
      $this->develGenerateContentPreNode($values);
      $start = time();
      $values['num_translations'] = 0;
      for ($i = 1; $i <= $values['num']; ++$i) {
        $this->develGenerateContentAddNode($values);
        if (isset($values['feedback']) && $i % $values['feedback'] == 0) {
          $now = time();
          $options = [
            '@feedback' => $values['feedback'],
            '@rate' => ($values['feedback'] * 60) / ($now - $start),
          ];
          $this->messenger->addStatus(dt('Completed @feedback nodes (@rate nodes/min)', $options));
          $start = $now;
        }
      }
    }

    $this->setMessage($this->formatPlural($values['num'], 'Created 1 node', 'Created @count nodes'));
    if ($values['num_translations'] > 0) {
      $this->setMessage($this->formatPlural($values['num_translations'], 'Created 1 node translation', 'Created @count node translations'));
    }
  }

  /**
   * Generate content in batch mode.
   *
   * This method is used when the number of elements is 50 or more.
   */
  private function generateBatchContent(array $values): void {
    $operations = [];

    // Remove unselected node types.
    $values['node_types'] = array_filter($values['node_types']);
    // If it is drushBatch then this operation is already run in the
    // self::validateDrushParams().
    if (!$this->drushBatch) {
      // Setup the batch operations and save the variables.
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchContentPreNode', $values],
      ];
    }

    // Add the kill operation.
    if ($values['kill']) {
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchContentKill', $values],
      ];
    }

    // Add the operations to create the nodes.
    for ($num = 0; $num < $values['num']; ++$num) {
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchContentAddNode', $values],
      ];
    }

    // Set the batch.
    $batch = [
      'title' => $this->t('Generating Content'),
      'operations' => $operations,
      'finished' => 'devel_generate_batch_finished',
      'file' => $this->extensionPathResolver->getPath('module', 'devel_generate') . '/devel_generate.batch.inc',
    ];

    batch_set($batch);
    if ($this->drushBatch) {
      drush_backend_batch_process();
    }
  }

  /**
   * Batch wrapper for calling ContentPreNode.
   */
  public function batchContentPreNode($vars, array &$context): void {
    $context['results'] = $vars;
    $context['results']['num'] = 0;
    $context['results']['num_translations'] = 0;
    $this->develGenerateContentPreNode($context['results']);
  }

  /**
   * Batch wrapper for calling ContentAddNode.
   */
  public function batchContentAddNode(array $vars, array &$context): void {
    if ($this->drushBatch) {
      $this->develGenerateContentAddNode($vars);
    }
    else {
      $this->develGenerateContentAddNode($context['results']);
    }

    if (!isset($context['results']['num'])) {
      $context['results']['num'] = 0;
    }

    ++$context['results']['num'];
    if (!empty($vars['num_translations'])) {
      $context['results']['num_translations'] += $vars['num_translations'];
    }
  }

  /**
   * Batch wrapper for calling ContentKill.
   */
  public function batchContentKill(array $vars, array &$context): void {
    if ($this->drushBatch) {
      $this->contentKill($vars);
    }
    else {
      $this->contentKill($context['results']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []): array {
    $add_language = self::csvToArray($options['languages']);
    // Intersect with the enabled languages to make sure the language args
    // passed are actually enabled.
    $valid_languages = array_keys($this->languageManager->getLanguages(LanguageInterface::STATE_ALL));
    $values['add_language'] = array_intersect($add_language, $valid_languages);

    $translate_language = self::csvToArray($options['translations']);
    $values['translate_language'] = array_intersect($translate_language, $valid_languages);

    $values['add_type_label'] = $options['add-type-label'];
    $values['kill'] = $options['kill'];
    $values['feedback'] = $options['feedback'];
    $values['skip_fields'] = is_null($options['skip-fields']) ? [] : self::csvToArray($options['skip-fields']);
    $values['base_fields'] = is_null($options['base-fields']) ? [] : self::csvToArray($options['base-fields']);
    $values['title_length'] = 6;
    $values['num'] = array_shift($args);
    $values['max_comments'] = array_shift($args);
    // Do not use csvToArray for 'authors' because it removes '0' values.
    $values['authors'] = is_null($options['authors']) ? [] : explode(',', $options['authors']);
    $values['roles'] = self::csvToArray($options['roles']);

    $all_types = array_keys(node_type_get_names());
    $default_types = array_intersect(['page', 'article'], $all_types);
    $selected_types = self::csvToArray($options['bundles'] ?: $default_types);

    if ($selected_types === []) {
      throw new \Exception(dt('No content types available'));
    }

    $values['node_types'] = array_combine($selected_types, $selected_types);
    $node_types = array_filter($values['node_types']);

    if (!empty($values['kill']) && $node_types === []) {
      throw new \Exception(dt('To delete content, please provide the content types (--bundles)'));
    }

    // Checks for any missing content types before generating nodes.
    if (array_diff($node_types, $all_types) !== []) {
      throw new \Exception(dt('One or more content types have been entered that don\'t exist on this site'));
    }

    if ($this->isBatch($values['num'], $values['max_comments'])) {
      $this->drushBatch = TRUE;
      $this->develGenerateContentPreNode($values);
    }

    return $values;
  }

  /**
   * Determines if the content should be generated in batch mode.
   */
  protected function isBatch(int $content_count, int $comment_count): bool {
    return $content_count >= 50 || $comment_count >= 10;
  }

  /**
   * Deletes all nodes of given node types.
   *
   * @param array $values
   *   The input values from the settings form.
   */
  protected function contentKill(array $values): void {
    $nids = $this->nodeStorage->getQuery()
      ->condition('type', $values['node_types'], 'IN')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($nids)) {
      $nodes = $this->nodeStorage->loadMultiple($nids);
      $this->nodeStorage->delete($nodes);
      $this->setMessage($this->t('Deleted @count nodes.', ['@count' => count($nids)]));
    }
  }

  /**
   * Preprocesses $results before adding content.
   *
   * @param array $results
   *   Results information.
   */
  protected function develGenerateContentPreNode(array &$results): void {
    $authors = $results['authors'];
    // Remove non-selected users. !== 0 will leave the Anonymous user in if it
    // was selected on the form or entered in the drush parameters.
    $authors = array_filter($authors, static fn($k): bool => $k !== 0);
    // Likewise remove non-selected roles.
    $roles = $results['roles'];
    $roles = array_filter($roles, static fn($k): bool => $k !== 0);

    // If specific roles have been selected then also add up to 50 users who
    // have one of these roles. There is no direct way randomise the selection
    // using entity queries, so we use a database query instead.
    if ($roles !== [] && !in_array('authenticated', $roles)) {
      $query = $this->database->select('user__roles', 'ur')
        ->fields('ur', ['entity_id', 'roles_target_id'])
        ->condition('roles_target_id', $roles, 'in')
        ->range(0, 50)
        ->orderRandom();
      $uids = array_unique($query->execute()->fetchCol());
      // If the 'anonymous' role is selected, then add '0' to the user ids. Also
      // do this if no users were specified and none were found with the role(s)
      // requested. This makes it clear that no users were found. It would be
      // worse to fall through and select completely random users who do not
      // have any of the roles requested.
      if (in_array('anonymous', $roles) || ($authors === [] && $uids === [])) {
        $uids[] = '0';
      }

      $authors = array_unique(array_merge($authors, $uids));
    }

    // If still no authors have been collected, or the 'authenticated' role was
    // requested then add a random set of users up to a maximum of 50.
    if ($authors === [] || in_array('authenticated', $roles)) {
      $query = $this->database->select('users', 'u')
        ->fields('u', ['uid'])
        ->range(0, 50)
        ->orderRandom();
      $uids = $query->execute()->fetchCol();
      $authors = array_unique(array_merge($authors, $uids));
    }

    $results['users'] = $authors;
  }

  /**
   * Create one node. Used by both batch and non-batch code branches.
   *
   * @param array $results
   *   Results information.
   */
  protected function develGenerateContentAddNode(array &$results): void {
    if (!isset($results['time_range'])) {
      $results['time_range'] = 0;
    }

    $users = $results['users'];

    $node_type = array_rand($results['node_types']);
    $uid = $users[array_rand($users)];

    // Add the content type label if required.
    $title_prefix = $results['add_type_label'] ? $this->nodeTypeStorage->load($node_type)->label() . ' - ' : '';

    $values = [
      'nid' => NULL,
      'type' => $node_type,
      'title' => $title_prefix . $this->getRandom()->sentences(mt_rand(1, $results['title_length']), TRUE),
      'uid' => $uid,
      'revision' => mt_rand(0, 1),
      'moderation_state' => 'published',
      'status' => TRUE,
      'promote' => mt_rand(0, 1),
      'created' => $this->time->getRequestTime() - mt_rand(0, $results['time_range']),
      // A flag to let hook_node_insert() implementations know that this is a
      // generated node.
      'devel_generate' => $results,
    ];

    if (isset($results['add_language'])) {
      $values['langcode'] = $this->getLangcode($results['add_language']);
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create($values);

    // Populate non-skipped fields with sample values.
    $this->populateFields($node, $results['skip_fields'], $results['base_fields']);

    // Remove the fields which are intended to have no value.
    foreach ($results['skip_fields'] as $field) {
      unset($node->$field);
    }

    $node->save();
    $this->insertNodeData($node);

    // Add url alias if required.
    if (!empty($results['add_alias'])) {
      $path_alias = $this->aliasStorage->create([
        'path' => '/node/' . $node->id(),
        'alias' => '/node-' . $node->id() . '-' . $node->bundle(),
        'langcode' => $values['langcode'] ?? LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);
      $path_alias->save();
    }

    // Add translations.
    $this->develGenerateContentAddNodeTranslation($results, $node);
  }

  /**
   * Create translation for the given node.
   *
   * @param array $results
   *   Results array.
   * @param \Drupal\node\NodeInterface $node
   *   Node to add translations to.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function develGenerateContentAddNodeTranslation(array &$results, NodeInterface $node): void {
    if (empty($results['translate_language'])) {
      return;
    }

    if (is_null($this->contentTranslationManager)) {
      return;
    }

    if (!$this->contentTranslationManager->isEnabled('node', $node->getType())) {
      return;
    }

    if ($node->get('langcode')->getLangcode() === LanguageInterface::LANGCODE_NOT_SPECIFIED
      || $node->get('langcode')->getLangcode() === LanguageInterface::LANGCODE_NOT_APPLICABLE) {
      return;
    }

    if (!isset($results['num_translations'])) {
      $results['num_translations'] = 0;
    }

    // Translate node to each target language.
    $skip_languages = [
      LanguageInterface::LANGCODE_NOT_SPECIFIED,
      LanguageInterface::LANGCODE_NOT_APPLICABLE,
      $node->get('langcode')->getLangcode(),
    ];
    foreach ($results['translate_language'] as $langcode) {
      if (in_array($langcode, $skip_languages)) {
        continue;
      }

      $translation_node = $node->addTranslation($langcode);
      $translation_node->setTitle($node->getTitle() . ' (' . $langcode . ')');
      $this->populateFields($translation_node);
      $translation_node->save();
      if ($translation_node->id() > 0 && !empty($results['add_alias'])) {
        $path_alias = $this->aliasStorage->create([
          'path' => '/node/' . $translation_node->id(),
          'alias' => '/node-' . $translation_node->id() . '-' . $translation_node->bundle() . '-' . $langcode,
          'langcode' => $langcode,
        ]);
        $path_alias->save();
      }

      ++$results['num_translations'];
    }
  }

  private function insertNodeData(NodeInterface $node): void {
    if (!isset($node->devel_generate)) {
      return;
    }

    $results = $node->devel_generate;
    if (!empty($results['max_comments'])) {
      foreach ($node->getFieldDefinitions() as $field_name => $field_definition) {
        if ($field_definition->getType() !== 'comment') {
          continue;
        }

        if ($node->get($field_name)->getValue()[0]['status'] !== CommentItemInterface::OPEN) {
          continue;
        }

        // Add comments for each comment field on entity.
        $this->addNodeComments($node, $field_definition, $results['users'], $results['max_comments'], $results['title_length']);
      }
    }

    if ($results['add_statistics']) {
      $this->addNodeStatistics($node);
    }
  }

  /**
   * Create comments and add them to a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to add comments to.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field storage definition.
   * @param array $users
   *   Array of users to assign comment authors.
   * @param int $max_comments
   *   Max number of comments to generate per node.
   * @param int $title_length
   *   Max length of the title of the comments.
   */
  private function addNodeComments(NodeInterface $node, FieldDefinitionInterface $field_definition, array $users, int $max_comments, int $title_length = 8): void {
    $parents = [];
    $commentStorage = $this->entityTypeManager->getStorage('comment');
    $field_name = $field_definition->getName();
    $num_comments = mt_rand(0, $max_comments);
    for ($i = 1; $i <= $num_comments; ++$i) {
      $query = $commentStorage->getQuery();
      switch ($i % 3) {
        case 0:
          // No parent.
        case 1:
          // Top level parent.
          $parents = $query
            ->condition('pid', 0)
            ->condition('entity_id', $node->id())
            ->condition('entity_type', 'node')
            ->condition('field_name', $field_name)
            ->range(0, 1)
            ->accessCheck(FALSE)
            ->execute();
          break;

        case 2:
          // Non top level parent.
          $parents = $query
            ->condition('pid', 0, '>')
            ->condition('entity_id', $node->id())
            ->condition('entity_type', 'node')
            ->condition('field_name', $field_name)
            ->range(0, 1)
            ->accessCheck(FALSE)
            ->execute();
          break;
      }

      $random = new Random();
      $stub = [
        'entity_type' => $node->getEntityTypeId(),
        'entity_id' => $node->id(),
        'field_name' => $field_name,
        'name' => 'devel generate',
        'mail' => 'devel_generate@example.com',
        'timestamp' => mt_rand($node->getCreatedTime(), $this->time->getRequestTime()),
        'subject' => substr($random->sentences(mt_rand(1, $title_length), TRUE), 0, 63),
        'uid' => $users[array_rand($users)],
        'langcode' => $node->language()->getId(),
      ];
      if ($parents) {
        $stub['pid'] = current($parents);
      }

      $comment = $commentStorage->create($stub);

      // Populate all core fields.
      $this->populateFields($comment);
      $comment->save();
    }
  }

  /**
   * Generate statistics information for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   */
  private function addNodeStatistics(NodeInterface $node): void {
    if (!$this->moduleHandler->moduleExists('statistics')) {
      return;
    }

    $statistic = [
      'nid' => $node->id(),
      'totalcount' => mt_rand(0, 500),
      'timestamp' => $this->time->getRequestTime() - mt_rand(0, $node->getCreatedTime()),
    ];
    $statistic['daycount'] = mt_rand(0, $statistic['totalcount']);
    $this->database->insert('node_counter')->fields($statistic)->execute();
  }

}
