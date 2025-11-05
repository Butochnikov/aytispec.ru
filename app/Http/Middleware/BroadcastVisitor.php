<?php

namespace App\Http\Middleware;

use App\Events\VisitorEntered;
use App\Services\OnlineVisitors;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BroadcastVisitor
{
    public function __construct(
        private readonly OnlineVisitors $onlineVisitors,
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->hasSession()) {
            return $next($request);
        }

        $visitorId = $this->ensureVisitorId($request);
        $isTrackable = $this->isTrackableRequest($request);
        $shouldAnnounce = $isTrackable && ! $request->session()->has('visitor_announced');

        $response = $next($request);

        if (! $isTrackable) {
            return $response;
        }

        $payload = $this->buildPayload($request, $visitorId);

        $this->onlineVisitors->remember($visitorId, $payload);

        if ($shouldAnnounce) {
            $this->announceVisitor($request, $payload);
        }

        return $response;
    }

    /**
     * Создаёт/возвращает идентификатор посетителя в сессии.
     */
    protected function ensureVisitorId(Request $request): string
    {
        $session = $request->session();

        if (! $session->has('visitor_uuid')) {
            $session->put('visitor_uuid', (string) Str::uuid());
        }

        return $session->get('visitor_uuid');
    }

    /**
     * Формирует полезную нагрузку для события/хранилища.
     */
    protected function buildPayload(Request $request, string $visitorId): array
    {
        $session = $request->session();

        $color = $session->get('visitor_color', $this->colorFromIdentifier($visitorId));
        $session->put('visitor_color', $color);

        $user = Auth::user();
        $label = $user?->name ?: 'Гость';
        $initial = $this->determineInitial($user?->name ?? null);

        return [
            'id' => $visitorId,
            'label' => $label,
            'initial' => $initial,
            'color' => $color,
        ];
    }

    /**
     * Build a deterministic color from a string identifier.
     */
    protected function colorFromIdentifier(string $identifier): string
    {
        $hash = crc32($identifier);
        $hue = $hash % 360;

        return "hsl({$hue}, 70%, 55%)";
    }

    /**
     * Определяет, стоит ли обрабатывать текущий запрос.
     */
    protected function isTrackableRequest(Request $request): bool
    {
        if (config('broadcasting.default') === 'null') {
            return false;
        }

        if (! $request->isMethod('get')) {
            return false;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return false;
        }

        $path = $request->path();

        if (str_starts_with($path, 'livewire') || str_starts_with($path, 'broadcasting')) {
            return false;
        }

        return true;
    }

    /**
     * Determine the avatar initial from the provided name.
     */
    protected function determineInitial(?string $name): string
    {
        $initial = Str::upper(Str::substr($name ?? '', 0, 1));

        return $initial !== '' ? $initial : 'Г';
    }

    /**
     * Рассылает событие о новом посетителе.
     *
     * @param  array{id:string,label:string,initial:string,color:?string}  $payload
     */
    protected function announceVisitor(Request $request, array $payload): void
    {
        $request->session()->put('visitor_announced', true);

        event(new VisitorEntered(
            id: $payload['id'],
            label: $payload['label'],
            initial: $payload['initial'],
            color: $payload['color'],
            avatarUrl: null,
        ));
    }
}
