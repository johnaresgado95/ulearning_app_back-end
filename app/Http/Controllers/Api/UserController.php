<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Create User
     * @param Request $request
     * @return User 
     */
    public function login(Request $request)
    {
        try {
            //Validated
            $validateUser = Validator::make($request->all(), 
            [
                'avatar' => 'required',
                'type' => 'required',
                
                'name' => 'required',
                'email' => 'required',
                'open_id' => 'required',
                // 'password' => 'required|min:6'
            ]);

            if($validateUser->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            // validated will have all user field values
            // We can save in the database
            $validated = $validateUser->validated();

            $map=[];
            // email, phone, apple, google, facebook
            $map['type'] = $validated['type'];
            $map['open_id'] = $validated['open_id'];
            $user = User::where($map)->first();

            // Whether user has already logged in or not
            // empty means does not exist 
            // then save the user in the database for first time
            if(empty($user->id)){
                // this certain user has never been in our database
                // our job is to assign the user in the database
                // this token is user id
                $validated["token"] = md5(uniqid().rand(10000, 99999));
                // User first time created
                $validated["created_at"] = Carbon::now();
                // Encrypt password
                // $validated['password'] = Hash::make($validated['password']);
                // returns the id of the row after saving
                $userID = User::insertGetId($validated);
                // user's all the information
                $userInfo = User::where('id', '=', $userID)->first();
                // Create a token
                $accessToken = $userInfo->createToken(uniqid())->plainTextToken;
                $userInfo->access_token = $accessToken;
                User::where('id', '=', $userID)->update(['access_token'=>$accessToken]);
                return response()->json([
                    'code' => 200,
                    'msg' => 'User Created Successfully',
                    'data' => $userInfo
                ], 200);
            }
            // User previously logged in
            // 
            $accessToken = $user->createToken(uniqid())->plainTextToken;
            $user->access_token = $accessToken;
            User::where('open_id', '=', $validated['open_id'])->update(['access_token'=>$accessToken]);

            return response()->json([
                'code' => 200,
                'msg' => 'User Logged In Successfully',
                'data' => $user
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}