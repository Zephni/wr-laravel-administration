<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use Exception;
use Livewire\Component;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class FileManager extends Component
{
    use WithFileUploads;

    /* Properties
    --------------------------------------------------------------------------*/
    public ?string $currentFileSystemName = null; // Set on mount
    public string $viewingDirectory = '';
    public array $currentDirectories = [];
    public array $currentFiles = [];
    public ?string $highlightedItem = null;
    public string $viewingItemContent = 'No content 1';
    public ?string $viewingItemType = null; // null, text, image, video, file (link)
    public int $viewFileMaxCharacters = 0;
    public $listeners = [
        'createDirectory' => 'createDirectory',
        'deleteFile' => 'deleteFile',
        'deleteDirectory' => 'deleteDirectory',
    ];

    public $uploadFile; // Modelled, used for file upload
    public $uploadFilePath; // Absolute path to directory for upload
    public $replaceFile; // Modelled, used for individual file replacement
    public $replaceFilePath; // Absolute path to file for replacement

    /* Update fields
    --------------------------------------------------------------------------*/
    public function updatedUploadFile($value)
    {
        if ($value && $this->uploadFilePath !== null) {
            // Get full file name
            $fileName = $value->getClientOriginalName();

            // Store the new file
            $this->getCurrentFileSystem()->put("{$this->viewingDirectory}/$fileName", $value->get());

            // Reset uploadFilePath
            $this->uploadFilePath = null;

            // Refresh the directories and files list
            $this->refresh();

            // View the new file
            $this->viewFile($this->viewingDirectory, $fileName);
        }
    }

    public function updatedReplaceFile($value)
    {
        if ($value && $this->replaceFilePath) {
            // Get last part of absolute file path
            $fileName = str($this->replaceFilePath)->afterLast('/')->toString();

            // Store the new file
            $this->getCurrentFileSystem()->put("{$this->viewingDirectory}/$fileName", $value->get());

            // Reset replaceFilePath
            $this->replaceFilePath = null;

            // Refresh the directories and files list
            $this->refresh();

            // View the new file
            $this->viewFile($this->viewingDirectory, $fileName);
        }
    }

    /* Livewire Methods / Hooks
    --------------------------------------------------------------------------*/
    public function mount()
    {
        // Redirect if file manager is not enabled in config
        if(config('wr-laravel-administration.file_manager.enabled', false) !== true) {
            $this->redirect(route('wrla.dashboard'));
        }

        // Config
        $this->viewFileMaxCharacters = config('wr-laravel-administration.file_manager.max_characters', 500000);
        $attemptFileSystemName = collect(config('wr-laravel-administration.file_manager.file_systems', []))->keys()->first();

        // Set file system
        $this->setFileSystem($attemptFileSystemName);

        // Set all log files and directories
        $this->setCurrentDirectoriesAndFiles();

        // Find the first file within found logs and set it as the default viewing log
        $this->selectFirstFileInCurrentDirectory();
    }

    public function render()
    {
        // Render the view
        return view(WRLAHelper::getViewPath('livewire.file-manager'), [
            'fullDirectoryPath' => $this->getFullDirectoryPath(false),
            'fullFilePath' => $this->getFullFilePath(false),
        ]);
    }

    /* Methods
    --------------------------------------------------------------------------*/
    public function getFullDirectoryPath($absolute = false)
    {
        // If viewing directory is empty, return root path
        if (empty($this->viewingDirectory)) {
            return $absolute ? $this->getCurrentFileSystem()->path('') : '';
        }

        // Get the full path to the current viewing directory
        $directoryPath = str_replace('.', '/', $this->viewingDirectory);

        return $absolute
            ? str_replace('\\', '/', $this->getCurrentFileSystem()->path($directoryPath))
            : $directoryPath;
    }

    public function getFullFilePath($absolute = false)
    {
        // Get the full path to the current viewing directory
        $directoryPath = $this->getFullDirectoryPath($absolute);

        // If no file is highlighted, return the directory path
        if ($this->highlightedItem === null) {
            return $directoryPath;
        }

        // Append the highlighted item to the directory path
        $filePath = $directoryPath . '/' . $this->highlightedItem;

        return $absolute
            ? str_replace('\\', '/', $this->getCurrentFileSystem()->path($filePath))
            : $filePath;
    }

    private function setCurrentDirectoriesAndFiles()
    {
        // If no file system is set, return
        if ($this->currentFileSystemName === null) {
            $this->currentDirectories = [];
            $this->currentFiles = [];
            return;
        }

        // Get all directories within given path and get last part of path
        $this->currentDirectories = $this->getCurrentFileSystem()->directories(str_replace('.', '/', $this->viewingDirectory));
        $this->currentDirectories = array_map(fn($directory) => str($directory)->afterLast('/')->toString(), $this->currentDirectories);
        
        // Get all files but ignore hidden files and get last part of path
        $this->currentFiles = $this->getCurrentFileSystem()->files(str_replace('.', '/', $this->viewingDirectory));
        $this->currentFiles = array_filter($this->currentFiles, fn($file) => !str_starts_with($file, '.'));
        $this->currentFiles = array_map(fn($file) => str($file)->afterLast('/')->toString(), $this->currentFiles);
    }

    private function getFileContent(string $filePath): string
    {
        // File path
        $filePath = str($filePath)->ltrim('/')->toString();

        // Get full path to file
        $fullPath = str($this->getCurrentFileSystem()->path($filePath))->replace('\\', '/')->toString();

        // Get file mime type
        try {
            $mimeType = mime_content_type($fullPath);
        } catch (Exception $e) {
            $mimeType = 'error';
        }

        // Match type of file
        $this->viewingItemType = match($mimeType) {
            'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp' => 'image',
            'video/mp4' => 'video',
            'text/plain', 'text/html', 'application/json', 'application/javascript' => 'text',
            'application/pdf' => 'pdf',
            'error' => 'error',
            default => 'unhandled',
        };

        // If file is text, return content
        if ($this->viewingItemType === 'text')
        {
            // Use storage to get file contents
            return $this->getCurrentFileSystem()->get($filePath);
        }
        elseif ($this->viewingItemType === 'image')
        {
            $rawImageData = $this->getCurrentFileSystem()->get($filePath);
            return 'data:image/'.str($mimeType)->afterLast('/').';base64,' . base64_encode($rawImageData);;
        }
        elseif ($this->viewingItemType === 'video')
        {
            // Get public path to video file
            return $this->getCurrentFileSystem()->url($filePath);
        }
        elseif ($this->viewingItemType === 'pdf')
        {
            return '❌ Cannot display PDF contents...';
        }
        elseif ($this->viewingItemType === 'error')
        {
            return '❌ Cannot display file contents...';
        }

        $this->highlightedItem = null;
        return '❌ Mime type not handled...';
    }

    public function getFullPath(string $directory, string $file): string
    {
        // If directory is empty, just pass the file
        if (empty($directory)) {
            return $this->getCurrentFileSystem()->path(str($file)->rtrim('/'));
        }

        // Swap .'s for /'s
        $directory = str_replace('.', '/', $directory);

        return str_replace('\\', '/', $this->getCurrentFileSystem()->path(str($directory . '/' . $file)->rtrim('/')));
    }

    public function switchDirectory(string $directory)
    {
        // Reset viewing item type
        $this->viewingItemType = null;

        // If $directory is .., go up a directory
        if (!empty($this->viewingDirectory) && $directory === '..')
        {
            $this->viewingDirectory = !str($this->viewingDirectory)->contains('.')
                ? ''
                : str($this->viewingDirectory)->beforeLast('.');
        }
        // Otherwise set viewing directory
        else
        {
            $this->viewingDirectory = trim($directory, '.');
        }

        $this->refresh();
        $this->selectFirstFileInCurrentDirectory();
        $this->viewFile($this->viewingDirectory, $this->highlightedItem);
    }

    public function viewFile(string $directoryPath, ?string $filePath)
    {
        if($filePath === null) {
            return;
        }

        $this->viewingDirectory = $directoryPath;
        $this->highlightedItem = $filePath;

        $this->viewingItemContent = $this->getFileContent(str($this->viewingDirectory)->replace('.', '/')->toString()."/{$this->highlightedItem}");
    }

    public function deleteFile(string $directoryPath, string $name)
    {
        $diskPath = rtrim($directoryPath.'/'.$name, '/');

        // Delete file
        $this->getCurrentFileSystem()->delete($diskPath);

        // Clean up, refresh and re-render
        $this->viewingDirectory = $directoryPath;
        $this->refresh();
        $this->selectFirstFileInCurrentDirectory();
        $this->render();
    }

    public function deleteDirectory(string $directoryPath, string $name)
    {
        $diskPath = rtrim($directoryPath.'/'.$name, '/');

        // Delete directory
        $this->getCurrentFileSystem()->deleteDirectory($diskPath);

        // Clean up, refresh and re-render
        $this->viewingDirectory = $directoryPath;
        $this->refresh();
        $this->selectFirstFileInCurrentDirectory();
        $this->render();
    }

    public function createDirectory(string $newDirectoryName)
    {
        // Get the full path to the new directory
        $fullPath = $this->getFullPath($this->viewingDirectory, $newDirectoryName);

        // Check if directory already exists
        if ($this->getCurrentFileSystem()->exists($fullPath)) {
            $this->addError('directory', 'Directory already exists.');
            return;
        }

        // Create the new directory
        $this->getCurrentFileSystem()->makeDirectory($this->viewingDirectory . '/' . $newDirectoryName);

        // Refresh the directories and files list
        $this->refresh();
    }

    public function refresh()
    {
        // Set all log files and directories
        $this->setCurrentDirectoriesAndFiles();

        // Update current selected log content
        $this->viewFile($this->viewingDirectory, $this->highlightedItem);
    }

    public function selectFirstFileInCurrentDirectory()
    {
        $this->highlightedItem = null;

        if(count($this->currentFiles) === 0) {
            return;
        }

        $this->highlightedItem = $this->currentFiles[array_key_first($this->currentFiles)];

        $this->viewingItemContent = $this->getFileContent("{$this->viewingDirectory}/{$this->highlightedItem}");
    }

    public function setFileSystem(string $fileSystem)
    {
        if(config("wr-laravel-administration.file_manager.file_systems.$fileSystem.enabled", false) !== true) {
            $this->currentFileSystemName = null;
            return;
        }

        $this->currentFileSystemName = $fileSystem;
    }

    public function getFileSystemAbsolutePath(): string
    {
        // Get the absolute path to the current file system
        return str_replace('\\', '/', $this->getCurrentFileSystem()->path(''));
    }

    private function getCurrentFileSystem()
    {
        return Storage::disk($this->currentFileSystemName);
    }
}
