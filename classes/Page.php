<?php

class Page
{
    protected $title;
    protected $description;

    public function __construct($title, $description)
    {
        $this->title = $title;
        $this->description = $description;
    }

    public function renderHeader()
    {
        echo '
        <!DOCTYPE html>
        <html lang="uk">
        <head>
            <meta charset="UTF-8">

            <meta name="viewport"
                  content="width=device-width, initial-scale=1.0">

            <title>' . $this->title . '</title>

            <link rel="stylesheet" href="css/style.css">
        </head>

        <body>

        <header>

    <div class="logo">
        ProHour
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


    <div class="header-actions">

        <div class="lang-switch">

            <button class="lang-btn active">

                <img src="img/ua.svg">

                UA

            </button>

            <button class="lang-btn">

                <img src="img/gb.svg">

                EN

            </button>

        </div>


        <a href="#" class="login-btn">
            Увійти
        </a>


        <a href="#" class="register-btn">
            Спробувати
        </a>

    </div>

</header>
        ';
    }

    public function renderBody()
    {
        echo '
        <div class="container">

            <section class="hero">

                <div class="hero-content">

                    <div class="badge">
                        Smart Time Tracking Platform
                    </div>

                    <h1>
                        Контролюйте час
                        по клієнтах,
                        проєктах та задачах
                    </h1>

                    <p>
                        ProHour допомагає командам
                        відстежувати робочий час,
                        контролювати активність
                        співробітників та аналізувати
                        ефективність роботи
                        у реальному часі.
                    </p>

                    <div class="hero-buttons">

                        <a href="tasks.php" class="primary-btn">
                            Відкрити задачі
                        </a>

                        <a href="#" class="secondary-btn">
                            Переглянути аналітику
                        </a>

                    </div>

                </div>


                <div class="hero-dashboard">

                    <div class="floating-card big-card">

                        <div class="card-label">
                            Активна задача
                        </div>

                        <div class="project-path">
                            NovaTech / CRM System
                        </div>

                        <div class="task-name">
                            API Integration
                        </div>

                        <div class="live-timer">
                            ⏱ 05:24:18
                        </div>

                    </div>


                    <div class="floating-small">

                        <div class="floating-card">

                            <div class="card-label">
                                Активних задач
                            </div>

                            <div class="mini-value">
                                12
                            </div>

                        </div>


                        <div class="floating-card">

                            <div class="card-label">
                                Працівників онлайн
                            </div>

                            <div class="mini-value">
                                5
                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <div class="cards">

                <div class="card">

                    <h2>Про систему</h2>

                    <p>
                        ProHour — сучасна web-система для
                        обліку робочого часу співробітників,
                        аналізу продуктивності,
                        контролю задач та формування звітності.
                    </p>

                </div>

                <div class="card">

                    <h2>Пошук</h2>

                    <div class="search-box">

                        <input
                            type="text"
                            placeholder="Пошук задач або працівників"
                        >

                        <button>
                            Знайти
                        </button>

                    </div>

                </div>

                <div class="card">

                    <h2>Модулі системи</h2>

                    <ul>
                        <li>Аналітика ефективності</li>
                        <li>Контроль задач</li>
                        <li>Облік робочого часу</li>
                        <li>Управління працівниками</li>
                        <li>Формування звітів</li>
                    </ul>

                </div>

                <div class="card">

                    <h2>Статистика</h2>

                    <h3>Активних користувачів</h3>

                    <div class="value">
                        124
                    </div>

                </div>

                <div class="card">

                    <h2>Зворотний зв’язок</h2>

                    <p>
                        Email: support@prohour.local
                    </p>

                    <p>
                        Телефон: +380991112233
                    </p>

                </div>

                <div class="card">

                    <h2>Оновлення системи</h2>

                    <p>
                        Останнє оновлення:
                    </p>

                    <div class="accent">
                        07.05.2026
                    </div>

                </div>

            </div>

        </div>
        ';
    }

    public function renderFooter()
    {
        echo '
        <footer>

            ProHour © 2026
            | Система обліку робочого часу

        </footer>

        </body>
        </html>
        ';
    }

    public function render()
    {
        $this->renderHeader();
        $this->renderBody();
        $this->renderFooter();
    }
}