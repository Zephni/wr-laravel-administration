<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Support\Arr;
use Illuminate\Contracts\View\View;

class InstanceAction
{
    public mixed $manageableModelInstance;
    public string $text = '';
    public ?string $icon = null;
    public ?string $color = null;
    public mixed $action = null;
    public array $attributes = [];
    public array $additonalAttributes = [];
    public mixed $enableOnCondition = true;
    public ?string $actionKey = null; // Only used if action is callable


    /**
     * Create instance action button
     * @param mixed $manageableModelInstance
     * @param string $text
     * @param mixed $icon
     * @param mixed $color
     * @param null|callable|string $action Takes model instance, returns string message or RedirectResponse
     * @param null|bool|callable $enableOnCondition
     * @param null|array $additonalAttributes
     * @return InstanceAction
     */
    public static function make(mixed $manageableModelInstance, string $text, ?string $icon = null, ?string $color = null, null|callable|string $action = null, null|bool|callable $enableOnCondition = null, ?array $additonalAttributes = null): InstanceAction
    {
        // New static instance action
        $instanceAction = new static();

        // Set up instance action properties
        $instanceAction->manageableModelInstance = $manageableModelInstance;
        $instanceAction->text = $text;
        $instanceAction->icon = $icon;
        $instanceAction->color = $color;

        if(!is_null($action)) {
            $instanceAction->setAction($action);
        }

        if(!is_null($additonalAttributes)) {
            $instanceAction->setAdditionalAttributes($additonalAttributes);
        }
        
        if(!is_null($enableOnCondition)) {
            $instanceAction->enableOnCondition($enableOnCondition);
        }

        // Return instance action
        return $instanceAction;
    }

    /**
     * Set action
     * @param callable|string $action If string then will be href link, if callable should take the model instance and return a string message for the user
     * @return InstanceAction
     */
    public function setAction(callable|string $action): static
    {
        if(is_callable($action)) {
            $this->actionKey = $this->registerInstanceAction($action);
        }

        $this->action = $action;
        return $this;
    }

    /**
     * Set additional attributes
     * @param array $additonalAttributes
     * @return InstanceAction
     */
    public function setAdditionalAttributes(array $additonalAttributes): static
    {
        $this->additonalAttributes = $additonalAttributes;
        return $this;
    }

    /**
     * Enable on condition
     * @param null|callable|bool $condition
     * @return InstanceAction
     */
    public function enableOnCondition(callable|bool $condition): static
    {
        $this->enableOnCondition = $condition;
        return $this;
    }

    /**
     * Applies onclick additional attribute to confirm action from user before executing
     * @param string $message
     * @return InstanceAction
     */
    public function confirm(string $message): static
    {
        // General escaping for JavaScript string
        $message = addslashes($message);

        $this->additonalAttributes['onclick'] = <<<JS
            if(!confirm('{$message}')) {
                event.stopImmediatePropagation();
            }
        JS;

        return $this;
    }

    /**
     * Prompt user and pass as ['input' => 'user input'] parameter to action
     * @param string $message
     * @param bool $allowEmpty If true, allows empty input; if false, user must provide input or cancel
     * @return InstanceAction
     */
    public function ask(string $message, bool $allowEmpty = false): static
    {
        // General escaping for JavaScript string
        $message = addslashes($message);

        $allowEmptyJs = $allowEmpty ? 'true' : 'false';

        $this->additonalAttributes['onclick'] = <<<JS
            let input = prompt('{$message}');

            if(input === null || (!{$allowEmptyJs} && input.trim() === '')) {
                event.stopImmediatePropagation();
                return;
            }

            window.wrla.instanceAction.parameters = { input: input };
        JS;

        return $this;
    }

    /**
     * If
     * 
     * @param callable $callback Must take and return InstanceAction
     */
    public function if(callable|bool $condition, callable $callback): static
    {
        if(is_callable($condition) ? call_user_func($condition) : $condition) {
            return $callback($this);
        }

        return $this;
    }

    /**
     * Register instance action on manageable model instance and get action key
     * @return string
     */
    public function registerInstanceAction($action): string
    {
        // Register the action with the manageable model instance and return action key
        return $this->manageableModelInstance->registerInstanceAction($action);
    }

    /**
     * Render the instance action button
     * @throws \Exception
     * @return string|View
     */
    public function render(): View|string
    {
        // Get instance id
        $instanceId = $this->manageableModelInstance->getModelInstance()->id;

        // Attributes
        if (is_string($this->action)) {
            $attributes = ['href' => $this->action];
        } elseif (is_callable($this->action)) {            
            $attributes = ['wire:click' => <<<JS
                callManageableModelAction('{$instanceId}', '{$this->actionKey}', window.wrla.instanceAction.parameters ?? {});
                window.wrla.instanceAction.parameters = {};
            JS];
        } elseif(!is_null($this->action)) {
            throw new \Exception('Action must be a string URL, callable, or null');
        }

        // If display on condition is false, return empty view
        if ($this->enableOnCondition !== null) {
            $enableOnCondition = is_callable($this->enableOnCondition) ? call_user_func($this->enableOnCondition) : $this->enableOnCondition;
            if(!$enableOnCondition) {
                return '';
            }
        }

        // If additional attributes do not contain 'title', set title from text
        if (empty($this->additonalAttributes['title'])) {
            $this->additonalAttributes['title'] = $this->text;
        }

        // Return button view
        return view(WRLAHelper::getViewPath('components.forms.button'), [
            'text' => $this->text,
            'icon' => $this->icon ?? 'fa fa-cog',
            'color' => $this->color ?? 'primary',
            'size' => 'small',
            'attributes' => Arr::toAttributeBag(array_merge($this->additonalAttributes ?? [], $attributes ?? [])),
        ]);
    }
}