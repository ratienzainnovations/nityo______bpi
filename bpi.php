<?php

class BPIExam{
    public $id;
    public $duration;
    public $dependencies;
    public $start_date;
    public $end_date;

    public function __construct($id, $duration, $dependencies) {

        $this->id = $id;
        $this->duration = $duration;
        $this->dependencies = $dependencies;
        $this->start_date = null;
        $this->end_date = null;

    }
}


function loadTask($filePath){
    $tasks = [];
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Read the header
        while (($data = fgetcsv($handle)) !== FALSE) {
            $id = $data[0];
            $duration = (int)$data[1];
            $dependencies = empty($data[2]) ? [] : explode(';', $data[2]);
            $tasks[] = new BPIExam($id, $duration, $dependencies);
        }
        fclose($handle);
    }
    //print_r($tasks);
    return $tasks;
}



function Scheduler($tasks){

    $container = [];
    $numofdependency = [];
    $taskMap = [];

    // dependencies
    foreach ($tasks as $task) {
        $taskMap[$task->id] = $task;
        $container[$task->id] = [];
        $numofdependency[$task->id] = 0;
    }

    //print_r($numofdependency);
    foreach ($tasks as $task) {
        foreach ($task->dependencies as $dependency) {
            if (isset($taskMap[$dependency])) {
                $container[$dependency][] = $task->id;
                $numofdependency[$task->id]++;
            }
        }
    }
  //  echo "\n-----";
  //  print_r($numofdependency);
    // sort task
    $queue = [];
    foreach ($taskMap as $taskId => $task) {
        if ($numofdependency[$taskId] == 0) {
            $queue[] = $taskId;
        }
    }

    $sortedTasks = [];

    while (count($queue) > 0) {
        $taskId = array_shift($queue);
        $sortedTasks[] = $taskId;

        foreach ($container[$taskId] as $neighbor) {
            $numofdependency[$neighbor]--;
            if ($numofdependency[$neighbor] == 0) {
                $queue[] = $neighbor;
            }
        }
    }

    // genrate start and end dates
    $today = new DateTime();
    foreach ($sortedTasks as $taskId) {
        $task = $taskMap[$taskId];
        if (empty($task->dependencies)) {
            $task->start_date = clone $today;
        } else {
            $maxEndDate = new DateTime();
            foreach ($task->dependencies as $dependency) {
                $depTask = $taskMap[$dependency];
                if ($depTask->end_date > $maxEndDate) {
                    $maxEndDate = $depTask->end_date;
                }
            }
            $task->start_date = (clone $maxEndDate)->modify('+1 day');
        }
        $task->end_date = (clone $task->start_date)->modify('+' . ($task->duration - 1) . ' days');
    }

    return $taskMap;
}


function DisplaySched($tasks, $planName){
    
    echo "Project Plan: " .  strtoupper($planName) ."\n";

    foreach ($tasks as $task) {

        $dependencies = empty($task->dependencies) ? 'No dependencies' : 'Dependencies on: Task ' . implode(', ', $task->dependencies);
        echo "Task {$task->id}: Start Date = {$task->start_date->format('Y-m-d')}, End Date = {$task->end_date->format('Y-m-d')} ({$dependencies})\n";
    }
    echo "\n";

}




//Ahr-Eey - Aug 2, 2024

$csvFiles = ['project_plan_1.csv', 'project_plan_2.csv']; 

header('Content-Type: plain/text');

foreach ($csvFiles as $csvFilePath) {
    $planName = pathinfo($csvFilePath, PATHINFO_FILENAME); 
    $tasks = loadTask($csvFilePath);
    $scheduledTasks = Scheduler($tasks);
    DisplaySched($scheduledTasks, $planName);
}



?>
