<?php

namespace App\Console\Commands;

use App\Services\ReportsData;
use App\Services\ReportsService;
use Illuminate\Console\Command;

class run_reports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run_reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to run the student assessment reports using cli';

    private $studentID;
    private $reportID;
    const _REPORT_IDS_ = [
        1 => 'Diagnostic',
        2 => 'Progress',
        3 => 'Feedback'
    ];

    private $dataFilePaths;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->dataFilePaths = config('reports.file_path');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if(!$this->validateDataFiles()){
            $this->error('No data files exists for the reports.');
            return 0;
        }

        $this->askStudentId();
        $this->askReportId();

        $reportsServ = new ReportsService(
            $this->studentID, 
            $this->getReportType(),
            new ReportsData($this->dataFilePaths)
        );

        $this->info($reportsServ->getReport());

        return 0;
    }

    private function validateDataFiles()
    {
        if(empty($this->dataFilePaths)) return false;

        foreach($this->dataFilePaths as $file){
            if(!file_exists($file)){
                return false;
                break;
            }
        }

        return true;
    }

    private function askStudentId()
    {
        $this->studentID = $this->ask('Student ID');
    }

    private function askReportId()
    {
        $this->reportID = $this->ask('Report to generate (1 for Diagnostic, 2 for Progress, 3 for Feedback)');
        if(!$this->validateReportId()){
            $this->error('Invalid report id');
            $this->askReportId();
        }   
    }

    private function validateReportId()
    {
        return in_array($this->reportID, array_keys(self::_REPORT_IDS_));
    }

    private function getReportType()
    {
        return self::_REPORT_IDS_[$this->reportID];
    }
}
