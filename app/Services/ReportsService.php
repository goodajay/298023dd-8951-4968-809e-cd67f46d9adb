<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @package App\Services
 * 
 * ReportService
 * 
 * @param $reportType
 * @param $fileData
 * @param $studObj
 */
class ReportsService
{
    /**
     * Report type
     * 
     * @var string
     */
    private $reportType;

    /**
     * Reports Data object
     * 
     * @var ReportsData
     */
    private $fileData;
    
    /**
     * Student information
     * @var array
     */
    private $studObj;


    /**
     *
     * @param string $studentId
     * @param string $reportType
     * @param ReportsData $fileData
     * 
     */
    public function __construct(string $studentId, string $reportType, ReportsData $fileData)
    {
        $this->reportType = $reportType;
        $this->fileData = $fileData;    
        $this->studObj = $fileData->getStudInfo($studentId);
    }

    /**
     * function to get the report based on studentId and report type
     */
    public function getReport()
    {
        if(empty($this->studObj) || !$this->fileData->isValidFilesData()) {
            return $this->emptyRecOutput();
        }

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

    /**
     * Function to return the empty record output string
     */
    private function emptyRecOutput()
    {
        return 'No records found for the student or no data files found.';
    }

    /**
     * Function to run the diagnostic report
     * This will return the details report for the latest assessment by providing the correct, incorrect reponses from each strand from the assessment
     * 
     * @return string
     */
    protected function getDiagnosticReport()
    {
        $latest = $this->fileData->getStudRespDataViaStudentId($this->studObj['id'])->first();
        if(empty($latest)) return $this->emptyRecOutput();

        $assmtName = $this->fileData->getAssessmentName($latest['assessmentId']);   
        $rawScore = Arr::get($latest, 'results.rawScore');
        $totalRes = count($latest['responses']);
        $perStrandResp = $this->calcCorrectResponsesPerStrand($latest['responses']);
        
        $return[] = sprintf(
            "%s %s recently completed %s assessment on %s.\n\rHe got %s questions right out of %d. Details by strand given below:\n\r",
            $this->studObj['firstName'], 
            $this->studObj['lastName'], 
            $assmtName, 
            $this->formatDate($latest['completed']),
            $rawScore, 
            $totalRes
        );

        foreach($perStrandResp as $strand=>$resp) {
            $return[] = sprintf("%s: %s out of %s correct", $strand, $resp['correct'], $resp['total']);
        }

        return $this->formatOutput($return);
    }

    /**
     * format output to be displayed on the cli
     * 
     * @return string
     */
    protected function formatOutput(array $response=[])
    {
        return implode("\n\r", $response);
    }

    /**
     * Function to calculate the correct, incorrect and total responses from each strand for the student
     * 
     * @return array
     */
    protected function calcCorrectResponsesPerStrand(array $responses=[]) : array
    {
        $strandQues = collect($this->fileData->getQuestionsData())->groupBy('strand');
        $resp = collect($responses);
        $return = [];

        foreach($strandQues as $strand=>$ques) {
            $ques = Arr::pluck($ques->toArray(), 'config.key', 'id');
            $strandRes = $resp->whereIn('questionId', array_keys($ques));

            $return[$strand] = [
                'total' => $strandRes->count(),
                'correct' => $strandRes->filter(function($r) use ($ques){
                    return $r['response'] === $ques[$r['questionId']]; 
                })->count(),
                'incorrect' => $strandRes->filter(function($r) use ($ques){
                    return $r['response'] !== $ques[$r['questionId']]; 
                })->count(),
            ]; 
        }

        return $return;
    }

    /**
     * Function to return the progress report for the all assessments
     * It will also compare the results of earliest and latest assessments
     * 
     * @return string
     */
    protected function getProgressReport()
    {
        $studResp = $this->fileData->getStudRespDataViaStudentId($this->studObj['id'])->sortBy('completed')->groupBy('assessmentId');
        if($studResp->isEmpty()) return $this->emptyRecOutput();

        $return = [];

        foreach($studResp as $assmntId => $resp){
            $assmtName = $this->fileData->getAssessmentName($assmntId);

            $return[] = sprintf(
                "%s %s has completed %s assessment %s times in total. Date and raw score given below:\n\r",
                $this->studObj['firstName'], 
                $this->studObj['lastName'],
                $assmtName,
                count($resp)
            );

            foreach($resp as $res){
                $return[] = sprintf(
                    "Data: %s, Raw Score: %s out of %s",
                    $this->formatDate($res['assigned'], 'jS F Y'),
                    $res['results']['rawScore'],
                    count($res['responses'])
                );
            }

            $first = collect($resp)->first()['results']['rawScore'];
            $last = collect($resp)->last()['results']['rawScore'];
            $return[] = sprintf(
                "\n\r%s %s got %s more correct in the recent completed assessment than the oldest:\n\r",
                $this->studObj['firstName'], 
                $this->studObj['lastName'],
                ($last - $first)
            );
        }

        return $this->formatOutput($return);
    }

    /**
     * Function to return the feedback report for the latest assessment
     * @return string
     */
    protected function getFeedbackReport() : string
    {
        $latest = $this->fileData->getStudRespDataViaStudentId($this->studObj['id'])->first();
        if(empty($latest)) return $this->emptyRecOutput();

        $assmtName = $this->fileData->getAssessmentName($latest['assessmentId']);   
        $rawScore = Arr::get($latest, 'results.rawScore');
        $responses = collect($latest['responses']);
        $totalRes = $responses->count();
        
        $return[] = sprintf(
            "%s %s recently completed %s assessment on %s.\n\rHe got %s questions right out of %d. Feedback for wrong answers given below:\n\r",
            $this->studObj['firstName'], 
            $this->studObj['lastName'], 
            $assmtName, 
            $this->formatDate($latest['completed']),
            $rawScore, 
            $totalRes
        );

        $respQue = $responses->pluck('response', 'questionId')->all();

        $incQues = collect($this->fileData->getQuestionsData())->filter(function($que) use($respQue){
            return $que['config']['key'] !== $respQue[$que['id']];
        });

        foreach($incQues as $que) {
            $queId = $que['id'];
            $correctKey = $que['config']['key'];
            $incOption = head(Arr::where($que['config']['options'], function($val, $key) use($respQue, $queId) {
                return $val['id'] === $respQue[$queId];
            }));

            $correctOption = head(Arr::where($que['config']['options'], function($val, $key) use($correctKey) {
                return $val['id'] === $correctKey;
            }));

            $return[] = sprintf("Question: %s", $que['stem']);
            $return[] = sprintf("Your answer: %s with value %s", $incOption['label'], $incOption['value']);
            $return[] = sprintf("Right answer: %s with value %s", $correctOption['label'], $correctOption['value']);
            $return[] = sprintf("Hint: %s", $que['config']['hint']);
        }

        return $this->formatOutput($return);
    }

    /**
     * Format the date to format
     * 
     * @param string $date,
     * @param string $format, default 'jS F Y h:i A'
     * 
     * @return string
     */
    protected function formatDate(string $date, string $format='jS F Y h:i A')
    {
        return date($format, strtotime(str_replace('/','-',$date)));
    }

}