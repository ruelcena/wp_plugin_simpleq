<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class SimpleQ {

  public static $tbl_questionnaire;
  public static $tbl_post;
	
	public static function init() {
  global $wpdb;
  
  self::$tbl_questionnaire = $wpdb->prefix . 'simpleq_qa';
  self::$tbl_post = $wpdb->prefix . 'simpleq_post';    
    
  wp_enqueue_style( 'qastyle.css', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), SIMPLEQ_VERSION );    
  wp_enqueue_script( 'gcaptcha', 'https://www.google.com/recaptcha/api.js', [], false, SIMPLEQ_VERSION );
  wp_enqueue_script( 'jqvalidator.js', plugin_dir_url( __FILE__ ) . 'js/jquery.validator.js', array('jquery'), false, SIMPLEQ_VERSION );
  wp_enqueue_script( 'frontjs.js', plugin_dir_url( __FILE__ ) . 'js/frontjs.js', array('jquery'), false, SIMPLEQ_VERSION );      
  
	}
  
  public static function post() {
  global $wpdb;
  
  //Registration form
  if ( isset( $_POST['accumed_qa_regform'] ) && wp_verify_nonce( $_POST['accumed_qa_regform'], 'accumed_qa_regform' )) {  
  if ( isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) && $email = sanitize_text_field(strip_tags($_POST['email'])) ) {
       
  
  }
  exit();
  }
  
  //Questionnaire post  
  if ( isset( $_POST['accumed_qa_form'] ) && wp_verify_nonce( $_POST['accumed_qa_form'], 'accumed_qa_form' ))
  {
    if ( isset($_POST['accumed_qa_choices']) && is_array($_POST['accumed_qa_choices']) && $qa = $_POST['accumed_qa_choices'] )
    {            
           
      //Reformat a field      
      $format = [      
      'company' => 'Company Name',
      'agreement' => 'Terms of Service-I agree to the terms of service.',
      'name' => 'Name',
      'sex' => 'Sex',
      'email' => 'Email Address',
      'age' => 'Age',
      'height' => 'Height (estimate) in cms or ft/in',
      'weight' => 'Weight (estimate) in kgs or lbs'
      ];    
      
      $data = json_decode(str_replace("\\","",$qa['final']));      
      
      $form = [];
      $name = '';
      
      foreach( $data as $qv ) {
      
        if ( is_array($qv) ) {
                  
          foreach( $qv as $v ) {
          
            $qname = $v->name;
                      
            //Get question            
            if ( substr_count($v->name, 'qa') ) {
            
              if ($qres = self::get_questionnaire( str_replace('qa','',$v->name) ))
              $qname = $qres->question;
              
            }
            
            if ( $v->name == 'signature' ) {
                        
            $form['declared'] = '';
            
            $qname = 'Electronic Signature';
            $v->value = sprintf('<img src="%s"/>', $v->value);
            }            
            
            //agree
            if ( $v->name == 'agreement' )
            $v->value = 'Yes';
            
            if ( isset( $format[$qname] ) )
            $qname = $format[$qname];
            
           
            $form[ $qname ] = $v->value;
            
            //Concatenate a name 
            if ( $v->name == 'first_name' )
            $name[] = $v->value;
            
            if ( $v->name == 'last_name' ) {            
            $name[] = $v->value;
            
            $form['Name'] = implode(' ', $name);            
            unset($form['first_name'], $form['last_name']);            
            }
                      
          }
          
        }
      
      }
      
      $form['Assessment Date'] = date('m/d/Y');      
      //Clean
      unset($form['accumed_qa_form'], $form['_wp_http_referer'], $form['accumed_qa_choices[final]'], $form['g-recaptcha-response']);     
      self::assessment($form);
      
      //Popup trigger
      $_SESSION['assessmentThank'] = date('Y-m-d h:i:s'); 
      
      //$wpdb->insert( self::$post, ['uid' => get_current_user_id(), 'questions' => json_encode($form), 'track' => date('Y-m-d h:i:s') ] );
      
      //wp_clear_auth_cookie();
      //wp_redirect( site_url() );
      exit();      
      
    }
  }
  }
  

	/**
	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
	 * prefix table name default wp
	 * @static
	 */
	public static function plugin_activation() {
  global $wpdb;
  
  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  
  $sql = "
  CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}simpleq_post` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`uid` INT(11) NULL DEFAULT NULL,
	`questions` TEXT NULL,
	`track` DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `uid` (`uid`),
	INDEX `track` (`track`)
  );  
  ";  
  dbDelta($sql);
  
  $sql = "
  CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}simpleq_qa` (
  	`id` INT(11) NOT NULL AUTO_INCREMENT,
  	`group` VARCHAR(100) NULL DEFAULT 'all',
  	`group_child` VARCHAR(100) NULL DEFAULT NULL,
  	`group_child_parent` TINYINT(1) NULL DEFAULT '0',
  	`question` TINYTEXT NULL,
  	`add_info` TINYTEXT NULL,
  	`choices` TINYTEXT NULL,
  	`input` VARCHAR(100) NULL DEFAULT NULL,
  	`enabled` TINYINT(1) NULL DEFAULT '1',
  	`required` TINYINT(1) NULL DEFAULT '1',
  	`qorder` MEDIUMINT(10) NULL DEFAULT NULL,
  	PRIMARY KEY (`id`),
  	INDEX `enabled` (`enabled`),
  	INDEX `qorder` (`qorder`)
  );  
  ";  
  dbDelta($sql);
  
  //set default option  
  update_option( 'accumed-qa-display','display_all' );  
  update_option( 'accumed-test-email', 'php.rcena@gmail.com' );  
  
	}

	/**
	 * Removes all connection options
	 * @static
	 */
	public static function plugin_deactivation( ) {
  
  //global $wpdb;  
  //$tables = ['wp_accumed_companys', 'wp_accumed_members', 'wp_accumed_post', 'wp_accumed_qa'];  
  //foreach( $tables as $t ) {
  //$wpdb->query( "DROP TABLE IF EXISTS " . $t );
  //}  

	}  

  
  public static function questionnaires( $group, $display='display_all' ) {
  
      if (!isset($group)) return false;
   
      // start output
      $o = '';
      $ch = '';
      $in = '';
      
      if ( $questions = self::get_questionnaires( $group ) ) {
      
      $r=1;
      $opt='';
      $gc = false;
      $qchild = '';
      $qids = [];
        
        foreach( $questions as $qa )
        {
        
          if ( $qa->group_child !== 'none' ) {
            $qids=[];                                                    
            $qchild = self::get_child_questionnaire( $qa->group_child );            
            foreach( $qchild as $qcc ) {
               $qids[] = $qcc->id;           
            }            
          }  
                       
          if ( $qa->choices ) {
          
          $ifds = ( $display == 'individual' && $r > 0 ) ? 'style="display:none;"' : '';
          $req_class = ( $qa->required ) ? '<span class="accumed-qa-required">*</span>' : '';
          $req_value = ( $qa->required ) ? 'required' : '';
          $req_blk = ( $qa->required ) ? 'qa-b-required' : '';          
          
          //Wrapper
          $o .= sprintf( '<div class="qa%d %s" data-b-id="%d" %s>', $qa->id, $req_blk, $qa->id, $ifds );
          
          //Title
          if ( !in_array( $qa->id, $qids )  ) {
          $o .= sprintf( '<h3>%d. %s %s</h3>', $r, esc_html($qa->question), ( ($qa->required)?'<span class="qa-required">*</span>':'' ) );
          
          if ( isset($qa->add_info) )
          $o .= sprintf( '<p>%s</p>', nl2br($qa->add_info) );
          
          $r++;          
          }          
          
          //switch function
          $sw_qa = ( $qa->group_child !== 'none' ) ? 'class="accumed_qa_option"' : '';
          
          $ch = explode( ',', $qa->choices );               
          $o .= '<ul>';
          
          if ( !in_array( $qa->id, $qids )  ) {
          
          //checkbox and radio only
          if ( $qa->input == 'checkbox' || $qa->input == 'radio' ) {             
          foreach( $ch as $sel ) {
            $o .= sprintf( '<li><input type="%s" name="qa%d" %s data-id="%d" value="%s" %s/>&nbsp;%s</li>', esc_html($qa->input), $qa->id, $sw_qa, $qa->id, $sel, esc_html($req_value), $sel );
          }          
          }
          //check and radio with input text option
          if ( $qa->input == 'checkbox_option' || $qa->input == 'radio_option' ) {             
          $opt = ( $qa->input == 'checkbox_option' ) ? 'checkbox' : 'radio';
           
          foreach( $ch as $sel ) {
            $o .= sprintf( '<li><input type="%s" name="qa%d" data-id="%d" value="%s" %s/>&nbsp;%s</li>', esc_html($opt), $qa->id, $qa->id, $sel, esc_html($req_value), $sel );
          }
          
          }
          
          //select / dropdown
          if ( $qa->input == 'select' ) {
           $o .= sprintf( '<li><select name="qa%d" data-id="%d" %s>', esc_html($qa->id), $qa->id, esc_html($req_value) );
             foreach( $ch as $sel ) {                               
             
             $o .= sprintf( '<option value="%s">%s</option>', $sel, $sel );
                                             
             }
           $o .= sprintf('</select></li>');
          }             
          
          //textarea
          if ( $qa->input == 'textarea' )
          $o .= sprintf( '<li><textarea name="qa%d" data-id="%d" cols="80" rows="4" %s></textarea></li>', $qa->id, $qa->id, esc_html($req_value) );
          
          //If has option textbox
          if ( $opt ) {              
            $o .= sprintf( '<li><input type="text" name="qa%d" placeholder="Other (please specify)"/></li>', $qa->id );
            $opt = '';
          }           
          
          }   
          
          //main
          
          
          //if first child captured
          if ( $qchild ) {
          
          //$gc = true;          
          $qchild = self::get_child_questionnaire( $qa->group_child );
          
          //Main
          //$o .= sprintf( '<li class="qa_c%d qa_child">', $qa->id );
          
          $rc = 0;
          $optc = '';
          
          foreach( $qchild as $qac ) {
          
          $req_value_c = ( $qac->required ) ? 'required' : '';
          
          $add_info = ( isset($qac->add_info) ) ? sprintf( '<p>%s</p>', nl2br($qac->add_info) ) : 'asdfasdadsf';          
          
          $o .= sprintf( '<li class="qa_c%d qa_child"><h3>%s</h3>%s</li>', $qa->id, esc_html($qac->question), $add_info );
          
          $chc = explode( ',', $qac->choices );
                    
          //checkbox and radio only
          if ( $qac->input == 'checkbox' || $qac->input == 'radio' ) {             
          foreach( $chc as $sel ) {
            $o .= sprintf( '<li class="qa_c%d qa_child"><input type="%s" name="qa%d" data-id="%d" value="%s" %s/>&nbsp;%s</li>', $qa->id, esc_html($qac->input), $qac->id, $qac->id, $sel, esc_html($req_value_c), $sel );
          }          
          }
          //check and radio with input text option
          if ( $qac->input == 'checkbox_option' || $qac->input == 'radio_option' ) {             
          $optc = ( $qac->input == 'checkbox_option' ) ? 'checkbox' : 'radio';
           
          foreach( $chc as $sel ) {
            $o .= sprintf( '<li class="qa_c%d qa_child"><input type="%s" name="qa%d" data-id="%d" value="%s" %s/>&nbsp;%s</li>', $qa->id, esc_html($optc), $qac->id, $qac->id, $sel, esc_html($req_value_c), $sel );
          }
          
          }
          
          //select / dropdown
          if ( $qac->input == 'select' ) {
           $o .= sprintf( '<li class="qa_c%d qa_child"><select name="qa%d" data-id="%d" %s>', $qa->id, esc_html($qac->id), esc_html($qac->id),  esc_html($req_value_c) );
             foreach( $chc as $sel ) {                               
             
             $o .= sprintf( '<option value="%s">%s</option>', $sel, $sel );
                                             
             }
           $o .= sprintf('</select></li>');
          }             
          
          //textarea
          if ( $qac->input == 'textarea' )
          $o .= sprintf( '<li class="qa_c%d qa_child"><textarea name="qa%d" data-id="%d" cols="80" rows="4" %s></textarea></li>', $qa->id, $qac->id, esc_html($qac->id), esc_html($req_value_c) );
          
          //If has option textbox
          if ( $optc ) {              
            $o .= sprintf( '<li class="qa_c%d qa_child"><input type="text" name="qa%d" placeholder="Other (please specify)"/></li>', $qa->id, $qac->id );
            $optc = '';
          }          
          
          }//Child questionnaire          
          }//Child display switching
          
          $o .= '</ul>';
          
          }//choices

                              
          $o .= '</div>';//wrapper
        
        }
               
      }//if has
      
      return $o;  
  }
  
  private static function get_questionnaires($group) {
  global $wpdb;  
  if (!isset($group)) return false;
  
  return $wpdb->get_results( sprintf( "select id,question,choices,input,`group`,group_child,required,add_info from %s where enabled=1 and `group`='%s' order by qorder", self::$tbl_questionnaire, $group ) );  
  }
  
  private static function get_questionnaire($id) {
  global $wpdb;  
  if ( !isset($id) ) return false;
  return $wpdb->get_row( sprintf('select id,question,choices,input,`group`,group_child,required,add_info from %s where id=%d order by qorder', self::$tbl_questionnaire, $id ) );  
  }
  
  private static function get_child_questionnaire($child) {
  global $wpdb;
  return $wpdb->get_results( sprintf("select id,question,choices,input,`group`,group_child,required,add_info from %s where group_child='%s' and group_child_parent=0 order by qorder", self::$tbl_questionnaire, $child ) );  
  }
  
  public static function clean_js($text) {
  return preg_replace('#<script(.*?)>(.*?)</script>#is', '', $text);
  }  
  
}//class
