<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Session;
use App\Services\JWTService;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    public function register(Request $resquest){
        $validated = Validator::make($resquest->all(), [
            'name' => 'required|string|max:60|min:3',
            'lastname' => 'required|string|max:60|min:3',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'sometimes|in:manager,registered_user'
        ]);

        if($validated->fails()){
            return response()->json([
                'status' => 'validation_error',
                'message' => $validated->errors()
            ], 401);
        }

        try {

            $user = User::create([
                'name' => $resquest->name,
                'lastname' =>  $resquest->lastname,
                'email' => $resquest->email,
                'password' => Hash::make($resquest->password),
                'is_active' => true,
                'login_attemps' => 0,
                'roles' => $resquest->roles ?? 'registered_user'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'The user has been created successfully'
            ],201);


        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen'.$error->getMessage()
            ],500);
        }
    }

    public function login(Request $request, JWTService $jWTService){

        $validated = Validator::make($request->all(), [
            'email' => 'required|email|max:100',
            'password' => 'required|string|min:8'
        ]);

        if($validated->fails()){
            return response()->json([
                'status' => 'validation_error',
                'message' => $validated->errors()
            ], 422);
        }

        try {

            $user = User::where('email', '=', $request->email)->first();

            if(!$user){
                return response()->json([
                    'status' => 'user_not_found',
                    'message' => 'Invalid credentials'
                ],401);
            }

            if(!$user->is_active){
                return response()->json([
                    'status' => 'account_unactive',
                    'message' => 'The account is unactive, Please contact support'
                ],401);
            }

            if($user->login_attemps >= 5){
                return response()->json([
                    'status' => 'account_blocked',
                    'message' => 'Account is blocked due to too many login attempts. Please contact support'
                ],401);
            }

            if(!Hash::check($request->password, $user->password)){
                $user->increment('login_attemps');
                return response()->json([
                    'status' => 'wrong_credentials',
                    'message' => 'Invalid credentials',
                    'login_attempts_left' => max(0, 5 - $user->login_attemps)
                ],401);
            }

            $user->login_attemps = 0;
            $user->last_login = now();
            $user->save();

            //Save the data inside the table sessions
            Session::create([
                'user_id' => $user->id,
                'ip_address' => null,
                'country' => null,
                'city' => null,
                'latitude' => null,
                'longitude' => null
            ]);

            $tokenPayload = [
                'user_id' => $user->id,
                'user_rol' => $user->roles,
                'user_email' => $user->email
            ];

            $token = $jWTService->generateTokenPair($tokenPayload);

            return response()->json([
              'status' => 'success',
              'message' => 'Login successful',
              'data' => $token
            ],200);

        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
    }

    public function getAllUsers(Request $request){

        try {
             $pagination = $request->input('per_page', 15);
             $search = $request->input('search');

             $query = User::select('id','name','lastname','email', 'is_active', 'roles', 'last_login', 'created_at', 'updated_at');

             if($search){
                $query->where(function($q) use ($search){
                    $q->where('name','like',"%{$search}%")
                    ->orWhere('lastname','like',"%{$search}%")
                    ->orWhere('email','like',"%{$search}%");
                });
             }

             $users = $query->paginate($pagination);

             return response()->json([
                'status' => 'success',
                'message' => 'Users retrived successfully',
                'data' => $users
             ],200);
        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }

    }


    public function getUserById(int $id){

        try {
             $user = User::find($id);

            if(!$user){
                return response()->json([
                    'status' => 'account_not_found',
                    'message' => 'The account could not be found'
                ],404);
            }

            $user->makeHidden(['password','login_attemps']);

            return response()->json([
                'status' => 'success',
                'message' => 'User has been found',
                'data' => $user
            ],200);

        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }

    }

    public function updateUser(Request $request, int $id){

        $validated = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:60|min:3',
            'lastname' => 'sometimes|string|max:60|min:3',
            'email' => 'sometimes|email',
            'password' => 'sometimes|string|min:8|confirmed',
            'is_active' => 'sometimes|boolean',
            'roles' => 'sometimes|in:manager,registered_user'
        ]);

        if($validated->fails()){
            return response()->json([
                'status' => 'validation_error',
                'message' => $validated->errors()
            ],401);
        }

        try {

            $user = User::find($id);


            if(!$user){
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'User not found'
                ], 404);
            }

            $dataUpdated = $request->only(['name', 'lastname', 'email', 'is_active', 'roles']);

            if($request->has('password')){
                $dataUpdated['password'] = Hash::make($request->password);
            }

            $user->update($dataUpdated);

            return response()->json([
                'status' => 'success',
                'message' => 'The user data has been updated',
                'data' => $user->makeHidden(['password','login_attemps'])
            ], 200);

        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
    }

    public function deleteUser(int $id){
        try {

            $user = User::find($id);

            if(!$user){
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'User not found'
                ],404);
            }

            $user->update(['is_active' => false]);

            return response()->json([
                'status' => 'success',
                'message' => 'User has been deactivated'
            ], 200);

        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen'.$error->getMessage()
            ],500);
        }
    }

    public function refreshToken(Request $request, JWTService $jWTService){

        $validated = Validator::make($request->all(), [
            'refresh_token' => 'required|string'
        ]);

        if($validated->fails()){
            return response()->json([
                'status' => 'validation_error',
                'message' => $validated->errors()
            ],401);
        }

        try {

        $decode = $jWTService->decodeRefreshToken($request->refresh_token);

        if($decode->type !== 'refresh_access_token'){
            return response()->json([
                'status' => 'invalid_type',
                'message' => 'Invalid refresh access token type'
            ],401);
        }

        $user = User::find($decode->user_id);

        if(!$user){
            return response()->json([
                    'status' => 'not_found',
                    'message' => 'User not found'
            ], 404);
        }

        if(!$user->is_active){
            return response()->json([
                    'status' => 'account_unactive',
                    'message' => 'The account is unactive, Please contact support'
            ],401);
        }

        $tokenPayload = [
                'user_id' => $user->id,
                'user_rol' => $user->roles,
                'user_email' => $user->email
        ];

        $token = $jWTService->generateToken($tokenPayload);

        return response()->json([
            'status' => 'success',
            'message' => 'Token refreshed successfully',
            'access_token' => $token
        ],200);
        
        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
       
    }

    public function validateAccessToken(Request $request, JWTService $jWTService){
        $validated = Validator::make($request->all(), [
            'access_token' => 'required|string'
        ]);

        if($validated->fails()){
            return response()->json([
                'status' => 'validation_error',
                'message' => $validated->errors()
            ],401);
        }

        try {

         $decode = $jWTService->decodeAccessToken($request->access_token);

         if($decode->type !== 'access_token'){
             return response()->json([
                'status' => 'invalid_type',
                'message' => 'Invalid access token type'
            ],401);
         }

         return response()->json([
            'status' => 'success',
            'message' => 'Token is valid'
         ],200);


        
        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
    }

}
