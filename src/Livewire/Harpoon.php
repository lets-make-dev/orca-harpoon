<?php

namespace MakeDev\OrcaHarpoon\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;

class Harpoon extends Component
{
    public string $sourceModule = '';

    public function harpoonElement(string $componentName, string $outerHtml): void
    {
        $validator = Validator::make(
            ['componentName' => $componentName],
            ['componentName' => ['required', 'regex:/^[A-Z][a-zA-Z0-9]+$/']],
        );

        if ($validator->fails()) {
            $this->addError('componentName', $validator->errors()->first('componentName'));

            return;
        }

        if ($this->sourceModule === '') {
            $this->addError('componentName', 'No source module detected for this page.');

            return;
        }

        $className = Str::studly($componentName);
        $kebabName = Str::kebab($componentName);
        $alias = strtolower($this->sourceModule).'-'.$kebabName;
        $modulePath = base_path('Modules/'.$this->sourceModule);

        if (! is_dir($modulePath)) {
            $this->addError('componentName', "Module directory not found: Modules/{$this->sourceModule}");

            return;
        }

        $this->createLivewireClass($modulePath, $className, $kebabName);
        $this->createBladeView($modulePath, $kebabName, $outerHtml);
        $this->updateServiceProvider($modulePath, $className, $alias);

        $refactorPrompt = $this->buildRefactorPrompt($kebabName, $alias, $outerHtml);

        session()->put('orca.pending_harpoon_refactor', [
            'prompt' => $refactorPrompt,
            'moduleInfo' => ['name' => $className],
        ]);

        $this->dispatch('harpoon-complete', [
            'componentName' => $className,
            'alias' => $alias,
            'classPath' => "Modules/{$this->sourceModule}/app/Livewire/{$className}.php",
            'viewPath' => "Modules/{$this->sourceModule}/resources/views/livewire/{$kebabName}.blade.php",
            'refactorPrompt' => $refactorPrompt,
        ]);
    }

    private function createLivewireClass(string $modulePath, string $className, string $kebabName): void
    {
        $viewNamespace = strtolower($this->sourceModule);

        $content = <<<PHP
        <?php

        namespace Modules\\{$this->sourceModule}\\Livewire;

        use Illuminate\\Contracts\\View\\View;
        use MakeDev\\MakeDev\\Concerns\\TransitionFadeIn;
        use MakeDev\\MakeDev\\Concerns\\TransitionFadeOut;
        use MakeDev\\MakeDev\\Livewire\\MakeDevModuleComponent;

        class {$className} extends MakeDevModuleComponent
        {
            use TransitionFadeIn, TransitionFadeOut;

            public function moduleInfo(): array
            {
                return [
                    'name' => '{$className}',
                    'description' => 'Extracted component from {$this->sourceModule}.',
                    'version' => '1.0.0',
                    'keyFiles' => [
                        'Modules/{$this->sourceModule}/app/Livewire/{$className}.php',
                        'Modules/{$this->sourceModule}/resources/views/livewire/{$kebabName}.blade.php',
                    ],
                    'capabilities' => [],
                    'dependencies' => [
                        'ModuleLoader',
                        'livewire/livewire',
                    ],
                    'agentReadme' => \$this->loadAgentReadme(),
                ];
            }

            public function overlayPosition(): string
            {
                return 'inline';
            }

            public function render(): View
            {
                return view('{$viewNamespace}::livewire.{$kebabName}');
            }
        }
        PHP;

        $classDir = $modulePath.'/app/Livewire';
        File::ensureDirectoryExists($classDir);
        File::put($classDir.'/'.$className.'.php', $content);
    }

    private function createBladeView(string $modulePath, string $kebabName, string $outerHtml): void
    {
        $viewDir = $modulePath.'/resources/views/livewire';
        File::ensureDirectoryExists($viewDir);

        $content = $outerHtml;

        File::put($viewDir.'/'.$kebabName.'.blade.php', $content."\n");
    }

    private function updateServiceProvider(string $modulePath, string $className, string $alias): void
    {
        $providerDir = $modulePath.'/app/Providers';
        $providerFile = collect(File::files($providerDir))
            ->first(fn ($file) => Str::endsWith($file->getFilename(), 'ServiceProvider.php'));

        if (! $providerFile) {
            return;
        }

        $providerPath = $providerFile->getPathname();
        $contents = File::get($providerPath);

        $useStatement = "use Modules\\{$this->sourceModule}\\Livewire\\{$className};";

        if (! str_contains($contents, $useStatement)) {
            $contents = preg_replace(
                '/(use Livewire\\\\Livewire;)/',
                "$1\n{$useStatement}",
                $contents,
                1
            );
        }

        $registration = "        Livewire::component('{$alias}', {$className}::class);";

        if (! str_contains($contents, $registration)) {
            $contents = preg_replace(
                '/(function registerLivewireComponents\(\): void\s*\{[^}]*)(})/',
                "$1    {$registration}\n    $2",
                $contents,
                1
            );
        }

        File::put($providerPath, $contents);
    }

    private function buildRefactorPrompt(string $kebabName, string $alias, string $outerHtml): string
    {
        return implode("\n", [
            "In Modules/{$this->sourceModule}, a section of HTML has been extracted into a new Livewire component '{$alias}'.",
            '',
            "New component view: Modules/{$this->sourceModule}/resources/views/livewire/{$kebabName}.blade.php",
            '',
            'The extracted HTML (as rendered in the browser):',
            '```html',
            Str::limit($outerHtml, 2000),
            '```',
            '',
            "Find the blade view in Modules/{$this->sourceModule}/resources/views/ that contains the source Blade code producing this rendered HTML.",
            'The source may use Blade directives (@foreach, @if, {{ $var }}, etc.).',
            '',
            "Replace the matching section with: @livewire('{$alias}')",
            'Preserve surrounding indentation. Only modify the source blade view, nothing else.',
        ]);
    }

    public function render(): View
    {
        return view('orcaharpoon::livewire.harpoon');
    }
}
