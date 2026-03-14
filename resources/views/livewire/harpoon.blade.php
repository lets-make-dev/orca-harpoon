<div
    x-data="orcaHarpoon()"
    x-on:keydown.escape.window="cancel()"
    data-orca-harpoon
    class="fixed bottom-4 left-14 z-[9999]"
>
    {{-- Harpoon toggle button --}}
    <button
        x-show="mode === 'idle'"
        x-on:click="startInspecting()"
        class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-800 text-zinc-400 shadow-lg transition hover:text-white"
        title="OrcaHarpoon — Extract element to component"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 11l4-7 4 7M7 11h8M7 11l-1 4h10l-1-4M12 4v0M12 18v3" />
        </svg>
    </button>

    {{-- Active indicator while inspecting --}}
    <button
        x-show="mode === 'inspecting'"
        x-on:click="cancel()"
        class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg animate-pulse"
        title="Click an element to capture it (Esc to cancel)"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 11l4-7 4 7M7 11h8M7 11l-1 4h10l-1-4M12 4v0M12 18v3" />
        </svg>
    </button>

    {{-- Highlight overlay (follows hovered element) --}}
    <template x-if="mode === 'inspecting'">
        <div
            x-ref="overlay"
            class="pointer-events-none fixed z-[99998] border-2 border-blue-400 bg-blue-400/10 transition-all duration-75"
            :style="`top:${overlayRect.top}px;left:${overlayRect.left}px;width:${overlayRect.width}px;height:${overlayRect.height}px;`"
        >
            <span
                x-show="overlayLabel"
                x-text="overlayLabel"
                class="absolute -top-6 left-0 rounded bg-blue-600 px-1.5 py-0.5 text-[10px] font-mono text-white whitespace-nowrap"
            ></span>
        </div>
    </template>

    {{-- Naming modal --}}
    <template x-if="mode === 'naming'">
        <div class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50" x-on:click.self="cancel()">
            <div class="w-full max-w-md rounded-lg border border-zinc-700 bg-zinc-800 p-6 shadow-2xl" x-on:click.stop>
                <h3 class="text-sm font-semibold text-zinc-200">Extract to Component</h3>

                <div class="mt-3 rounded bg-zinc-900 px-3 py-2">
                    <p class="font-mono text-[10px] text-zinc-500" x-text="capturedXPath"></p>
                    <p class="mt-1 font-mono text-[11px] text-zinc-400 line-clamp-3" x-text="capturedHtmlPreview"></p>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-zinc-400">Component Name (PascalCase)</label>
                    <input
                        x-model="componentName"
                        x-ref="nameInput"
                        type="text"
                        placeholder="e.g. TaskQueue"
                        class="mt-1 w-full rounded border border-zinc-600 bg-zinc-900 px-3 py-2 text-sm text-zinc-200 placeholder-zinc-500 focus:border-blue-500 focus:outline-none"
                        x-on:input="updatePromptPreview()"
                        x-on:keydown.enter="submit()"
                    />
                    <template x-if="error">
                        <p class="mt-1 text-xs text-red-400" x-text="error"></p>
                    </template>
                </div>

                {{-- AI Refactor Context preview --}}
                <div class="mt-3" x-data="{ showPrompt: false }">
                    <button
                        type="button"
                        x-on:click="showPrompt = !showPrompt"
                        class="flex items-center gap-1 text-[10px] font-medium text-zinc-500 transition hover:text-zinc-300"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-3 w-3 transition-transform"
                            :class="showPrompt && 'rotate-90'"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        AI Refactor Context
                    </button>
                    <textarea
                        x-show="showPrompt"
                        x-transition
                        readonly
                        class="mt-1 w-full rounded border border-zinc-700 bg-zinc-900 px-3 py-2 font-mono text-[10px] text-zinc-400 leading-relaxed"
                        rows="8"
                        x-text="refactorPromptPreview"
                    ></textarea>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        x-on:click="cancel()"
                        class="rounded px-3 py-1.5 text-xs font-medium text-zinc-400 transition hover:text-zinc-200"
                        :disabled="loading"
                    >Cancel</button>
                    <button
                        x-on:click="submit()"
                        class="rounded bg-blue-600 px-4 py-1.5 text-xs font-medium text-white transition hover:bg-blue-500 disabled:opacity-50"
                        :disabled="loading || !componentName"
                    >
                        <span x-show="!loading">Harpoon It</span>
                        <span x-show="loading" x-cloak>Extracting...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Success toast --}}
    <div
        x-show="successMessage"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-16 left-14 z-[99999] max-w-sm rounded-lg border border-green-700 bg-green-900/90 px-4 py-2 text-xs text-green-300 shadow-lg"
        x-cloak
    >
        <p x-text="successMessage"></p>
    </div>
