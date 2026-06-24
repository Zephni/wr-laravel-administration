<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class CreateNotificationCommand extends Command
{
    /**
     * The studly case notification name (may contain a sub-namespace path).
     */
    private string $notificationName = '';

    /**
     * The resolved class name (without namespace path).
     */
    private string $className = '';

    /**
     * The resolved namespace for the generated class.
     */
    private string $namespace = '';

    /**
     * The human readable display name used in the title.
     */
    private string $displayName = '';

    /**
     * Relative file path of the destination (forward slashes).
     */
    private string $filePath = '';

    /**
     * Whether an existing file should be overwritten.
     */
    private bool $forceOverwrite = false;

    /**
     * Whether to scaffold email sending logic.
     */
    private bool $includeEmail = false;

    /**
     * Whether to scaffold a custom button.
     */
    private bool $includeButtons = false;

    /**
     * The type of custom button to scaffold ('url' or 'livewire').
     */
    private string $buttonType = '';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wrla:notification {name?} {--email} {--buttons} {--button-type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new WRLA notification definition';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->resolveName();
        $this->resolveNamespace();

        if (! $this->confirmOverwrite()) {
            $this->warn('Notification creation cancelled.');

            return 0;
        }

        $this->resolveOptions();
        $this->generateNotification();

        $this->line('');

        return 1;
    }

    /**
     * Resolve the notification name from the argument or prompt the user.
     */
    private function resolveName(): void
    {
        $this->notificationName = $this->argument('name')
            ?: $this->ask('Please provide a notification class using studly case (eg. OrderShipped)');

        $this->filePath = str($this->notificationName)->replace('\\', '/')->__toString();
    }

    /**
     * Resolve the namespace, class name and display name.
     */
    private function resolveNamespace(): void
    {
        $baseNamespace = 'App\\WRLA\\NotificationDefinitions';

        if (str($this->notificationName)->contains('\\')) {
            $this->namespace = $baseNamespace.'\\'.str($this->notificationName)->beforeLast('\\')->__toString();
            $this->className = str($this->notificationName)->afterLast('\\')->__toString();
        } else {
            $this->namespace = $baseNamespace;
            $this->className = $this->notificationName;
        }

        $this->displayName = str($this->className)->headline()->__toString();
    }

    /**
     * Confirm whether to overwrite the file if it already exists.
     */
    private function confirmOverwrite(): bool
    {
        if (! File::exists($this->destinationPath())) {
            return true;
        }

        if ($this->confirm('The notification already exists. Do you want to overwrite it?', false)) {
            $this->forceOverwrite = true;

            return true;
        }

        return false;
    }

    /**
     * Resolve the optional features either from flags or by prompting.
     */
    private function resolveOptions(): void
    {
        $this->includeEmail = $this->option('email')
            ?: $this->confirm('Send an email (via EmailTemplate) when this notification is created?', false);

        $this->includeButtons = $this->option('buttons')
            ?: $this->confirm('Add a custom button to this notification?', false);

        if ($this->includeButtons) {
            $this->buttonType = $this->option('button-type')
                ?: $this->choice(
                    'What type of button should it be?',
                    ['url', 'livewire'],
                    'url'
                );
        }
    }

    /**
     * Generate the notification file from the stub.
     */
    private function generateNotification(): void
    {
        WRLAHelper::generateFileFromStub(
            'Notification.stub',
            $this->getStubVariables(),
            $this->destinationPath(),
            $this->forceOverwrite
        );

        $this->info("Notification {$this->className} created successfully here: ".WRLAHelper::removeBasePath($this->destinationPath()));
    }

    /**
     * Get the absolute destination path for the generated file.
     */
    private function destinationPath(): string
    {
        return app_path('WRLA/NotificationDefinitions/'.$this->filePath.'.php');
    }

    /**
     * Build the variables injected into the stub.
     */
    private function getStubVariables(): array
    {
        return [
            '{{ $NAMESPACE }}' => $this->namespace,
            '{{ $CLASS_NAME }}' => $this->className,
            '{{ $DISPLAY_NAME }}' => $this->displayName,
            '{{ $USES }}' => $this->buildUseStatements(),
            '{{ $OPTIONAL_METHODS }}' => $this->buildOptionalMethods(),
        ];
    }

    /**
     * Build the use statements required by the chosen options.
     */
    private function buildUseStatements(): string
    {
        $uses = [
            'use WebRegulate\LaravelAdministration\Classes\NotificationBase;',
            'use WebRegulate\LaravelAdministration\Models\Notification;',
        ];

        if ($this->includeButtons) {
            $uses[] = 'use Illuminate\Support\Collection;';
        }

        if ($this->includeEmail) {
            $uses[] = 'use WebRegulate\LaravelAdministration\Models\EmailTemplate;';
        }

        sort($uses);

        return implode("\n", $uses)."\n";
    }

    /**
     * Build the optional method blocks based on the chosen options.
     */
    private function buildOptionalMethods(): string
    {
        $methods = [];

        if ($this->includeEmail) {
            $methods[] = $this->emailMessageMethod();
        }

        if ($this->includeButtons) {
            $methods[] = $this->buttonsMethod();
        }

        if ($this->includeEmail) {
            $methods[] = $this->postCreatedMethod();
        }

        if ($methods === []) {
            return '';
        }

        return "\n\n".implode("\n\n", $methods);
    }

    /**
     * Stub for the getEmailMessage() override.
     */
    private function emailMessageMethod(): string
    {
        return <<<'PHP'
    /**
     * Get the message used in the email version of this notification.
     */
    public function getEmailMessage(): string
    {
        return $this->data['emailMessage'] ?? $this->getMessage();
    }
PHP;
    }

    /**
     * Stub for the getButtons() override, based on the chosen button type.
     */
    private function buttonsMethod(): string
    {
        return $this->buttonType === 'livewire'
            ? $this->livewireButtonMethod()
            : $this->urlButtonMethod();
    }

    /**
     * Stub for a getButtons() override with a URL link button.
     */
    private function urlButtonMethod(): string
    {
        return <<<'PHP'
    /**
     * Get the buttons displayed on this notification.
     */
    public function getButtons(Collection $defaultButtons, Notification $notification): Collection
    {
        return $defaultButtons->push(
            $this->buildNotificationButton(
                $notification, [
                    'href' => '/',
                    'target' => '_blank',
                ],
                'View',
                'fas fa-eye',
            )
        );
    }
PHP;
    }

    /**
     * Stub for a getButtons() override with a Livewire action button and its action method.
     */
    private function livewireButtonMethod(): string
    {
        return <<<'PHP'
    /**
     * Get the buttons displayed on this notification.
     */
    public function getButtons(Collection $defaultButtons, Notification $notification): Collection
    {
        return $defaultButtons->push(
            $this->buildNotificationActionButton(
                $notification,
                'exampleAction',
                ['notificationId' => $notification->id],
                'Confirm',
                'fas fa-check',
            )
        );
    }

    /**
     * Example action callable from a notification button.
     */
    public function exampleAction(array $data): void
    {
        //
    }
PHP;
    }

    /**
     * Stub for the postCreated() override that sends an email.
     */
    private function postCreatedMethod(): string
    {
        return <<<'PHP'
    /**
     * Handle logic after the notification has been created (e.g. send email).
     */
    public function postCreated(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setSubject($this->getTitle());
        $emailTemplate->setBody($this->getEmailMessageFinal());
        $emailTemplate->sendEmail(
            $this->getUserGroup()->pluck('email')->toArray(),
            null,
            false
        );
    }
PHP;
    }
}
