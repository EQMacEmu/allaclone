function toggleList(ulId) {
  var list = document.getElementById(ulId);
  if (list.style.display === "none") {
    list.style.display = "block";
  } else {
    list.style.display = "none";
  }
}
