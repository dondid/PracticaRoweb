<?php

namespace App\Http\Controllers;

use App\Models\PasswordReset;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 *
 */
class UserController extends ApiController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $error = false;

            /** @var User $user */
            $user = User::where('email', $request->get('email'))->first();
            if (!$user) {
                $error = true;
            } else {
                if (!Hash::check($request->get('password'), $user->password)) {
                    $error = true;
                }
            }

            if ($error) {
                return $this->sendError('Bad credentials!');
            }

            $token = $user->createToken('Practica');

            return $this->sendResponse([
                'token' => $token->plainTextToken,
                'user' => $user->toArray()
            ]);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function forgot_password(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            /** @var User $user */
            $user = User::where('email', $request->get('email'))->first();

            $passwordReset = new PasswordReset();
            $passwordReset->email = $user->email;
            $passwordReset->token = Str::random(10);
            $passwordReset->created_at = now();
            $passwordReset->save();

            $user->notify(new \App\Notifications\PasswordReset($passwordReset->token));

            return $this->sendResponse([
                'message' => 'Code sent on email!'
            ]);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function change_password(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:password_resets,email|exists:users,email',
                'code' => 'required',
                'password' => 'required|confirmed'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            /** @var PasswordReset $passwordReset */
            $passwordReset = PasswordReset::where('email', $request->get('email'))->first();

            $code = $request->get('code');
            $password = $request->get('password');

            if($passwordReset->token != $code){
                return $this->sendError('Bad code!');
            }

            $user = User::where('email', $request->get('email'))->first();
            $user->password = Hash::make($request->get('password'));
            $user->save();

            return $this->sendResponse([
                'message' => 'Password changed!'
            ]);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     *
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|confirmed'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $user = new User();
            $user->name = $request->get('name');
            $user->email = $request->get('email');
            $user->password = Hash::make($request->get('password'));
            $user->email_verified_at = Carbon::now();
            $user->save();

            $token = $user->createToken('Practica');

            return $this->sendResponse([
                'token' => $token->plainTextToken,
                'user' => $user->toArray()
            ]);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
