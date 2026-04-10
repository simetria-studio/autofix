<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreErrorRequest;
use App\Models\Error;
use App\Services\ErrorIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ErrorController extends Controller
{
    public function __construct(
        private readonly ErrorIngestionService $ingestion
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 20), 100));

        $errors = Error::query()
            ->when(
                $request->query('server_name'),
                fn ($query, string $name) => $query->where('server_name', $name)
            )
            ->when(
                ($src = $this->validLogSourceFilter($request->query('log_source'))) !== null,
                fn ($query) => $query->where('log_source', $src)
            )
            ->latest()
            ->paginate($perPage);

        return response()->json($errors);
    }

    public function store(StoreErrorRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $payload = $this->ingestion->ingest(
            $validated['message'],
            $validated['server_name'] ?? null,
            $validated['log_source'] ?? Error::LOG_SOURCE_SERVER
        );

        $status = $payload['deduplicated'] ? 200 : 201;

        return response()->json($payload, $status);
    }

    private function validLogSourceFilter(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $allowed = config('autofix.valid_log_sources', ['server', 'application']);

        return in_array($value, $allowed, true) ? $value : null;
    }
}
