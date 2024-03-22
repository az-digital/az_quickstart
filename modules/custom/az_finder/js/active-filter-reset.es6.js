((drupalSettings) => {
  document.addEventListener('DOMContentLoaded', () => {
    const filterContainers = document.querySelectorAll(
      '[data-az-better-exposed-filters]',
    );
    filterContainers.forEach((container) => {
      const resetButton = container.querySelector('.js-active-filters-reset');
      if (resetButton) {
        resetButton.addEventListener('click', (event) => {
          event.preventDefault();
          const checkboxes = container.querySelectorAll(
            'input[type="checkbox"]',
          );
          checkboxes.forEach((checkbox) => {
            checkbox.checked = false;
          });
          const textFields = container.querySelectorAll('input[type="text"]');
          textFields.forEach((textField) => {
            textField.value = '';
          });
          container.querySelector('.js-form-submit').click();
        });
      }
    });
  });
})(drupalSettings);
