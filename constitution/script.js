document.addEventListener("DOMContentLoaded", function () {
    // Make articles and amendments collapsible
    const articles = document.querySelectorAll("article");
    // Select the heading, content, and sections of each article/amendment
    articles.forEach((article) => {
        const heading = article.querySelector("h3");
        const content = article.querySelector("p");
        const sections = article.querySelectorAll("section");
        if (sections.length > 0) {
            // For articles/amendments with multiple sections
            sections.forEach((section) => {
                section.style.display = "none";
            });
            // Event listener to toggle the display of sections
            heading.addEventListener("click", () => {
                sections.forEach((section) => {
                    if (section.style.display === "none")section.style.display = "block";
                    else section.style.display = "none";
                });
                heading.classList.toggle("active");
            });
        } else if (content) {
            // For articles with a single section/paragraph
            content.style.display = "none";
            // Event listener to toggle the display of sections
            heading.addEventListener("click", () => {
                if (content.style.display === "none") content.style.display = "block";
                else content.style.display = "none";
                heading.classList.toggle("active");
            });
        }
    });

    // Smooth scroll for nav ba
    const navLinks = document.querySelectorAll("nav a");
    navLinks.forEach((link) => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            const targetId = this.getAttribute("href").substring(1);
            const targetElement = document.getElementById(targetId);
            targetElement.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    });
});
