<?php
namespace ForTrainingNG;
use SkinMustache;

/**
 * Makes various template data available so that data in 1.36 is available in 1.35.
 */
class SkinForTrainingNG extends SkinMustache {

	protected function getPortletData( $name, array $items ) {
		//$this->console_log($name);
		//$this->console_log($items);
		if (($name === 'tb') && !$this->getSkin()->getUser()->isLoggedIn()) {
			// Show toolbar only for logged-in users
			return null;
		} else {
			return parent::getPortletData( $name, $items);
		}
	}

	final public function console_log( $data ){
		echo '<script>';
		echo 'console.log('. json_encode( $data ) .')';
		echo '</script>';
	}

}