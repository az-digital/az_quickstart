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
        if (
          $targetAccordion &&
          hash !== '#' &&
          hash.startsWith('#accordion-') &&
          $targetAccordion.classList.contains('collapse')
        ) {
          const yOffset = -10;
          const y =
            $targetAccordion.getBoundingClientRect().top +
            window.scrollY +
            yOffset;
          // Smooth scroll to the accordion.
          $targetAccordion.scrollIntoView({
            top: y,
            behavior: 'smooth',
          });

          if ('collapse' in $targetAccordion) {
            $targetAccordion.collapse('show');
          } else {
            $targetAccordion.classList.add('show');
          }
          window.location.hash = hash;
          // Accessing history through window to avoid ESLint error.
          window.history.pushState(null, null, hash);
        }
      }
    };

    // Check for hash on initial load.
    handleAccordion(window.location.hash);

    // Add an event listener to handle clicks on anchor links
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener('click', function handleAnchorClick(e) {
        e.preventDefault();

        // Extract the hash from the anchor link and handle the accordion
        const hash = anchor.getAttribute('href');
        handleAccordion(hash);
      });
    });
  }

  // Initialize the script either on DOMContentLoaded or immediately if the event has already fired
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(this, this.document);
