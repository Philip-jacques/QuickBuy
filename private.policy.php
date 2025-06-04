<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - QuickBuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Your existing CSS variables (from RegisterPage) */
        :root {
            /* Main blues */
            --true-blue: #0466c8ff;
            --sapphire: #0353a4ff;
            --yale-blue: #023e7dff;
            --oxford-blue: #002855ff;
            --oxford-blue-2: #001845ff;
            --oxford-blue-3: #001233ff;

            /* Greens & Deeper Blues */
            --caribbean-current: #006466ff;
            --midnight-green: #065a60ff;
            --midnight-green-2: #0b525bff;
            --midnight-green-3: #144552ff;
            --prussian-blue: #212f45ff;
            --deep-space-blue: #0d1b2a;

            /* Neutrals */
            --gunmetal: #30343fff;
            --ghost-white: #fafaffff;
            --delft-blue: #273469ff;
            --space-cadet: #1e2749ff;
            --paynes-gray: #5c677dff;
            --slate-gray: #7d8597ff;
            --cool-gray: #979dacff;
            --charcoal: #1b3a4bff;

            /* Accent */
            --white-pop: #FFFFFF;
        }

        body {
            /* Galactic Market Background - Copied from LoginPage */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%;
            animation: bgShift 25s ease infinite;
            font-family: 'Poppins', sans-serif;
            color: var(--ghost-white);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 20px;
            min-height: 100vh;
            overflow-x: hidden;
        }

        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .policy-container {
            background-color: rgba(27, 42, 75, 0.4); /* Matches RegisterPage container */
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.5);
            width: 95%;
            max-width: 900px; /* Wider for policy content */
            text-align: left;
            box-sizing: border-box;
            color: var(--ghost-white);
            margin: 40px auto; /* Add margin for spacing */
            overflow-y: auto;
            max-height: 90vh; /* Limit height to allow scrolling within container */
        }

        .policy-container h1 {
            font-size: 2.8em;
            color: var(--white-pop);
            margin-bottom: 25px;
            text-align: center;
        }

        .policy-container h2 {
            font-size: 1.8em;
            color: var(--true-blue);
            margin-top: 35px;
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 8px;
        }

        .policy-container h3 {
            font-size: 1.4em;
            color: var(--white-pop);
            margin-top: 25px;
            margin-bottom: 10px;
        }

        .policy-container p, .policy-container ul li {
            font-size: 1em;
            line-height: 1.7;
            color: var(--slate-gray);
            margin-bottom: 15px;
        }

        .policy-container ul {
            list-style: none;
            padding-left: 0;
            margin-bottom: 20px;
        }

        .policy-container ul li {
            position: relative;
            padding-left: 25px;
            margin-bottom: 8px;
        }

        .policy-container ul li::before {
            content: '•';
            color: var(--white-pop);
            font-size: 1.2em;
            position: absolute;
            left: 0;
            top: 0;
        }

        .policy-container a {
            color: var(--white-pop);
            text-decoration: underline;
            transition: color 0.2s ease;
        }

        .policy-container a:hover {
            color: var(--caribbean-current);
        }

        /* Scrollbar styling for policy container */
        .policy-container::-webkit-scrollbar {
            width: 8px;
        }

        .policy-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .policy-container::-webkit-scrollbar-thumb {
            background: var(--sapphire);
            border-radius: 10px;
            transition: background 0.3s ease;
        }

        .policy-container::-webkit-scrollbar-thumb:hover {
            background: var(--true-blue);
        }

        /* Back button styling - new */
        .back-button-container {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .back-button {
            display: inline-block;
            background-color: var(--midnight-green);
            color: var(--ghost-white);
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.05em;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .back-button:hover {
            background-color: var(--caribbean-current);
            transform: translateY(-2px);
            color: var(--ghost-white); /* Ensure text color stays white on hover */
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .policy-container {
                padding: 30px;
                margin: 20px auto;
            }
            .policy-container h1 {
                font-size: 2.2em;
            }
            .policy-container h2 {
                font-size: 1.6em;
            }
            .policy-container h3 {
                font-size: 1.2em;
            }
            .policy-container p, .policy-container ul li {
                font-size: 0.95em;
            }
            .back-button {
                padding: 10px 25px;
                font-size: 1em;
            }
        }

        @media (max-width: 480px) {
            .policy-container {
                padding: 20px;
                margin: 15px auto;
            }
            .policy-container h1 {
                font-size: 1.8em;
            }
            .policy-container h2 {
                font-size: 1.4em;
            }
            .policy-container h3 {
                font-size: 1.1em;
            }
            .policy-container p, .policy-container ul li {
                font-size: 0.9em;
            }
            .back-button {
                padding: 8px 20px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="policy-container">
        <h1>Privacy Policy for QuickBuy</h1>
        <p style="text-align: center; color: var(--cool-gray);"><strong>(Effective Date: May 31, 2025)</strong></p>

        <p>At QuickBuy, your privacy is our priority. This Privacy Policy outlines how we collect, use, store, and protect your personal information when you use our website and services, ensuring compliance with South Africa's **Protection of Personal Information Act (POPIA)**.</p>
        <p>By accessing or using QuickBuy, you agree to the terms outlined in this policy. If you don't agree with these terms, please do not use our services.</p>

        <h2>1. Information We Collect</h2>
        <p>We collect various types of information to provide and improve our services to you.</p>

        <h3>1.1. Personal Information You Provide to Us:</h3>
        <p>This is information that can be used to identify you directly or indirectly. We collect this when you:</p>
        <ul>
            <li><strong>Register for an account:</strong>
                <ul>
                    <li>Username</li>
                    <li>Email address</li>
                    <li>Password (securely stored as a hash)</li>
                    <li>Role (Buyer/Seller)</li>
                </ul>
            </li>
            <li><strong>Create a seller profile or list items:</strong>
                <ul>
                    <li><strong>For individual sellers:</strong> Your full name, contact number, and possibly your physical address if you offer local pickup or need specific delivery arrangements.</li>
                    <li><strong>For business sellers:</strong> Business name, registration number, contact person's name, business contact number, physical address, and bank account details for payout processing.</li>
                </ul>
            </li>
            <li><strong>Place an order as a buyer:</strong>
                <ul>
                    <li>Shipping address (name, street, city, postal code, province, country)</li>
                    <li>Billing address (if different from shipping)</li>
                    <li>Payment information (e.g., credit card details, processed securely via our third-party payment gateway – <strong>QuickBuy does not store your raw credit card numbers</strong>).</li>
                </ul>
            </li>
            <li><strong>Communicate with us or other users:</strong>
                <ul>
                    <li>Messages, inquiries, and customer service interactions (e.g., through our contact forms or chat features).</li>
                </ul>
            </li>
            <li><strong>Participate in surveys, contests, or promotions:</strong>
                <ul>
                    <li>Any personal details you provide to participate, such as demographic information or contact details for prize delivery.</li>
                </ul>
            </li>
        </ul>

        <h3>1.2. Information We Collect Automatically (Usage Data):</h3>
        <p>When you access or use QuickBuy, we may automatically collect certain information about your device and activity. This may include:</p>
        <ul>
            <li><strong>Log Data:</strong> Your Internet Protocol (IP) address, browser type and version, the specific pages you visit on QuickBuy, the time and date of your visit, the time spent on those pages, unique device identifiers, and other diagnostic data.</li>
            <li><strong>Cookies and Tracking Technologies:</strong> We use cookies, web beacons, and similar tracking technologies to enhance your experience. These help us to:
                <ul>
                    <li>Remember your preferences and login sessions.</li>
                    <li>Analyze website traffic and understand user behavior.</li>
                    <li>Personalize your Browse and shopping experience.</li>
                    <li>We use Google Analytics to understand how users interact with our site. Google's ability to use and share information collected by Google Analytics about your visits to QuickBuy is restricted by the Google Analytics Terms of Service and the Google Privacy Policy.</li>
                </ul>
            </li>
            <li><strong>Device Information:</strong> Information about the device you use to access QuickBuy, including its hardware model, operating system version, unique device identifiers, and mobile network information.</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>We use the information we collect for the following primary purposes, ensuring it aligns with POPIA's conditions for lawful processing:</p>
        <ul>
            <li><strong>To Provide and Maintain Our Services:</strong> This includes operating QuickBuy, facilitating transactions between buyers and sellers, managing your user account (including your chosen role as a buyer or seller), and ensuring the platform functions correctly.</li>
            <li><strong>To Process Transactions and Fulfill Orders:</strong> We use your information to process purchases, manage shipping, handle payments securely, and issue invoices.</li>
            <li><strong>To Improve and Personalize Your Experience:</strong> We analyze how you use our services to enhance platform features, offer relevant products and content, and provide a more customized shopping experience.</li>
            <li><strong>To Communicate With You:</strong> This involves sending essential transactional emails (like order confirmations, shipping updates, password resets), responding to your inquiries and customer support requests, and delivering important service-related announcements.</li>
            <li><strong>To Monitor and Analyze Usage:</strong> We track and analyze trends, usage patterns, and activities on QuickBuy to better understand our audience and refine our services.</li>
            <li><strong>To Detect, Prevent, and Address Technical Issues and Fraud:</strong> We use your data for security purposes, to identify and mitigate potential risks, protect against fraudulent activities, and maintain the integrity of our platform.</li>
            <li><strong>To Comply With Legal Obligations:</strong> We process your information as necessary to meet our legal and regulatory requirements, including adherence to POPIA.</li>
            <li><strong>For Marketing and Promotional Purposes (with your consent):</strong> If you opt-in, we may use your information to send you newsletters, special offers, and promotions that we believe might interest you. You can easily opt-out of these communications at any time.</li>
        </ul>

        <h2>3. Disclosure of Your Information</h2>
        <p>We may share your personal information in specific situations and always strictly in accordance with POPIA. We do not sell your personal information to third parties.</p>

        <h3>3.1. With Other Users (Buyers and Sellers):</h3>
        <ul>
            <li><strong>For Transaction Fulfillment:</strong> As part of a transaction, a buyer's shipping address and contact name will be shared with the relevant seller to enable order fulfillment. Similarly, a seller's public profile details (e.g., username, seller ratings) and item listings will be visible to buyers.</li>
            <li><strong>For Communication:</strong> Users may communicate with each other through our platform's secure messaging features.</li>
        </ul>

        <h3>3.2. With Service Providers:</h3>
        <p>We engage trusted third-party companies and individuals ("Service Providers") to help us operate and improve QuickBuy. These providers perform tasks on our behalf, such as:</p>
        <ul>
            <li><strong>Payment Processors:</strong> (e.g., PayFast, Peach Payments, Stripe) to securely process your payments. They act as independent responsible parties for the payment data they collect.</li>
            <li><strong>Shipping and Logistics Partners:</strong> (e.g., The Courier Guy, Aramex) to handle the delivery of your orders.</li>
            <li><strong>Hosting Providers:</strong> (e.g., Your web hosting company) who host our website and databases.</li>
            <li><strong>Analytics Providers:</strong> (e.g., Google Analytics) to monitor and analyze the use of our Service.</li>
            <li><strong>Customer Support Tools:</strong> (e.g., Zendesk, LiveChat) to manage customer inquiries and provide support.</li>
        </ul>
        <p>These third parties are granted access to your Personal Information only to perform their specific services and are contractually obligated to protect your data in line with our privacy standards and POPIA.</p>

        <h3>3.3. For Business Transfers:</h3>
        <p>Should QuickBuy be involved in a merger, acquisition, or asset sale, your Personal Information may be transferred as part of that transaction. We will provide clear notice before your Personal Information is transferred and becomes subject to a different privacy policy.</p>

        <h3>3.4. For Law Enforcement and Legal Compliance:</h3>
        <p>We may disclose your Personal Information if legally required to do so or in response to legitimate requests by public authorities (e.g., a court order or government agency). This may be necessary to:</p>
        <ul>
            <li>Comply with a legal obligation or a court order.</li>
            <li>Protect and defend the rights or property of QuickBuy.</li>
            <li>Prevent or investigate possible wrongdoing related to our Service.</li>
            <li>Protect the personal safety of QuickBuy users or the public.</li>
            <li>Protect against legal liability.</li>
        </ul>

        <h3>3.5. Aggregated or Anonymized Data:</h3>
        <p>We may share aggregated or de-identified information that cannot reasonably be used to identify you with third parties for various purposes, including business analysis, marketing, or research.</p>

        <h2>4. Security of Your Information (POPIA Principle: Security Safeguards)</h2>
        <p>The security of your data is of utmost importance to us. We implement robust technical and organizational measures to protect your Personal Information against unauthorized access, alteration, disclosure, loss, or destruction. These measures include:</p>
        <ul>
            <li><strong>Encryption:</strong> We use <strong>SSL/TLS encryption (HTTPS)</strong> for all data transmitted between your browser and our servers, ensuring secure communication.</li>
            <li><strong>Hashing:</strong> Your passwords are <strong>hashed and salted</strong> using industry-standard cryptographic functions, meaning we store one-way encrypted versions, not your actual passwords.</li>
            <li><strong>Access Controls:</strong> Access to your personal data is strictly limited to authorized personnel who require it to perform their duties.</li>
            <li><strong>Regular Security Audits and Monitoring:</strong> We regularly conduct security audits and monitor our systems for vulnerabilities and potential threats. *(Adjust this if you don't do regular audits).*</li>
            <li><strong>Firewalls and Data Backups:</strong> We employ firewalls to protect our network and perform regular data backups to prevent data loss.</li>
        </ul>
        <p>While we strive to use commercially acceptable means to protect your Personal Information, no method of transmission over the Internet or electronic storage is 100% secure. Therefore, <strong>we cannot guarantee its absolute security.</strong> We also urge you to take steps to protect your own information, such as using strong, unique passwords and keeping your account details confidential.</p>

        <h2>5. Your Rights Under POPIA (Data Subject Rights)</h2>
        <p>In accordance with the Protection of Personal Information Act (POPIA), you, as a data subject, have the following rights regarding your personal information:</p>
        <ul>
            <li><strong>Right to be informed:</strong> You have the right to know what personal information we are collecting about you and how it is being used.</li>
            <li><strong>Right of access:</strong> You can request confirmation of whether we hold your personal information and request a copy of it.</li>
            <li><strong>Right to rectification (correction):</strong> You have the right to request that we correct any inaccurate or incomplete personal information we hold about you.</li>
            <li><strong>Right to erasure (deletion):</strong> You may request the deletion of your personal information, subject to any legal or contractual obligations we may have to retain it.</li>
            <li><strong>Right to object:</strong> You have the right to object to the processing of your personal information in certain circumstances, such as for direct marketing.</li>
            <li><strong>Right to restrict processing:</strong> You can request that we limit the way we use your personal information in certain situations.</li>
            <li><strong>Right to data portability:</strong> Where applicable, you can request that your personal information be transferred to you or another organization in a structured, commonly used, and machine-readable format.</li>
            <li><strong>Right to complain:</strong> You have the right to lodge a complaint with the <strong>Information Regulator of South Africa</strong> if you believe your rights under POPIA have been violated.</li>
        </ul>
        <p>To exercise any of these rights, please contact us using the details provided in the "Contact Us" section below. We will respond to your request within the timeframes prescribed by POPIA.</p>

        <h2>6. Retention of Your Information</h2>
        <p>We will retain your Personal Information only for as long as is necessary for the purposes set out in this Privacy Policy, and to comply with our legal obligations (e.g., if we are required to retain your data to comply with applicable laws, resolve disputes, and enforce our legal agreements and policies).</p>

        <h2>7. Links to Other Websites</h2>
        <p>Our Service may contain links to other websites that are not operated by us. If you click on a third-party link, you will be directed to that third party's site. We strongly advise you to review the Privacy Policy of every site you visit. We have no control over and assume no responsibility for the content, privacy policies, or practices of any third-party sites or services.</p>

        <h2>8. Children's Privacy</h2>
        <p>QuickBuy is not intended for use by individuals under the age of <strong>18</strong> ("Children"). We do not knowingly collect personally identifiable information from anyone under the age of <strong>18</strong>. If you are a parent or guardian and you are aware that your child has provided us with Personal Information, please contact us. If we become aware that we have collected Personal Information from children without verification of parental consent, we take steps to remove that information from our servers.</p>

        <h2>9. Changes to This Privacy Policy</h2>
        <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page. We will also update the "Effective Date" at the top of this Privacy Policy. You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p>

        <h2>10. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, your rights under POPIA, or our data practices, please contact us:</p>
        <ul>
            <li><strong>By Email:</strong>support@quickbuy.co.za</li>
            <li><strong>By Mail:</strong>
                <ul>
                    <li>QuickBuy</li>
                    <li>31 Smuts street</li>
                    <li>Malmesbury, 7300</li>
                    <li>Western Cape, South Africa</li>
                </ul>
            </li>
        </ul>
        <p><strong>Information Regulator of South Africa Contact Details (for complaints):</strong></p>
        <ul>
            <li><strong>Website:</strong> <a href="https://www.justice.gov.za/inforeg/" target="_blank">www.justice.gov.za/inforeg/</a></li>
            <li><strong>Email:</strong> <a href="mailto:complaints.IR@justice.gov.za">complaints.IR@justice.gov.za</a></li>
            <li><strong>Tel:</strong> 010 023 5200</li>
        </ul>
         </div>
</body>
</html>