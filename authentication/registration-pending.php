<?php
session_start();
if (empty($_SESSION['demo_pending_user_id'])) {
    header('Location: login.php#login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemPulse | Registration Pending</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: light;
            --surface: #faf8ff;
            --surface-card: #ffffff;
            --surface-container: #eaedff;
            --surface-pulse: #d2d8ff;
            --ink: #131b2e;
            --muted: #464555;
            --primary: #3525cd;
            --primary-deep: #1e00a9;
            --secondary: #4b41e1;
            --radius-lg: 1rem;
            --radius-xl: 1.25rem;
            --shadow-card: 0 20px 50px rgba(53, 37, 205, .10);
            --line: rgba(199, 196, 216, .48);
        }

        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--surface);
            color: var(--ink);
            font-family: "Inter", sans-serif;
            font-size: 1rem;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }

        .material-symbols-outlined {
            font-family: "Material Symbols Outlined";
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            font-feature-settings: "liga";
            -webkit-font-feature-settings: "liga";
            -webkit-font-smoothing: antialiased;
        }

        .auth-container {
            width: 100%;
            max-width: 480px;
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            border: 1px solid var(--line);
            padding: 3rem 2.5rem;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .brand-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        @keyframes pulse-bg {
            0%, 100% { background-color: var(--surface-container); }
            50% { background-color: var(--surface-pulse); }
        }

        .logo-icon {
            display: grid;
            width: 3.5rem;
            height: 3.5rem;
            place-items: center;
            border-radius: 1rem;
            background: var(--surface-container);
            color: rgb(53, 37, 205);
            font-size: 2rem;
            margin-bottom: 1.5rem;
            animation: pulse-bg 2.5s infinite ease-in-out;
        }

        .eyebrow {
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0 0 0.5rem 0;
        }

        .brand-header h1 {
            margin: 0;
            font-family: "Plus Jakarta Sans", sans-serif;
            font-weight: 800;
            font-size: 1.8rem;
            letter-spacing: -.04em;
            line-height: 1.2;
        }

        .status-message {
            color: var(--muted);
            font-size: 0.95rem;
            margin: 0 0 2rem 0;
        }

        .approval-note {
            margin-top: 1.5rem;
            color: #8c92a4;
            font-size: 0.8rem;
            line-height: 1.5;
            padding: 1rem;
            background: var(--surface);
            border-radius: 0.5rem;
            border: 1px dashed var(--line);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            margin-top: 2rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <main class="auth-container">
        <div class="brand-header">
            <span class="material-symbols-outlined logo-icon" aria-hidden="true">neurology</span>
            <p class="eyebrow">Registration complete</p>
            <h1>Pending Approval</h1>
        </div>

        <p class="status-message">
            Your account has been registered and is waiting for administrator approval. You will be redirected to log in as soon as it is approved.
        </p>

        <p id="approval-status" class="approval-note" role="status" aria-live="polite">
            Checking approval status…
        </p>
        
        <a href="login.php#login" class="back-link">
            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
            Go back to login
        </a>
    </main>

    <script>
        (function () {
            var statusMessage = document.getElementById('approval-status');

            async function checkApprovalStatus() {
                try {
                    var response = await fetch('registration-status.php', {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store'
                    });
                    if (!response.ok) {
                        throw new Error('Approval status request failed: ' + response.status);
                    }

                    var result = await response.json();
                    if (result.status === 'approved') {
                        window.location.replace('login.php?registered=approved#login');
                        return;
                    }
                    if (result.status === 'rejected') {
                        window.location.replace('login.php?error=account_rejected#login');
                        return;
                    }
                    if (result.status !== 'pending') {
                        throw new Error('Unexpected approval status response.');
                    }

                    statusMessage.textContent = 'Your account is still awaiting review. This page will redirect automatically once it is approved.';
                } catch (error) {
                    console.error('Unable to check registration approval status.', error);
                    statusMessage.textContent = 'We could not check your approval status right now. We will keep trying automatically.';
                }

                window.setTimeout(checkApprovalStatus, 5000);
            }

            checkApprovalStatus();
        })();
    </script>
</body>
</html>