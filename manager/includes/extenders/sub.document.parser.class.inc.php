<?php
class SubParser {
	function SubParser()
	{
	}
	function sendmail($params=array(), $msg='')
	{
		global $modx;
		if(isset($params) && is_string($params))
		{
			if(strpos($params,'=')===false)
			{
				if(strpos($params,'@')!==false) $p['to']	  = $params;
				else                            $p['subject'] = $params;
			}
			else
			{
				$params_array = explode(',',$params);
				foreach($params_array as $k=>$v)
				{
					$k = trim($k);
					$v = trim($v);
					$p[$k] = $v;
				}
			}
		}
		else
		{
			$p = $params;
			unset($params);
		}
		if(isset($p['sendto'])) $p['to'] = $p['sendto'];
		
		if(isset($p['to']) && preg_match('@^[0-9]+$@',$p['to']))
		{
			$userinfo = $modx->getUserInfo($p['to']);
			$p['to'] = $userinfo['email'];
		}
		if(isset($p['from']) && preg_match('@^[0-9]+$@',$p['from']))
		{
			$userinfo = $modx->getUserInfo($p['from']);
			$p['from']	 = $userinfo['email'];
			$p['fromname'] = $userinfo['username'];
		}
		if($msg==='' && !isset($p['body']))
		{
			$p['body'] = $_SERVER['REQUEST_URI'] . "\n" . $_SERVER['HTTP_USER_AGENT'] . "\n" . $_SERVER['HTTP_REFERER'];
		}
		elseif(is_string($msg) && 0<strlen($msg)) $p['body'] = $msg;
		
		$modx->loadExtension('MODxMailer');
		$sendto = (!isset($p['to']))   ? $modx->config['emailsender']  : $p['to'];
		$sendto = explode(',',$sendto);
		foreach($sendto as $address)
		{
			list($name, $address) = $modx->mail->address_split($address);
			$modx->mail->AddAddress($address,$name);
		}
		if(isset($p['cc']))
		{
			$p['cc'] = explode(',',$sendto);
			foreach($p['cc'] as $address)
			{
				list($name, $address) = $modx->mail->address_split($address);
				$modx->mail->AddCC($address,$name);
			}
		}
		if(isset($p['bcc']))
		{
			$p['bcc'] = explode(',',$sendto);
			foreach($p['bcc'] as $address)
			{
				list($name, $address) = $modx->mail->address_split($address);
				$modx->mail->AddBCC($address,$name);
			}
		}
		if(isset($p['from'])) list($p['fromname'],$p['from']) = $modx->mail->address_split($p['from']);
		$modx->mail->From	 = (!isset($p['from']))  ? $modx->config['emailsender']  : $p['from'];
		$modx->mail->FromName = (!isset($p['fromname'])) ? $modx->config['site_name'] : $p['fromname'];
		$modx->mail->Subject  = (!isset($p['subject']))  ? $modx->config['emailsubject'] : $p['subject'];
		$modx->mail->Body	 = $p['body'];
		$rs = $modx->mail->send();
		return $rs;
	}
	
	function rotate_log($target='event_log',$limit=2000, $trim=100)
	{
		global $modx, $dbase;
		
		if($limit < $trim) $trim = $limit;
		
		$count = $modx->db->getValue($modx->db->select('COUNT(id)',"[+prefix+]{$target}"));
		$over = $count - $limit;
		if(0 < $over)
		{
			$trim = ($over + $trim);
			$modx->db->delete("[+prefix+]{$target}",'','',$trim);
		}
		$result = $modx->db->query("SHOW TABLE STATUS FROM {$dbase}");
		while ($row = $modx->db->getRow($result))
		{
			$modx->db->query('OPTIMIZE TABLE ' . $row['Name']);
		}
	}
	
