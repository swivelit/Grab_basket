<x-guest-layout>
    <form method="POST" action="{{ route('otp.verify') }}">
        @csrf
        <input type="hidden" name="user_id" value="{{ $user_id }}">
        <div>
            <x-input-label for="code" :value="__('Enter OTP')" />
            <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>
        <div class="flex items-center justify-between mt-4">
            <form method="POST" action="{{ route('otp.send') }}" style="display:inline;" id="resendOtpForm">
                @csrf
                <input type="hidden" name="login" value="{{ $type === 'email' ? Auth::user()->email ?? '' : Auth::user()->phone ?? '' }}">
                <button type="submit" class="btn btn-link p-0" id="resendOtpBtn">Resend OTP</button>
            </form>
            <x-primary-button class="ms-4">
                {{ __('Verify OTP') }}
            </x-primary-button>
        </div>
        <div id="otpSuccessMsg" class="text-success mt-2" style="display:none;">OTP resent successfully!</div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var resendBtn = document.getElementById('resendOtpBtn');
                var resendForm = document.getElementById('resendOtpForm');
                var successMsg = document.getElementById('otpSuccessMsg');
                var cooldown = false;
                resendForm.addEventListener('submit', function(e) {
                    if (cooldown) {
                        e.preventDefault();
                        return;
                    }
                    cooldown = true;
                    resendBtn.disabled = true;
                    setTimeout(function() {
                        resendBtn.disabled = false;
                        cooldown = false;
                    }, 30000); // 30 seconds cooldown
                    successMsg.style.display = 'block';
                    setTimeout(function() {
                        successMsg.style.display = 'none';
                    }, 4000);
                });
            });
        </script>
    </form>
</x-guest-layout>
