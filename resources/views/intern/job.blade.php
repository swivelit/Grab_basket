<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Openings - Swivel IT</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f2f6ff;
            padding: 0;
        }

        /* ---------------- NAVBAR ---------------- */
        .navbar-custom {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .navbar-brand {
            font-weight: 700;
            color: #fff !important;
            font-size: 22px;
        }

        .navbar-brand span {
            font-size: 14px;
            font-weight: 500;
            display: block;
        }

        .nav-link {
            color: #e3e8ff !important;
            font-size: 16px;
            font-weight: 500;
            padding-right: 18px;
            transition: 0.3s ease;
        }

        .nav-link:hover {
            color: #fff !important;
            transform: translateY(-2px);
        }

        /* ---------------- JOB CARDS ---------------- */
        .job-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 25px;
            margin-top: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: 0.3s;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .apply-btn {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            border-radius: 30px;
            padding: 12px 25px;
            font-size: 17px;
            font-weight: 600;
            border: none;
            transition: 0.3s;
        }

        .apply-btn:hover {
            background: linear-gradient(135deg, #2575fc, #6a11cb);
            transform: scale(1.07);
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            color: #1a237e;
        }

        .subtext {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .highlight {
            font-weight: 700;
            color: #0d47a1;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                Swivel IT Company
                <span>DLF IT Park, Porur,Chennai</span>
            </a>

            <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="https://swivtrek.in/#about">About</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="https://swivtrek.in/#contact">Contact</a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="text-center mt-5 mb-4 fw-bold">Current Job Openings</h1>

        <div class="row g-4">

            <div class="col-md-6">
                <div class="job-card">
                    <h2 class="title">NQT Exam Openings</h2>
                    <p class="subtext"><span class="highlight">Unlimited Attempts</span></p>

                    <p class="subtext">
                        <span class="highlight">
                            <strong>Domain: NQT</strong>
                        </span>
                    </p>

                    <p class="subtext">Exam Frequency: <span class="highlight">Weekly</span></p>
                    <p class="subtext">Exam Fee: <span class="highlight">₹1,000</span></p>
                    <p class="subtext">Eligibility: <span class="highlight">Any Degree</span></p>

                    <button class="apply-btn mt-3"
                        onclick="applyNow('NQT Exam', 1000, '3hrs', 'NQT')">
                        Apply Now
                    </button>
                </div>
            </div>

            <!-- Chennai Branch -->
            <div class="col-md-6">
                <div class="job-card">
                    <h2 class="title">Chennai Branch Openings</h2>
                    <p class="subtext"><span class="highlight">90 Openings</span></p>

                    <p class="subtext">
                        <span class="highlight"><strong>Roles: Technical Associate</strong></span>
                    </p>

                    <p class="subtext">Course Period: <span class="highlight">6 Months</span></p>
                    <p class="subtext"> Course Fee + GST: <span class="highlight">₹11,800</span></p>
                    <p class="subtext">Starting Monthly Package: <span class="highlight">₹24,000</span></p>

                    <button class="apply-btn mt-3"
                        onclick="applyNow('Technical Associate', 11800, 24, 'IT Section')">
                        Apply Now
                    </button>
                </div>
            </div>

            <!-- Pondicherry Branch -->
            <div class="col-md-6">
                <div class="job-card">
                    <h2 class="title">Pondicherry Branch Openings</h2>
                    <p class="subtext"><span class="highlight">30 Openings</span></p>

                    <p class="subtext">
                        <span class="highlight">
                            <strong>Roles: Web Developer & Digital Marketing</strong>
                        </span>
                    </p>

                    <p class="subtext">Course Period: <span class="highlight">6 Months</span></p>
                    <p class="subtext"> Course Fee + GST: <span class="highlight">₹11,800</span></p>
                    <p class="subtext">Monthly Package After Internship: <span class="highlight">₹24,000</span></p>

                    <button class="apply-btn mt-3"
                        onclick="applyNow('Web Development', 11800, 24, 'IT Section')">
                        Apply Now
                    </button>
                </div>
            </div>

            <!-- NQT Exam Opening -->

        </div>

    </div>
    <script>
        function applyNow(name, fee, weeks, domain) {
            const url = `/internship/apply?name=${encodeURIComponent(name)}&fee=${fee}&weeks=${weeks}&domain=${encodeURIComponent(domain)}`;
            window.location.href = url;
        }
    </script>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>