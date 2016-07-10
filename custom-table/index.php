<?php
/*
Plugin Name: Custom table example
Description: example plugin to demonstrate wordpress capatabilities
Plugin URI: http://mac-blog.org.ua/
Author URI: http://mac-blog.org.ua/
Author: Marchenko Alexandr
License: Public Domain
Version: 1.1
How to use it:
    1. Change plugin folder and name
Replace:
    2. $arr_table => $arr_table_[name]
    3. Custom_Table_Example_List_Table to Custom_Table_Example_List_Table_[name]
    4. ta_cls_install => ta_cls_install_[name]
    5. ta_cls_admin => ta_cls_admin_[name]
    6. Change table information and schema in $arr_table
Frontend: Ajax calls to save in class cls_frontend
    1. Change ajax function name 'save_custom_table' in class cls_frontend and ajax function is in custom-table.js
*/
date_default_timezone_set('Asia/Ho_Chi_Minh');
global $arr_table;
$arr_table = array(
    'tbl_name'=>'persons',
    'singular' => 'Person',
    'plural' => 'Persons',
    'tbl_columns' => array(
                            'name'=>array(
                                'name'=>'Name', 
                                'type'=>'tinytext NOT NULL', 
                                'display_in_table'=>1, 
                                'sort'=>1,
                                'required'=>1
                                ),
                            'email'=>array(
                                'name'=>'Email', 
                                'type'=>'VARCHAR(100) NOT NULL', 
                                'display_in_table'=>1, 
                                'sort'=>1,
                                'required'=>1
                                ),
                            'age'=>array(
                                'name'=>'Age', 
                                'type'=>'int(11) NULL', 
                                'display_in_table'=>0, 
                                'sort'=>0,
                                'required'=>0
                                ),
                            'date_created'=>array(
                                'name'=>'Date created', 
                                'type'=>"datetime NULL DEFAULT '0000-00-00 00:00:00'", 
                                'display_in_table'=>0, 
                                'sort'=>0,
                                'required'=>0,
                                ),
                            'date_modified'=>array(
                                'name'=>'Date modified', 
                                'type'=>"datetime NULL DEFAULT '0000-00-00 00:00:00'", 
                                'display_in_table'=>0, 
                                'sort'=>0,
                                'required'=>0,
                                ),
        )
);
class ta_cls_install{

    public $custom_table_example_db_version = '1.1'; // version changed from 1.0 to 1.1

    /**
     * register_activation_hook implementation
     *
     * will be called when user activates plugin first time
     * must create needed database tables
     */
    
    function __construct() {
        add_action('activated_plugin', array($this, 'my_save_error'));
        //echo "register_activation_hook S";
        register_activation_hook(__FILE__, array($this, 'custom_table_example_install'));
        //echo "register_activation_hook E";
        register_activation_hook(__FILE__, array($this, 'custom_table_example_install_data'));

        //$this->custom_table_example_update_db_check();

        add_action('plugins_loaded', array($this, 'custom_table_example_update_db_check'));
    }

    function my_save_error()
    {
        file_put_contents(dirname(__file__).'/error_activation.txt', ob_get_contents());
    }

    function custom_table_example_install()
    {
        global $wpdb, $arr_table;
        $table_name = $wpdb->prefix . $arr_table['tbl_name']; // do not forget about tables prefix

        // sql to create your table
        // NOTICE that:
        // 1. each field MUST be in separate line
        // 2. There must be two spaces between PRIMARY KEY and its name
        //    Like this: PRIMARY KEY[space][space](id)
        // otherwise dbDelta will not work
        
        $sql = "CREATE TABLE " . $table_name . " (
          id int(11) NOT NULL AUTO_INCREMENT";

        $arr_columns = $arr_table['tbl_columns'];
        foreach ($arr_columns as $key => $value) {
            $sql .= ", " . $key . " " . $value['type'];
        }
        $sql .= ", PRIMARY KEY(id) );";
        //echo $sql ; die;
        // we do not execute sql directly
        // we are calling dbDelta which cant migrate database
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // save current database version for later use (on upgrade)
        add_option('custom_table_example_db_version', $this->custom_table_example_db_version);

        /**
         * [OPTIONAL] Example of updating to 1.1 version
         *
         * If you develop new version of plugin
         * just increment $custom_table_example_db_version variable
         * and add following block of code
         *
         * must be repeated for each new version
         * in version 1.1 we change email field
         * to contain 200 chars rather 100 in version 1.0
         * and again we are not executing sql
         * we are using dbDelta to migrate table changes
         */
        $installed_ver = get_option('custom_table_example_db_version');
        if ($installed_ver != $this->custom_table_example_db_version) {
            //User sql above

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            // notice that we are updating option, rather than adding it
            update_option('custom_table_example_db_version', $this->custom_table_example_db_version);
        }
    }

