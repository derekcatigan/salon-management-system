<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\BookingSchedule;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    /**
     * Time slots shared by both the manage page and the customer page.
     * Keep this list in one place so both sides stay in sync.
     */
    public const TIME_SLOTS = [
        '09:00 AM', '10:00 AM', '11:00 AM', '12:00 PM',
        '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM',
        '05:00 PM', '06:00 PM',
    ];

    /**
     * Admin/Staff view — shows a month calendar + slots for the selected date.
     */
    public function index(Request $request): Response
    {
        $month = $request->query('month', now()->format('Y-m'));
        $selectedDate = $request->query('date', now()->toDateString());

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // All schedule rows in the visible month
        $rows = BookingSchedule::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['date', 'time_slot', 'is_available', 'note']);

        // Group per day: { "2026-11-21": { "09:00 AM": true, "10:00 AM": false, ... } }
        $dayMap = [];
        foreach ($rows as $row) {
            $dayKey = $row->date->toDateString();
            $dayMap[$dayKey][$row->time_slot] = [
                'is_available' => $row->is_available,
                'note' => $row->note,
            ];
        }

        // Count taken slots per day (for the calendar dots)
        $takenPerDay = [];
        foreach ($dayMap as $dayKey => $slots) {
            $takenPerDay[$dayKey] = collect($slots)
                ->filter(fn ($s) => $s['is_available'] === false)
                ->count();
        }

        return Inertia::render('Manage/ManageSchedule', [
            'month' => $month,
            'selectedDate' => $selectedDate,
            'timeSlots' => self::TIME_SLOTS,
            'dayMap' => $dayMap,
            'takenPerDay' => $takenPerDay,
        ]);
    }

    /**
     * Toggle a slot between available and blocked.
     */
    public function toggle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time_slot' => ['required', 'string', 'in:'.implode(',', self::TIME_SLOTS)],
            'is_available' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:100'],
        ]);

        BookingSchedule::updateOrCreate(
            ['date' => $data['date'], 'time_slot' => $data['time_slot']],
            [
                'is_available' => $data['is_available'],
                'note' => $data['note'] ?? null,
                'updated_by' => $request->user()->id,
            ]
        );

        return back()->with('success', 'Schedule updated.');
    }

    /**
     * Bulk-open an entire day (used by "Open all" button).
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'is_available' => ['required', 'boolean'],
        ]);

        foreach (self::TIME_SLOTS as $slot) {
            BookingSchedule::updateOrCreate(
                ['date' => $data['date'], 'time_slot' => $slot],
                [
                    'is_available' => $data['is_available'],
                    'updated_by' => $request->user()->id,
                ]
            );
        }

        return back()->with('success', 'Day schedule updated.');
    }

    /**
     * Shared helper for the customer page.
     */
    public static function availabilityMap(string $fromDate, string $toDate): array
    {
        return BookingSchedule::query()
            ->whereBetween('date', [$fromDate, $toDate])
            ->get(['date', 'time_slot', 'is_available'])
            ->groupBy(fn ($r) => $r->date->toDateString())
            ->map(fn ($group) => $group->mapWithKeys(fn ($r) => [
                $r->time_slot => (bool) $r->is_available,
            ]))
            ->all();
    }
}
