function createItemCard(item) {
    return `
        <div class="item-card">
            <img src="${item.image_url || '../default_image.png'}" alt="${item.item_type}" class="item-image">
            <div class="item-type">${item.item_type}</div>
            <div class="item-description">
                Brand: ${item.brand}<br>
                Color: ${item.color}
                ${item.additional_info ? `<br>Additional Info: ${item.additional_info}` : ''}
            </div>
            <div class="item-details">
                Lost: ${new Date(item.lost_time).toLocaleString()}<br>
                Status: ${item.status}<br>
                ${item.locations ? `Locations: ${item.locations}` : ''}
            </div>
        </div>
    `;
}

async function fetchUserItems() {
    try {
        const response = await fetch('getUserItems.php');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        return data.items;
    } catch (error) {
        console.error('Error fetching items:', error);
        return [];
    }
}

async function renderItems() {
    const itemsGrid = document.getElementById('itemsGrid');
    if (itemsGrid) {
        try {
            const items = await fetchUserItems();
            if (items && items.length > 0) {
                itemsGrid.innerHTML = items.map(createItemCard).join('');
            } else {
                itemsGrid.innerHTML = '<p class="no-items">No items found.</p>';
            }
        } catch (error) {
            console.error('Error rendering items:', error);
            itemsGrid.innerHTML = '<p class="error-message">Failed to load items. Please try again later.</p>';
        }
    }
}

// Form navigation and validation
function initializeForm() {
    const infoForm = document.getElementById('infoForm');
    if (!infoForm) return;

    const pages = Array.from(document.querySelectorAll('#infoForm .page'));
    const nextBtns = document.querySelectorAll('.next-btn');
    const prevBtns = document.querySelectorAll('.prev-btn');
    const submitBtn = document.querySelector('.submit-btn');

    // Form validation
    function validatePage(pageIndex) {
        const page = pages[pageIndex];
        
        switch(pageIndex) {
            case 0: // First page - basic info
                const type = page.querySelector('input[name="type"]').value.trim();
                const brand = page.querySelector('input[name="brand"]').value.trim();
                const color = page.querySelector('input[name="color"]').value.trim();
                
                if (!type || !brand || !color) {
                    alert('Please fill in all required fields');
                    return false;
                }
                return true;

            case 1: // Second page - date
                const date = page.querySelector('input[name="date"]').value;
                if (!date) {
                    alert('Please select a date and time');
                    return false;
                }
                
                // Validate date is not in the future
                const selectedDate = new Date(date);
                const now = new Date();
                if (selectedDate > now) {
                    alert('Lost date cannot be in the future');
                    return false;
                }
                return true;

            case 2: // Third page - image
                // Image is optional, always valid
                return true;

            case 3: // Fourth page - locations
                const selectedLocations = document.querySelectorAll('input[name="locations[]"]:checked');
                if (selectedLocations.length === 0) {
                    alert('Please select at least one location');
                    return false;
                }
                return true;
        }
        return true;
    }

    // Navigation between pages
    function showPage(pageIndex) {
        pages.forEach((page, index) => {
            page.classList.toggle('active', index === pageIndex);
        });
    }

    nextBtns.forEach((btn, index) => {
        btn.addEventListener('click', () => {
            if (validatePage(index)) {
                showPage(index + 1);
            }
        });
    });

    prevBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const currentPageIndex = Array.from(pages).findIndex(page => 
                page.classList.contains('active')
            );
            showPage(currentPageIndex - 1);
        });
    });

    // Image handling
    const imageInput = document.getElementById('input-file');
    const imagePreview = document.getElementById('upload_image');

    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                // Validate file type
                const file = this.files[0];
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                
                if (!validTypes.includes(file.type)) {
                    alert('Please upload only JPEG or PNG images');
                    this.value = '';
                    imagePreview.src = '../default_image.png';
                    return;
                }

                // Validate file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Please upload an image smaller than 5MB');
                    this.value = '';
                    imagePreview.src = '../default_image.png';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Location search and selection
    initializeLocationHandling();

    // Form submission
    if (infoForm) {
        infoForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validatePage(3)) { // Validate final page
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            try {
                const formData = new FormData(this);
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                window.location.href = 'dashboard.php';
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while submitting the form. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit';
            }
        });
    }
    infoForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Form submission started');
    
        if (!validatePage(3)) {
            console.log('Validation failed');
            return;
        }
    
        // Disable submit button and show loading state
        const submitBtn = document.querySelector('.submit-btn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
    
        // Log the form data being sent
        const formData = new FormData(this);
        console.log('Form data being sent:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
    
        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
    
            console.log('Raw response:', response);
    
            // Try to parse the response as JSON
            let jsonResponse;
            try {
                const responseText = await response.text();
                console.log('Raw response text:', responseText);
                jsonResponse = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Failed to parse response:', parseError);
                throw new Error('Invalid response format');
            }
    
            console.log('Parsed response:', jsonResponse);
    
            if (jsonResponse.success) {
                console.log('Success! Redirecting to:', jsonResponse.redirect);
                window.location.href = jsonResponse.redirect;
            } else {
                console.error('Server reported error:', jsonResponse.message);
                if (jsonResponse.error_details) {
                    console.error('Error details:', jsonResponse.error_details);
                }
                alert(jsonResponse.message || 'An error occurred while submitting the form.');
            }
        } catch (error) {
            console.error('Submission error:', error);
            alert('An error occurred while submitting the form. Please check the console for details.');
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit';
        }
    });
}

