<?php 

class NetworkCoverageVisualizer {
    private $routerTypes = [
        'basic' => ['range' => 50, 'strength' => 'medium'],
        'pro' => ['range' => 100, 'strength' => 'high'],
        'enterprise' => ['range' => 150, 'strength' => 'very high']
    ];
    
    public function __construct() {
        // Enable CORS if needed
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: POST, OPTIONS");
            
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            
            exit(0);
        }
    }

    // ✅ Added this method to fix the error
    public function getRouterTypes() {
        return $this->routerTypes;
    }

    /**
     * Handle incoming requests
     */
    public function handleRequest() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid input data');
            }
            
            $result = $this->calculateCoverage(
                $input['areaCoordinates'] ?? [],
                $input['routerPlacements'] ?? []
            );
            
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function calculateCoverage($areaCoordinates, $routerPlacements) {
        if (count($areaCoordinates) != 4) {
            throw new Exception('Area must be defined by 4 coordinates (rectangle)');
        }
        
        $totalArea = $this->calculateAreaSize($areaCoordinates);
        
        if ($totalArea <= 0) {
            throw new Exception('Invalid area coordinates - the area size must be greater than zero');
        }
        
        $results = [
            'total_area' => $totalArea,
            'covered_area' => 0,
            'coverage_percentage' => 0,
            'uncovered_areas' => [],
            'recommendations' => []
        ];
        
        $coverageCircles = [];
        foreach ($routerPlacements as $placement) {
            if (!isset($this->routerTypes[$placement['type']])) {
                continue;
            }
            
            $routerType = $this->routerTypes[$placement['type']];
            $coverageCircles[] = [
                'center' => $placement['position'],
                'radius' => $routerType['range'],
                'type' => $placement['type']
            ];
        }
        
        if (empty($coverageCircles)) {
            throw new Exception('No valid routers placed');
        }
        
        $results['covered_area'] = $this->simulateCoveredArea($areaCoordinates, $coverageCircles, $totalArea);
        
        if ($totalArea > 0) {
            $results['coverage_percentage'] = ($results['covered_area'] / $totalArea) * 100;
        }
        
        if ($results['coverage_percentage'] < 100) {
            $results['recommendations'] = $this->generateRecommendations(
                $areaCoordinates, 
                $coverageCircles, 
                $results['coverage_percentage']
            );
        }
        
        return $results;
    }

    private function calculateAreaSize($coordinates) {
        $lat1 = $coordinates[0]['lat'];
        $lon1 = $coordinates[0]['lng'];
        $lat2 = $coordinates[2]['lat'];
        $lon2 = $coordinates[2]['lng'];
        
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        $lat3 = $coordinates[1]['lat'];
        $lon3 = $coordinates[1]['lng'];
        $dLat2 = deg2rad($lat3 - $lat1);
        $dLon2 = deg2rad($lon3 - $lon1);
        
        $a2 = sin($dLat2/2) * sin($dLat2/2) +
              cos(deg2rad($lat1)) * cos(deg2rad($lat3)) *
              sin($dLon2/2) * sin($dLon2/2);
        
        $c2 = 2 * atan2(sqrt($a2), sqrt(1-$a2));
        $distance2 = $earthRadius * $c2;
        
        return $distance * $distance2;
    }

    private function simulateCoveredArea($areaCoordinates, $coverageCircles, $totalArea) {
        if ($totalArea <= 0) return 0;
        
        $minLat = min(array_column($areaCoordinates, 'lat'));
        $maxLat = max(array_column($areaCoordinates, 'lat'));
        $minLng = min(array_column($areaCoordinates, 'lng'));
        $maxLng = max(array_column($areaCoordinates, 'lng'));
        
        $samplePoints = 100;
        $coveredPoints = 0;
        
        for ($i = 0; $i < $samplePoints; $i++) {
            $lat = $minLat + ($maxLat - $minLat) * (mt_rand() / mt_getrandmax());
            $lng = $minLng + ($maxLng - $minLng) * (mt_rand() / mt_getrandmax());
            
            foreach ($coverageCircles as $circle) {
                $distance = $this->calculateDistance(
                    $lat, $lng, 
                    $circle['center']['lat'], $circle['center']['lng']
                );
                
                if ($distance <= $circle['radius']) {
                    $coveredPoints++;
                    break;
                }
            }
        }
        
        return ($coveredPoints / $samplePoints) * $totalArea;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000;
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    private function generateRecommendations($areaCoordinates, $existingRouters, $currentCoverage) {
        $recommendations = [];
        $remainingCoverage = 100 - $currentCoverage;
        
        if ($remainingCoverage <= 0) {
            return $recommendations;
        }
        
        $minLat = min(array_column($areaCoordinates, 'lat'));
        $maxLat = max(array_column($areaCoordinates, 'lat'));
        $minLng = min(array_column($areaCoordinates, 'lng'));
        $maxLng = max(array_column($areaCoordinates, 'lng'));
        
        $centerLat = ($minLat + $maxLat) / 2;
        $centerLng = ($minLng + $maxLng) / 2;
        
        $recommendCount = ceil($remainingCoverage / 40);
        $recommendCount = max(1, $recommendCount);
        
        $recommendedType = 'pro';
        if ($remainingCoverage > 60) {
            $recommendedType = 'enterprise';
        } elseif ($remainingCoverage < 30) {
            $recommendedType = 'basic';
        }
        
        $suggestedPositions = [];
        $radius = min($maxLat - $minLat, $maxLng - $minLng) * 0.25;
        
        for ($i = 0; $i < $recommendCount; $i++) {
            $angle = $i * (2 * pi() / $recommendCount);
            $suggestedPositions[] = [
                'lat' => $centerLat + $radius * sin($angle),
                'lng' => $centerLng + $radius * cos($angle)
            ];
        }
        
        $recommendations[] = [
            'action' => 'add_routers',
            'count' => $recommendCount,
            'type' => $recommendedType,
            'positions' => $suggestedPositions,
            'expected_coverage_increase' => min($remainingCoverage, $recommendCount * 40)
        ];
        
        if (count($existingRouters) > 0) {
            $upgradeType = 'enterprise';
            $upgradeIncrease = min($remainingCoverage, 25 * count($existingRouters));
            
            $recommendations[] = [
                'action' => 'upgrade_routers',
                'type' => $upgradeType,
                'expected_coverage_increase' => $upgradeIncrease
            ];
        }
        
        return $recommendations;
    }
}

