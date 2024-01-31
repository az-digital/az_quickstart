var input = document.querySelectorAll(".az-bef-vertical input");

input.forEach(function (input) {
  input.addEventListener("change", function () {
    if (this.checked) {
      updateInputCount();
    }
  });
});

function updateInputCount(){
  alert("DERP");
};

document.addEventListener("DOMContentLoaded", function () {
  const countButton = checkboxContainer.getElementByClassName(".button");
  const checkboxContainer = document.getElementByClassName(".az-bef-vertical");
  const checkboxCount = 

  // Function to update the checkbox count
  function updateCheckboxCount() {
    const checkedCheckboxes = checkboxContainer.querySelectorAll('input[type="checkbox"]:checked');
    const numChecked = checkedCheckboxes.length;
    checkboxCount.textContent = numChecked + " checkboxes checked";
  }

  countButton.addEventListener("click", updateCheckboxCount);

  // Use event delegation to handle checkbox changes
  checkboxContainer.addEventListener("change", function (event) {
    if (event.target && event.target.type === "checkbox") {
      updateCheckboxCount();
    }
  });

  // Initialize the count on page load
  updateCheckboxCount();
});