// Location handling functionality
function initializeLocationHandling() {
    const searchBox = document.getElementById('locationSearch');
    const locationCheckboxes = document.querySelectorAll('.location-checkbox');
    const selectedList = document.getElementById('selectedList');
    const selectedCountSpan = document.getElementById('selectedCount');
    const submitBtn = document.querySelector('.submit-btn');

    function updateSelectedLocations() {
        const selectedBoxes = document.querySelectorAll('input[name="locations[]"]:checked');
        selectedList.innerHTML = '';
        selectedCountSpan.textContent = selectedBoxes.length;
        
        selectedBoxes.forEach(box => {
            const div = document.createElement('div');
            div.className = 'selected-item';
            div.innerHTML = `
                ${box.value}
                <button type="button" class="remove-location" data-id="${box.id}">×</button>
            `;
            selectedList.appendChild(div);
        });

        // Update submit button state
        if (submitBtn) {
            submitBtn.disabled = selectedBoxes.length === 0;
        }
    }

    // Handle location removal
    selectedList.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-location')) {
            const checkbox = document.getElementById(e.target.dataset.id);
            if (checkbox) {
                checkbox.checked = false;
                updateSelectedLocations();
            }
        }
    });

    function handleSearch() {
        const searchTerm = searchBox.value.toLowerCase();
        locationCheckboxes.forEach(checkbox => {
            const label = checkbox.querySelector('label').textContent.toLowerCase();
            const locationGroup = checkbox.closest('.location-group');
            const shouldShow = label.includes(searchTerm);
            checkbox.style.display = shouldShow ? 'block' : 'none';

            // Update group visibility
            if (locationGroup) {
                const visibleCheckboxes = Array.from(locationGroup.querySelectorAll('.location-checkbox'))
                    .some(cb => cb.style.display !== 'none');
                locationGroup.style.display = visibleCheckboxes ? 'block' : 'none';
            }
        });
    }

    if (searchBox) {
        searchBox.addEventListener('input', handleSearch);
    }

    if (locationCheckboxes.length) {
        locationCheckboxes.forEach(checkbox => {
            checkbox.querySelector('input').addEventListener('change', updateSelectedLocations);
        });
    }

    // Initialize selected locations
    updateSelectedLocations();
}

// Navigation and header functionality
function initializeNavigation() {
    // Active page highlighting
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.global-header nav ul li a');
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

