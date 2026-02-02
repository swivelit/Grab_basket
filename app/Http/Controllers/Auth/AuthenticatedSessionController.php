<?php




namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;

class AuthenticatedSessionController extends Controller
{
    protected function authenticated(Request $request, $user)
    {
        // Get user role
        $role = $user->role ?? 'buyer';

        // Seller: always go to dashboard
        if ($role === 'seller') {
            return redirect()->route('seller.dashboard');
        }

        // Buyer: respect intended URL (e.g. /food/cart), fallback to food index
        return redirect()->intended(route('customer.food.index'));
    }
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        
        $user = Auth::user();
        $role = $user->role ?? 'buyer';
        
        // Simple greeting message (faster processing)
        $greeting = "வணக்கம் {$user->name}! Welcome back to GrabBasket!";
        
        // Streamlined redirect logic
        $redirectRoute = $role === 'seller' ? 'seller.dashboard' : 'home';
        
        return redirect()->route($redirectRoute)->with([
            'success' => $greeting,
            'login_success' => true
        ]);
        
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Store a flag before logout to prevent session expiry issues
        $wasLoggedIn = Auth::check();
        
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Clear any cached responses
        if ($wasLoggedIn) {
            return redirect('/')->with('success', 'You have been logged out successfully.')
                ->withHeaders([
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0'
                ]);
        }

        return redirect('/');
    }

    /**
     * Get gender-based greeting message
     */
    private function getGenderBasedGreeting(string $gender, string $name): string
    {
        switch ($gender) {
            case 'male':
                return "வணக்கம் {$name}! Welcome back to GrabBasket!";
            case 'female':
                return "வணக்கம் {$name}! Welcome back to GrabBasket!";
            case 'other':
                return "வணக்கம் {$name}! Welcome back to GrabBasket!";
            default:
                return "வணக்கம் {$name}! Welcome back to GrabBasket!";
        }
    }
}
