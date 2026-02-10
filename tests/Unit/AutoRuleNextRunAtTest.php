<?php

namespace Tests\Unit;

use App\Models\AutoRule;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AutoRuleNextRunAtTest extends TestCase
{
    public function test_once_rule_returns_null_when_time_has_passed(): void
    {
        $rule = new AutoRule([
            'frequency' => 'once',
            'start_date' => '2026-02-10',
            'run_at' => '09:00',
            'timezone' => 'Europe/Kyiv',
        ]);

        $nowUtc = CarbonImmutable::parse('2026-02-10 07:01:00', 'UTC'); // 09:01 Kyiv
        $nextRunUtc = $rule->computeNextRunAt($nowUtc);

        $this->assertNull($nextRunUtc);
    }

    public function test_once_rule_returns_scheduled_time_when_still_in_future(): void
    {
        $rule = new AutoRule([
            'frequency' => 'once',
            'start_date' => '2026-02-10',
            'run_at' => '09:00',
            'timezone' => 'Europe/Kyiv',
        ]);

        $nowUtc = CarbonImmutable::parse('2026-02-10 06:59:00', 'UTC'); // 08:59 Kyiv
        $nextRunUtc = $rule->computeNextRunAt($nowUtc);

        $this->assertNotNull($nextRunUtc);
        $this->assertSame('2026-02-10 07:00:00', $nextRunUtc->toDateTimeString());
    }

    public function test_daily_rule_moves_to_next_day_when_today_time_has_passed(): void
    {
        $rule = new AutoRule([
            'frequency' => 'daily',
            'start_date' => '2026-02-10',
            'run_at' => '09:00',
            'timezone' => 'Europe/Kyiv',
        ]);

        $nowUtc = CarbonImmutable::parse('2026-02-10 07:10:00', 'UTC'); // 09:10 Kyiv
        $nextRunUtc = $rule->computeNextRunAt($nowUtc);

        $this->assertNotNull($nextRunUtc);
        $this->assertSame('2026-02-11 07:00:00', $nextRunUtc->toDateTimeString());
    }

    public function test_every_n_days_rule_moves_by_interval(): void
    {
        $rule = new AutoRule([
            'frequency' => 'every_n_days',
            'interval_days' => 3,
            'start_date' => '2026-02-10',
            'run_at' => '09:00',
            'timezone' => 'Europe/Kyiv',
        ]);

        $nowUtc = CarbonImmutable::parse('2026-02-11 09:00:00', 'UTC'); // 11:00 Kyiv
        $nextRunUtc = $rule->computeNextRunAt($nowUtc);

        $this->assertNotNull($nextRunUtc);
        $this->assertSame('2026-02-13 07:00:00', $nextRunUtc->toDateTimeString());
    }

    public function test_weekly_rule_moves_to_next_matching_weekday(): void
    {
        $rule = new AutoRule([
            'frequency' => 'weekly',
            'days_of_week' => [2], // Tuesday
            'start_date' => '2026-02-10',
            'run_at' => '09:00',
            'timezone' => 'Europe/Kyiv',
        ]);

        $nowUtc = CarbonImmutable::parse('2026-02-10 07:30:00', 'UTC'); // Tuesday 09:30 Kyiv
        $nextRunUtc = $rule->computeNextRunAt($nowUtc);

        $this->assertNotNull($nextRunUtc);
        $this->assertSame('2026-02-17 07:00:00', $nextRunUtc->toDateTimeString());
    }

    public function test_weekly_rule_returns_null_without_selected_weekdays(): void
    {
        $rule = new AutoRule([
            'frequency' => 'weekly',
            'days_of_week' => [],
            'start_date' => '2026-02-10',
            'run_at' => '09:00',
            'timezone' => 'Europe/Kyiv',
        ]);

        $nowUtc = CarbonImmutable::parse('2026-02-10 07:30:00', 'UTC');
        $nextRunUtc = $rule->computeNextRunAt($nowUtc);

        $this->assertNull($nextRunUtc);
    }

    public function test_monthly_rule_moves_to_next_month_when_today_time_has_passed(): void
    {
        $rule = new AutoRule([
            'frequency' => 'monthly',
            'day_of_month' => 10,
            'start_date' => '2026-01-10',
            'run_at' => '09:00',
            'timezone' => 'Europe/Kyiv',
        ]);

        $nowUtc = CarbonImmutable::parse('2026-02-10 08:00:00', 'UTC'); // 10:00 Kyiv
        $nextRunUtc = $rule->computeNextRunAt($nowUtc);

        $this->assertNotNull($nextRunUtc);
        $this->assertSame('2026-03-10 07:00:00', $nextRunUtc->toDateTimeString());
    }

    public function test_monthly_rule_caps_day_to_last_day_of_month(): void
    {
        $rule = new AutoRule([
            'frequency' => 'monthly',
            'day_of_month' => 31,
            'start_date' => '2026-01-31',
            'run_at' => '09:00',
            'timezone' => 'Europe/Kyiv',
        ]);

        $nowUtc = CarbonImmutable::parse('2026-02-10 07:00:00', 'UTC');
        $nextRunUtc = $rule->computeNextRunAt($nowUtc);

        $this->assertNotNull($nextRunUtc);
        $this->assertSame('2026-02-28 07:00:00', $nextRunUtc->toDateTimeString());
    }
}
