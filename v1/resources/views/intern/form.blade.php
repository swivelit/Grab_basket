<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swivel IT Internship Application Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7ff;
            padding: 15px;
        }

        .container {
            max-width: 850px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
        }

        h2 {
            margin-bottom: 20px;
            text-align: center;
            font-size: 24px;
        }

        .company-logo {
            max-width: 100px;
            display: block;
            margin: 0 auto 20px auto;
        }

        .company-info {
            text-align: center;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .section-title {
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 16px;
        }

        .btn-success {
            font-size: 16px;
        }
    </style>
</head>

<body>
    <div class="container">
        <img src="{{ asset('asset/images/swivel.jpg') }}" alt="Swivel IT Logo" class="company-logo">
        <h2>Swivel IT Internship Application Form</h2>

        <div class="company-info">
            <p><strong>Company:</strong> Swivel IT</p>
            <p><strong>Address:</strong> DLF IT Park, Porur, Chennai</p>
            <p><strong>Program Highlights:</strong> Hands-on training, Real-time projects, Mentorship, Certificate &</p>
            <p><strong>NQT EXAM APPLICATION FORM</strong></p>
        </div>

        <form id="applicationForm">
            <!-- Internship Details -->
            <h5 class="section-title">Form Details</h5>
            <div class="mb-3">
                <label class="form-label">Course Name</label>
                <input type="text" class="form-control" id="courseName" name="courseName" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Domain</label>
                <input type="text" class="form-control" id="domain" name="domain" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Duration (Weeks)</label>
                <input type="text" class="form-control" id="weeks" name="weeks" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Fees (₹)</label>
                <input type="text" class="form-control" id="fee" name="fee" readonly>
            </div>

            <!-- Student Details -->
            <h5 class="section-title">Student Details</h5>
            <div class="row">
                <div class="mb-3 col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="studentName" required>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Age</label>
                    <input type="number" class="form-control" name="studentAge" required>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Gender</label>
                    <select class="form-select" name="studentGender" required>
                        <option value="">-- Select Gender --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="studentEmail" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" class="form-control" name="studentAddress" required>
            </div>
            <div class="mb-3">
                <label class="form-label">College/University</label>
                <input type="text" class="form-control" name="studentCollege" required>
            </div>
            <div class="row">
                <div class="mb-3 col-md-6">
                    <label class="form-label">Qualification</label>
                    <input type="text" class="form-control" name="studentQualification" required>
                </div>
                <div class="mb-3 col-md-6">
                    <label class="form-label">Passed Out Year</label>
                    <input type="number" class="form-control" name="studentPassedOut" required>
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-md-6">
                    <label class="form-label">District</label>
                    <input type="text" class="form-control" name="studentDistrict">
                </div>
                <div class="mb-3 col-md-6">
                    <label class="form-label">Reference</label>
                    <input type="text" class="form-control" name="studentReference">
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-md-6">
                    <label class="form-label">Pincode</label>
                    <input type="number" class="form-control" name="studentPincode">
                </div>
                <div class="mb-3 col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" name="studentPhone" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">WhatsApp Number</label>
                <input type="tel" class="form-control" name="studentWhatsapp">
            </div>

            <button type="button" id="rzp-button" class="btn btn-success w-100">Pay & Submit</button>
        </form>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        // Pre-fill course details from URL or defaults
        const params = new URLSearchParams(window.location.search);
        document.getElementById("courseName").value = params.get("name") || 'Swivel IT Internship Program';
        document.getElementById("domain").value = params.get("domain") || 'IT / Software Development';
        document.getElementById("weeks").value = params.get("weeks") || '8';
        document.getElementById("fee").value = params.get("fee") || '5000';

        document.getElementById('rzp-button').onclick = function(e) {
            e.preventDefault();
            let formData = new FormData(document.getElementById('applicationForm'));

            fetch("{{ url('/internship/payment') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.order_id) {
                        alert('Error creating Razorpay order');
                        return;
                    }

                    var options = {
                        "key": data.razorpay_key,
                        "amount": data.amount,
                        "currency": "INR",
                        "name": formData.get('studentName'),
                        "description": "Internship Fees",
                        "order_id": data.order_id,
                        "handler": function(response) {
                            fetch("{{ url('/internship/payment/success') }}", {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify({
                                        razorpay_payment_id: response.razorpay_payment_id,
                                        intern_id: data.intern_id
                                    })
                                }).then(res => res.text())
                                .then(html => document.body.innerHTML = html);
                        },
                        "theme": {
                            "color": "#3399cc"
                        }
                    };
                    var rzp1 = new Razorpay(options);
                    rzp1.open();
                })
                .catch(err => console.error(err));
        }
    </script>
</body>

</html>