<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ReportsService
{
    private $studentId;
    private $reportType;
    private $fileData;
    private $studObj;

    public function __construct(string $studentId, string $reportType, ReportsData $fileData)
    {
        $this->reportType = $reportType;
        $this->fileData = $fileData;    
        $this->studObj = $fileData->getStudInfo($studentId);
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
        $latest = $this->fileData->getStudRespDataViaStudentId($this->studObj['id'])->first();
        $assmtName = $this->fileData->getAssessmentName($latest['assessmentId']);   
        $rawScore = Arr::get($latest, 'results.rawScore');
        $totalRes = count($latest['responses']);
        $queIds = Arr::pluck($latest['responses'], 'questionId');
        $strands = $this->fileData->getStrandsViaQueIds($queIds);
        $perStrandResp = $this->calcCorrectResponsesPerStrand($latest['responses']);
        // dd($assmtName);
        // dd($strands);
        
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

    protected function formatOutput(array $response=[])
    {
        return implode("\n\r", $response);
    }

    protected function calcCorrectResponsesPerStrand(array $responses=[])
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

    protected function getProgressReport()
    {
        $studResp = $this->fileData->getStudRespDataViaStudentId($this->studObj['id'])->sortBy('completed')->groupBy('assessmentId');
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

    protected function getFeedbackReport()
    {

    }

    protected function formatDate(string $date, string $format='jS F Y h:i A')
    {
        return date($format, strtotime(str_replace('/','-',$date)));
    }

}