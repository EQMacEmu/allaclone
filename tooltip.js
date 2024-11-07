document.addEventListener("DOMContentLoaded", () => {
  const tooltip = document.getElementById("tooltip");
  let timeoutId;

  // Function to fetch and show item stats
  async function showItemStats(event) {
    const itemLink = event.target;
    const itemId = itemLink.dataset.itemId;

    // Fetch item stats from the server
    try {
      const response = await fetch(`/allaclone/item-preview.php?id=${itemId}`);
      const data = await response.text();

      // Set tooltip content
      tooltip.innerHTML = data;
      tooltip.style.visibility = "visible";
      tooltip.style.left = `${event.pageX + 10}px`;
      tooltip.style.top = `${event.pageY + 10}px`;
    } catch (error) {
      console.error("Error fetching item data:", error);
    }
  }

  // Hide tooltip when mouse leaves the link
  function hideTooltip() {
    tooltip.style.visibility = "hidden";
  }

  // Attach event listeners to all links with data-item-id
  document.querySelectorAll("a[data-item-id]").forEach(link => {
    link.addEventListener("mousemove", (event) => {
      // Update tooltip position on mouse move
      tooltip.style.left = `${event.pageX + 10}px`;
      tooltip.style.top = `${event.pageY + 10}px`;
    });
    link.addEventListener("mouseenter", () => {
      e = event;
      timeoutId = setTimeout(() => {
        showItemStats(e);
      }, 500);
    });
    link.addEventListener("mouseleave", () => {
        clearTimeout(timeoutId);
        hideTooltip(e);
    });
    link.addEventListener("onclick", () => {
        clearTimeout(timeoutId);
        hideTooltip(e);
    });
  });
});
