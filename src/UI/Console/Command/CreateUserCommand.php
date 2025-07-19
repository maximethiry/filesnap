<?php

declare(strict_types=1);

namespace App\UI\Console\Command;

use App\Application\Domain\User\UserRole;
use App\Application\UseCase\User\Create\CreateUserRequest;
use App\Application\UseCase\User\Create\CreateUserUseCase;
use App\Infrastructure\UseCase\User\CreateUserWithConfigurationUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-user')]
final class CreateUserCommand extends Command
{
    private const string ARGUMENT_EMAIL = 'email';
    private const string ARGUMENT_PASSWORD = 'password';
    private const string ARGUMENT_IS_ADMIN = 'is_admin';
    private const array ARGUMENT_IS_ADMIN_VALUES = [
        'true',
        'false',
    ];

    public function __construct(private readonly CreateUserWithConfigurationUseCase $createUserUseCase)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            self::ARGUMENT_EMAIL,
            InputArgument::REQUIRED,
            'The account email.'
        );

        $this->addArgument(
            self::ARGUMENT_PASSWORD,
            InputArgument::REQUIRED,
            'The account password.'
        );

        $this->addArgument(
            self::ARGUMENT_IS_ADMIN,
            InputArgument::OPTIONAL,
            'Tha account privileges.',
            'false',
            self::ARGUMENT_IS_ADMIN_VALUES
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isAdminArgument = $input->getArgument(self::ARGUMENT_IS_ADMIN);

        if (
            is_string($isAdminArgument) === false
            || in_array($isAdminArgument, self::ARGUMENT_IS_ADMIN_VALUES, true) === false
        ) {
            $output->writeln(sprintf(
                'The %s argument is invalid. It must be one of theses values : %s.',
                self::ARGUMENT_IS_ADMIN,
                implode(', ', self::ARGUMENT_IS_ADMIN_VALUES)
            ));

            return Command::FAILURE;
        }

        $roles = [UserRole::User];

        if ($isAdminArgument === 'true') {
            $roles[] = UserRole::Admin;
        }

        $emailArgument = $input->getArgument(self::ARGUMENT_EMAIL);
        $passwordArgument = $input->getArgument(self::ARGUMENT_PASSWORD);

        if (is_string($emailArgument) === false) {
            $output->writeln('The email parameter must be a string.');

            return Command::FAILURE;
        }

        if (strlen($emailArgument) === 0) {
            $output->writeln('The email parameter must not be empty');

            return Command::FAILURE;
        }

        if (is_string($passwordArgument) === false) {
            $output->writeln('The password parameter must be a string.');

            return Command::FAILURE;
        }

        if (strlen($passwordArgument) === 0) {
            $output->writeln('The password parameter must not be empty');

            return Command::FAILURE;
        }

        ($this->createUserUseCase)(new CreateUserRequest($emailArgument, $passwordArgument, $roles));

        return Command::SUCCESS;
    }
}
