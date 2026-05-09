<!DOCTYPE html>
<html lang="uk">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>ProHour</title>

    <link rel="stylesheet" href="css/new-style.css">

</head>

<body>

    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <header>

       <div class="logo">

            <img src="img/logo.png" alt="ProHour">

        </div>


    <nav>

        <a href="#">
            Можливості
        </a>

        <a href="#">
            Для кого
        </a>

        <a href="#">
            Як це працює
        </a>

        <a href="#">
            Аналітика
        </a>

    </nav>


        <div class="lang-switch">

            <button class="lang-btn active">

                <img src="img/ua.svg" alt="UA">

                UA

            </button>


            <button class="lang-btn">

                <img src="img/gb.svg" alt="EN">

                EN

            </button>

        </div>

        <div class="user-panel">

            <div class="user-status">
                Online
            </div>

            <div class="user-avatar">
                D
            </div>

        </div>

    </header>


    <main>

        <section class="hero">

            <div class="hero-content">


                <h1>

                    Контролюйте час.<br>
                    <span>
                        Організовуйте роботу.<br>
                    </span>

                    Приймайте рішення.<br>

                </h1>

                <p>

                    Задачі, проєкти, облік часу
                    та аналітика —
                    в одному просторі
                    для компаній,
                    команд та фрілансерів.

                </p>

                <div class="hero-buttons">

                    <a href="#" class="primary-btn">
                        Відкрити задачі
                    </a>

                    <a href="#" class="secondary-btn">
                        Дізнатись більше
                    </a>

                </div>

            </div>

        </section>



        <section class="features reveal">

            <div class="section-header">

                <div class="section-badge">
                    Можливості системи
                </div>

                <h2>
                    Все необхідне
                    для контролю часу
                </h2>

            </div>


            <div class="features-grid">

                <div class="feature-card">

                    <div class="feature-icon">
                        ⏱
                    </div>

                    <h3>
                        Облік часу
                    </h3>

                    <p>
                        Відстежуйте час
                        виконання задач
                        у реальному часі.
                    </p>

                </div>


                <div class="feature-card">

                    <div class="feature-icon">
                        📁
                    </div>

                    <h3>
                        Проєкти
                    </h3>

                    <p>
                        Групуйте задачі
                        по клієнтах
                        та проєктах.
                    </p>

                </div>


                <div class="feature-card">

                    <div class="feature-icon">
                        ✅
                    </div>

                    <h3>
                        Задачі
                    </h3>

                    <p>
                        Контролюйте
                        статуси задач
                        та дедлайни.
                    </p>

                </div>


                <div class="feature-card">

                    <div class="feature-icon">
                        📊
                    </div>

                    <h3>
                        Аналітика
                    </h3>

                    <p>
                        Аналізуйте
                        продуктивність
                        та навантаження.
                    </p>

                </div>

            </div>

        </section>



        <section class="workflow reveal">

            <div class="section-header">

                <div class="section-badge">
                    Workflow
                </div>

                <h2>
                    Простий процес роботи
                </h2>

            </div>


            <div class="workflow-line">

                <div class="workflow-item">

                    <div class="workflow-number">
                        01
                    </div>

                    <h3>
                        Клієнт
                    </h3>

                </div>


                <div class="workflow-item">

                    <div class="workflow-number">
                        02
                    </div>

                    <h3>
                        Проєкт
                    </h3>

                </div>


                <div class="workflow-item">

                    <div class="workflow-number">
                        03
                    </div>

                    <h3>
                        Задача
                    </h3>

                </div>


                <div class="workflow-item">

                    <div class="workflow-number">
                        04
                    </div>

                    <h3>
                        Час
                    </h3>

                </div>

            </div>

        </section>



        <section class="stats reveal">

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-value">
                        124h
                    </div>

                    <div class="stat-label">
                        Зафіксовано часу
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-value">
                        12
                    </div>

                    <div class="stat-label">
                        Активних задач
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-value">
                        8
                    </div>

                    <div class="stat-label">
                        Активних проєктів
                    </div>

                </div>

            </div>

        </section>



        <section class="feedback reveal">

            <div class="feedback-card">

                <div class="section-badge">
                    Зворотний зв’язок
                </div>

                <h2>
                    Маєте питання?
                </h2>

                <p>
                    Напишіть нам повідомлення
                    або залиште свій email.
                </p>


                <form>

                    <input
                        type="email"
                        placeholder="Ваш email"
                    >

                    <textarea
                        placeholder="Ваше повідомлення"
                    ></textarea>

                    <button>
                        Надіслати
                    </button>

                </form>

            </div>

        </section>

    </main>



    <footer>

        <div>
            ProHour © 2026
        </div>

        <div>
            Smart Time Tracking System
        </div>

    </footer>



    <script>

        const reveals = document.querySelectorAll('.reveal');

        const observer = new IntersectionObserver(entries => {

            entries.forEach(entry => {

                if(entry.isIntersecting){

                    entry.target.classList.add('active');

                }

            });

        });

        reveals.forEach(el => observer.observe(el));

    </script>

</body>
</html>