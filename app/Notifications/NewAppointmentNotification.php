<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Notifications\Channels\TelegramChannel;
use App\Services\AppointmentNotificationContent;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAppointmentNotification extends Notification
{
    public function __construct(
        public Appointment $appointment,
        private string $channel,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channel === 'telegram'
            ? [TelegramChannel::class]
            : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $content = new AppointmentNotificationContent($this->appointment);

        return (new MailMessage)
            ->subject($content->title())
            ->markdown('mail.appointments.new', ['content' => $content]);
    }

    public function toTelegram(object $notifiable): string
    {
        return (new AppointmentNotificationContent($this->appointment))->toTelegramHtml();
    }
}
