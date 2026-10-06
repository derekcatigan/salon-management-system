<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Manage/ManageInventory');
    }
}
