document.addEventListener('DOMContentLoaded', function () {
  const countButton = document.querySelector('.js-bef-clear-all');
  const checkboxContainer = document.querySelector('.az-bef-vertical');
  const checkboxCount = countButton.querySelector('.js-bef-filter-count');
  const titleInput = checkboxContainer.querySelector('input[name="title"]');

  // Function to update the checkbox count
  function updateCheckboxCount() {
    const checkedCheckboxes = checkboxContainer.querySelectorAll(
      'input[type="checkbox"]:checked',
    );
    const numChecked = checkedCheckboxes.length;
    checkboxCount.textContent = `(${numChecked})`;
    if (numChecked > 0 && countButton.classList.contains('d-none')) {
      countButton.classList.remove('d-none');
    } else if (numChecked === 0 && !countButton.classList.contains('d-none')) {
      countButton.classList.add('d-none');
    }
  }

  // Function to uncheck all checkboxes
  function uncheckAllCheckboxes(event) {
    event.preventDefault();
    const checkboxes = checkboxContainer.querySelectorAll(
      'input[type="checkbox"]',
    );
    checkboxes.forEach((checkbox) => {
      checkbox.checked = false;
    });
    updateCheckboxCount();
  }
// Function to clear search box.
function clearSearchBox(event) {
  titleInput.value = '';
}

// Function to clear all filters
function clearAllFilters(event) {
    clearSearchBox(event);
    uncheckAllCheckboxes(event);
    event.preventDefault();
}
  countButton.addEventListener('click', clearAllFilters);

  // Use event delegation to handle checkbox changes
  checkboxContainer.addEventListener('change', function (event) {
    if (event.target && event.target.type === 'checkbox') {
      updateCheckboxCount();
    }
  });

  // Initialize the count on page load
  updateCheckboxCount();
});
