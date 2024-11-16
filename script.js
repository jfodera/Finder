function createItemCard(item, type = "lost") {
  const dateField = type === "lost" ? "lost_time" : "found_time";
  const statusClass = item.status.toLowerCase().replace(" ", "-");

  return `
        <div class="item-card ${statusClass}">
            <img src="${item.image_url || "../default_image.png"}" alt="${
    item.item_type
  }" class="item-image">
            <div class="item-header">
                <div class="item-type">${item.item_type}</div>
                <div class="item-status ${statusClass}">${item.status}</div>
            </div>
            <div class="item-description">
                <div><strong>Brand:</strong> ${item.brand || "N/A"}</div>
                <div><strong>Color:</strong> ${item.color || "N/A"}</div>
                ${
                  item.additional_info
                    ? `<div><strong>Additional Info:</strong> ${item.additional_info}</div>`
                    : ""
                }
            </div>
            <div class="item-details">
                <div><strong>${
                  type === "lost" ? "Lost" : "Found"
                } on:</strong> ${new Date(
    item[dateField]
  ).toLocaleString()}</div>
                <div><strong>Reported on:</strong> ${new Date(
                  item.created_at
                ).toLocaleString()}</div>
                <div><strong>Item ID:</strong> ${item.item_id}</div>
            </div>
        </div>
    `;
}

async function fetchItems(endpoint) {
  try {
    const response = await fetch(endpoint);
    if (!response.ok) {
      throw new Error("Network response was not ok");
    }
    const data = await response.json();
    return data.items;
  } catch (error) {
    console.error("Error fetching items:", error);
    return [];
  }
}

function initializeTabs() {
  const tabButtons = document.querySelectorAll(".tab-button");
  const tabContents = document.querySelectorAll(".tab-content");

  if (tabButtons.length > 0) {
    tabButtons[0].classList.add("active");
    if (tabContents.length > 0) {
      tabContents[0].classList.add("active");
    }
  }

  tabButtons.forEach((button) => {
    button.addEventListener("click", async () => {
      tabButtons.forEach((btn) => btn.classList.remove("active"));
      tabContents.forEach((content) => content.classList.remove("active"));

      button.classList.add("active");
      const baseId = button.dataset.tab;
      const tabId = window.isRecorder ? 
                    baseId + "ItemsGrid" : 
                    (baseId === 'matches' ? 'userMatchesGrid' : 'itemsGrid');
      
      const content = document.getElementById(tabId);
      if (content) {
        content.classList.add("active");
        if (baseId === 'matches') {
          await renderMatches();
        }
      }
    });
  });
}


async function renderItems() {
  if (window.isRecorder) {
    const lostItemsGrid = document.getElementById("lostItemsGrid");
    const foundItemsGrid = document.getElementById("foundItemsGrid");

    try {
      const [lostItems, foundItems] = await Promise.all([
        fetchItems("getLostItems.php"),
        fetchItems("getFoundItems.php")
      ]);

      if (lostItemsGrid) {
        lostItemsGrid.innerHTML = lostItems.length > 0
          ? lostItems.map(item => createItemCard(item, "lost")).join("")
          : '<p class="no-items">No lost items reported.</p>';
      }

      if (foundItemsGrid) {
        foundItemsGrid.innerHTML = foundItems.length > 0
          ? foundItems.map(item => createItemCard(item, "found")).join("")
          : '<p class="no-items">No found items reported.</p>';
      }
    } catch (error) {
      console.error("Error rendering items:", error);
      const errorMessage = '<p class="error-message">Failed to load items.</p>';
      if (lostItemsGrid) lostItemsGrid.innerHTML = errorMessage;
      if (foundItemsGrid) foundItemsGrid.innerHTML = errorMessage;
    }
  } else {
    const itemsGrid = document.getElementById("itemsGrid");
    if (!itemsGrid) return;

    try {
      const items = await fetchItems("getUserItems.php");
      itemsGrid.innerHTML = items.length > 0
        ? items.map(item => createItemCard(item)).join("")
        : '<p class="no-items">No items found.</p>';
    } catch (error) {
      console.error("Error rendering items:", error);
      itemsGrid.innerHTML = '<p class="error-message">Failed to load items.</p>';
    }
  }
}

