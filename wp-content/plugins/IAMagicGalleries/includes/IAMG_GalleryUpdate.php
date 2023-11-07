<?php
/*
 * Copyright © 2023  Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

/*
 * Copyright © 2023  Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

if(!defined('WPINC')) die;
if(!defined("ABSPATH")) exit;

//For future use
class IAMG_GalleryUpdate {
	public $posts = array();

	public $needUpdate = 1;

	public $dbVersionOld = false;

	public $dbVersion = false;

	public $fieldArray = array(
		); 

//	public $functionArray = array(
//		);

	public function __construct(){
		
		$curVersion = get_option( 'iamg_install_version', 0 );

		if( $curVersion != IAMG_VERSION ){
			update_option('iamg_install_date', time() );
			update_option('iamg_install_version', IAMG_VERSION );
		}
		
//		$this->dbVersionOld = get_option( 'iamg_db_version', 0 );
//
//		$this->dbVersion = IAMG_VERSION;
//
//		if( $this->dbVersionOld == $this->dbVersion )  $this->needUpdate = false;

		if( $this->needUpdate ){
			update_option( 'iamg_after_install', '1' );

//            update_option( 'iamg_db_version', IAMG_VERSION );

            if (( count($this->fieldArray) )) {
				$this->posts = $this->getGalleryPost();
				$this->update();
			}
		}
	}


	public function getGalleryPost(){
		$my_wp_query = new WP_Query();
 		return $my_wp_query->query( 
				array( 
					'post_type' => IAMG_POST_TYPE,
					'posts_per_page' => 999, 
				)
			);
	}
	
	public function fieldInit( $fields ){
		for($i=0;$i<count($this->posts);$i++){
			$postId = $this->posts[$i]->ID;
			if( count($fields) ){
				foreach($fields as $key => $value){
					add_post_meta( $postId, IAMG_PREFIX.$key, $value, true );
				}
			}
		}
	}

	public function update(){
		if( count($this->fieldArray) ){
			foreach($this->fieldArray as $version => $fields){
				if( 
					version_compare( $version, $this->dbVersionOld, '>')
                    || version_compare( $version, $this->dbVersion, '<=')
				){
					if( isset($fields) ) $this->fieldInit( $fields );
				}
			}
		}
	}
}
$update = new IAMG_GalleryUpdate();