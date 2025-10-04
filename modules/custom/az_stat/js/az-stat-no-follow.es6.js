/**
 * @file
 * Adds event listener and handler for stats in order prevent links from working
 * under certain circumstances.
 */

((Drupal) => {
  /**
   * Behavior for stat no-follow preview links.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches to stat preview links in node edit form.
   */
  Drupal.behaviors.azStatNoFollow = {
    attach(context) {
      /**
       * Disables links for stats.
       *
       * @param {ClickEvent} event - Click event.
       */
      function noFollow(event) {
        event.preventDefault();
      }

      /**
       * Adds event listeners to stat links.
       */
      const stats = context.querySelectorAll('.az-stat-no-follow');
      [...stats].forEach((stat) => {
        stat.addEventListener('click', noFollow);
      });
    },
  };
})(this.Drupal);
