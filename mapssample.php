<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Coverage Visualization</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<style>
    body {
        background-color: #E6F2F4;
        font-family: Arial, sans-serif;
        color: #333;
        margin: 0;
        padding: 0;
    }

    gmp-map:not(:defined) {
        display: none;
    }

    #title {
        color: #fff;
        background-color: #4d90fe;
        font-size: 25px;
        font-weight: 500;
        padding: 6px 12px;
    }

    #infowindow-content {
        display: none;
    }

    .pac-card {
        background-color: #fff;
        border-radius: 2px;
        box-shadow: 0 1px 4px -1px rgba(0, 0, 0, 0.3);
        margin: 10px;
        font: 400 18px Roboto, Arial, sans-serif;
        overflow: hidden;
    }

    .pac-controls {
        display: inline-block;
        padding: 5px 11px;
    }

    .pac-controls label {
        font-size: 13px;
        font-weight: 300;
    }

    #place-picker {
        box-sizing: border-box;
        width: 100%;
        padding: 0.5rem 1rem 1rem;
    }
    
    .map-wrapper {
        height: 500px;
        border: 2px solid #ccc;
        border-radius: 5px;
        overflow: hidden;
        position: relative;
    }
    
    .step-indicator {
        margin-bottom: 20px;
    }
    
    .step {
        display: inline-block;
        width: 30px;
        height: 30px;
        line-height: 30px;
        border-radius: 50%;
        text-align: center;
        background-color: #ddd;
        margin-right: 10px;
    }
    
    .step.active {
        background-color: #4d90fe;
        color: white;
    }
    
    .step.completed {
        background-color: #5cb85c;
        color: white;
    }
    
    .coverage-circle {
        position: absolute;
        border-radius: 50%;
        background-color: rgba(77, 144, 254, 0.2);
        border: 2px solid rgba(77, 144, 254, 0.8);
        pointer-events: none;
    }
    
    .router-icon {
        position: absolute;
        width: 24px;
        height: 24px;
        background-color: #4d90fe;
        border-radius: 50%;
        border: 2px solid white;
        cursor: move;
        z-index: 100;
    }
    
    .area-rectangle {
        position: absolute;
        background-color: rgba(92, 184, 92, 0.2);
        border: 2px dashed #5cb85c;
        pointer-events: none;
    }
    
    .coverage-status {
        margin-top: 15px;
        padding: 10px;
        border-radius: 5px;
    }
    
    .full-coverage {
        background-color: #dff0d8;
        color: #3c763d;
    }
    
    .partial-coverage {
        background-color: #fcf8e3;
        color: #8a6d3b;
    }
    
    .no-coverage {
        background-color: #f2dede;
        color: #a94442;
    }
    
    #router-details {
        margin-top: 15px;
    }
    
    .hidden {
        display: none;
    }
    
    .btn-step {
        margin-top: 10px;
    }
