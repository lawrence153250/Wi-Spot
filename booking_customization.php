<?php
// Start output buffering to catch accidental output
ob_start();

session_start();

// Then include your other files
include 'chatbot-widget.html';
require_once 'config.php';

// Initialize variables from calculator if they exist
$calculated_speed = $_POST['speed'] ?? null;
$calculated_users = $_POST['users'] ?? null;

// Initialize other form fields
$event_type = '';
$budget = '';
$area_size = '';
$recommendation = '';
$package = null;
$showEquipmentForm = false;

// Check for coverage data from mapcoverage.php
if (isset($_SESSION['coverage_data'])) {
    $area_size = $_SESSION['coverage_data']['area_size'] ?? '';
    
    // Check if recommendation is for Advance Kit with additional EAPs
    if (($_SESSION['coverage_data']['recommended_package'] ?? '') === 'Advance Kit' && 
        ($_SESSION['coverage_data']['additional_eaps'] ?? 0) > 0) {
        $showEquipmentForm = true;
        $recommendation = "Based on your coverage analysis, we recommend the Advance Kit plus " . 
                         $_SESSION['coverage_data']['additional_eaps'] . " additional EAP110-Outdoor V3 routers.";
    }
    
    // Clear the coverage data after use
    unset($_SESSION['coverage_data']);
}

