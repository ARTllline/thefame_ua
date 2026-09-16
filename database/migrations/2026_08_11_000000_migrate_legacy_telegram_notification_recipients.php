<?php

use App\Services\AppointmentNotificationSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('site_settings')
            ->where('key', AppointmentNotificationSettings::KEY)
            ->first();

        if (! $setting) {
            return;
        }

        $config = json_decode($setting->value, true) ?: [];

        $currentProfileIds = $config['telegram_profile_ids'] ?? [];
        $currentDirectRecipients = $config['telegram_direct_recipients'] ?? [];

        if (($config['telegram_recipients_migrated'] ?? false)
            && is_array($currentProfileIds)
            && is_array($currentDirectRecipients)
            && array_is_list($currentProfileIds)
            && array_is_list($currentDirectRecipients)) {
            return;
        }

        $profileIds = [];
        $directRecipients = [];
        $siteRegion = $this->siteRegion();
        $oldRecipients = is_array($config['telegram_recipients'] ?? null)
            ? $config['telegram_recipients']
            : [];
        $hasCentralSelection = (bool) ($config['telegram_recipients_migrated'] ?? false)
            || $this->hasCentralSelection($currentProfileIds, $currentDirectRecipients);

        if ($hasCentralSelection) {
            $profileIds = $this->localValues($currentProfileIds, $siteRegion);
            $directRecipients = $this->localValues($currentDirectRecipients, $siteRegion);
        } elseif ($oldRecipients !== []) {
            foreach ($oldRecipients as $identifier => $scope) {
                $scope = in_array($scope, ['all', 'ua', 'dubai'], true) ? $scope : 'all';

                if (! in_array($scope, ['all', $siteRegion], true)) {
                    continue;
                }

                $profile = $this->findTelegramProfile((string) $identifier);

                if ($profile) {
                    $profileIds[] = $profile->id;
                } else {
                    $directRecipients[] = (string) $identifier;
                }
            }
        } else {
            $subscriptionColumn = $siteRegion === 'dubai'
                ? 'is_appointment_dubai'
                : 'is_appointment_ua';

            DB::table('users')
                ->whereNotNull('telegram_id')
                ->where('telegram_id', '!=', 0)
                ->where($subscriptionColumn, true)
                ->pluck('id')
                ->each(function ($id) use (&$profileIds) {
                    $profileIds[] = $id;
                });
        }

        $config['telegram_profile_ids'] = array_values(array_unique(array_map('intval', $profileIds)));
        $config['telegram_direct_recipients'] = array_values(array_unique(array_map('strval', $directRecipients)));
        $config['telegram_recipients_migrated'] = true;

        DB::table('site_settings')
            ->where('id', $setting->id)
            ->update([
                'value' => json_encode($config, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $setting = DB::table('site_settings')
            ->where('key', AppointmentNotificationSettings::KEY)
            ->first();

        if (! $setting) {
            return;
        }

        $config = json_decode($setting->value, true) ?: [];
        Arr::forget($config, ['telegram_profile_ids', 'telegram_direct_recipients']);
        $config['telegram_recipients_migrated'] = false;

        DB::table('site_settings')
            ->where('id', $setting->id)
            ->update([
                'value' => json_encode($config, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    private function findTelegramProfile(string $identifier): ?object
    {
        if (preg_match('/^-?\d+$/', $identifier)) {
            return DB::table('users')->where('telegram_id', $identifier)->first(['id']);
        }

        if (str_starts_with($identifier, '@')) {
            return DB::table('users')
                ->whereRaw('LOWER(telegram_login) = ?', [mb_strtolower(ltrim($identifier, '@'))])
                ->first(['id']);
        }

        return null;
    }

    private function siteRegion(): string
    {
        return mb_strtolower((string) config('notifications.appointments.site_region')) === 'dubai'
            ? 'dubai'
            : 'ua';
    }

    private function hasCentralSelection(mixed $profileIds, mixed $directRecipients): bool
    {
        return collect(is_array($profileIds) ? $profileIds : [])->flatten()->filter()->isNotEmpty()
            || collect(is_array($directRecipients) ? $directRecipients : [])->flatten()->filter()->isNotEmpty();
    }

    /** @return array<int, mixed> */
    private function localValues(mixed $values, string $siteRegion): array
    {
        if (! is_array($values)) {
            return [];
        }

        if (array_is_list($values)) {
            return $values;
        }

        return collect(['all', $siteRegion])
            ->flatMap(fn ($scope) => is_array($values[$scope] ?? null) ? $values[$scope] : [])
            ->values()
            ->all();
    }
};
