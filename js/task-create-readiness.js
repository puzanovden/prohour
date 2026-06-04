document.addEventListener('DOMContentLoaded', () => {
    const taskNameInput = document.getElementById('taskNameInput');
    const taskCommentInput = document.getElementById('taskCommentInput');
    const taskReadinessLabel = document.getElementById('taskReadinessLabel');
    const taskReadinessValue = document.getElementById('taskReadinessValue');

    if (!taskNameInput || !taskReadinessLabel || !taskReadinessValue) {
        return;
    }

    function updateTaskReadiness() {
        const taskName = taskNameInput.value.trim();
        const taskComment = taskCommentInput ? taskCommentInput.value.trim() : '';

        if (taskName === '') {
            taskReadinessLabel.textContent = 'Стан заповнення';
            taskReadinessValue.textContent = 'Недостатньо даних';
            return;
        }

        if (taskComment === '') {
            taskReadinessLabel.textContent = 'Стан задачі';
            taskReadinessValue.textContent = 'Можна створити';
            return;
        }

        taskReadinessLabel.textContent = 'Стан задачі';
        taskReadinessValue.textContent = 'Готова до старту';
    }

    taskNameInput.addEventListener('input', updateTaskReadiness);

    if (taskCommentInput) {
        taskCommentInput.addEventListener('input', updateTaskReadiness);
    }

    updateTaskReadiness();
});