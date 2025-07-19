<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\ArgumentResolver;

use App\Infrastructure\Symfony\Attribute\MapPayloadUuidsFromBase58;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final readonly class PayloadUuidsFromBase58ValueResolver implements ValueResolverInterface
{
    /**
     * @return array{0?:list<Uuid>}
     */
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        $attribute = $argument->getAttributesOfType(MapPayloadUuidsFromBase58::class)[0] ?? null;

        if ($attribute === null) {
            return [];
        }

        $uuids = array_values(array_map(
            static function (mixed $value): Uuid {
                if (is_string($value) === false) {
                    throw $this->invalidArgumentHttpException();
                }

                try {
                    $uuid = Uuid::fromBase58($value);
                } catch (\InvalidArgumentException) {
                    throw $this->invalidArgumentHttpException();
                }

                return $uuid;
            },
            $request->getPayload()->all()
        ));

        return [$uuids];
    }

    private function invalidArgumentHttpException(): NotFoundHttpException
    {
        return new NotFoundHttpException('Invalid argument.');
    }
}
