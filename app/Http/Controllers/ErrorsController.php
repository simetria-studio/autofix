<?php

namespace App\Http\Controllers;

use App\Models\Error;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ErrorsController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->value();

        $source = $request->string('source')->value();
        $allowedSources = config('autofix.valid_log_sources', ['server', 'application']);

        $errors = Error::query()
            ->when(
                $request->filled('server_name'),
                fn ($query) => $query->where('server_name', $request->string('server_name'))
            )
            ->when(
                $source !== '' && in_array($source, $allowedSources, true),
                fn ($query) => $query->where('log_source', $source)
            )
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';

                return $query->where('message', 'like', $like);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('errors.index', [
            'errors' => $errors,
            'logSourceLabels' => config('autofix.log_source_labels', []),
            'activeSource' => in_array($source, $allowedSources, true) ? $source : '',
        ]);
    }

    public function show(Request $request, Error $error): View
    {
        return view('errors.show', [
            'error' => $error,
            'backUrl' => $this->safeListReturnUrl($request->query('return')),
        ]);
    }

    private function safeListReturnUrl(?string $return): string
    {
        if ($return !== null && str_starts_with($return, '/') && ! str_starts_with($return, '//')) {
            return $return;
        }

        return route('errors.index');
    }
}
