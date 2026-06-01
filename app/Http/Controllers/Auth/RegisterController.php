<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Pterodactyl\Rules\Username;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Events\Auth\DirectLogin;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UserCreationService;

class RegisterController extends Controller
{
    /**
     * RegisterController constructor.
     */
    public function __construct(
        private AuthManager $auth,
        private UserCreationService $creator,
    ) {
    }

    /**
     * Handle user registration request.
     */
    public function register(Request $request): JsonResponse
    {
        if (!config('pterodactyl.registration.enabled', false)) {
            return response()->json([
                'error' => 'Registration is currently disabled by administrator.',
            ], 403);
        }

        $request->validate([
            'email' => 'required|email|unique:users,email',
            'username' => ['required', 'string', 'min:3', 'max:30', 'unique:users,username', new Username()],
            'name_first' => 'required|string|min:1|max:50',
            'name_last' => 'required|string|min:1|max:50',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->creator->handle([
            'email' => $request->input('email'),
            'username' => $request->input('username'),
            'name_first' => $request->input('name_first'),
            'name_last' => $request->input('name_last'),
            'password' => $request->input('password'),
            'root_admin' => false,
        ]);

        $this->auth->guard()->login($user, true);
        Event::dispatch(new DirectLogin($user, true));

        return response()->json([
            'success' => true,
            'intended' => '/',
        ]);
    }
}
