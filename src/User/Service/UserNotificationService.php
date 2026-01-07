<?php

declare(strict_types=1);

namespace App\User\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class UserNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $passwordResetUrlTemplate,
        private readonly string $fromAddress,
    )
    {
    }

    public function sendTemporaryPassword(User $user, string $plainPassword): void
    {
        if ($user->getEmail() === null) {
            return;
        }

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($user->getEmail())
            ->subject('Temporäres Passwort')
            ->text(sprintf('Hallo %s,%sIhr temporäres Passwort lautet: %s', $user->getName(), PHP_EOL.PHP_EOL, $plainPassword));

        $this->mailer->send($email);
    }

    public function sendPasswordResetLink(User $user, string $token): void
    {
        if ($user->getEmail() === null) {
            return;
        }

        $resetUrl = sprintf($this->passwordResetUrlTemplate, urlencode($token));
        $email = (new Email())
            ->from($this->fromAddress)
            ->to($user->getEmail())
            ->subject('Passwort zurücksetzen')
            ->text(sprintf('Hallo %s,%sBitte nutzen Sie folgenden Link, um Ihr Passwort zurückzusetzen:%s%s', $user->getName(), PHP_EOL.PHP_EOL, PHP_EOL, $resetUrl));

        $this->mailer->send($email);
    }
}
