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
    
    // Add submission lock
    let isSubmitting = false;

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

    // Single form submission handler
    if (infoForm) {
        infoForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('Form submission started');

            // Prevent duplicate submissions
            if (isSubmitting) {
                console.log('Form is already being submitted');
                return;
            }

            if (!validatePage(3)) {
                console.log('Validation failed');
                return;
            }

            // Set submission lock
            isSubmitting = true;

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
                // Reset submission lock and button state
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit';
            }
        });
    }
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
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        
        document.getElementById(tabName + '-items').classList.add('active');
        document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.add('active');
    }

    // Enhanced createItemCard function for recorders
    function createItemCard(item, isRecorder = false) {
        const cardHtml = `
            <div class="item-card">
                <img src="${item.image_url}" alt="${item.item_type}" class="item-image">
                <div class="item-type">${item.item_type}</div>
                <div class="item-description">
                    Brand: ${item.brand}<br>
                    Color: ${item.color}
                    ${item.additional_info ? `<br>Additional Info: ${item.additional_info}` : ''}
                </div>
                <div class="item-details">
                    ${item.lost_time ? `${isRecorder ? 'Found' : 'Lost'}: ${new Date(item.lost_time).toLocaleString()}<br>` : ''}
                    Status: ${item.status}<br>
                    ${item.locations ? `Locations: ${item.locations}` : ''}
                    ${isRecorder && item.reporter_email ? `<br>Reported by: ${item.reporter_email}` : ''}
                    ${isRecorder && item.recorder_email ? `<br>Recorded by: ${item.recorder_email}` : ''}
                </div>
                ${isRecorder && item.status === 'pending' ? 
                    `<button class="match-button" onclick="matchItem(${item.id})">Match Item</button>` 
                    : ''
                }
            </div>
        `;
        return cardHtml;
    }

    async function fetchAllItems() {
        try {
            const response = await fetch('getAllItems.php');
            if (!response.ok) throw new Error('Network response was not ok');
            return await response.json();
        } catch (error) {
            console.error('Error fetching items:', error);
            return { lost_items: [], found_items: [] };
        }
    }

    async function renderAllItems() {
        const lostItemsGrid = document.getElementById('lostItemsGrid');
        const foundItemsGrid = document.getElementById('foundItemsGrid');
        const isRecorder = document.querySelector('input[name="is_recorder"]:checked') ? true : false;

        try {
            const data = await fetchAllItems();
            // Render lost items
            if (lostItemsGrid) {
                if (data.lost_items && data.lost_items.length > 0) {
                    lostItemsGrid.innerHTML = data.lost_items.map(item => createItemCard(item, isRecorder)).join('');
                } else {
                    lostItemsGrid.innerHTML = '<p class="no-items">No lost items found.</p>';
                }
            }

            // Render found items if user is recorder
            if (isRecorder && foundItemsGrid) {
                if (data.found_items && data.found_items.length > 0) {
                    foundItemsGrid.innerHTML = data.found_items.map(item => createItemCard(item, true)).join('');
                } else {
                    foundItemsGrid.innerHTML = '<p class="no-items">No found items recorded yet.</p>';
                }
            }
        } catch (error) {
            const errorMessage = '<p class="error-message">Failed to load items.</p>';
            if (lostItemsGrid) lostItemsGrid.innerHTML = errorMessage;
            if (foundItemsGrid) foundItemsGrid.innerHTML = errorMessage;
        }
    }

    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
        renderAllItems();
        initializeNavigation();
    });
});

