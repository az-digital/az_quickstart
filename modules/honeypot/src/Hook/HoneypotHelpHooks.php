<?php

declare(strict_types=1);

namespace Drupal\honeypot\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Hook implementations used to provide help.
 */
final class HoneypotHelpHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new HoneypotHelpHooks service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.honeypot':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The Honeypot module uses both the honeypot and timestamp methods of deterring spam bots from completing forms on your Drupal site. These methods are effective against many spam bots, and are not as intrusive as CAPTCHAs or other methods which punish the user. For more information, see the <a href=":url">online documentation for the Honeypot module</a>.', [':url' => 'https://www.drupal.org/docs/contributed-modules/honeypot']) . '</p>';
        $output .= '<h3>' . $this->t('Uses') . '</h3>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Configuring Honeypot') . '</dt>';
        $output .= '<dd>' . $this->t('All settings for this module are on the Honeypot configuration page, under the Configuration section, in the Content authoring settings. You can visit the configuration page directly from the Honeypot configuration link below. The configuration settings are described in the <a href=":url">online documentation for the Honeypot module</a>.', [':url' => 'https://www.drupal.org/docs/contributed-modules/honeypot/using-honeypot']) . '</dd>';
        $output .= '<dt>' . $this->t('Setting up Honeypot in your own forms') . '</dt>';
        $output .= '<dd>' . $this->t("Honeypot protection can be bypassed for certain user roles. For instance, site administrators, who just might be able to fill out a form in less than 5 seconds. And, Honeypot protection can be enabled only for certain forms. Or, it can protect all forms on the site. Finally, honeypot protection can be used in any of your own forms by simply including a little code snippet included on the module's project page.") . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

}
