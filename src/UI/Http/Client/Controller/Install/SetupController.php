<?php

declare(strict_types=1);

namespace App\UI\Http\Client\Controller\Install;

use App\Application\Domain\User\UserRole;
use App\Application\UseCase\User\Create\CreateUserRequest;
use App\Application\UseCase\User\Create\CreateUserUseCase;
use App\Infrastructure\Symfony\Form\SetupType;
use App\Infrastructure\Symfony\Security\AuthenticationService;
use App\UI\Http\FilesnapAbstractController;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/setup',
    name: 'client_install_setup',
    methods: [
        Request::METHOD_GET,
        Request::METHOD_POST,
    ]
)]
final class SetupController extends FilesnapAbstractController
{
    private readonly Application $application;
    private readonly ArrayInput $doctrineDatabaseCreateCommand;
    private readonly ArrayInput $doctrineMigrationsMigrateCommand;
    private ?string $error = null;

    public function __construct(
        private readonly CreateUserUseCase $createUserUseCase,
        private readonly KernelInterface $kernel,
        private readonly AuthenticationService $authenticationService,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        $this->application = new Application($this->kernel);
        $this->application->setAutoExit(false);

        $this->doctrineDatabaseCreateCommand = new ArrayInput(['command' => 'doctrine:database:create']);
        $this->doctrineMigrationsMigrateCommand = new ArrayInput(['command' => 'doctrine:migrations:migrate']);
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[Autowire(param: 'app.project_directory')] string $projectDirectory,
        Request $request,
    ): Response {
        $setupFile = sprintf('%s/.setup', $projectDirectory);
        $installAuthorized = $this->filesystem->exists($setupFile);

        if ($installAuthorized === false) {
            return $this->redirectToRoute('client_login');
        }

        $form = $this->createForm(SetupType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (defined('STDIN') === false) {
                define('STDIN', fopen('php://stdin', 'rb'));
            }

            /** @var array{adminEmail:non-empty-string, adminPlainPassword:non-empty-string, dbAlreadyCreated:bool} $postedData */
            $postedData = $form->getData();

            if ($postedData['dbAlreadyCreated'] === false) {
                $this->runCommand($this->doctrineDatabaseCreateCommand);
            }

            $this->runCommand($this->doctrineMigrationsMigrateCommand);
            $this->createAdminUser($postedData['adminEmail'], $postedData['adminPlainPassword']);

            if ($this->error === null) {
                $this->filesystem->remove($setupFile);
                $this->authenticationService->login($postedData['adminEmail']);

                return $this->redirectToRoute('client_user_gallery');
            }
        }

        return $this->view([
            'form' => $form,
            'error' => $this->error,
        ]);
    }

    private function runCommand(ArrayInput $command): void
    {
        if ($this->error !== null) {
            return;
        }

        try {
            $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL);
            $executionStatus = $this->application->run($command, $output);

            if ($executionStatus !== 0) {
                $this->error = $output->fetch();
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    /**
     * @param non-empty-string $email
     * @param non-empty-string $plainPassword
     * @return void
     */
    private function createAdminUser(string $email, string $plainPassword): void
    {
        if ($this->error !== null) {
            return;
        }

        try {
            ($this->createUserUseCase)(new CreateUserRequest(
                $email,
                $plainPassword,
                [UserRole::User, UserRole::Admin]
            ));
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }
}
