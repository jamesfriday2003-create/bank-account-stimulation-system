const menuToggle = document.getElementById("menuToggle");

const sidebar = document.getElementById("sidebar");

if (menuToggle && sidebar) {

    menuToggle.addEventListener("click", function () {

        sidebar.classList.toggle("open");

    });

    // Close menu when a navigation link is selected
    sidebar.querySelectorAll("nav a").forEach(function(link) {

        link.addEventListener("click", function() {

            sidebar.classList.remove("open");

        });

    });

}