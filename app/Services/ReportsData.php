<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ReportsData
{
    /**
     *
     * @var array, path to data files
     */
    private $dataFiles;

    /**
     * read the data files and load the data into filesData array
     *
     * @var array $filesData
     */
    private $filesData;

    /**
     *
     * @param array $dataFiles=[]
     * 
     */
    public function __construct(array $dataFiles=[])
    {
        $this->dataFiles = $dataFiles; 
        
        $this->loadFiles();
    }

    /**
     * Read the files and convert the json data to array
     */
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

    /**
     * return the filesData property of the class
     * 
     * @return array
     */
    public function getFilesData() : array
    {
        return $this->filesData;
    }

    /**
     * Validate the required files data
     * 
     * @return bool
     */
    public function isValidFilesData() : bool
    {
        return !empty($this->getFilesData()) && !empty($this->getAssessmentData()) && !empty($this->getStudData()) && !empty($this->getQuestionsData()) && !empty($this->getStudRespData());
    }

    /**
     * get the assessments data from the files data
     * 
     * @return array
     */
    public function getAssessmentData() : array
    {
        return $this->getFilesData()['assessments'] ?? [];
    }

    /**
     * Get the questions data from the files data
     * 
     * @return array
     */
    public function getQuestionsData() : array
    {
        return $this->getFilesData()['questions'] ?? [];
    }

    /**
     * Get the student responses data from the files data
     * 
     * @return array
     */
    public function getStudRespData() : array
    {
        return $this->getFilesData()['student-responses'] ?? [];
    }

    /**
     * Get student data from the file data
     * 
     * @return array
     */
    public function getStudData() : array
    {
        return $this->getFilesData()['students'] ?? [];
    }

    /**
     * Get the student response details for the student id
     * 
     * @return Collection
     */
    public function getStudRespDataViaStudentId(string $studentId='') : Collection
    {
        if(empty($this->getStudRespData())) return collect([]);

        $studResData = collect($this->getStudRespData())
                    ->where('student.id', $studentId)
                    ->whereNotNull('completed')
                    ->sortByDesc('completed');
        
        return $studResData; 
    }

    /**
     * Get the assessment name from the assessment record
     * 
     * @return string
     */
    public function getAssessmentName(string $assmntId) : string
    {
        return empty($this->getAssessmentData()) ? '' : collect($this->getAssessmentData())->where('id', $assmntId)->first()['name']; 
    }

    /**
     * Get the student info array from the student data
     * 
     * @return array|null
     */
    public function getStudInfo(string $studId) : ?array
    {
        return collect($this->getStudData())->where('id', $studId)->first();
    }

    /**
     * Get list of strands based on the question Ids
     * 
     * @return Collection
     */
    public function getStrandsViaQueIds(array $qIds=[]) : Collection
    {
        return collect($this->getQuestionsData())->whereIn('id', $qIds)->pluck('strand')->unique()->values();
    }

    /**
     * Get strands collection group by strand
     * 
     * @return Collection
     */
    public function getStrandsGroupViaStrandName() : Collection
    {
        return collect($this->getQuestionsData())->groupBy('strand');
    }
}