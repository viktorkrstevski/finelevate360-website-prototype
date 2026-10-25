<?php
$hooks=[];
define('ABSPATH','/');
function is_admin(){return false;}
function home_url($path=''){return 'https://stg.finelevate360.com'.$path;}
function wp_parse_url($value,$component){return parse_url($value,$component);}
function register_activation_hook($file,$callback){}
function add_filter($name,$callback,$priority=10,$args=1){global $hooks;$hooks[$name]=$callback;}
function add_action($name,$callback,$priority=10,$args=1){}
class WP_Error { public $code; function __construct($code,$message){$this->code=$code;} }
require __DIR__.'/../wordpress/plugins/finelevate-staging-migration/migration.php';
if (!DISABLE_WP_CRON || WP_ENVIRONMENT_TYPE!=='staging') throw new Exception('Missing staging isolation');
if ($hooks['pre_get_ready_cron_jobs']()!==[])throw new Exception('Cron isolation missing');
if ($hooks['action_scheduler_queue_runner_concurrent_batches']()!==0)throw new Exception('Scheduler isolation missing');
$http=$hooks['pre_http_request'];
foreach(['https://finelevate360.com/wp-json/','https://api.brevo.com/send','http://downloads.wordpress.org/plugin.zip','https://api.wordpress.org.evil.test/'] as $url)if(!($http(false,[],$url) instanceof WP_Error))throw new Exception('Unsafe outbound allowed');
if($http(false,[],'https://downloads.wordpress.org/plugin/test.zip')!==false)throw new Exception('Official updates blocked');
if($hooks['pre_wp_mail']!=='__return_false')throw new Exception('Mail isolation missing');
echo "Migration guard: 9 isolation checks passed.\n";
