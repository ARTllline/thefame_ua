<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AppointmentNotificationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyTelegramRecipientMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_current_sites_legacy_switches_are_migrated(): void
    {
        config(['notifications.appointments.site_region' => 'ua']);

        $profile = User::factory()->create([
            'telegram_id' => 123456,
            'telegram_login' => 'legacy_manager',
        ]);
        $profile->forceFill(['is_appointment_ua' => true, 'is_appointment_dubai' => true])->save();

        $dubaiOnlyProfile = User::factory()->create([
            'telegram_id' => 654321,
        ]);
        $dubaiOnlyProfile->forceFill(['is_appointment_ua' => false, 'is_appointment_dubai' => true])->save();

        $setting = SiteSetting::query()
            ->where('key', AppointmentNotificationSettings::KEY)
            ->firstOrFail();
        $config = json_decode($setting->value, true);
        $config['telegram_recipients'] = [];
        $config['telegram_recipients_migrated'] = false;
        $setting->update(['value' => json_encode($config)]);

        $migration = require database_path('migrations/2026_08_11_000000_migrate_legacy_telegram_notification_recipients.php');
        $migration->up();

        $migrated = json_decode($setting->fresh()->value, true);

        $this->assertTrue($migrated['telegram_recipients_migrated']);
        $this->assertSame([$profile->id], $migrated['telegram_profile_ids']);
        $this->assertSame([], $migrated['telegram_direct_recipients']);
    }

    public function test_previous_central_identifiers_are_migrated_without_readding_legacy_switches(): void
    {
        config(['notifications.appointments.site_region' => 'ua']);

        $selectedProfile = User::factory()->create([
            'telegram_id' => 123456,
            'telegram_login' => 'selected_manager',
        ]);
        $staleProfile = User::factory()->create([
            'telegram_id' => 654321,
            'telegram_login' => 'stale_legacy_manager',
        ]);
        $staleProfile->forceFill(['is_appointment_ua' => true, 'is_appointment_dubai' => true])->save();

        $setting = SiteSetting::query()
            ->where('key', AppointmentNotificationSettings::KEY)
            ->firstOrFail();
        $config = json_decode($setting->value, true);
        $config['telegram_recipients'] = [
            '@selected_manager' => 'ua',
            '-100999' => 'dubai',
        ];
        $config['telegram_recipients_migrated'] = false;
        $setting->update(['value' => json_encode($config)]);

        $migration = require database_path('migrations/2026_08_11_000000_migrate_legacy_telegram_notification_recipients.php');
        $migration->up();

        $migrated = json_decode($setting->fresh()->value, true);

        $this->assertSame([$selectedProfile->id], $migrated['telegram_profile_ids']);
        $this->assertSame([], $migrated['telegram_direct_recipients']);
    }

    public function test_dubai_site_migrates_only_dubai_switches(): void
    {
        config(['notifications.appointments.site_region' => 'dubai']);

        $uaProfile = User::factory()->create([
            'telegram_id' => 123456,
        ]);
        $uaProfile->forceFill(['is_appointment_ua' => true, 'is_appointment_dubai' => false])->save();

        $dubaiProfile = User::factory()->create([
            'telegram_id' => 654321,
        ]);
        $dubaiProfile->forceFill(['is_appointment_ua' => false, 'is_appointment_dubai' => true])->save();

        $setting = SiteSetting::query()
            ->where('key', AppointmentNotificationSettings::KEY)
            ->firstOrFail();
        $config = json_decode($setting->value, true);
        $config['telegram_recipients'] = [];
        $config['telegram_recipients_migrated'] = false;
        $setting->update(['value' => json_encode($config)]);

        $migration = require database_path('migrations/2026_08_11_000000_migrate_legacy_telegram_notification_recipients.php');
        $migration->up();

        $migrated = json_decode($setting->fresh()->value, true);

        $this->assertSame([$dubaiProfile->id], $migrated['telegram_profile_ids']);
        $this->assertSame([], $migrated['telegram_direct_recipients']);
    }

    public function test_intermediate_regional_settings_are_flattened_for_the_current_site(): void
    {
        config(['notifications.appointments.site_region' => 'dubai']);

        $allProfile = User::factory()->create(['telegram_id' => 111111]);
        $uaProfile = User::factory()->create(['telegram_id' => 222222]);
        $dubaiProfile = User::factory()->create(['telegram_id' => 333333]);

        $setting = SiteSetting::query()
            ->where('key', AppointmentNotificationSettings::KEY)
            ->firstOrFail();
        $config = json_decode($setting->value, true);
        $config['telegram_recipients_migrated'] = true;
        $config['telegram_profile_ids'] = [
            'all' => [$allProfile->id],
            'ua' => [$uaProfile->id],
            'dubai' => [$dubaiProfile->id],
        ];
        $config['telegram_direct_recipients'] = [
            'all' => ['-1001'],
            'ua' => ['-1002'],
            'dubai' => ['-1003'],
        ];
        $setting->update(['value' => json_encode($config)]);

        $migration = require database_path('migrations/2026_08_11_000000_migrate_legacy_telegram_notification_recipients.php');
        $migration->up();

        $migrated = json_decode($setting->fresh()->value, true);

        $this->assertSame([$allProfile->id, $dubaiProfile->id], $migrated['telegram_profile_ids']);
        $this->assertSame(['-1001', '-1003'], $migrated['telegram_direct_recipients']);
    }

    public function test_follow_up_migration_repairs_already_migrated_regional_settings(): void
    {
        config(['notifications.appointments.site_region' => 'ua']);

        $allProfile = User::factory()->create(['telegram_id' => 111111]);
        $uaProfile = User::factory()->create(['telegram_id' => 222222]);
        $dubaiProfile = User::factory()->create(['telegram_id' => 333333]);

        $setting = SiteSetting::query()
            ->where('key', AppointmentNotificationSettings::KEY)
            ->firstOrFail();
        $config = json_decode($setting->value, true);
        $config['telegram_recipients_migrated'] = true;
        $config['telegram_profile_ids'] = [
            'all' => [$allProfile->id],
            'ua' => [$uaProfile->id],
            'dubai' => [$dubaiProfile->id],
        ];
        $config['telegram_direct_recipients'] = [
            'all' => ['-1001'],
            'ua' => ['-1002'],
            'dubai' => ['-1003'],
        ];
        $setting->update(['value' => json_encode($config)]);

        $migration = require database_path('migrations/2026_08_11_124000_flatten_site_telegram_notification_recipients.php');
        $migration->up();

        $migrated = json_decode($setting->fresh()->value, true);

        $this->assertSame([$allProfile->id, $uaProfile->id], $migrated['telegram_profile_ids']);
        $this->assertSame(['-1001', '-1002'], $migrated['telegram_direct_recipients']);
    }
}
