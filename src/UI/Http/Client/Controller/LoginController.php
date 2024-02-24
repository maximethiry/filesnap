<?php
declare(strict_types=1);

namespace App\UI\Http\Client\Controller;

use App\UI\Http\FilesnapAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route(
    name: 'client_login',
    methods: [
        Request::METHOD_GET,
        Request::METHOD_POST
    ]
)]
final class LoginController extends FilesnapAbstractController
{
    public function __invoke(
        AuthenticationUtils $authenticationUtils,
        #[MapQueryParameter(name: 'setup_finished')] ?bool $setupFinished
    ): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('client_user_gallery');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(parameters: [
            'last_username' => $lastUsername,
            'error' => $error,
            'setup_finished' => $setupFinished
        ]);
    }
}
