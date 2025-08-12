<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * ./bin/console app:create-user admin@test.com admin --role=ROLE_ADMIN.
 */
#[AsCommand(
    name: 'app:create-user',
    description: 'Create new user',
)]
class AppCreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email address for the new user')
            ->addArgument('password', InputArgument::REQUIRED, 'The plain‐text password')
            ->addOption(
                'role',
                'r',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'One or more roles (e.g. --role=ROLE_ADMIN --role=ROLE_HELPDESK)',
                ['ROLE_ADMIN']
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');
        $plainPwd = (string) $input->getArgument('password');
        /** @var array<int, string> $roles */
        $roles = $input->getOption('role');

        $existing = $this->em
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existing) {
            $io->error(\sprintf('A user with email "%s" already exists.', $email));

            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $hashed = $this->passwordHasher->hashPassword($user, $plainPwd);
        $user->setPassword($hashed);
        $this->em->persist($user);
        $this->em->flush();

        $io->success(\sprintf('Admin user "%s" created with roles: %s', $email, implode(', ', $roles)));

        return Command::SUCCESS;
    }
}
