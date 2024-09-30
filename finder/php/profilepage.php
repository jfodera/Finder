<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Finder - Profile Page</title>
    <link rel="stylesheet" href="/finder/style.css">
</head>
<body>


    <div class="container">
        <div class="logo">Finder</div>
        <h1>Your Lost Items</h1>
        <div class="items-grid" id="itemsGrid"></div>
        <a href="report-lost.php" class="button">Report Lost Item</a>
    </div>

    <script>
        // Fake data based on the provided database structure
        const lostItems = [
            {
                item_id: 1,
                item_type: "Phone",
                description: "iPhone 12 Pro, Space Gray",
                location: "Central Park",
                lost_time: "2024-09-28 14:30:00",
                status: "lost",
                image_url: "/api/placeholder/250/150"
            },
            {
                item_id: 2,
                item_type: "Wallet",
                description: "Brown leather wallet with initials JD",
                location: "Main Street Coffee Shop",
                lost_time: "2024-09-27 09:15:00",
                status: "found",
                image_url: "/api/placeholder/250/150"
            },
            {
                item_id: 3,
                item_type: "Keys",
                description: "Car keys with a red keychain",
                location: "City Gym",
                lost_time: "2024-09-26 18:45:00",
                status: "lost",
                image_url: "/api/placeholder/250/150"
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
    </script>
</body>
</html>