<?php

namespace App\Http\Controllers\Auth;

use App\Enums\LoginChannel;
use App\Http\Controllers\Controller;
use App\Services\OtpLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class OtpLoginController extends Controller
{
    public function __construct(private OtpLoginService $otp) {}

    public function create(): View
    {
        return view('auth.otp.requestCode');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['identifier' => ['required', 'string', 'max:191']]);

        $this->sendCode($data['identifier'], $request->ip());

        return redirect()->route('otp.verify');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('otp');
        if (! $pending) {
            return redirect()->route('login');
        }

        return view('auth.otp.verifyCode', [
            'identifier' => $pending['identifier'],
            'channel' => LoginChannel::from($pending['channel']),
            'debugCode' => $pending['debug_code'],
            'minutes' => config('rental.otp.ttl_minutes'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('otp');
        if (! $pending) {
            return redirect()->route('login');
        }

        $data = $request->validate(
            ['code' => ['required', 'digits:6']],
            ['code.digits' => 'Mã gồm 6 chữ số.'],
        );

        $user = $this->otp->verify($pending['identifier'], $data['code']);
        $request->session()->forget('otp');

        if (! $user) {
            // Khách mới: nhớ là đã xác minh, A5.5 hỏi tên rồi mới tạo tài khoản
            $request->session()->put('otp_verified', [
                'identifier' => $pending['identifier'],
                'channel' => $pending['channel'],
                'at' => now()->timestamp,
            ]);

            return redirect()->route('otp.profile');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

            return redirect()->intended('/');
    }

    public function resend(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('otp');
        if (! $pending) {
            return redirect()->route('login');
        }

        $this->sendCode($pending['identifier'], $request->ip());

        return back()->with('status', 'Đã gửi mã mới.');
    }

    private function sendCode(string $input, ?string $ip): void
    {
        try {
            $result = $this->otp->requestCode($input, $ip);
        } catch (TransportExceptionInterface $e) {
            Log::error('Gửi mã OTP thất bại', ['error' => $e->getMessage()]);
            throw ValidationException::withMessages([
                'identifier' => 'Không gửi được mã lúc này. Vui lòng thử lại sau hoặc dùng số điện thoại.',
            ]);
        }

        $isSms = $result['channel'] === LoginChannel::Sms;

        session()->put('otp', [
            'identifier' => $result['identifier'],
            'channel' => $result['channel']->value,
            'debug_code' => app()->isLocal() && $isSms ? $result['code'] : null,
        ]);
    }
}