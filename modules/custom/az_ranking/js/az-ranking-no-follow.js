/**
 * @file
 * Adds event listener and handler for rankings in order prevent links from working
 * under certain circumstances.
 */

((Drupal) => {
  /**
   * Behavior for ranking no-follow preview links.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches to ranking preview links in node edit form.
   */
  Drupal.behaviors.azRankingNoFollow = {
    attach(context) {
      /**
       * Disables links for rankings.
       *
       * @param {ClickEvent} event - Click event.
       */
      function noFollow(event) {
        event.preventDefault();
      }

      /**
       * Adds event listeners to ranking links.
       */
      const rankings = context.querySelectorAll('.az-ranking-no-follow');
      [...rankings].forEach((ranking) => {
        ranking.addEventListener('click', noFollow);
      });
    },
  };
})(Drupal);
