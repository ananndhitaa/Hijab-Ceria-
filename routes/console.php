<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Bootstrap a platform SUPER_ADMIN account idempotently in the testing/disposable environment.
 */
Artisan::command('nayara:super-admin:bootstrap
    {--email= : Email address for the Super Admin}
    {--name= : Full name for the Super Admin}
    {--password= : Password (minimum 8 characters)}
    {--force : Bypass interactive prompts}', function () {

    // 1. Safety Gate: Guard against running on protected operational databases
    $currentDb = DB::connection()->getDatabaseName();
    $protectedDbs = ['hijab_erp', 'hijab_erp_nayara_rehearsal'];

    if (in_array(strtolower((string) $currentDb), $protectedDbs, true)) {
        $this->error("CRITICAL SAFETY ERROR: Cannot bootstrap Super Admin on protected operational database [{$currentDb}]!");
        return 1;
    }

    $email = $this->option('email');
    $name = $this->option('name');
    $password = $this->option('password');

    // Interactive prompts if not provided via flags
    if (! $email) {
        $email = $this->ask('Masukkan email Super Admin');
    }

    if (! $name) {
        $name = $this->ask('Masukkan nama Super Admin', 'Platform Super Admin');
    }

    if (! $password) {
        $password = $this->secret('Masukkan password Super Admin (min. 8 karakter)');
    }

    // Validate inputs
    $validator = Validator::make([
        'email'    => $email,
        'name'     => $name,
        'password' => $password,
    ], [
        'email'    => ['required', 'string', 'email'],
        'name'     => ['required', 'string', 'max:255'],
        'password' => ['required', 'string', 'min:8'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }
        return 1;
    }

    // 2. Idempotent creation/update
    /** @var User $user */
    $user = User::where('email', $email)->first();

    if ($user) {
        $user->name = $name;
        $user->password = Hash::make($password);
        $user->role = UserRole::SUPER_ADMIN;
        $user->status = UserStatus::ACTIVE;
        $user->outlet_id = null; // Strictly NULL for platform role
        $user->save();

        $this->info("Akun Super Admin yang sudah ada berhasil diperbarui: [{$email}]");
    } else {
        $user = User::create([
            'name'      => $name,
            'email'     => $email,
            'password'  => Hash::make($password),
            'role'      => UserRole::SUPER_ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => null, // Strictly NULL for platform role
        ]);

        $this->info("Akun Super Admin baru berhasil dibuat: [{$email}]");
    }

    $this->table(
        ['ID', 'Nama', 'Email', 'Role', 'Status', 'Outlet ID'],
        [[
            $user->id,
            $user->name,
            $user->email,
            $user->role->value,
            $user->status->value,
            $user->outlet_id ?? 'NULL (Platform Authority)',
        ]]
    );

    return 0;
})->purpose('Bootstrap a platform SUPER_ADMIN account idempotently in disposable test environment');

/**
 * Secure one-time bootstrap procedure for the verified operational database (hijab_erp).
 * Satisfies Section K safety gates:
 * - Strictly sets role = SUPER_ADMIN, status = ACTIVE, outlet_id = null
 * - Password entered interactively via secret input (no plaintext in shell history)
 * - Never prints password or password hash
 * - Validates against duplicate platform users or accidental ERP assignment
 */
