document.addEventListener('DOMContentLoaded', () => {
  // Listen for all modals on the page
  document.querySelectorAll('.az-gallery-modal').forEach((modal) => {
    const carousel = modal.querySelector('.carousel.az-gallery');
    if (!carousel) return;
    const prevBtn = carousel.querySelector('.carousel-control-prev');
    const nextBtn = carousel.querySelector('.carousel-control-next');
    const items = carousel.querySelectorAll('.carousel-item');

    const updateNavButtons = () => {
      const activeIndex = Array.from(items).findIndex((item) =>
        item.classList.contains('active'),
      );
      prevBtn.disabled = activeIndex === 0;
      nextBtn.disabled = activeIndex === items.length - 1;
      prevBtn.querySelector('.visually-hidden').textContent =
        activeIndex === 0 ? 'No previous slide' : 'Previous';
      nextBtn.querySelector('.visually-hidden').textContent =
        activeIndex === items.length - 1 ? 'Return to first slide' : 'Next';
    };

    carousel.addEventListener('slid.bs.carousel', updateNavButtons);
    modal.addEventListener('shown.bs.modal', () => {
      setTimeout(updateNavButtons, 50);
    });

    // Handle thumbnail activation for THIS modal only
    let requestedSlideIndex = null;
    document
      .querySelectorAll(`.az-gallery-open-modal[data-bs-target="#${modal.id}"]`)
      .forEach((thumbnail) => {
        thumbnail.addEventListener('mousedown', function () {
          requestedSlideIndex = this.getAttribute('data-bs-slide-to');
        });
        thumbnail.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            requestedSlideIndex = this.getAttribute('data-bs-slide-to');
          }
        });
      });

    modal.addEventListener('shown.bs.modal', () => {
      if (requestedSlideIndex !== null) {
        // Find the indicator button with the matching data-bs-slide-to
        const indicator = carousel.querySelector(
          `.carousel-indicators [data-bs-slide-to="${requestedSlideIndex}"]`,
        );
        if (indicator) {
          indicator.click();
        }
        requestedSlideIndex = null;
      }
    });

    updateNavButtons();
  });
});
