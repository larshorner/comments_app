<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller{
    /**
     * Display the login view.
     *
     * @return Application|Factory|View|\Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Authenticate a user from the DB, if the user does not exist create the new user
     * @param Request $request
     * @return Application|RedirectResponse|Redirector
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->only('email');
        $user        = User::where('email', $credentials['email'])->first();
        if(empty($user)){
            $user = User::create(['email' => $credentials['email']]);
        }

        Auth::login($user);
        $redirect = redirect('/login');
        if(Auth::check()){
            $redirect = redirect('/comments');
        }

        return $redirect;
    }

    /**
     * Destroy an authenticated session.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
