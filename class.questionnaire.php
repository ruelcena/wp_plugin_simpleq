<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Keep silence man...
if ( basename($_SERVER['SCRIPT_FILENAME']) === 'class.questionnaire.php' ) die();

if( !class_exists('WP_List_Table') ){                                 
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );    
}                                                               
                                                              


class SimpleQA extends WP_List_Table {

   protected $data = null;                
   protected $table = 'wp_accumed_qa';
   private $total_items = 0;
    
   function __construct() {
   global $wpdb;
   
   parent::__construct( array(
            'singular' => 'qa',
            'plural' => 'qa',
            'ajax' => false,
            'screen' => null ) );    
                
   
   //Get count of all questionnaires         
   $res = $wpdb->get_row( sprintf("select count(id) as totalqa from %s", $this->table) );   
   $this->total_items = $res->totalqa;
            
	}

    function column_default($item, $column_name){
        switch($column_name){        
            case 'id':
            case 'qorder':
            case 'question':
            case 'group':
            case 'group_child':
            case 'group_child_parent':
            case 'choices':      
            case 'qorder':
            return $item[$column_name];
        }
    }
    
   function column_cb( $item ) {    
    return sprintf( '<input type="checkbox" name="qa[]" data-show="%d" value="%s"/>', $item['enabled'], $item['id'] );
   }
   
   function column_group($item) {
      $q_group = get_option( 'accumed-qa-questionnaire-group' );
      if ($q_group) {
        $qg = (array)json_decode($q_group);
      }      
      return $qg[ $item['group'] ];
   }
   
   function column_group_child($item) {
      $q_group = get_option( 'accumed-qa-questionnaire-group-child' );
      if ($q_group) {
        $qg = (array)json_decode($q_group);
      }      
      
      $is_parent = ($item['group_child_parent']) ? '&nbsp;<span class="qa-group-child-parent">Parent</span>' : ''; 
      
      return (isset($qg[ $item['group_child'] ]))?$qg[ $item['group_child'] ] . $is_parent :'None';
   }
   
   function column_action( $item ) {
   
    $up = admin_url('admin.php?page=accumed-qa&oup=' . $item['id']);
    $down = admin_url('admin.php?page=accumed-qa&odown=' . $item['id']);
    $down_btn = sprintf('<a href="%s" title="Ordering Down"><span class="dashicons dashicons-arrow-down-alt2"></span></a>', esc_url($down));
    $up_btn = sprintf('<a href="%s" title="Ordering Up"><span class="dashicons dashicons-arrow-up-alt2"></span></a>',esc_url($up));
   
    $order = ($item['qorder'] == 1) ? $down_btn : $up_btn . $down_btn;    
    
    if ($item['qorder']==$this->total_items)
    $order = $up_btn;
       
    return sprintf( '<div class="action-menu"><a href="%s" title="Edit a question"><span class="dashicons dashicons-edit"></span></a>&nbsp;'. $order .'</div>',admin_url('admin.php?page=accumed-qa-form&id=' . $item['id']));
   }
    
    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'qorder' => 'Seq',                        
            'question'     =>  'Question',
            'group' => 'Group',
            'group_child' => 'Child',
            'choices'     =>  'Choices',            
            'action'     => 'Action'
        );
        return $columns;
    }    

    function get_sortable_columns() {
        $sortable_columns = array(            
            'group' => array('group', false),            
            'qorder' => array('qorder', false)                               
        );
        return $sortable_columns;
    }
    

    function get_bulk_actions() {
    return array(  'show' => 'Show', 'hide' => 'Hide', 'delete' => 'Delete' );    
    }    

    function get_data() {
    global $wpdb;    
    $where = ( isset( $_REQUEST['s'] ) ) ? 'where question like \'' . esc_html($_REQUEST['s']) . '%%\'' : '';    
    return $wpdb->get_results( sprintf("select * from %s %s", $this->table, $where), ARRAY_A );
    }
    
    function prepare_items() {

        $data = $this->get_data();        
    
        $per_page = 10;
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);

        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? strip_tags($_REQUEST['orderby']) : 'qorder'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? strip_tags($_REQUEST['order']) : 'asc'; //If no order, default to asc            
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }       
        
        usort($data, 'usort_reorder');
        
        $current_page = $this->get_pagenum();        
        
        $total_items = count($data);
        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        $this->items = $data;        
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
        
    }
    
}