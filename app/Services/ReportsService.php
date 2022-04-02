<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ReportsService
{
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
        if(empty($this->studObj)) return 'No records found for the student';

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
        $latest = $this->fileData->getStudRespDataViaStudentId($this->studObj['id'])->first();
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

    protected function formatDate(string $date, string $format='jS F Y h:i A')
    {
        return date($format, strtotime(str_replace('/','-',$date)));
    }

}