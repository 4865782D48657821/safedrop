<?php

namespace Tests;

use App\Services\UrlReviewService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeUrlReviewDns();
    }

    protected function fakeUrlReviewDns(array $records = []): void
    {
        app()->instance(
            UrlReviewService::class,
            new UrlReviewService(fn (string $host): array => $records[$host] ?? ['93.184.216.34']),
        );
    }
}
