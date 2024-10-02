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
    itemsGrid.innerHTML = lostItems.map(createItemCard).join('');
}

document.addEventListener('DOMContentLoaded', renderItems);

document.addEventListener('DOMContentLoaded', function () {
    var currentPage = window.location.pathname.split('/').pop();
    var navLinks = document.querySelectorAll('.global-header nav ul li a');
    navLinks.forEach(function (link) {
        if (link.getAttribute('href') === currentPage) {
            link.parentElement.classList.add('active');
        }
    });
});