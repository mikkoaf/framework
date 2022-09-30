<?php

namespace Illuminate\Tests\Queue\Delaying;

use Illuminate\Container\Container;
use Illuminate\Queue\InvalidPayloadException;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Mockery as m;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use Throwable;

class DelayedBeyondRetryUntilTest extends TestCase
{

    public function testRetryUntilPassesWithoutDelay(): void
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });
        $queue = new QueueFake(new Container);

        $job = m::mock(Job::class);
        $job->shouldReceive('retryUntil')->once()->andReturn(now()->addMinute()->toDateTime());
        $queue->push($job);

        /**
         * Assert that one job, the mock has been pushed. $queue->assertPushed(Job::class), preferred if the Job::class
         * equals with the mock.
         */
        $this->assertCount(1, $queue->pushedJobs());

        Str::createUuidsNormally();
    }

    public function testExpiredRetryUntilFailsWithoutDelay(): void
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });
        $queue = new QueueFake(new Container);

        $job = m::mock(Job::class);
        $job->shouldReceive('retryUntil')->once()->andReturn(now()->subMinute()->toDateTime());

        try {
            $queue->push($job);
        } catch (InvalidPayloadException $exception) {
            $this->assertEquals('Job set to expire before it is pushed to queue!', $exception->getMessage());
        } catch (Throwable $unexpectedThrowable) {
            $this->fail($unexpectedThrowable->getMessage());
        }

        $this->assertCount(0, $queue->pushedJobs());

        Str::createUuidsNormally();
    }


    public function testDelayBeyondRetryUntilFails(): void
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });
        $queue = new QueueFake(new Container);

        $job = m::mock(Job::class);
        $job->shouldReceive('retryUntil')->once()->andReturn(10);

        try {
            $queue->later(11,$job);
        } catch (InvalidPayloadException $exception) {
            $this->assertEquals('Job set to expire before it is pushed to queue!', $exception->getMessage());
        } catch (Throwable $unexpectedThrowable) {
            $this->fail($unexpectedThrowable->getMessage());
        }

        $this->assertCount(0, $queue->pushedJobs());

        Str::createUuidsNormally();
    }
}
