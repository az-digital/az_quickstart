let copyLinks = document.querySelectorAll('.js-click-copy');
const base_url = window.location.origin;

function handleClick(event) {
  if (event.type === "click") {
    event.preventDefault();
    let href = event.srcElement.getAttribute("href");
    navigator.clipboard.writeText(base_url+href);
    event.srcElement.classList.add("js-click-copy--copied");

  } else {
    return false;
  }
}

copyLinks.forEach(element => (
  element.addEventListener(
    "click",
    handleClick,
    false
  )
));

