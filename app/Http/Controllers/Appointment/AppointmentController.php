<?php

namespace App\Http\Controllers\Appointment;

use App\Enum\BookingStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Manage\ScheduleController;
use App\Http\Requests\Website\StoreBookingRequest;
use App\Models\Booking;
use App\Models\BookingSchedule;
use App\Models\Service;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(): Response
    {
        $from = now()->toDateString();
        $to = now()->addDays(60)->toDateString();

        $services = Service::query()
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'price', 'duration_minutes', 'description'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category,
                'price' => (float) $s->price,
                'duration' => $s->duration_minutes,
                'description' => $s->description,
            ]);

        $categories = collect(['all'])
            ->merge($services->pluck('category')->unique()->values())
            ->map(fn ($c) => [
                'key' => $c,
                'label' => $c === 'all' ? 'All' : $c,
            ])
            ->values();

        $user = auth()->user();
        $defaults = [
            'full_name' => '',
            'phone' => '',
            'email' => '',
        ];

        if ($user) {
            $profile = $user->profile;
            $parts = array_filter([
                $profile?->firstname,
                $profile?->middlename,
                $profile?->lastname,
                $profile?->suffix,
            ]);
            $defaults = [
                'full_name' => trim(implode(' ', $parts)),
                'phone' => $profile?->contact ?? '',
                'email' => $user->email,
            ];
        }

        return Inertia::render('Website/AppointmentWeb', [
            'services' => $services,
            'categories' => $categories,
            'timeSlots' => ScheduleController::TIME_SLOTS,
            'availability' => ScheduleController::availabilityMap($from, $to),
            'isAuthenticated' => $user !== null,
            'defaults' => $defaults,
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $service = Service::active()->find($data['service_id']);
        if (! $service) {
            return back()
                ->withErrors(['service_id' => 'The selected service is no longer available.'])
                ->withInput();
        }

        // BookingSchedule uses `date` + `time_slot` (not booking_date)
        $blocked = BookingSchedule::query()
            ->whereDate('date', $data['booking_date'])
            ->where('time_slot', $data['time_slot'])
            ->where('is_available', false)
            ->exists();

        if ($blocked) {
            return back()
                ->withErrors(['time_slot' => 'That time slot has just been taken. Please pick another.'])
                ->withInput();
        }

        try {
            $booking = DB::transaction(function () use ($user, $service, $data) {
                BookingSchedule::updateOrCreate(
                    [
                        'date' => $data['booking_date'],
                        'time_slot' => $data['time_slot'],
                    ],
                    [
                        'is_available' => false,
                        'note' => 'Booked via website',
                        'updated_by' => $user->id,
                    ]
                );

                return Booking::create([
                    'reference_no' => Booking::generateReference($data['booking_date']),
                    'customer_id' => $user->id,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'service_category' => $service->category,
                    'service_price' => $service->price,
                    'service_duration' => $service->duration_minutes,
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'booking_date' => $data['booking_date'],
                    'time_slot' => $data['time_slot'],
                    'notes' => $data['notes'] ?? null,
                    'status' => BookingStatusEnum::Pending,
                ]);
            });
        } catch (UniqueConstraintViolationException $e) {
            return back()
                ->withErrors(['time_slot' => 'That time slot was just booked by someone else. Please choose another.'])
                ->withInput();
        }

        return redirect()
            ->route('appointment.web.index')
            ->with('success', "Your appointment is booked! Reference: {$booking->reference_no}");
    }
}
