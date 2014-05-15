<?php

class Crunchbutton_Driver_Document_Status extends Cana_Table {

	public function __construct($id = null) {
		parent::__construct();
		$this
			->table('driver_document_status')
			->idVar('id_driver_document_status')
			->load($id);
	}

	public function www(){
		return Util::uploadWWW() . 'drivers-doc/';
	}

	public function path(){
		return Util::uploadPath() . '/drivers-doc/';
	}

	public function document( $id_admin, $id_driver_document ){
		$document = Crunchbutton_Driver_Document_Status::q( 'SELECT * FROM driver_document_status WHERE id_admin = ' . $id_admin . ' AND id_driver_document =' . $id_driver_document )->get( 0 );	
		if( $document->id_driver_document ){
			return $document;
		}
		return new Crunchbutton_Driver_Document_Status();
	}

	public function exports(){
		$out = $this->properties();
		$out[ 'url' ] = Crunchbutton_Driver_Document_Status::www() . $out[ 'file' ];
		return $out;
	}

}