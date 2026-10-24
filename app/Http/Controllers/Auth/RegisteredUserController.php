<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    private function resolveAuthenticatedUser(): ?User
    {
        foreach (['sanctum', 'api', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user();
            }
        }

        return null;
    }

    private function authorizeUserAction(string $ability, User|string $target): void
    {
        $authenticatedUser = $this->resolveAuthenticatedUser();

        if (! $authenticatedUser) {
            abort(403, 'You do not have permission to perform this action.');
        }

        Gate::forUser($authenticatedUser)->authorize($ability, $target);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): Response
    {
        $this->authorizeUserAction('create', User::class);

        $allowedRoles = ['Admin', 'HR Manager', 'Manager', 'Auditor'];

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'employee_id' => 'nullable|exists:employees,id',
            'role' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) use ($allowedRoles): void {
                if (! in_array(strtolower((string) $value), array_map('strtolower', $allowedRoles), true)) {
                    $fail('The selected role is invalid.');
                }
            }],
        ]);

        $normalizedRole = collect($allowedRoles)
            ->first(fn (string $role) => strcasecmp($role, (string) $request->role) === 0, $request->role);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
            'employee_id' => $request->employee_id,
            'role' => $normalizedRole,
        ];
        if (isset($request->employee_id)) {
            $userData['employee_id'] = $request->employee_id;
        }
        $user = User::create($userData);

        event(new Registered($user));

        return response()->noContent();
    }

    public function update(Request $request, string $id): Response
    {
        $user = User::findOrFail($id);
        $this->authorizeUserAction('update', $user);

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($request->email, 'email'),
            ],
            'employee_id' => 'nullable|exists:employees,id',
            'role' => ['sometimes', 'string', 'in:Admin,HR Manager,Manager,Auditor'],
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];
        if (isset($request->employee_id)) {
            $userData['employee_id'] = $request->employee_id;
        }
        $user->update($userData);

        return response()->noContent();
    }

    public function index(): JsonResponse
    {
        $this->authorizeUserAction('viewAny', User::class);
        $users = (new User)->getAllUsers();

        return response()->json($users);
    }

    public function destroy(Request $request, string $id): Response
    {
        $user = User::findOrFail($id);
        $this->authorizeUserAction('delete', $user);

        $user->delete();

        return response()->noContent();
    }
}
