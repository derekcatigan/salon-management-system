<?php

namespace App\Http\Controllers\Appointment;

use App\Enum\BookingStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $status = $request->query('status', 'all');
        $from = $request->query('from');
        $to = $request->query('to');
        $search = trim((string) $request->query('q', ''));

        $query = Booking::query()->forCustomer($user->id);

        if ($status !== 'all' && in_array($status, BookingStatusEnum::values(), true)) {
            $query->where('status', $status);
        }

        if ($from) {
            $query->whereDate('booking_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('booking_date', '<=', $to);
        }

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('reference_no', 'like', $term)
                    ->orWhere('service_name', 'like', $term)
                    ->orWhere('service_category', 'like', $term);
            });
        }

        $bookings = $query
            ->orderByDesc('booking_date')
            ->orderByDesc('time_slot')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Booking $b) => [
                'id' => $b->id,
                'reference_no' => $b->reference_no,
                'service_name' => $b->service_name,
                'service_category' => $b->service_category,
                'service_price' => (float) $b->service_price,
                'service_duration' => $b->service_duration,
                'booking_date' => $b->booking_date->format('Y-m-d'),
                'time_slot' => $b->time_slot,
                'status' => $b->status->value,
                'status_label' => $b->status->label(),
                'status_color' => $b->status->color(),
                'notes' => $b->notes,
                'created_at' => $b->created_at?->format('Y-m-d'),
            ]);

        // Summary stats — respects the same filters so they move with the view
        $statsBase = Booking::query()->forCustomer($user->id);
        if ($from) {
            $statsBase->whereDate('booking_date', '>=', $from);
        }
        if ($to) {
            $statsBase->whereDate('booking_date', '<=', $to);
        }

        $counts = (clone $statsBase)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'total' => (clone $statsBase)->count(),
            'pending' => (int) ($counts[BookingStatusEnum::Pending->value] ?? 0),
            'confirmed' => (int) ($counts[BookingStatusEnum::Confirmed->value] ?? 0),
            'completed' => (int) ($counts[BookingStatusEnum::Completed->value] ?? 0),
            'cancelled' => (int) ($counts[BookingStatusEnum::Cancelled->value] ?? 0),
        ];

        return Inertia::render('Website/AppointmentHistoryWeb', [
            'bookings' => $bookings,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'from' => $from,
                'to' => $to,
                'q' => $search,
            ],
            'statusOptions' => collect(BookingStatusEnum::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }
}
