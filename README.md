<p align="center">
  <strong>OrcaHarpoon</strong>
</p>

<p align="center">
Extract any HTML element into a Livewire component with a single click.
</p>

<p align="center">
Point. Click. Component. OrcaHarpoon turns visual element inspection into instant Livewire scaffolding — complete with AI refactor prompts for seamless source code integration.
</p>

<p align="center">

[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4+-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire 4](https://img.shields.io/badge/Livewire-4-FB70A9?logo=livewire&logoColor=white)](https://livewire.laravel.com)

</p>

## What is OrcaHarpoon?

OrcaHarpoon is a developer tool that lets you visually inspect any element on your page and instantly scaffold a fully-wired Livewire component from it. It generates the PHP class, Blade view, and service provider registration — then produces an AI refactor prompt so Claude can replace the original HTML with your new `@livewire()` tag.

## Quick Start

```bash
composer require make-dev/orca-harpoon
```

That's it. OrcaHarpoon auto-injects into every page in local environments via middleware.

## How It Works

```
1. Click the harpoon icon (bottom-left corner)
2. Hover over elements — a blue highlight follows your cursor
3. Click an element to capture it
4. Name your component (PascalCase, e.g. TaskQueue)
5. Click "Harpoon It"
```

OrcaHarpoon then:

- Creates `Modules/{Module}/app/Livewire/{ComponentName}.php` extending `MakeDevModuleComponent`
- Creates `Modules/{Module}/resources/views/livewire/{component-name}.blade.php` with the captured HTML
- Adds `Livewire::component()` registration to the module's service provider
- Stores an AI refactor prompt in the session for [Orca](../Orca) to pick up
- Dispatches a `harpoon-complete` Livewire event with all file paths and the refactor prompt

The generated component includes transition traits, module metadata, overlay positioning, and Agent README support out of the box.

## Features

- **Visual Element Inspector** — Browser-native DOM inspection with live blue highlight overlay, element tag + class labels, and XPath generation.

- **One-Click Scaffolding** — Generates a complete Livewire component: PHP class, Blade view, and service provider registration in a single action.

- **PascalCase Validation** — Client-side and server-side validation ensures component names follow convention.

- **AI Refactor Prompts** — Automatically generates a structured prompt that instructs Claude to find the source Blade code and replace it with `@livewire('component-alias')`.

- **Orca Integration** — Refactor prompts are stored in the session (`orca.pending_harpoon_refactor`) so Orca can pick them up after the Vite file-watcher triggers a page refresh.

- **Module-Aware** — Automatically detects which module owns the current page from the route's controller namespace and scopes all generated files to that module.

## UI States

| State | Visual | Interaction |
|---|---|---|
| **Idle** | Harpoon icon button (dark, bottom-left) | Click to start inspecting |
| **Inspecting** | Pulsing blue button + highlight overlay following cursor | Click any element to capture, Esc to cancel |
| **Naming** | Modal dialog with XPath, HTML preview, name input, prompt preview | Enter name and click "Harpoon It", Esc to cancel |
| **Success** | Green toast notification | Auto-dismisses after 5 seconds |

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=orcaharpoon-config
```

| Variable | Default | Description |
|---|---|---|
| `MODULE_ORCA_HARPOON_ENABLED` | `true` | Enable/disable OrcaHarpoon entirely |

OrcaHarpoon only activates in the **local environment** (`app()->isLocal()`). It will never appear in production.

## Generated Component Example

When you harpoon an element named `TaskQueue` in the `DemoSimpleHomepage` module, you get:

**`Modules/DemoSimpleHomepage/app/Livewire/TaskQueue.php`**

```php
namespace Modules\DemoSimpleHomepage\Livewire;

use MakeDev\MakeDev\Concerns\TransitionFadeIn;
use MakeDev\MakeDev\Concerns\TransitionFadeOut;
use MakeDev\MakeDev\Livewire\MakeDevModuleComponent;

class TaskQueue extends MakeDevModuleComponent
{
    use TransitionFadeIn, TransitionFadeOut;

    public function moduleInfo(): array
    {
        return [
            'name' => 'TaskQueue',
            'description' => 'Extracted component from DemoSimpleHomepage.',
            'version' => '1.0.0',
            // ...
        ];
    }

    public function render()
    {
        return view('demosimplehomepage::livewire.task-queue');
    }
}
```

**`Modules/DemoSimpleHomepage/resources/views/livewire/task-queue.blade.php`**

Contains the captured outer HTML from the browser.

## Architecture

```
src/
├── Http/Middleware/
│   └── InjectOrcaHarpoon.php    # Auto-injects component into HTML responses
├── Livewire/
│   └── Harpoon.php              # Core extraction logic and file generation
└── Providers/
    └── OrcaHarpoonServiceProvider.php
config/
└── orcaharpoon.php              # Enable/disable toggle
resources/views/livewire/
└── harpoon.blade.php            # Alpine.js UI (inspector, modal, toast)
```

### Middleware Guards

The `InjectOrcaHarpoon` middleware only injects when all conditions are met:
- Environment is `local`
- Config `orcaharpoon.enabled` is `true`
- Request does not have `X-Livewire` header (skips Livewire updates)
- Response Content-Type contains `text/html`

## Requirements

- PHP 8.4+
- Laravel 12
- Livewire 4
- [MakeDev](../MakeDev) (for `MakeDevModuleComponent` base class and transition traits)

## License

MIT
