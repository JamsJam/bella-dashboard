<?php

namespace App\Application\Clothes\Mapper;

use App\Application\Clothes\DTO\BestsellerUpdateInput;
use Symfony\Component\HttpFoundation\Request;

final readonly class BestsellerRequestMapper
{
    public function map(Request $request): BestsellerUpdateInput
    {
        $payload = $this->jsonPayload($request);
        $ids = $request->request->all('ids') ?: ($payload['ids'] ?? []);
        $slugs = $request->request->all('slugs') ?: ($payload['slugs'] ?? []);

        if ((!is_array($slugs) || [] === $slugs) && isset($payload['slug'])) {
            $slugs = [$payload['slug']];
        }

        $mode = (string) ($request->request->get('mode') ?: ($payload['mode'] ?? 'add'));

        return new BestsellerUpdateInput(
            ids: $this->positiveIds($ids),
            slugs: $this->nonEmptySlugs($slugs),
            mode: in_array($mode, ['add', 'replace', 'remove'], true) ? $mode : 'add',
            pruneOverflow: $request->request->getBoolean('prune_overflow') || (bool) ($payload['pruneOverflow'] ?? false),
            csrfToken: (string) ($request->headers->get('X-CSRF-TOKEN') ?: $request->request->get('_csrf_token', '')),
            wantsTurboStream: str_contains((string) $request->headers->get('Accept'), 'text/vnd.turbo-stream.html'),
            wantsJson: $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept'), 'application/json'),
            isXmlHttpRequest: $request->isXmlHttpRequest(),
        );
    }

    /** @return array<string, mixed> */
    private function jsonPayload(Request $request): array
    {
        if (!str_contains((string) $request->headers->get('Content-Type'), 'application/json')) {
            return [];
        }

        $payload = json_decode($request->getContent(), true);

        return is_array($payload) ? $payload : [];
    }

    /** @return list<int> */
    private function positiveIds(mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $ids),
            static fn (int $id): bool => $id > 0,
        )));
    }

    /** @return list<string> */
    private function nonEmptySlugs(mixed $slugs): array
    {
        if (!is_array($slugs)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $slug): string => trim((string) $slug), $slugs),
        )));
    }
}