    /**
     * register_activation_hook implementation
     *
     * [OPTIONAL]
     * additional implementation of register_activation_hook
     * to insert some dummy data
     */
    function custom_table_example_install_data()
    {
        global $wpdb, $arr_table;

        $table_name = $wpdb->prefix . $arr_table['tbl_name']; // do not forget about tables prefix

        // $wpdb->insert($table_name, array(
        //     'name' => 'Alex',
        //     'email' => 'alex@example.com',
        //     'age' => 25
        // ));

        $arr_columns = $arr_table['tbl_columns'];
        $arr_data = array();
        foreach ($arr_columns as $key => $value) {
            if(strpos(strtolower($value['type']), "int") !== FALSE)
                $arr_data[$key] = 1;
            elseif(in_array(strtolower($key), array("date_created", "date_modified")))
                $arr_data[$key] = date("Y-m-d H:i:s");
            elseif(strpos(strtolower($value['type']), "datetime") !== FALSE)
                $arr_data[$key] = date("Y-m-d H:i:s");
            elseif(strpos(strtolower($value['type']), "date") !== FALSE)
                $arr_data[$key] = date("Y-m-d");
            elseif(strtolower($key) == "email")
                $arr_data[$key] = "ninhtheanh@gmail.com";
            else
                $arr_data[$key] = $value['name'];
        }
        for($i = 0; $i < 10; $i++){
            $wpdb->insert($table_name, $arr_data);
        }
    }



    /**
     * Trick to update plugin database, see docs
     */
    function custom_table_example_update_db_check()
    {
        if (get_site_option('custom_table_example_db_version') != $this->custom_table_example_db_version) {
            $this->custom_table_example_install();
        }
    }
    
}//end ta_cls_install class
$ta_cls_install = new ta_cls_install();

/**
 * PART 2. Defining Custom Table List
 * ============================================================================
 *
 * In this part you are going to define custom table list class,
 * that will display your database records in nice looking table
 *
 * http://codex.wordpress.org/Class_Reference/WP_List_Table
 * http://wordpress.org/extend/plugins/custom-list-table-example/
 */

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Custom_Table_Example_List_Table class that will display our custom table
 * records in nice table
 */
class Custom_Table_Example_List_Table extends WP_List_Table
{
    /**
     * [REQUIRED] You must declare constructor and give some basic params
     */
    function __construct()
    {
        global $status, $page, $arr_table;

        parent::__construct(array(
            'singular' => $arr_table['singular'],
            'plural' => $arr_table['plural'],
        ));
    }

