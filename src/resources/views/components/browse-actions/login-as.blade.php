@if($modelInstance->id != null)
@themeComponent('forms.button', [
    'href' => route('wrla.impersonate.login-as', [
        'id' => $modelInstance->id
    ]),
    'size' => 'small',
    'color' => 'teal',
    'type' => 'button',
    'text' => 'Login',
    'icon' => 'fa fa-lock relative !mr-[3px] text-[10px]',
    'attributes' => new \Illuminate\View\ComponentAttributeBag([
        'onclick' => "confirm('Login as ' + '{$modelInstance->email}' + '?')",
        'title' => 'Login as ' . $modelInstance->name
    ])
])
@endif