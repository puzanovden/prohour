<?php

require_once "Page.php";

class HomePage extends Page
{
    private array $labStatus = [];

    public function __construct($title, Translator $t, array $labStatus = [])
    {
        parent::__construct($title, $t);
        $this->labStatus = $labStatus;
    }

    public function renderBody()
    {
        $alertHtml = "";

        if (!empty($this->labStatus['errors'])) {
            $alertHtml .= "<div class=\"mvc-alert-error\">";
            foreach ($this->labStatus['errors'] as $err) {
                $safeError = htmlspecialchars($err, ENT_QUOTES, 'UTF-8');
                $alertHtml .= "⚠️ {$safeError}<br>";
            }
            $alertHtml .= "</div>";
        } elseif (isset($this->labStatus['success']) && $this->labStatus['success'] === true) {
            $lastTime = htmlspecialchars($this->labStatus['last_time'] ?? '-', ENT_QUOTES, 'UTF-8');

            $alertHtml .= "<div class=\"mvc-alert-success\">";
            $alertHtml .= "<strong>✅ Форма успішно пройшла валідацію регулярними виразами лаби №3!</strong>";
            $alertHtml .= "<span>Остання дія в системі зафіксована о: {$lastTime}</span>";
            $alertHtml .= "</div>";
        }

        echo '<link rel="stylesheet" href="css/mvc.css">';
        echo '<link rel="stylesheet" href="css/home.css">';

        echo <<<HTML
        <main class="mvc-page home-page">
            <section class="mvc-shell home-shell reveal">

                <div class="mvc-hero home-hero" id="for-whom">
                    <div class="home-hero-content">
                        <div class="mvc-eyebrow">ProHour Platform</div>

                        <h1>
                            {$this->t->get('hero_h1_1')}<br>
                            {$this->t->get('hero_h1_2')}<br>
                            {$this->t->get('hero_h1_3')}
                        </h1>

                        <p>
                            {$this->t->get('hero_p')}
                        </p>

                        <div class="mvc-home-actions">
                            <a href="tasks.php" class="mvc-secondary-link">{$this->t->get('hero_btn_tasks')}</a>
                            <a href="#features" class="mvc-light-link">{$this->t->get('hero_btn_more')}</a>
                        </div>
                    </div>

                    <div class="mvc-hero-badge home-hero-badge">
                        <span>Smart time tracking</span>
                        <strong>Задачі, клієнти, проєкти та аналітика в одному робочому просторі</strong>
                    </div>
                </div>

                <div class="mvc-stats-grid home-stats reveal">
                    <div class="mvc-stat-card">
                        <span>{$this->t->get('stat_label_time')}</span>
                        <strong>124h</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>{$this->t->get('stat_label_tasks')}</span>
                        <strong>12</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>{$this->t->get('stat_label_proj')}</span>
                        <strong>8</strong>
                    </div>

                    <div class="mvc-stat-card">
                        <span>Командна робота</span>
                        <strong>24/7</strong>
                    </div>
                </div>

                <section id="features" class="mvc-panel reveal">
                    <div class="mvc-panel-header">
                        <div>
                            <div class="mvc-eyebrow">{$this->t->get('features_badge')}</div>
                            <h2>{$this->t->get('features_h2')}</h2>
                            <p>Основні можливості системи ProHour для організації роботи команди, клієнтів, проєктів і задач.</p>
                        </div>
                    </div>

                    <div class="mvc-feature-grid">
                        <div class="mvc-status-card reveal">
                            <span>⏱ {$this->t->get('f_time_h3')}</span>
                            <p>{$this->t->get('f_time_p')}</p>
                        </div>

                        <div class="mvc-status-card reveal">
                            <span>📁 {$this->t->get('f_proj_h3')}</span>
                            <p>{$this->t->get('f_proj_p')}</p>
                        </div>

                        <div class="mvc-status-card reveal">
                            <span>✅ {$this->t->get('f_tasks_h3')}</span>
                            <p>{$this->t->get('f_tasks_p')}</p>
                        </div>

                        <div class="mvc-status-card reveal">
                            <span>📊 {$this->t->get('f_stat_h3')}</span>
                            <p>{$this->t->get('f_stat_p')}</p>
                        </div>
                    </div>
                </section>

                <section id="workflow" class="mvc-panel reveal">
                    <div class="mvc-panel-header">
                        <div>
                            <div class="mvc-eyebrow">{$this->t->get('wf_badge')}</div>
                            <h2>{$this->t->get('wf_h2')}</h2>
                            <p>Типовий сценарій роботи користувача в системі ProHour.</p>
                        </div>
                    </div>

                    <div class="home-workflow-grid">
                        <div class="mvc-pattern-step reveal">
                            <span>01</span>
                            <div>
                                <strong>{$this->t->get('wf_1')}</strong>
                                <p>Користувач створює або отримує задачу в межах проєкту.</p>
                            </div>
                        </div>

                        <div class="mvc-pattern-step reveal">
                            <span>02</span>
                            <div>
                                <strong>{$this->t->get('wf_2')}</strong>
                                <p>Запускається таймер обліку фактично витраченого часу.</p>
                            </div>
                        </div>

                        <div class="mvc-pattern-step reveal">
                            <span>03</span>
                            <div>
                                <strong>{$this->t->get('wf_3')}</strong>
                                <p>Дані накопичуються для подальшої аналітики та звітів.</p>
                            </div>
                        </div>

                        <div class="mvc-pattern-step reveal">
                            <span>04</span>
                            <div>
                                <strong>{$this->t->get('wf_4')}</strong>
                                <p>Керівник бачить стан задач, проєктів і продуктивність команди.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="analytics" class="mvc-panel reveal">
                    <div class="mvc-panel-header">
                        <div>
                            <div class="mvc-eyebrow">Аналітика</div>
                            <h2>Контроль часу та результатів</h2>
                            <p>ProHour дозволяє бачити активність користувачів, статуси задач і загальний прогрес роботи.</p>
                        </div>

                        <a href="app.php?route=analytics" class="mvc-secondary-link">Відкрити аналітику</a>
                    </div>

                    <div class="mvc-big-metric reveal">
                        <span>Загальний фокус системи</span>
                        <strong>Time + Tasks + Team</strong>
                    </div>
                </section>

                <section id="feedback" class="mvc-panel reveal">
                    <div class="mvc-panel-header">
                        <div>
                            <div class="mvc-eyebrow">{$this->t->get('fb_badge')}</div>
                            <h2>{$this->t->get('fb_h2')}</h2>
                            <p>{$this->t->get('fb_p')}</p>
                        </div>
                    </div>

                    {$alertHtml}

                    <form method="POST" action="index.php#feedback" class="mvc-form mvc-home-form">
                        <label>
                            Email
                            <input 
                                type="email" 
                                name="feedback_email" 
                                required 
                                placeholder="{$this->t->get('fb_ph_email')}"
                                autocomplete="email"
                            >
                        </label>

                        <label>
                            Репозиторій
                            <input 
                                type="text" 
                                name="feedback_project_url" 
                                placeholder="Посилання на ваш репозиторій GitHub (https://...)"
                            >
                        </label>

                        <label>
                            Дедлайн
                            <input 
                                type="text" 
                                name="feedback_deadline" 
                                placeholder="Бажаний дедлайн у форматі дд/мм/рррр"
                            >
                        </label>

                        <label class="mvc-home-form-wide">
                            Повідомлення
                            <textarea 
                                name="feedback_message" 
                                required 
                                placeholder="{$this->t->get('fb_ph_msg')}"
                            ></textarea>
                        </label>

                        <button type="submit">{$this->t->get('fb_btn')}</button>
                    </form>
                </section>

            </section>
        </main>
HTML;
    }
}