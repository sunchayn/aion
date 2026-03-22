<?php

namespace Tests\Support\TestCases;

use Mockery;
use PHPUnit\Framework\TestCase;

class UnitTestCase extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }
}
