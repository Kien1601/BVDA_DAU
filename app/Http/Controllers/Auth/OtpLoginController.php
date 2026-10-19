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

                if ($email = $this->otp->staffEmail($data['identifier'])) {
            return redirect()->route('admin.login')
                ->withInput(['email' => $email])
                ->with('status', 'Tài khoản nhân viên: nhập mật khẩu để đăng nhập.');
        }
        
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

        public function profile(Request $request): View|RedirectResponse
    {
        $verified = $this->verifiedSession($request);
        if (! $verified) {
            return redirect()->route('login')->withErrors(['identifier' => 'Phiên xác minh đã hết, vui lòng nhận mã mới.']);
        }

        return view('auth.otp.completeProfile', [
            'identifier' => $verified['identifier'],
            'channel' => LoginChannel::from($verified['channel']),
        ]);
    }

    public function storeProfile(Request $request): RedirectResponse
    {
        $verified = $this->verifiedSession($request);
        if (! $verified) {
            return redirect()->route('login')->withErrors(['identifier' => 'Phiên xác minh đã hết, vui lòng nhận mã mới.']);
        }

        $channel = LoginChannel::from($verified['channel']);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => [$channel === LoginChannel::Email ? 'required' : 'nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
        ]);

        $user = $this->otp->registerCustomer(
            $verified['identifier'], $channel, $data['name'], $data['phone'] ?? null, $data['email'] ?? null,
        );

        $request->session()->forget('otp_verified');
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    /** Đã xác minh mã trong 15 phút gần đây thì mới cho tạo tài khoản */
    private function verifiedSession(Request $request): ?array
    {
        $verified = $request->session()->get('otp_verified');

        if (! $verified || now()->timestamp - $verified['at'] > 15 * 60) {
            $request->session()->forget('otp_verified');

            return null;
        }

        return $verified;
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