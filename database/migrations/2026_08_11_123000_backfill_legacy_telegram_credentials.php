<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('users')
                ->where(function ($query) {
                    $query->whereNull('email')->orWhereNull('password');
                })
                ->orderBy('id')
                ->get(['id', 'telegram_id', 'email', 'password'])
                ->each(function ($user) {
                    $updates = [];

                    if ($user->email === null) {
                        $identifier = $user->telegram_id ?: $user->id;
                        $updates['email'] = "telegram_{$identifier}@telegram.local";
                    }

                    if ($user->password === null) {
                        $updates['password'] = Hash::make(Str::random(64));
                    }

                    if ($updates !== []) {
                        DB::table('users')->where('id', $user->id)->update($updates);
                    }
                });
        });
    }

    public function down(): void
    {
        // Generated credentials cannot be safely distinguished from real credentials.
    }
};