</div>

<script>
    function orcaHarpoon() {
        return {
            mode: 'idle',
            overlayRect: { top: 0, left: 0, width: 0, height: 0 },
            overlayLabel: '',
            capturedHtml: '',
            capturedXPath: '',
            capturedHtmlPreview: '',
            componentName: '',
            loading: false,
            error: '',
            successMessage: '',
            refactorPromptPreview: '',

            _onMouseOver: null,
            _onClick: null,
            _onKeyDown: null,
            _targetEl: null,

            _highlightElement(el) {
                if (!el || el.closest('[data-orca-harpoon]')) {
                    return;
                }
                this._targetEl = el;
                const rect = el.getBoundingClientRect();
                this.overlayRect = {
                    top: rect.top,
                    left: rect.left,
                    width: rect.width,
                    height: rect.height,
                };
                const tag = el.tagName.toLowerCase();
                const classes = [...el.classList].slice(0, 3).join('.');
                this.overlayLabel = classes ? `${tag}.${classes}` : tag;
            },

            startInspecting() {
                this.mode = 'inspecting';
                document.body.style.cursor = 'crosshair';

                this._onMouseOver = (e) => {
                    if (e.target.closest('[data-orca-harpoon]')) {
                        return;
                    }
                    this._highlightElement(e.target);
                };

                this._onClick = (e) => {
                    if (e.target.closest('[data-orca-harpoon]')) {
                        return;
                    }
                    e.preventDefault();
                    e.stopPropagation();

                    const el = this._targetEl || e.target;
                    this.capturedHtml = el.outerHTML;
                    this.capturedXPath = this.getXPath(el);
                    this.capturedHtmlPreview = this.capturedHtml.substring(0, 200) + (this.capturedHtml.length > 200 ? '...' : '');
                    this.componentName = '';
                    this.error = '';

                    this.stopInspecting();
                    this.mode = 'naming';

                    this.$nextTick(() => {
                        if (this.$refs.nameInput) {
                            this.$refs.nameInput.focus();
                        }
                    });
                };

                this._onKeyDown = (e) => {
                    if (!e.shiftKey || !this._targetEl) {
                        return;
                    }

                    let next = null;

                    if (e.key === 'ArrowUp') {
                        next = this._targetEl.parentElement;
                        if (next && (next === document.documentElement || next === document.body)) {
                            next = null;
                        }
                    } else if (e.key === 'ArrowDown') {
                        next = this._targetEl.firstElementChild;
                    } else if (e.key === 'ArrowLeft') {
                        next = this._targetEl.previousElementSibling;
                        if (!next) {
                            next = this._targetEl.parentElement?.lastElementChild;
                        }
                    } else if (e.key === 'ArrowRight') {
                        next = this._targetEl.nextElementSibling;
                        if (!next) {
                            next = this._targetEl.parentElement?.firstElementChild;
                        }
                    }

                    if (next && !next.closest('[data-orca-harpoon]')) {
                        e.preventDefault();
                        e.stopPropagation();
                        this._highlightElement(next);
                    }
                };

                document.addEventListener('mouseover', this._onMouseOver, true);
                document.addEventListener('click', this._onClick, true);
                document.addEventListener('keydown', this._onKeyDown, true);
            },

            stopInspecting() {
                document.body.style.cursor = '';
                if (this._onMouseOver) {
                    document.removeEventListener('mouseover', this._onMouseOver, true);
                }
                if (this._onClick) {
                    document.removeEventListener('click', this._onClick, true);
                }
                if (this._onKeyDown) {
                    document.removeEventListener('keydown', this._onKeyDown, true);
                }
                this._onMouseOver = null;
                this._onClick = null;
                this._onKeyDown = null;
                this._targetEl = null;
                this.overlayRect = { top: 0, left: 0, width: 0, height: 0 };
                this.overlayLabel = '';
            },

            cancel() {
                this.stopInspecting();
                this.mode = 'idle';
            },

            updatePromptPreview() {
                if (!this.componentName || !this.capturedHtml) {
                    this.refactorPromptPreview = '';
                    return;
                }
                const kebab = this.componentName.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
                const mod = this.$wire.sourceModule;
                const htmlSnippet = this.capturedHtml.substring(0, 2000);

                if (mod) {
                    const alias = mod.toLowerCase() + '-' + kebab;
                    this.refactorPromptPreview = [
                        `In Modules/${mod}, a section of HTML has been extracted into a new Livewire component '${alias}'.`,
                        '',
                        `New component view: Modules/${mod}/resources/views/livewire/${kebab}.blade.php`,
                        '',
                        'The extracted HTML (as rendered in the browser):',
                        '```html',
                        htmlSnippet,
                        '```',
                        '',
                        `Find the blade view in Modules/${mod}/resources/views/ that contains the source Blade code producing this rendered HTML.`,
                        'The source may use Blade directives (@@foreach, @@if, @{{ $var }}, etc.).',
                        '',
                        `Replace the matching section with: @@livewire('${alias}')`,
                        'Preserve surrounding indentation. Only modify the source blade view, nothing else.',
                    ].join('\n');
                } else {
                    const alias = 'makedev-' + kebab;
                    this.refactorPromptPreview = [
                        `A section of HTML has been extracted into a new app-level Livewire component '${alias}'.`,
                        '',
                        `New component class: app/MakeDev/Components/${this.componentName}.php`,
                        `New component view: resources/views/makedev/components/${kebab}.blade.php`,
                        '',
                        'The extracted HTML (as rendered in the browser):',
                        '```html',
                        htmlSnippet,
                        '```',
                        '',
                        'Find the blade view in resources/views/ that contains the source Blade code producing this rendered HTML.',
                        'The source may use Blade directives (@@foreach, @@if, @{{ $var }}, etc.).',
                        '',
                        `Replace the matching section with: @@livewire('${alias}')`,
                        'Preserve surrounding indentation. Only modify the source blade view, nothing else.',
                    ].join('\n');
                }
            },

            init() {
                this.$wire.$on('harpoon-complete', (data) => {
                    const info = data[0] ?? data;
                    this.successMessage = `Created ${info.componentName} component!`;
                    this.mode = 'idle';
                    this.loading = false;

                    setTimeout(() => { this.successMessage = ''; }, 5000);
                });

                this.$wire.$on('harpoon-error', (data) => {
                    const info = data[0] ?? data;
                    this.error = info.message;
                    this.loading = false;
                });
            },

            async submit() {
                if (!this.componentName || this.loading) {
                    return;
                }

                if (!/^[A-Z][a-zA-Z0-9]+$/.test(this.componentName)) {
                    this.error = 'Name must be PascalCase (e.g. TaskQueue)';
                    return;
                }

                this.loading = true;
                this.error = '';

                try {
                    await this.$wire.harpoonElement(this.componentName, this.capturedHtml);
                } catch (e) {
                    this.error = 'An error occurred: ' + e.message;
                    this.loading = false;
                }
            },

            getXPath(el) {
                if (el.id) {
                    return `//*[@id="${el.id}"]`;
                }
                const parts = [];
                while (el && el.nodeType === 1) {
                    let idx = 1;
                    let sibling = el.previousElementSibling;
                    while (sibling) {
                        if (sibling.tagName === el.tagName) {
                            idx++;
                        }
                        sibling = sibling.previousElementSibling;
                    }
                    parts.unshift(`${el.tagName.toLowerCase()}[${idx}]`);
                    el = el.parentElement;
                }
                return '/' + parts.join('/');
            },
        };
    }
</script>
