<?php

declare(strict_types=1);

namespace Larena\Docara\Contracts;

interface DocaraGateway
{
    public function describePage(string $pageRef): ?DocumentationPage;

    public function projectForSearch(string $pageRef, string $actorRef): ?SearchProjection;
}
