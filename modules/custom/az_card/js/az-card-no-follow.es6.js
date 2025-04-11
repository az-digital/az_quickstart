/**
 * @file
 * Adds event listener and handler for cards in order prevent links from working
 * under certain circumstances.
 */

((Drupal) => {
  /**
   * Behavior for card no-follow preview links.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches to card preview links in node edit form.
   */
  Drupal.behaviors.azCardNoFollow = {
    attach(context) {
      /**
       * Disables links for cards.
       *
       * @param {ClickEvent} event - Click event.
       */
      function noFollow(event){
        event.preventDefault();
      }

      /**
       * Adds event listeners to card links.
       */
      const cards = context.querySelectorAll('.az-card-no-follow');
      [...cards].forEach((card) => {
        card.addEventListener('click', noFollow);
      });
    },
  };
})(this.Drupal);