    /**
     * [REQUIRED] this is a default column renderer
     *
     * @param $item - row (key, value array)
     * @param $column_name - string (key)
     * @return HTML
     */
    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    /**
     * [OPTIONAL] this is example, how to render specific column
     *
     * method name must be like this: "column_[column_name]"
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_age($item)
    {
        return '<em>' . $item['age'] . '</em>';
    }

    /**
     * [OPTIONAL] this is example, how to render column with actions,
     * when you hover row "Edit | Delete" links showed
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_name($item)
    {
        global $arr_table;
        // links going to /admin.php?page=[your_plugin_page][&other_params]
        // notice how we used $_REQUEST['page'], so action will be done on curren page
        // also notice how we use $this->_args['singular'] so in this example it will
        // be something like &person=2
        $actions = array(
            'view' => sprintf('<a href="?page='.$arr_table['tbl_name'].'_form&view=1&id=%s">%s</a>', $item['id'], __('View', 'custom_table_example')),
            'edit' => sprintf('<a href="?page='.$arr_table['tbl_name'].'_form&id=%s">%s</a>', $item['id'], __('Edit', 'custom_table_example')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'custom_table_example')),
        );

        return sprintf('%s %s',
            $item['name'],
            $this->row_actions($actions)
        );
    }

    /**
     * [REQUIRED] this is how checkbox column renders
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    /**
     * [REQUIRED] This method return columns to display in table
     * you can skip columns that you do not want to show
     * like content, or description
     *
     * @return array
     */
    function get_columns()
    {
        global $arr_table;
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            // 'name' => __('Name', 'custom_table_example'),
            // 'email' => __('E-Mail', 'custom_table_example'),
            // 'age' => __('Age', 'custom_table_example'),
        );
        
        $arr_columns = $arr_table['tbl_columns'];
        foreach ($arr_columns as $key => $value) {
            if($value['display_in_table'] == 1){
                $columns[$key] = $value['name'];
            }
        }
        //continue
        return $columns;
    }

    /**
     * [OPTIONAL] This method return columns that may be used to sort table
     * all strings in array - is column names
     * notice that true on name column means that its default sort
     *
     * @return array
     */
    function get_sortable_columns()
    {
        global $arr_table;
        $sortable_columns = array(
            // 'name' => array('name', true),
            // 'email' => array('email', false),
            // 'age' => array('age', false),
        );
        $arr_columns = $arr_table['tbl_columns'];
        $is_first = true;
        foreach ($arr_columns as $key => $value) {
            if($value['sort'] == 1){
                $sortable_columns[$key] = array($key, $is_first);
            }
            $is_first = false;
        }
        return $sortable_columns;
    }

    /**
     * [OPTIONAL] Return array of bult actions if has any
     *
     * @return array
     */
    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    /**
     * [OPTIONAL] This method processes bulk actions
     * it can be outside of class
     * it can not use wp_redirect coz there is output already
     * in this example we are processing delete action
     * message about successful deletion will be shown on page in next part
     */
    function process_bulk_action()
    {
        global $wpdb, $arr_table;
        $table_name = $wpdb->prefix . $arr_table['tbl_name']; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    /**
     * [REQUIRED] This is the most important method
     *
     * It will get rows from database and prepare them to be showed in table
     */
    function prepare_items()
    {
        global $wpdb, $arr_table;
        $table_name = $wpdb->prefix . $arr_table['tbl_name']; // do not forget about tables prefix

        // constant, how much records will be shown per page
        $per_page = get_option( 'posts_per_page' );
        $per_page = $default_posts_per_page > 0 ? $default_posts_per_page : 10;


        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);

        // [OPTIONAL] process bulk action if any
        $this->process_bulk_action();

        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        
        $paged = $paged * $per_page;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'name';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        // [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array
        //echo $wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged);

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
        
        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
    }
} //end class Custom_Table_Example_List_Table


$cls_admin_persons = new ta_cls_admin();
//how to call admin_menu: 
//way 1: http://stackoverflow.com/questions/5040737/why-does-using-this-inside-a-class-in-a-wordpress-plugin-throw-a-fatal-error/5040778#5040778
// add_action('admin_menu', array(&$cls_admin_persons, 'custom_table_example_admin_menu'));
//way 2: call in __construct

class ta_cls_admin{    
    /**
     * PART 3. Admin page
     * ============================================================================
     *
     * In this part you are going to add admin page for custom table
     *
     * http://codex.wordpress.org/Administration_Menus
     */

