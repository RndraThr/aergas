<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - AERGAS System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            overflow: hidden;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            height: 100vh;
            position: relative;
        }

        /* LEFT PANEL - VISUAL ASSETS */
        .left-panel {
            flex: 1.2;
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 50%, #1a202c 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="8" height="8" patternUnits="userSpaceOnUse"><path d="M 8 0 L 0 0 0 8" fill="none" stroke="rgba(148,163,184,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            animation: gridFloat 25s ease-in-out infinite;
        }

        @keyframes gridFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .watermark-logo {
            position: absolute;
            inset: 0;
            background: url('{{ asset('assets/CGP.png') }}') no-repeat center;
            background-size: 65% auto;
            opacity: 0.12;
            filter: grayscale(100%) brightness(1.3);
            pointer-events: none;
            z-index: 1;
        }

        .visual-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 500px;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .brand-logos {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 40px;
            margin-bottom: 50px;
            position: relative;
            z-index: 3;
        }

        .brand-logo {
            transition: all 0.3s ease;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
        }

        .brand-logo:hover {
            transform: translateY(-5px) scale(1.05);
            filter: drop-shadow(0 8px 20px rgba(0, 0, 0, 0.4));
        }

        .cgp-logo {
            width: 90px;
            height: auto;
        }

        .aergas-logo {
            width: 160px;
            height: auto;
        }

        .system-info {
            color: #e2e8f0;
            margin-bottom: 40px;
        }

        .system-title {
            font-size: 36px;
            font-weight: 900;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #ff8c5a, #ff6b35, #ff4500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            letter-spacing: 1px;
            color: #ff8c5a;
        }

        .system-title::after {
            content: '🔥';
            position: absolute;
            top: -10px;
            right: -25px;
            font-size: 20px;
            animation: flame 2s ease-in-out infinite alternate;
        }

        @keyframes flame {
            0% { transform: rotate(-5deg) scale(1); opacity: 0.8; }
            100% { transform: rotate(5deg) scale(1.1); opacity: 1; }
        }

        .system-subtitle {
            font-size: 18px;
            font-weight: 500;
            color: #f1f5f9;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .system-description {
            font-size: 14px;
            color: #e2e8f0;
            line-height: 1.6;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            font-weight: 400;
        }

        /* VISUAL ELEMENTS - PIPES & GAS ASSETS */
        .gas-assets {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .gas-asset {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .gas-asset:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 107, 53, 0.5);
            transform: translateY(-3px);
        }

        .gas-asset i {
            font-size: 28px;
            color: #ff6b35;
            margin-bottom: 12px;
            display: block;
        }

        .gas-asset span {
            font-size: 12px;
            color: #cbd5e1;
            font-weight: 500;
        }

        /* FLOATING ANIMATION ELEMENTS */
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }

        .pipe-element {
            position: absolute;
            background: linear-gradient(90deg, #ff6b35, #e55a2b);
            border-radius: 10px;
            opacity: 0.6;
            animation: float 8s ease-in-out infinite;
        }

        .pipe-element:nth-child(1) {
            width: 100px;
            height: 6px;
            top: 20%;
            left: -50px;
            animation-delay: 0s;
            animation-duration: 12s;
        }

        .pipe-element:nth-child(2) {
            width: 80px;
            height: 4px;
            top: 60%;
            left: -40px;
            animation-delay: 4s;
            animation-duration: 10s;
        }

        .pipe-element:nth-child(3) {
            width: 120px;
            height: 8px;
            top: 80%;
            left: -60px;
            animation-delay: 8s;
            animation-duration: 14s;
        }

        @keyframes float {
            0% {
                transform: translateX(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.3;
            }
            100% {
                transform: translateX(calc(100vw + 200px)) rotate(360deg);
                opacity: 0;
            }
        }

        /* RIGHT PANEL - LOGIN FORM */
        .right-panel {
            flex: 0.8;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }


        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 32px 64px rgba(0, 0, 0, 0.15);
            padding: 50px 40px;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 2;
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header-logos {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
            margin-bottom: 25px;
        }

        .header-logo {
            transition: all 0.3s ease;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.2));
        }

        .header-logo:hover {
            transform: translateY(-3px) scale(1.05);
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
        }

        .header-cgp-logo {
            width: 100px;
            height: auto;
        }

        .header-aergas-logo {
            width: 120px;
            height: auto;
        }

        .mobile-subtitle {
            display: none;
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .login-subtitle {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        .login-form {
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px 16px 52px;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            color: #1e293b;
            font-weight: 500;
        }

        .form-input:focus {
            outline: none;
            border-color: #ff6b35;
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.1);
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .form-input:focus + .input-icon {
            color: #ff6b35;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s ease;
            padding: 4px;
        }

        .password-toggle:hover {
            color: #ff6b35;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #ff6b35;
        }

        .forgot-password {
            color: #ff6b35;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #e55a2b;
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ff6b35 0%, #e55a2b 50%, #d45426 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(255, 107, 53, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .login-btn .btn-text {
            transition: opacity 0.3s ease;
        }

        .login-btn.loading .btn-text {
            opacity: 0;
        }

        .spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .login-btn.loading .spinner {
            opacity: 1;
        }

        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fecaca;
            animation: slideInDown 0.3s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .footer {
            text-align: center;
            margin-top: 32px;
            color: #94a3b8;
            font-size: 12px;
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 1024px) {
            .login-wrapper {
                flex-direction: column;
            }

            .left-panel {
                flex: none;
                height: 40vh;
                padding: 20px;
            }

            .visual-content {
                max-width: none;
                padding: 20px;
                height: auto;
                justify-content: flex-start;
            }

            .brand-logos {
                gap: 25px;
                margin-bottom: 25px;
            }

            .cgp-logo {
                width: 70px;
            }

            .aergas-logo {
                width: 120px;
            }

            .system-title {
                font-size: 26px;
                margin-bottom: 8px;
            }

            .system-title::after {
                font-size: 16px;
                right: -20px;
            }

            .system-subtitle {
                font-size: 16px;
                margin-bottom: 6px;
            }

            .system-description {
                font-size: 12px;
                margin-bottom: 20px;
            }

            .gas-assets {
                grid-template-columns: repeat(6, 1fr);
                gap: 10px;
                margin-top: 15px;
            }

            .gas-asset {
                padding: 12px 8px;
            }

            .gas-asset i {
                font-size: 18px;
                margin-bottom: 6px;
            }

            .gas-asset span {
                font-size: 9px;
                line-height: 1.2;
            }

            .right-panel {
                flex: none;
                height: 60vh;
            }

            .login-container {
                padding: 30px 25px;
                margin: 20px;
                max-width: none;
            }
        }

        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: row;
            }

            .left-panel {
                display: none;
            }

            .right-panel {
                flex: 1;
                height: 100vh;
                background: linear-gradient(135deg, #2d3748 0%, #4a5568 50%, #1a202c 100%);
                position: relative;
            }


            .right-panel::after {
                content: '';
                position: absolute;
                inset: 0;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="8" height="8" patternUnits="userSpaceOnUse"><path d="M 8 0 L 0 0 0 8" fill="none" stroke="rgba(148,163,184,0.15)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
                pointer-events: none;
                z-index: 1;
                animation: gridFloat 25s ease-in-out infinite;
            }

            /* Mobile Background Elements */
            .mobile-bg-elements {
                position: absolute;
                inset: 0;
                pointer-events: none;
                z-index: 1;
                overflow: hidden;
            }

            .floating-orb {
                position: absolute;
                border-radius: 50%;
                background: linear-gradient(135deg, rgba(255, 107, 53, 0.15), rgba(229, 90, 43, 0.1));
                animation: floatOrb 20s ease-in-out infinite;
                filter: blur(2px);
            }

            .floating-orb:nth-child(1) {
                width: 150px;
                height: 150px;
                top: 10%;
                left: -75px;
                animation-delay: 0s;
                animation-duration: 25s;
            }

            .floating-orb:nth-child(2) {
                width: 120px;
                height: 120px;
                top: 60%;
                right: -60px;
                animation-delay: 8s;
                animation-duration: 30s;
            }

            .floating-orb:nth-child(3) {
                width: 100px;
                height: 100px;
                bottom: 20%;
                left: -50px;
                animation-delay: 15s;
                animation-duration: 22s;
            }

            .floating-orb:nth-child(4) {
                width: 80px;
                height: 80px;
                top: 5%;
                right: 20%;
                animation-delay: 5s;
                animation-duration: 18s;
            }

            .floating-orb:nth-child(5) {
                width: 90px;
                height: 90px;
                bottom: 5%;
                right: 15%;
                animation-delay: 12s;
                animation-duration: 26s;
            }

            .floating-orb:nth-child(6) {
                width: 70px;
                height: 70px;
                top: 15%;
                left: 5%;
                animation-delay: 20s;
                animation-duration: 20s;
            }

            @keyframes floatOrb {
                0%, 100% {
                    transform: translateY(0px) translateX(0px) scale(1);
                    opacity: 0.3;
                }
                25% {
                    transform: translateY(-30px) translateX(20px) scale(1.1);
                    opacity: 0.5;
                }
                50% {
                    transform: translateY(-15px) translateX(-15px) scale(0.9);
                    opacity: 0.4;
                }
                75% {
                    transform: translateY(-25px) translateX(10px) scale(1.05);
                    opacity: 0.6;
                }
            }

            /* Geometric shapes */
            .mobile-shapes {
                position: absolute;
                inset: 0;
                pointer-events: none;
                z-index: 1;
            }

            .mobile-shape {
                position: absolute;
                background: rgba(255, 255, 255, 0.08);
                transform-origin: center;
                animation: rotateShape 30s linear infinite;
            }

            .mobile-shape.diamond {
                width: 40px;
                height: 40px;
                top: 8%;
                right: 5%;
                transform: rotate(45deg);
                border-radius: 8px;
            }

            .mobile-shape.triangle {
                width: 0;
                height: 0;
                border-left: 25px solid transparent;
                border-right: 25px solid transparent;
                border-bottom: 40px solid rgba(255, 255, 255, 0.06);
                bottom: 8%;
                right: 8%;
                background: none;
            }

            .mobile-shape.hexagon {
                width: 50px;
                height: 50px;
                background: rgba(255, 255, 255, 0.05);
                position: absolute;
                top: 5%;
                left: 3%;
                clip-path: polygon(30% 0%, 70% 0%, 100% 50%, 70% 100%, 30% 100%, 0% 50%);
            }

            .mobile-shape.circle {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.06);
                top: 30%;
                right: 3%;
                animation-duration: 25s;
            }

            .mobile-shape.square {
                width: 35px;
                height: 35px;
                background: rgba(255, 255, 255, 0.04);
                bottom: 15%;
                left: 8%;
                border-radius: 6px;
                animation-duration: 20s;
            }

            .mobile-shape.star {
                width: 40px;
                height: 40px;
                background: rgba(255, 255, 255, 0.07);
                top: 40%;
                left: 2%;
                clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
                animation-duration: 35s;
            }

            /* Additional floating lines for tech feel */
            .tech-lines {
                position: absolute;
                inset: 0;
                pointer-events: none;
                z-index: 1;
            }

            .tech-line {
                position: absolute;
                background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.3), transparent);
                height: 1px;
                animation: moveLine 8s ease-in-out infinite;
            }

            .tech-line:nth-child(1) {
                width: 100px;
                top: 12%;
                left: -50px;
                animation-delay: 0s;
            }

            .tech-line:nth-child(2) {
                width: 150px;
                bottom: 12%;
                right: -75px;
                animation-delay: 3s;
                animation-duration: 12s;
            }

            .tech-line:nth-child(3) {
                width: 80px;
                top: 50%;
                left: -40px;
                animation-delay: 6s;
                animation-duration: 10s;
            }

            @keyframes moveLine {
                0% {
                    transform: translateX(0) scaleX(0);
                    opacity: 0;
                }
                20% {
                    transform: translateX(0) scaleX(1);
                    opacity: 1;
                }
                80% {
                    transform: translateX(100vw) scaleX(1);
                    opacity: 0.8;
                }
                100% {
                    transform: translateX(100vw) scaleX(0);
                    opacity: 0;
                }
            }

            @keyframes rotateShape {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }

            .login-container {
                background: rgba(255, 255, 255, 0.95);
                padding: 30px 25px;
                margin: 20px;
                max-width: 400px;
                position: relative;
                z-index: 2;
            }

            .login-container::before {
                content: '';
                position: absolute;
                inset: 0;
                background: url('{{ asset('assets/CGP.png') }}') no-repeat center bottom 20%;
                background-size: 80% auto;
                opacity: 0.06;
                filter: grayscale(100%) brightness(0%) contrast(100%);
                pointer-events: none;
                z-index: 0;
                border-radius: 24px;
            }

            .login-container > * {
                position: relative;
                z-index: 1;
            }

            .header-logos {
                display: flex !important;
                flex-direction: row !important;
                align-items: center;
                justify-content: center;
                gap: 20px;
                margin-bottom: 20px;
            }

            .header-cgp-logo {
                width: 60px;
                flex-shrink: 0;
            }

            .header-aergas-logo {
                width: 100px;
                flex-shrink: 0;
            }

            .mobile-subtitle {
                display: block;
                font-size: 14px;
                color: #3b82f6;
                font-weight: 500;
                margin-bottom: 20px;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            }

            .login-title {
                font-size: 24px;
            }

            .login-subtitle {
                font-size: 13px;
            }

            .form-input {
                padding: 14px 18px 14px 48px;
                font-size: 15px;
            }

            .input-icon {
                left: 16px;
                font-size: 14px;
            }

            .password-toggle {
                right: 14px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 15px;
                padding: 25px 20px;
                max-width: none;
            }

            .login-container::before {
                background: url('{{ asset('assets/CGP.png') }}') no-repeat center bottom 20%;
                background-size: 70% auto;
                opacity: 0.04;
                filter: grayscale(100%) brightness(0%) contrast(100%);
            }

            .header-logos {
                display: flex !important;
                flex-direction: row !important;
                align-items: center;
                justify-content: center;
                gap: 15px;
                margin-bottom: 20px;
            }

            .header-cgp-logo {
                width: 100px;
                flex-shrink: 0;
            }

            .header-aergas-logo {
                width: 120px;
                flex-shrink: 0;
            }

            .mobile-subtitle {
                font-size: 12px;
                margin-bottom: 18px;
            }

            .login-title {
                font-size: 22px;
            }

            .login-subtitle {
                font-size: 12px;
            }

            .form-input {
                padding: 12px 16px 12px 44px;
                font-size: 14px;
            }

            .input-icon {
                left: 14px;
                font-size: 13px;
            }

            .password-toggle {
                right: 12px;
                font-size: 13px;
            }

        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- LEFT PANEL - VISUAL ASSETS -->
        <div class="left-panel">
            <div class="watermark-logo"></div>

            <div class="floating-elements">
                <div class="pipe-element"></div>
                <div class="pipe-element"></div>
                <div class="pipe-element"></div>
            </div>

            <div class="visual-content">

                <div class="system-info">
                    <h1 class="system-title">AERGAS SYSTEM</h1>
                    <p class="system-subtitle">AI-Enabled Reporting for Gas Infrastructure</p>
                    <p class="system-description">Comprehensive gas installation management system with intelligent photo validation, real-time monitoring, and automated reporting</p>
                </div>

                <div class="gas-assets">
                    <div class="gas-asset">
                        <i class="fas fa-cog"></i>
                        <span>Pipeline<br>Management</span>
                    </div>
                    <div class="gas-asset">
                        <i class="fas fa-wrench"></i>
                        <span>Service<br>Connection</span>
                    </div>
                    <div class="gas-asset">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Regulator<br>Installation</span>
                    </div>
                    <div class="gas-asset">
                        <i class="fas fa-fire"></i>
                        <span>Gas-In<br>Testing</span>
                    </div>
                    <div class="gas-asset">
                        <i class="fas fa-camera"></i>
                        <span>AI Photo<br>Validation</span>
                    </div>
                    <div class="gas-asset">
                        <i class="fas fa-chart-line"></i>
                        <span>Real-time<br>Reports</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL - LOGIN FORM -->
        <div class="right-panel">
            <div class="mobile-bg-elements">
                <div class="floating-orb"></div>
                <div class="floating-orb"></div>
                <div class="floating-orb"></div>
                <div class="floating-orb"></div>
                <div class="floating-orb"></div>
                <div class="floating-orb"></div>
            </div>
            <div class="mobile-shapes">
                <div class="mobile-shape diamond"></div>
                <div class="mobile-shape triangle"></div>
                <div class="mobile-shape hexagon"></div>
                <div class="mobile-shape circle"></div>
                <div class="mobile-shape square"></div>
                <div class="mobile-shape star"></div>
            </div>
            <div class="tech-lines">
                <div class="tech-line"></div>
                <div class="tech-line"></div>
                <div class="tech-line"></div>
            </div>
            <div class="login-container">
                <div class="login-header">
                    <div class="header-logos">
                        <img src="{{ asset('assets/CGP5.png') }}" alt="CGP5 Logo" class="header-logo header-cgp-logo">
                        <img src="{{ asset('assets/AERGAS_PNG.png') }}" alt="AERGAS Logo" class="header-logo header-aergas-logo">
                    </div>
                    <p class="mobile-subtitle">AI-Enabled Reporting for Gas Infrastructure</p>
                    <h2 class="login-title">Welcome Back</h2>
                    <p class="login-subtitle">Sign in to your AERGAS account</p>
                </div>

                <form class="login-form" id="loginForm" method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <div id="errorMessage" class="error-message" style="display: none;"></div>

                    @if ($errors->any())
                        <div class="error-message">
                            @foreach ($errors->all() as $error)
                                {{ $error }}
                            @endforeach
                        </div>
                    @endif

                    <div class="form-group">
                        <input type="text" id="username" name="username" class="form-input" placeholder="Username" value="{{ old('username') }}" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>

                    <div class="form-group">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Password" required>
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <div class="remember-forgot">
                        <label class="remember-me">
                            <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="login-btn" id="loginBtn">
                        <span class="btn-text">Sign In</span>
                        <div class="spinner"></div>
                    </button>
                </form>

                <div class="footer">
                    <p>&copy; 2024 AERGAS System. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        const toggleIcon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);

            if (type === 'password') {
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        });

        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const errorMessage = document.getElementById('errorMessage');

        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            loginBtn.classList.add('loading');
            hideError();

            const formData = new FormData(loginForm);
            const loginData = {
                username: formData.get('username'),
                password: formData.get('password'),
                remember: formData.get('remember') ? true : false
            };

            try {
                const response = await fetch('{{ route("login.post") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(loginData)
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess('Login successful! Redirecting...');

                    setTimeout(() => {
                        window.location.href = result.redirect || '/dashboard';
                    }, 1500);
                } else {
                    showError(result.message || 'Login failed. Please try again.');
                }
            } catch (error) {
                console.error('Login error:', error);
                showError('Network error. Please check your connection and try again.');
            } finally {
                loginBtn.classList.remove('loading');
            }
        });

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function hideError() {
            errorMessage.style.display = 'none';
        }

        function showSuccess(message) {
            errorMessage.style.background = '#c6f6d5';
            errorMessage.style.color = '#276749';
            errorMessage.style.borderLeftColor = '#38a169';
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.classList.add('focused');
            });

            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentNode.classList.remove('focused');
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                loginForm.dispatchEvent(new Event('submit'));
            }
        });

        window.addEventListener('load', function() {
            fetch('{{ route("auth.check") }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.authenticated && result.user) {
                    const roles = result.user.roles || [];
                    let redirectUrl = '/dashboard';

                    if (roles.includes('super_admin') || roles.includes('admin')) {
                        redirectUrl = '/dashboard';
                    } else if (roles.includes('jalur')) {
                        redirectUrl = '/jalur';
                    } else if (roles.includes('sk')) {
                        redirectUrl = '/sk/create';
                    } else if (roles.includes('sr')) {
                        redirectUrl = '/sr/create';
                    } else if (roles.includes('gas_in')) {
                        redirectUrl = '/gas-in/create';
                    } else if (roles.includes('cgp')) {
                        redirectUrl = '/approvals/cgp';
                    } else if (roles.includes('tracer')) {
                        redirectUrl = '/dashboard';
                    }
                    window.location.href = redirectUrl;
                }
            })
            .catch(() => {

            });
        });
        </script>
</body>
</html>