// Equipment pricing rules
$equipmentPrices = [
    'Wifi Router' => 300,
    'Wifi Extender' => 100,
    'Ethernet Cable' => 50,
    'Network Switch' => 500,
    'EAP110-Outdoor V3' => 250 // Added EAP110 pricing
];
$defaultPrice = 300;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_booking'])) {
        // Get form data
        $speed = $_POST['speed'] ?? $calculated_speed;
        $users = $_POST['users'] ?? $calculated_users;
        $event_type = $_POST['event_type'] ?? '';
        $budget = $_POST['budget'] ?? '';
        $area_size = $_POST['area_size'] ?? '';

        // Process equipment selection if any
        $selectedEquipment = [];
        $totalAdditionalPrice = 0;

        if (!empty($_POST['equipment_ids']) && !empty($_POST['quantities'])) {
            foreach ($_POST['equipment_ids'] as $index => $itemId) {
                $quantity = $_POST['quantities'][$index] ?? 1;
                
                // Get equipment details from inventory
                $query = "SELECT itemId, itemName, itemType FROM inventory WHERE itemId = '$itemId' AND status = 'available'";
                $result = mysqli_query($conn, $query);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $equipment = mysqli_fetch_assoc($result);
                    
                    // Determine price based on itemType
                    $price = $defaultPrice;
                    foreach ($equipmentPrices as $type => $typePrice) {
                        if (strcasecmp(trim($equipment['itemType']), $type) === 0) {
                            $price = $typePrice;
                            break;
                        }
                    }
                    
                    $selectedEquipment[] = [
                        'id' => $equipment['itemId'],
                        'name' => $equipment['itemName'],
                        'type' => $equipment['itemType'],
                        'price' => $price,
                        'quantity' => $quantity
                    ];
                    
                    $totalAdditionalPrice += $price * $quantity;
                }
            }
        }

        // Find matching package from database
        // First try to find an exact match
        $query = "SELECT * FROM package WHERE 
                  expectedBandwidth >= '$speed' AND 
                  numberOfUsers >= '$users' AND
                  eventAreaSize >= '$area_size' AND
                  eventType = '$event_type' AND
                  status = 'available'
                  ORDER BY price ASC LIMIT 1";
        $result = mysqli_query($conn, $query);
        $package = mysqli_fetch_assoc($result);

        if (!$package) {
            // If no exact match, find the closest available package
            $query = "SELECT * FROM package WHERE 
                      (expectedBandwidth >= '$speed' OR numberOfUsers >= '$users' OR eventAreaSize >= '$area_size')
                      AND status = 'available'
                      ORDER BY 
                      ABS(expectedBandwidth - '$speed') + 
                      ABS(numberOfUsers - '$users') + 
                      ABS(eventAreaSize - '$area_size') ASC
                      LIMIT 1";
            $result = mysqli_query($conn, $query);
            $package = mysqli_fetch_assoc($result);

            if ($package) {
                $recommendation = "We couldn't find an exact match, but we recommend our '{$package['packageName']}' package. ";
                
                // Additional recommendations based on requirements
                $needs = [];
                if ($speed > $package['expectedBandwidth']) {
                    $needs[] = "upgrade to higher bandwidth (needed: {$speed}Mbps, package: {$package['expectedBandwidth']}Mbps)";
                }
                if ($users > $package['numberOfUsers']) {
                    $needs[] = "additional equipment for more users (needed: {$users}, package: {$package['numberOfUsers']})";
                }
                if ($area_size > $package['eventAreaSize']) {
                    // Calculate additional routers needed (1 router covers 200sqm)
                    $additionalRouters = ceil(($area_size - $package['eventAreaSize']) / 200);
                    $needs[] = "extra coverage for larger area (needed: {$area_size}sqm, package: {$package['eventAreaSize']}sqm) - requires {$additionalRouters} additional EAP110-Outdoor V3 routers";
                }
                if ($event_type != $package['eventType']) {
                    $needs[] = "different event type setup (needed: {$event_type}, package: {$package['eventType']})";
                }

                if (!empty($needs)) {
                    $recommendation .= "You may need: " . implode(', ', $needs) . ". ";
                }
                
                $recommendation .= "Please contact our team for customization options.";
                
                // Check if the recommendation is for Advanced Kit with larger area needed
                if (strpos($package['packageName'], 'Advanced Kit') !== false && 
                    $area_size > $package['eventAreaSize']) {
                    $showEquipmentForm = true;
                }
            } else {
                $recommendation = "No suitable package found. Our team can create a custom solution for your event.";
                $showEquipmentForm = true;
            }
        }
        } elseif (isset($_POST['add_equipment'])) {
            // Handle adding equipment and returning to booking.php
            $equipmentData = [];
            if (!empty($_POST['equipment_ids']) && !empty($_POST['quantities'])) {
                foreach ($_POST['equipment_ids'] as $index => $itemId) {
                    $quantity = $_POST['quantities'][$index] ?? 1;
                    
                    // Get equipment details from inventory
                    $query = "SELECT itemId, itemName, itemType FROM inventory WHERE itemId = '$itemId' AND status = 'available'";
                    $result = mysqli_query($conn, $query);
                    
                    if ($result && mysqli_num_rows($result) > 0) {
                        $equipment = mysqli_fetch_assoc($result);
                        
                        // Determine price based on itemType
                        $price = $defaultPrice;
                        foreach ($equipmentPrices as $type => $typePrice) {
                            if (strcasecmp(trim($equipment['itemType']), $type) === 0) {
                                $price = $typePrice;
                                break;
                            }
                        }
                        
                        $equipmentData[] = [
                            'id' => $equipment['itemId'],
                            'name' => $equipment['itemName'],
                            'type' => $equipment['itemType'],
                            'price' => $price,
                            'quantity' => $quantity
                        ];
                    }
                }
            }
            
            // Store equipment data in session and redirect
            $_SESSION['selected_equipment'] = $equipmentData;
            header("Location: booking.php");
            exit();
        }
}

// Get available equipment for the form
$availableEquipment = [];
$query = "SELECT itemId, itemName, itemType, quantity FROM inventory WHERE status = 'available' AND quantity > 0";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $availableEquipment[] = $row;
    }
}

