<div class="mvc-hero">
    <div>
        <div class="mvc-eyebrow">Планування роботи</div>
        <h1>Створення нової задачі</h1>
        <p>
            Додайте нову задачу до свого робочого простору, щоб зафіксувати майбутню активність,
            описати контекст і надалі відстежувати витрачений на неї час.
        </p>
    </div>

    <div class="mvc-hero-badge">
        <span id="taskReadinessLabel">Стан заповнення</span>
        <strong id="taskReadinessValue">Недостатньо даних</strong>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="mvc-alert-success">
        <strong>Задачу створено.</strong>
        <span>Вона вже доступна у списку задач і може бути використана для подальшого трекінгу часу.</span>
    </div>
<?php endif; ?>

<section class="mvc-form-layout">
    <div class="mvc-form-card">
        <h2>Дані задачі</h2>
        <p>
            Вкажіть коротку, зрозумілу назву та додайте коментар, який допоможе
            швидко пригадати суть роботи без зайвих уточнень.
        </p>

        <form method="POST" action="app.php?route=tasks/create" class="mvc-form">
            <label>
                Назва задачі
                <input
                    id="taskNameInput"
                    type="text"
                    name="name"
                    placeholder="Наприклад: Підготувати звіт по часу"
                    required
                >
            </label>

            <label>
                Коментар
                <textarea
                    id="taskCommentInput"
                    name="comment"
                    rows="5"
                    placeholder="Короткий опис, очікуваний результат або важливі уточнення"
                ></textarea>
            </label>

            <button type="submit">Створити задачу</button>
        </form>
    </div>

    <aside class="mvc-explanation-card">
        <h2>Як краще оформити задачу?</h2>

        <div class="mvc-pattern-step">
            <span>1</span>
            <div>
                <strong>Сформулюйте дію</strong>
                <p>Назва має відповідати на питання, що саме потрібно зробити.</p>
            </div>
        </div>

        <div class="mvc-pattern-step">
            <span>2</span>
            <div>
                <strong>Додайте контекст</strong>
                <p>У коментарі варто зазначити деталі, обмеження або очікуваний результат.</p>
            </div>
        </div>

        <div class="mvc-pattern-step">
            <span>3</span>
            <div>
                <strong>Тримайте задачу конкретною</strong>
                <p>Занадто великі задачі складніше оцінювати й аналізувати за часом.</p>
            </div>
        </div>

        <div class="mvc-pattern-step">
            <span>4</span>
            <div>
                <strong>Оновлюйте статус</strong>
                <p>Запускайте, ставте на паузу або завершуйте задачу, щоб аналітика була точною.</p>
            </div>
        </div>
    </aside>
</section>

<script src="js/task-create-readiness.js"></script>