function createMatchFlow(matches) {
  let html = `
      <div class="match-flow">
        <div class="match-column lost-items">
          <h3>Lost Items</h3>
          <div class="items-list">
            ${matches
              .map((match) => createFlowCard(match.lost_item, "lost"))
              .join("")}
          </div>
        </div>
        
        <div class="match-column connections">
          <h3>Connections</h3>
          <div class="connection-lines">
            ${matches
              .map(
                (match) => `
              <div class="connection" data-lost="${match.lost_item.item_id}" data-found="${match.found_item.item_id}">
                <div class="line"></div>
                <div class="match-status ${match.status}">${match.status}</div>
                <div class="match-actions">
                  <button onclick="handleMatch(${match.match_id}, 'confirm')" class="action-btn confirm">✓</button>
                  <button onclick="handleMatch(${match.match_id}, 'reject')" class="action-btn reject">✗</button>
                </div>
              </div>
            `
              )
              .join("")}
          </div>
        </div>
  
        <div class="match-column found-items">
          <h3>Found Items</h3>
          <div class="items-list">
            ${matches
              .map((match) => createFlowCard(match.found_item, "found"))
              .join("")}
          </div>
        </div>
      </div>
    `;

  return html;
}

function createFlowCard(item, type) {
  return `
      <div class="flow-card" data-id="${item.item_id}">
        <img src="${item.image_url || "../default_image.png"}" alt="${
    item.item_type
  }" class="item-thumb">
        <div class="flow-info">
          <div class="item-type">${item.item_type}</div>
          <div class="item-details">
            <span>${item.brand || "N/A"}</span>
            <span>${item.color || "N/A"}</span>
          </div>
        </div>
      </div>
    `;
}

function createUserMatchCard(match) {
  return `
      <div class="user-match-card ${match.status}">
        <div class="found-item-details">
          <img src="${
            match.found_item.image_url || "../default_image.png"
          }" alt="${match.found_item.item_type}" class="match-image">
          <div class="match-info">
            <h4>Potential Match Found!</h4>
            <p>Item Type: ${match.found_item.item_type}</p>
            <p>Brand: ${match.found_item.brand || "N/A"}</p>
            <p>Color: ${match.found_item.color || "N/A"}</p>
            <p>Found on: ${new Date(
              match.found_item.found_time
            ).toLocaleDateString()}</p>
            <p>Status: ${match.status}</p>
          </div>
        </div>
        ${
          match.status === "pending"
            ? `
          <div class="match-actions">
            <button onclick="handleUserMatch(${match.match_id}, 'confirm')" class="button confirm">This is my item</button>
            <button onclick="handleUserMatch(${match.match_id}, 'reject')" class="button reject">Not my item</button>
          </div>
        `
            : ""
        }
      </div>
    `;
}

async function handleMatch(matchId, action) {
  try {
    const response = await fetch('handleMatch.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ match_id: matchId, action: action })
    });
    
    if (!response.ok) throw new Error('Network response was not ok');
    
    const result = await response.json();
    if (result.success) {
      // Refresh both matches and items views after action
      await Promise.all([renderMatches(), renderItems()]);
    } else {
      alert(result.message || 'Failed to process match');
    }
  } catch (error) {
    console.error('Error handling match:', error);
    alert('An error occurred. Please try again.');
  }
}

