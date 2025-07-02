<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_embed\Kernel;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Kernel\SmartDefaultSettingsTest;

/**
 * @covers \Drupal\entity_embed\Plugin\CKEditor4To5Upgrade\EntityEmbed
 * @group entity_embed
 * @group ckeditor5
 * @requires module ckeditor5
 * @internal
 */
class UpgradePathTest extends SmartDefaultSettingsTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_embed',
    // The embed and node modules are required to install embed.button.node.yml.
    'embed',
    'node',
    // Provides an editor plugin with ID ckeditor in order to satisfy config
    // validation.
    'entity_embed_ckeditor_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if (version_compare(\Drupal::VERSION, '11.0', '>=')) {
      // https://drupal.org/i/3239012
      $this->markTestSkipped('CKEditor 4 to 5 upgrade path has been removed from Drupal 11.');
    }

    parent::setUp();

    $this->installConfig(['entity_embed']);

    $filter_config_bad_filter_html = [
      'filter_html' => [
        'id' => 'filter_html',
        'status' => 1,
        'settings' => [
          'allowed_html' => '<p> <br> <strong>',
        ],
      ],
    ];
    $filter_config_entity_embed_off = [
      'entity_embed' => [
        'status' => 0,
      ],
    ];
    $filter_config_entity_embed_on = [
      'entity_embed' => [
        'status' => 1,
      ],
    ];
    FilterFormat::create([
      'format' => 'entity_embed_disabled',
      'name' => 'Entity Embed disabled',
      'filters' => $filter_config_bad_filter_html + $filter_config_entity_embed_off,
    ])->setSyncing(TRUE)->save();
    FilterFormat::create([
      'format' => 'entity_embed_enabled_misconfigured_format_filter_html',
      'name' => 'Entity Embed enabled on a misconfigured format (filter_html wrong)',
      'filters' => $filter_config_bad_filter_html + $filter_config_entity_embed_on,
    ])->setSyncing(TRUE)->save();
    FilterFormat::create([
      'format' => 'entity_embed_enabled_misconfigured_format_missing_entity_embed',
      'name' => 'Entity Embed enabled on a misconfigured format (entity_embed missing)',
      'filters' => $filter_config_bad_filter_html + $filter_config_entity_embed_off,
    ])->setSyncing(TRUE)->save();
    FilterFormat::create([
      'format' => 'entity_embed_enabled',
      'name' => 'Entity Embed enabled on a well-configured format',
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <drupal-entity data-entity-type data-entity-uuid data-entity-embed-display data-entity-embed-display-settings data-view-mode data-align data-caption data-embed-button data-langcode alt title>',
          ],
        ],
      ] + $filter_config_entity_embed_on,
    ])->setSyncing(TRUE)->save();

    $generate_editor_settings = function (bool $node_embed_button_in_toolbar) {
      return [
        'toolbar' => [
          'rows' => [
            0 => [
              [
                'name' => 'Basic Formatting',
                'items' => [
                  'Bold',
                  'Format',
                ],
              ],
              [
                'name' => 'Embedding',
                'items' => $node_embed_button_in_toolbar
                  ? [
                    'node',
                  ]
                  : [],
              ],
            ],
          ],
        ],
        'plugins' => [],
      ];
    };

    Editor::create([
      'format' => 'entity_embed_disabled',
      'editor' => 'ckeditor',
      'settings' => $generate_editor_settings(FALSE),
    ])->setSyncing(TRUE)->save();
    Editor::create([
      'format' => 'entity_embed_enabled_misconfigured_format_filter_html',
      'editor' => 'ckeditor',
      'settings' => $generate_editor_settings(TRUE),
    ])->setSyncing(TRUE)->save();
    Editor::create([
      'format' => 'entity_embed_enabled_misconfigured_format_missing_entity_embed',
      'editor' => 'ckeditor',
      'settings' => $generate_editor_settings(TRUE),
    ])->setSyncing(TRUE)->save();
    Editor::create([
      'format' => 'entity_embed_enabled',
      'editor' => 'ckeditor',
      'settings' => $generate_editor_settings(TRUE),
    ])->setSyncing(TRUE)->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function provider(): \Generator {
    $expected_ckeditor5_toolbar = [
      'items' => [
        'bold',
        '|',
        'node',
      ],
    ];

    yield "entity_embed disabled" => [
      'format_id' => 'entity_embed_disabled',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];

    yield "entity_embed enabled on a misconfigured text format: filter_html wrong" => [
      'format_id' => 'entity_embed_enabled_misconfigured_format_filter_html',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [],
      ],
      'expected_superset' => '<drupal-entity alt title data-align data-caption data-entity-embed-display data-entity-embed-display-settings data-view-mode data-entity-uuid data-langcode data-embed-button="node" data-entity-type="node">',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:  The tag <em class="placeholder">&lt;drupal-entity&gt;</em>; These attributes: <em class="placeholder"> alt (for &lt;drupal-entity&gt;), title (for &lt;drupal-entity&gt;), data-align (for &lt;drupal-entity&gt;), data-caption (for &lt;drupal-entity&gt;), data-entity-embed-display (for &lt;drupal-entity&gt;), data-entity-embed-display-settings (for &lt;drupal-entity&gt;), data-view-mode (for &lt;drupal-entity&gt;), data-entity-uuid (for &lt;drupal-entity&gt;), data-langcode (for &lt;drupal-entity&gt;), data-embed-button (for &lt;drupal-entity&gt;), data-entity-type (for &lt;drupal-entity&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "entity_embed enabled on a misconfigured text format: entity_embed off" => [
      'format_id' => 'entity_embed_enabled_misconfigured_format_missing_entity_embed',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
      'expected_post_filter_drop_fundamental_compatibility_violations' => NULL,
      'expected_post_update_text_editor_violations' => [
        'settings.toolbar.items.2' => 'The <em class="placeholder">Node</em> toolbar item requires the <em class="placeholder">Display embedded entities</em> filter to be enabled.',
      ],
    ];

    yield "entity_embed enabled on a well-configured text format" => [
      'format_id' => 'entity_embed_enabled',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            'bold',
            '|',
            'node',
            // Added because the CKEditor 4 entity_embed plugin uses attribute
            // restrictions that are too permissive: it allows any value for the
            // `data-entity-type` and `data-embed-button` attributes, but it
            // should have been restricted to be more narrow.
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              '<drupal-entity data-entity-type data-embed-button>',
            ],
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [
        'status' => [
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Entity Embed enabled on a well-configured format</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;drupal-entity data-entity-type data-embed-button&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following:  Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;drupal-entity data-entity-type data-embed-button&gt;. Additional details are available in your logs.',
        ],
      ],
    ];

    // Verify that none of the core test cases are broken; especially important
    // for Linkit since it extends the behavior of Drupal core.
    foreach (parent::provider() as $label => $case) {
      yield $label => $case;
    }
  }

}
