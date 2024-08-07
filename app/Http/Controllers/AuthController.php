<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);
    
            $user = new User();
            $user->name =$request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            if($user->save()){
                return response()->json(['result' => 'Created'], 203);
            }else{
                throw new \Exception('Error en la creación del usuario, intente de nuevo');
            }
        } catch (ValidationException $e) {
            return response()->json(['result' => $e->errors()], 422);
        }catch (\Exception $e) {
            return response()->json(['result' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required',
                'password' => 'required|string|min:6',
            ]);
            $credentials = $request->only('email', 'password');
            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $token = $user->createToken('Personal Access Token')->plainTextToken;
                return response()->json(['token' => $token]);
            }
            return response()->json(['error' => 'Unauthorized'], 401);
        } catch (ValidationException $e) {
            return response()->json(['result' => $e->errors()], 422);
        }catch (\Exception $e) {
            return response()->json(['result' => $e->getMessage()], 500);
        }
        
    }
}
