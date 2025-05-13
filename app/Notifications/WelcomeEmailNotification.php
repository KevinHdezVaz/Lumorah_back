<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeEmailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 ¡Bienvenid@ a Lumorah.ai, ' . $notifiable->nombre . '! 🎉')
            ->greeting('¡Hola, ' . $notifiable->nombre . '! 👋')
            ->line('')
            ->line('Nos alegra profundamente que hayas dado este paso importante hacia tu bienestar emocional. 🥰')
            ->line('')
            ->line('Ahora formas parte de un espacio seguro donde:')
            ->line('💬 **Tu guía terapéutico**: Exprésate sin prejuicios')
            ->line('🌱 **Crecimiento personal**: Descubrirás recursos para tu desarrollo emocional')
            ->line('🔒 **Confidencialidad total**: Sin juicios, sin vergüenza')
            ->line('')
            ->line('"El viaje más importante es el que hacemos hacia nosotros mismos" — ¿List@ para comenzar?')
            ->salutation('Con todo nuestro apoyo, El equipo de Lumorah.ai 💙');
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
