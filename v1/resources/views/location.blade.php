<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Live Location - OpenStreetMap</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }

        .container {
            width: 350px;
            margin: 80px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background: #0056b3;
        }

        .output {
            margin-top: 15px;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>📍 Live Location (OpenStreetMap)</h2>
        <button onclick="getLocation()">Get My Location</button>

        <div id="output" class="output"></div>
    </div>

    <script>
        function getLocation() {
            if (!navigator.geolocation) {
                alert("Geolocation not supported");
                return;
            }

            document.getElementById("output").innerHTML = "Fetching location...";

            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;

                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
                        .then(res => res.json())
                        .then(data => {
                            const addr = data.address;

                            document.getElementById("output").innerHTML = `
            <b>Latitude:</b> ${lat}<br>
            <b>Longitude:</b> ${lon}<br><br>
            <b>Street:</b> ${addr.road || ''}<br>
            <b>City:</b> ${addr.city || addr.town || ''}<br>
            <b>District:</b> ${addr.county || ''}<br>
            <b>State:</b> ${addr.state || ''}<br>
            <b>Country:</b> ${addr.country || ''}
          `;
                        });
                },
                error => {
                    alert("Location permission denied");
                }
            );
        }

    </script>
</body>

</html>