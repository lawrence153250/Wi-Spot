<?php
session_start();

// Set session timeout to 15 minutes (900 seconds)
$inactive = 900; 

// Check if timeout variable is set
if (isset($_SESSION['timeout'])) {
    // Calculate the session's lifetime
    $session_life = time() - $_SESSION['timeout'];
    if ($session_life > $inactive) {
        // Logout and redirect to login page
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
}

// Update timeout with current time
$_SESSION['timeout'] = time();

if (!isset($_SESSION['username'])) {
    echo '<div class="alert">You need to log in first. Redirecting to login page...</div>';
    header("Refresh: 3; url=login.php");
    exit();
}

$username = $_SESSION['username'];

require_once 'config.php';

// Fetch user information safely using prepared statements
$stmt = $conn->prepare("SELECT * FROM customer WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $customerId = $user['customerId'];
} else {
    die("User not found.");
}

$stmt->close();

// Set the default time zone to Philippine time
date_default_timezone_set('Asia/Manila');

// Get the current date in the desired format 
$effectiveDate = date("F j, Y");

function formatDate($dateString) {
    return date("F j, Y", strtotime($dateString));
}


// Function to compute price based on package and number of days
function computePrice($packageId, $dateOfBooking, $dateOfReturn) {
    $packagePrices = [1 => 1000, 2 => 1500, 3 => 4000, 4 => 5000];

    $startDate = new DateTime($dateOfBooking);
    $endDate = new DateTime($dateOfReturn);
    $interval = $startDate->diff($endDate);
    $numberOfDays = max($interval->days, 1); // Ensure at least 1-day charge

    return $packagePrices[$packageId] * $numberOfDays;
}
// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $packageId = $_POST['packageId'];
    $dateOfBooking = $_POST['dateOfBooking'];
    $dateOfReturn = $_POST['dateOfReturn'];
    $eventLocation = $_POST['eventLocation'];
    $lendingAgreement = $_POST['lendingAgreement'];
    $totalPrice = $_POST['totalPrice']; // Get the total price from the form

    // Validate date range
    if (new DateTime($dateOfBooking) >= new DateTime($dateOfReturn)) {
        echo '<div class="alert alert-danger">Error: Return date must be after the booking date.</div>';
    } else {
        // Insert booking with the total price
        $stmt = $conn->prepare("INSERT INTO booking (customerId, packageId, dateOfBooking, dateOfReturn, eventLocation, price, lendingAgreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssds", $customerId, $packageId, $dateOfBooking, $dateOfReturn, $eventLocation, $totalPrice, $lendingAgreement);

        if ($stmt->execute()) {
            // Clear the custom equipment from session after successful booking
            unset($_SESSION['custom_equipment']);
            echo '<div class="alert alert-success">Booking successfully created!</div>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
}

// Initialize equipment variables
$equipment_name = $_POST['equipment_name'] ?? null;
$equipment_price = $_POST['equipment_price'] ?? null;
$equipment_quantity = $_POST['equipment_quantity'] ?? 1; // Default to 1 if not specified

// If receiving multiple equipment items (array format)
$equipment_list = $_POST['equipment'] ?? []; // Array of equipment items

// Process single equipment item
if ($equipment_name && $equipment_price) {
    $equipment_item = [
        'name' => $equipment_name,
        'price' => (float)$equipment_price,
        'quantity' => (int)$equipment_quantity
    ];
    
    // Add to session or process as needed
    $_SESSION['selected_equipment'] = $equipment_item;
}

// Process multiple equipment items
if (!empty($equipment_list)) {
    $processed_equipment = [];
    
    foreach ($equipment_list as $item) {
        // Ensure we have the required fields
        if (!empty($item['name']) && isset($item['price'])) {
            $processed_equipment[] = [
                'name' => htmlspecialchars($item['name']),
                'price' => (float)$item['price'],
                'quantity' => isset($item['quantity']) ? (int)$item['quantity'] : 1
            ];
        }
    }
    
    if (!empty($processed_equipment)) {
        $_SESSION['selected_equipment'] = $processed_equipment;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking page</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="bookingstyles.css">
    <!-- Include Signature Pad library -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
</head>
<body style="background-color: #f0f3fa;"> <nav class="navbar navbar-expand-lg navbar-dark" id="grad">
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
    
  <div class="booking-header">
      <h1>BOOKING RESERVATION FORM</h1>
  </div>

    <h3>Clients Basic Information</h3>
    <div class="user-info-section">
        <p><strong>First Name: </strong> <?php echo $user['firstName']; ?></p>
    </div>
    <div class="user-info-section">
        <p><strong>Last Name: </strong> <?php echo $user['lastName']; ?></p>
    </div>
    <div class="user-info-section">
        <p><strong>Address: </strong><?php echo $user['address']; ?></p>
    </div>
    <div class="user-info-section">
        <p><strong>Email: </strong><?php echo $user['email'] ?></p>
    </div>
    <div class="user-info-section">
        <p><strong>Contact: </strong><?php echo $user['contactNumber'] ?></p>
    </div>

    <h3>Loan Period</h3>
    <form id="bookingForm" method="POST">
        <div class="form-group">
            <label for="dateOfBooking">Start Date: </label>
            <input type="date" id="dateOfBooking" name="dateOfBooking" required>
        </div>
        <div class="form-group">
            <label for="dateOfReturn">Date of Return: </label>
            <input type="date" id="dateOfReturn" name="dateOfReturn" required>
        </div>
        <div class="form-group">
            <label for="eventLocation">Event's Location Address:</label>
            <input type="text" id="eventLocation" name="eventLocation" required>
        </div>

        <h4>Not sure which package to choose? Check our <a href="mapcoverage.php">Map Coverage</a> to see signal strength at your event location.</h4>
        <h4>Want something more personalized? Use our <a href="booking_customization.php">Booking Customization</a> to build a package that fits your needs.</h4>
        <div class="form-group">
            <label>Choose a Package:</label>
            <div class="package-selection">
                <label class="package-option">
                    <input type="radio" name="packageId" value="1">
                    <img src="package1.png" alt="Package 1" class="package-img">
                    <span>Package 1</span>
                    <p>Price: ₱1000 per day</p>
                </label>

                <label class="package-option">
                    <input type="radio" name="packageId" value="2">
                    <img src="package2.png" alt="Package 2" class="package-img">
                    <span>Package 2</span>
                    <p>Price: ₱1500 per day</p>
                </label>

                <label class="package-option">
                    <input type="radio" name="packageId" value="3">
                    <img src="package3.png" alt="Package 3" class="package-img">
                    <span>Package 3</span>
                    <p>Price: ₱4000 per day</p>
                </label>

                <label class="package-option">
                    <input type="radio" name="packageId" value="4">
                    <img src="package4.png" alt="Package 4" class="package-img">
                    <span>Package 4</span>
                    <p>Price: ₱5000 per day</p>
                </label>
            </div>
        </div>

        <div class="user-info-section">
        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#lending" required>View Lending Agreement</button>
        </div>

        <!-- Hidden input to store signature -->
        <input type="hidden" id="lendingAgreement" name="lendingAgreement">

        <div class="form-group">
        <input type="hidden" id="totalPrice" name="totalPrice">
        <button type="button" class="btn btn-primary">Book Now</button>
        </div>
    </form>

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
</div>

    <!-- Lending Agreement Modal with Signature Pad -->
    <div class="modal fade" id="lending" tabindex="-1" aria-labelledby="lendingLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lendingLabel">Starlink Device Lending Agreement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                <p>This Device Lending Agreement ("Agreement") is entered into on this day <strong><?php echo $effectiveDate; ?></strong> between:</p>

                <p><strong>Lender:</strong> Joshua Ed Napila, an individual residing at 52 Eagle Street, Don Mariano Subdivision, Cainta, Rizal ("Lender").</p>

                <p><strong>Borrower:</strong> <strong><?php echo $user['firstName'] .' '. $user['lastName']; ?></strong> , an individual residing at <strong><?php echo $user['address']; ?></strong> .</p>

                <h2>Background:</h2>
                <p>The Lender is the owner of a Starlink device, hereinafter referred to as the "Device," and is willing to lend it to the Borrower subject to the terms and conditions outlined in this Agreement.</p>

                <h2>Terms and Conditions:</h2>

                <ol>
                    <li>
                        <strong>Device Description:</strong> The Device subject to this Agreement is described as follows:
                        <ul>
                            <li><strong>Model:</strong> KIT303105607/Gen 2</li>
                            <li><strong>Serial Number:</strong> 2DWC235000042417</li>
                            <li><strong>Router ID:</strong> 01000000000000000044E2CD</li>
                        </ul>
                    </li>

                    <li>
                        <strong>Loan Period:</strong> The Borrower acknowledges and agrees that the Device is being loaned for a period commencing on the Start Date and ending on the agreed-upon return date, unless otherwise extended in writing by the Lender ("Loan Period").
                    </li>

                    <li>
                        <strong>Purpose:</strong> The Borrower agrees to use the Device solely for personal use and not for any commercial purposes.
                    </li>

                    <li>
                        <strong>Care and Maintenance:</strong> The Borrower shall use the Device in a careful and proper manner, following all instructions provided by the manufacturer. The Borrower shall be responsible for any damage to the Device beyond normal wear and tear during the Loan Period.
                    </li>

                    <li>
                        <strong>Return Condition:</strong> At the end of the Loan Period or upon demand by the Lender, the Borrower shall return the Device to the Lender in the same condition as it was received, ordinary wear and tear excepted.
                    </li>

                    <li>
                        <strong>Loss or Damage:</strong> The Borrower shall be liable for any loss, theft, or damage to the Device that occurs during the Loan Period, and shall reimburse the Lender for the cost of repair or replacement of the Device.
                    </li>

                    <li>
                        <strong>Indemnification:</strong> The Borrower agrees to indemnify and hold harmless the Lender from any claims, damages, liabilities, or expenses arising out of or in connection with the Borrower's use of the Device.
                    </li>

                    <li>
                        <strong>Ownership:</strong> The Borrower acknowledges that the Device is and shall remain the property of the Lender, and that the Borrower has no ownership interest or rights therein except as expressly provided in this Agreement.
                    </li>

                    <li>
                        <strong>Termination:</strong> The Lender reserves the right to terminate this Agreement and demand the immediate return of the Device at any time for any reason, upon written notice to the Borrower.
                    </li>

                    <li>
                        <strong>Entire Agreement:</strong> This Agreement constitutes the entire agreement between the parties with respect to the subject matter hereof, and supersedes all prior and contemporaneous agreements and understandings, whether written or oral, relating to such subject matter.
                    </li>
                </ol>

                    <p>Please sign if you agree</p>
                    <div id="signature-pad" class="signature-pad">
                        <canvas id="signature-canvas"></canvas>
                    </div>
                    <button type="button" id="clear-signature" class="btn btn-danger mt-2">Clear Signature</button>
                </div>
                <div class="modal-footer">
                    <button type="button" id="save-signature" class="btn btn-primary" data-bs-dismiss="modal">Agree</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Customer Name:</strong> <span id="modalCustomerName"></span></p>
                    <p><strong>Start Date:</strong> <span id="modalDateOfBooking"></span></p>
                    <p><strong>Return Date:</strong> <span id="modalDateOfReturn"></span></p>
                    <p><strong>Event Location:</strong> <span id="modalEventLocation"></span></p>
                    <p><strong>Package Chosen:</strong> <span id="modalPackageChosen"></span></p>
                    
                    <!-- Added Equipment Section -->
                    <div class="equipment-summary mt-3">
                        <h6><strong>Additional Equipment:</strong></h6>
                        <div id="modalEquipmentList" class="mb-2">
                            <!-- Equipment items will be inserted here by JavaScript -->
                        </div>
                        <p class="text-end"><strong>Equipment Total: </strong><span id="modalEquipmentTotal">₱0.00</span></p>
                    </div>
                    
                    <p class="mt-3"><strong>Total Price:</strong> <span id="modalTotalPrice"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                    <button type="submit" form="bookingForm" name="register" class="btn btn-primary">Confirm Booking</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Signature Pad Script -->
    <script>
   document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('signature-canvas');
    const signaturePad = new SignaturePad(canvas);

    // Clear signature
    document.getElementById('clear-signature').addEventListener('click', function () {
        signaturePad.clear();
    });

    // Save signature
    document.getElementById('save-signature').addEventListener('click', function () {
        if (signaturePad.isEmpty()) {
            alert('Please provide a signature first.');
        } else {
            const signatureData = signaturePad.toDataURL(); // Get signature as image data URL
            document.getElementById('lendingAgreement').value = signatureData; // Store in hidden input
            alert('Lending Agreement has been signed successfully.');
        }
    });

    // Book Now button click handler
    document.querySelector('.btn-primary').addEventListener('click', function (event) {
        event.preventDefault(); // Prevent the default behavior of the button

        const signatureData = document.getElementById('lendingAgreement').value;

        if (!signatureData) {
            alert('You need to sign the agreement first before proceeding.');
        } else {
            // Update confirmation modal with form data
            const customerName = "<?php echo $user['firstName'] . ' ' . $user['lastName']; ?>";
            const dateOfBooking = formatDate(document.getElementById('dateOfBooking').value);
            const dateOfReturn = formatDate(document.getElementById('dateOfReturn').value);
            const eventLocation = document.getElementById('eventLocation').value;
            const packageId = document.querySelector('input[name="packageId"]:checked').value;
            const packageName = document.querySelector('input[name="packageId"]:checked + .package-img + span').textContent;
            const packagePrice = computePrice(packageId, document.getElementById('dateOfBooking').value, document.getElementById('dateOfReturn').value);
            
            // Get custom equipment data from PHP session
            const customEquipment = <?php echo isset($_SESSION['custom_equipment']) ? json_encode($_SESSION['custom_equipment']) : '[]'; ?>;
            let equipmentTotal = 0;
            let equipmentHTML = '';
            
            if (customEquipment.length > 0) {
                customEquipment.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    equipmentTotal += itemTotal;
                    equipmentHTML += `
                        <div class="d-flex justify-content-between">
                            <span>${item.name} (${item.type}) x ${item.quantity}</span>
                            <span>₱${itemTotal.toFixed(2)}</span>
                        </div>
                    `;
                });
            } else {
                equipmentHTML = '<p class="text-muted">No additional equipment selected</p>';
            }
            
            // Update modal content
            document.getElementById('modalCustomerName').textContent = customerName;
            document.getElementById('modalDateOfBooking').textContent = dateOfBooking;
            document.getElementById('modalDateOfReturn').textContent = dateOfReturn;
            document.getElementById('modalEventLocation').textContent = eventLocation;
            document.getElementById('modalPackageChosen').textContent = packageName;
            
            // Update equipment section
            const equipmentList = document.getElementById('modalEquipmentList');
            equipmentList.innerHTML = equipmentHTML;
            document.getElementById('modalEquipmentTotal').textContent = `₱${equipmentTotal.toFixed(2)}`;
            
            // Calculate and display total price (package + equipment)
            const totalPrice = packagePrice + equipmentTotal;
            document.getElementById('modalTotalPrice').textContent = `₱${totalPrice.toFixed(2)}`;
            
            // Store the total price in the hidden field
            document.getElementById('totalPrice').value = totalPrice;

            // Show the confirmation modal
            const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            confirmationModal.show();
        }
    });

    // Function to compute price
    function computePrice(packageId, dateOfBooking, dateOfReturn) {
        const packagePrices = {
            1: 1000, // Package 1: ₱1000 per day
            2: 1500, // Package 2: ₱1500 per day
            3: 4000, // Package 3: ₱4000 per day
            4: 5000, // Package 4: ₱5000 per day
        };

        const startDate = new Date(dateOfBooking);
        const endDate = new Date(dateOfReturn);
        const timeDiff = endDate - startDate;
        const numberOfDays = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));

        return packagePrices[packageId] * numberOfDays;
    }
});

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}
</script>

</body>
</html>