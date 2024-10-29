<?php
/*
Plugin Name: Author2Categories 
Plugin URI: 
Description: Allows you to limit the categories users can post entries to.   
Author: Sung Lee
Version: 1.1
Author URI: http://logicalschema.wordpress.com
*/ 

// mySQL table
$user_cats_table = $table_prefix . "authors2categories";

a2c_check_table();

//This function is to check the database for the existence of the table. If it exists, nothing is done. 
//If it does not exist, it creates a new table with the name table_prefix + user_cats_manager

function a2c_check_table() {
	global $user_cats_table, $wpdb;

	
	if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $user_cats_table)) != $user_cats_table) {
	
		$query = sprintf("
			CREATE TABLE %s (
			  user_id int(11) NOT NULL, 
			  term_id int(11) NOT NULL,
			  PRIMARY KEY (user_id, term_id))",
		  	$user_cats_table );
		echo $query;
		$wpdb->query($query);
	}
		 
		 
} //a2c_check_table

// administration panel
function a2c_admin() {
	if (function_exists('add_management_page')) {
		add_management_page('Authors2Categories Options', 'Authors2Categories', 8, basename(__FILE__), 'authors2categories_admin_page');
	}
}

function authors2categories_admin_page() {
	global $table_prefix, $user_cats_table, $wpdb;
	
	
	if (isset($_POST['edit_user'])) {
		
	}
	
	if (isset($_POST['save'])) {
		$valueString = "VALUES ";
		

		$query = sprintf("DELETE FROM %s WHERE user_id=%d",$user_cats_table,$_POST['user']);
		$wpdb->query($query);
		
		
		if (isset($_POST['categories']) && isset($_POST['user']) &&  count($_POST['categories']) > 0 ){
			for ($i=0; $i < count($_POST['categories']); $i++){
				$valueString = $valueString . "(" . $_POST['user'] . "," . $_POST['categories'][$i] . "),";
			}
			
			$valueString = substr_replace($valueString,"",-1);
			
		/**
			$query = "INSERT INTO $user_cats_table (user_id, term_id) " . $valueString;
			mysql_query($query);
		**/
		
			$query = sprintf("INSERT INTO %s (user_id, term_id) %s", $user_cats_table, $valueString);
			$wpdb->query($query);
			
			_e('Categories were added');
		}

	}
		
	?>

	<div class="wrap">
		<div id="users">
			<h2>User Category Manager</h2>
				<form name="l2c" method="post">
  				<h3><label for="ucm_users">
    			Select the user
    			</label></h3>
  			<div class="submit">
    		<select name="user" id="ucm_users">
    
    			<?php
    			global $wpdb;
    			$userSelected = 0;
    			
    			if (isset($_POST['user']))
						$userSelected = $_POST['user'];
				else
						$userSelected = 1;    			
    			
    	
    			$userRows = $wpdb->get_results("SELECT ID, user_login FROM $wpdb->users ORDER BY user_login");
    			foreach ($userRows as $userRow) {
    				if ($userRow->ID == $userSelected)
    					echo "<option value=\"" . $userRow->ID . "\" selected=\"selected\">" . $userRow->user_login . "</option>"; 
    				else
    					echo "<option value=\"" . $userRow->ID . "\">" . $userRow->user_login . "</option>";
    			}
				?>
    		</select>
    	
    		<input type="submit" name="edit_user" value="<?php _e('Edit User', '') ?> &raquo;">
   	
   			<BR><BR><BR>
			
			
  			<h2>Editing User</h2>
 			 <div id="message" class="updated fade"><p>Choose the categories that the user can use</p></div>

			<div class="wrap" id="usercategories">
  			<table class="form-table">
				<?php 
					global $wpdb, $user_cats_table;
					$elementString = "";
					$selected = array();
					
    				if (isset($_POST['user'])) {
						$userSelected = $_POST['user'];
						
						$query = sprintf("SELECT term_id FROM %s WHERE user_id=%d ORDER BY term_id", $user_cats_table, $userSelected);
						$checkCategories = $wpdb->get_results($query);
						
						foreach ($checkCategories as $checkCategory) {
    						$selected[] = $checkCategory->term_id;
    					}

					}	
					else
						$userSelected = 1;    
						
					
					$categoryRows = $wpdb->get_results("SELECT term_id, name FROM $wpdb->terms ORDER BY name");
		
					foreach ($categoryRows as $categoryRow) {
						echo "<input type=\"checkbox\" name=\"categories[]\" value=\"" . $categoryRow->term_id . "\" ";
						if (in_array($categoryRow->term_id, $selected)) echo "checked=\"checked\"";
						echo "/> " . $categoryRow->name . "<br />"; 
    				}
				?>
      		</table>
      		</div>
		 	<input type="submit" name="save" value="<?php _e('Save', '') ?> &raquo;" >	
  			</div>
				</form>
		
		</div>
	</div>
	  </form>
	</div>

	<?php
}

function a2c_disable_cats(){
	global $user_cats_table, $current_user, $wpdb;
	get_currentuserinfo();

	$myID = $current_user->ID;	
	
	$selectedCategories=$wpdb->get_results("SELECT term_id FROM $user_cats_table WHERE user_id= $myID");
	foreach ($selectedCategories as $selectedCategory){
		$myCategories[] = $selectedCategory->term_id;
	}
	
	echo "<script>\n";		

	$term_categories = $wpdb->get_results("SELECT term_id FROM $wpdb->terms ORDER BY term_id");
	foreach ($term_categories as $termCategory) {
		if (in_array($termCategory->term_id, $myCategories) == false){
			$element = $termCategory->term_id;
			echo "if (document.getElementById('category-" . $element . "'))" . "document.getElementById('category-" . $element . "').style.display='none';" . "\n";
			echo "if (document.getElementById('popular-category-" . $element . "'))" . "document.getElementById('popular-category-" . $element . "').style.display='none';" . "\n";
		}
	}
		
	echo "</script>\n";
}

add_action('simple_edit_form', 'a2c_disable_cats');
add_action('edit_form_advanced', 'a2c_disable_cats');
add_action('admin_menu', 'a2c_admin');
?>