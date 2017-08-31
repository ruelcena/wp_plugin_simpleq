<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class SimpleQ_Admin
{  

	public static function init() {
    add_action( 'admin_menu', ['SimpleQ_Admin','menu'] );
    add_action( 'admin_init', ['SimpleQ_Admin','admin_init'] );    
    add_action( 'admin_enqueue_scripts', ['SimpleQ_Admin','resources'] );        
	}
  
	public static function resources() {      

  wp_enqueue_style( 'simpleq-tagsinput.css', plugin_dir_url( __FILE__ ) . 'css/jquery.tagsinput.css', array(), SIMPLEQ_VERSION );
  wp_enqueue_style( 'simpleq-style.css', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), SIMPLEQ_VERSION );
  wp_enqueue_script( 'jquery-ui-autocomplete' );  
  wp_enqueue_script( 'simpleq-jquery.tagsinput.js', plugin_dir_url( __FILE__ ) . 'js/jquery.tagsinput.js', array('jquery'), false, SIMPLEQ_VERSION );
  wp_enqueue_script( 'simpleq-jsadmin', plugin_dir_url( __FILE__ ) . 'js/jsadmin.js', array('jquery'), false, SIMPLEQ_VERSION );  
  wp_localize_script( 'simpleq-jsadmin', 'simpleqApp', ['ajax_url' => admin_url( 'admin-ajax.php' )]);
   
	}
  
	public static function menu() {		
    global $submenu;    
        
    $role = 'manage_options';//default
    
    add_menu_page( _('SimpleQ'), _('SimpleQ'), $role, 'simpleq', ['SimpleQ_Admin','dashboard'] );
        
    //add_submenu_page( 'simpleq', __('Questionnaires'), __('Questionnaires'), 'manage_options', 'simpleq-qa', ['SimpleQ_Admin','dashboard'] );
    add_submenu_page( 'simpleq', __('Settings'), __('Settings'), 'manage_options', 'simpleq-settings', ['SimpleQ_Admin','dashboard'] );
    add_submenu_page( 'simpleq', __('Questionnare Form'), __('Questionnaire Form'), 'manage_options', 'simpleq-qa-form', ['SimpleQ_Admin','qa_form'] );
    
    $submenu['simpleq'][0][0] = 'Questionnaires';   

       
	}
    

	public static function admin_init() {
  
  global $wpdb;
  
  //Error form
  $error = array();
    
  //Setting
  if ( isset( $_POST['qa_settings'] ) && wp_verify_nonce( $_POST['qa_settings'], 'qa_settings' )) {
    if ( isset($_POST['display_set']) && $ds = sanitize_text_field(strip_tags($_POST['display_set'])) ) {   
      update_option( 'simpleq-qa-display', $ds );
    }
    if ( isset($_POST['redirect']) && $ds = sanitize_text_field(strip_tags($_POST['redirect'])) ) {   
      update_option( 'simpleq-qa-redirect', $ds );
    }
    if ( isset($_POST['questionnaire_group']) && $q_group = sanitize_text_field(strip_tags($_POST['questionnaire_group'])) ) {
            
      $qg = array( 'all' => 'All' );  
      $q_group = explode( ',', $q_group );
      foreach( $q_group as $vg ) {
        $qg[ preg_replace('/[^A-Za-z0-9\-]/','_',strtolower($vg)) ] = $vg;
      }      
      update_option( 'simpleq-qa-questionnaire-group', json_encode($qg) );
      
    }
    if ( isset($_POST['questionnaire_group_child']) && $q_group = sanitize_text_field(strip_tags($_POST['questionnaire_group_child'])) ) {   
      
      $qg = array( 'none' => 'None' );  
      $q_group = explode( ',', $q_group );
      foreach( $q_group as $vg ) {
        $qg[ preg_replace('/[^A-Za-z0-9\-]/','_',strtolower($vg)) ] = $vg;
      }      
      update_option( 'simpleq-qa-questionnaire-group-child', json_encode($qg) );
      
    }
    
    if ( isset($_POST['simpleq_test_email']) && $setting = sanitize_text_field(strip_tags($_POST['simpleq_test_email'])) ) {
      update_option( 'simpleq-test-email', $setting );    
    }
    
    if ( isset($_POST['simpleq_qpage']) && $setting = sanitize_text_field(strip_tags($_POST['simpleq_qpage'])) ) {
    update_option( 'simpleq-qpage', $setting );    
    }    
        
    $_SESSION['notice_update'] = 'Done updating.';
  } 

  
  //Q and A
  //Do form submission  
  if ( isset( $_POST['new_qa'] ) && wp_verify_nonce( $_POST['new_qa'], 'new_qa' )) {     
   
     if ( empty($_POST['group']) ) {
     $error['group'] = 'Enter a group.';
     }
       
     if ( empty($_POST['question']) ) {
     $error['question'] = 'Enter a question.';
     }

     if ( empty($_POST['choices']) ) {
     $error['choices'] = 'Enter a choices.';
     }
     
     if ( count($error) == 0 ) { 
     
            //Update member
       if ( isset($_POST['edit']) && intval($_POST['edit']) && $mid = sanitize_text_field(strip_tags($_POST['edit'])) ) {
              
       $wpdb->update( SimpleQ::$tbl_questionnaire, [
       'question' => sanitize_text_field(strip_tags($_POST['question'])),
       'choices' => ucwords(sanitize_text_field(strip_tags($_POST['choices']))),
       'add_info' => (isset($_POST['add_info']))?SimpleQ::clean_js($_POST['add_info']):'',       
       'group' => sanitize_text_field(strip_tags($_POST['group'])),
       'group_child' => sanitize_text_field(strip_tags($_POST['group_child'])),
       'group_child_parent' => (isset($_POST['group_child_parent']))?sanitize_text_field(strip_tags($_POST['group_child_parent'])):'',              
       'enabled' => (isset($_POST['enabled']))?sanitize_text_field(strip_tags($_POST['enabled'])):0,
       'input' => sanitize_text_field(strip_tags($_POST['input'])),
       'required' => sanitize_text_field(strip_tags($_POST['required']))       
       ], ['id' => $mid] );
       
       $_SESSION['notice_update'] = 'Done updating a questionnaire.';
                     
       } else {
                  
       $wpdb->insert( SimpleQ::$tbl_questionnaire, [
       'question' => sanitize_text_field(strip_tags($_POST['question'])),
       'choices' => ucwords(sanitize_text_field(strip_tags($_POST['choices']))),
       'add_info' => (isset($_POST['add_info']))?sanitize_text_field(strip_tags($_POST['add_info'])):'',       
       'group' => sanitize_text_field(strip_tags($_POST['group'])),
       'group_child' => sanitize_text_field(strip_tags($_POST['group_child'])),
       'group_child_parent' => (isset($_POST['group_child_parent']))?sanitize_text_field(strip_tags($_POST['group_child_parent'])):'',              
       'input' => sanitize_text_field(strip_tags($_POST['input'])),
       'required' => sanitize_text_field(strip_tags($_POST['required']))       
       ] );
       
       //Update order the default order
       $wpdb->update( SimpleQ::$tbl_questionnaire, ['qorder' => $wpdb->insert_id], ['id' => $wpdb->insert_id] );
       
       $_SESSION['notice_update'] = 'Done adding a new questionnaire.';           
       }
     
     
       //Last error to get
       if ($wpdb->last_error) {
       $error['last_error'] = $wpdb->last_error;       
       } else {
       wp_safe_redirect(admin_url('admin.php?page=simpleq-qa')); 
       }
     }  
     
     $_SESSION['error'] = $error;
  }//qa
    
   //Delete QA function
    if ( isset( $_POST['delete_qa'] ) && wp_verify_nonce( $_POST['delete_qa'], 'delete_qa' )) {
    
      //Show
      if ( $_POST['action'] === 'show' || $_POST['action'] === 'hide' ) {
        $enabled = ($_POST['action']=='hide')?0:1;              
        if ( count($_POST['qa']) ) {
          foreach( $_POST['qa'] as $m ) {            
            $wpdb->update( SimpleQ::$tbl_questionnaire, ['enabled' => $enabled], ['id' => sanitize_text_field($m)] );
          }
          $_SESSION['notice_update'] = 'Done updating.';                      
        }
      }
      //Delete        
      if ( isset($_POST['action']) && $_POST['action'] === 'delete' ) {    
        if ( count($_POST['qa']) ) {
          foreach( $_POST['qa'] as $m ) {
            $wpdb->delete( SimpleQ::$tbl_questionnaire, array('id' => sanitize_text_field($m)) );
          }
          $_SESSION['notice_update'] = 'Done deleting.';                      
        }
      }
    }
    
   //Reordering         
   if ( isset($_GET['oup']) && intval($_GET['oup']) && $goup = strip_tags($_GET['oup']) )
   {
     $dta = $wpdb->get_row( sprintf("select id, qorder from %s where id=%d", SimpleQ::$tbl_questionnaire, $goup) );     
     $curr_order = ($dta->qorder-1);
     $prev_order = $dta->qorder;
     $wpdb->update( SimpleQ::$tbl_questionnaire, ['qorder'=>$prev_order], ['qorder' => $curr_order] );
     $wpdb->update( SimpleQ::$tbl_questionnaire, ['qorder'=>$curr_order], ['id' => $goup] );
     wp_redirect( admin_url('admin.php?page=simpleq-qa') );
     exit();   
   }
   
   if ( isset($_GET['odown']) && intval($_GET['odown']) && $goup = strip_tags($_GET['odown']) )
   {
     $dta = $wpdb->get_row( sprintf("select id, qorder from %s where id=%d", SimpleQ::$tbl_questionnaire, $goup) );     
     $curr_order = ($dta->qorder+1);
     $prev_order = $dta->qorder;
     $wpdb->update( SimpleQ::$tbl_questionnaire, ['qorder'=>$prev_order], ['qorder' => $curr_order] );
     $wpdb->update( SimpleQ::$tbl_questionnaire, ['qorder'=>$curr_order], ['id' => $goup] );
     wp_redirect( admin_url('admin.php?page=simpleq-qa') );
     exit();   
   }           
		
	}
    

  public static function qa_form() {
  global $wpdb;
    
  $error = (isset($_SESSION['error']))?$_SESSION['error']:[];  
  unset($_SESSION['error']);
  
  //Get member
  if ( isset($_GET['id']) && intval($_GET['id']) && $mid = esc_html(strip_tags($_GET['id'])) ) {
      $mem = $wpdb->get_row( sprintf("select * from %s where id=%d", SimpleQ::$tbl_questionnaire, $mid) );
  }
  
  //main group
  $q_group = get_option( 'simpleq-qa-questionnaire-group' );
  if ($q_group) {
    $qg = (array)json_decode($q_group);
  }
  
  //child group
  $q_group_c = get_option( 'simpleq-qa-questionnaire-group-child' );
  if ($q_group_c) {
    $qg_c = (array)json_decode($q_group_c);
  }  
  
    
  ?>
  
  <div class="simpleq w-sm">
  
  <h1>Questionnaire</h1>
  <p class="error"><?php echo (isset($error['last_error']))?esc_html($error['last_error']):'';?></p>
  
  <form id="questionnaire" method="post" action=""> 
  <?php wp_nonce_field( 'new_qa', 'new_qa' ); ?>
  <?php if (isset($mem)) { ?>
  <input type="hidden" name="edit" value="<?php echo $mem->id;?>">  
  <?php }?>
  
  <table class="form-table"> 
  
  
  <tr>
		<th><label>Group</label><span class="required">*</span></th>
		<td>      
      <select name="group" class="form-control">
        <?php foreach( $qg as $k => $v ) { ?>        
        <option value="<?php echo esc_html($k);?>" <?php echo (isset($mem) && $mem->group == $k)?'selected':'';?>><?php echo esc_html($v);?></option>        
        <?php }?>
      </select>
      <p class="error"><?php echo (isset($error['group']))?esc_html($error['group']):'';?></p>
    </td>
	</tr>
  
  <tr>
		<th><label>Group Child</label></th>
		<td>      
      <select name="group_child" class="form-control">
        <?php foreach( $qg_c as $k => $v ) { ?>        
        <option value="<?php echo esc_html($k);?>" <?php echo (isset($mem) && $mem->group_child == $k)?'selected':'';?>><?php echo esc_html($v);?></option>        
        <?php }?>
      </select>
      <p></p>
      <div class="note"><b>Parent</b> <input type="checkbox" name="group_child_parent" value="1" <?php echo (isset($mem) && $mem->group_child_parent) ? 'checked':'';?>> | Optional, this is intended for conditional Yes/No or Switch selection</div>
      <p class="error"><?php echo (isset($error['group']))?esc_html($error['group']):'';?></p><br />
    </td>
	</tr>   
  
  <tr>
		<th><label>Question</label><span class="required">*</span></th>
		<td>
    <textarea name="question" class="form-control" cols="2" rows="20" style="height:80px;"><?php echo (isset($mem))?$mem->question:'';?></textarea>
    <p class="error"><?php echo (isset($error['question']))?esc_html($error['question']):'';?></p><br />
    </td>
	</tr>
  
  <tr>
		<th><label>Additional Info</label></th>
		<td>
    <textarea name="add_info" class="form-control" cols="2" rows="20" style="height:80px;"><?php echo (isset($mem))?$mem->add_info:'';?></textarea>
    <p class="error"><?php echo (isset($error['add_info']))?esc_html($error['add_info']):'';?></p><br />
    </td>
	</tr>  
  
  <tr>
		<th><label>Choices</label><span class="required">*</span></th>
		<td>      
      <textarea name="choices" id="simpleq_set_choices" class="form-control"><?php echo (isset($mem))?$mem->choices:'';?></textarea>       
      <p class="error"><?php echo (isset($error['choices']))?esc_html($error['choices']):'';?></p>     
    </td>
	</tr>
  
  <tr>
		<th><label>Input Choices</label></th>
		<td>      
      <select name="input" class="form-control">
        <option value="radio" <?php echo (isset($mem) && $mem->input == 'radio')?'selected':'';?>>Radio Button</option>
        <option value="checkbox" <?php echo (isset($mem) && $mem->input == 'checkbox')?'selected':'';?>>CheckBox</option>
        <option value="select" <?php echo (isset($mem) && $mem->input == 'select')?'selected':'';?>>Select / Drop down</option>
        <option value="textarea" <?php echo (isset($mem) && $mem->input == 'text')?'selected':'';?>>Text</option>                
        <option value="radio_option" <?php echo (isset($mem) && $mem->input == 'radio_option')?'selected':'';?>>Radio Button with input text option</option>
        <option value="checkbox_option" <?php echo (isset($mem) && $mem->input == 'checkbox_option')?'selected':'';?>>CheckBox with input text option</option>                

      </select>
    </td>
	</tr>
  
  <tr>
		<th>&nbsp;</th>
		<td>
      <input type="radio" name="required" value="1" <?php echo (isset($mem) && $mem->required)?'checked':'';?>>&nbsp;Required <span class="required">*</span>&nbsp;
      <input type="radio" name="required" value="0" <?php echo (isset($mem) && !$mem->required)?'checked':'';?>>&nbsp;Not required
    </td>
	</tr>  
  
  <?php if ( isset($_GET['id']) ) {?>  
  <tr>
		<th>&nbsp;</th>
		<td>    
      <input type="radio" name="enabled" value="1" <?php echo (isset($mem) && $mem->enabled)?'checked':'';?>>&nbsp;Show&nbsp;
      <input type="radio" name="enabled" value="0" <?php echo (isset($mem) && !$mem->enabled)?'checked':'';?>>&nbsp;Hide
    </td>
	</tr>
  <?php }?>  
  
  <tr>
		<th>&nbsp;</th>
		<td><button class="button button-primary" type="submit">Save</button>&nbsp;<a href="<?php echo admin_url('admin.php?page=simpleq-qa');?>" class="button button-secondary">Cancel</a></td>
	</tr>
  
  <tr>
    <th>&nbsp;</th>
    <td>
      <!--Preview-->  
  <div class="qpreview">
    <h2 class="prev-title">Preview</h2>    
    <h2 class="prev-question"></h2>
    <div class="prev-add-info"></div>
    <div class="prev-choices"></div>  
  </div>
    
    </td>  
  </tr>  
  
  </table>
  
  </form> 

  
  </div>
  
  <?php
  }  
  
  public static function dashboard() {
  
  //Current page
  $is_page = ( isset($_GET['page']) ) ? strip_tags($_GET['page']) : '';
  
  //Notice
  $notice_update = (isset($_SESSION['notice_update']))?$_SESSION['notice_update']:'';
  unset($_SESSION['notice_update']);
  
  //Option settting
  $display = get_option( 'simpleq-qa-display' );
  $redirect = get_option( 'simpleq-qa-redirect' );
  $test_email = get_option( 'simpleq-test-email' );
  $qpage = get_option( 'simpleq-qpage' );  
  
  $questionnaire_group = '';
  $q_group = get_option( 'simpleq-qa-questionnaire-group' );
  if ($q_group) {
    $q_group = (array)json_decode($q_group);
    $questionnaire_group = implode(',', $q_group);    
  }
  
  $questionnaire_group_child = '';
  $q_group_c = get_option( 'simpleq-qa-questionnaire-group-child' );
  if ($q_group_c) {
    $q_group_c = (array)json_decode($q_group_c);
    $questionnaire_group_child = implode(',', $q_group_c);    
  }
  
  $list = new SimpleQA();
	$list->prepare_items();
  
  ?>  
  
  <div class="simpleq wrap">
  
    <?php if (!empty($notice_update)) {?>
    <div class="notice updated"><?php echo $notice_update;?></div>
    <?php }?>
  
    <h1>Simple Questionnaire</h1>
    
    <h2 class="nav-tab-wrapper wp-clearfix">
      <a href="<?php echo admin_url('admin.php?page=simpleq');?>" class="nav-tab <?php echo ($is_page=='simpleq')?'nav-tab-active':''?>">Questionnaires</a>      
      <a href="<?php echo admin_url('admin.php?page=simpleq-settings');?>" class="nav-tab <?php echo ($is_page=='simpleq-settings')?'nav-tab-active':''?>">Settings</a>                              
		</h2>
    
    <!--members-->
    <div class="tab-content">
    
    <?php if ( $is_page === 'simpleq' ) { ?>
    
    <form id="questionnaire" action="" method="post">
    <?php wp_nonce_field( 'delete_qa', 'delete_qa' ); ?>    
    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>    

        
    <?php
    $list->search_box( 'Search', 'simpleq_qa' );
    $list->display();  
    ?>
    
    </form>
    
    <p><a href="<?php echo admin_url('admin.php?page=simpleq-qa-form');?>" class="button button-primary">Add New Question</a></p>
    
    <?php } else if ( $is_page === 'simpleq-settings' )  {?>
    
    <div class="w-sm">
    
    <form action="" method="post">
    <?php wp_nonce_field( 'qa_settings', 'qa_settings' ); ?>
    
    <table class="form-table">
    
    <tr>
  		<th><label>Questionnaire Display</label></th>
  		<td>
      <select name="display_set" class="form-control">
           <option value="display_all" <?php echo ($display=='display_all')?'selected':'';?>>Display All</option>
           <option value="individual" <?php echo ($display=='individual')?'selected':'';?>>Individual</option>
      </select>    
      </td>
  	</tr>
    
  <tr>
		<th><label>Group Questionnare</label></th>
		<td>      
      <textarea name="questionnaire_group" id="simpleq_questionnaire_group" class="form-control"><?php echo esc_html($questionnaire_group);?></textarea>
      <div class="note">Organizing a group of question.</div>
      <br />
    </td>
	</tr>
  
  <tr>
		<th><label>Group Child Questionnare</label></th>
		<td>      
      <textarea name="questionnaire_group_child" id="simpleq_questionnaire_group_child" class="form-control"><?php echo esc_html($questionnaire_group_child);?></textarea>
      <div class="note">This is intended for conditional switching like Yes / No.</div>
      <br />
    </td>
	</tr>
  
    <tr>
  		<th><label>Test Email</label></th>
  		<td>
      <input type="text" name="simpleq_test_email" class="form-control" value="<?php echo esc_html($test_email);?>"/>      
      <div class="note">A recipient email address for testing only.</div><br />          
      </td>
  	</tr>
    
    <tr>
  		<th><label>Questionnaire Page</label></th>
  		<td>
      <input type="text" name="simpleq_qpage" class="form-control" value="<?php echo esc_html($qpage);?>"/>
      <div class="note">Set here for your current questionnaire page where based in template.</div>      
      </td>
  	</tr>

    <tr>
  		<th><label>Redirect Page</label></th>
  		<td>
      <input type="text" name="redirect" class="form-control"/>      
      <div class="note">This will redirect if all questions done.</div>
      <div class="note">If this empty, it will redirect the page itself.</div>    
      </td>
  	</tr>    
    
    <tr>
    	<th>&nbsp;</th>
    	<td><button class="button button-primary" type="submit">Save</button></td>
    </tr>
    
    </table>
    
    </form>    
    
    </div>
    
    <?php }?>
    
    </div>
  
  </div>
  
  <?php  
    
  }//dashboard  
  

}