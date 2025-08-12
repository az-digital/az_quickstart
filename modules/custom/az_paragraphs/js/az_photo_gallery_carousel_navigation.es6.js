document.addEventListener('DOMContentLoaded', () => {
  // Listen for all modals on the page
  document.querySelectorAll('.az-gallery-modal').forEach((modal) => {
    const carousel = modal.querySelector('.carousel.az-gallery');
    if (!carousel) return;
    const prevBtn = carousel.querySelector('.carousel-control-prev');
    const nextBtn = carousel.querySelector('.carousel-control-next');
    const items = carousel.querySelectorAll('.carousel-item');

    const updateNavButtons = () => {
      // Simple navigation - always enabled, standard labels
      prevBtn.disabled = false;
      nextBtn.disabled = false;
      prevBtn.querySelector('.visually-hidden').textContent = 'Previous';
      nextBtn.querySelector('.visually-hidden').textContent = 'Next';
    };

    carousel.addEventListener('slid.bs.carousel', updateNavButtons);
    modal.addEventListener('shown.bs.modal', () => {
      setTimeout(updateNavButtons, 50);
    });

    // Function to set the active slide and update indicators
    function setActiveSlide(targetIndex) {
      items.forEach((item, index) => {
        if (index === targetIndex) {
          item.classList.add('active');
        } else {
          item.classList.remove('active');
        }
      });

      // Update indicators to match
      const indicators = carousel.querySelectorAll(
        '.carousel-indicators button',
      );
      indicators.forEach((indicator, index) => {
        if (index === targetIndex) {
          indicator.classList.add('active');
          indicator.setAttribute('aria-current', 'true');
        } else {
          indicator.classList.remove('active');
          indicator.removeAttribute('aria-current');
        }
      });

      updateNavButtons();
    }

    // Handle thumbnail activation for THIS modal only
    document
      .querySelectorAll(`.az-gallery-open-modal[data-bs-target="#${modal.id}"]`)
      .forEach((thumbnail) => {
        thumbnail.addEventListener('mousedown', () => {
          const slideIndex = thumbnail.getAttribute('data-bs-slide-to');
          setActiveSlide(parseInt(slideIndex, 10));
        });
        thumbnail.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault(); // Prevent default space/enter behavior
            const slideIndex = thumbnail.getAttribute('data-bs-slide-to');
            setActiveSlide(parseInt(slideIndex, 10));
            // Trigger the modal to open
            thumbnail.click();
          }
        });
      });

    modal.addEventListener('shown.bs.modal', () => {
      // Just update nav buttons since slide is already set
      updateNavButtons();
    });

    updateNavButtons();
  });
});
