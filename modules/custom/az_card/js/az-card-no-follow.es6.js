/**
 * @file
 * Adds event listener and handler for cards in order prevent links from working
 * under certain circumstances.
 */

((document) => {
  /**
   * Disables links for cards.
   *
   * @param {ClickEvent} event - Click event.
   */
  function noFollow(event) {
    event.preventDefault();
  }

  /**
   * Adds event listeners to card links.
   */
  const cards = document.querySelectorAll('.az-card-no-follow');
  [...cards].forEach((card) => {
    card.addEventListener('click', noFollow);
  });
})(this.document);
