<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Products - GrabBaskets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Test Products Search Page</h1>
        <p>This is a simple test page to verify the route is working.</p>
        
        <div class="alert alert-success">
            <h4>✅ Success!</h4>
            <p>The /products route is now working. This means our AJAX-based search interface can be deployed.</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h3>API Test</h3>
                <button id="testAPI" class="btn btn-primary">Test Instant Search API</button>
                <div id="apiResult" class="mt-3"></div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('testAPI').addEventListener('click', async () => {
            try {
                const response = await fetch('/api/search/instant?q=');
                const data = await response.json();
                document.getElementById('apiResult').innerHTML = `
                    <div class="alert alert-info">
                        <strong>API Response:</strong><br>
                        Products: ${data.products ? data.products.length : 0}<br>
                        Categories: ${data.categories ? data.categories.length : 0}<br>
                        Status: ✅ Working
                    </div>
                `;
            } catch (error) {
                document.getElementById('apiResult').innerHTML = `
                    <div class="alert alert-danger">
                        <strong>API Error:</strong> ${error.message}
                    </div>
                `;
            }
        });
    </script>
</body>
</html>