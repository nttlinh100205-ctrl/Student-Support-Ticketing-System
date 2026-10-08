<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\Request;

class CatalogUsageController extends Controller
{
    public function __invoke(Request $request, AuthContext $auth)
    {
        abort_unless($auth->role() === 'admin', 403);
        $data = $request->validate([
            'department_id' => 'required_without:support_type_id|integer|min:1',
            'support_type_id' => 'required_without:department_id|integer|min:1',
        ]);

        return response()->json(['success' => true, 'data' => ['used' => SupportRequest::where($data)->exists()], 'message' => null]);
    }
}
