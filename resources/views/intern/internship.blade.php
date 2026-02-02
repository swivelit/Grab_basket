<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Internship Details</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ICONS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7ff;
        }

        .header-section {
            background: linear-gradient(135deg, #1a73e8, #6a11cb);
            padding: 60px 0;
            color: white;
            text-align: center;
        }

        .header-section h1 {
            font-size: 42px;
            font-weight: 700;
        }

        .section-title {
            font-size: 30px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
        }

        .internship-card {
            border-radius: 15px;
            padding: 25px;
            background: white;
            transition: 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .internship-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .process-step {
            text-align: center;
            padding: 20px;
        }

        .process-step i {
            font-size: 40px;
            color: #6a11cb;
            margin-bottom: 10px;
        }

        .require-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .apply-btn {
            background: #1a73e8;
            color: white;
        }

        .apply-btn:hover {
            background: #0d52b6;
        }

        .cards-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 576px) {
            .cards-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 768px) {
            .cards-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 992px) {
            .cards-container {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (min-width: 1200px) {
            .cards-container {
                grid-template-columns: repeat(5, 1fr);
            }
        }
    </style>
</head>

<body>

    <!-- HEADER SECTION -->
    <div class="header-section">
        <h1>Welcome to Our NQT Program</h1>
        <p class="lead">Learn, Build & Grow with Real-World Skills</p>
    </div>

    <!-- ABOUT SECTION -->
    <div class="container mt-5">
        <div class="section-title">About Our NQT</div>

        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="require-box">
                    <p style="font-size: 17px;">
                        Our internships are designed to provide hands-on experience with industry-level projects.
                        Whether you choose Python, AI, Full-Stack, Data Science, Marketing, or Cloud, you will work
                        on real-time tasks guided by expert mentors.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- PROCESS SECTION -->
    <div class="container mt-5">
        <div class="section-title">NQT Process</div>

        <div class="row text-center">
            <div class="col-md-3 process-step">
                <i class="fas fa-file-alt"></i>
                <h5>1. Registration</h5>
                <p>Choose your internship and complete registration.</p>
            </div>

            <div class="col-md-3 process-step">
                <i class="fas fa-book-reader"></i>
                <h5>2. Training</h5>
                <p>Learn concepts with mentor-guided sessions.</p>
            </div>

            <div class="col-md-3 process-step">
                <i class="fas fa-laptop-code"></i>
                <h5>3. Projects</h5>
                <p>Work on real-world assignments and projects.</p>
            </div>

            <div class="col-md-3 process-step">
                <i class="fas fa-certificate"></i>
                <h5>4. Certification</h5>
                <p>Receive an internship completion certificate.</p>
            </div>
        </div>
    </div>

    <!-- REQUIREMENTS SECTION -->
    <div class="container mt-5">
        <div class="section-title">NQT Requirements</div>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="require-box">
                    <ul style="font-size: 17px;">
                        <li>Basic knowledge of computers</li>
                        <li>Willingness to learn new skills</li>
                        <li>Laptop or Desktop with internet</li>
                        <li>Basic problem-solving ability</li>
                        <li>No prior experience required</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <br>

    <!-- TOTAL OPENINGS BOX -->
    <h2 class="opening" id="totalOpenings"></h2>
    <script>
        let totalOpenings = 20000;
        document.getElementById("totalOpenings").innerHTML = `
        <div class="openings-box">
            <span>Total NQT Openings Left</span>
            
            <strong>${totalOpenings}</strong>
        </div>
    `;
    </script>

    <style>
        .openings-box {
            width: 100%;
            max-width: 450px;
            margin: 20px auto;
            padding: 15px;
            text-align: center;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            font-size: 22px;
            font-weight: 600;
            border-radius: 14px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
            letter-spacing: 1px;
        }

        .openings-box strong {
            font-size: 26px;
            font-weight: 700;
            margin-left: 8px;
            color: #fff700;
            text-shadow: 1px 1px 2px black;
        }

        @media (max-width: 576px) {
            .openings-box {
                font-size: 18px;
                padding: 12px;
            }

            .openings-box strong {
                font-size: 22px;
            }
        }
    </style>

    <!-- INTERNSHIP OPENINGS -->
    <div class="container mt-5 mb-5">
        <div class="section-title">NQT Programs</div>
        <div class="cards-container" id="internshipContainer"></div>
    </div>

    <!-- JS SECTION -->
    <script>
        const internships = [{
                name: "PYTHON BASICS",
                fee: 5000,
                weeks: 12,
                domain: "Python"
            },
            {
                name: "PYTHON (BASICS TO INTERMEDIATE)",
                fee: 5000,
                weeks: 12,
                domain: "Python"
            },
            {
                name: "PYTHON ADVANCED",
                fee: 5000,
                weeks: 12,
                domain: "Python"
            },
            {
                name: "PYTHON (BASICS TO ADVANCED)",
                fee: 5000,
                weeks: 12,
                domain: "Python"
            },
            {
                name: "PYTHON WITH AI",
                fee: 5000,
                weeks: 12,
                domain: "Python + AI"
            },
            {
                name: "PYTHON WITH GEN AI",
                fee: 5000,
                weeks: 12,
                domain: "Python + Gen AI"
            },
            {
                name: "PYTHON WITH AGENTIC AI",
                fee: 5000,
                weeks: 12,
                domain: "Agentic AI"
            },
            {
                name: "PYTHON FULLSTACK",
                fee: 5000,
                weeks: 12,
                domain: "Fullstack"
            },
            {
                name: "PYTHON FULLSTACK WITH AI",
                fee: 5000,
                weeks: 12,
                domain: "Fullstack + AI"
            },
            {
                name: "PROMPT ENGINEERING FOUNDATION",
                fee: 5000,
                weeks: 12,
                domain: "Prompt Engineering"
            },
            {
                name: "PROMPT ENGINEERING ADVANCED",
                fee: 5000,
                weeks: 12,
                domain: "Prompt Engineering"
            },
            {
                name: "DATA ANALYTICS",
                fee: 5000,
                weeks: 12,
                domain: "Data Analytics"
            },
            {
                name: "DATA ANALYTICS WITH AI",
                fee: 5000,
                weeks: 12,
                domain: "Data Analytics + AI"
            },
            {
                name: "DATASCIENCE",
                fee: 5000,
                weeks: 12,
                domain: "Data Science"
            },
            {
                name: "DATASCIENCE WITH AI",
                fee: 5000,
                weeks: 12,
                domain: "Data Science + AI"
            },
            {
                name: "AI ENGINEER",
                fee: 5000,
                weeks: 12,
                domain: "AI Engineering"
            },
            {
                name: "AGENTIC AI FOUNDATION",
                fee: 5000,
                weeks: 12,
                domain: "Agentic AI"
            },
            {
                name: "AGENTIC AI AUTOMATION (N8N)",
                fee: 5000,
                weeks: 12,
                domain: "N8N Automation"
            },
            {
                name: "AGENTIC AI",
                fee: 5000,
                weeks: 12,
                domain: "Agentic AI"
            },
            {
                name: "DIGITAL MARKETING FOUNDATION",
                fee: 5000,
                weeks: 12,
                domain: "Digital Marketing"
            },
            {
                name: "DIGITAL MARKETING",
                fee: 5000,
                weeks: 12,
                domain: "Digital Marketing"
            },
            {
                name: "DIGITAL MARKETING WITH AI",
                fee: 5000,
                weeks: 12,
                domain: "DM + AI"
            },
            {
                name: "DIGITAL MARKETING WITH GEN AI",
                fee: 5000,
                weeks: 12,
                domain: "DM + Gen AI"
            },
            {
                name: "DIGITAL MARKETING WITH AGENTIC AI",
                fee: 5000,
                weeks: 12,
                domain: "DM + Agentic AI"
            },
            {
                name: "MERN STACK",
                fee: 5000,
                weeks: 12,
                domain: "MERN"
            },
            {
                name: "MERN STACK WITH AI",
                fee: 5000,
                weeks: 12,
                domain: "MERN + AI"
            },
            {
                name: "LARAVEL",
                fee: 5000,
                weeks: 12,
                domain: "Laravel"
            },
            {
                name: "LARAVEL WITH AI",
                fee: 5000,
                weeks: 12,
                domain: "Laravel + AI"
            },
            {
                name: "SNOWFLAKE",
                fee: 5000,
                weeks: 12,
                domain: "Snowflake"
            },
            {
                name: "CLOUD FULLSTACK FOUNDATION",
                fee: 5000,
                weeks: 12,
                domain: "Cloud Fullstack"
            },
            {
                name: "CLOUD FULLSTACK INTERMEDIATE",
                fee: 5000,
                weeks: 12,
                domain: "Cloud Fullstack"
            },
            {
                name: "CLOUD FULLSTACK ADVANCED",
                fee: 5000,
                weeks: 12,
                domain: "Cloud Advanced"
            },
            {
                name: "National Qualifier Test",
                fee: 1000,
                weeks: 0,
                domain: "NQT"
            }
        ];

        const container = document.getElementById("internshipContainer");

        internships.forEach(item => {
            container.innerHTML += `
                <div class="internship-card">
                    <h5>${item.name}</h5>
                    <p><b>Domain:</b> ${item.domain}</p>
                    <p><b>Fees:</b> ₹${item.fee}</p>
                    <p><b>Duration:</b> ${item.weeks} Weeks</p>
                    <button class="btn apply-btn w-100 mt-3"
                        onclick="applyNow('${item.name}', ${item.fee}, ${item.weeks}, '${item.domain}')">
                        Apply Now
                    </button>
                </div>
            `;
        });

        function applyNow(name, fee, weeks, domain) {
            const url = `/internship/apply?name=${encodeURIComponent(name)}&fee=${fee}&weeks=${weeks}&domain=${encodeURIComponent(domain)}`;
            window.location.href = url;
        }
    </script>

</body>

</html>