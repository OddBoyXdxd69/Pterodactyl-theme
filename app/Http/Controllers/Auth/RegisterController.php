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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

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

        // Check if OTP is enabled and mail is configured
        if (config('pterodactyl.registration.otp', false)) {
            $otp = sprintf("%06d", mt_rand(100000, 999999));
            $email = $request->input('email');

            // Store registration data in Cache for 15 minutes
            $cacheData = [
                'email' => $email,
                'username' => $request->input('username'),
                'name_first' => $request->input('name_first'),
                'name_last' => $request->input('name_last'),
                'password' => $request->input('password'),
                'otp' => $otp,
            ];
            Cache::put('registration_otp_' . $email, $cacheData, now()->addMinutes(15));

            // Send HTML email
            $mailContent = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - INVMC</title>
</head>
<body style="margin: 0; padding: 0; background-color: #07080e; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #e5e7eb;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 40px auto; background-color: #11121c; border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); overflow: hidden;">
        <!-- Header -->
        <tr>
            <td align="center" style="padding: 40px 20px; background: linear-gradient(135deg, #8b5cf6 0%, #3b82f6 100%);">
                <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: 2px;">INVMC</h1>
                <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Security Verification</p>
            </td>
        </tr>
        <!-- Content -->
        <tr>
            <td style="padding: 40px 30px;">
                <h2 style="margin-top: 0; color: #ffffff; font-size: 20px; font-weight: 600; text-align: center;">Verify Your Registration</h2>
                <p style="color: #9ca3af; font-size: 15px; line-height: 1.6; text-align: center; margin-bottom: 30px;">
                    Thank you for signing up with INVMC. To complete your registration, please use the 6-digit verification code below. This code will expire in 15 minutes.
                </p>
                <!-- OTP Box -->
                <table align="center" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" style="background-color: rgba(139, 92, 246, 0.1); border: 2px dashed #8b5cf6; border-radius: 12px; padding: 15px 40px;">
                            <span style="font-family: \'Courier New\', Courier, monospace; font-size: 36px; font-weight: 700; color: #8b5cf6; letter-spacing: 8px;">' . $otp . '</span>
                        </td>
                    </tr>
                </table>
                <p style="color: #6b7280; font-size: 13px; text-align: center; margin-top: 30px; margin-bottom: 0;">
                    If you did not request this email, you can safely ignore it.
                </p>
            </td>
        </tr>
        <!-- Footer -->
        <tr>
            <td align="center" style="padding: 20px; background-color: #0b0c16; border-top: 1px solid rgba(255, 255, 255, 0.05); color: #4b5563; font-size: 12px;">
                &copy; ' . date('Y') . ' INVMC. All rights reserved.<br>
                <span style="color: #374151;">Protected by reCAPTCHA</span>
            </td>
        </tr>
    </table>
</body>
</html>
';

            try {
                Mail::html($mailContent, function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Verify Your Email - INVMC OTP Code')
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to send OTP verification email. Please contact administrator or check mail settings: ' . $e->getMessage(),
                ], 500);
            }

            return response()->json([
                'otp_required' => true,
                'email' => $email,
            ]);
        }

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

    /**
     * Confirm registration OTP and complete sign up.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        if (!config('pterodactyl.registration.enabled', false)) {
            return response()->json([
                'error' => 'Registration is currently disabled by administrator.',
            ], 403);
        }

        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $email = $request->input('email');
        $otp = $request->input('otp');

        $cacheData = Cache::get('registration_otp_' . $email);

        if (!$cacheData || $cacheData['otp'] !== $otp) {
            return response()->json([
                'error' => 'The verification code provided is incorrect or has expired.',
            ], 400);
        }

        // Create the user
        $user = $this->creator->handle([
            'email' => $cacheData['email'],
            'username' => $cacheData['username'],
            'name_first' => $cacheData['name_first'],
            'name_last' => $cacheData['name_last'],
            'password' => $cacheData['password'],
            'root_admin' => false,
        ]);

        // Clear cache
        Cache::forget('registration_otp_' . $email);

        $this->auth->guard()->login($user, true);
        Event::dispatch(new DirectLogin($user, true));

        return response()->json([
            'success' => true,
            'intended' => '/',
        ]);
    }
}
