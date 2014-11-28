<?php

class Controller_Api_PexCard extends Crunchbutton_Controller_RestAccount {

	public function init() {

		switch ( c::getPagePiece( 2 ) ) {

			case 'pex-id':
				$this->_pex_id();
				break;

			case 'driver-search':
				$this->_driver_search();
				break;

			case 'driver-active':
				$this->_driver_active();
				break;

			case 'admin-pexcard':
				$this->_admin_pexcard();
				break;

			case 'admin-pexcard-remove':
				$this->_admin_pexcard_remove();
				break;

			case 'pexcard-change-card-status':
				$this->_pexcard_change_card_status();
				break;

			default:
				$this->_error();
				break;
		}

	}

	private function _driver_active(){

		$id_pexcard = $this->request()[ 'id_pexcard' ];

		if( $id_pexcard ){
			$id_admin = $this->request()[ 'id_admin' ];
			$id_admin = c::admin()->id_admin;
			$admin_pexcard = Cockpit_Admin_Pexcard::getByPexcard( $id_pexcard );
			if( !$admin_pexcard->id_admin ){
				$opened = false;
				$customer = Crunchbutton_Pexcard_Card::details( $id_pexcard );
				if( $customer->body && $customer->body->cards ){
					foreach( $customer->body->cards as $card ){
						$last_four = str_replace( 'X', '', $card->cardNumber );
						Crunchbutton_Pexcard_Card::change_status( $card->id, Crunchbutton_Pexcard_Card::CARD_STATUS_OPEN );
						$opened = true;
					}
				}

				if( $opened ){
					$admin_pexcard->card_serial = $customer->body->lastName;
					$admin_pexcard->last_four = $last_four;
					$admin_pexcard->id_admin = $id_admin;
					$admin_pexcard->save();
					$admin_pexcard = Cockpit_Admin_Pexcard::o( $admin_pexcard->id_admin_pexcard );
					$admin = $admin_pexcard->admin();
					$card = $customer->body;
					$card->admin_name = $admin->name;
					echo json_encode( [ 'success' => $card ] );exit();
				}
			}
		}
		$this->_error( 'Card Not Found' );
	}

	private function _driver_search(){
		$crunchbutton_id = $this->request()[ 'crunchbutton_card_id' ];
		$last_four_digits = $this->request()[ 'last_four_digits' ];

		if( $crunchbutton_id ){
			$cards = Crunchbutton_Pexcard_Card::card_list();
			if( is_array( $cards->body ) ){
				foreach( $cards->body as $card ){
					if( $card->lastName == $crunchbutton_id ){
						$admin_pexcard = Cockpit_Admin_Pexcard::getByPexcard( $card->id );
						if( !$admin_pexcard->id_admin ){

							$_cards = $card->cards;
							foreach( $_cards as $_card ){
								if( strpos( $_card->cardNumber, $last_four_digits ) ){
									echo json_encode( $card );exit;
								}
							}
						}
					}
				}
			} else {
				$this->_error( 'Oops, something is wrong!' );
			}
		}
		$this->_error( 'Card Not Found' );
	}

	private function _admin_pexcard(){

		if( !c::admin()->permission()->check( ['global', 'settlement' ] ) ){
			$this->_error();
		}

		$id_admin = $this->request()[ 'id_admin' ];
		$id_pexcard = $this->request()[ 'id_pexcard' ];
		$card_serial = $this->request()[ 'card_serial' ];
		$last_four = $this->request()[ 'last_four' ];
		$last_four = str_replace( 'X', '', $last_four );
		$admin_pexcard = Cockpit_Admin_Pexcard::getByPexcard( $id_pexcard );
		$admin_pexcard->id_admin = $id_admin;
		$admin_pexcard->card_serial = $card_serial;
		$admin_pexcard->last_four = $last_four;
		$admin_pexcard->save();
		$admin_pexcard = Cockpit_Admin_Pexcard::o( $admin_pexcard->id_admin_pexcard );
		$admin = $admin_pexcard->admin();
		echo json_encode( [ 'success' => [ 'name' => $admin->name, 'login' => $admin->login ] ] );
	}

	private function _pexcard_change_card_status(){

		if( !c::admin()->permission()->check( ['global', 'settlement' ] ) ){
			$this->_error();
		}

		$id_card = $this->request()[ 'id_card' ];
		$status = $this->request()[ 'status' ];
		$card = Crunchbutton_Pexcard_Card::change_status( $id_card, $status );
		if( $card->body && $card->body->id ){
			$card = $card->body;
			$admin_pexcard = Cockpit_Admin_Pexcard::getByPexcard( $card->id );
			if( $admin_pexcard->id_admin ){
				$card->id_admin = intval( $admin_pexcard->id_admin );
				$card->admin_name = $admin_pexcard->admin()->name;
				$card->admin_login = $admin_pexcard->admin()->login;
			}
			echo json_encode( $card );exit;
		}
		$this->_error( 'Oops, something is wrong!' );
	}

	private function _admin_pexcard_remove(){

		if( !c::admin()->permission()->check( ['global', 'settlement' ] ) ){
			$this->_error();
		}

		$id_pexcard = $this->request()[ 'id_pexcard' ];
		$admin_pexcard = Cockpit_Admin_Pexcard::getByPexcard( $id_pexcard );
		if( $admin_pexcard->id_admin_pexcard ){
			$admin_pexcard->id_admin = null;
			$admin_pexcard->save();
		}
		echo json_encode( [ 'success' => $admin_pexcard->id_admin_pexcard ] );
	}

	private function _pex_id(){

		if( !c::admin()->permission()->check( ['global', 'settlement' ] ) ){
			$this->_error();
		}

		$crunchbutton_id = $this->request()[ 'id' ];
		if( $crunchbutton_id ){
			$cards = Crunchbutton_Pexcard_Card::card_list();
			if( is_array( $cards->body ) ){
				foreach( $cards->body as $card ){
					if( $card->lastName == $crunchbutton_id ){
						$admin_pexcard = Cockpit_Admin_Pexcard::getByPexcard( $card->id );
						if( $admin_pexcard->id_admin ){
							$card->id_admin = intval( $admin_pexcard->id_admin );
							$card->admin_name = $admin_pexcard->admin()->name;
							$card->admin_login = $admin_pexcard->admin()->login;
						}
						echo json_encode( $card );exit;
					}
				}
			} else {
				$this->_error( 'Oops, something is wrong!' );
			}
		}
		$this->_error( 'Card Not Found' );
	}

	private function _error( $error = 'invalid request' ){
		echo json_encode( [ 'error' => $error ] );
		exit();
	}
}