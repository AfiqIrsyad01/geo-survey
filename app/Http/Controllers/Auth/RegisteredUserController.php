<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use App\Mail\UserCredentials;
use Illuminate\Support\Facades\Mail;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view and user list.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'users' => User::orderBy('created_at', 'desc')->get()
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::min(8)->letters()->numbers()->symbols()],
            'role' => 'required|in:admin,staff,hod',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        // Transmission: Send Credentials to User
        Mail::to($user->email)->send(new UserCredentials($user, $request->password));

        event(new Registered($user));

        return redirect()->back()->with('success', "Personnel '{$user->name}' initialized and credentials transmitted to {$user->email}");
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // Protection: Cannot edit admin via this specialized list if requested
        if ($user->role === 'admin') {
            return redirect()->back()->with('error', 'Administrator profiles must be managed via higher clearance protocols.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,staff,hod',
        ]);

        $user->update($request->only('name', 'email', 'role'));

        return redirect()->back()->with('success', "Profile for '{$user->name}' updated.");
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->role === 'admin' && $user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Self-deactivation is prohibited for security stability.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'Activated' : 'Suspended';
        return redirect()->back()->with('success', "Operational status for '{$user->name}' set to {$status}.");
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->role === 'admin') {
            return redirect()->back()->with('error', 'Recursive deletion of admin entities is restricted.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->back()->with('success', "Identity '{$userName}' purged from system logs.");
    }
}
