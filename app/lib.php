<?php

ini_set('default_charset', 'utf-8');
define('APP_ROOT', dirname(dirname(__FILE__)) );

include_once APP_ROOT . '/.shhh';
date_default_timezone_set( CFG_TIMEZONE );

/**
 * TxtQuick class.
 *
 *
 */
class TxtQuick {

	/**
	 * get function.
	 *
	 * @access public
	 * @static
	 * @param int $json (default: 0)
	 * @param int $offset (default: 0)
	 * @param int $num (default: 1000)
	 * @return void
	 */

  static public function get($json = 0, $offset = 0, $num = 1000)
  {

    $sql = sprintf("SELECT SmsSid,FromCountry,FromCity,Phone,FromZip,Body,Posted FROM r ORDER BY Posted DESC LIMIT %d, %d", $offset, $num) ;

    $posts = TxtQuick_Utils::get_all( $sql );

    if ( $json == 1)
    {
      header('Content-type: application/json');
      print json_encode( $posts );
    }
    else
    {
    return $posts;
    }
  }

}


/**
 * TxtQuick_SMS class.
 *
	 <code>
	 $t = new TxtQuick_SMS( $sms );
	 $t->process();
	 </code>
 *
 */
class TxtQuick_SMS {
	var $sms;
	var $response = '';

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $sms
	 * @return void
	 */
	public function __construct($sms)
	{
    $sms['Body'] = TxtQuick_Utils::cleaner($sms['Body']);

    $sms['PhoneHash'] = $this->hash_phone($sms['From']);

    $sms['From'] = $this->tap_phone($sms['From']);

    $this->sms = $sms;
	}

	/**
	 * process function. only check for Empties
	 *
	 * @access public
	 * @return void
	 */
	public function process()
	{
    //log for debug
    TxtQuick_Utils::log($this->sms);

    //save everything for now
    $this->save($this->sms);

    //email it out
    //$this->email();

    //respond to user
    $this->respond();
	}

	/**
	 * respond with thanks or query
	 *
	 * @access public
	 * @return void
	 */
	public function respond()
	{
    $body = trim($this->sms['Body']);

    if ( empty( $body ) ):
      $this->response = 'What do you feel? Que sientes?';
    else:
      $this->response = 'Gracias. Thanks. Merci !';
    endif;

    $this->twilio();
	}

	/**
	 * wrap for twilio (abstract for other SMS providors)
	 *
	 * @access public
	 * @return void
	 */
	public function twilio()
	{
    header("content-type: text/xml");
    $template  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $template .= sprintf("<Response><Sms>%s</Sms></Response>", $this->response);
    print $template;
	}

	/**
	 * send email
	 *
	 * @access public
	 * @return void
	 */
	public function email()
	{
    $to      = CFG_EMAILS;
    $subject = 'txtQuick Response';
    $message = $this->sms['Body'];
    $headers = 'From: web@txtback.com' . "\r\n";
    mail($to, $subject, $message, $headers);
	}

	/**
	 * truncate phone
	 *
	 * @access public
	 * @return void
	 */
	private function tap_phone($p)
	{
    return substr($p, 2, 6);
	}

	/**
	 * hash phone for multiple input tracking
	 *
	 * @access public
	 * @return void
	 */
	private function hash_phone($p)
	{
		return crypt($p, CFG_SALT ); 
	}

  /**
  * save the text 
  *
  * @access private
  * @param mixed $i
  * @return void
  */
  private function save($i)
  {
    $sql = array(
      'q' => "INSERT INTO r (SmsSid,FromCountry,FromCity,Phone,PhoneHash,FromZip,Body,Posted) VALUES (?,?,?,?,?,?,?,?)",
      'p' => array(
        $i['SmsSid'],
        $i['FromCountry'],
        $i['FromCity'],
        $i['From'],
        $i['PhoneHash'],
        $i['FromZip'],
        $i['Body'],
        time()
      )
    );

    TxtQuick_Utils::prepped($sql);
  }

}

/**
 * TxtQuick_Utils class.
 */
class TxtQuick_Utils {

