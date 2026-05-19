/**
 * @file
 * Handle copy-to-clipboard functionality for accordion links.
 * On load, if an anchor link exists in the URL, handle
 * anchor link scrolling and expand content.
 */

/**
 * Scroll offset in pixels to maintain space above the target element.
 */
const SCROLL_OFFSET = 75;

/**
 * Expands accordion and scrolls to anchor with vertical offset.
 */
function scrollToAccordion() {
  console.log("method called");
  var anchortag = window.location.hash.substring(1); // Get anchor link hash without the #
  
  if (!anchortag) { // No anchor link found
    return;
  }

  // Get the parent 
  var parent_accordion = document.querySelector('[data-bs-target="#' + anchortag + '"]');
  console.log("anchor tag: ", anchortag);
  console.log("parent accordion: ", parent_accordion);
  
  if (parent_accordion !== null) {
    // Disable browser automatic scroll restoration to handle it manually
//    if ('scrollRestoration' in history) {
//      history.scrollRestoration = 'manual';
//    }
    
    // Un-collapse selected accordion if collapsed
    if (parent_accordion.classList.contains("collapsed")) {
      parent_accordion.classList.remove('collapsed');
      parent_accordion.setAttribute('aria-expanded', 'true');
      var accordion_body_elem = document.getElementById(anchortag);
      accordion_body_elem.classList.add('show');
    }
    
    // Scroll to element with offset
    var elementPosition = parent_accordion.getBoundingClientRect().top + window.pageYOffset;
    window.scrollTo({
      top: elementPosition - SCROLL_OFFSET,
      behavior: 'smooth'
    });
  }
}

// Function on content load and when changing the anchor link
document.addEventListener('DOMContentLoaded', scrollToAccordion);
window.addEventListener('hashchange', scrollToAccordion);

function click2copy(accordionId, event) {
  // Prevent the click from bubbling up to the accordion-button
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
      console.log('URL copied to clipboard:', urlToCopy);
    })
    .catch(err => {
      console.error('Failed to copy URL to clipboard:', err);
    });
}
