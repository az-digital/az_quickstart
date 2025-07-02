/**
 * @file
 * Drupal Embed module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attaches or detaches behaviors, except the ones we do not want.
   *
   * @param {string} action
   *   Either 'attach' or 'detach'.
   * @param {HTMLDocument|HTMLElement} context
   *   The context argument for Drupal.attachBehaviors()/detachBehaviors().
   * @param {object} settings
   *   The settings argument for Drupal.attachBehaviors()/detachBehaviors().
   */
  Drupal.runEmbedBehaviors = function (action, context, settings) {
    // Do not run the excluded behaviors.
    var stashed = {};
    $.each(Drupal.embed.excludedBehaviors, function (i, behavior) {
      stashed[behavior] = Drupal.behaviors[behavior];
      delete Drupal.behaviors[behavior];
    });
    // Run the remaining behaviors.
    (action === 'attach' ? Drupal.attachBehaviors : Drupal.detachBehaviors)(context, settings);
    // Put the stashed behaviors back in.
    $.extend(Drupal.behaviors, stashed);
  };

  /**
   * Ajax 'embed_insert' command: insert the rendered embedded item.
   *
   * The regular Drupal.ajax.commands.insert() command cannot target elements
   * within iframes. This is a skimmed down equivalent that works no matter
   * whether the CKEditor is in iframe or div area mode.
   *
   * @param {Drupal.Ajax} ajax
   *   An Ajax object.
   * @param {object} response
   *   The Ajax response.
   * @param {string} response.data
   *    The Ajax response's content.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.embed_insert = function (ajax, response, status) {
    var $target = $(ajax.element);
    // No need to detach behaviors here, the widget is created fresh each time.
    $target.html(response.data);
    Drupal.runEmbedBehaviors('attach', $target.get(0), response.settings || ajax.settings);
  };

  /**
   * Stores settings specific to Embed module.
   */
  Drupal.embed = {

    /**
     * A list of behaviors which are to be excluded while attaching/detaching.
     *
     * - Drupal.behaviors.editor, to avoid editor inception.
     * - Drupal.behaviors.contextual, to keep contextual links hidden.
     */
    excludedBehaviors: ['editor', 'contextual']

  };

})(jQuery, Drupal);
