<?php

namespace Drupal\workbench_access\Traits;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Methods to set form page title.
 */
trait WorkbenchAccessFormPageTitleTrait {

  /**
   * Returns a page title for the form.
   *
   * @param string $user_type
   *   The user group.
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $access_scheme
   *   Access scheme.
   * @param string $id
   *   The section id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A page title.
   */
  protected function getPageTitle(string $user_type, AccessSchemeInterface $access_scheme, string $id): TranslatableMarkup {
    $scheme_label = $access_scheme->label();
    $section = $access_scheme->getAccessScheme()->load($id);
    $section_label = $section['label'] ?? '';
    if ($section_label && $section_label !== $scheme_label) {
      return $this->t('%user_type assigned to %scheme for %section', [
        '%user_type' => $user_type,
        '%scheme' => $scheme_label,
        '%section' => $section_label,
      ]);
    }
    return $this->t('%user_type assigned to %scheme', [
      '%user_type' => $user_type,
      '%scheme' => $scheme_label,
    ]);
  }

}