  /**
  * simple message logger
  *
  * @access public
  * @static
  * @param array $var
  * @return void
  */
  static function log(array $var)
  {
    $msg = date(DATE_RFC822) .' '. var_export($var, true) ."\n";
    file_put_contents( APP_ROOT . '/storage/txt.log', $msg, FILE_APPEND);
  }

  /**
  * prepped function.
  *
  * @access public
  * @static
  * @param array $sql
  * @return void
  */
  static function prepped(array $sql)
  {
    $db = TxtQuick_Database::getInstance();
    $p  = $db->prepare($sql['q']);
    return $p->execute($sql['p']);
  }

  /**
  * query function.
  *
  * @access public
  * @static
  * @param mixed $sql
  * @return void
  */
  static function query($sql)
  {
    $db = TxtQuick_Database::getInstance();
    return $db->query($sql);
  }

  /**
  * get_all function.
  *
  * @access public
  * @static
  * @param mixed $sql
  * @return void
  */
  static function get_all($sql)
  {
    $db = TxtQuick_Database::getInstance();
    $res= $db->query($sql);
    if ($res)
      return $res->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
  * cleaner function.
  *
  * @access public
  * @static
  * @param mixed $text
  * @return void
  */
  static function cleaner($text)
  {
    $text = preg_replace("/<script[^>]*>.*?< *script[^>]*>/i", "", $text);
    $text = preg_replace("/<script[^>]*>/i", "", $text);
    $text = preg_replace("/<style[^>]*>.*<*style[^>]*>/i", "", $text);
    $text = self::strip_tags_attributes($text,'<p><a>');

    return trim($text);
  }

  /**
  * Sanitize for javascript attributes that will remain on allowed html tags
  *
  * thanks!: http://www.experts-exchange.com/Web_Development/Web_Languages-Standards/PHP/Q_24765149.html
  *
  * @access private
  * @param mixed $text
  * @param array $allowed
  * @return string $text
  */
  static function strip_tags_attributes($text, $allowed)
  {
    $disabled = array
    (
    'onabort','onactivate','onafterprint','onafterupdate','onbeforeactivate', 'onbeforecopy', 'onbeforecut',
    'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce',
    'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavaible', 'ondatasetchanged',
    'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragdrop', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover',
    'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterupdate', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp',
    'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave',
    'onmousemove', 'onmoveout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste',
    'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowexit', 'onrowsdelete',
    'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload'
    );

    return preg_replace('/<(.*?)>/ie',
    "'<' . preg_replace(array('/javascript:[^\"\']*/i', '/(" . implode('|', $disabled) . ")[ \\t\\n]*=[ \\t\\n]*[\"\'][^\"\']*[\"\']/i', '/\s+/'), array('', '', ' '), stripslashes('\\1')) . '>'",
    strip_tags($text, $allowed));
  }

}

/**
 * TxtQuick_Database class.
 * Database Singleton
 */
class TxtQuick_Database {
	private static $instance=NULL;
	private $dbh;

  /**
  * __construct function.
  *
  * @access private
  * @return void
  */
  private function __construct()
  {
    global $db;

    try
    {
      $this->dbh = new PDO('sqlite:'. APP_ROOT .'/storage/txt.db');
      $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch(PDOException $e)
    {
      echo $e->getMessage();
    }
  }

  /**
  * getInstance function.
  *
  * @access public
  * @static
  * @return void
  */
  public static function getInstance()
  {
    if (!self::$instance) {
      self::$instance = new TxtQuick_Database();
    }
    return self::$instance;
  }

	/**
	 * prepare function.
	 *
	 * @access public
	 * @param mixed $sql
	 * @return void
	 */
	public function prepare($sql)
	{
    try
    {
      return $this->dbh->prepare($sql);
    }
      catch(PDOException $e)
    {
      echo $e->getMessage();
    }
	}

  /**
  * query function.
  *
  * @access public
  * @param mixed $sql
  * @return void
  */
  public function query($sql)
  {
    try
    {
      return $this->dbh->query($sql);
    }
      catch(PDOException $e)
    {
      echo $e->getMessage();
    }
  }

}


