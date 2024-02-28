/**
 * @file
 * Provides scroll to and expand functionality for accordion based on anchor links.
 */

((window, document) => {
  function init() {
    // Function to handle accordion based on hash
    const handleAccordion = (hash) => {
      if (hash) {
        const $targetAccordion = document.querySelector(hash);
        if ($targetAccordion) {
          if ('collapse' in $targetAccordion) {
            $targetAccordion.collapse('show');
          } else {
            $targetAccordion.classList.add('show');
          }
          // Smooth scroll to the accordion
          $targetAccordion.scrollIntoView({ behavior: 'smooth' });

          window.location.hash = hash;
          history.pushState(null, null, hash);
        }
      }
    };

    // Check for hash on initial load.
    handleAccordion(window.location.hash);

    // Add an event listener to handle clicks on anchor links
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();

        // Extract the hash from the anchor link and handle the accordion
        const hash = this.getAttribute('href');
        handleAccordion(hash);
      });
    });
  }

  // Event listener for DOMContentLoaded to initialize the script
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    // The DOMContentLoaded event has already fired; run init immediately
    init();
  }
})(this, this.document);
