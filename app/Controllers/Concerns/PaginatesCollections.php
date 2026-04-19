<?php

namespace App\Controllers\Concerns;

trait PaginatesCollections
{
    /**
     * @return array{items: array<int, mixed>, pagination: array<string, mixed>}
     */
    protected function paginateCollection(array $items, string $pageParam = 'page', string $perPageParam = 'per_page'): array
    {
        $query = $this->request->getGet();
        $requestedPerPage = (int) ($query[$perPageParam] ?? $this->perPageOptions[0]);
        $perPage = in_array($requestedPerPage, $this->perPageOptions, true) ? $requestedPerPage : $this->perPageOptions[0];

        $total = count($items);
        $lastPage = max(1, (int) ceil(max($total, 1) / $perPage));
        $page = max(1, (int) ($query[$pageParam] ?? 1));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;

        unset($query[$pageParam], $query[$perPageParam]);

        $buildUrl = function (int $targetPage, ?int $targetPerPage = null) use ($query, $pageParam, $perPageParam): string {
            $params = $query;
            $params[$pageParam] = $targetPage;
            $params[$perPageParam] = $targetPerPage ?? ($params[$perPageParam] ?? null);

            if (($params[$perPageParam] ?? null) === null) {
                unset($params[$perPageParam]);
            }

            $url = current_url();

            return $params === [] ? $url : $url . '?' . http_build_query($params);
        };

        $links = [];
        $startPage = max(1, $page - 2);
        $endPage = min($lastPage, $page + 2);

        for ($cursor = $startPage; $cursor <= $endPage; $cursor++) {
            $links[] = [
                'label' => (string) $cursor,
                'url' => $buildUrl($cursor, $perPage),
                'active' => $cursor === $page,
            ];
        }

        return [
            'items' => array_values(array_slice($items, $offset, $perPage)),
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'lastPage' => $lastPage,
                'start' => $total === 0 ? 0 : $offset + 1,
                'end' => min($offset + $perPage, $total),
                'pageParam' => $pageParam,
                'perPageParam' => $perPageParam,
                'query' => $query,
                'options' => $this->perPageOptions,
                'hasPrev' => $page > 1,
                'hasNext' => $page < $lastPage,
                'prevUrl' => $page > 1 ? $buildUrl($page - 1, $perPage) : null,
                'nextUrl' => $page < $lastPage ? $buildUrl($page + 1, $perPage) : null,
                'links' => $links,
            ],
        ];
    }
}
