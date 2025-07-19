<?php

declare(strict_types=1);

namespace App\UI\Http\Client\Controller\User;

use App\Application\Domain\User\Exception\AlreadyExistingUserWithEmail;
use App\Application\Domain\User\Exception\EmailIsUserCurrentEmail;
use App\Application\UseCase\User\UpdateEmailById\UpdateUserEmailByIdRequest;
use App\Application\UseCase\User\UpdateEmailById\UpdateUserEmailByIdUseCase;
use App\Application\UseCase\User\UpdatePasswordById\UpdateUserPasswordByIdRequest;
use App\Application\UseCase\User\UpdatePasswordById\UpdateUserPasswordByIdUseCase;
use App\Infrastructure\Symfony\Form\UpdateEmailType;
use App\Infrastructure\Symfony\Form\UpdatePasswordType;
use App\Infrastructure\Symfony\Form\UserConfigurationType;
use App\Infrastructure\Symfony\Security\AuthenticationService;
use App\Infrastructure\UseCase\UserConfiguration\Get\GetUserConfigurationRequest;
use App\Infrastructure\UseCase\UserConfiguration\Get\GetUserConfigurationUseCase;
use App\UI\Http\FilesnapAbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/u/settings',
    name: 'client_user_settings',
    methods: [
        Request::METHOD_GET,
        Request::METHOD_POST,
    ],
)]
final class SettingsController extends FilesnapAbstractController
{
    private ?Request $request = null;

    /** @var array<string, FormInterface> */
    private array $forms = [];

    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly UpdateUserEmailByIdUseCase $updateUserEmailByIdUseCase,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UpdateUserPasswordByIdUseCase $updateUserPasswordByIdUseCase,
        private readonly GetUserConfigurationUseCase $getUserConfigurationUseCase,
    ) {
    }

    /**
     * @throws \ReflectionException
     */
    public function __invoke(Request $request): Response
    {
        $this->request = $request;

        $useCaseResponse = ($this->getUserConfigurationUseCase)(
            new GetUserConfigurationRequest($this->getAuthenticatedUser()->getId())
        );
        $userConfiguration = $useCaseResponse->userConfiguration;

        $updateEmailForm = $this->createForm(UpdateEmailType::class);
        $updatePasswordForm = $this->createForm(UpdatePasswordType::class);
        $userConfigurationForm = $this->createForm(UserConfigurationType::class, [
            'formats' => $userConfiguration->enabledConversionFormats,
            'snapExpirationDaysInterval' => $userConfiguration->snapExpirationInterval?->d ?? 30
        ]);

        $this->addForm('updateEmailForm', $updateEmailForm);
        $this->addForm('updatePasswordForm', $updatePasswordForm);
        $this->addForm('userConfigurationForm', $userConfigurationForm);

        return $this->handleForms() ?? $this->view($this->forms);
    }

    /**
     * @throws \ReflectionException
     */
    private function handleForms(): ?Response
    {
        $reflectionClass = new \ReflectionClass(self::class);

        /** @var list<\ReflectionMethod> $handlingMethods */
        $handlingMethods = array_values(array_filter(
            $reflectionClass->getMethods(\ReflectionMethod::IS_PRIVATE),
            static fn (\ReflectionMethod $method): bool => preg_match('/handle(.+)Form$/', $method->getName()) === 1
        ));

        foreach ($handlingMethods as $method) {
            /** @var Response|null $view */
            $view = $method->invoke($this);

            if ($view !== null) {
                return $view;
            }
        }

        return null;
    }

    private function addForm(string $id, FormInterface $form): void
    {
        $this->forms[$id] = $form;
    }

    private function getForm(string $id): ?FormInterface
    {
        return $this->forms[$id] ?? null;
    }

    /** @phpstan-ignore method.unused */
    private function handleUpdateEmailForm(): ?Response
    {
        $updateEmailForm = $this->getForm('updateEmailForm');

        if ($updateEmailForm === null) {
            throw new \RuntimeException('UpdateEmailForm cannot be empty');
        }

        $updateEmailForm->handleRequest($this->request);
        $formCanBeProcessed = $updateEmailForm->isSubmitted() && $updateEmailForm->isValid();

        if ($formCanBeProcessed === false) {
            return null;
        }

        $newEmail = $updateEmailForm->get('email')->getData();

        if (is_string($newEmail) === false) {
            throw new BadRequestHttpException();
        }

        $user = $this->getAuthenticatedUser();

        try {
            ($this->updateUserEmailByIdUseCase)(new UpdateUserEmailByIdRequest($user->getId(), $newEmail));
        } catch (AlreadyExistingUserWithEmail) {
            $message = sprintf('The email "%s" is already in use.', $newEmail);
            $updateEmailForm->get('email')->addError(new FormError($message));

            return $this->view($this->forms);
        } catch (EmailIsUserCurrentEmail) {
            $message = sprintf('The email "%s" is your current email.', $newEmail);
            $updateEmailForm->get('email')->addError(new FormError($message));

            return $this->view($this->forms);
        }

        $this->authenticationService->login($newEmail);

        return $this->redirectToRoute('client_user_settings');
    }

    /** @phpstan-ignore method.unused */
    private function handleUpdatePasswordForm(): ?Response
    {
        $updatePasswordForm = $this->getForm('updatePasswordForm');

        if ($updatePasswordForm === null) {
            throw new \RuntimeException('UpdatePasswordForm cannot be empty');
        }

        $updatePasswordForm->handleRequest($this->request);
        $formCanBeProcessed = $updatePasswordForm->isSubmitted() && $updatePasswordForm->isValid();

        if ($formCanBeProcessed === false) {
            return null;
        }

        $currentPassword = $updatePasswordForm->get('currentPassword')->getData();
        $newPassword = $updatePasswordForm->get('newPassword')->getData();

        if (is_string($currentPassword) === false || is_string($newPassword) === false) {
            throw new BadRequestHttpException();
        }

        $user = $this->getAuthenticatedUser();

        if ($this->passwordHasher->isPasswordValid($user, $currentPassword) === false) {
            throw new AccessDeniedHttpException();
        }

        ($this->updateUserPasswordByIdUseCase)(new UpdateUserPasswordByIdRequest(
            $user->getId(),
            $newPassword,
            false
        ));

        $this->authenticationService->login($user->getUserIdentifier());

        return $this->redirectToRoute('client_user_settings');
    }
}