// ✅ Instantiate and handle requests
$visualizer = new NetworkCoverageVisualizer();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visualizer->handleRequest();
} else {
    // For GET requests: return router types for frontend use
    echo json_encode([
        'router_types' => $visualizer->getRouterTypes(),
        'instructions' => 'Send a POST request with areaCoordinates and routerPlacements to calculate coverage'
    ]);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Coverage Visualization | Wi-Spot</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* If your navbar is fixed, you might need this */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030; /* Bootstrap default z-index for fixed navbars */
        }

        #map-container {
            height: 600px;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        .coverage-controls {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .router-option {
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
            transition: all 0.3s;
        }
        .router-option:hover {
            background: #f0f0f0;
        }
        .router-option.selected {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
        }
        .coverage-stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .coverage-meter {
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            margin: 10px 0;
            overflow: hidden;
        }
        .coverage-progress {
            height: 100%;
            background: linear-gradient(90deg, #ff6b6b, #4CAF50);
            width: 0%;
            transition: width 0.5s;
        }
        #router-tooltip {
            position: absolute;
            background: white;
            padding: 5px 10px;
            border-radius: 4px;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
            z-index: 1000;
            display: none;
        }
    </style>
</head>
<body style="background-color: #f0f3fa;"> <nav class="navbar navbar-expand-lg navbar-dark" id="grad">
<nav class="navbar navbar-expand-lg navbar-dark" id="grad">
    <div class="nav-container">
        <a class="navbar-brand" href="index.php"><img src="logoo.png" class="logo"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse d-flex justify-content-between align-items-center" id="navbarNav">
            <ul class="navbar-nav">
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
                <div class="auth-buttons ms-auto">
                    <a class="btn btn-primary" href="login.php">LOGIN</a>
                    <a class="nav-link" href="register.php">SIGN UP</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container" style="padding-top: 80px;"> <div class="text-center">
        <h1 class="mb-4">Network Coverage Visualization</h1>
        <p class="lead mb-4">See the network coverage of all available routers and plan your perfect setup</p>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="coverage-controls">
                <h4><i class="bi bi-gear"></i> Setup Your Area</h4>
                <div class="mb-3">
                    <label for="location-search" class="form-label">Search Location:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="location-search" placeholder="Enter venue address">
                        <button class="btn btn-primary" id="search-btn"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Define Coverage Area:</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary flex-grow-1" id="draw-btn"><i class="bi bi-square"></i> Draw Area</button>
                        <button class="btn btn-outline-danger flex-grow-1" id="clear-btn"><i class="bi bi-trash"></i> Clear</button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Router Type:</label>
                    <div id="router-options">
                        <div class="router-option" data-type="basic">
                            <h6>Basic Router</h6>
                            <small class="text-muted">Range: 50m | Medium strength</small>
                        </div>
                        <div class="router-option" data-type="pro">
                            <h6>Pro Router</h6>
                            <small class="text-muted">Range: 100m | High strength</small>
                        </div>
                        <div class="router-option" data-type="enterprise">
                            <h6>Enterprise Router</h6>
                            <small class="text-muted">Range: 150m | Very high strength</small>
                        </div>
                    </div>
                </div>
                
                <button class="btn btn-primary w-100 mb-3" id="calculate-btn" disabled>
                    <i class="bi bi-calculator"></i> Calculate Coverage
                </button>
                
                <div class="coverage-stats">
                    <h5>Coverage Results</h5>
                    <div class="d-flex justify-content-between">
                        <span>Coverage:</span>
                        <span id="coverage-percent">0%</span>
                    </div>
                    <div class="coverage-meter">
                        <div class="coverage-progress" id="coverage-progress"></div>
                    </div>
                    <div id="recommendations">
                        <p class="text-muted">Draw an area and place routers to see recommendations</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div id="map-container"></div>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> <strong>How to use:</strong> Search for your location, draw the event area, 
                select a router type, and click on the map to place routers.
            </div>
        </div>
    </div>
</div>

<div id="router-tooltip"></div>

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
</body>
</html>

<!-- Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCFx7Z_5qK__AetA_wIPEFEpuAhIxIsouI&libraries=drawing,geometry&callback=initMap" async defer></script>

<script>
    // Global variables
    let map;
    let drawingManager;
    let selectedArea = null;
    let selectedRouterType = 'pro';
    let routers = [];
    let coverageOverlays = [];
    
    // Initialize the map
    function initMap() {
        // Create map centered on a default location
        map = new google.maps.Map(document.getElementById('map-container'), {
            center: {lat: 40.7128, lng: -74.0060}, // New York coordinates
            zoom: 15,
            mapTypeId: 'hybrid'
        });
        
        // Initialize drawing tools
        initDrawingTools();
        
        // Setup event listeners
        setupEventListeners();
    }
    
    // Initialize drawing tools
    function initDrawingTools() {
        drawingManager = new google.maps.drawing.DrawingManager({
            drawingMode: null,
            drawingControl: false,
            rectangleOptions: {
                fillColor: '#4285F4',
                fillOpacity: 0.2,
                strokeWeight: 2,
                strokeColor: '#4285F4',
                editable: true,
                draggable: true
            }
        });
        
        drawingManager.setMap(map);
        
        // Listen for when a rectangle is completed
        google.maps.event.addListener(drawingManager, 'rectanglecomplete', function(rectangle) {
            // Clear any previous area
            if (selectedArea) {
                selectedArea.setMap(null);
            }
            
            selectedArea = rectangle;
            updateUI();
            
            // Listen for changes to the rectangle
            google.maps.event.addListener(rectangle, 'bounds_changed', function() {
                updateUI();
            });
        });
    }
    
    // Setup event listeners for UI elements
    function setupEventListeners() {
        // Search location
        document.getElementById('search-btn').addEventListener('click', searchLocation);
        document.getElementById('location-search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') searchLocation();
        });
        
        // Draw area buttons
        document.getElementById('draw-btn').addEventListener('click', function() {
            drawingManager.setDrawingMode(google.maps.drawing.OverlayType.RECTANGLE);
        });
        
        document.getElementById('clear-btn').addEventListener('click', clearAll);
        
        // Router type selection
        const routerOptions = document.querySelectorAll('.router-option');
        routerOptions.forEach(option => {
            option.addEventListener('click', function() {
                routerOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                selectedRouterType = this.dataset.type;
            });
        });
        
        // Select 'pro' router by default
        document.querySelector('.router-option[data-type="pro"]').classList.add('selected');
        
        // Calculate coverage
        document.getElementById('calculate-btn').addEventListener('click', calculateCoverage);
        
        // Map click listener for placing routers
        map.addListener('click', function(event) {
            if (!selectedArea) {
                alert('Please define your coverage area first');
                return;
            }
            
            // Check if the click is within the selected area
            const bounds = selectedArea.getBounds();
            if (!bounds.contains(event.latLng)) {
                alert('Please place routers within your defined area');
                return;
            }
            
            placeRouter(event.latLng);
        });
    }
    
    // Search for a location
    function searchLocation() {
        const geocoder = new google.maps.Geocoder();
        const address = document.getElementById('location-search').value;
        
        if (!address) return;
        
        geocoder.geocode({ 'address': address }, function(results, status) {
            if (status === 'OK') {
                map.setCenter(results[0].geometry.location);
                map.setZoom(17);
            } else {
                alert('Location not found: ' + status);
            }
        });
    }
    
    // Place a router on the map
    function placeRouter(position) {
        const router = {
            position: position,
            type: selectedRouterType,
            marker: new google.maps.Marker({
                position: position,
                map: map,
                icon: {
                    url: getRouterIcon(selectedRouterType),
                    scaledSize: new google.maps.Size(32, 32)
                },
                title: `${selectedRouterType} router`
            })
        };
        
        // Add hover tooltip
        router.marker.addListener('mouseover', function() {
            showRouterTooltip(router, this.getPosition());
        });
        
        router.marker.addListener('mouseout', function() {
            document.getElementById('router-tooltip').style.display = 'none';
        });
        
        // Add right-click to remove
        router.marker.addListener('rightclick', function() {
            this.setMap(null);
            routers = routers.filter(r => r.marker !== this);
            updateUI();
        });
        
        routers.push(router);
        updateUI();
    }
    
    // Get router icon based on type
    function getRouterIcon(type) {
        const icons = {
            basic: 'https://maps.google.com/mapfiles/kml/shapes/communications.png',
            pro: 'https://maps.google.com/mapfiles/kml/shapes/phone.png',
            enterprise: 'https://maps.google.com/mapfiles/kml/shapes/tower.png'
        };
        return icons[type] || icons.basic;
    }
    
    // Show router tooltip
    function showRouterTooltip(router, position) {
        const tooltip = document.getElementById('router-tooltip');
        tooltip.innerHTML = `
            <strong>${router.type} router</strong><br>
            Range: ${getRouterRange(router.type)}m
        `;
        tooltip.style.display = 'block';
        
        // Position the tooltip near the marker
        const point = map.getProjection().fromLatLngToPoint(position);
        const tooltipWidth = 150;
        const tooltipHeight = 50;
        
        tooltip.style.left = (point.x * (1 << map.getZoom())) - (tooltipWidth / 2) + 'px';
        tooltip.style.top = (point.y * (1 << map.getZoom())) - tooltipHeight + 'px';
    }
    
    // Get router range
    function getRouterRange(type) {
        const ranges = {
            basic: 50,
            pro: 100,
            enterprise: 150
        };
        return ranges[type] || 50;
    }
    
    // Clear all drawings and routers
    function clearAll() {
        if (selectedArea) {
            selectedArea.setMap(null);
            selectedArea = null;
        }
        
        routers.forEach(router => router.marker.setMap(null));
        routers = [];
        
        clearCoverageOverlays();
        updateUI();
    }
    
    // Clear coverage overlays
    function clearCoverageOverlays() {
        coverageOverlays.forEach(overlay => overlay.setMap(null));
        coverageOverlays = [];
    }
    
    // Update UI based on current state
    function updateUI() {
        const calculateBtn = document.getElementById('calculate-btn');
        
        if (selectedArea) {
            calculateBtn.disabled = false;
        } else {
            calculateBtn.disabled = true;
        }
    }
    
    // Calculate coverage and show results
    function calculateCoverage() {
        if (!selectedArea || routers.length === 0) {
            alert('Please define an area and place at least one router');
            return;
        }
        
        // Get area bounds coordinates
        const bounds = selectedArea.getBounds();
        const ne = bounds.getNorthEast();
        const sw = bounds.getSouthWest();
        
        const areaCoordinates = [
            { lat: ne.lat(), lng: sw.lng() }, // NW
            { lat: ne.lat(), lng: ne.lng() }, // NE
            { lat: sw.lat(), lng: ne.lng() }, // SE
            { lat: sw.lat(), lng: sw.lng() }  // SW
        ];
        
        // Prepare router placements for API
        const routerPlacements = routers.map(router => ({
            type: router.type,
            position: { lat: router.position.lat(), lng: router.position.lng() }
        }));
        
        // Call backend API
        fetch('mapcoverage.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                areaCoordinates: areaCoordinates,
                routerPlacements: routerPlacements
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
                return;
            }
            
            // Update UI with results
            displayCoverageResults(data);
            
            // Visualize coverage on map
            visualizeCoverage(data);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to calculate coverage');
        });
    }
    
    // Display coverage results in UI
    function displayCoverageResults(data) {
        // Update coverage percentage
        const percent = Math.round(data.coverage_percentage);
        document.getElementById('coverage-percent').textContent = `${percent}%`;
        document.getElementById('coverage-progress').style.width = `${percent}%`;
        
        // Update recommendations
        const recommendationsEl = document.getElementById('recommendations');
        
        if (data.recommendations && data.recommendations.length > 0) {
            let html = '<h6>Recommendations</h6>';
            
            data.recommendations.forEach(rec => {
                if (rec.action === 'add_routers') {
                    html += `
                        <div class="alert alert-warning">
                            <i class="bi bi-lightbulb"></i> Add ${rec.count} ${rec.type} router(s) to 
                            increase coverage by ~${rec.expected_coverage_increase}%
                        </div>
                    `;
                } else if (rec.action === 'upgrade_routers') {
                    html += `
                        <div class="alert alert-info">
                            <i class="bi bi-lightbulb"></i> Consider upgrading to ${rec.type} routers 
                            for ~${rec.expected_coverage_increase}% better coverage
                        </div>
                    `;
                }
            });
            
            recommendationsEl.innerHTML = html;
        } else {
            recommendationsEl.innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Great! Your area is fully covered.
                </div>
            `;
        }
    }
    
    // Visualize coverage on the map
    function visualizeCoverage(data) {
        // Clear previous coverage overlays
        clearCoverageOverlays();
        
        // Draw coverage circles for each router
        routers.forEach(router => {
            const range = getRouterRange(router.type);
            const coverageCircle = new google.maps.Circle({
                strokeColor: '#4285F4',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#4285F4',
                fillOpacity: 0.35,
                map: map,
                center: router.position,
                radius: range
            });
            
            coverageOverlays.push(coverageCircle);
        });
    }
    // Clear all drawings and routers
    function clearAll() {
        // Clear selected area
        if (selectedArea) {
            selectedArea.setMap(null);
            selectedArea = null;
        }

        // Clear routers
        routers.forEach(router => {
            router.marker.setMap(null);
        });
        routers = [];

        // Clear coverage overlays
        coverageOverlays.forEach(overlay => {
            overlay.setMap(null);
        });
        coverageOverlays = [];

        // Reset UI
        document.getElementById('coverage-progress').style.width = '0%';
        document.getElementById('coverage-percent').textContent = '0%';
        document.getElementById('recommendations').innerHTML = `
            <p class="text-muted">Draw an area and place routers to see recommendations</p>
        `;

        document.getElementById('calculate-btn').disabled = true;
    }

    function updateUI() {
        document.getElementById('calculate-btn').disabled = !selectedArea || routers.length === 0;
    }

</script>

</body>
</html>