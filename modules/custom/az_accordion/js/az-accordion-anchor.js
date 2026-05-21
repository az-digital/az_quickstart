/**
 * @file
 * Handle copy-to-clipboard functionality for accordion links.
 * On load, if an anchor link exists in the URL, handle
 * anchor link scrolling and expand content.
 */

/* 
 * Scroll when loading document and when changing the hash URL
 */
document.addEventListener('DOMContentLoaded', scrollToAccordion);
window.addEventListener('hashchange', scrollToAccordion);

/**
 * Expands accordion and scrolls to anchor link header with vertical offset.
 */
function scrollToAccordion() {
  var scroll_offset = 75; // How many Pixels above the scroll target (prevents anchor scroll being on very top of screen)
  var anchortag = window.location.hash.substring(1); // Get anchor link hash without the #
  
  if (!anchortag) { // No anchor link found
    return;
  }

  // Get the accordion
  var parent_accordion = document.querySelector('[data-bs-target="#' + anchortag + '"]'); 
  if (parent_accordion !== null) {
    // Un-collapse parent accordion if collapsed
    if (parent_accordion.classList.contains("collapsed")) {
      parent_accordion.classList.remove('collapsed');
      parent_accordion.setAttribute('aria-expanded', 'true');
      var accordion_body_elem = document.getElementById(anchortag);
      accordion_body_elem.classList.add('show');
    }
    
    // Scroll to element with offset
    var elementPosition = parent_accordion.getBoundingClientRect().top + window.pageYOffset;
    window.scrollTo({
      top: elementPosition - scroll_offset,
      behavior: 'smooth'
    });
  }
}

/**
 * Copies anchor link when clicked
 */
function copyAnchor(accordionId, event) {
  // Prevents anchor link from activating on click
  if (event) {
    event.stopPropagation();
    event.preventDefault();
  }

  // Get the current URL without any existing hash
  const baseUrl = window.location.href.split('#')[0];

  // Construct the URL with the accordion ID anchor
  const urlToCopy = baseUrl + '#' + accordionId;

  // Copy to clipboard using the Clipboard API
  navigator.clipboard.writeText(urlToCopy)
    .then(() => {
      //TODO: Visual Feedback, popup modal or temp icon animation
      console.log('URL copied to clipboard:', urlToCopy);
    })
    .catch(err => {
      console.error('Failed to copy URL to clipboard:', err);
    });
}