async function handleUserMatch(matchId, action) {
  try {
    const response = await fetch('handleUserMatch.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ match_id: matchId, action: action })
    });
    
    if (!response.ok) throw new Error('Network response was not ok');
    
    const result = await response.json();
    if (result.success) {
      // Refresh both matches and items views after action
      await Promise.all([renderMatches(), renderItems()]);
    } else {
      alert(result.message || 'Failed to process match');
    }
  } catch (error) {
    console.error('Error handling user match:', error);
    alert('An error occurred. Please try again.');
  }
}
async function renderMatches() {
  // Clear current content first
  const matchesGrid = window.isRecorder ? 
    document.getElementById("matchesGrid") : 
    document.getElementById("userMatchesGrid");
    
  if (!matchesGrid) return;
  matchesGrid.innerHTML = '<div class="loading">Loading matches...</div>';

  try {
    const matches = await fetchItems("getMatches.php");

    if (window.isRecorder) {
      matchesGrid.innerHTML = matches.length 
        ? createMatchFlow(matches)
        : '<p class="no-items">No potential matches found.</p>';
      
      if (matches.length) {
        matches.forEach(match => {
          const actionBtns = matchesGrid.querySelectorAll(`[data-match-id="${match.match_id}"] .action-btn`);
          actionBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
              const action = btn.classList.contains('confirm') ? 'confirm' : 'reject';
              handleMatch(match.match_id, action);
            });
          });
        });
      }
    } else {
      matchesGrid.innerHTML = matches.length
        ? matches.map(match => createUserMatchCard(match)).join("")
        : '<p class="no-items">No potential matches found for your items.</p>';
    }
  } catch (error) {
    console.error("Error rendering matches:", error);
    matchesGrid.innerHTML = '<p class="error-message">Failed to load matches.</p>';
  }
}

async function renderItems() {
  if (window.isRecorder) {
    const lostItemsGrid = document.getElementById("lostItemsGrid");
    const foundItemsGrid = document.getElementById("foundItemsGrid");

    try {
      // Fetch lost items
      const lostItems = await fetchItems("getLostItems.php");
      if (lostItemsGrid) {
        console.log("Lost items:", lostItems); // Debug log
        if (lostItems && lostItems.length > 0) {
          lostItemsGrid.innerHTML = lostItems
            .map((item) => createItemCard(item, "lost"))
            .join("");
        } else {
          lostItemsGrid.innerHTML =
            '<p class="no-items">No lost items reported.</p>';
        }
      }

      // Fetch found items
      const foundItems = await fetchItems("getFoundItems.php");
      if (foundItemsGrid) {
        console.log("Found items:", foundItems); // Debug log
        if (foundItems && foundItems.length > 0) {
          foundItemsGrid.innerHTML = foundItems
            .map((item) => createItemCard(item, "found"))
            .join("");
        } else {
          foundItemsGrid.innerHTML =
            '<p class="no-items">No found items reported.</p>';
        }
      }

      // Ensure tabs are initialized after content is loaded
      initializeTabs();
    } catch (error) {
      console.error("Error rendering items:", error);
      if (lostItemsGrid) {
        lostItemsGrid.innerHTML =
          '<p class="error-message">Failed to load lost items.</p>';
      }
      if (foundItemsGrid) {
        foundItemsGrid.innerHTML =
          '<p class="error-message">Failed to load found items.</p>';
      }
    }
  } else {
    // Regular user view - fetch and display only their lost items
    const itemsGrid = document.getElementById("itemsGrid");
    if (itemsGrid) {
      try {
        const items = await fetchItems("getUserItems.php");
        if (items && items.length > 0) {
          itemsGrid.innerHTML = items
            .map((item) => createItemCard(item))
            .join("");
        } else {
          itemsGrid.innerHTML = '<p class="no-items">No items found.</p>';
        }
      } catch (error) {
        console.error("Error rendering items:", error);
        itemsGrid.innerHTML =
          '<p class="error-message">Failed to load items. Please try again later.</p>';
      }
    }
  }
}

