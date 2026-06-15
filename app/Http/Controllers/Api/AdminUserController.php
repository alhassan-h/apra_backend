<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withCount('prescriptions')
            ->when(
                $request->search,
                fn ($q, $s) => $q->where('full_name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%"),
            )
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->latest('created_at')
            ->paginate($request->integer('per_page', 20));

        return AdminUserResource::collection($users);
    }

    public function update(Request $request, User $user): AdminUserResource|JsonResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['ADMIN', 'PHARMACIST'])],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'ADMIN') {
            return response()->json(['message' => 'You cannot demote your own account.'], 422);
        }

        $user->update($data);

        return new AdminUserResource($user->loadCount('prescriptions'));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
