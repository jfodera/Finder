// FAKE ITEMS
const lostItems = [
    {
        item_id: 1,
        item_type: "Phone",
        description: "iPhone 12 Pro, Space Gray",
        location: "Central Park",
        lost_time: "2024-09-28 14:30:00",
        status: "lost",
        image_url: "" 
    },
    {
        item_id: 2,
        item_type: "Wallet",
        description: "Brown leather wallet with initials JD",
        location: "Main Street Coffee Shop",
        lost_time: "2024-09-27 09:15:00",
        status: "found",
        image_url: "" 
    },
    {
        item_id: 3,
        item_type: "Keys",
        description: "Car keys with a red keychain",
        location: "City Gym",
        lost_time: "2024-09-26 18:45:00",
        status: "lost",
        image_url: "" 
    }
];

function createItemCard(item) {
    return `
        <div class="item-card">
            <img src="${item.image_url}" alt="${item.item_type}" class="item-image">
            <div class="item-type">${item.item_type}</div>
            <div class="item-description">${item.description}</div>
            <div class="item-details">
                Location: ${item.location}<br>
                Lost: ${new Date(item.lost_time).toLocaleString()}<br>
                Status: ${item.status}
            </div>
        </div>
    `;
}

function renderItems() {
    const itemsGrid = document.getElementById('itemsGrid');
    if (itemsGrid) {
        itemsGrid.innerHTML = lostItems.map(createItemCard).join('');
    }
}

// Form navigation and validation
function initializeForm() {
    const infoForm = document.getElementById('infoForm');
    if (!infoForm) return; // Exit if not on a form page

    const pages = Array.from(document.querySelectorAll('#infoForm .page'));
    const nextBtns = document.querySelectorAll('.next-btn');
    const prevBtns = document.querySelectorAll('.prev-btn');

    // Form navigation
    nextBtns.forEach(button => {
        button.addEventListener('click', () => {
            changePage('next');
        });
    });

    prevBtns.forEach(button => {
        button.addEventListener('click', () => {
            changePage('prev');
        });
    });

    function changePage(btn) {
        const active = document.querySelector('#infoForm .page.active');
        let index = pages.indexOf(active);
        pages[index].classList.remove('active');
        if (btn === 'next') {
            index++;
        } else if (btn === 'prev') {
            index--;
        }
        pages[index].classList.add('active');
    }

    // Image preview
    const uploadImg = document.getElementById("upload_image");
    const inputFile = document.getElementById("input-file");
    if (inputFile) {
        inputFile.onchange = function() {
            uploadImg.src = URL.createObjectURL(inputFile.files[0]);
        }
    }

    // Location search and selection
    const searchBox = document.getElementById('locationSearch');
    const locationCheckboxes = document.querySelectorAll('.location-checkbox');
    const selectedCountSpan = document.getElementById('selectedCount');

    if (searchBox) {
        searchBox.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            locationCheckboxes.forEach(checkbox => {
                const label = checkbox.querySelector('label').textContent.toLowerCase();
                const locationGroup = checkbox.closest('.location-group');
                checkbox.style.display = label.includes(searchTerm) ? 'flex' : 'none';
                
                // Show/hide category headers based on visible checkboxes
                if (locationGroup) {
                    const visibleCheckboxes = locationGroup.querySelectorAll('.location-checkbox[style="display: flex"]');
                    locationGroup.style.display = visibleCheckboxes.length > 0 ? 'block' : 'none';
                }
            });
        });
    }

    // Update selected locations count
    if (locationCheckboxes.length > 0) {
        locationCheckboxes.forEach(checkbox => {
            checkbox.querySelector('input').addEventListener('change', updateSelectedCount);
        });

        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('input[type="checkbox"]:checked').length;
            selectedCountSpan.textContent = selectedCount;
            
            // Validate minimum selection
            const submitBtn = document.querySelector('.submit-btn');
            if (submitBtn) {
                submitBtn.disabled = selectedCount === 0;
            }
        }
    }
}

// Navigation and header functionality
function initializeNavigation() {
    // Active page highlighting
    var currentPage = window.location.pathname.split('/').pop();
    var navLinks = document.querySelectorAll('.global-header nav ul li a');
    navLinks.forEach(function(link) {
        if (link.getAttribute('href') === currentPage) {
            link.parentElement.classList.add('active');
        }
    });

    // Mobile menu toggle
    const hamburger = document.getElementById("hamburger");
    const navMenu = document.getElementById("nav-menu");
    if (hamburger && navMenu) {
        hamburger.addEventListener("click", function() {
            navMenu.classList.toggle("active");
        });
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    renderItems();
    initializeForm();
    initializeNavigation();
});