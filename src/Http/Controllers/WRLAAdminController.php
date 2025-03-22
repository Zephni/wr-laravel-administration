<?php

namespace WebRegulate\LaravelAdministration\Http\Controllers;

use App\WRLA\User;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;

/**
 * Class WRLAAdminController
 *
 * This class is responsible for handling the administration routes and actions in the Laravel application.
 * It extends the base Controller class and provides methods for managing the dashboard, browsing and upserting manageable models, and logging out.
 *
 * @package WebRegulate\LaravelAdministration\Http\Controllers
 */
class WRLAAdminController extends Controller
{
    /**
     * index / dashboard view
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        return view(WRLAHelper::getViewPath('dashboard'));
    }

    /**
     * ManageableModel browse view
     * @param Request $request
     * @param string $modelUrlAlias
     * @return View | RedirectResponse
     */
    public function browse(Request $request, string $modelUrlAlias): View | RedirectResponse
    {
        // Set page type
        WRLAHelper::setCurrentPageType(PageType::BROWSE);

        // Get preFilters from Get url
        $preFilters = $request->get('preFilters') ?? null;

        // Get the manageable model class by its URL alias
        $manageableModelClass = ManageableModel::getByUrlAlias($modelUrlAlias);

        // If the manageable model is null, redirect to the dashboard with error
        if (is_null($manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model with url alias `$modelUrlAlias` not found.");
        }

        // Set current active manageable model class
        WRLAHelper::setCurrentActiveManageableModelClass($manageableModelClass);

        // Check has browse permission
        if(!$manageableModelClass::getPermission(ManageableModelPermissions::BROWSE)) {
            return redirect()->route('wrla.dashboard')->with('error', "You do not have permission to browse ".$manageableModelClass::getDisplayName().".");
        }

        return view(WRLAHelper::getViewPath('livewire-content'), [
            'title' => 'Browse ' . $manageableModelClass::getDisplayName(),
            'livewireComponentAlias' => 'wrla.manageable-models.browse',
            'livewireComponentData' => [
                'manageableModelClass' => $manageableModelClass,
                'preFilters' => $preFilters ?? null
            ]
        ]);
    }

    /**
     * ManageableModel upsert view
     * @param Request $request
     * @param string $modelUrlAlias
     * @param ?int $id
     * @return View | RedirectResponse
     */
    public function upsert(Request $request, string $modelUrlAlias, ?int $modelId = null): View | RedirectResponse
    {
        // Get the manageable model by its URL alias
        $manageableModelClass = ManageableModel::getByUrlAlias($modelUrlAlias);

        // If the manageable model is null, redirect to the dashboard with error
        if (is_null($manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model with url alias `$modelUrlAlias` not found.");
        }

        // Set page type
        $upsertType = WRLAHelper::setCurrentPageType($modelId == null ? PageType::CREATE : PageType::EDIT);
        WRLAHelper::setCurrentActiveManageableModelClass($manageableModelClass);

        // If model id doesn't exist then return to dashboard with error
        if($modelId != null && $manageableModelClass::getBaseModelClass()::find($modelId) == null) {
            return redirect()->route('wrla.dashboard')->with('error', "Model ".$manageableModelClass." with ID `$modelId` not found.");
        }

        return view(WRLAHelper::getViewPath('livewire-content'), [
            'title' => str($upsertType->value)->lower()->title()->toString() . ' ' . $manageableModelClass::getDisplayName(),
            'livewireComponentAlias' => 'wrla.manageable-models.upsert',
            'livewireComponentData' => [
                'manageableModelClass' => $manageableModelClass,
                'upsertType' => $modelId == null ? PageType::CREATE : PageType::EDIT,
                'modelId' => $modelId,
            ]
        ]);
    }

    /**
     * ManageableModel upsert submit
     * @param Request $request
     * @param string $modelUrlAlias
     * @param ?int $id
     * @return RedirectResponse
     */
    public function upsertPost(Request $request, string $modelUrlAlias, ?int $modelId = null): RedirectResponse
    {
        try {
            // Get manageable model class by its URL alias
            $manageableModelClass = ManageableModel::getByUrlAlias($modelUrlAlias);
    
            // Check model class exists
            if (is_null($manageableModelClass) || !class_exists($manageableModelClass)) {
                return redirect()->route('wrla.dashboard')->with('error', "Manageable model `$manageableModelClass` not found.");
            }
    
            // Set page type and manageable model class
            WRLAHelper::setCurrentPageType($modelId == null ? PageType::CREATE : PageType::EDIT);
            WRLAHelper::setCurrentActiveManageableModelClass($manageableModelClass);
    
            if($modelId == null)
            {
                // Create new model instance
                $manageableModel = $manageableModelClass::make();
            }
            else
            {
                // Get model by it's id
                $manageableModel =  $manageableModelClass::make($modelId);
    
                // Check model id exists
                if ($manageableModel == null) {
                    return redirect()->route('wrla.dashboard')->with('error', "Model ".$manageableModelClass." with ID `$modelId` not found.");
                }
            }
    
            // Get manageable fields (we need to get these first and set the livewire fields, and then get them again so
            // we can make sure all the correct fields and values are exactly as they were prior to submitting the form.
            $manageableFields = $manageableModel->getManageableFieldsFinal();
    
            $usesLivewireFields = false;
            foreach($manageableFields as $manageableField) {
                if($manageableField->isModeledWithLivewire()) {
                    ManageableModel::setLivewireField($manageableField->getAttribute('name'), $request->input($manageableField->getAttribute('name')));
                    $usesLivewireFields = true;
                }
            }
    
            if($usesLivewireFields) {
                $manageableFields = $manageableModel->getManageableFieldsFinal();
            }
    
            // Run pre validation hook on all manageable fields and store in array to merge with request
            $requestMerge = [];
            foreach ($manageableFields as $manageableField) {
                $forceMergeIntoRequest = $manageableField->preValidation($request->input($manageableField->getAttribute('name')));
    
                if($forceMergeIntoRequest) {
                    $requestMerge[$manageableField->getAttribute('name')] = $manageableField->getAttribute('value');
                }
            }
    
            $request->merge($requestMerge);
    
            // Get validation rules for this model
            $rules = $manageableModel->getValidationRules()->toArray();
    
            // Validate
            $validator = Validator::make($request->all(), $rules);
    
            // Run manageable model inline validation
            $inlineValidationResult = $manageableModel->runInlineValidation($request);
    
            // If either validator or inline validation fails, redirect back with input and errors
            if($validator->fails() || $inlineValidationResult !== true) {
                // Get base validation errors
                $validationErrors = $validator->errors();
    
                // Add inline validation error key and value to validation errors message bag
                if($inlineValidationResult !== true) {
                    foreach($inlineValidationResult as $key => $value) {
                        $validationErrors->add($key, $value);
                    }
                }
    
                // Redirect back with input and errors
                return redirect()->back()->withInput()->withErrors($validationErrors)->withFragment('#first-message');
            }
    
            // Update only changed values on the model instance (Note that this also updates special relationship fields)
            $result = $manageableModel->updateModelInstanceProperties($request, $manageableFields, $request->all());
    
            // If the result is not true, redirect back with input and errors
            if($result !== true) {
                return redirect()->back()->withInput()->withErrors($result)->withFragment('#first-message');
            }
    
            // Save the model
            $manageableModel->getmodelInstance()->save();
    
            // Perform any necessary actions after updating the model instance
            $manageableModel->postUpdateModelInstance($request, $manageableModel->getmodelInstance());
    
            // Default success message
            $defaultSuccessMessage = 'Saved '.$manageableModel->getDisplayName().' #'.$manageableModel->getmodelInstance()->id.' successfully.';
            $defaultSuccessMessage .= ' <a href="'.route('wrla.manageable-models.create', ['modelUrlAlias' => $manageableModel->getUrlAlias()]).'" class="font-bold underline">Click here</a>';
            $defaultSuccessMessage .= $modelId == null
                ? ' to create another '.$manageableModel->getDisplayName(false).' record.'
                : ' to create a new '.$manageableModel->getDisplayName(false).' record.';
    
            // If wrla_override_redirect_route passed as GET parameter, redirect to that route
            if($request->has('wrla_override_redirect_route')) {
                // If wrla_override_success_message passed as GET parameter, use that as success message
                $message = $request->has('wrla_override_success_message')
                    ? $request->get('wrla_override_success_message')
                    : $defaultSuccessMessage;
    
                return redirect()->route($request->get('wrla_override_redirect_route'))->with('success', $message);
            }
        } catch (\Exception $e) {    
            // Redirect back with error message
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        // Redirect with success
        return redirect()->route('wrla.manageable-models.edit', [
            'modelUrlAlias' => $manageableModel->getUrlAlias(),
            'id' => $manageableModel->getmodelInstance()->id
        ])->with('success', $defaultSuccessMessage);
    }

    public function uploadWysiwygImage(Request $request)
    {
        return WRLAHelper::uploadWysiwygImage($request);
    }

    /**
     * View file manager
     *
     * @param Request $request
     * @return View
     */
    public function fileManager(Request $request): View
    {
        return view(WRLAHelper::getViewPath('livewire-content'), [
            'title' => 'File Manager',
            'livewireComponentAlias' => 'wrla.file-manager',
            'livewireComponentData' => []
        ]);
    }

    /**
     * View logs
     *
     * @param Request $request
     * @return View
     */
    public function logs(Request $request): View
    {
        return view(WRLAHelper::getViewPath('livewire-content'), [
            'title' => 'View Logs',
            'livewireComponentAlias' => 'wrla.logs',
            'livewireComponentData' => []
        ]);
    }

    /**
     * Manage account view
     *
     * @param Request $request
     * @return View
     */
    public function manageAccount(Request $request): View
    {
        // Set page type
        WRLAHelper::setCurrentPageType(PageType::EDIT);
        WRLAHelper::setCurrentActiveManageableModelClass(User::class);

        // Get manageable model instance
        $manageableModel = User::current();

        return view(WRLAHelper::getViewPath('livewire-content'), [
            'title' => "Manage Account",
            'livewireComponentAlias' => 'wrla.manageable-models.upsert',
            'livewireComponentData' => [
                'manageableModelClass' => User::class,
                'upsertType' => PageType::EDIT,
                'modelId' => $manageableModel->getModelInstance()->id,
                'overrideTitle' => 'Manage Account',
            ]
        ]);
    }
}
