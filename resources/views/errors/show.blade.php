@extends('layouts.app')

@section('title', 'Erro #'.$error->id.' · '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a
                href="{{ $backUrl }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-emerald-400/90 transition hover:text-emerald-300"
            >
                <span aria-hidden="true">←</span> Voltar à lista
            </a>
        </div>

        <header class="mb-8 border-b border-zinc-800 pb-6">
            <p class="text-sm font-medium text-emerald-400/90">Detalhe do erro</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-white">
                Erro #{{ $error->id }}
            </h1>
            <div class="mt-4 flex flex-wrap gap-2">
                @if ($error->resolved)
                    <span class="inline-flex rounded-full bg-emerald-950 px-3 py-1 text-xs font-medium text-emerald-400 ring-1 ring-emerald-500/30">
                        Resolvido
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-amber-950 px-3 py-1 text-xs font-medium text-amber-400 ring-1 ring-amber-500/25">
                        Aberto
                    </span>
                @endif
                <span class="inline-flex rounded-full bg-zinc-800 px-3 py-1 text-xs font-medium text-zinc-300">
                    {{ $error->occurrence_count ?? 1 }} ocorrência(s)
                </span>
            </div>
        </header>

        <dl class="mb-8 grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-zinc-800 bg-zinc-900/40 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Servidor</dt>
                <dd class="mt-1 text-sm text-zinc-200">{{ $error->server_name ?: '—' }}</dd>
            </div>
            <div class="rounded-lg border border-zinc-800 bg-zinc-900/40 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Origem do log</dt>
                <dd class="mt-1 text-sm text-zinc-200">
                    {{ config('autofix.log_source_labels')[$error->log_source] ?? ($error->log_source ?: 'Servidor (nginx/apache)') }}
                </dd>
            </div>
            <div class="rounded-lg border border-zinc-800 bg-zinc-900/40 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Registrado em</dt>
                <dd class="mt-1 text-sm text-zinc-200">
                    {{ $error->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}
                </dd>
            </div>
            <div class="rounded-lg border border-zinc-800 bg-zinc-900/40 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Última atualização</dt>
                <dd class="mt-1 text-sm text-zinc-200">
                    {{ $error->updated_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}
                </dd>
            </div>
            @if ($error->fingerprint)
                <div class="rounded-lg border border-zinc-800 bg-zinc-900/40 p-4 sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Fingerprint (deduplicação)</dt>
                    <dd class="mt-1 break-all font-mono text-xs text-zinc-400">{{ $error->fingerprint }}</dd>
                </div>
            @endif
        </dl>

        <section class="mb-8" aria-labelledby="msg-heading">
            <div class="mb-2 flex items-center justify-between gap-2">
                <h2 id="msg-heading" class="text-sm font-semibold uppercase tracking-wider text-zinc-400">
                    Mensagem completa
                </h2>
                <button
                    type="button"
                    class="rounded-md border border-zinc-600 px-2 py-1 text-xs font-medium text-zinc-300 transition hover:bg-zinc-800"
                    data-copy-target="error-message"
                >
                    Copiar
                </button>
            </div>
            <pre
                id="error-message"
                class="max-h-[min(24rem,50vh)] overflow-auto whitespace-pre-wrap break-words rounded-xl border border-zinc-800 bg-zinc-950 p-4 font-mono text-sm leading-relaxed text-zinc-200"
            >{{ $error->message }}</pre>
        </section>

        <section aria-labelledby="sol-heading">
            <div class="mb-2 flex items-center justify-between gap-2">
                <h2 id="sol-heading" class="text-sm font-semibold uppercase tracking-wider text-zinc-400">
                    Sugestão Autofix
                </h2>
                <button
                    type="button"
                    class="rounded-md border border-zinc-600 px-2 py-1 text-xs font-medium text-zinc-300 transition hover:bg-zinc-800"
                    data-copy-target="error-solution"
                >
                    Copiar
                </button>
            </div>
            <pre
                id="error-solution"
                class="max-h-[min(16rem,40vh)] overflow-auto whitespace-pre-wrap break-words rounded-xl border border-emerald-900/40 bg-emerald-950/20 p-4 font-sans text-sm leading-relaxed text-emerald-100/90"
            >{{ $error->solution }}</pre>
        </section>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-copy-target]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-copy-target');
                    const el = document.getElementById(id);
                    if (!el) return;
                    const text = el.textContent ?? '';
                    navigator.clipboard.writeText(text).then(() => {
                        const prev = btn.textContent;
                        btn.textContent = 'Copiado!';
                        setTimeout(() => { btn.textContent = prev; }, 1500);
                    });
                });
            });
        </script>
    @endpush
@endsection
