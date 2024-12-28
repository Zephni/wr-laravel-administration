<?php

namespace WebRegulate\LaravelAdministration\Models;

use App\Traits\Cacheable;
use App\Mail\WRLA\EmailTemplateMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use SoftDeletes, Cacheable;

    const RENDER_MODE_BLADE = 'blade';
    const RENDER_MODE_EMAIL = 'email';

    protected $fillable = [
        'category',
        'alias',
        'subject',
        'body',
        'mappings',
        'description',
        'requires_attachment',
    ];

    private ?array $dataArray = null;
    private bool $errorFound = false;

    /**
     * Find by alias.
     *
     * @param string $category
     * @param string $alias
     * @param array $models key value array, eg ['user' => User::find(1), ...]
     * @return ?self
     */
    public static function findByAlias(string $category, string $alias, array $models): ?self
    {
        $emailTemplate = once(fn() => self::where('category', $category)->where('alias', 'like', $alias)->first());

        if($emailTemplate == null) {
            throw new \Exception('Email template not found with alias: ' . $alias);
        }

        $emailTemplate->buildDataArrayFromModels($models);
        return $emailTemplate;
    }

    /**
     * Get available key mappings.
     *
     * @return array
     */
    public function getKeyMappings(): array
    {
        $mappings = json_decode(!empty($this->mappings) ? $this->mappings : '{"user": {
    "id": null,
    "name": null,
    "email": null,
}}', true);

        if ($mappings == null) {
            throw new \Exception('Email template key mappings can not be null. (Perhaps issue with JSON formatting?)');
        }

        return $mappings;
    }

    /**
     * Build data array for email template from models (but only the attributes available in key mappings).
     *
     * @param array $keyValueModels eg. ['user' => User::find(1), ...]
     * @return array
     */
    public function buildDataArrayFromModels(array $models): array
    {
        // For some reason the below caching style was returning the dataArray from the previous emailTemplate... not sure why
        // if ($this->dataArray) {
        //     return $this->dataArray;
        // }

        $data = $this->getKeyMappings();

        try {
            // Loop through key mappings and set each value based on model attributes
            foreach ($data as $key => $model) {
                if(!isset($models[$key])) {
                    continue;
                }

                $modelData = $models[$key]->only(array_keys($model));
                $data[$key] = $modelData;
            }

            $this->dataArray = $data;
        } catch (\Exception $e) {
            $this->errorFound = true;
        }

        return $data;
    }

    /**
     * Set user data from email if exists. Note this also updates the SMTP config.
     *
     * @param string $email
     * @return void
     */
    public function setUserDataFromEmail(string $email): void
    {
        $user = User::where('email', $email)->first();

        if($user !== null) {
            $this->dataArray = ['user' => $user->only(array_keys($this->getKeyMappings()['user']))];
        }
    }

    /**
     * Set data array.
     *
     * @param array $dataArray
     * @return self
     */
    public function setDataArray(array $dataArray): self
    {
        $this->dataArray = $dataArray;
        return $this;
    }

    /**
     * Inject template variables (set in email key mappings config) into string. Eg. Hello {{ user.name }}.
     *
     * @param string $string
     * @return string
     */
    public function injectVariablesIntoString(string $string, string $renderMode = self::RENDER_MODE_EMAIL): string
    {
        $buildString = $string;

        if($this->errorFound) {
            return $buildString;
        }

        try {
            // Loop through each data array and inject into string
            foreach ($this->dataArray as $key => $model) {
                foreach ($model as $modelKey => $modelValue) {
                    $buildString = str_replace('{{ ' . $key . '.' . $modelKey . ' }}', $modelValue, $buildString);
                }
            }

            // Render mode escaping
            if($this->renderMode == self::RENDER_MODE_BLADE) {
                $buildString = str_replace(['{', '}'], ['(', ')'], $buildString);
                $buildString = str_replace('@', "{{ '@' }}", $buildString);
                $buildString = str_replace('$', "{{ '$' }}", $buildString);
                $buildString = htmlspecialchars($buildString);
            } else {
                $buildString = htmlspecialchars($buildString);
                $buildString = str_replace(['{', '}'], ['&#123;', '&#125;'], $buildString);
                $buildString = str_replace('$', '&#36;', $buildString);
            }

            // Replace new lines with <br> tags
            $buildString = nl2br($buildString);
        } catch (\Exception $e) {
            $this->errorFound = true;
        }

        return $buildString;
    }

    /**
     * Set subject template.
     *
     * @param string $subject
     * @return self
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set body template.
     *
     * @param string $body
     * @return self
     */
    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Get final subject
     *
     * @return string
     */
    public function getFinalSubject(string $renderMode = self::RENDER_MODE_EMAIL): string
    {
        return $this->injectVariablesIntoString($this->subject ?? '', $renderMode);
    }

    /**
     * Get final body
     *
     * @return string
     */
    public function getFinalBody(string $renderMode = self::RENDER_MODE_EMAIL): string
    {
        return $this->injectVariablesIntoString($this->body ?? '', $renderMode);
    }

    /**
     * Send email to addresses with this template.
     *
     * @param string|array $toAddresses
     * @param ?array $attachments
     * @param bool $sendSeperateEmails
     * @return $success
     */
    public function sendEmail(string|array $toAddresses, ?array $attachments = null, bool $sendSeperateEmails = true): bool
    {
        if($this->errorFound) {
            return false;
        }

        // Send as seperate emails mode
        if($sendSeperateEmails) {
            if(is_string($toAddresses)) {
                $toAddresses = [$toAddresses];
            }

            // Remove any empty values or values without @ symbol
            $toAddresses = array_filter($toAddresses, fn($toAddress) => !empty($toAddress) && str($toAddress)->contains('@'));

            foreach($toAddresses as $toAddress) {
                Log::channel('smtp')->info("Sending {$this->alias} email template", ['to' => $toAddress]);

                $mail = Mail::send(new EmailTemplateMail(
                    $this,
                    $toAddress,
                    $attachments
                ));

                // If not null, log it
                if($mail === null) {
                    Log::channel('smtp')->error('Email failed to send', ['to' => $toAddress]);
                    continue;
                }
            }

            return true;
        }

        // Send as one email with first as to, and rest as cc mode
        $mail = Mail::send(new EmailTemplateMail(
            $this,
            $toAddresses,
            $attachments
        ));

        // If not null, we assume success
        return $mail !== null;
    }

    /**
     * Get available mappings list as formatted HTML
     *
     * @return string
     */
    public function getMappingsListFormattedHTML(): string
    {
        $mappings = $this->getKeyMappings();
        $html = '<ul class="list-disc list-inside">';
        foreach ($mappings as $key => $mapping) {
            // Build a list like this: key.mapping[0], key.mapping[1], key.mapping[2]...
            if (is_array($mapping)) {
                $html .= "<li>$key: &nbsp;";
                $html .= implode(', ', array_map(function ($item) use ($key) {
                    return <<<HTML
                        <span onclick="
                            window.wrlaInsertTextAtCursor('@{{ $key.$item }}');
                        " class="select-none cursor-pointer text-primary-700 font-medium">$key.$item</span>
                    HTML;
                }, array_keys($mapping)));
                $html .= '</li>';
            }
        }
        $html .= '</ul>';
        return $html;
    }
}
