<?php

include "AEJob.php";

class AEJobTest extends AEJob_base {

    public function __construct () {
        parent::__construct();
    }

    public static function save($job) {
        try {
            $serialized_job = serialize($job);
            $path = realpath("./JobFiles/") . $job->aejob_id . "job";
            file_put_contents($path, $serialized_job);
            return true;
        } catch(Exception $ex) {
            return false;
        }
    }

    public static function load($job_id) {
        try {
            $path = realpath("./JobFiles/") . job_id . "job";
            $serialized_job = file_get_contents($path);

            $job = unserialize($serialized_job);

            return $job;
        } catch(Exception $ex) {
            return false;
        }
    }

    public static function list_jobs() {
        return scandir(realpath("./JobFiles/"));
    }
}