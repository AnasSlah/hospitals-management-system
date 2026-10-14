<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تواصل معنا</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2c3e50;
            --whatsapp: #25d366;
            --bg: #f4f7f6;
            --white: #ffffff;
        }

        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--bg);
            font-family: 'Tajawal', sans-serif;
        }

        .contact-container {
            width: 95%;
            max-width: 480px;
        }

        .contact-card {
            background: var(--white);
            padding: 40px 25px;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border-top: 8px solid var(--primary);
            transition: transform 0.3s ease;
        }

        .contact-card:hover {
            transform: translateY(-5px);
        }

        .icon-circle {
            width: 70px;
            height: 70px;
            background: #f0f7ff;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 30px;
            margin: 0 auto 20px;
        }

        h1 {
            font-size: 1.7rem;
            color: var(--primary-dark);
            margin-bottom: 10px;
        }

        .description {
            color: #7f8c8d;
            font-size: 0.95rem;
            margin-bottom: 30px;
        }

        .info-grid {
            display: grid;
            gap: 12px;
            margin-bottom: 25px;
        }

        .info-box {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8fafb;
            border-radius: 15px;
            text-decoration: none;
            color: var(--primary-dark);
            transition: 0.2s;
            border: 1px solid transparent;
        }

        .info-box:hover {
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        }

        .info-box i {
            font-size: 18px;
            color: var(--primary);
            width: 20px;
            text-align: center;
        }

        .info-content {
            text-align: right;
        }

        .info-label {
            display: block;
            font-size: 0.75rem;
            color: #95a5a6;
            margin-bottom: 2px;
        }

        .info-value {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .btn-whatsapp {
            background-color: var(--whatsapp);
            color: white;
            grid-column: span 2;
        }

        .btn-whatsapp:hover {
            background-color: #1cb157;
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        }

        .social-row {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f1f3f5;
            color: var(--primary-dark);
            text-decoration: none;
            transition: 0.3s;
        }

        .social-icon:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }
    </style>
</head>

<body>

    <div class="contact-container">
        <div class="contact-card">
            <div class="icon-circle">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            
            <h1>تواصل معنا</h1>
            <p class="description">يسعدنا استقبال استفساراتكم وملاحظاتكم في أي وقت.</p>

            <div class="info-grid">
                <a href="mailto:Anasahmad2039@gmail.com" class="info-box">
                    <i class="fa-solid fa-envelope"></i>
                    <div class="info-content">
                        <span class="info-label">البريد الإلكتروني</span>
                        <span class="info-value">tem@gmail.com</span>
                    </div>
                </a>

                <div class="info-box">
                    <i class="fa-solid fa-phone"></i>
                    <div class="info-content">
                        <span class="info-label">رقم الهاتف</span>
                        <span class="info-value" dir="ltr">+249 9xxxxxxxx</span>
                    </div>
                </div>

                <div class="info-box">
                    <i class="fa-solid fa-location-dot"></i>
                    <div class="info-content">
                        <span class="info-label">العنوان</span>
                        <span class="info-value">الخرطوم، السودان</span>
                    </div>
                </div>
            </div>

            <a href="https://wa.me/2490000000" class="btn btn-whatsapp" target="_blank">
                <i class="fab fa-whatsapp"></i>
                تواصل عبر واتساب
            </a>

            <div class="social-row">
                <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social-icon"><i class="fa-brands fa-x-twitter"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>

</body>
</html>
