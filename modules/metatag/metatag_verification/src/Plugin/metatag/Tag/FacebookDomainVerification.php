<?php

namespace Drupal\metatag_verification\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'facebook-domain-verification' meta tag.
 *
 * @MetatagTag(
 *   id = "facebook_domain_verification",
 *   label = @Translation("Facebook"),
 *   description = @Translation("A string provided by <a href=':facebook'>Facebook</a>, full details are available from the <a href=':help'>Facebook online help</a>.", arguments = { ":facebook" = "https://facebook.com", ":help" = "https://developers.facebook.com/docs/sharing/domain-verification/verifying-your-domain/#meta-tags" }),
 *   name = "facebook-domain-verification",
 *   group = "site_verification",
 *   weight = 3,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class FacebookDomainVerification extends MetaNameBase {
}