Artisan::command('nayara:super-admin:provision-operational
    {--email= : Email address for the operational Super Admin}
    {--name= : Full name for the operational Super Admin}
    {--password= : Password (for non-interactive provisioning only)}
    {--force : Force operation without confirmation prompt}', function () {

    $currentDb = DB::connection()->getDatabaseName();
    $this->info('=== NAYARA Operational Super Admin Provisioning ===');
    $this->line("Target Database: [{$currentDb}]");

    $email = $this->option('email');
    $name = $this->option('name');
    $password = $this->option('password');

    if (! $email) {
        $email = $this->ask('Masukkan email Super Admin');
    }

    if (! $name) {
        $name = $this->ask('Masukkan nama lengkap Super Admin', 'Platform Super Admin');
    }

    if (! $password) {
        $password = $this->secret('Masukkan password Super Admin (min. 8 karakter)');
        $passwordConfirmation = $this->secret('Konfirmasi password Super Admin');
        if ($password !== $passwordConfirmation) {
            $this->error('Konfirmasi password tidak cocok.');
            return 1;
        }
    }

    $validator = Validator::make([
        'email'    => $email,
        'name'     => $name,
        'password' => $password,
    ], [
        'email'    => ['required', 'string', 'email'],
        'name'     => ['required', 'string', 'max:255'],
        'password' => ['required', 'string', 'min:8'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }
        return 1;
    }

    // Safety: check existing user
    $existing = User::where('email', $email)->first();
    if ($existing) {
        if (! $this->option('force') && ! $this->confirm("User dengan email [{$email}] sudah ada (Role: {$existing->role->value}). Perbarui akun ini menjadi Super Admin?")) {
            $this->warn('Operasi dibatalkan.');
            return 1;
        }
    }

    DB::transaction(function () use ($existing, $email, $name, $password) {
        if ($existing) {
            $existing->name = $name;
            $existing->password = Hash::make($password);
            $existing->role = UserRole::SUPER_ADMIN;
            $existing->status = UserStatus::ACTIVE;
            $existing->outlet_id = null; // Strictly NULL: platform authority only
            $existing->save();
            $this->info("Akun Super Admin [{$email}] berhasil diperbarui.");
        } else {
            User::create([
                'name'      => $name,
                'email'     => $email,
                'password'  => Hash::make($password),
                'role'      => UserRole::SUPER_ADMIN,
                'status'    => UserStatus::ACTIVE,
                'outlet_id' => null, // Strictly NULL: platform authority only
            ]);
            $this->info("Akun Super Admin baru [{$email}] berhasil diprovisi.");
        }
    });

    $user = User::where('email', $email)->first();
    $this->table(
        ['ID', 'Nama', 'Email', 'Role', 'Status', 'Outlet ID (Platform Scope)'],
        [[
            $user->id,
            $user->name,
            $user->email,
            $user->role->value,
            $user->status->value,
            $user->outlet_id ?? 'NULL (Platform Authority Only)',
        ]]
    );

    return 0;
})->purpose('Securely provision or update a SUPER_ADMIN account on the operational database');

/**
 * Synchronize platform ad campaigns, release expired reservations, sync subscriptions, and send SLA reminders.
 */
Artisan::command('nayara:platform:sync', function (
    \App\Services\Platform\AdCapacityAndScheduleService $scheduleService,
    \App\Services\Platform\PlatformNotificationService $notifService
) {
    $this->info('Running NAYARA platform synchronization...');

    // 1. Synchronize ad campaign statuses (SCHEDULED -> ACTIVE, ACTIVE -> COMPLETED)
    $syncResult = $scheduleService->syncCampaignStatuses();
    $this->line("• Campaign sync: {$syncResult['activated']} activated, {$syncResult['completed']} completed.");

    // 2. Release expired reservations (2-hour unpaid, or hold expired)
    $released = $scheduleService->releaseExpiredReservations();
    $this->line("• Expired reservations released: {$released}.");

    // 3. Mark expired subscriptions
    $expiredSubs = \App\Models\Platform\TenantSubscription::where('status', \App\Models\Platform\TenantSubscription::STATUS_ACTIVE)
        ->where('expires_at', '<', now())
        ->update(['status' => \App\Models\Platform\TenantSubscription::STATUS_EXPIRED]);
    $this->line("• Subscriptions expired: {$expiredSubs}.");

    // 4. Send creative revision reminders (Section V)
    $reminders = $notifService->sendRevisionReminders();
    $this->line("• Revision reminders dispatched: {$reminders}.");

    $this->info('NAYARA platform sync completed.');
    return 0;
})->purpose('Synchronize platform ad campaigns, release expired reservations, and send reminders');

// Register schedule in Laravel console
\Illuminate\Support\Facades\Schedule::command('nayara:platform:sync')->everyMinute();