	function logEvent($evtid, $type, $msg, $source= 'Parser')
	{
		global $modx;
		
		$evtid= intval($evtid);
		$type = intval($type);
		if ($type < 1) $type= 1; // Types: 1 = information, 2 = warning, 3 = error
		if (3 < $type) $type= 3;
		$msg= $modx->db->escape($msg);
		$source= $modx->db->escape($source);
		if (function_exists('mb_substr'))
		{
			$source = mb_substr($source, 0, 50 , $modx->config['modx_charset']);
		}
		else
		{
			$source = substr($source, 0, 50);
		}
		$LoginUserID = $modx->getLoginUserID();
		if ($LoginUserID == '' || $LoginUserID===false) $LoginUserID = '-';
		
		$fields['eventid']     = $evtid;
		$fields['type']        = $type;
		$fields['createdon']   = $_SERVER['REQUEST_TIME'];
		$fields['source']      = $source;
		$fields['description'] = $msg;
		$fields['user']        = $LoginUserID;
		$insert_id = $modx->db->insert($fields,'[+prefix+]event_log');
		if(!$modx->db->conn) $source = 'DB connect error';
		if(isset($modx->config['send_errormail']) && $modx->config['send_errormail'] !== '0')
		{
			if($modx->config['send_errormail'] <= $type)
			{
				$subject = 'Error mail from ' . $modx->config['site_name'];
				$modx->sendmail($subject,$source);
			}
		}
		if (!$insert_id)
		{
			echo 'Error while inserting event log into database.';
			exit();
		}
		else
		{
			$trim  = (isset($modx->config['event_log_trim']))  ? intval($modx->config['event_log_trim']) : 100;
			if(($insert_id % $trim) == 0)
			{
				$limit = (isset($modx->config['event_log_limit'])) ? intval($modx->config['event_log_limit']) : 2000;
				$modx->rotate_log('event_log',$limit,$trim);
			}
		}
	}
	
    function clearCache($params=array()) {
    	global $modx;
    	
    	if($modx->isBackend() && !$modx->hasPermission('empty_cache')) return;
    	if(is_string($params) && preg_match('@^[1-9][0-9]*$@',$params))
    	{
    		if($modx->config['cache_type']==='2')
    		{
    			$url = $modx->config['base_url'] . $modx->makeUrl($params,'','','root_rel');
    			$filename = md5($url);
    		}
    		else
    			$filename = "docid_{$params}";
    		$page_cache_path = "{$base_path}assets/cache/{$filename}.pageCache.php";
    		if(is_file($page_cache_path))
    		{
    			unlink($page_cache_path);
    			$modx->config['cache_type'] = '0';
    		}
    		return;
    	}
    	
    	if(opendir(MODX_BASE_PATH . 'assets/cache')!==false)
    	{
    		$showReport = ($params['showReport']) ? $params['showReport'] : false;
    		$target = ($params['target']) ? $params['target'] : 'pagecache,sitecache';
    		
			include_once MODX_MANAGER_PATH . 'processors/cache_sync.class.processor.php';
			$sync = new synccache();
			$sync->setCachepath(MODX_BASE_PATH . 'assets/cache/');
			$sync->setReport($showReport);
			$sync->setTarget($target);
			$sync->emptyCache(); // first empty the cache
			return true;
		}
		else return false;
	}
	
