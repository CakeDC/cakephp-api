<?php
declare(strict_types=1);

namespace CakeDC\Api\Test\App\DI\Service;

class TestService
{
    public function data(): array
    {
        return [
            'a' => 1,
            'b' => 2,
        ];
    }
}