    /**
     * admin_menu hook implementation, will add pages to list persons and to add new one
    */
    function __construct() {
        add_action('admin_menu', array($this, 'custom_table_example_admin_menu'));
        add_action('init', array($this, 'custom_table_example_languages'));
    }
    public function custom_table_example_admin_menu()
    {
        global $arr_table;
        add_menu_page($arr_table['plural'], $arr_table['plural'], 'activate_plugins', $arr_table['tbl_name'], 
            array(&$this, 'custom_table_example_page_handler'));

        add_submenu_page($arr_table['tbl_name'], $arr_table['plural'], 'All ' . $arr_table['plural'], 'activate_plugins', $arr_table['tbl_name'], 
            array(&$this, 'custom_table_example_page_handler'));

        // add new will be described in next part
        add_submenu_page($arr_table['tbl_name'], 'Add New', 'Add New', 'activate_plugins', $arr_table['tbl_name'].'_form', 
            array(&$this, 'custom_table_example_form_page_handler'));
    }    

    /**
     * List page handler
     *
     * This function renders our custom table
     * Notice how we display message about successfull deletion
     * Actualy this is very easy, and you can add as many features
     * as you want.
     *
     * Look into /wp-admin/includes/class-wp-*-list-table.php for examples
     */
    public function custom_table_example_page_handler()
    {
        global $wpdb, $arr_table;

        $table = new Custom_Table_Example_List_Table();
        $table->prepare_items();

        $message = '';
        if ('delete' === $table->current_action()) {
            $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'custom_table_example'), count($_REQUEST['id'])) . '</p></div>';
        }
        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php echo $arr_table['plural'];?> <a class="add-new-h2"
                                         href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page='.$arr_table['tbl_name'].'_form') ?>"><?php _e('Add New', 'custom_table_example')?></a>
            </h2>
            <?php echo $message; ?>
            <form id="<?php echo $arr_table['tbl_name'];?>-table" method="GET">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
                <?php $table->display() ?>
            </form>
        </div>
    <?php
    }

    /**
     * PART 4. Form for adding andor editing row
     * ============================================================================
     *
     * In this part you are going to add admin page for adding andor editing items
     * You cant put all form into this function, but in this example form will
     * be placed into meta box, and if you want you can split your form into
     * as many meta boxes as you want
     *
     * http://codex.wordpress.org/Data_Validation
     * http://codex.wordpress.org/Function_Reference/selected
     */

    /**
     * Form page handler checks is there some data posted and tries to save it
     * Also it renders basic wrapper in which we are callin meta box render
     */
    public function custom_table_example_form_page_handler()
    {
        global $wpdb, $arr_table;
        $table_name = $wpdb->prefix . $arr_table['tbl_name']; // do not forget about tables prefix
        $message = '';
        $notice = '';

        // this is default $item which will be used for new records
        $default = array(
            'id' => 0
        );

        $arr_columns = $arr_table['tbl_columns'];
        foreach ($arr_columns as $key => $value) {
            if(strpos(strtolower($value['type']), "int") !== FALSE)
                $default[$key] = 0;
            elseif(strpos(strtolower($value['type']), "datetime") !== FALSE)
                $default[$key] = "0000-00-00 00:00:00";
            elseif(strpos(strtolower($value['type']), "date") !== FALSE)
                $default[$key] = "0000-00-00";
            else
                $default[$key] = "";
        }

        // here we are verifying does this request is post back and have correct nonce
        if (wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
            // combine our default item with request params
            $item = shortcode_atts($default, $_REQUEST);
            // validate data, and if all ok save item to database
            // if id is zero insert otherwise update
            $wpdb->show_errors();
            $item_valid = $this->custom_table_example_validate($item);
            if ($item_valid === true) {
                if ($item['id'] == 0) {
                    if($this->fieldExistInArrayColumns("date_created"))
                        $item['date_created'] = date("Y-m-d H:i:s");
                    if($this->fieldExistInArrayColumns("date_modified"))
                        $item['date_modified'] = date("Y-m-d H:i:s");
                    $result = $wpdb->insert($table_name, $item);
                    $item['id'] = $wpdb->insert_id;
                    if ($result) {
                        $message = __('Item was successfully saved', 'custom_table_example');
                    } else {
                        $notice = __('There was an error while saving item', 'custom_table_example');
                    }
                } else {                    
                    if($this->fieldExistInArrayColumns("date_modified"))
                        $item['date_modified'] = date("Y-m-d H:i:s");

                    $arr_upd_item = $item;
                    if($this->fieldExistInArrayColumns("date_created"))
                        unset($arr_upd_item['date_created']);

                    $result = $wpdb->update($table_name, $arr_upd_item, array('id' => $item['id']));
                    if ($result !== FALSE) {
                        $message = __('Item was successfully updated', 'custom_table_example');
                    } else {
                        $notice = __('There was an error while updating item', 'custom_table_example');
                    }
                }
                //$wpdb->print_error();
            } else {
                // if $item_valid not true it contains error message(s)
                $notice = $item_valid;
            }
        }
        else { //edit form
            // if this is not post back we load item to edit or give new one to create
            $item = $default;
            if (isset($_REQUEST['id'])) {
                $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);                
                if (!$item) {
                    $item = $default;
                    $notice = __('Item not found', 'custom_table_example');
                }
            }
        }

        // here we adding our custom meta box
        //4nd is same with first para of do_meta_boxes()
        add_meta_box($arr_table['tbl_name'].'_form_meta_box', $arr_table['singular'].' data', array(&$this, 'custom_table_example_form_meta_box_handler'), $arr_table['tbl_name'], 'normal', 'default');

        $this->add_custom_style();
        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php $arr_table['singular'];?> <a class="add-new-h2"
                                        href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page='.$arr_table['tbl_name']) ?>"><?php _e('Back To List', 'custom_table_example')?></a>
            </h2>

            <?php if (!empty($notice)): ?>
            <div id="notice" class="error"><p><?php echo $notice ?></p></div>
            <?php endif ?>
            <?php if (!empty($message)): ?>
            <div id="message" class="updated"><p><?php echo $message ?></p></div>
            <?php endif ?>

            <form id="form" method="POST">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
                <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
                <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

                <div class="metabox-holder" id="poststuff">
                    <div id="post-body">
                        <div id="post-body-content">
                            <?php /* And here we call our custom meta box */ ?>
                            <?php do_meta_boxes($arr_table['tbl_name'], 'normal', $item); ?>
                            <?php if(!isset($_GET['view'])){?>
                            <input type="submit" value="<?php _e('Save', 'custom_table_example')?>" id="submit" class="button-primary" name="submit">
                            <?php }?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    <?php
    }
    function fieldExistInArrayColumns($field){
        global $arr_table;
        $arr_columns = $arr_table['tbl_columns'];
        foreach ($arr_columns as $key => $value) {
            if($key == $field)
                return true;
        }
        return false;
    }
    /**
     * This function renders our custom meta box
     * $item is row
     *
     * @param $item
     */
    public function custom_table_example_form_meta_box_handler($item)
    {
        global $arr_table;
        if(isset($_GET['view']) && $_GET['view'] == 1){
            $this->view_detail_data($item);
            return;
        }
        ?>
        <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
            <tbody>
            <?php
                $arr_columns = $arr_table['tbl_columns'];
                $arr_data = array();
                foreach ($arr_columns as $key => $value) {
                    $this->render_textbox($key, $value['name'], $item[$key], $value['required']);
                }
            ?>            
            </tbody>
        </table>
    <?php
    }
    public function render_textbox($name, $title, $value, $required){
        $required_text = ($required == 1 ? '<span class="required">*</span>' : '');
        $required = ($required == 1 ? 'required' : '');
    ?>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="<?php echo $name;?>"><?php echo $title;?><?php echo $required_text;?></label>
            </th>
            <td>
                <?php 
                if(in_array(strtolower($name), array("date_created", "date_modified"))){
                    echo $value;
                ?>
                    <input id="<?php echo $name;?>" name="<?php echo $name;?>" type="hidden" value="<?php echo esc_attr($value)?>">
                <?php
                }else{
                ?>
                    <input id="<?php echo $name;?>" name="<?php echo $name;?>" type="text" style="width: 95%" value="<?php echo esc_attr($value)?>"
                       size="50" class="code" placeholder="<?php echo $title;?>" <?php echo $required;?>>
                <?php }?>
            </td>
        </tr>
    <?php    
    }
    public function view_detail_data($item){
        global $arr_table;
    ?>
        <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
            <tbody>
            <?php
                $arr_columns = $arr_table['tbl_columns'];
                foreach ($arr_columns as $key => $value) {
            ?>
                    <tr class="form-field">
                        <th valign="top" scope="row">
                            <?php echo $value['name'];?>
                        </th>
                        <td>
                            <?php echo $item[$key];?>
                        </td>
                    </tr>
            <?php
                }
            ?>        
            </tbody>
        </table>
    <?php
    }
    public function add_custom_style(){
    ?>
        <style type="text/css">
            .required{
                color: red;
            }
        </style>
    <?php
    }
    /**
     * Simple function that validates data and retrieve bool on success
     * and error message(s) on error
     *
     * @param $item
     * @return bool|string
     */
    public function custom_table_example_validate($item)
    {
        global $arr_table;
        $messages = array();

        $arr_columns = $arr_table['tbl_columns'];
        foreach ($arr_columns as $key => $value) {
            if($value['required'] && empty($item[$key])){
                $messages[] = $value['name'] . ' is required';
            }
        }

        if (empty($messages)) return true;
        return implode('<br />', $messages);
    }

    /**
     * Do not forget about translating your plugin, use __('english string', 'your_uniq_plugin_name') to retrieve translated string
     * and _e('english string', 'your_uniq_plugin_name') to echo it
     * in this example plugin your_uniq_plugin_name == custom_table_example
     *
     * to create translation file, use poedit FileNew catalog...
     * Fill name of project, add "." to path (ENSURE that it was added - must be in list)
     * and on last tab add "__" and "_e"
     *
     * Name your file like this: [my_plugin]-[ru_RU].po
     *
     * http://codex.wordpress.org/Writing_a_Plugin#Internationalizing_Your_Plugin
     * http://codex.wordpress.org/I18n_for_WordPress_Developers
     */
    public function custom_table_example_languages()
    {
        load_plugin_textdomain('custom_table_example', false, dirname(plugin_basename(__FILE__)));
    }    
}//end class ta_cls_admin

