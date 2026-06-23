<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Guest Portal
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Manage your bookings and pre-arrival check-in
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            
            <?php if ($error = \Syncro\Security\SessionManager::getFlash('error')): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>
            <?php if ($success = \Syncro\Security\SessionManager::getFlash('success')): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                    <p class="text-sm text-green-700"><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['verify']) && isset($_SESSION['guest_otp_email'])): ?>
                
                <!-- OTP Verification Form -->
                <form class="space-y-6" action="<?= base_url('/guest/verify') ?>" method="POST">
                    <?= csrf_field() ?>">
                    <?php if (isset($_GET['return_to'])): ?>
                        <input type="hidden" name="return_to" value="<?= htmlspecialchars($_GET['return_to']) ?>">
                    <?php endif; ?>
                    
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <p class="text-sm text-blue-700">
                            We've sent a 6-digit code to <strong><?= htmlspecialchars($_SESSION['guest_otp_email']) ?></strong>.
                        </p>
                    </div>

                    <div>
                        <label for="otp" class="block text-sm font-medium text-gray-700">
                            Enter 6-digit Code
                        </label>
                        <div class="mt-1">
                            <input id="otp" name="otp" type="text" autocomplete="one-time-code" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-lg text-center tracking-widest font-mono">
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Verify & Login
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <a href="<?= base_url('/guest/login') ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Try a different email</a>
                </div>

            <?php else: ?>

                <!-- Email Request Form -->
                <form class="space-y-6" action="<?= base_url('/guest/login') ?>" method="POST">
                    <?= csrf_field() ?>">
                    <?php if (isset($_GET['return_to'])): ?>
                        <input type="hidden" name="return_to" value="<?= htmlspecialchars($_GET['return_to']) ?>">
                    <?php endif; ?>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Email address
                        </label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" autocomplete="email" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Enter the email you used to make your booking. We'll send you a secure login link.</p>
                    </div>

                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Send Login Code
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">Or continue with</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <?php $googleAuthUrl = isset($_GET['return_to']) ? '/guest/login/google?return_to=' . urlencode($_GET['return_to']) : '/guest/login/google'; ?>
                        <a href="<?= $googleAuthUrl ?>" class="w-full inline-flex justify-center items-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="var(--theme2)"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="var(--success)"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="var(--theme)"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="var(--danger)"/><path d="M1 1h22v22H1z" fill="none"/></svg>
                            Google
                        </a>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>
