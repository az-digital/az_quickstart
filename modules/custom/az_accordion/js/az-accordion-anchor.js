/**
 * @file
 * Handle copy-to-clipboard functionality for accordion links.
 * On load, if an anchor link exists in the URL, handle
 * anchor link scrolling and expand the accordion content.
 */

/**
 * Expands accordion and scrolls to anchor link header with vertical offset.
 */
function scrollToAccordion() {
    const anchorTag = window.location.hash.substring(1); // Get anchor link hash without the '#'

    if (!anchorTag) { // No anchor link found, do nothing.
        return
    }

    const scrollOffset = 75; // How many Pixels above the scroll target (top of screen)

    // Get the accordion
    const parentAccordion = document.querySelector(
        `[data-bs-target="#${anchorTag}"]`,
    );
    if (parentAccordion !== null) {
        // Un-collapse parent accordion if collapsed
        if (parentAccordion.classList.contains('collapsed')) {
            parentAccordion.classList.remove('collapsed');
            parentAccordion.setAttribute('aira-expanded', 'true');
            const accordionBodyElem = document.getElementById(anchorTag);
            accordionBodyElem.classList.add('show');
        }

        // Scroll to element with offset
        const elementPosition = parentAccordion.getBoundingClientRect().top + window.pageYOffset;
        window.scrollTo({
            top: elementPosition - scrollOffset,
            behavior: 'smooth',
        });
    }
}

/**
 * Scroll when loading document and when changing the hash URL.
 */
document.addEventListener('DOMContentLoaded', scrollToAccordion);
window.addEventListener('hashchange', scrollToAccordion);

/**
 * Copies anchor link when clicked.
 * @param accordionId - the id of the accordion from Twig.
 * @param event - the onclick event.
 */

function copyAnchor(accordionId, event) {
    // Prevents anchor link from activating on click (from sitting on top of another button)
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }

    // Get the current URL without any existing hash
    const baseUrl = window.location.href.split('#')[0];

    // Construct the URL with the accordion ID anchor
    const urlToCopy = `${baseUrl}#${accordionId}`;

    // Copy to clipboard using the Clipboard API
    navigator.clipboard.writeText(urlToCopy).then(() => {
        // TODO: Visual Feedback, popup modal or temp icon animation
        console.log('URL copied to clipboard: ', urlToCopy);
    }).catch((err) => {
        console.error('Failed to copy URL to clipboard: ', err);
    });
}