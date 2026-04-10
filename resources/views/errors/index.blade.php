@extends('layouts.app')

@section('title', 'Erros · '.config('app.name'))

@section('content')
    @php
        $filterBase = array_filter(
            [
                'server_name' => request('server_name'),
                'q' => request('q'),
            ],
            fn ($v) => $v !== null && $v !== ''
        );
    @endphp
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-400/90">Autofix</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-white">Erros capturados</h1>
                <p class="mt-1 max-w-xl text-sm text-zinc-400">
                    Mensagens recebidas pela API e sugestões geradas automaticamente.
                </p>
            </div>
            <a
                href="{{ url('/') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg border border-zinc-700 bg-zinc-900 px-4 py-2 text-sm font-medium text-zinc-200 transition hover:border-zinc-600 hover:bg-zinc-800"
            >
                Início
            </a>
        </header>

        <nav
            class="mb-6 flex flex-wrap gap-2 rounded-xl border border-zinc-800 bg-zinc-900/40 p-2"
            aria-label="Origem do log"
        >
            <a
                href="{{ route('errors.index', $filterBase) }}"
                class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $activeSource === '' ? 'bg-emerald-600 text-white' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200' }}"
            >
                Todos
            </a>
            @foreach ($logSourceLabels as $srcKey => $srcLabel)
                <a
                    href="{{ route('errors.index', array_merge($filterBase, ['source' => $srcKey])) }}"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $activeSource === $srcKey ? 'bg-emerald-600 text-white' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200' }}"
                >
                    {{ $srcLabel }}
                </a>
            @endforeach
        </nav>

        <form
            method="get"
            action="{{ route('errors.index') }}"
            class="mb-6 flex flex-col gap-3 rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 sm:flex-row sm:flex-wrap sm:items-end"
        >
            @if ($activeSource !== '')
                <input type="hidden" name="source" value="{{ $activeSource }}">
            @endif
            <div class="min-w-[12rem] flex-1">
                <label for="server_name" class="mb-1 block text-xs font-medium uppercase tracking-wider text-zinc-500">
                    Servidor
                </label>
                <input
                    type="text"
                    name="server_name"
                    id="server_name"
                    value="{{ request('server_name') }}"
                    placeholder="ex.: web01"
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white placeholder:text-zinc-600 focus:border-emerald-500/60 focus:outline-none focus:ring-1 focus:ring-emerald-500/40"
                >
            </div>
            <div class="min-w-[12rem] flex-[2]">
                <label for="q" class="mb-1 block text-xs font-medium uppercase tracking-wider text-zinc-500">
                    Buscar na mensagem
                </label>
                <input
                    type="text"
                    name="q"
                    id="q"
                    value="{{ request('q') }}"
                    placeholder="Trecho do log..."
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white placeholder:text-zinc-600 focus:border-emerald-500/60 focus:outline-none focus:ring-1 focus:ring-emerald-500/40"
                >
            </div>
            <div class="flex gap-2">
                <button
                    type="submit"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500"
                >
                    Filtrar
                </button>
                <a
                    href="{{ route('errors.index') }}"
                    class="rounded-lg border border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:bg-zinc-800"
                >
                    Limpar
                </a>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900/30 shadow-xl shadow-black/20">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-800 bg-zinc-900/80 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Servidor</th>
                            <th class="px-4 py-3 whitespace-nowrap">Origem</th>
                            <th class="px-4 py-3">Mensagem</th>
                            <th class="px-4 py-3">Sugestão</th>
                            <th class="px-4 py-3 text-center">Ocorr.</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 whitespace-nowrap">Quando</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">Detalhe</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/80">
                        @forelse ($errors as $error)
                            <tr class="transition hover:bg-zinc-800/40">
                                <td class="px-4 py-3 font-mono text-xs text-zinc-500">#{{ $error->id }}</td>
                                <td class="px-4 py-3 text-zinc-300">
                                    {{ $error->server_name ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md bg-zinc-800 px-2 py-0.5 text-xs text-zinc-300">
                                        {{ $logSourceLabels[$error->log_source] ?? ($error->log_source ?: 'Servidor') }}
                                    </span>
                                </td>
                                <td class="max-w-md px-4 py-3">
                                    <p class="line-clamp-3 text-zinc-200" title="{{ $error->message }}">
                                        {{ $error->message }}
                                    </p>
                                </td>
                                <td class="max-w-xs px-4 py-3 text-zinc-400">
                                    <p class="line-clamp-3 text-sm leading-relaxed" title="{{ $error->solution }}">
                                        {{ $error->solution }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex min-w-8 justify-center rounded-md bg-zinc-800 px-2 py-0.5 font-mono text-xs text-emerald-300">
                                        {{ $error->occurrence_count ?? 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($error->resolved)
                                        <span class="inline-flex rounded-full bg-emerald-950 px-2 py-0.5 text-xs font-medium text-emerald-400 ring-1 ring-emerald-500/30">
                                            Resolvido
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-950 px-2 py-0.5 text-xs font-medium text-amber-400 ring-1 ring-amber-500/25">
                                            Aberto
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500">
                                    {{ $error->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a
                                        href="{{ route('errors.show', ['error' => $error, 'return' => request()->getRequestUri()]) }}"
                                        class="inline-flex rounded-lg border border-zinc-600 bg-zinc-900 px-3 py-1.5 text-xs font-medium text-emerald-400/90 transition hover:border-emerald-500/40 hover:bg-zinc-800"
                                    >
                                        Ver completo
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-16 text-center text-zinc-500">
                                    Nenhum erro registrado ainda. Envie eventos via
                                    <code class="rounded bg-zinc-800 px-1.5 py-0.5 font-mono text-zinc-300">POST /api/errors</code>
                                    ou pelo agente no servidor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($errors->hasPages())
                <div class="border-t border-zinc-800 px-4 py-3 text-sm dark">
                    {{ $errors->links('pagination::tailwind') }}
                </div>
            @endif
        </div>
    </div>
@endsection