// Form navigation and validation
function initializeForm() {
  const infoForm = document.getElementById("infoForm");
  if (!infoForm) return;

  const pages = Array.from(document.querySelectorAll("#infoForm .page"));
  const nextBtns = document.querySelectorAll(".next-btn");
  const prevBtns = document.querySelectorAll(".prev-btn");
  const submitBtn = document.querySelector(".submit-btn");

  // Add submission lock
  let isSubmitting = false;

  // Form validation
  function validatePage(pageIndex) {
    const page = pages[pageIndex];

    switch (pageIndex) {
      case 0: // First page - basic info
      //called when set and go to next page
        const type = page.querySelector('input[name="type"]').value.trim();
        const brand = page.querySelector('input[name="brand"]').value.trim();
        const color = page.querySelector('input[name="color"]').value.trim();

        if (!type || !brand || !color) {
          alert("Please fill in all required fields");
          return false;
        }else if(type.length <= 3){
          alert("Please add better description for 'type' field");
          return false;
        }else if(brand.length <= 3){
          alert("Please add better description for 'brand' field");
          return false;
        }else if(color.length <= 2){
          alert("Please add better description for 'color' field");
          return false;
        }
        
        
        return true;

      case 1: // Second page - date
        const date = page.querySelector('input[name="date"]').value;
        if (!date) {
          alert("Please select a date and time");
          return false;
        }

        // Validate date is not in the future
        const selectedDate = new Date(date);
        const now = new Date();
        if (selectedDate > now) {
          alert("Lost date cannot be in the future");
          return false;
        }
        return true;

      case 2: // Third page - image
        // Image is optional, always valid
        return true;

      case 3: // Fourth page - locations
        const selectedLocations = document.querySelectorAll(
          'input[name="locations[]"]:checked'
        );
        if (selectedLocations.length === 0) {
          alert("Please select at least one location");
          return false;
        }
        return true;
    }
    return true;
  }

  // Navigation between pages
  function showPage(pageIndex) {
    pages.forEach((page, index) => {
      //set the active one to the one passed as param
      page.classList.toggle("active", index === pageIndex);
    });
    $("#pgnum").html(pageIndex+1); 
  }

  nextBtns.forEach((btn, index) => {
    btn.addEventListener("click", () => {
      if (validatePage(index)) {
        showPage(index + 1);
      }
    });
  });

  prevBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const currentPageIndex = Array.from(pages).findIndex((page) =>
        page.classList.contains("active")
      );
      showPage(currentPageIndex - 1);
    });
  });

  // Image handling
  const imageInput = document.getElementById("input-file");
  const imagePreview = document.getElementById("upload_image");

  if (imageInput) {
    imageInput.addEventListener("change", function (e) {
      if (this.files && this.files[0]) {
        // Validate file type
        const file = this.files[0];
        const validTypes = ["image/jpeg", "image/png", "image/jpg"];

        if (!validTypes.includes(file.type)) {
          alert("Please upload only JPEG or PNG images");
          this.value = "";
          imagePreview.src = "../default_image.png";
          return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          alert("Please upload an image smaller than 5MB");
          this.value = "";
          imagePreview.src = "../default_image.png";
          return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function (e) {
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
    infoForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      console.log("Form submission started");

      // Prevent duplicate submissions
      if (isSubmitting) {
        console.log("Form is already being submitted");
        return;
      }

      if (!validatePage(3)) {
        console.log("Validation failed");
        return;
      }

      // Set submission lock
      isSubmitting = true;

      // Disable submit button and show loading state
      const submitBtn = document.querySelector(".submit-btn");
      submitBtn.disabled = true;
      submitBtn.textContent = "Submitting...";

      // Log the form data being sent
      const formData = new FormData(this);
      console.log("Form data being sent:");
      for (let pair of formData.entries()) {
        console.log(pair[0] + ": " + pair[1]);
      }

      try {
        const response = await fetch(this.action, {
          method: "POST",
          body: formData,
        });

        console.log("Raw response:", response);

        // Try to parse the response as JSON
        let jsonResponse;
        try {
          const responseText = await response.text();
          console.log("Raw response text:", responseText);
          jsonResponse = JSON.parse(responseText);
        } catch (parseError) {
          console.error("Failed to parse response:", parseError);
          throw new Error("Invalid response format");
        }

        console.log("Parsed response:", jsonResponse);

        if (jsonResponse.success) {
          console.log("Success! Redirecting to:", jsonResponse.redirect);
          window.location.href = jsonResponse.redirect;
        } else {
          console.error("Server reported error:", jsonResponse.message);
          if (jsonResponse.error_details) {
            console.error("Error details:", jsonResponse.error_details);
          }
          alert(
            jsonResponse.message ||
              "An error occurred while submitting the form."
          );
        }
      } catch (error) {
        console.error("Submission error:", error);
        alert(
          "An error occurred while submitting the form. Please check the console for details."
        );
      } finally {
        // Reset submission lock and button state
        isSubmitting = false;
        submitBtn.disabled = false;
        submitBtn.textContent = "Submit";
      }
    });
  }
}

// Location handling functionality
function initializeLocationHandling() {
  const searchBox = document.getElementById("locationSearch");
  const locationCheckboxes = document.querySelectorAll(".location-checkbox");
  const selectedList = document.getElementById("selectedList");
  const selectedCountSpan = document.getElementById("selectedCount");
  const submitBtn = document.querySelector(".submit-btn");

  function updateSelectedLocations() {
    const selectedBoxes = document.querySelectorAll(
      'input[name="locations[]"]:checked'
    );
    selectedList.innerHTML = "";
    selectedCountSpan.textContent = selectedBoxes.length;

    selectedBoxes.forEach((box) => {
      const div = document.createElement("div");
      div.className = "selected-item";
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
  selectedList.addEventListener("click", (e) => {
    if (e.target.classList.contains("remove-location")) {
      const checkbox = document.getElementById(e.target.dataset.id);
      if (checkbox) {
        checkbox.checked = false;
        updateSelectedLocations();
      }
    }
  });

  function handleSearch() {
    const searchTerm = searchBox.value.toLowerCase();
    locationCheckboxes.forEach((checkbox) => {
      const label = checkbox.querySelector("label").textContent.toLowerCase();
      const locationGroup = checkbox.closest(".location-group");
      const shouldShow = label.includes(searchTerm);
      checkbox.style.display = shouldShow ? "block" : "none";

      // Update group visibility
      if (locationGroup) {
        const visibleCheckboxes = Array.from(
          locationGroup.querySelectorAll(".location-checkbox")
        ).some((cb) => cb.style.display !== "none");
        locationGroup.style.display = visibleCheckboxes ? "block" : "none";
      }
    });
  }

  if (searchBox) {
    searchBox.addEventListener("input", handleSearch);
  }

  if (locationCheckboxes.length) {
    locationCheckboxes.forEach((checkbox) => {
      checkbox
        .querySelector("input")
        .addEventListener("change", updateSelectedLocations);
    });
  }

  // Initialize selected locations
  updateSelectedLocations();
}

// Navigation and header functionality
function initializeNavigation() {
  // Active page highlighting
  const currentPage = window.location.pathname.split("/").pop();
  const navLinks = document.querySelectorAll(".global-header nav ul li a");
  navLinks.forEach(function (link) {
    if (link.getAttribute("href") === currentPage) {
      link.parentElement.classList.add("active");
    }
  });

  // Mobile menu toggle
  const hamburger = document.getElementById("hamburger");
  const navMenu = document.getElementById("nav-menu");
  if (hamburger && navMenu) {
    hamburger.addEventListener("click", function () {
      navMenu.classList.toggle("active");
    });
  }
}

document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM loaded, initializing..."); 
  console.log("Is recorder:", window.isRecorder); 
  renderItems();
  renderMatches();
  initializeForm();
  initializeNavigation();
});

document.addEventListener('visibilitychange', () => {
  if (!document.hidden && document.querySelector('.tab-button[data-tab="matches"].active')) {
    renderMatches();
  }
});
