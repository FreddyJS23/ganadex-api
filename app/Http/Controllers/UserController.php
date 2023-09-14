<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user= new User;
        $user->password=hash::make($request->clave);
        $user->role_id=1;
        $user->fill($request->except('clave'));
        $user->save();
       
        return response()->json(['message'=>'usuario creado'],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json(['user'=>new UserResource($user)],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->clave = Hash::make($request->clave);
        $user->fill($request->all())->save();

        return  response()->json(['user' => new UserResource($user)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        return  response()->json(['userID' => User::destroy($user->id) ?  $user->id : ''], 200);
    }
}
