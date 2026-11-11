<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function words(int $count): string
    {
        return implode(' ', array_fill(0, $count, 'kata'));
    }
}
