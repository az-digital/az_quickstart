<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards player metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_player",
 *   label = @Translation("Media player URL"),
 *   description = @Translation("The full URL for loading a media player, specifically an iframe for an embedded video rather than the URL to a page that contains a player. Required when using the Player Card type."),
 *   name = "twitter:player",
 *   group = "twitter_cards",
 *   weight = 400,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   absolute_url = TRUE
 * )
 */
class TwitterCardsPlayer extends MetaNameBase {
}
