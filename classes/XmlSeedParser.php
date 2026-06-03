<?php
namespace App\Xml;

class XmlSeedParser
{
    private string $currentTag = '';
    private array $seedData = [
        'users' => []
    ];
    private array $currentUser = [];
    private array $currentClient = [];
    private array $currentProject = [];
    private array $currentTask = [];

    public function parseFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("XML seed-файл не знайдено: " . $filePath);
        }

        $parser = \xml_parser_create('UTF-8');

        \xml_set_element_handler(
            $parser,
            [$this, 'startElement'],
            [$this, 'endElement']
        );

        \xml_set_character_data_handler(
            $parser,
            [$this, 'characterData']
        );

        $xmlData = \file_get_contents($filePath);

        if (!\xml_parse($parser, $xmlData, true)) {
            $errorMessage = \sprintf(
                'Помилка XML-парсингу: %s на рядку %d',
                \xml_error_string(\xml_get_error_code($parser)),
                \xml_get_current_line_number($parser)
            );

            throw new \RuntimeException($errorMessage);
        }

        return $this->seedData;
    }

    public function startElement($parser, string $name, array $attributes): void
    {
        $tag = \strtolower($name);
        $this->currentTag = $tag;

        if ($tag === 'user') {
            $this->currentUser = [
                'email' => $attributes['EMAIL'] ?? '',
                'password' => $attributes['PASSWORD'] ?? '',
                'name' => '',
                'projects' => []
            ];
        }

        if ($tag === 'client') {
            $this->currentClient = [
                'name' => ''
            ];
        }

        if ($tag === 'project') {
            $this->currentProject = [
                'name' => '',
                'description' => '',
                'client' => [],
                'tasks' => []
            ];
        }

       if ($tag === 'task') {
            $status = $attributes['STATUS'] ?? 'paused';

            $this->currentTask = [
                'name' => '',
                'status' => $status,
                'comment' => '',
                'accumulated_time' => (int)($attributes['ACCUMULATED_TIME'] ?? 0),
                'last_started_at' => $status === 'active' ? time() : 0
            ];
        }
    }

    public function endElement($parser, string $name): void
    {
        $tag = \strtolower($name);

        if ($tag === 'client') {
            $this->currentProject['client'] = $this->currentClient;
            $this->currentClient = [];
        }

        if ($tag === 'task') {
            $this->currentProject['tasks'][] = $this->currentTask;
            $this->currentTask = [];
        }

        if ($tag === 'project') {
            $this->currentUser['projects'][] = $this->currentProject;
            $this->currentProject = [];
        }

        if ($tag === 'user') {
            $this->seedData['users'][] = $this->currentUser;
            $this->currentUser = [];
        }

        $this->currentTag = '';
    }

    public function characterData($parser, string $data): void
    {
        $value = \trim($data);

        if ($value === '') {
            return;
        }

        switch ($this->currentTag) {
            case 'name':
                $this->writeName($value);
                break;

            case 'description':
                $this->currentProject['description'] = $this->appendValue(
                    $this->currentProject['description'] ?? '',
                    $value
                );
                break;

            case 'comment':
                $this->writeComment($value);
                break;
        }
    }

    private function writeName(string $value): void
    {
        if (!empty($this->currentTask)) {
            $this->currentTask['name'] = $this->appendValue($this->currentTask['name'], $value);
            return;
        }

        if (!empty($this->currentClient)) {
            $this->currentClient['name'] = $this->appendValue($this->currentClient['name'], $value);
            return;
        }

        if (!empty($this->currentProject)) {
            $this->currentProject['name'] = $this->appendValue($this->currentProject['name'], $value);
            return;
        }

        if (!empty($this->currentUser)) {
            $this->currentUser['name'] = $this->appendValue($this->currentUser['name'], $value);
        }
    }

    private function writeComment(string $value): void
    {
        if (!empty($this->currentTask)) {
            $this->currentTask['comment'] = $this->appendValue($this->currentTask['comment'], $value);
        }
    }

    private function appendValue(string $currentValue, string $newValue): string
    {
        if ($currentValue === '') {
            return $newValue;
        }

        return $currentValue . ' ' . $newValue;
    }

    public static function renderHtmlTable(array $seedData): string
    {
        $html = '<table border="1" cellpadding="8" cellspacing="0">';
        $html .= '<tr>';
        $html .= '<th>Користувач</th>';
        $html .= '<th>Email</th>';
        $html .= '<th>Клієнт</th>';
        $html .= '<th>Проєкт</th>';
        $html .= '<th>Опис проєкту</th>';
        $html .= '<th>Завдання</th>';
        $html .= '<th>Статус</th>';
        $html .= '<th>Коментар до завдання</th>';
        $html .= '</tr>';

        foreach ($seedData['users'] as $user) {
            foreach ($user['projects'] as $project) {
                foreach ($project['tasks'] as $task) {
                    $html .= '<tr>';
                    $html .= '<td>' . \htmlspecialchars($user['name']) . '</td>';
                    $html .= '<td>' . \htmlspecialchars($user['email']) . '</td>';
                    $html .= '<td>' . \htmlspecialchars($project['client']['name']) . '</td>';
                    $html .= '<td>' . \htmlspecialchars($project['name']) . '</td>';
                    $html .= '<td>' . \htmlspecialchars($project['description']) . '</td>';
                    $html .= '<td>' . \htmlspecialchars($task['name']) . '</td>';
                    $html .= '<td>' . \htmlspecialchars($task['status']) . '</td>';
                    $html .= '<td>' . \htmlspecialchars($task['comment']) . '</td>';
                    $html .= '</tr>';
                }
            }
        }

        $html .= '</table>';

        return $html;
    }
}