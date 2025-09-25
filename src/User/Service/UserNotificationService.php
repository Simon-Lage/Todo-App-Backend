<?php

declare(strict_types=1);

namespace App\User\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class UserNotificationService
{
    public function __construct(private readonly MailerInterface $mailer, private readonly string $passwordResetUrlTemplate)
    {
    }

    public function sendTemporaryPassword(User $user, string $plainPassword): void
    {
        if ($user->getEmail() === null) {
            return;
        }

        $email = (new Email())
            ->to($user->getEmail())
            ->subject('Temporary password')
            ->text(sprintf('Hello %s,%sYour temporary password is: %s', $user->getName(), PHP_EOL.PHP_EOL, $plainPassword));

        $this->mailer->send($email);
    }

    public function sendPasswordResetLink(User $user, string $token): void
    {
        if ($user->getEmail() === null) {
            return;
        }

        $resetUrl = sprintf($this->passwordResetUrlTemplate, urlencode($token));
        $email = (new Email())
            ->to($user->getEmail())
            ->subject('Password reset request')
            ->text(sprintf('Hello %s,%sUse the following link to reset your password: %s', $user->getName(), PHP_EOL.PHP_EOL, $resetUrl));

        $this->mailer->send($email);
    }
}
