<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterPostRequest;
use App\Http\Resources\DataResource;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use App\Notifications\welcomeMessage;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class AuthController extends Controller
{

    /**
     * @param RegisterPostRequest $request
     * @return JsonResponse
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function register(RegisterPostRequest $request): JsonResponse
    {
        $tenant_name = str_replace(' ', '', strtolower($request->tenant_name));

        $exist = Tenant::where('id',$tenant_name)->first();
        if ($exist){
            return response()->json([
                'message' => 'Already have this business name'
            ], 401);
        }

        $tenant = Tenant::create(['id' => $tenant_name]);
        $tenant->domains()->create(['domain' => $tenant_name . '.' . env('APP_DOMAIN')]);

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->tenant_id = $tenant->id;
        $user->save();
        $user->notify(new welcomeMessage($user->name,$user->email));

        $user = User::where('email', $request->email)->first();
        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => $user,
            'redirect_url' => 'http://' . $tenant->id . '.' . env('FRONTEND_URL') . '/auth/redirect?token=' . $token . '&tenant=' . $tenant->id
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function login(Request $request): JsonResponse
    {
        $validateUser = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Please fill all the fields! ',
                'errors' => $validateUser->errors()
            ], 401);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => "That email and password doesn't seem to be correct."
            ], 404);
        }
        $tenant = Tenant::where('id', $user->tenant_id)->first();

        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => "That email and password doesn't seem to be correct."
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => $user,
            'redirect_url' => 'http://' . $tenant->id . '.' . env('FRONTEND_URL') . '/auth/redirect?token=' . $token . '&tenant=' . $tenant->id
        ]);
    }

    /**
     * @return JsonResponse
     */
    public
    function logout(): JsonResponse
    {
        auth()->user()->tokens()->delete();
        return response()->json([
            'redirect_url' => env('FRONTEND_URL') . '/auth/login'
        ]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        // Validate the request data
        $request->validate([
            'email' => 'required|email',
        ]);

        //check user exist
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 419);
        } else {
            $token = rand(100000, 999999);
            $user->reset_token = $token;
            $user->save();

            $user->notify(new PasswordResetNotification($token, $user->email));

            $email = Crypt::encrypt($request->email);
            return response()->json([
                'message' => 'Reset password link sent on your email id.',
                'email' => $email
            ], 200);
        }
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'password' => 'required|confirmed|min:8',
            'token' => 'required',
        ]);

        // Set custom validation messages
        $customMessages = [
            'password.required' => 'The password field is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'token.required' => 'The token field is required.',
        ];

        // Apply custom messages to the validator
        $validator->setCustomMessages($customMessages);

        // Check if validation fails
        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            throw new HttpResponseException(
                response()->json([
                    'message' => implode(', ', $errors),
                    'errors' => [
                        'password' => $errors['password'][0] ?? false,
                        'token' => $errors['token'][0] ?? false,
                    ],
                ], 422)
            // You can customize the response according to your needs
            );
        }

        $email = decrypt($request->token);
        //check user exist
        $user = User::where('email',$email)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 419);
        } else {

            if (auth()->attempt(['email' => $email, 'password' => $request->password])) {
                return response()->json([
                    'message' => "You entered your previous password !"
                ], 401);
            }

            $user->password = bcrypt($request->password);
            $user->reset_token = null;
            $user->save();

            return response()->json([
                'redirect_url' => 'http://' . env('FRONTEND_URL') . '/auth/login'
            ], 200);
        }
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'email' => 'required|email'
        ]);

        $user = User::where('email',$request->email)->where('reset_token',$request->code)->first();
        if (!$user){
            return response()->json(['message' => 'Invalid authentication code !'],404);
        }

        if ($user->reset_token !== $request->code){
            return response()->json(['message' => 'Invalid authentication code !'],404);
        }

        $user->update(['reset_token' => null]);

        $token = encrypt($user->email);
        return response()->json(['redirect_url' => 'http://' . env('FRONTEND_URL') . '/auth/resetpassword?token='.$token]);
    }

    public function emailVerify(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'email' => 'required|email'
        ]);

        $user = User::where('email',$request->email)->where('reset_token',$request->code)->first();
        if (!$user){
            return response()->json(['message' => 'Invalid authentication code !'],404);
        }

        if ($user->reset_token !== $request->code){
            return response()->json(['message' => 'Invalid authentication code !'],404);
        }

        $user->update(['reset_token' => null]);

        $token = encrypt($user->email);

//        return response()->json(['redirect_url' => 'http://' . env('FRONTEND_URL') . '/auth/resetpassword?token='.$token]);
        return redirect()->to('http://' . env('FRONTEND_URL') . '/auth/resetpassword?token='.$token);
    }

    public function user(): DataResource
    {
        $user = auth()->user();
        return new DataResource($user);
    }
}
