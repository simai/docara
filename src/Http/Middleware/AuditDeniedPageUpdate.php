<?php

declare(strict_types=1);

namespace Larena\Docara\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Docara\Audit\DocaraPageAuditEventDescriptor;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuditDeniedPageUpdate
{
    public function __construct(private AuditEventPipeline $audit)
    {
    }

    /**
     * @param Closure(Request): mixed $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (!$response instanceof Response
            || $response->getStatusCode() !== Response::HTTP_FORBIDDEN
            || !$request->isMethod('PUT')
            || !($request->routeIs('larena.docara.admin.pages.update') || $request->routeIs('larena.docara.admin.pages.blocks.update'))) {
            return $response;
        }

        $actor = trim((string) $request->attributes->get('larena_access_actor', ''));
        $slug = trim((string) $request->route('slug', ''));
        if ($actor === '' || $slug === '') {
            return $response;
        }

        $composition = $request->routeIs('larena.docara.admin.pages.blocks.update');
        $descriptor = $composition
            ? new \Larena\Docara\Audit\DocaraPageCompositionAuditEventDescriptor('update_denied')
            : new DocaraPageAuditEventDescriptor('update_denied');
        $payload = [
            'operation' => $composition ? 'composition_update_denied' : 'update_denied',
            'slug' => $slug,
            'status' => 'denied',
            'reason' => 'permission_denied',
        ];
        if ($composition) {
            $payload['block_count'] = 0;
            $payload['block_types'] = [];
        }

        $this->audit->route($descriptor, AuditEvent::create(
            sourcePackage: $descriptor->sourcePackage(),
            category: $descriptor->category(),
            type: $descriptor->type(),
            actor: $actor,
            subject: 'docara:page_slug:' . $slug,
            severity: $descriptor->severity(),
            retentionClass: $descriptor->retentionClass(),
            correlationId: Str::uuid()->toString(),
            payload: $payload,
        ));

        return $response;
    }
}
