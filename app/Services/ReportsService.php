<?php

namespace App\Services;

use Illuminate\Support\Str;

class ReportsService
{
    private $studentId;
    private $reportType;
    private $fileData;

    public function __construct(string $studentId, string $reportType, ReportsData $fileData)
    {
        $this->studentId = $studentId;
        $this->reportType = $reportType;
        $this->fileData = $fileData;    
    }

    public function getReport()
    {
        switch(Str::lower($this->reportType)){
            case 'diagnostic':
                return $this->getDiagnosticReport();
                break;
                
            case 'progress':
                return $this->getProgressReport();
                break;

            case 'feedback':
                return $this->getFeedbackReport();
                break;
        }
    }

    protected function getDiagnosticReport()
    {

    }

    protected function getProgressReport()
    {

    }

    protected function getFeedbackReport()
    {

    }
    
}