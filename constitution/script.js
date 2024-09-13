document.addEventListener("DOMContentLoaded", function () {
  // Make articles and amendments collapsible
  const articles = document.querySelectorAll("article");
  articles.forEach((article) => {
    // Gets h3 and p tags of html
    const heading = article.querySelector("h3");
    const content = article.querySelector("p");

    // Code hides content by default but if if heading is clicked, it will show the content
    content.style.display = "none";
    heading.addEventListener("click", () => {
      if (content.style.display === "none") content.style.display = "block";
      else content.style.display = "none";
      heading.classList.toggle("active");
    });
  });

  // Implement smooth scroll for navigation
  const navLinks = document.querySelectorAll("nav a");
  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      // Prevent default link behavior
      e.preventDefault();
      // Get the target element's id from the href attribute
      const targetId = this.getAttribute("href").substring(1);
      // Find the target element
      const targetElement = document.getElementById(targetId);
      // Scroll to the target element smoothly
      targetElement.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  });
});
