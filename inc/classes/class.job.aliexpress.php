<?php
/**
 * AEW_Job
 * 
 * FINISH TYPES:
 * 0: No
 * 1: Yes
 * 2: With Errors
 * 3: Processing
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if(!class_exists('AWE_Job')) {
    class AEW_Job {

        public static function create_job($job){

            if($job['job_id'] === NULL) {
                return;
            }
            global $wpdb;
            $wpdb->insert( 
                $wpdb->prefix.'aew_jobs', 
                array( 
                    'success_total' => $job['success_item_count'], 
                    'total_item' => $job['total_item_count'],
                    'jobID' => $job['job_id'],
                    'data_job' => json_encode($job['result_list'])
                ));
        }

        public static function update_job($job){
            global $wpdb;
           
            $job = self::get_status_code($job);

            if(!isset($job['result_list']['single_item_response_dto'])) {
                return;
            }

			
		    $reportingError = array();
			foreach($job['result_list']['single_item_response_dto'] as $e) {
                if(isset($e['item_execution_result'])) {
    				$msg = json_decode($e['item_execution_result'], true);
    				if(isset($msg['errorMessage'])) {
    				    
    				    $reportingError[] = array($e['item_content_id'] => $msg['errorMessage']);
    				}
                }
			}
            $wpdb->update( $wpdb->prefix.'aew_jobs', 
            array( 
                'success_total' => $job['success_item_count'], 
                'total_item' => $job['total_item_count'],
                'finished' => $job['finished'],
                'last_check' => date_i18n('Y-m-d H:i:s'),
                'data_job' => json_encode($reportingError)
            ), array(
                'jobID' => $job['job_id']
            ));
        }
        public static function get_jobs(){
            global $wpdb;
            if(isset($_GET['from_date']) and isset($_GET['to_date'])){
                $queryFecha = 'WHERE create_at BETWEEN "'.sanitize_text_field($_GET['from_date']).'" and "'.sanitize_text_field($_GET['to_date']).'"';
            }else{
                $last24 = date('Y-m-d H:i:s', strtotime('-24 hours'));
                $queryFecha = 'WHERE create_at > "'.$last24.'"';            
            }
            $ResultJobs = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."aew_jobs $queryFecha ORDER by id DESC");
            return $ResultJobs;
        }
        public static function get_pending_jobs(){
            global $wpdb;
            $ResultJobs = $wpdb->get_results( "SELECT jobID FROM ".$wpdb->prefix."aew_jobs WHERE finished = 0 ORDER by id DESC");
            return $ResultJobs;
        }
        public static function get_status_job($job_state) {
            $job_state = self::get_status_code($job_state);
            $job_state['codeFinished'] = $job_state['finished'];
            $job_state['classFinished'] = self::get_class_line_job($job_state['codeFinished']);
            $job_state['finished'] = self::get_status_byCode($job_state['finished']);
            return $job_state;
        }
        public static function get_class_line_job($job_state) {
            switch($job_state) {
                case 1:
                    return 'job-yes';
                break;
                case 2:
                    return 'job-error';
                break;
                case 3:
                    return 'job-processing';
                break;
                default:
                    return '';
                break;
            }
        }
        public static function get_status_code($job) {
            $job['finished'] = 3;
            if(!isset($job['result_list'])) {
                return $job;
            }
            if($job['result_list']['single_item_response_dto'] == "") {
                return $job;
            }
            if(intval($job['total_item_count']) == intval($job['success_item_count'])) {

                $job['finished'] = 1;

            }else{
                //Only One
                if(isset($job['result_list']['single_item_response_dto'][0]) && is_array($job['result_list']['single_item_response_dto'][0])) {
                    if(intval($job['total_item_count']) == sizeof($job['result_list']['single_item_response_dto'])) {
        
                        $job['finished'] = 2;
        
                    }elseif(intval($job['total_item_count']) > sizeof($job['result_list']['single_item_response_dto'])){
        
                        $job['finished'] = 3;
                    }

                }else{
                    if(intval($job['total_item_count']) == 1) {
        
                        $job['finished'] = 2;
        
                    }elseif(intval($job['total_item_count']) > sizeof($job['result_list']['single_item_response_dto'])){
        
                        $job['finished'] = 3;
                    }
                }
            }
            return $job;
        }
        public static function get_status_byCode($code) {
            switch($code) {
                case 1:
                    return __('Yes', 'aliexpress');
                break;
                case 2:
                    return __('With Errors', 'aliexpress');
                break;
                case 3:
                    return __('Processing', 'aliexpress');
                break;
                default:
                    return __('No', 'aliexpress');
                break;
            }
        }
    }
}
?>