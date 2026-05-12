<?php

class Translator
{
    private $lang;
    
    private $dictionary = [
        'uk' => [
            // Загальні
            'nav_features' => 'Можливості',
            'nav_for_whom' => 'Для кого',
            'nav_how' => 'Як це працює',
            'nav_analytics' => 'Аналітика',
            'status_online' => 'Онлайн',
            
            // HomePage
            'home_title' => 'ProHour - Система відстеження часу',
            'hero_h1_1' => 'Контролюйте час.',
            'hero_h1_2' => 'Організовуйте роботу.',
            'hero_h1_3' => 'Приймайте рішення.',
            'hero_p' => 'Задачі, проєкти, облік часу та аналітика — в одному просторі для компаній, команд та фрілансерів.',
            'hero_btn_tasks' => 'Відкрити задачі',
            'hero_btn_more' => 'Дізнатись більше',
            'features_badge' => 'Можливості системи',
            'features_h2' => 'Все необхідне для контролю часу',
            'f_time_h3' => 'Облік часу',
            'f_time_p' => 'Відстежуйте час виконання задач у реальному часі.',
            'f_proj_h3' => 'Проєкти',
            'f_proj_p' => 'Групуйте задачі по клієнтах та проєктах.',
            'f_tasks_h3' => 'Задачі',
            'f_tasks_p' => 'Контролюйте статуси задач та дедлайни.',
            'f_stat_h3' => 'Аналітика',
            'f_stat_p' => 'Аналізуйте продуктивність та навантаження.',
            'wf_badge' => 'Workflow',
            'wf_h2' => 'Простий процес роботи',
            'wf_1' => 'Клієнт',
            'wf_2' => 'Проєкт',
            'wf_3' => 'Задача',
            'wf_4' => 'Час',
            'stat_label_time' => 'Зафіксовано часу',
            'stat_label_tasks' => 'Активних задач',
            'stat_label_proj' => 'Активних проєктів',
            'fb_badge' => 'Зворотний зв’язок',
            'fb_h2' => 'Маєте питання?',
            'fb_p' => 'Напишіть нам повідомлення або залиште свій email.',
            'fb_ph_email' => 'Ваш email',
            'fb_ph_msg' => 'Ваше повідомлення',
            'fb_btn' => 'Надіслати',

            // TasksPage
            'tasks_title' => 'Мої задачі',
            'add_task' => 'Додати нову задачу',
            'task_name_ph' => 'Назва задачі (напр., Проєктування БД)',
            'btn_create' => 'Створити',
            'in_progress' => 'В процесі виконання 🚀',
            'btn_pause' => '⏸ Пауза',
            'btn_play' => '▶ Запустити',
            'all_tasks' => 'Усі задачі',
            'empty_list' => 'Список порожній. Час додати першу задачу!',
            'status_completed' => '✅ Завершено',
            'status_active' => '▶ В роботі',
            'status_paused' => '⏸ На паузі',
            'btn_edit' => 'Змінити',
            'btn_delete' => 'Видалити',
            'ph_new_name' => 'Нова назва',
        ],
        'en' => [
            // Загальні
            'nav_features' => 'Features',
            'nav_for_whom' => 'For Whom',
            'nav_how' => 'How it works',
            'nav_analytics' => 'Analytics',
            'status_online' => 'Online',
            
            // HomePage
            'home_title' => 'ProHour - Time Tracking System',
            'hero_h1_1' => 'Control your time.',
            'hero_h1_2' => 'Organize your work.',
            'hero_h1_3' => 'Make decisions.',
            'hero_p' => 'Tasks, projects, time tracking, and analytics — in one workspace for companies, teams, and freelancers.',
            'hero_btn_tasks' => 'Open Tasks',
            'hero_btn_more' => 'Learn More',
            'features_badge' => 'System Features',
            'features_h2' => 'Everything you need to control time',
            'f_time_h3' => 'Time Tracking',
            'f_time_p' => 'Track task execution time in real-time.',
            'f_proj_h3' => 'Projects',
            'f_proj_p' => 'Group tasks by clients and projects.',
            'f_tasks_h3' => 'Tasks',
            'f_tasks_p' => 'Monitor task statuses and deadlines.',
            'f_stat_h3' => 'Analytics',
            'f_stat_p' => 'Analyze productivity and workload.',
            'wf_badge' => 'Workflow',
            'wf_h2' => 'Simple work process',
            'wf_1' => 'Client',
            'wf_2' => 'Project',
            'wf_3' => 'Task',
            'wf_4' => 'Time',
            'stat_label_time' => 'Tracked hours',
            'stat_label_tasks' => 'Active tasks',
            'stat_label_proj' => 'Active projects',
            'fb_badge' => 'Feedback',
            'fb_h2' => 'Have questions?',
            'fb_p' => 'Send us a message or leave your email.',
            'fb_ph_email' => 'Your email',
            'fb_ph_msg' => 'Your message',
            'fb_btn' => 'Send',

            // TasksPage
            'tasks_title' => 'My Tasks',
            'add_task' => 'Add new task',
            'task_name_ph' => 'Task name (e.g., DB Design)',
            'btn_create' => 'Create',
            'in_progress' => 'In Progress 🚀',
            'btn_pause' => '⏸ Pause',
            'btn_play' => '▶ Play',
            'all_tasks' => 'All Tasks',
            'empty_list' => 'List is empty. Time to add the first task!',
            'status_completed' => '✅ Completed',
            'status_active' => '▶ Active',
            'status_paused' => '⏸ Paused',
            'btn_edit' => 'Edit',
            'btn_delete' => 'Delete',
            'ph_new_name' => 'New name',
        ]
    ];

    public function __construct($lang = 'uk')
    {
        $this->lang = array_key_exists($lang, $this->dictionary) ? $lang : 'uk';
    }

    public function get($key)
    {
        return $this->dictionary[$this->lang][$key] ?? $key;
    }

    public function getLang()
    {
        return $this->lang;
    }
}