<?php

namespace Drupal\workbench_access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Token handler for Workbench Access.
 *
 * TokenAPI still uses procedural code, but we have moved it to a class for
 * easier refactoring.
 */
class WorkbenchAccessTokens {

  use StringTranslationTrait;

  /**
   * The core token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The core module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The workbench access user section storage service.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * Constructs a WorkbenchAccessTokens object.
   *
   * @param \Drupal\Core\Utility\Token $token_service
   *   The core token service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The core module handler.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $user_section_storage
   *   The workbench access user section storage service.
   */
  public function __construct(Token $token_service, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, UserSectionStorageInterface $user_section_storage) {
    $this->tokenService = $token_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->userSectionStorage = $user_section_storage;
  }

  /**
   * Implements hook_token_info().
   */
  public function getTokenInfo() {
    $info['tokens'] = [
      'user' => [
        'workbench-access-sections' => [
          'name' => $this->t('Workbench access sections'),
          'description' => $this->t('Section assignments for the user account.'),
          // Optionally use token module's array type which gives users greater
          // control on output.
          'type' => $this->moduleHandler->moduleExists('token') ? 'array' : '',
        ],
      ],
      'node' => [
        'workbench-access-sections' => [
          'name' => $this->t('Workbench access sections'),
          'description' => $this->t('Section assignments for content.'),
          // Optionally use token module's array type which gives users greater
          // control on output.
          'type' => $this->moduleHandler->moduleExists('token') ? 'array' : '',
        ],
      ],
    ];

    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  public function getTokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];

    // User tokens.
    if ($type === 'user' && !empty($data['user'])) {
      /** @var \Drupal\user\UserInterface $user */
      $user = $data['user'];

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'workbench-access-sections':
            if ($sections = $this->getUserSectionNames($user, $bubbleable_metadata)) {
              if (function_exists('token_render_array')) {
                $replacements[$original] = token_render_array($sections, $options);
              }
              else {
                $replacements[$original] = implode(', ', $sections);
              }
            }
            break;
        }
      }

      // Chained token relationships.
      if ($section_tokens = $this->tokenService->findWithPrefix($tokens, 'workbench-access-sections')) {
        if ($sections = $this->getUserSectionNames($user, $bubbleable_metadata)) {
          $replacements += $this->tokenService->generate('array', $section_tokens, ['array' => $sections], $options, $bubbleable_metadata);
        }
      }
    }
    // Node tokens.
    if ($type === 'node' && !empty($data['node'])) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $data['node'];

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'workbench-access-sections':
            if ($sections = $this->getNodeSectionNames($node, $bubbleable_metadata)) {
              if (function_exists('token_render_array')) {
                $replacements[$original] = token_render_array($sections, $options);
              }
              else {
                $replacements[$original] = implode(', ', $sections);
              }
            }
            break;
        }
      }

      // Chained token relationships.
      if ($section_tokens = $this->tokenService->findWithPrefix($tokens, 'workbench-access-sections')) {
        if ($sections = $this->getNodeSectionNames($node, $bubbleable_metadata)) {
          $replacements += $this->tokenService->generate('array', $section_tokens, ['array' => $sections], $options, $bubbleable_metadata);
        }
      }
    }

    return $replacements;
  }

  /**
   * Generates an array of section names for a given account.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The cache metadata.
   *
   * @return array
   *   An array of section names.
   */
  private function getUserSectionNames(UserInterface $user, BubbleableMetadata $bubbleable_metadata) {
    $schemes = $this->entityTypeManager->getStorage('access_scheme')->loadMultiple();
    return array_reduce($schemes, function (array $carry, AccessSchemeInterface $scheme) use ($user, $bubbleable_metadata) {
      if (!$sections = $this->userSectionStorage->getUserSections($scheme, $user)) {
        return $carry;
      }
      $bubbleable_metadata->addCacheableDependency($scheme);

      return array_merge($carry, array_reduce($scheme->getAccessScheme()->getTree(), function ($inner, $info) use ($sections) {
        $user_in_sections = array_intersect_key($info, array_combine($sections, $sections));
        return array_merge($inner, array_column($user_in_sections, 'label'));
      }, []));
    }, []);
  }

  /**
   * Generates an array of section names for a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The user account.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The cache metadata.
   *
   * @return array
   *   An array of section names.
   */
  private function getNodeSectionNames(NodeInterface $node, BubbleableMetadata $bubbleable_metadata) {
    $schemes = $this->entityTypeManager->getStorage('access_scheme')->loadMultiple();
    return array_reduce($schemes, function (array $carry, AccessSchemeInterface $scheme) use ($node, $bubbleable_metadata) {
      if (!$sections = $scheme->getAccessScheme()->getEntityValues($node)) {
        return $carry;
      }
      $bubbleable_metadata->addCacheableDependency($scheme);

      return array_merge($carry, array_reduce($scheme->getAccessScheme()->getTree(), function ($inner, $info) use ($sections) {
        $node_in_sections = array_intersect_key($info, array_combine($sections, $sections));
        return array_merge($inner, array_column($node_in_sections, 'label'));
      }, []));
    }, []);
  }

}
