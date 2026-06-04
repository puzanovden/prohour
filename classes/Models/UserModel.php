<?php

namespace App\Models;

class UserModel
{
    public ?int $id;
    public string $email;
    public string $password;
    public string $name;

    public function __construct(?int $id, string $email, string $password, string $name)
    {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
    }
}