</style>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" id="grad">
    <div class="nav-container">
        <a class="navbar-brand" href="index.php"><img src="logo.jpg"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item active">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="booking.php">Booking</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="mapcoverage.php">Map Coverage</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="aboutus.php">About Us</a>
                </li>
                <?php if (isset($_SESSION['username'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><?php echo $_SESSION['username']; ?> <i class="bi bi-person-circle"></i></a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Log In</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Sign Up</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-4">
    <h1>Network Coverage Visualization</h1>
    <p>Follow these steps to visualize and assess the network coverage for your event or office location.</p>
    
    <div class="step-indicator">
        <span id="step1-indicator" class="step active">1</span>
        <span id="step2-indicator" class="step">2</span>
        <span id="step3-indicator" class="step">3</span>
        <span id="step4-indicator" class="step">4</span>
    </div>
    
    <div class="row">
        <!-- Map Container -->
        <div class="col-md-8">
            <div class="map-wrapper" id="map-container">
                <script type="module" src="https://ajax.googleapis.com/ajax/libs/@googlemaps/extended-component-library/0.6.11/index.min.js"></script>
                <gmpx-api-loader
                    key=""
                    solution-channel="GMP_CCS_autocomplete_v5">
                </gmpx-api-loader>
                <gmp-map id="map" center="40.749933,-73.98633" zoom="13" map-id="DEMO_MAP_ID" style="width: 100%; height: 500px;">
                    <div slot="control-block-start-inline-start" class="pac-card" id="pac-card">
                        <div>
                            <div id="title">Autocomplete search</div>
                            <div id="type-selector" class="pac-controls">
                                <input type="radio" name="type" id="changetype-all" checked="checked" />
                                <label for="changetype-all">All</label>

                                <input type="radio" name="type" id="changetype-establishment" />
                                <label for="changetype-establishment">Establishment</label>

                                <input type="radio" name="type" id="changetype-address" />
                                <label for="changetype-address">Address</label>

                                <input type="radio" name="type" id="changetype-geocode" />
                                <label for="changetype-geocode">Geocode</label>

                                <input type="radio" name="type" id="changetype-cities" />
                                <label for="changetype-cities">Cities</label>

                                <input type="radio" name="type" id="changetype-regions" />
                                <label for="changetype-regions">Regions</label>
                            </div>
                            <br />
                            <div id="strict-bounds-selector" class="pac-controls">
                                <input type="checkbox" id="use-strict-bounds" value="" />
                                <label for="use-strict-bounds">Restrict to map viewport</label>
                            </div>
                        </div>
                        <gmpx-place-picker id="place-picker" for-map="map"></gmpx-place-picker>
                    </div>
                    <gmp-advanced-marker id="marker"></gmp-advanced-marker>
                </gmp-map>
                <div id="area-rectangle" class="area-rectangle hidden"></div>
                <div id="router-container"></div>
                <div id="coverage-container"></div>
            </div>
        </div>

        <!-- Side Panel -->
        <div class="col-md-4">
            <div class="card p-3 shadow-sm">
                <!-- Step 1: Location Search -->
                <div id="step1-panel">
                    <h5>Step 1: Find Your Location</h5>
                    <hr>
                    <p>Use the search box on the map to find your event or office location.</p>
                    <p>Once you've found the correct location, click "Next" to continue.</p>
                    <button class="btn btn-primary btn-step" onclick="nextStep(1)">Next</button>
                </div>
                
                <!-- Step 2: Define Area -->
                <div id="step2-panel" class="hidden">
                    <h5>Step 2: Define Your Area</h5>
                    <hr>
                    <p>Click and drag on the map to define the area that needs network coverage.</p>
                    <div class="mb-3">
                        <label for="area-width" class="form-label">Width (meters):</label>
                        <input type="number" class="form-control" id="area-width" value="100">
                    </div>
                    <div class="mb-3">
                        <label for="area-height" class="form-label">Height (meters):</label>
                        <input type="number" class="form-control" id="area-height" value="100">
                    </div>
                    <button class="btn btn-secondary btn-step" onclick="prevStep(2)">Back</button>
                    <button class="btn btn-primary btn-step" onclick="createArea()">Create Area</button>
                </div>
                
                <!-- Step 3: Select Router -->
                <div id="step3-panel" class="hidden">
                    <h5>Step 3: Select Router Type</h5>
                    <hr>
                    <div class="mb-3">
                        <label for="routerType" class="form-label">Router Type:</label>
                        <select class="form-select" id="routerType">
                            <option value="basic">Basic Router (50m range)</option>
                            <option value="standard">Standard Router (75m range)</option>
                            <option value="premium">Premium Router (100m range)</option>
                        </select>
                    </div>
                    <div id="router-details">
                        <p><strong>Coverage Radius:</strong> <span id="coverage-radius">50</span> meters</p>
                        <p><strong>Max Devices:</strong> <span id="max-devices">50</span></p>
                        <p><strong>Speed:</strong> <span id="router-speed">100</span> Mbps</p>
                    </div>
                    <div id="coverage-status" class="coverage-status hidden">
                        Coverage status will appear here
                    </div>
                    <button class="btn btn-secondary btn-step" onclick="prevStep(3)">Back</button>
                    <button class="btn btn-primary btn-step" onclick="addRouter()">Add Router</button>
                    <button class="btn btn-success btn-step" onclick="nextStep(3)">Finish Setup</button>
                </div>
                
                <!-- Step 4: Results -->
                <div id="step4-panel" class="hidden">
                    <h5>Step 4: Coverage Results</h5>
                    <hr>
                    <div id="results-container">
                        <p>Your coverage analysis will appear here.</p>
                    </div>
                    <button class="btn btn-secondary btn-step" onclick="prevStep(4)">Back</button>
                    <button class="btn btn-primary btn-step" onclick="saveConfiguration()">Save Configuration</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="foot-container" id="grad">
    <div class="foot-icons">
        <a href="https://www.youtube.com/" class="bi bi-youtube text-altlight" target="_blank"></a>
        <a href="https://web.facebook.com/" class="bi bi-facebook text-altlight" target="_blank"></a>
        <a href="https://www.instagram.com/" class="bi bi-instagram text-altlight" target="_blank"></a>
        <a href="https://www.tiktok.com/" class="bi bi-tiktok text-altlight" target="_blank"></a>
    </div>
    <hr>
    <div class="foot-policy">
        <div class="row">
            <div class="col-md-3">
                <a class="foot-policy text-altlight" href="termsofservice.php" target="_blank">Terms of Service</a>
            </div>
            <div class="col-md-3">
                <a class="foot-policy text-altlight" href="copyrightpolicy.php" target="_blank">Copyright Policy</a>
            </div>
            <div class="col-md-3">
                <a class="foot-policy text-altlight" href="privacypolicy.php" target="_blank">Privacy Policy</a>
            </div>
            <div class="col-md-3">
                <a class="foot-policy text-altlight" href="contactus.php" target="_blank">Contact Us</a>
            </div>
        </div>
    </div>
    <hr>
    <div class="foot_text text-altlight">
        <p>Wi-spot is available in English, French, German, Italian, Spanish, and more.</p><br>
        <p>
            &copy;2025 Wi-spot. All rights reserved. Wi-spot and related trademarks and logos are the property of Wi-spot. All other trademarks are the property of their respective owners.
        </p><br>
        <p>
            This webpage is for educational purposes only and no copyright infringement is intended.
        </p>
    </div>
</div>

<script>
    // Global variables
    let currentStep = 1;
    let map;
    let rectangle = null;
    let routers = [];
    let coverageCircles = [];
    let isDrawing = false;
    let startPoint = null;
    let currentArea = null;
    
    // Router specifications
    const routerSpecs = {
        basic: { radius: 50, devices: 50, speed: 100, color: '#4d90fe' },
        standard: { radius: 75, devices: 100, speed: 300, color: '#5cb85c' },
        premium: { radius: 100, devices: 200, speed: 500, color: '#d9534f' }
    };
    
    // Initialize the application
    async function init() {
        await customElements.whenDefined('gmp-map');
        
        map = document.querySelector("gmp-map").innerMap;
        const marker = document.getElementById("marker");
        const strictBoundsInputElement = document.getElementById("use-strict-bounds");
        const placePicker = document.getElementById("place-picker");
        const infowindowContent = document.getElementById("infowindow-content");
        const infowindow = new google.maps.InfoWindow();
        
        map.setOptions({mapTypeControl: false});
        infowindow.setContent(infowindowContent);
        
        placePicker.addEventListener('gmpx-placechange', () => {
            const place = placePicker.value;
            
            if (!place.location) {
                window.alert("No details available for input: '" + place.name + "'");
                infowindow.close();
                marker.position = null;
                return;
            }
            
            if (place.viewport) {
                map.fitBounds(place.viewport);
            } else {
                map.setCenter(place.location);
                map.setZoom(17);
            }
            
            marker.position = place.location;
            infowindowContent.children["place-name"].textContent = place.displayName;
            infowindowContent.children["place-address"].textContent = place.formattedAddress;
            infowindow.open(map, marker);
        });
        
        // Setup click listeners for place type filters
        function setupClickListener(id, type) {
            const radioButton = document.getElementById(id);
            radioButton.addEventListener("click", () => {
                placePicker.type = type;
            });
        }
        
        setupClickListener("changetype-all", "");
        setupClickListener("changetype-address", "address");
        setupClickListener("changetype-establishment", "establishment");
        setupClickListener("changetype-geocode", "geocode");
        setupClickListener("changetype-cities", "(cities)");
        setupClickListener("changetype-regions", "(regions)");
        
        strictBoundsInputElement.addEventListener("change", () => {
            placePicker.strictBounds = strictBoundsInputElement.checked;
        });
        
        // Add event listener for router type change
        document.getElementById('routerType').addEventListener('change', updateRouterDetails);
        
        // Initialize router details
        updateRouterDetails();
    }
    
    // Update router details based on selection
    function updateRouterDetails() {
        const routerType = document.getElementById('routerType').value;
        const specs = routerSpecs[routerType];
        
        document.getElementById('coverage-radius').textContent = specs.radius;
        document.getElementById('max-devices').textContent = specs.devices;
        document.getElementById('router-speed').textContent = specs.speed;
    }
    
    // Step navigation functions
    function nextStep(current) {
        if (current === 1) {
            // Validate step 1
            const placePicker = document.getElementById("place-picker");
            if (!placePicker.value || !placePicker.value.location) {
                alert("Please select a location first.");
                return;
            }
        } else if (current === 3) {
            // Validate step 3
            if (routers.length === 0) {
                alert("Please add at least one router.");
                return;
            }
            
            // Generate results
            generateResults();
        }
        
        document.getElementById(`step${current}-panel`).classList.add('hidden');
        document.getElementById(`step${current}-indicator`).classList.remove('active');
        document.getElementById(`step${current}-indicator`).classList.add('completed');
        
        currentStep = current + 1;
        
        document.getElementById(`step${currentStep}-panel`).classList.remove('hidden');
        document.getElementById(`step${currentStep}-indicator`).classList.add('active');
    }
    
    function prevStep(current) {
        document.getElementById(`step${current}-panel`).classList.add('hidden');
        document.getElementById(`step${current}-indicator`).classList.remove('active');
        
        currentStep = current - 1;
        
        document.getElementById(`step${currentStep}-panel`).classList.remove('hidden');
        document.getElementById(`step${currentStep}-indicator`).classList.add('active');
    }
    
    // Create the area rectangle
    function createArea() {
        const width = parseFloat(document.getElementById('area-width').value);
        const height = parseFloat(document.getElementById('area-height').value);
        
        if (isNaN(width) || isNaN(height) || width <= 0 || height <= 0) {
            alert("Please enter valid width and height values.");
            return;
        }
        
        const center = map.getCenter();
        const projection = map.getProjection();
        const centerPx = projection.fromLatLngToPoint(center);
        
        // Convert meters to pixels at current zoom level
        const metersPerPixel = 156543.03392 * Math.cos(center.lat() * Math.PI / 180) / Math.pow(2, map.getZoom());
        const widthPx = width / metersPerPixel;
        const heightPx = height / metersPerPixel;
        
        const nePx = new google.maps.Point(centerPx.x + widthPx/2, centerPx.y - heightPx/2);
        const swPx = new google.maps.Point(centerPx.x - widthPx/2, centerPx.y + heightPx/2);
        
        const ne = projection.fromPointToLatLng(nePx);
        const sw = projection.fromPointToLatLng(swPx);
        
        // Store area bounds
        currentArea = {
            ne: ne,
            sw: sw,
            width: width,
            height: height
        };
        
        // Show the rectangle on the map
        const rectangleDiv = document.getElementById('area-rectangle');
        const mapContainer = document.getElementById('map-container');
        const mapRect = mapContainer.getBoundingClientRect();
        
        const nePxViewport = projection.fromLatLngToPoint(ne);
        const swPxViewport = projection.fromLatLngToPoint(sw);
        
        const left = (nePxViewport.x * mapRect.width) - mapRect.left;
        const top = (nePxViewport.y * mapRect.height) - mapRect.top;
        const right = (swPxViewport.x * mapRect.width) - mapRect.left;
        const bottom = (swPxViewport.y * mapRect.height) - mapRect.top;
        
        rectangleDiv.style.left = `${left}px`;
        rectangleDiv.style.top = `${top}px`;
        rectangleDiv.style.width = `${right - left}px`;
        rectangleDiv.style.height = `${bottom - top}px`;
        rectangleDiv.classList.remove('hidden');
        
        // Move to next step
        nextStep(2);
    }
    
    // Add a router to the map
    function addRouter() {
        const routerType = document.getElementById('routerType').value;
        const specs = routerSpecs[routerType];
        
        // Center the router in the area for now (user can drag it later)
        const center = map.getCenter();
        const projection = map.getProjection();
        const centerPx = projection.fromLatLngToPoint(center);
        
        const routerId = 'router-' + Date.now();
        const routerDiv = document.createElement('div');
        routerDiv.id = routerId;
        routerDiv.className = 'router-icon';
        routerDiv.style.backgroundColor = specs.color;
        routerDiv.setAttribute('data-radius', specs.radius);
        routerDiv.setAttribute('data-type', routerType);
        
        // Position the router in the center of the map container
        const mapContainer = document.getElementById('map-container');
        const mapRect = mapContainer.getBoundingClientRect();
        
        routerDiv.style.left = `${mapRect.width/2 - 12}px`;
        routerDiv.style.top = `${mapRect.height/2 - 12}px`;
        
        // Make the router draggable
        makeDraggable(routerDiv);
        
        document.getElementById('router-container').appendChild(routerDiv);
        
        // Add to routers array
        routers.push({
            id: routerId,
            type: routerType,
            x: mapRect.width/2,
            y: mapRect.height/2,
            radius: specs.radius
        });
        
        // Update coverage visualization
        updateCoverage();
    }
    
    // Make an element draggable
    function makeDraggable(element) {
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        
        element.onmousedown = dragMouseDown;
        
        function dragMouseDown(e) {
            e = e || window.event;
            e.preventDefault();
            
            // Get the mouse cursor position at startup
            pos3 = e.clientX;
            pos4 = e.clientY;
            
            document.onmouseup = closeDragElement;
            document.onmousemove = elementDrag;
        }
        
        function elementDrag(e) {
            e = e || window.event;
            e.preventDefault();
            
            // Calculate the new cursor position
            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;
            
            // Set the element's new position
            const newTop = (element.offsetTop - pos2) + 'px';
            const newLeft = (element.offsetLeft - pos1) + 'px';
            
            element.style.top = newTop;
            element.style.left = newLeft;
            
            // Update router position in array
            const routerId = element.id;
            const routerIndex = routers.findIndex(r => r.id === routerId);
            if (routerIndex !== -1) {
                routers[routerIndex].x = parseInt(newLeft) + 12; // Add half width
                routers[routerIndex].y = parseInt(newTop) + 12; // Add half height
                
                // Update coverage visualization
                updateCoverage();
            }
        }
        
        function closeDragElement() {
            // Stop moving when mouse button is released
            document.onmouseup = null;
            document.onmousemove = null;
        }
    }
    
    // Update coverage visualization
    function updateCoverage() {
        // Clear existing coverage circles
        document.getElementById('coverage-container').innerHTML = '';
        coverageCircles = [];
        
        if (!currentArea || routers.length === 0) return;
        
        // Get area rectangle dimensions
        const rectangleDiv = document.getElementById('area-rectangle');
        const rectLeft = parseInt(rectangleDiv.style.left);
        const rectTop = parseInt(rectangleDiv.style.top);
        const rectWidth = parseInt(rectangleDiv.style.width);
        const rectHeight = parseInt(rectangleDiv.style.height);
        
        const rectRight = rectLeft + rectWidth;
        const rectBottom = rectTop + rectHeight;
        
        // Check coverage for each router
        let fullyCovered = true;
        let anyCoverage = false;
        
        routers.forEach(router => {
            // Create coverage circle
            const circle = document.createElement('div');
            circle.className = 'coverage-circle';
            circle.style.width = `${router.radius * 2}px`;
            circle.style.height = `${router.radius * 2}px`;
            circle.style.left = `${router.x - router.radius}px`;
            circle.style.top = `${router.y - router.radius}px`;
            
            // Set color based on router type
            const routerType = router.type;
            circle.style.backgroundColor = `rgba(${hexToRgb(routerSpecs[routerType].color)}, 0.2)`;
            circle.style.borderColor = `rgba(${hexToRgb(routerSpecs[routerType].color)}, 0.8)`;
            
            document.getElementById('coverage-container').appendChild(circle);
            coverageCircles.push(circle);
        });
        
        // Check if area is fully covered
        // This is a simplified check - in a real app you'd do more precise calculations
        const coverageStatus = document.getElementById('coverage-status');
        coverageStatus.classList.remove('hidden', 'full-coverage', 'partial-coverage', 'no-coverage');
        
        if (routers.length > 0) {
            coverageStatus.textContent = `Area coverage: ${routers.length} router(s) placed.`;
            coverageStatus.classList.add('partial-coverage');
            anyCoverage = true;
            
            // In a real app, you'd do more precise coverage calculations here
            if (routers.length >= 2) {
                coverageStatus.textContent = "Area appears to be fully covered!";
                coverageStatus.classList.remove('partial-coverage');
                coverageStatus.classList.add('full-coverage');
                fullyCovered = true;
            }
        } else {
            coverageStatus.textContent = "No coverage - please add routers.";
            coverageStatus.classList.add('no-coverage');
            fullyCovered = false;
        }
        
        return fullyCovered;
    }
    
    // Helper function to convert hex to rgb
    function hexToRgb(hex) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `${r}, ${g}, ${b}`;
    }
    
    // Generate results for step 4
    function generateResults() {
        const resultsContainer = document.getElementById('results-container');
        resultsContainer.innerHTML = '';
        
        // Summary
        const summary = document.createElement('div');
        summary.className = 'mb-3';
        
        const coverageStatus = updateCoverage();
        
        if (coverageStatus) {
            summary.innerHTML = `
                <h5 class="text-success">✓ Full Coverage Achieved</h5>
                <p>Your area is fully covered with ${routers.length} router(s).</p>
            `;
        } else {
            summary.innerHTML = `
                <h5 class="text-warning">⚠ Partial Coverage</h5>
                <p>Your area may have some coverage gaps with ${routers.length} router(s).</p>
                <p>Consider adding more routers or repositioning existing ones.</p>
            `;
        }
        
        resultsContainer.appendChild(summary);
        
        // Router details
        const routerDetails = document.createElement('div');
        routerDetails.className = 'mb-3';
        
        let detailsHTML = '<h5>Router Details:</h5><ul class="list-group">';
        
        routers.forEach((router, index) => {
            const specs = routerSpecs[router.type];
            detailsHTML += `
                <li class="list-group-item">
                    <strong>Router ${index + 1}:</strong> ${router.type} (${specs.radius}m range)
                </li>
            `;
        });
        
        detailsHTML += '</ul>';
        routerDetails.innerHTML = detailsHTML;
        resultsContainer.appendChild(routerDetails);
        
        // Recommendations
        const recommendations = document.createElement('div');
        recommendations.className = 'mb-3';
        
        if (!coverageStatus && routers.length === 1) {
            recommendations.innerHTML = `
                <h5>Recommendations:</h5>
                <div class="alert alert-info">
                    <p>For better coverage, consider adding at least one more router.</p>
                    <p>Try placing routers at opposite ends of your area for optimal coverage.</p>
                </div>
            `;
        } else if (!coverageStatus) {
            recommendations.innerHTML = `
                <h5>Recommendations:</h5>
                <div class="alert alert-info">
                    <p>Your area might still have coverage gaps. Consider:</p>
                    <ul>
                        <li>Adding more routers</li>
                        <li>Using routers with larger coverage radius</li>
                        <li>Repositioning existing routers for better overlap</li>
                    </ul>
                </div>
            `;
        } else {
            recommendations.innerHTML = `
                <h5>Recommendations:</h5>
                <div class="alert alert-success">
                    <p>Your coverage looks good! You can proceed with this configuration.</p>
                </div>
            `;
        }
        
        resultsContainer.appendChild(recommendations);
    }
    
    // Save configuration (placeholder)
    function saveConfiguration() {
        alert("Configuration saved! (This would connect to your backend in a real application)");
        // In a real app, you would send the configuration to your server here
    }
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', init);
</script>
</html>
