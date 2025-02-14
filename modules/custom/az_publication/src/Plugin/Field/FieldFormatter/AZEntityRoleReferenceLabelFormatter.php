<?php

namespace Drupal\az_publication\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 */
#[FieldFormatter(
  id: 'az_entity_role_reference_label',
  label: new TranslatableMarkup('Label'),
  description: new TranslatableMarkup('Display the label of the referenced entities.'),
  field_types: [
    'az_entity_role_reference',
  ],
)]
class AZEntityRoleReferenceLabelFormatter extends EntityReferenceLabelFormatter {

  // Stub.
}
