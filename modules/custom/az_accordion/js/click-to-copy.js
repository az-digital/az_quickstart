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
 * Expands accordion and scrolls to anchor with offset.
 */
function expandAndScrollToAnchor() {
  var anchortag = window.location.hash.substring(1); // Get anchor link hash without the #
  
  if (!anchortag) {
    return;
  }

  var anchorelem = document.querySelector('[data-bs-target="#' + anchortag + '"]');
  console.log("anchor tag: ", anchortag);
  console.log("anchor elem: ", anchorelem);
  
  if (anchorelem !== null) {
    // Disable automatic scroll restoration to handle it manually
    if ('scrollRestoration' in history) {
      history.scrollRestoration = 'manual';
    }
    
    if (anchorelem.classList.contains("collapsed")) {
      console.log("Un collapsing...");
      anchorelem.classList.remove('collapsed');
      anchorelem.setAttribute('aria-expanded', 'true');
      // Body
      var accordion_body_elem = document.getElementById(anchortag);
      accordion_body_elem.classList.add('show');
    }
    
    // Scroll to element with offset
    var elementPosition = anchorelem.getBoundingClientRect().top + window.pageYOffset;
    window.scrollTo({
      top: elementPosition - SCROLL_OFFSET,
      behavior: 'smooth'
    });
  }
}

document.addEventListener('DOMContentLoaded', expandAndScrollToAnchor);
window.addEventListener('hashchange', expandAndScrollToAnchor);

function click2copy(accordionId) {
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
