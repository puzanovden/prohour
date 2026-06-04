<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use RuntimeException;

class TaskActionDomLogger
{
    private string $filePath;
    private const ROOT_ELEMENT = 'taskActions';

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function log(array $actionData): void
    {
        $dom = $this->loadOrCreateDocument();
        $root = $dom->documentElement;

        if (!$root) {
            throw new RuntimeException('Не вдалося отримати кореневий елемент XML-документа.');
        }

        $actionElement = $dom->createElement('action');
        $actionElement->setAttribute('created_at', date('c'));

        $actionElement->appendChild(
            $this->createTextElement($dom, 'type', $actionData['type'] ?? 'unknown')
        );

        $actionElement->appendChild(
            $this->createTextElement($dom, 'description', $actionData['description'] ?? '')
        );

        $actionElement->appendChild(
            $this->createTextElement($dom, 'user_id', (string)($actionData['user_id'] ?? ''))
        );

        $actionElement->appendChild(
            $this->createTextElement($dom, 'user_name', $actionData['user_name'] ?? '')
        );

        $actionElement->appendChild(
            $this->createTextElement($dom, 'user_email', $actionData['user_email'] ?? '')
        );

        $actionElement->appendChild(
            $this->createTextElement($dom, 'task_id', (string)($actionData['task_id'] ?? ''))
        );

        $actionElement->appendChild(
            $this->createTextElement($dom, 'scheduler_task', $actionData['scheduler_task'] ?? '')
        );

        $root->appendChild($actionElement);

        $dom->formatOutput = true;
        $dom->save($this->filePath);
    }

    public function getActions(): array
    {
        if (!file_exists($this->filePath) || filesize($this->filePath) === 0) {
            return [];
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->load($this->filePath);

        $actions = [];

        foreach ($dom->getElementsByTagName('action') as $actionElement) {
            $actions[] = [
                'created_at' => $actionElement->getAttribute('created_at'),
                'type' => $this->getChildText($actionElement, 'type'),
                'description' => $this->getChildText($actionElement, 'description'),
                'user_id' => $this->getChildText($actionElement, 'user_id'),
                'user_name' => $this->getChildText($actionElement, 'user_name'),
                'user_email' => $this->getChildText($actionElement, 'user_email'),
                'task_id' => $this->getChildText($actionElement, 'task_id'),
                'scheduler_task' => $this->getChildText($actionElement, 'scheduler_task'),
            ];
        }

        return array_reverse($actions);
    }

    private function loadOrCreateDocument(): DOMDocument
    {
        $this->ensureDirectoryExists();

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if (file_exists($this->filePath) && filesize($this->filePath) > 0) {
            $dom->load($this->filePath);

            if ($dom->documentElement !== null) {
                return $dom;
            }
        }

        $root = $dom->createElement(self::ROOT_ELEMENT);
        $dom->appendChild($root);
        $dom->save($this->filePath);

        return $dom;
    }

    private function ensureDirectoryExists(): void
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    private function createTextElement(DOMDocument $dom, string $name, string $value): DOMElement
    {
        $element = $dom->createElement($name);
        $element->appendChild($dom->createTextNode($value));

        return $element;
    }

    private function getChildText(DOMElement $parent, string $tagName): string
    {
        $items = $parent->getElementsByTagName($tagName);

        if ($items->length === 0) {
            return '';
        }

        return $items->item(0)->textContent ?? '';
    }
}