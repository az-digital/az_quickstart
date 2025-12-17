/**
 * @file
 * A JavaScript file for the back to top functionality.
 *
 */

((Drupal) => {
  Drupal.behaviors.azBackToTop = {
    attach: (context) => {
      if (context !== document) {
        return;
      }
      // only run this script if the document height is 4 times the height
      // of the browser window the page is being viewed through.
      if (
        document.body.clientHeight /
          document.documentElement.clientHeight >=
        3
      ) {
        const backToTop = document.getElementById('az-back-to-top');

        if (!backToTop) {
          return;
        }

        // Smoothly scroll to the top of the page if the arrow is clicked.
        backToTop.addEventListener('click', () => {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Hide the arrow if we're at the top of the page.
        window.addEventListener('scroll', () => {
          if (window.scrollY > 750) {
            backToTop.style.display = 'flex';
          } else {
            backToTop.style.display = 'none';
          }
        });
      }
    },
  };
})(Drupal);
