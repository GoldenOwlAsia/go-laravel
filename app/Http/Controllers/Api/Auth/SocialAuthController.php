<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocialProviderRequest;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialAuthController extends Controller
{
    public function redirectToProvider(SocialProviderRequest $request, $provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback(SocialProviderRequest $request, $provider)
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first()?->user;

        if (! $user) {
            $email = $socialUser->getEmail();

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['error' => __('auth.email_required')], 422);
            }

            try {
                $user = DB::transaction(function () use ($socialUser, $provider, $email) {
                    $user = User::firstOrCreate(
                        ['email' => $email],
                        [
                            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? Str::before($email, '@'),
                            'password' => bcrypt(Str::random(16)),
                        ]
                    );

                    $user->socialAccounts()->create([
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                    ]);

                    return $user;
                });
            } catch (UniqueConstraintViolationException) {
                $user = SocialAccount::where('provider', $provider)
                    ->where('provider_id', $socialUser->getId())
                    ->firstOrFail()
                    ->user;
            }
        }

        $token = JWTAuth::fromUser($user);

        return response()->json($this->tokenPayload($token));
    }
}
