<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutoRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'expense_type_id',
        'expense_category_id',
        'requisites',
        'requisites_file_url',
        'amount',
        'ready_for_payment',
        'frequency',
        'interval_days',
        'days_of_week',
        'day_of_month',
        'start_date',
        'run_at',
        'timezone',
        'next_run_at',
        'last_run_at',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'ready_for_payment' => 'boolean',
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'start_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutoRuleLog::class);
    }

    public function computeNextRunAt(?CarbonInterface $fromUtc = null): ?CarbonInterface
    {
        $timezone = $this->timezone ?: 'Europe/Kyiv';
        $fromUtc = $fromUtc ?: now('UTC');
        $fromLocal = $fromUtc->copy()->setTimezone($timezone);

        $runAt = $this->run_at ?: '09:00';
        $runAt = strlen($runAt) === 5 ? $runAt.':00' : $runAt;
        $startDateString = $this->start_date instanceof Carbon
            ? $this->start_date->toDateString()
            : Carbon::parse($this->start_date)->toDateString();

        $base = Carbon::createFromFormat('Y-m-d H:i:s', "{$startDateString} {$runAt}", $timezone);

        if ($this->frequency === 'once') {
            if ($base->lessThanOrEqualTo($fromLocal)) {
                return null;
            }

            return $base->copy()->setTimezone('UTC');
        }

        if ($this->frequency === 'daily') {
            if ($fromLocal->lessThan($base)) {
                return $base->copy()->setTimezone('UTC');
            }

            $candidate = $fromLocal->copy()->setTimeFromTimeString($runAt);
            if ($candidate->lessThanOrEqualTo($fromLocal)) {
                $candidate->addDay();
            }

            return $candidate->setTimezone('UTC');
        }

        if ($this->frequency === 'every_n_days') {
            $interval = max(1, (int) $this->interval_days);
            if ($fromLocal->lessThan($base)) {
                return $base->copy()->setTimezone('UTC');
            }

            $diffDays = $base->diffInDays($fromLocal);
            $steps = intdiv($diffDays, $interval) + 1;
            $candidate = $base->copy()->addDays($steps * $interval);

            return $candidate->setTimezone('UTC');
        }

        if ($this->frequency === 'weekly') {
            $days = array_values(array_filter((array) $this->days_of_week));
            if ($days === []) {
                return null;
            }

            if ($fromLocal->lessThan($base)) {
                $fromLocal = $base->copy();
            }

            $candidate = $fromLocal->copy()->setTimeFromTimeString($runAt);
            for ($i = 0; $i < 14; $i++) {
                $dayIso = $candidate->dayOfWeekIso;
                if (in_array($dayIso, $days, true) && $candidate->greaterThan($fromLocal)) {
                    return $candidate->copy()->setTimezone('UTC');
                }
                $candidate->addDay()->setTimeFromTimeString($runAt);
            }

            return null;
        }

        $dayOfMonth = (int) $this->day_of_month;
        if ($dayOfMonth <= 0) {
            $dayOfMonth = (int) $base->day;
        }

        if ($this->frequency === 'monthly') {
            if ($fromLocal->lessThan($base)) {
                $fromLocal = $base->copy();
            }

            $candidate = $fromLocal->copy()->setTimeFromTimeString($runAt);
            $candidateDay = min($dayOfMonth, $candidate->daysInMonth);
            $candidate->setDay($candidateDay);
            if ($candidate->lessThanOrEqualTo($fromLocal)) {
                $candidate->addMonthNoOverflow();
                $candidateDay = min($dayOfMonth, $candidate->daysInMonth);
                $candidate->setDay($candidateDay);
            }

            return $candidate->setTimezone('UTC');
        }

        return null;
    }
}