$cls_frontend = new cls_frontend();
class cls_frontend
{
    function __construct()
    {
        //Add script and defind ajax url
        add_action('wp_enqueue_scripts', array($this, 'add_script'));
        $ajax_function = 'save_custom_table';
        add_action( 'wp_ajax_nopriv_'.$ajax_function, array($this, $ajax_function) );
        add_action( 'wp_ajax_'.$ajax_function, array($this, $ajax_function) );
    }

    function add_script()
    {   
        // wp_enqueue_script( 'jquery', plugins_url('js/jquery-2.1.1', __FILE__) );     
        wp_enqueue_script( 'jquery-validate', plugins_url('js/jquery.validate.min.js', __FILE__), array( 'jquery' ), '1.0.3' );

        wp_register_script( "custom-table", plugins_url('js/custom-table.js', __FILE__), array('jquery') );
        wp_localize_script( 'custom-table', 'custom_table_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ))); 
        wp_enqueue_script( 'custom-table' );
    }
    
    function save_custom_table(){
        global $wpdb, $arr_table;
        $table_name = $wpdb->prefix . $arr_table['tbl_name'];
        $arr_columns = $arr_table['tbl_columns'];
        $id = (isset($_POST['id']) && $_POST['id'] > 0) ? $_POST['id'] : 0;
        $arr_data = array();
        foreach ($arr_columns as $key => $value) {
            $field_value = isset($_POST[$key]) ? $_POST[$key] : "";
            if(in_array(strtolower($key), array("date_created", "date_modified")))
                $field_value = date("Y-m-d H:i:s");
            else
                $arr_data[$key] = $field_value;

            $arr_data[$key] = $field_value;       
        }
        if($id > 0)
            unset($arr_data['date_created']);
        //print_r($arr_data);
        if($id == 0)
            $result = $wpdb->insert($table_name, $arr_data);
        else
            $result = $wpdb->update($table_name, $arr_data, array('id' => $id));
        
        if ($result !== FALSE)
            $result = array("status"=>1, 'message'=>'ok' );
        else
            $result = array("status"=>0, 'message'=>'Fail' );

        echo json_encode($result);
        die();
    }
}