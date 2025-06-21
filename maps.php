<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Network Coverage Visualization</title>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      background-color: #E6F2F4;
      font-family: Arial, sans-serif;
      color: #333;
      margin: 0;
      padding: 0;
    }

    .nav-link {
      color: #ffffff; 
    }

    .navbar-brand img {
      max-height: 40px; 
    }

    .main-container {
      display: flex;
      padding: 20px;
      gap: 20px;
      margin-top: 20px;
    }

    .controls-container {
      width: 350px;
      background-color: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .map-container {
      flex: 1;
      height: 700px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    #map {
      width: 100%;
      height: 100%;
    }

    h1 {
      color: #333;
      font-family: 'Garet', sans-serif;
      margin-bottom: 20px;
      text-align: center;
    }

    .btn {
      margin-bottom: 10px;
      width: 100%;
    }

    .draw-btn { background-color: #007bff; color: white; }
    .pin-btn { background-color: #28a745; color: white; }
    .remove-rect-btn { background-color: #d9534f; color: white; }
    .clear-pins-btn { background-color: #f0ad4e; color: white; }
    .back-btn { background-color: #6c757d; color: white; }

    #output {
      margin-top: 15px;
      padding: 10px;
      background-color: #f8f9fa;
      border-radius: 4px;
      border-left: 4px solid #007bff;
    }

    #recommendation {
      margin-top: 15px;
      padding: 15px;
      background-color: #f8f9fa;
      border-radius: 4px;
      border-left: 4px solid #28a745;
    }

    .device-select {
      width: 100%;
      padding: 8px;
      margin-bottom: 15px;
      border-radius: 4px;
      border: 1px solid #ced4da;
    }

    .foot-container {
      background-color: #E6F2F4; 
      padding: 50px;
      margin-top: 20px;
      border-radius: 10px;
    }

    .foot-icons a {
      color: #333; 
      font-size: 24px;
      margin-right: 10px;
    }

    .foot-policy .foot-policy {
      color: #333;
    }

    .foot_text {
      color: #333; 
      font-size: 14px;
      line-height: 1.6;
    }

    #grad {
      background: linear-gradient(90deg, #007bff 0%, #00b4ff 100%);
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark" id="grad">
    <div class="container-fluid">
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
        <div class="auth-buttons ms-auto">
          <a class="btn btn-primary" href="login.php">LOGIN</a>
          <a class="nav-link" href="register.php">SIGN UP</a>
        </div>
      </div>
    </div>
  </nav>

  <div class="container mt-4">
    <h1>Network Coverage Visualization</h1>
    <div class="main-container">
      <div class="controls-container">
        <input id="searchBox" type="text" class="form-control mb-3" placeholder="Search location...">
        
        <button id="drawRectBtn" class="btn draw-btn">Draw Square</button>
        <button id="placePinBtn" class="btn pin-btn">Place Pin</button>
        
        <select id="deviceSelector" class="device-select">
          <option value="297|#007BFF|Starlink Router">Starlink Router - 297m²</option>
          <option value="200|#28a745|EAP110-Outdoor V3">EAP110-Outdoor V3 - 200m²</option>
        </select>
        
        <button id="removeRectangleBtn" class="btn remove-rect-btn">Remove Rectangle</button>
        <button id="clearAllPinsBtn" class="btn clear-pins-btn">Clear All Pins</button>
        
        <div id="output">Click "Draw Square" or "Place Pin" to start</div>
        <div id="recommendation"></div>
        
        <!-- Back to Booking button - hidden by default -->
        <button id="backToBookingBtn" class="btn back-btn" style="display: none;">
          <i class="bi bi-arrow-left"></i> Back to Booking Customization
        </button>
      </div>
      
      <div class="map-container">
        <div id="map"></div>
      </div>
    </div>
  </div>

  <div class="foot-container" id="grad">
    <div class="foot-icons text-center">
      <a href="https://www.youtube.com/" class="bi bi-youtube text-altlight" target="_blank"></a>
      <a href="https://web.facebook.com/" class="bi bi-facebook text-altlight" target="_blank"></a>
      <a href="https://www.instagram.com/" class="bi bi-instagram text-altlight" target="_blank"></a>
      <a href="https://www.tiktok.com/" class="bi bi-tiktok text-altlight" target="_blank"></a>
    </div>
    <hr>
    <div class="foot-policy">
      <div class="row">
        <div class="col-md-3 text-center">
          <a class="foot-policy text-altlight" href="termsofservice.php" target="_blank">Terms of Service</a>
        </div>
        <div class="col-md-3 text-center">
          <a class="foot-policy text-altlight" href="copyrightpolicy.php" target="_blank">Copyright Policy</a>
        </div>
        <div class="col-md-3 text-center">
          <a class="foot-policy text-altlight" href="privacypolicy.php" target="_blank">Privacy Policy</a>
        </div>
        <div class="col-md-3 text-center">
          <a class="foot-policy text-altlight" href="contactus.php" target="_blank">Contact Us</a>
        </div>
      </div>
    </div>
    <hr>
    <div class="foot_text text-altlight text-center">
      <p>Wi-spot is available in English, French, German, Italian, Spanish, and more.</p>
      <p>
        &copy;2025 Wi-spot. All rights reserved. Wi-spot and related trademarks and logos are the property of Wi-spot. All other trademarks are the property of their respective owners.
      </p>
      <p>
        This webpage is for educational purposes only and no copyright infringement is intended.
      </p>
    </div>
  </div>

  <script>
    let map;
    let drawingManager;
    let rectangle = null;
    let rectangleArea = 0;
    let pins = [];
    let mode = null;
    let routerIcon;
    let additionalEAPs = 0;
    let recommendedPackage = '';

    // Check if page was opened from booking_customization.php
    const urlParams = new URLSearchParams(window.location.search);
    const fromBooking = urlParams.has('fromBooking');

    // Show back button if opened from booking page
    if (fromBooking) {
      document.getElementById('backToBookingBtn').style.display = 'block';
    }

    function initMap() {
      const philippines = { lat: 13.41, lng: 122.56 };
      map = new google.maps.Map(document.getElementById("map"), {
        center: philippines,
        zoom: 6,
      });

      // Define router icon AFTER maps is initialized
      routerIcon = {
        url: "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="%23007BFF" class="bi bi-wifi" viewBox="0 0 16 16">
            <path d="M15.384 6.115a.485.485 0 0 0-.047-.736A12.44 12.44 0 0 0 8 3C5.259 3 2.723 3.882.663 5.379a.485.485 0 0 0-.048.736.52.52 0 0 0 .668.05A11.45 11.45 0 0 1 8 4c2.507 0 4.827.802 6.716 2.164.205.148.49.13.668-.049"/>
            <path d="M13.229 8.271a.482.482 0 0 0-.063-.745A9.46 9.46 0 0 0 8 6c-1.905 0-3.68.56-5.166 1.526a.48.48 0 0 0-.063.745.525.525 0 0 0 .652.065A8.46 8.46 0 0 1 8 7a8.46 8.46 0 0 1 4.576 1.336c.206.132.48.108.653-.065m-2.183 2.183c.226-.226.185-.605-.1-.75A6.5 6.5 0 0 0 8 9c-1.06 0-2.062.254-2.946.704-.285.145-.326.524-.1.75l.015.015c.16.16.407.19.611.09A5.5 5.5 0 0 1 8 10c.868 0 1.69.201 2.42.56.203.1.45.07.61-.091zM9.06 12.44c.196-.196.198-.52-.04-.66A2 2 0 0 0 8 11.5a2 2 0 0 0-1.02.28c-.238.14-.236.464-.04.66l.706.706a.5.5 0 0 0 .707 0l.707-.707z"/>
            </svg>
        `),
        scaledSize: new google.maps.Size(32, 32),
        anchor: new google.maps.Point(16, 16)
      };

      const input = document.getElementById("searchBox");
      const searchBox = new google.maps.places.SearchBox(input);
      map.addListener("bounds_changed", () => {
        searchBox.setBounds(map.getBounds());
      });
      searchBox.addListener("places_changed", () => {
        const places = searchBox.getPlaces();
        if (places.length === 0) return;
        const bounds = new google.maps.LatLngBounds();
        places.forEach((place) => {
          if (!place.geometry) return;
          if (place.geometry.viewport) bounds.union(place.geometry.viewport);
          else bounds.extend(place.geometry.location);
        });
        map.fitBounds(bounds);
      });

      drawingManager = new google.maps.drawing.DrawingManager({
        drawingControl: false,
        drawingMode: null,
        rectangleOptions: {
          fillColor: "#ff0000",
          fillOpacity: 0.3,
          strokeWeight: 2,
          editable: true,
          draggable: true,
        },
      });
      drawingManager.setMap(map);

      google.maps.event.addListener(drawingManager, "rectanglecomplete", (rect) => {
        if (rectangle) rectangle.setMap(null);
        rectangle = rect;
        updateRectangleArea(rectangle);
        google.maps.event.addListener(rectangle, "bounds_changed", () => {
          updateRectangleArea(rectangle);
        });
      });

      document.getElementById("drawRectBtn").addEventListener("click", () => {
        mode = "rectangle";
        drawingManager.setDrawingMode(google.maps.drawing.OverlayType.RECTANGLE);
        updateOutput();
      });

      document.getElementById("placePinBtn").addEventListener("click", () => {
        mode = "pin";
        drawingManager.setDrawingMode(null);
        updateOutput();
      });

      document.getElementById("removeRectangleBtn").addEventListener("click", () => {
        if (rectangle) {
          rectangle.setMap(null);
          rectangle = null;
          rectangleArea = 0;
          updateOutput();
          document.getElementById("recommendation").innerHTML = "";
        }
      });

      document.getElementById("clearAllPinsBtn").addEventListener("click", () => {
        pins.forEach(p => {
          p.marker.setMap(null);
          p.circle.setMap(null);
        });
        pins = [];
        updateOutput("All pins cleared.");
        document.getElementById("recommendation").innerHTML = "";
      });

      // Back to booking button handler
      document.getElementById("backToBookingBtn").addEventListener("click", () => {
        saveDataToSession();
        window.location.href = "booking_customization.php";
      });

      map.addListener("click", (e) => {
        if (mode === "pin") {
          const [areaVal, color, deviceType] = document.getElementById("deviceSelector").value.split("|");
          const selectedArea = parseFloat(areaVal);
          const radius = Math.sqrt(selectedArea / Math.PI);
          addPinWithRadius(e.latLng, radius, selectedArea, color, deviceType);
        }
      });
    }

    function saveDataToSession() {
      // Create a form dynamically
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'save_coverage_data.php';
      
      // Add rectangle area
      const areaInput = document.createElement('input');
      areaInput.type = 'hidden';
      areaInput.name = 'area_size';
      areaInput.value = rectangleArea.toFixed(2);
      form.appendChild(areaInput);
      
      // Add recommended package
      const packageInput = document.createElement('input');
      packageInput.type = 'hidden';
      packageInput.name = 'recommended_package';
      packageInput.value = recommendedPackage;
      form.appendChild(packageInput);
      
      // Add additional EAPs if needed
      const eapInput = document.createElement('input');
      eapInput.type = 'hidden';
      eapInput.name = 'additional_eaps';
      eapInput.value = additionalEAPs;
      form.appendChild(eapInput);
      
      // Submit the form
      document.body.appendChild(form);
      form.submit();
    }

    function addPinWithRadius(position, radius, areaLabel, color, deviceType) {
      const marker = new google.maps.Marker({
        position: position,
        map: map,
        icon: routerIcon,
        draggable: true,
      });

      const circle = new google.maps.Circle({
        map: map,
        radius: radius,
        fillColor: color,
        fillOpacity: 0.3,
        strokeColor: color,
        strokeOpacity: 0.8,
        strokeWeight: 2,
      });

      circle.bindTo("center", marker, "position");

      pins.push({ marker, circle, deviceType, area: areaLabel });

      marker.addListener("drag", () => {
        updateOutput();
        checkCoverage();
      });

      marker.addListener("click", () => {
        if (confirm("Remove this pin and its coverage area?")) {
          marker.setMap(null);
          circle.setMap(null);
          pins = pins.filter(p => p.marker !== marker);
          updateOutput("Pin removed.");
          checkCoverage();
        }
      });

      updateOutput();
      checkCoverage();
    }

    function updateRectangleArea(rect) {
      const bounds = rect.getBounds();
      const NE = bounds.getNorthEast();
      const SW = bounds.getSouthWest();
      const SE = new google.maps.LatLng(SW.lat(), NE.lng());
      const NW = new google.maps.LatLng(NE.lat(), SW.lng());
      rectangleArea = google.maps.geometry.spherical.computeArea([NE, SE, SW, NW]);
      updateOutput();
      checkCoverage();
    }

    function updateOutput(extraMessage = "") {
      const rectMsg = rectangleArea > 0 ? `<strong>Rectangle Area:</strong> ${rectangleArea.toFixed(2)} m²` : "";
      const pinCount = pins.length;
      const pinMsg = pinCount > 0 ? `<strong>${pinCount} pin(s)</strong> placed.` : "No pins placed.";
      const extra = extraMessage ? `<br>${extraMessage}` : "";

      document.getElementById("output").innerHTML =
        `${rectMsg}<br>${pinMsg}${extra}`;
    }
    
    function checkCoverage() {
      if (rectangleArea === 0 || pins.length === 0) {
        document.getElementById("recommendation").innerHTML = "";
        return;
      }
      
      // Count device types
      const deviceCounts = {
        "Starlink Router": 0,
        "EAP110-Outdoor V3": 0
      };
      
      pins.forEach(pin => {
        deviceCounts[pin.deviceType]++;
      });
      
      // Calculate total coverage area (simplified - doesn't account for overlapping circles)
      const starlinkCoverage = deviceCounts["Starlink Router"] * 297;
      const eapCoverage = deviceCounts["EAP110-Outdoor V3"] * 200;
      const totalCoverage = starlinkCoverage + eapCoverage;
      
      // Check if coverage fills the rectangle (using 90% threshold to account for overlaps)
      const coverageFills = totalCoverage >= rectangleArea * 0.9;
      
      // Reset additional EAPs
      additionalEAPs = 0;
      
      // Generate recommendation
      let recommendation = "";
      
      if (!coverageFills) {
        const neededCoverage = rectangleArea * 0.9 - totalCoverage;
        additionalEAPs = Math.ceil(neededCoverage / 200);
        recommendation = `<div class="alert alert-warning">
          <strong>Current coverage is insufficient.</strong><br>
          Add at least ${additionalEAPs} more EAP110-Outdoor V3 router(s) to adequately cover the area.
        </div>`;
        recommendedPackage = '';
      } else {
        // Determine which package to recommend
        const eapCount = deviceCounts["EAP110-Outdoor V3"];
        
        if (eapCount === 0) {
          recommendation = `<div class="alert alert-success">
            <strong>Recommended package:</strong> Basic Kit (1 Starlink Router)<br>
            Current setup provides adequate coverage for the area.
          </div>`;
          recommendedPackage = 'Basic Kit';
        } else if (eapCount === 1) {
          recommendation = `<div class="alert alert-success">
            <strong>Recommended package:</strong> Boost Kit (1 Starlink Router + 1 EAP110-Outdoor V3)<br>
            Current setup provides adequate coverage for the area.
          </div>`;
          recommendedPackage = 'Boost Kit';
        } else if (eapCount >= 2 && eapCount <= 3) {
          recommendation = `<div class="alert alert-success">
            <strong>Recommended package:</strong> Robust Kit (1 Starlink Router + 3 EAP110-Outdoor V3)<br>
            Current setup provides adequate coverage for the area.
          </div>`;
          recommendedPackage = 'Robust Kit';
        } else if (eapCount >= 4 && eapCount <= 5) {
          recommendation = `<div class="alert alert-success">
            <strong>Recommended package:</strong> Advance Kit (1 Starlink Router + 5 EAP110-Outdoor V3)<br>
            Current setup provides adequate coverage for the area.
          </div>`;
          recommendedPackage = 'Advance Kit';
        } else if (eapCount > 5) {
          additionalEAPs = eapCount - 5;
          recommendation = `<div class="alert alert-success">
            <strong>Recommended package:</strong> Advance Kit (1 Starlink Router + 5 EAP110-Outdoor V3)<br>
            <strong>Plus ${additionalEAPs} additional EAP110-Outdoor V3 router(s)</strong><br>
            Current setup provides adequate coverage for the area.
          </div>`;
          recommendedPackage = 'Advance Kit';
        }
      }
      
      // Add device summary
      recommendation = `
        <div class="mb-3">
          <strong>Current Setup:</strong><br>
          ${deviceCounts["Starlink Router"]} Starlink Router(s)<br>
          ${deviceCounts["EAP110-Outdoor V3"]} EAP110-Outdoor V3 router(s)
        </div>
        ${recommendation}
      `;
      
      document.getElementById("recommendation").innerHTML = recommendation;
    }
  </script>

  <script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCFx7Z_5qK__AetA_wIPEFEpuAhIxIsouI&libraries=places,drawing,geometry&callback=initMap"
    async
    defer
  ></script>
</body>
</html>