// If we have additional EAPs from coverage data, pre-select them
if (isset($_SESSION['coverage_data']['additional_eaps'])) {
    $eap110s = array_filter($availableEquipment, function($item) {
        return $item['itemType'] === 'EAP110-Outdoor V3';
    });
    
    if (!empty($eap110s)) {
        $eap110 = reset($eap110s);
        $selectedEquipment[] = [
            'id' => $eap110['itemId'],
            'name' => $eap110['itemName'],
            'type' => $eap110['itemType'],
            'price' => $equipmentPrices['EAP110-Outdoor V3'],
            'quantity' => $_SESSION['coverage_data']['additional_eaps']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Customization</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="bookingcustomstyle.css">
</head>
<body style="background-color: #f0f3fa;"> 
<nav class="navbar navbar-expand-lg navbar-dark" id="grad">
    <div class="container">
        <a class="navbar-brand" href="index.php"><img src="logoo.png" class="logo"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="booking.php">BOOKING</a></li>
                <li class="nav-item"><a class="nav-link" href="mapcoverage.php">MAP COVERAGE</a></li>
                <li class="nav-item"><a class="nav-link" href="customer_voucher.php">VOUCHERS</a></li>
                <li class="nav-item"><a class="nav-link" href="aboutus.php">ABOUT US</a></li>
            </ul>

            <?php if (isset($_SESSION['username'])): ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><?= htmlspecialchars($_SESSION['username']) ?> <i class="bi bi-person-circle"></i></a>
                    </li>
                </ul>
            <?php else: ?>
                <div class="auth-buttons d-flex flex-column flex-lg-row ms-lg-auto gap-2 mt-2 mt-lg-0">
                    <a class="btn btn-primary" href="login.php">LOGIN</a>
                    <a class="nav-link" href="register.php">SIGN UP</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

    <div class="container">
        <h1>Customize Your Booking</h1>
        
        <form method="post" style="margin-top: 80px;">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="speed" class="form-label">Required Bandwidth (Mbps)</label>
                    <?php if ($calculated_speed): ?>
                        <input type="number" class="form-control readonly-field" id="speed" name="speed" 
                            value="<?= htmlspecialchars($calculated_speed) ?>" readonly>
                    <?php else: ?>
                        <input type="number" class="form-control" id="speed" name="speed" required step="0.01">
                    <?php endif; ?>
                    <div class="mt-1">
                        <small class="text-muted">Don't know what speed you need? <a href="speedCalculator.php">Try our Bandwidth Calculator</a></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="users" class="form-label">Number of Users</label>
                    <?php if ($calculated_users): ?>
                        <input type="number" class="form-control readonly-field" id="users" name="users" 
                            value="<?= htmlspecialchars($calculated_users) ?>" readonly>
                    <?php else: ?>
                        <input type="number" class="form-control" id="users" name="users" required>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="event_type" class="form-label">Event Type</label>
                    <select class="form-select" id="event_type" name="event_type" required>
                        <option value="">Select event type</option>
                        <option value="indoor" <?= $event_type === 'indoor' ? 'selected' : '' ?>>Indoor Event</option>
                        <option value="outdoor" <?= $event_type === 'outdoor' ? 'selected' : '' ?>>Outdoor Event</option>
                        <option value="concert" <?= $event_type === 'concert' ? 'selected' : '' ?>>Concert</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="budget" class="form-label">Budget (PHP)</label>
                    <input type="number" class="form-control" id="budget" name="budget" 
                           value="<?= htmlspecialchars($budget) ?>" step="0.01" required>
                </div>
            </div>
            
             <div class="mb-3">
                <label for="area_size" class="form-label">Area Size (square meters)</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="area_size" name="area_size" 
                           value="<?= htmlspecialchars($area_size) ?>" step="0.01" required>
                    <?php if (isset($_GET['fromBooking'])): ?>
                        <button type="button" class="btn btn-outline-secondary" 
                                onclick="window.location.href='mapcoverage.php?fromBooking=1'">
                            <i class="bi bi-map"></i> Calculate Area
                        </button>
                    <?php endif; ?>
                </div>
                <i class="bi bi-info-circle"></i> Need help determining your area size? 
                <a href="mapcoverage.php?fromBooking=1" class="alert-link">Use our Coverage Visualization Tool</a>
            </div>
            
            <button type="submit" name="submit_booking" class="btn btn-primary">Find Packages</button>
        </form>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])): ?>
            <div class="mt-4">
                <?php if ($package): ?>
                    <div class="package-card">
                        <div class="package-name"><?= htmlspecialchars($package['packageName']) ?></div>
                        <p class="text-muted"><?= htmlspecialchars($package['description']) ?></p>
                        
                        <div class="package-features">
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-speedometer2"></i></span>
                                <span>Bandwidth: <?= htmlspecialchars($package['expectedBandwidth']) ?> Mbps</span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-people-fill"></i></span>
                                <span>Supports: <?= htmlspecialchars($package['numberOfUsers']) ?> users</span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-pin-map-fill"></i></span>
                                <span>Area: <?= htmlspecialchars($package['eventAreaSize']) ?> sqm</span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-tag-fill"></i></span>
                                <span>Price: $<?= htmlspecialchars($package['price']) ?></span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-gear-fill"></i></span>
                                <span>Event Type: <?= ucfirst(htmlspecialchars($package['eventType'])) ?></span>
                            </div>
                        </div>
                        
                        <?php if ($package['equipmentsIncluded']): ?>
                            <div class="mt-3">
                                <h5>Equipment Included:</h5>
                                <p><?= nl2br(htmlspecialchars($package['equipmentsIncluded'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($selectedEquipment)): ?>
                            <div class="mt-3">
                                <h5>Additional Equipment:</h5>
                                <ul>
                                    <?php foreach ($selectedEquipment as $item): ?>
                                        <li><?= htmlspecialchars($item['name']) ?> (<?= htmlspecialchars($item['type']) ?>) - ₱<?= number_format($item['price'], 2) ?> x <?= $item['quantity'] ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p><strong>Total Additional Cost: ₱<?= number_format($totalAdditionalPrice, 2) ?></strong></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($recommendation): ?>
                    <div class="recommendation">
                        <h4><i class="bi bi-info-circle-fill"></i> Recommendation</h4>
                        <p><?= htmlspecialchars($recommendation) ?></p>
                        <a href="contact.php" class="btn btn-outline-primary mt-2">Contact Our Team</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($showEquipmentForm): ?>
                   <div class="mt-4">
                    <h3>Additional Equipment Needed</h3>
                    <form method="post" id="equipmentForm">
                        <div id="equipmentForms" class="mb-2">
                            <?php if (!empty($selectedEquipment)): ?>
                                <?php foreach ($selectedEquipment as $item): ?>
                                    <div class="add-equipment-form">
                                        <div class="row equipment-form-row">
                                            <div class="col-md-6 equipment-form-col">
                                                <select class="form-select equipment-select" name="equipment_ids[]" required>
                                                    <option value="">Select Equipment</option>
                                                    <?php foreach ($availableEquipment as $equip): ?>
                                                        <option value="<?= $equip['itemId'] ?>" 
                                                            <?= $equip['itemId'] == $item['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($equip['itemName']) ?> 
                                                            (<?= htmlspecialchars($equip['itemType']) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 equipment-form-col">
                                                <input type="number" class="form-control quantity-input" 
                                                    name="quantities[]" 
                                                    min="1" value="<?= $item['quantity'] ?>" required>
                                            </div>
                                            <div class="col-md-3 equipment-form-col">
                                                <button type="button" class="btn btn-outline-danger remove-equipment w-100">
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="add-equipment-form">
                                    <div class="row equipment-form-row">
                                        <div class="col-md-6 equipment-form-col">
                                            <select class="form-select equipment-select" name="equipment_ids[]" required>
                                                <option value="">Select Equipment</option>
                                                <?php foreach ($availableEquipment as $equip): ?>
                                                    <option value="<?= $equip['itemId'] ?>">
                                                        <?= htmlspecialchars($equip['itemName']) ?> 
                                                        (<?= htmlspecialchars($equip['itemType']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 equipment-form-col">
                                            <input type="number" class="form-control quantity-input" name="quantities[]" min="1" value="1" required>
                                        </div>
                                        <div class="col-md-3 equipment-form-col">
                                            <button type="button" class="btn btn-outline-danger remove-equipment w-100" style="display: none;">
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-2">
                            <button type="submit" name="add_equipment" class="btn btn-primary">Save Equipment and Return to Booking</button>
                            <a href="booking.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

      <div class="foot-container">
    <div class="foot-logo" style="text-align: center; margin-bottom: 1rem;">
    <img src="logofooter.png" alt="Wi-Spot Logo" style="width: 140px;">
  </div>
  <div class="foot-icons">
    <a href="https://www.facebook.com/WiSpotServices" class="bi bi-facebook" target="_blank"></a>
  </div>

  <hr>

  <div class="foot-policy">
    <div class="policy-links">
      <a href="termsofservice.php" target="_blank">TERMS OF SERVICE</a>
      <a href="copyrightpolicy.php" target="_blank">COPYRIGHT POLICY</a>
      <a href="privacypolicy.php" target="_blank">PRIVACY POLICY</a>
      <a href="contactus.php" target="_blank">CONTACT US</a>
    </div>
  </div>

  <hr>

  <div class="foot_text">
    <br>
    <p>&copy;2025 Wi-spot. All rights reserved. Wi-spot and related trademarks and logos are the property of Wi-spot. All other trademarks are the property of their respective owners.</p><br>
  </div>
</div>

   <script>
    document.addEventListener('DOMContentLoaded', function() {
        const equipmentForms = document.getElementById('equipmentForms');
        const addMoreBtn = document.getElementById('addMoreEquipment');
        const availableEquipment = <?= json_encode($availableEquipment) ?>;
        const selectedEquipment = <?= json_encode($selectedEquipment ?? []) ?>;
        
        // Add more equipment form
        if (addMoreBtn) {
            addMoreBtn.addEventListener('click', function() {
                const newForm = document.createElement('div');
                newForm.className = 'add-equipment-form'; // Use the same compact class
                newForm.innerHTML = `
                    <div class="row equipment-form-row">
                        <div class="col-md-6 equipment-form-col">
                            <select class="form-select equipment-select" name="equipment_ids[]" required>
                                <option value="">Select Equipment</option>
                                ${availableEquipment.map(equip => {
                                    // Only show equipment that hasn't been selected yet
                                    let isSelected = selectedEquipment.some(sel => sel.id == equip.itemId);
                                    if (!isSelected) {
                                        return `<option value="${equip.itemId}">
                                            ${equip.itemName} (${equip.itemType})
                                        </option>`;
                                    }
                                    return '';
                                }).join('')}
                            </select>
                        </div>
                        <div class="col-md-3 equipment-form-col">
                            <input type="number" class="form-control quantity-input" name="quantities[]" min="1" value="1" required>
                        </div>
                        <div class="col-md-3 equipment-form-col">
                            <button type="button" class="btn btn-outline-danger remove-equipment w-100">Remove</button>
                        </div>
                    </div>
                `;
                
                equipmentForms.appendChild(newForm);
                updateRemoveButtons();
            });
        }
        
        // Remove equipment form
        if (equipmentForms) {
            equipmentForms.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-equipment')) {
                    e.target.closest('.add-equipment-form').remove();
                    updateRemoveButtons();
                }
            });
        }
        
        function updateRemoveButtons() {
            const forms = document.querySelectorAll('.add-equipment-form');
            forms.forEach((form, index) => {
                const removeBtn = form.querySelector('.remove-equipment');
                if (forms.length > 1) {
                    removeBtn.style.display = 'block';
                } else {
                    removeBtn.style.display = 'none';
                }
            });
        }

        // Initialize the first form's remove button
        updateRemoveButtons();
    });
</script>
</body>
</html>