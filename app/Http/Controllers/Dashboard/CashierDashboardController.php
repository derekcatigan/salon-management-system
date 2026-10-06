<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class CashierDashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/CashierDashboard');
    }
}