    function messageQuit($msg= 'unspecified error', $query= '', $is_error= true, $nr= '', $file= '', $source= '', $text= '', $line= '', $output='') {
    	global $modx;

        $version= isset ($GLOBALS['version']) ? $GLOBALS['version'] : '';
		$release_date= isset ($GLOBALS['release_date']) ? $GLOBALS['release_date'] : '';
        $request_uri = $modx->decoded_request_uri;
        $request_uri = htmlspecialchars($request_uri, ENT_QUOTES);
        $ua          = htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES);
        $referer     = htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES);
        if ($is_error) {
	        $str = '<h3 style="color:red">&laquo; MODX Parse Error &raquo;</h3>
                    <table border="0" cellpadding="1" cellspacing="0">
                    <tr><td colspan="2">MODX encountered the following error while attempting to parse the requested resource:</td></tr>
                    <tr><td colspan="2"><b style="color:red;">&laquo; ' . $msg . ' &raquo;</b></td></tr>';
        } else {
	        $str = '<h3 style="color:#003399">&laquo; MODX Debug/ stop message &raquo;</h3>
                    <table border="0" cellpadding="1" cellspacing="0">
                    <tr><td colspan="2">The MODX parser recieved the following debug/ stop message:</td></tr>
                    <tr><td colspan="2"><b style="color:#003399;">&laquo; ' . $msg . ' &raquo;</b></td></tr>';
        }

        if (!empty ($query)) {
	        $str .= '<tr><td colspan="2"><div style="font-weight:bold;border:1px solid #ccc;padding:8px;color:#333;background-color:#ffffcd;">SQL &gt; <span id="sqlHolder">' . $query . '</span></div>
                    </td></tr>';
        }

        $errortype= array (
            E_ERROR             => "ERROR",
            E_WARNING           => "WARNING",
            E_PARSE             => "PARSING ERROR",
            E_NOTICE            => "NOTICE",
            E_CORE_ERROR        => "CORE ERROR",
            E_CORE_WARNING      => "CORE WARNING",
            E_COMPILE_ERROR     => "COMPILE ERROR",
            E_COMPILE_WARNING   => "COMPILE WARNING",
            E_USER_ERROR        => "USER ERROR",
            E_USER_WARNING      => "USER WARNING",
            E_USER_NOTICE       => "USER NOTICE",
            E_STRICT            => "STRICT NOTICE",
            E_RECOVERABLE_ERROR => "RECOVERABLE ERROR",
            E_DEPRECATED        => "DEPRECATED",
            E_USER_DEPRECATED   => "USER DEPRECATED"
        );

		if(!empty($nr) || !empty($file))
		{
			$str .= '<tr><td colspan="2"><b>PHP error debug</b></td></tr>';
			if ($text != '')
			{
				$str .= '<tr><td colspan="2"><div style="font-weight:bold;border:1px solid #ccc;padding:8px;color:#333;background-color:#ffffcd;">Error : ' . $text . '</div></td></tr>';
			}
			if($output!='')
			{
				$str .= '<tr><td colspan="2"><div style="font-weight:bold;border:1px solid #ccc;padding:8px;color:#333;background-color:#ffffcd;">' . $output . '</div></td></tr>';
			}
			$str .= '<tr><td valign="top">ErrorType[num] : </td>';
			$str .= '<td>' . $errortype [$nr] . "[{$nr}]</td>";
			$str .= '</tr>';
			$str .= "<tr><td>File : </td><td>{$file}</td></tr>";
			$str .= "<tr><td>Line : </td><td>{$line}</td></tr>";
		}
        
        if ($source != '')
        {
            $str .= "<tr><td>Source : </td><td>{$source}</td></tr>";
        }

        $str .= '<tr><td colspan="2"><b>Basic info</b></td></tr>';

        $str .= '<tr><td valign="top">REQUEST_URI : </td>';
        $str .= "<td>{$request_uri}</td>";
        $str .= '</tr>';
        
	    if(isset($_GET['a']))      $action = $_GET['a'];
	    elseif(isset($_POST['a'])) $action = $_POST['a'];
        if(isset($action) && !empty($action))
        {
        	include_once($modx->config['core_path'] . 'actionlist.inc.php');
        	global $action_list;
        	if(isset($action_list[$action])) $actionName = " - {$action_list[$action]}";
        	else $actionName = '';
			$str .= '<tr><td valign="top">Manager action : </td>';
			$str .= "<td>{$action}{$actionName}</td>";
			$str .= '</tr>';
        }
        
        if(preg_match('@^[0-9]+@',$modx->documentIdentifier))
        {
        	$resource  = $modx->getDocumentObject('id',$modx->documentIdentifier);
        	$url = $modx->makeUrl($modx->documentIdentifier,'','','full');
        	$link = '<a href="' . $url . '" target="_blank">' . $resource['pagetitle'] . '</a>';
			$str .= '<tr><td valign="top">Resource : </td>';
			$str .= '<td>[' . $modx->documentIdentifier . ']' . $link . '</td></tr>';
        }

        if(!empty($modx->currentSnippet))
        {
            $str .= "<tr><td>Current Snippet : </td>";
            $str .= '<td>' . $modx->currentSnippet . '</td></tr>';
        }

        if(!empty($modx->event->activePlugin))
        {
            $str .= "<tr><td>Current Plugin : </td>";
            $str .= '<td>' . $modx->event->activePlugin . '(' . $modx->event->name . ')' . '</td></tr>';
        }

        $str .= "<tr><td>Referer : </td><td>{$referer}</td></tr>";
        $str .= "<tr><td>User Agent : </td><td>{$ua}</td></tr>";

        $str .= "<tr><td>IP : </td>";
        $str .= '<td>' . $_SERVER['REMOTE_ADDR'] . '</td>';
        $str .= '</tr>';

        $str .= '<tr><td colspan="2"><b>Benchmarks</b></td></tr>';

        $str .= "<tr><td>MySQL : </td>";
	    $str .= '<td>[^qt^] ([^q^] Requests)</td>';
        $str .= '</tr>';

        $str .= "<tr><td>PHP : </td>";
	    $str .= '<td>[^p^]</td>';
        $str .= '</tr>';

        $str .= "<tr><td>Total : </td>";
	    $str .= '<td>[^t^]</td>';
        $str .= '</tr>';

	    $str .= "<tr><td>Memory : </td>";
	    $str .= '<td>[^m^]</td>';
	    $str .= '</tr>';
	    
        $str .= "</table>\n";

        $totalTime= ($modx->getMicroTime() - $modx->tstart);

		$mem = (function_exists('memory_get_peak_usage')) ? memory_get_peak_usage()  : memory_get_usage() ;
		$total_mem = $modx->nicesize($mem - $modx->mstart);
		
        $queryTime= $modx->queryTime;
        $phpTime= $totalTime - $queryTime;
        $queries= isset ($modx->executedQueries) ? $modx->executedQueries : 0;
        $queryTime= sprintf("%2.4f s", $queryTime);
        $totalTime= sprintf("%2.4f s", $totalTime);
        $phpTime= sprintf("%2.4f s", $phpTime);

        $str= str_replace('[^q^]', $queries, $str);
        $str= str_replace('[^qt^]',$queryTime, $str);
        $str= str_replace('[^p^]', $phpTime, $str);
        $str= str_replace('[^t^]', $totalTime, $str);
        $str= str_replace('[^m^]', $total_mem, $str);

        if(isset($php_errormsg) && !empty($php_errormsg)) $str = "<b>{$php_errormsg}</b><br />\n{$str}";
		$str .= '<br />' . $modx->get_backtrace(debug_backtrace()) . "\n";
		

        // Log error
        if(!empty($modx->currentSnippet)) $source = 'Snippet - ' . $modx->currentSnippet;
        elseif(!empty($modx->event->activePlugin)) $source = 'Plugin - ' . $modx->event->activePlugin;
        elseif($source!=='') $source = 'Parser - ' . $source;
        elseif($query!=='')  $source = 'SQL Query';
        else             $source = 'Parser';
        if(isset($actionName) && !empty($actionName)) $source .= $actionName;
        switch($nr)
        {
        	case E_DEPRECATED :
        	case E_USER_DEPRECATED :
        	case E_STRICT :
        	case E_NOTICE :
        	case E_USER_NOTICE :
        		$error_level = 2;
        		break;
        	default:
        		$error_level = 3;
        }
        $modx->logEvent(0, $error_level, $str,$source);
        if($modx->error_reporting==='99' && !isset($_SESSION['mgrValidated'])) return true;

        // Set 500 response header
	    if($error_level !== 2) header('HTTP/1.1 500 Internal Server Error');

        // Display error
	    if (isset($_SESSION['mgrValidated']))
	    {
	        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html><head><title>MODX Content Manager ' . $version . ' &raquo; ' . $release_date . '</title>
	             <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	             <link rel="stylesheet" type="text/css" href="' . $modx->config['site_url'] . 'manager/media/style/' . $modx->config['manager_theme'] . '/style.css" />
	             <style type="text/css">body { padding:10px; } td {font:inherit;}</style>
	             </head><body>
	             ' . $str . '</body></html>';
	    
	    }
        else  echo 'Error';
        ob_end_flush();

        exit;
    }

	function get_backtrace($backtrace)
	{
		$str = "<p><b>Backtrace</b></p>\n";
		$str  .= '<table>';
		$backtrace = array_reverse($backtrace);
		foreach ($backtrace as $key => $val)
		{
			$key++;
			if(substr($val['function'],0,11)==='messageQuit') break;
			elseif(substr($val['function'],0,8)==='phpError') break;
			$path = str_replace('\\','/',$val['file']);
			if(strpos($path,MODX_BASE_PATH)===0) $path = substr($path,strlen(MODX_BASE_PATH));
    		switch($val['type'])
			{
    			case '->':
    			case '::':
    				$functionName = $val['function'] = $val['class'] . $val['type'] . $val['function'];
    				break;
    			default:
    				$functionName = $val['function'];
				}
			$str .= "<tr><td valign=\"top\">{$key}</td>";
        	$str .= "<td>{$functionName}()<br />{$path} on line {$val['line']}</td>";
		}
		$str .= '</table>';
		return $str;
	}

    function sendRedirect($url, $count_attempts= 0, $type= 'REDIRECT_HEADER', $responseCode= '301')
    {
    	global $modx;
    	
    	if (empty($url)) return false;
    	elseif(preg_match('@^[1-9][0-9]*$@',$url)) {
    		$url = $modx->makeUrl($url,'','','full');
    	}
    	
    	if ($count_attempts == 1) {
    		// append the redirect count string to the url
    		$currentNumberOfRedirects= isset ($_REQUEST['err']) ? $_REQUEST['err'] : 0;
    		if ($currentNumberOfRedirects > 3) {
    			$modx->messageQuit("Redirection attempt failed - please ensure the document you're trying to redirect to exists. <p>Redirection URL: <i>{$url}</i></p>");
    		} else {
    			$currentNumberOfRedirects += 1;
    			if (strpos($url, '?') > 0) $url .= '&';
    			else                       $url .= '?';
    			$url .= "err={$currentNumberOfRedirects}";
    		}
    	}
    	if ($type == 'REDIRECT_REFRESH') $header= "Refresh: 0;URL={$url}";
    	elseif($type == 'REDIRECT_META') {
    		$header= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=' . $url . '" />';
    		echo $header;
    		exit;
    	}
    	elseif($type == 'REDIRECT_HEADER') {
    		// check if url has /$base_url
    		global $base_url, $site_url;
    		if (substr($url, 0, strlen($base_url)) == $base_url) {
    			// append $site_url to make it work with Location:
    			$url= $site_url . substr($url, strlen($base_url));
    		}
    		if (strpos($url, "\n") === false) $header= 'Location: ' . $url;
    		else $modx->messageQuit('No newline allowed in redirect url.');
    	}
    	if (!empty($responseCode)) {
    		if    (strpos($responseCode, '301') !== false) $responseCode = 301;
    		elseif(strpos($responseCode, '302') !== false) $responseCode = 302;
    		elseif(strpos($responseCode, '303') !== false) $responseCode = 303;
    		elseif(strpos($responseCode, '307') !== false) $responseCode = 307;
    		else $responseCode = '';
    		if(!empty($responseCode))
    		{
        		header($header, true, $responseCode);
        		exit;
    		}
    	}
    	header($header);
    	exit();
    }
    
	function sendForward($id, $responseCode= '')
	{
		global $modx;
		
		if ($modx->forwards > 0)
		{
			$modx->forwards= $modx->forwards - 1;
			$modx->documentIdentifier= $id;
			$modx->documentMethod= 'id';
			$modx->documentObject= $modx->getDocumentObject('id', $id);
			if ($responseCode)
			{
				header($responseCode);
			}
			echo $modx->prepareResponse();
		}
		else
		{
			header('HTTP/1.0 500 Internal Server Error');
			die('<h1>ERROR: Too many forward attempts!</h1><p>The request could not be completed due to too many unsuccessful forward attempts.</p>');
		}
		exit();
	}
	
	function sendErrorPage()
	{
		global $modx;
		
		// invoke OnPageNotFound event
		$modx->invokeEvent('OnPageNotFound');
		
		if($modx->config['error_page']) $dist = $modx->config['error_page'];
		else                            $dist = $modx->config['site_start'];
		
		$modx->http_status_code = '404';
		$modx->sendForward($dist, 'HTTP/1.0 404 Not Found');
	}
	
	function sendUnauthorizedPage()
	{
		global $modx;
		
		// invoke OnPageUnauthorized event
		$_REQUEST['refurl'] = $modx->documentIdentifier;
		$modx->invokeEvent('OnPageUnauthorized');
		
		if($modx->config['unauthorized_page']) $dist = $modx->config['unauthorized_page'];
		elseif($modx->config['error_page'])    $dist = $modx->config['error_page'];
		else                                   $dist = $modx->config['site_start'];
		
		$modx->http_status_code = '403';
		$modx->sendForward($dist , 'HTTP/1.1 403 Forbidden');
	}

	# Displays a javascript alert message in the web browser
	function webAlert($msg, $url= '')
	{
		global $modx;
		
		$msg= addslashes($modx->db->escape($msg));
		if (substr(strtolower($url), 0, 11) == 'javascript:')
		{
			$act= '__WebAlert();';
			$fnc= 'function __WebAlert(){' . substr($url, 11) . '};';
		}
		else
		{
			$act= $url ? "window.location.href='" . addslashes($url) . "';" : '';
		}
		$html= "<script>{$fnc} window.setTimeout(\"alert('{$msg}');{$act}\",100);</script>";
		if ($modx->isFrontend())
		{
			$modx->regClientScript($html);
		}
		else
		{
			echo $html;
		}
	}

	function getSnippetId()
	{
		global $modx;
		
		if ($modx->currentSnippet)
		{
			$snip = $modx->db->escape($modx->currentSnippet);
			$rs= $modx->db->select('id', '[+prefix+]site_snippets', "name='{$snip}'",'',1);
			$row= @ $modx->db->getRow($rs);
			if ($row['id']) return $row['id'];
		}
		return 0;
	}
	
	function getSnippetName()
	{
		global $modx;
		
		return $modx->currentSnippet;
	}
	
	function runSnippet($snippetName, $params= array ())
	{
		global $modx;
		
		if (isset ($modx->snippetCache[$snippetName]))
		{
			$snippet= $modx->snippetCache[$snippetName];
			$properties= $modx->snippetCache["{$snippetName}Props"];
		}
		else
		{ // not in cache so let's check the db
			$esc_name = $modx->db->escape($snippetName);
			$result= $modx->db->select('name,snippet,properties','[+prefix+]site_snippets',"name='{$esc_name}'");
			if ($modx->db->getRecordCount($result) == 1)
			{
				$row = $modx->db->getRow($result);
				$snippet= $modx->snippetCache[$snippetName]= $row['snippet'];
				$properties= $modx->snippetCache["{$snippetName}Props"]= $row['properties'];
			}
			else
			{
				$snippet= $modx->snippetCache[$snippetName]= "return false;";
				$properties= '';
			}
		}
		// load default params/properties
		$parameters= $modx->parseProperties($properties);
		$parameters= array_merge($parameters, $params);
		// run snippet
		return $modx->evalSnippet($snippet, $parameters);
	}
	
	# Change current web user's password - returns true if successful, oterhwise return error message
	function changeWebUserPassword($oldPwd, $newPwd)
	{
		global $modx;
		
		if ($_SESSION['webValidated'] != 1) return false;
		
		$uid = $modx->getLoginUserID();
		$ds = $modx->db->select('id,username,password', '[+prefix+]web_users', "`id`='{$uid}'");
		$total = $modx->db->getRecordCount($ds);
		if ($total != 1) return false;
		
		$row= $modx->db->getRow($ds);
		if ($row['password'] == md5($oldPwd))
		{
			if (strlen($newPwd) < 6) return 'Password is too short!';
			elseif ($newPwd == '')   return "You didn't specify a password for this user!";
			else
			{
				$newPwd = $modx->db->escape($newPwd);
				$modx->db->update("password = md5('{$newPwd}')", '[+prefix+]web_users', "id='{$uid}'");
				// invoke OnWebChangePassword event
				$modx->invokeEvent('OnWebChangePassword',
				array
				(
					'userid' => $row['id'],
					'username' => $row['username'],
					'userpassword' => $newPwd
				));
				return true;
			}
		}
		else return 'Incorrect password.';
	}
	
	# add an event listner to a plugin - only for use within the current execution cycle
	function addEventListener($evtName, $pluginName)
	{
		global $modx;
		
		if(!$evtName || !$pluginName) return false;
		
		if (!isset($modx->pluginEvent[$evtName]))
		{
			$modx->pluginEvent[$evtName] = array();
		}
		
		$result = array_push($modx->pluginEvent[$evtName], $pluginName);
		
		return $result; // return array count
	}
	
    # remove event listner - only for use within the current execution cycle
    function removeEventListener($evtName, $pluginName='') {
    	global $modx;
    	
        if (!$evtName)
            return false;
        if ( $pluginName == '' ){
            unset ($modx->pluginEvent[$evtName]);
            return true;
        }else{
            foreach($modx->pluginEvent[$evtName] as $key => $val){
                if ($modx->pluginEvent[$evtName][$key] == $pluginName){
                    unset ($modx->pluginEvent[$evtName][$key]);
                    return true;
                }
            }
        }
        return false;
    }

	function regClientCSS($src, $media)
	{
    	global $modx;
    	
		if (empty($src) || isset ($modx->loadedjscripts[$src])) return '';
		
		$nextpos = max(array_merge(array(0),array_keys($modx->sjscripts)))+1;
		
		$modx->loadedjscripts[$src]['startup'] = true;
		$modx->loadedjscripts[$src]['version'] = '0';
		$modx->loadedjscripts[$src]['pos']     = $nextpos;
		
		if (strpos(strtolower($src), '<style') !== false || strpos(strtolower($src), '<link') !== false)
		{
			$modx->sjscripts[$nextpos]= $src;
		}
		else
		{
			$media = $media ? 'media="' . $media . '" ' : '';
			$modx->sjscripts[$nextpos] = "\t" . '<link rel="stylesheet" type="text/css" href="'.$src.'" '.$media.'/>';
		}
	}

     # Registers Client-side JavaScript 	- these scripts are loaded at the end of the page unless $startup is true
	function regClientScript($src, $options= array('name'=>'', 'version'=>'0', 'plaintext'=>false), $startup= false)
	{
		global $modx;
		
		if (empty($src)) return ''; // nothing to register
		
		if (!is_array($options))
		{
			if(is_bool($options))       $options = array('plaintext'=>$options);
			elseif(is_string($options)) $options = array('name'=>$options);
			else                        $options = array();
		}
		$name      = isset($options['name'])      ? strtolower($options['name']) : '';
		$version   = isset($options['version'])   ? $options['version'] : '0';
		$plaintext = isset($options['plaintext']) ? $options['plaintext'] : false;
		$key       = !empty($name)                ? $name : $src;
		
		$useThisVer= true;
		if (isset($modx->loadedjscripts[$key]))
		{ // a matching script was found
			// if existing script is a startup script, make sure the candidate is also a startup script
			if ($modx->loadedjscripts[$key]['startup']) $startup= true;
			
			if (empty($name))
			{
				$useThisVer= false; // if the match was based on identical source code, no need to replace the old one
			}
			else
			{
				$useThisVer = version_compare($modx->loadedjscripts[$key]['version'], $version, '<');
			}
			
			if ($useThisVer)
			{
				if ($startup==true && $modx->loadedjscripts[$key]['startup']==false)
				{ // remove old script from the bottom of the page (new one will be at the top)
					unset($modx->jscripts[$modx->loadedjscripts[$key]['pos']]);
				}
				else
				{ // overwrite the old script (the position may be important for dependent scripts)
					$overwritepos= $modx->loadedjscripts[$key]['pos'];
				}
			}
			else
			{ // Use the original version
				if ($startup==true && $modx->loadedjscripts[$key]['startup']==false)
				{ // need to move the exisiting script to the head
					$version= $modx->loadedjscripts[$key][$version];
					$src= $modx->jscripts[$modx->loadedjscripts[$key]['pos']];
					unset($modx->jscripts[$modx->loadedjscripts[$key]['pos']]);
				}
				else return ''; // the script is already in the right place
			}
		}
		
		if ($useThisVer && $plaintext!=true && (strpos(strtolower($src), "<script") === false))
		{
			$src= "\t" . '<script type="text/javascript" src="' . $src . '"></script>';
		}
		
		if ($startup)
		{
			$pos = isset($overwritepos) ? $overwritepos : max(array_merge(array(0),array_keys($modx->sjscripts)))+1;
			$modx->sjscripts[$pos]= $src;
		}
		else
		{
			$pos = isset($overwritepos) ? $overwritepos : max(array_merge(array(0),array_keys($modx->jscripts)))+1;
			$modx->jscripts[$pos]= $src;
		}
		$modx->loadedjscripts[$key]['version']= $version;
		$modx->loadedjscripts[$key]['startup']= $startup;
		$modx->loadedjscripts[$key]['pos']= $pos;
	}
	
    function regClientStartupHTMLBlock($html) // Registers Client-side Startup HTML block
    {
    	$this->regClientScript($html, true, true);
    }
    
    function regClientHTMLBlock($html) // Registers Client-side HTML block
    {
    	$this->regClientScript($html, true);
    }
    
	# Registers Startup Client-side JavaScript - these scripts are loaded at inside the <head> tag
	function regClientStartupScript($src, $options)
	{
        $this->regClientScript($src, $options, true);
	}
}