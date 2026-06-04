<?php

namespace App\Models;

class ProjectModel
{
    public ?int $id;
    public ?int $clientId;
    public string $name;
    public ?string $description;

    public function __construct(?int $id, ?int $clientId, string $name, ?string $description)
    {
        $this->id = $id;
        $this->clientId = $clientId;
        $this->name = $name;
        $this->description = $description;
    }
}