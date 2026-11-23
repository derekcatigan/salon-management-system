<?php

namespace App\Http\Controllers\Manage;

use App\Enum\BookingStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Models\BookingSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'all');
        $from = $request->query('from');
        $to = $request->query('to');
        $search = trim((string) $request->query('q', ''));

        $query = Booking::query()->with(['customer:id,email', 'customer.profile']);

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
                    ->orWhere('full_name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        $bookings = $query
            ->orderByDesc('booking_date')
            ->orderByDesc('time_slot')
            ->paginate(15)
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
                'full_name' => $b->full_name,
                'phone' => $b->phone,
                'email' => $b->email,
                'notes' => $b->notes,
                'customer_email' => $b->customer?->email,
                'customer_name' => $this->composeCustomerName($b->customer),
                'created_at' => $b->created_at?->format('Y-m-d H:i'),
            ]);

        // Stats — respect date filter only, not status
        $statsBase = Booking::query();
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
            'today' => (clone $statsBase)->whereDate('booking_date', today())->count(),
        ];

        return Inertia::render('Manage/ManageBooking', [
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
                'color' => $c->color(),
            ]),
        ]);
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): RedirectResponse
    {
        $newStatus = BookingStatusEnum::from($request->validated('status'));
        $oldStatus = $booking->status;

        DB::transaction(function () use ($booking, $oldStatus, $newStatus) {
            $booking->update(['status' => $newStatus]);

            // Re-open the schedule slot when a booking is cancelled
            if ($newStatus === BookingStatusEnum::Cancelled
                && in_array($oldStatus->value, ['pending', 'confirmed'], true)) {
                BookingSchedule::query()
                    ->whereDate('date', $booking->booking_date->toDateString())
                    ->where('time_slot', $booking->time_slot)
                    ->update([
                        'is_available' => true,
                        'note' => 'Cancelled by staff',
                        'updated_by' => request()->user()->id,
                    ]);
            }

            // Re-block the slot if a cancelled booking is re-activated
            if ($oldStatus === BookingStatusEnum::Cancelled
                && in_array($newStatus->value, ['pending', 'confirmed'], true)) {
                BookingSchedule::updateOrCreate(
                    [
                        'date' => $booking->booking_date->toDateString(),
                        'time_slot' => $booking->time_slot,
                    ],
                    [
                        'is_available' => false,
                        'note' => 'Reactivated by staff',
                        'updated_by' => request()->user()->id,
                    ]
                );
            }
        });

        return back()->with(
            'success',
            "Booking {$booking->reference_no} status updated to {$newStatus->label()}."
        );
    }

    private function composeCustomerName(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $profile = $user->profile;
        if (! $profile) {
            return $user->email;
        }

        $parts = array_filter([
            $profile->firstname,
            $profile->middlename,
            $profile->lastname,
            $profile->suffix,
        ]);

        return count($parts) ? implode(' ', $parts) : $user->email;
    }
}
