<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StaffDirectoryController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->status === 'ACTIVE', 403);

        return response()->json(['data' => User::query()
            ->where('status', 'ACTIVE')->whereIn('role', ['STAFF', 'DEPARTMENT_HEAD'])
            ->orderBy('id')->get(['id', 'name', 'role', 'department_id'])]);
    }
}
