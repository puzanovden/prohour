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
            $alertHtml .= "<div style='background:#fdebd0; color:#b03a2e; padding:10px; border-radius:5px; margin-bottom:15px; font-weight:bold;'>";
            foreach ($this->labStatus['errors'] as $err) {
                $alertHtml .= "⚠️ " . htmlspecialchars($err) . "<br>";
            }
            $alertHtml .= "</div>";
        } elseif (isset($this->labStatus['success']) && $this->labStatus['success'] === true) {
            $alertHtml .= "<div style='background:#d4efdf; color:#196f3d; padding:10px; border-radius:5px; margin-bottom:15px; font-weight:bold;'>";
            $alertHtml .= "✅ Форма успішно пройшла валідацію регулярними виразами лаби №3!<br>";
            $alertHtml .= "Остання дія в системі зафіксована о: " . htmlspecialchars($this->labStatus['last_time']);
            $alertHtml .= "</div>";
        }

        echo <<<HTML
    <main>
        <section class="hero">
            <div class="hero-content">
                <h1>
                    {$this->t->get('hero_h1_1')}<br>
                    <span>{$this->t->get('hero_h1_2')}<br></span>
                    {$this->t->get('hero_h1_3')}<br>
                </h1>
                <p>
                    {$this->t->get('hero_p')}
                </p>
                <div class="hero-buttons">
                    <a href="tasks.php" class="primary-btn">{$this->t->get('hero_btn_tasks')}</a>
                    <a href="#" class="secondary-btn">{$this->t->get('hero_btn_more')}</a>
                </div>
            </div>
        </section>

        <section id="features" class="features reveal">
            <div class="section-header">
                <div class="section-badge">{$this->t->get('features_badge')}</div>
                <h2>{$this->t->get('features_h2')}</h2>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">⏱</div>
                    <h3>{$this->t->get('f_time_h3')}</h3>
                    <p>{$this->t->get('f_time_p')}</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📁</div>
                    <h3>{$this->t->get('f_proj_h3')}</h3>
                    <p>{$this->t->get('f_proj_p')}</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">✅</div>
                    <h3>{$this->t->get('f_tasks_h3')}</h3>
                    <p>{$this->t->get('f_tasks_p')}</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>{$this->t->get('f_stat_h3')}</h3>
                    <p>{$this->t->get('f_stat_p')}</p>
                </div>
            </div>
        </section>

        <section id="workflow" class="workflow reveal">
            <div class="section-header">
                <div class="section-badge">{$this->t->get('wf_badge')}</div>
                <h2>{$this->t->get('wf_h2')}</h2>
            </div>
            <div class="workflow-line">
                <div class="workflow-item">
                    <div class="workflow-number">01</div>
                    <h3>{$this->t->get('wf_1')}</h3>
                </div>
                <div class="workflow-item">
                    <div class="workflow-number">02</div>
                    <h3>{$this->t->get('wf_2')}</h3>
                </div>
                <div class="workflow-item">
                    <div class="workflow-number">03</div>
                    <h3>{$this->t->get('wf_3')}</h3>
                </div>
                <div class="workflow-item">
                    <div class="workflow-number">04</div>
                    <h3>{$this->t->get('wf_4')}</h3>
                </div>
            </div>
        </section>

        <section id="analytics" class="stats reveal">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">124h</div>
                    <div class="stat-label">{$this->t->get('stat_label_time')}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">12</div>
                    <div class="stat-label">{$this->t->get('stat_label_tasks')}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">8</div>
                    <div class="stat-label">{$this->t->get('stat_label_proj')}</div>
                </div>
            </div>
        </section>

        <section id="feedback" class="feedback reveal">
            <div  class="feedback-card">
                <div class="section-badge">{$this->t->get('fb_badge')}</div>
                <h2>{$this->t->get('fb_h2')}</h2>
                <p>{$this->t->get('fb_p')}</p>
                
                {$alertHtml}

                <form method="POST" action="index.php#feedback">
                    <input type="text" name="feedback_email" required placeholder="{$this->t->get('fb_ph_email')}" style="width:100%; margin-bottom:10px; padding:10px;">
                    
                    <input type="text" name="feedback_project_url" placeholder="Посилання на ваш репозиторій GitHub (https://...)" style="width:100%; margin-bottom:10px; padding:10px;">
                    
                    <input type="text" name="feedback_deadline" placeholder="Бажаний дедлайн у форматі дд/мм/рррр (напр. 02/06/2026)" style="width:100%; margin-bottom:10px; padding:10px;">
                    
                    <textarea name="feedback_message" required placeholder="{$this->t->get('fb_ph_msg')}" style="width:100%; margin-bottom:10px; padding:10px;"></textarea>
                    
                    <button type="submit">{$this->t->get('fb_btn')}</button>
                </form>
            </div>
        </section>
    </main>
HTML;
    }
}