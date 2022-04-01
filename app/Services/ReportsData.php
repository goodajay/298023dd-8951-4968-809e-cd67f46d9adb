<?php

namespace App\Services;

class ReportsData
{
    private $dataFiles;
    private $filesData;

    public function __construct(array $dataFiles=[])
    {
        $this->dataFiles = $dataFiles; 
        
        $this->loadFiles();
    }

    private function loadFiles()
    {
        if(empty($this->dataFiles)) {
            $this->filesData = [];
        }

        foreach($this->dataFiles as $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);
            $this->filesData[$fileName] = json_decode(file_get_contents($file), true);
        }
    }

    public function getFilesData()
    {
        return $this->filesData;
    }
}