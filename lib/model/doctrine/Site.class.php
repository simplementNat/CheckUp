<?php

/**
 * Site
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    checkup
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Site extends BaseSite
{
  public $curl_getinfo = '';
  private $page = '';
  private $message = '';
  private $format = '';
  private $redirection = false;
  private $failed = 0;
  
  public function retrieveHttpCode()
  {
    $this->curl_getinfo = $this->curlCall();
    
    $http_code = $this->curl_getinfo['http_code'];
    
    if (($http_code == 301) || ($http_code == 302)) {
    	$this->redirection = $http_code;
    	$this->curl_getinfo = $this->curlCall(true);
    	$http_code = $this->curl_getinfo['http_code'];
    }
    
    if (($http_code != $this->http_code) || ($http_code != 200))
    {
    	$this->failed++;
    	$this->curl_getinfo = '';
    	
    	if ($this->failed > 2) {
	    	if ($this->http_code != '')
	    	{
	    		$this->message .= sprintf($this->format, 'http code', $this->http_code, $http_code);
	    	}
	      $this->http_code = $http_code;
	      $this->save();
    	} else {
    		$this->retrieveHttpCode();
    	}
    }
	    		
    
    return $http_code;
  }
  
  public function retrieveUpInfo($format)
  {
  	$this->format = $format;
  	
    $this->retrieveHttpCode();
    
	  $this->last_check = new Doctrine_Expression('NOW()');
    $this->save();
    
    return $this->message;
  }
  
  public function retrieveNetworkInfo($format) 
  {
  	$this->format = $format;
  	
  	$this->retrieveIP();
  	$this->retrieveHost();
    
    return $this->message;
  }
  
  public function retrieveContentInfo($format)
  {
  	$this->format = $format;
  	
    //$this->retrieveScreenshot();
    //$this->retrieveFavicon();
    $this->retrieveTitle();
      
      /* todo
       * - retrieve apple favicon
       * - save google analytics state
       * - save piwik state
       * - save title
       * - save meta description
       * - save meta keyword
       */  
    
    return $this->message;
  }
  
  private function curlCall($follow_redirect = false) 
  {
    if($this->curl_getinfo == '' || $follow_redirect)
    {
	    $handle = curl_init($this->url);
	    curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
	    if ($follow_redirect)
	    {
        curl_setopt($handle,  CURLOPT_FOLLOWLOCATION, 1);
	    }
	    $this->page = curl_exec($handle);
	    
			$effective_url = curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
			$effective_url = str_replace('HTTPS://', 'https://', $effective_url);
			$this->effective_url = str_replace('HTTP://', 'http://', $effective_url);
			$this->save();
	      
      $this->curl_getinfo = curl_getinfo($handle);
	    curl_close($handle);
    }
    
    return $this->curl_getinfo;
  }
  
  public function retrieveIP()
  {
    $ip = gethostbyname($this->url);
    
    if ($ip != $this->ip)
    {
    	if ($this->ip != '')
    	{
    		$this->message .= sprintf($this->format, 'ip', $this->ip, $ip);
    	}
      
      $this->ip = $ip;
      $this->save();
    }
    
    return $ip;
  }
  
  public function retrieveHost()
  {
    $host = gethostbyaddr(gethostbyname($this->url));
    
    if ($host != $this->host)
    {
    	if ($this->host != '')
    	{
    		$this->message .= sprintf($this->format, 'host', $this->host, $host);
    	}
    	
      
      $this->host = $host;
      $this->save();
    }
    
    return $host;
  }
  
  public function retrieveTitle()
  {
    $this->curl_getinfo = $this->curlCall();
    
    if ($this->page)
    {
	    $this->curlCall();
	    $dom = new Zend_Dom_Query($this->page);
	    $results = $dom->query('title');
	    $title = '';
	    foreach ($results as $result) {
	      	$title = $result->nodeValue;
	    }
    
	    if ($title != $this->title)
	    {
	    	if ($this->title != '')
	    	{
	    		$this->message .= sprintf($this->format, 'title', $this->title, $title);
	    	}
	    	
	      $this->title = $title;
	      $this->save();
	    }
	    
	    return $title;
    }
    
	  return false;
  }
   
  public function retrieveFavicon()
  {
    $screenshot_dir = '';
    $screenshot_temp_dir = '';
    $downloaded = false;
    $favicon_url = '';
        
  	$screenshot_dir = $screenshot_dir.'/favicon/'.$this->getName().'-';
    
    $dest = $screenshot_dir.date('Y-m').'.ico';
    
    if ($this->page)
    {
	    if (!file_exists($dest)) 
	    {
		    $this->curlCall();
		    $dom = new Zend_Dom_Query($this->page);
		    $results = $dom->query('link');
		    foreach ($results as $result) {
		      if ($result->getAttribute('rel') == 'shortcut icon')
		      {
		      	$href = $result->getAttribute('href');
		      	if (substr($href, 0, 7) == 'http://')
		      	{
		      		$favicon_url = $href;
		      	} elseif (substr($href, 0, 1) == '/') {
	            $favicon_url = 'http://'.$this->name.$href;
		      	} else {
	            $favicon_url = 'http://'.$this->effective_url.'/../'.$href;
		      	}
		      }
		    }
		    
		    if ($favicon_url == '')
		    {
		    	$favicon_url = 'http://'.$this->getName().'/favicon.ico';
		    }
	    
	      if (copy($favicon_url, $dest))
	      {
	        $downloaded = true;
	      }
	    }
    }
    
      
    if ($downloaded)
    {
      $this->last_favicon = new Doctrine_Expression('NOW()');
      $this->save();
    } 
    
    return $screenshot_dir.date('Y-m').'.ico';
  }
  
  public function retrieveScreenshot()
  {
    $screenshot_dir = '';
    $screenshot_temp_dir = '';
  	
  	$screenshot_dir = $screenshot_dir.'/screenshot/'.$this->getName().'-';
  	
	  if (!file_exists($screenshot_dir.date('Y-m').'.jpg')) 
	  {
	    //$src = 'http://images.websnapr.com/?size=S&key=VGe46D2C8i46&url='.$this->getName();
	    //$src = 'http://www.myscreenshots.net/?url='.$this->getName().'&size=320x240';
	    //$src = 'http://www.robothumb.com/src/?url='.$this->getName().'&size=800x600';//&alt='.url_image_remplacement;
	    //$src = 'http://screenshots.snyke.com/target=http://'.$this->getName();
	    //$src = 'http://add.shotbot.net/k=2558dd10689e/nojs/http://'.$this->getName();
	    //$src = 'http://static.shotbot.net/'.md5('http://'.$this->getName()).'/1024.jpg';
	    //$src = 'http://snapcasa.com/get.aspx?code=12974&size=l&url=http://'.$this->getName();
	    //$src = 'http://thumbs.miwim.fr/img.php?url=http://'.$this->getName().'&size=800x600';//&remplace=url_image_remplacement
	    
	  	
      $src = 'http://www.apercite.fr/api/apercite/800x600/oui/non/http://'.$this->getName();
      // to update
      //$src = 'http://www.apercite.fr/api/maj-apercite/simplementNat/f2b9543448bbe64f1261def5a0dc9cab/adresse/oui/oui/http://'.$this->getName();
      
	    $temp = $screenshot_temp_dir.$this->getName().'.jpg';
	    
	    if(copy($src, $temp))
	    {
		    if (filesize($temp) > 32384)
		    {
			    $img = new sfImage($temp, 'image/png');
			    $img->saveAs($screenshot_dir.date('Y-m').'.png');
        
	        $this->last_screenshot = new Doctrine_Expression('NOW()');
	        $this->save();
		    }
		    
	      unlink($temp);
	    }
	    
	  }
	  
	  return $screenshot_dir.date('Y-m').'.jpg';
  }
}
