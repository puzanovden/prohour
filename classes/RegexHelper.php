<?php
namespace App\Utils;

class RegexHelper {

    public static function stringToHtml(string $text): string {
        $html = preg_replace("/\n/", "<br>", $text);
        return "<p>" . $html . "</p>";
    }

    public static function htmlToString(string $html): string {
        return preg_replace("/<[^>]*>/", "", $html);
    }

    public static function validateEmail(string $email): bool {
        return (bool)preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $email);
    }

    public static function validateUrl(string $url): bool {
        $pattern = "/^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)$/";
        return (bool)preg_match($pattern, $url);
    }

    public static function validateDate(string $date): bool {
        $pattern = "/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/((1[6-9]|[2-9]\d)\d{2})$/";
        return (bool)preg_match($pattern, $date);
    }

 public static function analyzeLogFile(string $filePath): array {
    if (!file_exists($filePath)) {
        return ['time' => '-', 'action' => 'Лог-файл відсутній', 'login_time' => '-'];
    }
    
    $content = file_get_contents($filePath);
    preg_match_all("/\[(.*?)\]\s(.*)/", $content, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) {
        return ['time' => '-', 'action' => 'Дій не зафіксовано', 'login_time' => '-'];
    }
    $lastLog = end($matches);
    $lastActionTime = $lastLog[1];
    $lastActionName = trim($lastLog[2]);
    $lastLoginTime = '-';
    for ($i = count($matches) - 1; $i >= 0; $i--) {
        if (preg_match("/(Вхід|Авторизація|Login|Авторизовано)/iu", $matches[$i][2])) {
            $lastLoginTime = $matches[$i][1];
            break; 
        }
    }
    if ($lastLoginTime === '-') {
        $lastLoginTime = $lastActionTime;
    }
    
    return [
        'time' => $lastActionTime,
        'action' => $lastActionName,
        'login_time' => $lastLoginTime
    ];
}

    public static function manageTaskScheduler(string $configPath, string $taskName, string $action, string $param = ''): void {
        $config = file_exists($configPath) ? file_get_contents($configPath) : "";
        
        if ($action === 'disable') {
            $config = preg_replace("/^" . preg_quote($taskName, '/') . "=.*/m", "# $taskName=disabled", $config);
        } elseif ($action === 'schedule') {
            $newLine = "{$taskName}={$param}";
            if (preg_match("/^" . preg_quote($taskName, '/') . "=.*/m", $config)) {
                $config = preg_replace("/^" . preg_quote($taskName, '/') . "=.*/m", $newLine, $config);
            } else {
                $config .= "\n" . $newLine;
            }
        } elseif ($action === 'rename') {
            $config = preg_replace("/^" . preg_quote($taskName, '/') . "=(.*)/m", "{$param}=$1", $config);
            $config = preg_replace("/^#\s*" . preg_quote($taskName, '/') . "=disabled/m", "# {$param}=disabled", $config);
        }
        
        file_put_contents($configPath, trim($config));
    }
}