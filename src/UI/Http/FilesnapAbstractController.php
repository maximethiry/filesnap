<?php

declare(strict_types=1);

namespace App\UI\Http;

use App\Application\Domain\User\User;
use App\Infrastructure\Symfony\Security\Entity\SecurityUser;
use App\Infrastructure\UseCase\UserConfiguration\Get\GetUserConfigurationUseCase;
use App\Infrastructure\UserConfiguration\UserConfiguration;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

abstract class FilesnapAbstractController extends AbstractController
{
    protected function getAuthenticatedUser(): SecurityUser
    {
        /** @var SecurityUser|null $user */
        $user = $this->getUser();

        if ($user === null) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'No user authenticated');
        }

        return $user;
    }

    protected function emptyResponse(): Response
    {
        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function view(array $parameters = [], ?Response $response = null): Response
    {
        $routeAttributes = new \ReflectionClass(static::class)->getAttributes(Route::class);

        if ($routeAttributes === []) {
            throw new \RuntimeException(sprintf('No %s attribute for %s.', Route::class, static::class));
        }

        if (count($routeAttributes) > 1) {
            throw new \RuntimeException(sprintf('Multiple routes defined for %s, you must render the view with the "render" method.', static::class));
        }

        $routeName = $routeAttributes[0]->getArguments()['name'] ?? null;

        if ($routeName === null) {
            throw new \RuntimeException(sprintf('No route name argument defined on %s attribute in %s.', Route::class, static::class));
        }

        return parent::render(
            sprintf('%s.html.twig', str_replace('_', '/', $routeName)),
            $parameters,
            $response
        );
    }
}
