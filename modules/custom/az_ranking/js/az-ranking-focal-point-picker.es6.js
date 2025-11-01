/**
 * @file
 * Focal Point Picker for az_image media type.
 *
 * Adds a clickable overlay to the focal point picker section in the media edit form.
 */

((Drupal, once) => {
  Drupal.behaviors.azRankingFocalPointPicker = {
    attach: (context) => {
      // Find the focal point picker wrapper in the media form
      const pickerWrappers = once(
        'focal-point-picker',
        '.focal-point-picker-wrapper',
        context,
      );

      pickerWrappers.forEach((wrapper) => {
        // Find the image
        const image = wrapper.querySelector('.focal-point-picker-image');

        if (!image) {
          // eslint-disable-next-line no-console
          console.warn('No image found in focal point picker wrapper');
          return;
        }

        // Get current focal point values from data attributes
        let focalX = parseFloat(wrapper.getAttribute('data-focal-x')) || 0.5;
        let focalY = parseFloat(wrapper.getAttribute('data-focal-y')) || 0.5;

        // Wrap the image in a positioned container to constrain the overlay
        const imageContainer = document.createElement('div');
        imageContainer.className = 'focal-point-image-container';
        imageContainer.style.position = 'relative';
        imageContainer.style.display = 'inline-block';
        imageContainer.style.maxWidth = '100%';

        // Wrap the image
        image.parentNode.insertBefore(imageContainer, image);
        imageContainer.appendChild(image);

        // Create overlay and indicator
        const overlay = document.createElement('div');
        overlay.className = 'focal-point-overlay';
        const indicator = document.createElement('div');
        indicator.className = 'focal-point-indicator';
        indicator.title = 'Click to set focal point';

        overlay.appendChild(indicator);
        imageContainer.appendChild(overlay);

        // Get hidden field inputs for storing focal point values
        // Use class selectors for more reliable targeting
        const focalXInput = context.querySelector('.js-focal-point-x-value');
        const focalYInput = context.querySelector('.js-focal-point-y-value');

        if (!focalXInput || !focalYInput) {
          // eslint-disable-next-line no-console
          console.warn(
            'Could not find focal point input fields! Focal point changes will not be saved.',
          );
        }

        const updateIndicatorPosition = () => {
          const width = image.offsetWidth;
          const height = image.offsetHeight;

          // If dimensions are 0 or suspiciously small, the image isn't ready yet
          // Skip the update and it will be retried by other strategies
          if (width < 10 || height < 10) {
            return;
          }

          const indicatorLeft = focalX * width;
          const indicatorTop = focalY * height;

          indicator.style.left = `${indicatorLeft}px`;
          indicator.style.top = `${indicatorTop}px`;
        };

        // Set initial position
        // Use multiple strategies to ensure we set the red focus point picker
        // only when the image is fully rendered

        // Strategy 1: Wait for image load (for first-time loads)
        image.addEventListener('load', () => {
          updateIndicatorPosition();
        });

        // Strategy 2: Use setTimeout to allow DOM to settle after modal opens
        setTimeout(() => {
          updateIndicatorPosition();
        }, 100);

        // Strategy 3: If image is cached (complete=true), use longer delays
        // because the image reports as complete but dimensions aren't ready yet
        if (image.complete) {
          setTimeout(() => {
            updateIndicatorPosition();
          }, 150);

          setTimeout(() => {
            updateIndicatorPosition();
          }, 300);

          // Final attempt for stubborn cases
          setTimeout(() => {
            updateIndicatorPosition();
          }, 500);
        }

        // Strategy 4: Listen for dialog/modal open events
        document.addEventListener('dialogopen', () => {
          setTimeout(() => {
            updateIndicatorPosition();
          }, 150);
        });

        // Handle clicks on overlay
        overlay.addEventListener('click', (e) => {
          const rect = image.getBoundingClientRect();
          const width = image.offsetWidth;
          const height = image.offsetHeight;

          // Calculate relative position
          focalX = (e.clientX - rect.left) / width;
          focalY = (e.clientY - rect.top) / height;

          // Clamp values
          focalX = Math.max(0, Math.min(1, focalX));
          focalY = Math.max(0, Math.min(1, focalY));

          // Update hidden fields
          if (focalXInput && focalYInput) {
            focalXInput.value = focalX.toFixed(2);
            focalXInput.dispatchEvent(new Event('change', { bubbles: true }));
            focalYInput.value = focalY.toFixed(2);
            focalYInput.dispatchEvent(new Event('change', { bubbles: true }));
          }

          // Update indicator position
          updateIndicatorPosition();

          // Show feedback
          indicator.classList.add('focal-point-indicator--active');
          setTimeout(() => {
            indicator.classList.remove('focal-point-indicator--active');
          }, 300);
        });

        // Update on resize
        window.addEventListener('resize', updateIndicatorPosition);
      });
    },
  };
})(Drupal, once);
