<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:fix:jwt-keys',
    description: 'Fix missing JWT keys by regenerating them',
)]
final class FixJwtKeysCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $jwtDir = '/var/jwt';
        $privateKey = "$jwtDir/private.pem";
        $publicKey = "$jwtDir/public.pem";

        if (file_exists($privateKey) && file_exists($publicKey)) {
            $io->info('JWT keys already exist');
            return Command::SUCCESS;
        }

        $io->warning('JWT keys are missing. Generating new keys...');

        if (!is_dir($jwtDir)) {
            mkdir($jwtDir, 0755, true);
        }

        $process = new Process([
            'php',
            'bin/console',
            'lexik:jwt:generate-keypair',
            '--overwrite',
        ]);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('Failed to generate JWT keys: ' . $process->getErrorOutput());
            return Command::FAILURE;
        }

        $configJwtDir = __DIR__ . '/../../config/jwt';
        if (is_dir($configJwtDir)) {
            $configPrivateKey = "$configJwtDir/private.pem";
            $configPublicKey = "$configJwtDir/public.pem";

            if (file_exists($configPrivateKey) && file_exists($configPublicKey)) {
                if (!copy($configPrivateKey, $privateKey)) {
                    $io->error('Failed to copy private key to /var/jwt/');
                    return Command::FAILURE;
                }
                if (!copy($configPublicKey, $publicKey)) {
                    $io->error('Failed to copy public key to /var/jwt/');
                    return Command::FAILURE;
                }

                chmod($privateKey, 0600);
                chmod($publicKey, 0644);

                $io->success('JWT keys generated and moved to /var/jwt/');
                $io->note('Please restart the PHP container for the changes to take effect.');
                return Command::SUCCESS;
            }
        }

        $io->error('JWT keys were not generated in config/jwt/');
        return Command::FAILURE;
    }
}

