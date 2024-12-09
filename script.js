function createItemCard(item, type) {
  const dateField = type === "lost" ? "lost_time" : "found_time";
  const statusClass = item.status.toLowerCase().replace(" ", "-");


    // Determine which field to show based on type
    // if type is lost, show reporter_name
    // if type is found, show recorder_name

  let creatorLine = "";
  if (type === "lost") {
    creatorLine = `<div><strong>Reported by:</strong> ${item.reporter_name || "N/A"}</div>`;
  } else if (type === "found") {
    creatorLine = `<div><strong>Found by:</strong> ${item.recorder_name || "N/A"}</div>`;
  }

  return `
        <div class="item-card ${statusClass}">
            <div class="item-image-container" onclick="openImageModal('${item.image_url || "./../assets/placeholderImg.svg"}', '${item.item_type}')">
                <img src="${item.image_url || "./../assets/placeholderImg.svg"}" 
                     alt="${item.item_type}" 
                     class="item-image">
            </div>

            <div class="item-header">
                <div class="item-type">${item.item_type}</div>
                <div>${item.brand || "N/A"} | ${item.color || "N/A"}</div>
                ${item.status !== 'lost' ? `
                <div class="item-status ${statusClass}">${item.status}</div>
                ` : ''}
            </div>

            <div class="item-description">
                ${item.additional_info ? `<div><strong>Additional Info:</strong> ${item.additional_info}</div>` : ""}
                <div><strong>Location:</strong> ${item.locations || "N/A"}</div>
                ${creatorLine}
            </div>

            <div class="item-details">
                <div><strong>${type === "lost" ? "Lost" : "Found"} on:</strong> ${new Date(item[dateField]).toLocaleString()}</div>
                <div><strong>Reported on:</strong> ${new Date(item.created_at).toLocaleString()}</div>
                ${window.isRecorder ? `<div><strong>Item ID:</strong> ${item.item_id}</div>` : ''}
            </div>
        </div>
    `;
}

// Fetch items from the db
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

// function to initialize the tabs
function initializeTabs() {
  const tabButtons = document.querySelectorAll(".tab-button");
  const tabContents = document.querySelectorAll(".tab-content");

  // Set the first tab as active by default
  if (tabButtons.length > 0) {
    tabButtons[0].classList.add("active");
    if (tabContents.length > 0) {
      tabContents[0].classList.add("active");
    }
  }

  // allows the user to switch between tabs and renders the content of each tab
  tabButtons.forEach((button) => {
    button.addEventListener("click", async () => {
      
      // when a tab is clicked, remove the active class from all tabs and contents
      tabButtons.forEach((btn) => btn.classList.remove("active"));
      tabContents.forEach((content) => content.classList.remove("active"));

     //adds active ot button that was clicked 
      button.classList.add("active");
      //defines base id 'aka 'matches''
      const baseId = button.dataset.tab;
      
      //defining how we are going to identify content picker 
      var tabId = 'null';
      if(window.isRecorder){
        if(baseId == 'matches'){
          tabId = 'matchesGrid'; 
        }else{
          tabId = baseId + 'ItemsGrid'; 
        }
      }else{ 
        if(baseId == 'matches'){
          tabId = 'userMatchesGrid'; 
        }else{
          tabId = 'itemsGrid'; 
        }
      }

      const content = document.getElementById(tabId);
      // if the tab is maches, render the matches and if the tab is lost or found, render the items
      if (content) {
        content.classList.add("active");
        if (baseId === 'matches') {
          await renderMatches();
        } else if (baseId === 'lost' || baseId === 'found') {
          await renderItems();
        }
      }
    });
  });
}

// function to render everything on dasboard
async function renderItems() {

  // if the user is a recorder, render the items for the recorder
  if (window.isRecorder) {
    // Get active tab
    const activeTab = document.querySelector('.tab-button.active').dataset.tab;
    const lostItemsGrid = document.getElementById("lostItemsGrid");
    const foundItemsGrid = document.getElementById("foundItemsGrid");

    try {
      // if active tab is lost, fetch lost items
      if (activeTab === 'lost') {
        const lostItems = await fetchItems("getLostItems.php");
        if (lostItemsGrid) {
          lostItemsGrid.innerHTML = lostItems.length > 0
            ? lostItems.map(item => createItemCard(item, "lost")).join("")
            : '<p class="no-items">No lost items reported.</p>';
            backgroundResize();
        }
      }
      // if active tab is found, fetch found items
      else if (activeTab === 'found') {
        const foundItems = await fetchItems("getFoundItems.php");
        if (foundItemsGrid) {
          foundItemsGrid.innerHTML = foundItems.length > 0
            ? foundItems.map(item => createItemCard(item, "found")).join("")
            : '<p class="no-items">No found items reported.</p>';
            backgroundResize();
        }
      }
    } catch (error) {
      console.error("Error rendering items:", error);
      const errorMessage = '<p class="error-message">Failed to load items.</p>';
      if (activeTab === 'lost' && lostItemsGrid) {
        lostItemsGrid.innerHTML = errorMessage;
      }
      if (activeTab === 'found' && foundItemsGrid) {
        foundItemsGrid.innerHTML = errorMessage;
      }
    }
  } else {
    // else render the items for the regular user
    const itemsGrid = document.getElementById("itemsGrid");
    if (!itemsGrid) return;
    // calls getUserItems.php to fetch all lost items for the user
    try {
      const items = await fetchItems("getUserItems.php");
      itemsGrid.innerHTML = items.length > 0
        ? items.map(item => createItemCard(item, "lost")).join("")
        : '<p class="no-items">No items found.</p>';
    } catch (error) {
      console.error("Error rendering items:", error);
      itemsGrid.innerHTML = '<p class="error-message">Failed to load items.</p>';
    }
  }
}

