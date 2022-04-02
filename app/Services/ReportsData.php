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

    public function getAssessmentData()
    {
        return $this->getFilesData()['assessments'];
    }

    public function getQuestionsData()
    {
        return $this->getFilesData()['questions'];
    }

    public function getStudRespData()
    {
        return $this->getFilesData()['student-responses'];
    }

    public function getStudData()
    {
        return $this->getFilesData()['students'];
    }

    public function getStudRespDataViaStudentId(string $studentId='')
    {
        if(empty($this->getStudRespData())) return collect([]);

        $studResData = collect($this->getStudRespData())
                    ->where('student.id', $studentId)
                    ->whereNotNull('completed')
                    ->sortByDesc('completed');
        
        return $studResData; 
    }

    public function getAssessmentName(string $assmntId)
    {
        return empty($this->getAssessmentData()) ? '' : collect($this->getAssessmentData())->where('id', $assmntId)->first()['name']; 
    }

    public function getStudInfo(string $studId)
    {
        return collect($this->getStudData())->where('id', $studId)->first();
    }

    public function getStrandsViaQueIds(array $qIds=[])
    {
        return collect($this->getQuestionsData())->whereIn('id', $qIds)->pluck('strand')->unique()->values();
    }

    public function getStrandsGroupViaStrandName()
    {
        return collect($this->getQuestionsData())->groupBy('strand');
    }
}