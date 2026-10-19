<?php

namespace App\Services;

use App\Enums\LoginChannel;
use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use App\Services\Otp\SmsSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OtpLoginService
{
    public function __construct(private SmsSender $sms)
    {
    }

    /** Nhận chuỗi khách gõ, trả về [dạng chuẩn, kênh gửi] */
    public function parseIdentifier(string $input): array
    {
        $input = trim($input);

        if (str_contains($input, '@')) {
            $email = mb_strtolower($input);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [$email, LoginChannel::Email];
            }
        } elseif (($phone = self::normalizePhone($input)) !== null) {
            return [$phone, LoginChannel::Sms];
        }

        throw ValidationException::withMessages([
            'identifier' => 'Nhập email hoặc số điện thoại di động hợp lệ.',
        ]);
    }

    /** "0905 123 456", "+84905123456" -> "0905123456". Không phải số di động VN thì trả null */
    public static function normalizePhone(string $input): ?string
    {
        $digits = preg_replace('/\D/', '', $input);
        if (str_starts_with($digits, '84') && strlen($digits) === 11) {
            $digits = '0'.substr($digits, 2);
        }

        return preg_match('/^0[35789]\d{8}$/', $digits) ? $digits : null;
    }

    public function findUser(string $identifier, LoginChannel $channel): ?User
    {
        return User::where($channel === LoginChannel::Email ? 'email' : 'phone', $identifier)->first();
    }

    /** Tạo và gửi mã. Trả về cả mã gốc: chỉ để hiện "mã thử" khi chạy local */
    public function requestCode(string $input, ?string $ip = null): array
    {
        [$identifier, $channel] = $this->parseIdentifier($input);
        $this->ensureCanUseOtp($this->findUser($identifier, $channel));
        $this->ensureNotThrottled($identifier);

        $code = $this->generateCode();

        DB::transaction(function () use ($identifier, $channel, $code, $ip) {
            // Mã cũ chưa dùng thì hủy: chỉ mã mới nhất có hiệu lực
            LoginCode::where('identifier', $identifier)->whereNull('used_at')->update(['used_at' => now()]);

            LoginCode::create([
                'identifier' => $identifier,
                'channel' => $channel,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(config('rental.otp.ttl_minutes')),
                'ip' => $ip,
            ]);
        });

        $this->send($identifier, $channel, $code);

        return ['identifier' => $identifier, 'channel' => $channel, 'code' => $code];
    }

    /** Kiểm tra mã. Đúng thì trả về tài khoản, hoặc null nếu là khách mới chưa có tài khoản */
    public function verify(string $identifier, string $code): ?User
    {
        // Không ném lỗi bên trong transaction, nếu không số lần sai sẽ bị hoàn tác
        [$status, $left, $loginCode] = DB::transaction(function () use ($identifier, $code) {
            $loginCode = LoginCode::where('identifier', $identifier)
                ->whereNull('used_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $loginCode || ! $loginCode->isUsable()) {
                return ['expired', 0, null];
            }

            if (! Hash::check(trim($code), $loginCode->code_hash)) {
                $loginCode->attempts++;
                $left = config('rental.otp.max_attempts') - $loginCode->attempts;
                if ($left <= 0) {
                    $loginCode->used_at = now();
                }
                $loginCode->save();

                return [$left > 0 ? 'wrong' : 'locked', $left, null];
            }

            $loginCode->update(['used_at' => now()]);

            return ['ok', 0, $loginCode];
        });

        match ($status) {
            'expired' => throw ValidationException::withMessages(['code' => 'Mã đã hết hạn hoặc không còn hiệu lực. Bấm "Gửi lại mã" để nhận mã mới.']),
            'locked' => throw ValidationException::withMessages(['code' => 'Nhập sai quá nhiều lần, mã đã bị hủy. Bấm "Gửi lại mã" để nhận mã mới.']),
            'wrong' => throw ValidationException::withMessages(['code' => "Mã không đúng. Còn {$left} lần thử."]),
            default => null,
        };

        $user = $this->findUser($identifier, $loginCode->channel);
        $this->ensureCanUseOtp($user);

        if ($user && $loginCode->channel === LoginChannel::Email && ! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }

        /** Tạo tài khoản khách sau khi đã xác minh mã. Không có mật khẩu. */
    public function registerCustomer(string $identifier, LoginChannel $channel, string $name, ?string $phone = null, ?string $email = null): User
    {
        // Bấm gửi hai lần: tài khoản đã được tạo ở lần trước
        if ($user = $this->findUser($identifier, $channel)) {
            $this->ensureCanUseOtp($user);

            return $user;
        }

        if ($channel === LoginChannel::Email) {
            $email = $identifier;
            $phone = self::normalizePhone((string) $phone);
            if (! $phone) {
                throw ValidationException::withMessages(['phone' => 'Số điện thoại di động không hợp lệ.']);
            }
        } else {
            $phone = $identifier;
            $email = $email ? mb_strtolower(trim($email)) : null;
        }

        if (User::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages(['phone' => 'Số điện thoại này đã có tài khoản. Hãy đăng nhập bằng số điện thoại đó.']);
        }
        if ($email && User::where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'Email này đã có tài khoản. Hãy đăng nhập bằng email đó.']);
        }

        return User::forceCreate([
            'name' => trim($name),
            'email' => $email,
            'phone' => $phone,
            'password' => null,
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => $channel === LoginChannel::Email ? now() : null,
        ]);
    }

        /** Email (hoặc SĐT) của nhân viên thì trả về email để chuyển sang đăng nhập mật khẩu */
    public function staffEmail(string $input): ?string
    {
        [$identifier, $channel] = $this->parseIdentifier($input);
        $user = $this->findUser($identifier, $channel);

        if (! $user) {
            return null;
        }

        $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        return $role === 'customer' ? null : $user->email;
    }

    /** Chỉ khách hàng đang hoạt động mới đăng nhập bằng OTP. Chưa có tài khoản thì cho qua */
        private function ensureCanUseOtp(?User $user): void
    {
        if (! $user) {
            return;
        }

        $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        if ($role !== 'customer') {
            throw ValidationException::withMessages(['identifier' => 'Tài khoản nhân viên đăng nhập bằng mật khẩu ở trang quản lý.']);
        }
        if (! $user->is_active) {
            throw ValidationException::withMessages(['identifier' => 'Tài khoản đã bị khóa. Vui lòng liên hệ cửa hàng.']);
        }
    }

    private function ensureNotThrottled(string $identifier): void
    {
        $wait = config('rental.otp.resend_seconds');
        $last = LoginCode::where('identifier', $identifier)->latest('id')->first();

        if ($last && $last->created_at->gt(now()->subSeconds($wait))) {
            $seconds = max(1, (int) ceil($wait - $last->created_at->diffInSeconds(now())));
            throw ValidationException::withMessages(['identifier' => "Vui lòng chờ {$seconds} giây rồi gửi lại mã."]);
        }

        $lastHour = LoginCode::where('identifier', $identifier)->where('created_at', '>=', now()->subHour())->count();
        if ($lastHour >= config('rental.otp.max_per_hour')) {
            throw ValidationException::withMessages(['identifier' => 'Bạn đã yêu cầu quá nhiều mã. Vui lòng thử lại sau 1 giờ.']);
        }
    }

    private function generateCode(): string
    {
        $length = config('rental.otp.length');

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    private function send(string $identifier, LoginChannel $channel, string $code): void
    {
        $minutes = config('rental.otp.ttl_minutes');

        if ($channel === LoginChannel::Email) {
            Mail::to($identifier)->send(new LoginCodeMail($code, $minutes));

            return;
        }

        $this->sms->send($identifier, "Rental GPS: ma dang nhap cua ban la {$code}, hieu luc {$minutes} phut. Khong chia se ma nay cho ai.");
    }
}