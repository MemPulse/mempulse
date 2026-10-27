<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="This email address is already associated with a MemPulse account.">
    <title>MemPulse | Email Already Registered</title>

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
            --ink: #131b2e;
            --muted: #464555;
            --primary: #3525cd;
            --primary-hover: #4b41e1;
            --line: rgba(199, 196, 216, .48);
            --shadow-card: 0 20px 50px rgba(53, 37, 205, .10);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            display: grid;
            min-height: 100vh;
            place-items: center;
            margin: 0;
            padding: 1.5rem;
            background: var(--surface);
            color: var(--ink);
            font: 400 1rem/1.6 "Inter", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .material-symbols-outlined {
            font-family: "Material Symbols Outlined";
            font-weight: normal;
            font-style: normal;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            white-space: nowrap;
            font-feature-settings: "liga";
            -webkit-font-smoothing: antialiased;
        }

        .notice-card {
            width: min(100%, 30rem);
            padding: clamp(2rem, 7vw, 3rem);
            border: 1px solid var(--line);
            border-radius: 1.25rem;
            background: var(--surface-card);
            box-shadow: var(--shadow-card);
            text-align: center;
        }

        .notice-icon {
            display: grid;
            width: 4rem;
            height: 4rem;
            place-items: center;
            margin: 0 auto 1.5rem;
            border-radius: 1.1rem;
            background: var(--surface-container);
            color: var(--primary);
            font-size: 2rem;
        }

        .eyebrow {
            margin: 0 0 .5rem;
            color: var(--primary);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font: 800 clamp(1.5rem, 5vw, 1.8rem)/1.2 "Plus Jakarta Sans", sans-serif;
            letter-spacing: -.04em;
        }

        .message {
            margin: 1rem 0 0;
            color: var(--muted);
            font-size: .95rem;
        }

        .account-note {
            margin: 1.25rem 0 1.75rem;
            padding: 1rem;
            border: 1px solid var(--line);
            border-radius: .75rem;
            background: var(--surface);
            color: var(--muted);
            font-size: .85rem;
            text-align: left;
        }

        .actions {
            display: grid;
            gap: .75rem;
        }

        .button {
            display: inline-flex;
            min-height: 3rem;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .7rem 1.25rem;
            border: 1px solid transparent;
            border-radius: 999px;
            background: var(--primary);
            color: #fff;
            font: 700 .95rem/1.2 "Plus Jakarta Sans", sans-serif;
            text-decoration: none;
            transition: background .2s, transform .2s;
        }

        .button:hover {
            transform: translateY(-1px);
            background: var(--primary-hover);
        }

        .text-link {
            color: var(--primary);
            font-size: .9rem;
            font-weight: 600;
            text-decoration: none;
        }

        .text-link:hover { text-decoration: underline; }
        a:focus-visible { outline: 3px solid #8b80ff; outline-offset: 3px; }
    </style>
</head>
<body>
    <main class="notice-card">
        <span class="material-symbols-outlined notice-icon" aria-hidden="true">person_check</span>
        <p class="eyebrow">Registration not completed</p>
        <h1>Email already registered</h1>
        <p class="message">
            There is already a MemPulse account associated with this email address. Sign in if you already have access, or use a different email to create a new account.
        </p>
        <p class="account-note">
            If the existing account is waiting for approval or has been suspended, registering again will not change its status. Please contact an administrator for help with that account.
        </p>
        <div class="actions">
            <a class="button" href="login.php#login">
                <span class="material-symbols-outlined" aria-hidden="true">login</span>
                Go to login
            </a>
            <a class="text-link" href="login.php#signup">Use a different email</a>
        </div>
    </main>
</body>
</html>
