<?php

namespace WebRegulate\LaravelAdministration\Models;

use App\Mail\WRLA\EmailTemplateMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class EmailTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'wrla_email_templates';

    public const RENDER_MODE_BLADE = 'blade';

    public const RENDER_MODE_EMAIL = 'email';

    protected $guarded = [];

    public array $usingSMTPConfig = [];

    public ?array $dataArray = null;

    public bool $errorFound = false;

    public string $errorMessage = '';

    /**
     * Get by alias.
     */
    public static function getByAlias(string $alias, array $dataOrModels = []): ?static
    {
        // Cache the email template for this request
        $emailTemplate = once(fn () => static::where('alias', 'like', $alias)->first());

        if ($emailTemplate == null) {
            throw new \Exception('Email template not found with alias: '.$alias);
        }

        // Set data array
        if (! empty($dataOrModels)) {
            $emailTemplate->setDataArray($dataOrModels);
        }

        return $emailTemplate;
    }

    /**
     * Get by caetgory and alias.
     */
    public static function getByCategoryAlias(string $category, string $alias, array $dataOrModels = []): ?static
    {
        // Cache the email template for this request
        $emailTemplate = once(fn () => static::where('category', $category)->where('alias', 'like', $alias)->first());

        if ($emailTemplate == null) {
            throw new \Exception("Email template not found with category: $category and alias: $alias");
        }

        // Set data array
        if (! empty($dataOrModels)) {
            $emailTemplate->setDataArray($dataOrModels);
        }

        return $emailTemplate;
    }

    /**
     * Set data array.
     *
     * @param  array  $dataArray  Can be key => data, key => model,... etc
     */
    public function setDataArray(array $dataArray): static
    {
        $this->dataArray = $this->buildDataArrayFromDataOrModels($dataArray);

        return $this;
    }

    /**
     * Merge data array
     *
     * @param  array  $dataArray  Can be key => data, key => model,... etc
     */
    public function mergeDataArray(array $dataArray): static
    {
        $this->dataArray = array_merge($this->dataArray ?? [], $this->buildDataArrayFromDataOrModels($dataArray));

        return $this;
    }

    /**
     * Get data element by dotted key.
     */
    public function getData(string $key): mixed
    {
        return data_get($this->dataArray, $key, null);
    }

    /**
     * Fill missing data in data array with (key.subkey here) placeholders.
     */
    public function fillMissingDataWithPlaceholders(): static
    {
        $data = $this->getKeyMappings();

        // dd($data);

        // Loop through key mappings and set each value based on model attributes
        foreach ($data as $key => $model) {
            try {
                if (! isset($this->dataArray[$key])) {
                    $this->dataArray[$key] = [];
                }

                foreach ($model as $modelKey => $modelValue) {
                    if (! isset($this->dataArray[$key][$modelKey])) {
                        $this->dataArray[$key][$modelKey] = "($key.$modelKey here)";
                    }
                }
            } catch (\Exception) {
                // dd($e->getMessage(), $key, $model);
            }
        }

        return $this;
    }

    /**
     * Get available key mappings.
     */
    public function getKeyMappings(): array
    {
        return once(function () {
            $mappings = ! empty($this->mappings)
                ? json_decode($this->mappings, true)
                : [];

            return $mappings ?? [];
        });
    }

    /**
     * Build data array for email template from models (but only the attributes available in key mappings).
     *
     * @param  array  $modelsOrData  eg. ['data' => ['key' => 'value',... ], 'user' => User::find(1), ...]
     */
    public function buildDataArrayFromDataOrModels(array $modelsOrData): array
    {
        // For some reason the below caching style was returning the dataArray from the previous emailTemplate... not sure why
        // if ($this->dataArray) {
        //     return $this->dataArray;
        // }

        $data = [];

        // Apply data/models to data if key mappings exist
        foreach ($modelsOrData as $key => $dataOrModel) {
            $keyMappings = $this->getKeyMappings();

            // If key does not exist in key mappings, skip
            // if(!isset($keyMappings[$key])) {
            //     continue;
            // }

            // If data is Model, get only the attributes that are in the key mappings
            if ($dataOrModel instanceof Model) {
                $data[$key] = $dataOrModel->only(array_keys($keyMappings[$key]));

                continue;
            }

            // If data is an array, string, or numeric, use that
            elseif (is_array($dataOrModel) || is_string($dataOrModel) || is_numeric($dataOrModel)) {
                $data[$key] = $dataOrModel;

                continue;
            }

            // Otherwise, do nothing
        }

        // Apply custom values to data array
        $data = $this->applyCustomisedDataValues($data);

        return $data;
    }

    /**
     * Apply customised data values to the data array.
     *
     * "field": null -> Uses whatever is passed from the modelsOrData array
     * "field": "some string" -> A fall back value if the modelsOrData array is empty()
     * More to come...
     */
    public function applyCustomisedDataValues(array $data): array
    {
        if (empty($data)) {
            return $data;
        }

        // Get key mappings
        $keyMappings = $this->getKeyMappings();

        // Loop through each data array and apply based on key mapping type (explained above)
        foreach ($keyMappings as $key => $mapping) {
            // If data is an array, loop through each key and value
            if (is_array($mapping)) {
                foreach ($mapping as $mappingKey => $mappingValue) {
                    // If mapping value is null, skip
                    if (is_null($mappingValue)) {
                        continue;
                    }
                    // If data value is empty and mapping value is a string, set it to the data value
                    elseif (is_string($mappingValue)) {
                        if (empty($data[$key][$mappingKey])) {
                            $data[$key][$mappingKey] = $mappingValue;
                        }
                    }
                }
            }
            // Else if $mapping is a string, set it to the data value
            elseif (is_string($mapping)) {
                // If data value is empty, set to mapping
                if (empty($data[$key])) {
                    $data[$key] = $mapping;
                }
            }
        }

        return $data;
    }

    /**
     * Set user data from email if exists. Note this also updates the SMTP config.
     */
    public function setUserDataFromEmail(string $email): static
    {
        $user = WRLAHelper::getUserModelClass()::where('email', $email)->first();

        if ($user !== null) {
            $this->dataArray = ['user' => $user->only(array_keys($this->getKeyMappings()['user']))];
        }

        return $this;
    }

    /**
     * Inject template variables (set in email key mappings config) into string. Eg. Hello {{ user.name }}.
     */
    public function injectVariablesIntoString(string $string, string $renderMode = self::RENDER_MODE_EMAIL): string
    {
        $buildString = $string;

        if ($this->errorFound) {
            return str($buildString)->replace('{', '(')->replace('}', ')')->toString();
        }

        try {
            // Loop through each data array and inject into string
            foreach ($this->dataArray as $key => $data) {
                // If data is an array, loop through each key and value
                if (is_array($data)) {
                    foreach ($data as $dataKey => $dataValue) {
                        $buildString = str_replace('{{ '.$key.'.'.$dataKey.' }}', $dataValue ?? '', $buildString);
                    }
                }
                // If data is string, just replace it
                elseif (is_string($data)) {
                    $buildString = str_replace('{{ '.$key.' }}', $data ?? '', $buildString);
                }
            }

            // Render mode escaping
            if ($renderMode == self::RENDER_MODE_BLADE) {
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
            $this->errorMessage = $e->getMessage();
        }

        return $buildString;
    }

    /**
     * Set subject template.
     */
    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set body template.
     */
    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get final subject
     */
    public function getFinalSubject(string $renderMode = self::RENDER_MODE_EMAIL): string
    {
        return $this->injectVariablesIntoString($this->subject ?? '', $renderMode);
    }

    /**
     * Get final body
     */
    public function getFinalBody(string $renderMode = self::RENDER_MODE_EMAIL): string
    {
        return $this->injectVariablesIntoString($this->body ?? '', $renderMode);
    }

    /**
     * Get value by dotted key from data array.
     */
    public function getValueByKey(string $key): mixed
    {
        return data_get($this->dataArray, $key);
    }

    /**
     * Send email to addresses with this template.
     *
     * @return $success
     */
    public function sendEmail(string|array $toAddresses, ?array $attachments = null, bool $sendSeperateEmails = true, string $smtpKey = 'smtp'): bool
    {
        if ($this->errorFound) {
            return false;
        }

        // Get current smtp and host from config
        $smtpData = config("mail.mailers.$smtpKey");
        $smtpHost = $smtpData['host'];

        // If smtpData does not have from.address or from.name, set them to the default values
        if (! isset($smtpData['from']['address']) || ! isset($smtpData['from']['name'])) {
            $smtpData['from']['address'] = config('mail.from.address');
            $smtpData['from']['name'] = config('mail.from.name');
        }

        // Send as seperate emails mode
        if ($sendSeperateEmails) {
            if (is_string($toAddresses)) {
                $toAddresses = [$toAddresses];
            }

            // Remove any empty values or values without @ symbol
            $toAddresses = array_filter($toAddresses, fn ($toAddress) => ! empty($toAddress) && str($toAddress)->contains('@'));

            foreach ($toAddresses as $toAddress) {
                Log::channel('smtp')->info("Sending {$this->alias} email template ({$smtpHost})", [
                    'mappings' => $this->dataArray,
                    'to' => $toAddress
                ]);

                $mail = Mail::mailer($smtpKey)
                    ->send(new EmailTemplateMail(
                        $this,
                        $toAddress,
                        $attachments,
                        $smtpData
                    ));

                // If not null, log it
                if ($mail === null) {
                    Log::channel('smtp')->error('Email failed to send', ['to' => $toAddress]);

                    continue;
                }
            }

            return true;
        }

        Log::channel('smtp')->info("Sending {$this->alias} email template ({$smtpHost})", [
            'mappings' => $this->dataArray,
            'to' => $toAddresses
        ]);

        // Send as one email with first as to, and rest as cc mode
        $mail = Mail::mailer($smtpKey)->send(new EmailTemplateMail(
            $this,
            $toAddresses,
            $attachments,
            $smtpData
        ));

        // If not null, we assume success
        return $mail !== null;
    }

    /**
     * Render email, returning the HTML.
     *
     * @return string
     */
    public function renderEmail(string $renderMode = EmailTemplate::RENDER_MODE_EMAIL)
    {
        return view('email.wrla.email-template-mail', [
            'emailTemplate' => $this,
            'renderMode' => $renderMode,
        ])->render();
    }

    /**
     * Get available mappings list as formatted HTML
     */
    public function getMappingsListFormattedHTML(): string
    {
        $mappings = $this->getKeyMappings();
        $html = '<ul class="list-disc list-inside">';
        foreach ($mappings as $key => $mapping) {
            // Build a list like this: key.mapping[0], key.mapping[1], key.mapping[2]...
            if (is_array($mapping)) {
                $html .= "<li>$key: &nbsp;";
                $html .= implode(', ', array_map(fn ($item) => <<<HTML
                        <span onclick="
                            window.wrlaInsertTextAtCursor('@{{ $key.$item }}');
                        " class="select-none cursor-pointer text-primary-700 font-medium">$key.$item</span>
                    HTML, array_keys($mapping)));
                $html .= '</li>';
            }
        }
        $html .= '</ul>';

        return $html;
    }
}
