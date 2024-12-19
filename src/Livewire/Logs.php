<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use Livewire\Component;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class Logs extends Component
{
    /* Properties
    --------------------------------------------------------------------------*/
    public string $viewingLogsDirectory = '';
    public array $logDirectoriesAndFiles = [];
    public ?string $viewingLogFile = null;
    public string $viewingLogContent = 'No content 1';

    /* Livewire Methods / Hooks
    --------------------------------------------------------------------------*/
    public function mount()
    {
        // Get all log files and directories
        $this->logDirectoriesAndFiles = WRLAHelper::getDirectoriesAndFiles(storage_path('logs'));

        // Find the first file within found logs and set it as the default viewing log
        $this->selectFirstLogFileInCurrentDirectory();
    }

    public function render()
    {
        // Get current directories and files
        $currentDirectoriesAndFiles = empty($this->viewingLogsDirectory)
            ? $this->logDirectoriesAndFiles
            : data_get($this->logDirectoriesAndFiles, $this->viewingLogsDirectory, []);

        // Prepend .. directory if viewing a subdirectory
        if (!empty($this->viewingLogsDirectory)) {
            $currentDirectoriesAndFiles = ['..' => '..'] + $currentDirectoriesAndFiles;
        }

        return view(WRLAHelper::getViewPath('livewire.logs'), [
            'currentDirectoriesAndFiles' => $currentDirectoriesAndFiles,
        ]);
    }
    
    /* Methods
    --------------------------------------------------------------------------*/
    private function getLogContent(string $logFile): string
    {
        $fullPath = storage_path('logs/' . str($logFile)->ltrim('/'));

        return file_exists($fullPath)
            ? file_get_contents($fullPath)
            : "Error finding file at path: {$fullPath}";
    }

    public function getFullPath(string $directory, string $file): string
    {
        // If directory is empty, just pass the file
        if (empty($directory)) {
            return storage_path('logs/' . $file);
        }

        // Swap .'s for /'s
        $directory = str_replace('.', '/', $directory);

        return storage_path('logs/' . $directory . '/' . $file);
    }

    public function switchDirectory(string $directory)
    {
        $this->viewingLogsDirectory = $directory;
        $this->selectFirstLogFileInCurrentDirectory();
        $this->viewLogFile($this->viewingLogsDirectory, $this->viewingLogFile);
    }

    public function viewLogFile(string $directoryPath, string $logFile)
    {
        $this->viewingLogsDirectory = $directoryPath;
        $this->viewingLogFile = $logFile;
        $this->viewingLogContent = $this->getLogContent("{$this->viewingLogsDirectory}/{$this->viewingLogFile}");
    }

    public function selectFirstLogFileInCurrentDirectory()
    {
        $currentDirectoryContents = empty($this->viewingLogsDirectory)
            ? $this->logDirectoriesAndFiles
            : data_get($this->logDirectoriesAndFiles, $this->viewingLogsDirectory, []);
            
        foreach ($currentDirectoryContents as $directory => $directoryOrFile) {
            if (is_string($directoryOrFile)) {
                $this->viewingLogFile = $directoryOrFile;
                break;
            }
        }

        $this->viewingLogContent = $this->getLogContent("{$this->viewingLogsDirectory}/{$this->viewingLogFile}");
    }
}