// function to create the match flow 
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
                (match) => {
                  const displayStatus = match.status === 'confirmed' ? 'claimed' : match.status;
                  return `
                    <div class="connection" data-lost="${match.lost_item.item_id}" data-found="${match.found_item.item_id}">
                      <div class="line"></div>
                      <div class="match-status ${displayStatus}">${displayStatus}</div>
                      <div class="match-actions">
                        <button onclick="handleMatch(${match.match_id}, 'confirm')" class="action-btn confirm">✓</button>
                        <button onclick="handleMatch(${match.match_id}, 'reject')" class="action-btn reject">✗</button>
                      </div>
                    </div>
                  `;
                }
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

// creates the flow card for the match flow
function createFlowCard(item, type) {
  return `
      <div class="flow-card" data-id="${item.item_id}">
        <img src="${item.image_url || "./../assets/placeholderImg.svg"}" alt="${
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


//here the user never actually accepts or rejects anything 
// idea behind this was for user to go to pubsafe
// and pubsafe would reject or accept the match
function createUserMatchCard(match) {
  return `
      <div class="user-match-card ${match.status}">
        <div class="found-item-details">
          <img src="${
            match.found_item.image_url || "./../assets/placeholderImg.svg"
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
      </div>
    `;
}

// function to handle the match for the recorder
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

//Never Used -> future implementation
// // function to handle the match for the user
// async function handleUserMatch(matchId, action) {
//   try {
//     const response = await fetch('handleUserMatch.php', {
//       method: 'POST',
//       headers: { 'Content-Type': 'application/json' },
//       body: JSON.stringify({ match_id: matchId, action: action })
//     });
    
//     if (!response.ok) throw new Error('Network response was not ok');
    
//     const result = await response.json();
//     if (result.success) {
//       // Refresh both matches and items views after action
//       await Promise.all([renderMatches(), renderItems()]);
//     } else {
//       alert(result.message || 'Failed to process match');
//     }
//   } catch (error) {
//     console.error('Error handling user match:', error);
//     alert('An error occurred. Please try again.');
//   }
// }

// function to render the matches
async function renderMatches() {
  // Clear current content first
  const matchesGrid = window.isRecorder ? 
    document.getElementById("matchesGrid") : 
    document.getElementById("userMatchesGrid");
    
  if (!matchesGrid) return;
  matchesGrid.innerHTML = '<div class="loading">Loading matches...</div>';
  updateMatchHighlighting();

  // calls getMatches.php to fetch all the matches
  try {
    const matches = await fetchItems("getMatches.php");

    if (window.isRecorder) {
      matchesGrid.innerHTML = matches.length 
        ? createMatchFlow(matches)
        : '<p class="no-items">No potential matches found.</p>';
      
      // if there are matches, the recorder can confirm or reject the match
      if (matches.length) {
        // when the recorder clicks on check mark it will confirm the match
        // when the recorder clicks on the x it will reject the match
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
          imagePreview.src = "./../assets/placeholderImg.svg";
          return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          alert("Please upload an image smaller than 5MB");
          this.value = "";
          imagePreview.src = "./../assets/placeholderImg.svg";
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
      //on submit do this: 
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

document.addEventListener('DOMContentLoaded', function() {
  console.log("DOM loaded, initializing...");
  console.log("Is recorder:", window.isRecorder);
  
  if (typeof newMatchesCount !== 'undefined' && newMatchNotification) {
      showMatchNotification(newMatchesCount);
      const matchesTab = document.querySelector('.tab-button[data-tab="matches"]');
      if (matchesTab) {
          matchesTab.click();
      }
  }
  initializeTabs();
  renderItems();
  renderMatches();
  initializeForm();
  initializeNavigation();
  
  const newStyles = `
      .match-notification {
          position: fixed;
          top: 20px;
          right: 20px;
          background: #4CAF50;
          color: white;
          padding: 15px 25px;
          border-radius: 8px;
          box-shadow: 0 2px 10px rgba(0,0,0,0.1);
          transition: opacity 0.3s ease;
          z-index: 1000;
          opacity: 1;
      }

      .notification-content {
          display: flex;
          align-items: center;
          gap: 10px;
      }

      .notification-icon {
          font-size: 20px;
      }

      .notification-close {
          background: none;
          border: none;
          color: white;
          cursor: pointer;
          font-size: 20px;
          padding: 0 5px;
          margin-left: 10px;
      }

      .notification-close:hover {
          transform: scale(1.1);
      }

      .new-match {
          animation: highlightMatch 5s ease-out;
      }

      @keyframes highlightMatch {
          0%, 100% {
              background-color: transparent;
          }
          50% {
              background-color: rgba(76, 175, 80, 0.1);
          }
      }
  `;
  
  const styleSheet = document.createElement("style");
  styleSheet.textContent = newStyles;
  document.head.appendChild(styleSheet);
});

document.addEventListener('visibilitychange', () => {
  if (!document.hidden && document.querySelector('.tab-button[data-tab="matches"].active')) {
    renderMatches();
  }
});

function showMatchNotification(count) {
  const notification = document.createElement('div');
  notification.className = 'match-notification';
  notification.innerHTML = `
      <div class="notification-content">
          <span class="notification-icon">🔍</span>
          <span class="notification-text">
              ${count} new potential match${count > 1 ? 'es' : ''} found!
          </span>
          <button class="notification-close">×</button>
      </div>
  `;
  
  document.body.appendChild(notification);
  
  // Add close button functionality
  notification.querySelector('.notification-close').addEventListener('click', () => {
      notification.style.opacity = '0';
      setTimeout(() => notification.remove(), 500);
  });
  
  // Auto-hide after 5 seconds
  setTimeout(() => {
      notification.style.opacity = '0';
      setTimeout(() => notification.remove(), 500);
  }, 5000);
}

function updateMatchHighlighting() {
  if (typeof newMatchNotification !== 'undefined' && newMatchNotification) {
      const matchCards = document.querySelectorAll('.match-card, .flow-card, .user-match-card');
      matchCards.forEach(card => {
          card.classList.add('new-match');
          setTimeout(() => {
              card.classList.remove('new-match');
          }, 5000);
      });
  }
}

function handleImagePreview(imageInput, previewElement) {
  imageInput.addEventListener("change", function(e) {
    if (this.files && this.files[0]) {
      const file = this.files[0];
      const validTypes = ["image/jpeg", "image/png", "image/jpg"];

      if (!validTypes.includes(file.type)) {
        alert("Please upload only JPEG or PNG images");
        this.value = "";
        previewElement.src = ".././../assets/placeholderImg.svg";
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        alert("Please upload an image smaller than 5MB");
        this.value = "";
        previewElement.src = "./../assets/placeholderImg.svg";
        return;
      }

      const reader = new FileReader();
      reader.onload = function(e) {
        // Create a temporary image to get dimensions
        const img = new Image();
        img.onload = function() {
          previewElement.src = e.target.result;
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });
}

// Initialize image handling in form initialization
const imageInput = document.getElementById("input-file");
const imagePreview = document.getElementById("upload_image");
if (imageInput && imagePreview) {
  handleImagePreview(imageInput, imagePreview);
}

// Image modal functionality
function openImageModal(imageSrc, title) {
  const modal = document.getElementById('imageModal');
  const modalImg = document.getElementById('modalImage');
  const modalTitle = document.getElementById('modalTitle');
  
  modal.style.display = "flex";
  modalImg.src = imageSrc;
  modalTitle.textContent = title;
  
  // fade in modal effect
  setTimeout(() => {
      modal.style.opacity = "1";
  }, 100);
}

// Close image modal
function closeImageModal() {
  const modal = document.getElementById('imageModal');
  modal.style.opacity = "0";
  setTimeout(() => {
      modal.style.display = "none";
  }, 300);
}

// Close modal when clicking outside the image
window.onclick = function(event) {
  const modal = document.getElementById('imageModal');
  if (event.target === modal) {
      closeImageModal();
  }
}

function backgroundResize() {
  const body = document.querySelector('body');
  var bodyHeight;
  setTimeout(function () {
    bodyHeight = body.offsetHeight
    document.querySelector('.under').style.height = String(bodyHeight-1) + 'px';
  
  document.documentElement.style.setProperty('--background-under-height', String(bodyHeight * -1.2) + 'px');
  renderBubbles();
  }, 100);
  
}

function renderBubbles() {
  const bubble_container = document.querySelector(".under");
  for (var i = 0; i < 20; i++) {
      var new_bubble = '<div class="bubble" style="'
      bubble_left = String(Math.random() * 90 + 5) + '%'
      bubble_width =  String(Math.random() * 30 + 10) + 'px'
      bubble_delay =  String(Math.random() * 10) + 's'
      bubble_duration = String(Math.random() * 12 + 3) + 's'
      var style = `left:${bubble_left}; width:${bubble_width}; 
      animation-delay:${bubble_delay}; animation-duration:${bubble_duration};`
      new_bubble += `${style}"></div>`
      bubble_container.innerHTML += new_bubble
  }
}

window.onload = function() {
  backgroundResize();
}