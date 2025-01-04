<?php
/*
PubDateFix - plugin for GetSimple CMS (3.1+)
Makes GS page date field fixed (and editable), plus new replacement lastUpdate field 

Helper functions / template tags:
 - get_page_lastupdate([dateformat])
 - return_page_lastupdate([dateformat])
(they work exactly like GetSimple's get_page_date() and return_page_date()
when this plugin is not installed)

A new editable Publication Date field is displayed in page options.
If the date/time entered is invalid, is will be saved as the current datetime ("now").

Default date[time] editing format is 'Y-m-d H:i' (for e.g. 2015-12-31 23:59)
You can define your custom format in your site's gsconfig.php file.
Some examples:

 define('PUBDATEFORMAT','Y/m/d H.i'); // ==> 2015/12/31 23.59
 define('PUBDATEFORMAT','Y-m-d');     // ==> 2015-12-31
 ...

To disable the datepicker, insert this in gsconfig.php:

 define('PUBDATEPICKER', false);

(jQuery DateTimePicker by XDSoft <http://xdsoft.net/jqplugins/datetimepicker/>)

*/

define('PUBDATEFIXVERSION', '0.3');

// register plugin
$thisfile = basename(__FILE__, ".php");
register_plugin(
  $thisfile,
  'pubDateFix',
  PUBDATEFIXVERSION,
  'Carlos Navarro',
  'http://www.cyberiada.org/cnb/',
  'Makes pubDate field fixed and editable (with date/time picker), adds lastUpdate field'
);

add_action('changedata-save', 'pubdatefix_save');
if (basename($_SERVER['PHP_SELF']) == 'edit.php') {
  i18n_merge($thisfile, $LANG) || (strlen($LANG) > 2 && i18n_merge($thisfile, substr($LANG,0,2))) || i18n_merge($thisfile, 'en');
  add_action('edit-extras','pubdatefix_edit'); 
  if ((!defined('PUBDATEPICKER') || PUBDATEPICKER) && function_exists('register_script')) { // GS 3.1+
    register_script('jquery-datetimepicker', $SITEURL.'plugins/pubdatefix/js/jquery.datetimepicker.js', '2.3.2', false);
    queue_script('jquery-datetimepicker', GSBACK);
    register_style('jquery-datetimepicker', $SITEURL.'plugins/pubdatefix/css/jquery.datetimepicker.css', PUBDATEFIXVERSION, 'screen');
    queue_style('jquery-datetimepicker', GSBACK);
    add_action('footer', 'pubdatefix_footer');
  }
}

function get_page_lastupdate($i = "l, F jS, Y - g:i A") {
  echo return_page_lastupdate($i);
}

function return_page_lastupdate($i = "l, F jS, Y - g:i A") {
  global $data_index;
  global $TIMEZONE;
  if ($TIMEZONE != '') {
    if (function_exists('date_default_timezone_set')) {
      date_default_timezone_set($TIMEZONE);
    }
  }
  if (isset($data_index->lastUpdate)) {
    return date($i, strtotime($data_index->lastUpdate));
  } else {
    return date($i, strtotime($data_index->pubDate));
  }
}

function pubdatefix_save() {
  global $xml;
  if (isset($_POST['pubdatefix']) && strval(strtotime($_POST['pubdatefix']))!='') {
    unset($xml->pubDate);
    $xml->addChild('pubDate', date('r',strtotime($_POST['pubdatefix'])));
  }
  $xml->addChild('lastUpdate', date('r')); // now
}

function pubdatefix_edit() {
  global $data_edit;
  $format = pubdatefix_format();
  echo '<div class="leftopt"><p>';
  echo '<label for="pubdatefix">',i18n_r('pubdatefix/PUBDATE'),':</label>';
  echo '<input class="text short" id="pubdatefix" name="pubdatefix" type="text" value="';
  if (isset($data_edit->pubDate)) { echo date($format, strtotime($data_edit->pubDate)); }
  echo '" placeholder="',date($format, strtotime(date('r'))),'" />';
  echo '</p></div>';
  echo '<div class="clear"></div>';

  // backend last saved:
  global $pubDate;
  if (isset($data_edit->lastUpdate)) {
    $pubDate = $data_edit->lastUpdate;
  } else {
    if (isset($data_edit->pubDate)) {
      $pubDate = $data_edit->pubDate;
    }
  }
}

function pubdatefix_format() {
  return defined('PUBDATEFORMAT') ? PUBDATEFORMAT : 'Y-m-d H:i';
}

function pubdatefix_footer() {
  global $LANG;
  $lg = substr($LANG, 0, 2);
  if (in_array($lg, array('fi','sk', 'bg','ch','cs','da','de','el','es','fr','hu','it','nl','no','pl','pt','ru','se','sl','tr','vi')))
    $start = 1; // week starts on Monday
  else
    $start = 0; // Sunday
  if (!in_array($lg, array('bg','ch','cs','da','de','el','en','es','fa','fr','hu','it','ja','kr','nl','no','pl','pt','ru','se','sl','th','tr','vi')))
    $lg = 'en'; // fallback to English if not supported by datetimepicker
  $format = pubdatefix_format();
?>
<script type="text/javascript">
  jQuery('#pubdatefix').datetimepicker({
    format:'<?php echo $format; ?>',
    lang:'<?php echo $lg; ?>',
    dayOfWeekStart: <?php echo $start; ?>
  });
</script>
<?php
}
