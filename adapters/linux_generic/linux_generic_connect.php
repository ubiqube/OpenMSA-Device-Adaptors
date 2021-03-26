<?php
/*
 * 	Version: 0.1: linux_generic_connect.php
 *  	Created: Jun 7, 2012
 *  	Available global variables
 *  	$sms_sd_ctx        	pointer to sd_ctx context to retreive usefull field(s)
 *  	$sms_sd_info        	sd_info structure
 *  	$sms_csp            	pointer to csp context to send response to user
 *  	$sdid
 *  	$sms_module         	module name (for patterns)
 *  	$SMS_RETURN_BUF    	string buffer containing the result
 */

// Open/close the session dialog with the router
// For communication with the router
require_once 'smsd/sms_common.php';
require_once 'smsd/expect.php';
require_once 'smsd/ssh_connection.php';
require_once 'smsd/telnet_connection.php';
require_once load_once('linux_generic', 'common.php');
require_once "$db_objects";

// return false if error, true if ok
function linux_generic_connect($sd_ip_addr = null, $login = null, $passwd = null, $adminpasswd = null, $port_to_use = null)
{
  global $sms_sd_ctx;
  global $model_data;
  global $priv_key;

  $data = json_decode($model_data, true);
  debug_dump($data, "DATA\n");
  try
  {
    // default private key name can be set in adapter config file sms_router.conf
    if (isset($data['priv_key']))
    {
      $priv_key = $data['priv_key'];
    }
    // check if the default private key name was overridden by a configuration variable
    if (isset($sd->SD_CONFIGVAR_list['SSH_KEY'])) {
      $priv_key = trim($sd->SD_CONFIGVAR_list['SSH_KEY']->VAR_VALUE);  
    }
    echo("found key name: ".$priv_key);

    if (isset( $data['class'])) {
      $class = $data['class'];
      echo("found class name: ".$class);
      $sms_sd_ctx = new $class($sd_ip_addr, $login, $passwd, $adminpasswd, $port_to_use);
    } else {
      $sms_sd_ctx = new LinuxGenericsshConnection($sd_ip_addr, $login, $passwd, $adminpasswd, $port_to_use);
    }
    $sms_sd_ctx->setParam("PROTOCOL", "SSH");
  }
  catch (SmsException $e)
  {
    debug_dump($e);
    return $e->getCode();
  }
  return SMS_OK;
}

// Disconnect
// return false if error, true if ok
function linux_generic_disconnect()
{
  $sms_sd_ctx = null;
  return SMS_OK;
}

function linux_generic_synchro_prompt()
{
  global $sms_sd_ctx;

  $msg = 'UBISyncro' . mt_rand(10000, 99999);
  $prompt = $sms_sd_ctx->getPrompt();
  sendexpectone(__FILE__ . ':' . __LINE__, $sms_sd_ctx, "echo -n {$msg}", "{$msg}{$prompt}");
}

class LinuxGenericsshConnection extends SshConnection
{
  public function do_store_prompt()
  {
    global $sendexpect_result;

    $this->sendCmd(__FILE__ . ':' . __LINE__, "stty -echo");
    $this->sendCmd(__FILE__ . ':' . __LINE__, "stty -onlcr ocrnl -echoctl -echoe -opost rows 0 columns 0 line 0");
    $tab[0] = '#';
    $tab[1] = '$';
    $index = sendexpect(__FILE__ . ':' . __LINE__, $this, '', $tab);
    $index = sendexpect(__FILE__ . ':' . __LINE__, $this, '', $tab);

    sendexpectone(__FILE__ . ':' . __LINE__, $this, 'echo -n UBISynchroForPrompt', 'UBISynchroForPrompt');

    $tab[0] = '#';
    $tab[1] = '$';
    $index = sendexpect(__FILE__ . ':' . __LINE__, $this, 'echo', $tab);

    $this->prompt = trim($sendexpect_result);
    if (strrchr($this->prompt, "\n") !== false)
    {
       $this->prompt = substr(strrchr($this->prompt, "\n"), 1);
    }

    echo "Prompt found: {$this->prompt} for {$this->sd_ip_config}\n";

    // synchronize again

    $msg = 'UBISyncro' . mt_rand(10000, 99999);
    $prompt = $this->prompt;
    sendexpectone(__FILE__ . ':' . __LINE__, $this, "echo -n {$msg}", "{$msg}{$prompt}");

  }

  public function do_start() {
      $this->setParam('chars_to_remove', array("\033[00m", "\033[m"));
  }

}

class LinuxsshKeyConnection extends SshKeyConnection
{

  public function do_store_prompt()
  {
    global $sendexpect_result;

    $this->sendCmd(__FILE__ . ':' . __LINE__, "stty -echo");
    $this->sendCmd(__FILE__ . ':' . __LINE__, "stty -onlcr ocrnl -echoctl -echoe -opost rows 0 columns 0 line 0");
    $tab[0] = '#';
    $tab[1] = '$';
    $index = sendexpect(__FILE__ . ':' . __LINE__, $this, '', $tab);
    $index = sendexpect(__FILE__ . ':' . __LINE__, $this, '', $tab);

    sendexpectone(__FILE__ . ':' . __LINE__, $this, 'echo -n UBISynchroForPrompt', 'UBISynchroForPrompt');

    $tab[0] = '#';
    $tab[1] = '$';
    $index = sendexpect(__FILE__ . ':' . __LINE__, $this, 'echo', $tab);

    $this->prompt = trim($sendexpect_result);
    if (strrchr($this->prompt, "\n") !== false)
    {
       $this->prompt = substr(strrchr($this->prompt, "\n"), 1);
    }

    echo "Prompt found: {$this->prompt} for {$this->sd_ip_config}\n";

    // synchronize again

    $msg = 'UBISyncro' . mt_rand(10000, 99999);
    $prompt = $this->prompt;
    sendexpectone(__FILE__ . ':' . __LINE__, $this, "echo -n {$msg}", "{$msg}{$prompt}");

  }

  public function do_start() {
      $this->setParam('chars_to_remove', array("\033[00m", "\033[m"));
  }
}



?>
