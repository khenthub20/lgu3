<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Privacy | Baranggay Laforteza Holdings 264</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg: #020617;
            --glass-bg: rgba(15, 23, 42, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 800px;
            width: 100%;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .brand-logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, var(--primary), #a855f7);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }

        h1 { font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        h2 { font-size: 1.8rem; font-weight: 700; color: var(--primary); margin: 2rem 0 1rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem; }
        h3 { font-size: 1.2rem; font-weight: 600; color: #fff; margin: 1.5rem 0 0.8rem; }
        h4 { font-size: 1.1rem; font-weight: 600; color: #cbd5e1; margin: 1.2rem 0 0.5rem; }
        
        p { margin-bottom: 1.2rem; color: var(--text-muted); }
        ul { margin-bottom: 1.2rem; padding-left: 1.5rem; color: var(--text-muted); }
        li { margin-bottom: 0.6rem; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: 0.3s;
        }
        .back-link:hover { color: #fff; transform: translateX(-5px); }

        .footer {
            text-align: center;
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        @media (max-width: 640px) {
            .container { padding: 2rem 1.5rem; }
            h1 { font-size: 1.8rem; }
            h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="container">
        <a href="signup.php" class="back-link">
            <i data-feather="arrow-left" style="width:18px;"></i> Back to Registration
        </a>

        <div class="header">
            <div class="brand-logo">
                <i data-feather="shield" style="color:#fff; width:32px; height:32px;"></i>
            </div>
            <h1>Terms & Data Privacy Policy</h1>
            <p>Last updated: February 1, 2026</p>
        </div>

        <section>
            <h2>Terms and Conditions</h2>
            <p>These Terms and Conditions govern the use of the Baranggay Laforteza Holdings 264 Public Facilities Reservation System. By accessing the portal, citizens, organizations, and partner agencies agree to observe the policies set by the Municipal Facilities Management Office.</p>
            
            <p>Reservations are considered tentative until a confirmation notice is issued by the LGU. The LGU reserves the right to reassign, reschedule, or decline requests to ensure continuity of essential public services, disaster response operations, and official functions.</p>
            
            <p>Users shall provide accurate contact information, submit complete supporting documents, and settle applicable fees within the prescribed period. Non-compliance may result in cancellation without prejudice to future bookings.</p>
            
            <p>Any unauthorized commercial activity, political gathering without clearance, or activity that jeopardizes public safety is strictly prohibited. Damages to facilities shall be charged to the reserving party and may include administrative sanctions.</p>
            
            <p>By proceeding, you acknowledge that you have read and understood these terms and agree to comply with all LGU directives related to facility utilization.</p>
        </section>

        <section>
            <h2>Data Privacy Policy</h2>
            
            <h3>1. Data Controller</h3>
            <p>The Baranggay Laforteza Holdings 264 Public Facilities Reservation System is operated by the Baranggay Laforteza Holdings 264 Facilities Management Office. We are committed to protecting your personal data in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173) and its Implementing Rules and Regulations.</p>

            <h3>2. Data Protection Officer</h3>
            <p>For privacy concerns, you may contact our Data Protection Officer:</p>
            <ul>
                <li><strong>Email:</strong> dpo@laforteza.gov.ph</li>
                <li><strong>Office:</strong> Baranggay Laforteza Holdings 264 Facilities Management Office</li>
                <li><strong>Contact:</strong> Via the Contact page of this portal</li>
            </ul>

            <h3>3. What Data We Collect</h3>
            <p>We collect only the minimum personal data required to process facility reservations:</p>
            <ul>
                <li><strong>Identity Information:</strong> Name, valid ID (optional)</li>
                <li><strong>Contact Information:</strong> Email address, mobile number</li>
                <li><strong>Address Information:</strong> Street, house number (to verify residency in Baranggay Laforteza Holdings 264)</li>
                <li><strong>Reservation Details:</strong> Facility, date, time, purpose, number of attendees</li>
            </ul>

            <h3>4. Why We Collect Your Data</h3>
            <p>We process your personal data based on:</p>
            <ul>
                <li>Your consent when you register and accept this policy</li>
                <li>Legitimate government function to manage public facilities and serve residents</li>
                <li>Legal obligation to maintain records as required by government regulations</li>
            </ul>

            <h3>5. How We Use Your Data</h3>
            <p>Your information is used solely for:</p>
            <ul>
                <li>Verifying your identity and residency</li>
                <li>Processing and managing facility reservations</li>
                <li>Communicating reservation status and updates</li>
                <li>Coordinating facility usage and scheduling</li>
                <li>Sending official advisories related to your reservations</li>
                <li>Improving service delivery through anonymized analytics</li>
            </ul>

            <h3>6. Data Sharing and Disclosure</h3>
            <p>We do not sell or share your personal data with third parties, except:</p>
            <ul>
                <li>When required by law or court order</li>
                <li>When necessary to protect public safety or interest</li>
                <li>With other LGU offices for official coordination (e.g., disaster response)</li>
                <li>With your explicit consent</li>
            </ul>

            <h3>7. Data Retention</h3>
            <p>Personal data is retained for:</p>
            <ul>
                <li><strong>Active accounts:</strong> Duration of account + 3 years after last activity</li>
                <li><strong>Reservation records:</strong> 5 years as required by COA regulations</li>
                <li><strong>Audit logs:</strong> 2 years for security purposes</li>
            </ul>
            <p>After retention periods, data is securely deleted or anonymized.</p>

            <h3>8. Your Rights as a Data Subject</h3>
            <p>Under the Data Privacy Act, you have the right to:</p>
            <ul>
                <li><strong>Access:</strong> Request a copy of your personal data</li>
                <li><strong>Rectify:</strong> Correct inaccurate or incomplete information</li>
                <li><strong>Erase:</strong> Request deletion of your data</li>
                <li><strong>Object:</strong> Object to processing for direct marketing</li>
                <li><strong>Data Portability:</strong> Receive your data in a structured format</li>
                <li><strong>Withdraw Consent:</strong> Withdraw consent at any time</li>
            </ul>

            <h3>9. Security Measures</h3>
            <p>We implement robust security safeguards: technical (encryption, HTTPS), organizational (role-based access), and physical (secure server facilities).</p>

            <h3>10. Automated Decision-Making</h3>
            <p>This system uses AI-powered features for conflict detection and recommendations. These are advisory only; final decisions are made by authorized LGU staff.</p>

            <h3>11. Data Breach Notification</h3>
            <p>In the event of a breach, we will notify the National Privacy Commission within 72 hours and affected individuals without undue delay.</p>

            <h3>12. Cookies and Tracking</h3>
            <p>We use essential cookies for authentication and anonymized analytics. No third-party tracking cookies are used.</p>

            <h3>13. Children's Privacy</h3>
            <p>This system is intended for users 18 years and older. We do not knowingly collect personal data from minors.</p>

            <h3>14. Changes to This Policy</h3>
            <p>We may update this policy. Significant changes will be communicated via email or system notification.</p>

            <h3>15. Contact Us</h3>
            <p>For questions or to exercise your rights, contact our Data Protection Officer at dpo@laforteza.gov.ph or the National Privacy Commission at complaints@privacy.gov.ph.</p>
        </section>

        <div class="footer">
            <p>&copy; 2026 Baranggay Laforteza Holdings 264. All Rights Reserved.</p>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>
