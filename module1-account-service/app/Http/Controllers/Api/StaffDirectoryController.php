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

    public function names(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor->status === 'ACTIVE', 403);
        $data = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'required|integer|min:1']);
        $query = User::query()->whereIn('id', $data['ids']);
        if ($actor->role === 'STUDENT') {
            $query->where(fn ($query) => $query->where('id', $actor->id)->orWhereIn('role', ['STAFF', 'DEPARTMENT_HEAD', 'ADMIN']));
        } else {
            abort_unless(in_array($actor->role, ['STAFF', 'DEPARTMENT_HEAD', 'ADMIN'], true), 403);
        }

        return response()->json(['data' => $query->get(['id', 'name', 'role'])]);
    }
}
