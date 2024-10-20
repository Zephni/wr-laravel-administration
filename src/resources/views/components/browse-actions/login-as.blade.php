@if($modelInstance->id != null)
@themeComponent('forms.button', [
    'href' => route('wrla.impersonate.login-as', [
        'id' => $modelInstance->id
    ]),
    'size' => 'small',
    'color' => 'muted',
    'type' => 'button',
    'text' => 'Login',
    'icon' => 'fa fa-lock relative !mr-[3px] text-[10px]',
    'attributes' => new \Illuminate\View\ComponentAttributeBag([
        'onclick' => "if(!confirm('Login as ' + '{$modelInstance->email}' + '?')) { event.preventDefault(); }",
        'title' => 'Login as ' . $modelInstance->name
    ])
])
@endif