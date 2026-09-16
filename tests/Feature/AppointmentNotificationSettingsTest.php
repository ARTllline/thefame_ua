<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AppointmentNotificationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentNotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_profiles_and_direct_recipients_are_used_for_every_local_appointment(): void
    {
        $profile = User::factory()->create([
            'telegram_id' => 123456,
            'telegram_login' => 'manager_kyiv',
        ]);

        $setting = SiteSetting::query()
            ->where('key', AppointmentNotificationSettings::KEY)
            ->firstOrFail();
        $config = json_decode($setting->value, true);
        $config['telegram_recipients_migrated'] = true;
        $config['telegram_profile_ids'] = [$profile->id];
        $config['telegram_direct_recipients'] = ['-1001'];
        $setting->update(['value' => json_encode($config)]);

        $settings = app(AppointmentNotificationSettings::class);

        $this->assertTrue($settings->emailEnabled());
        $this->assertSame(['vedutenkonikita149@gmail.com'], $settings->emailRecipients());
        $this->assertSame(
            ['123456', '-1001'],
            $settings->telegramRecipients(),
        );
        $this->assertSame(
            ['enabled' => true, 'ua' => true, 'dubai' => false],
            $settings->telegramStatus('123456'),
        );
    }

    public function test_direct_personal_username_is_resolved_through_registered_bot_profile(): void
    {
        User::factory()->create([
            'telegram_id' => 123456,
            'telegram_login' => 'PersonalProfile',
        ]);

        $setting = SiteSetting::query()
            ->where('key', AppointmentNotificationSettings::KEY)
            ->firstOrFail();
        $config = json_decode($setting->value, true);
        $config['telegram_recipients_migrated'] = true;
        $config['telegram_profile_ids'] = [];
        $config['telegram_direct_recipients'] = ['@personalprofile'];
        $setting->update(['value' => json_encode($config)]);

        $settings = app(AppointmentNotificationSettings::class);

        $this->assertSame(['123456'], $settings->telegramRecipients());
    }

    public function test_intermediate_regional_format_is_read_as_current_site_recipients(): void
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

        $this->assertSame(
            ['111111', '222222', '-1001', '-1002'],
            app(AppointmentNotificationSettings::class)->telegramRecipients(),
        );
    }
}
