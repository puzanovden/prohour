<?php

class Page
{
    protected $title;
    protected $t;

    public function __construct($title, Translator $t)
    {
        $this->title = $title;
        $this->t = $t;
    }

    public function renderHeader()
    {
        $activeUa = $this->t->getLang() === 'uk' ? 'active' : '';
        $activeEn = $this->t->getLang() === 'en' ? 'active' : '';

        echo <<<HTML
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$this->title}</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <header>
        <a href="index.php" class="logo">
            <img src="img/logo.png" alt="ProHour">
        </a>

        <nav>
            <a href="#">{$this->t->get('nav_features')}</a>
            <a href="#">{$this->t->get('nav_for_whom')}</a>
            <a href="#">{$this->t->get('nav_how')}</a>
            <a href="#">{$this->t->get('nav_analytics')}</a>
        </nav>

        <div class="lang-switch">
            <a href="?lang=uk" class="lang-btn {$activeUa}">
                <img src="img/ua.svg" alt="UA"> UA
            </a>
            <a href="?lang=en" class="lang-btn {$activeEn}">
                <img src="img/gb.svg" alt="EN"> EN
            </a>
        </div>

        <div class="user-panel">
            <div class="user-status">{$this->t->get('status_online')}</div>
            <div class="user-avatar">D</div>
        </div>
    </header>
HTML;
    }
    
    public function renderBody()
    {
    }

    public function renderFooter()
    {
        echo <<<HTML
    <footer>
        <div>ProHour © 2026</div>
        <div>Smart Time Tracking System</div>
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
HTML;
    }

    public function render()
    {
        $this->renderHeader();
        $this->renderBody();
        $this->renderFooter();
    }
}