<?php

class Controller_api_driver_onboarding extends Crunchbutton_Controller_Rest {
	
	public function init() {

		if( $this->method() != 'post' ){
			$this->_error();
		}

		$name = $this->request()[ 'name' ];
		if( trim( $name ) == '' ){
			$this->_error( 'Enter your name!' );
		}

		$phone = preg_replace( '/[^0-9]/i', '', $this->request()[ 'phone' ] );
		if( trim( $phone ) == '' ){
			$this->_error( 'Enter your phone!' );
		}

		$admin = Admin::q( 'SELECT * FROM admin WHERE phone = "' . $phone . '"' );
		if( $admin->count() != 0 ){
			$this->_error( 'This phone is already registred!' );
		}

		$email = $this->request()[ 'email' ];
		if( trim( $email ) ){
			$admin = Admin::q( 'SELECT * FROM admin WHERE email = "' . $email . '"' );
			if( $admin->count() != 0 ){
				$this->_error( 'This email is already registred!' );
			}
		}

		$driver = new Crunchbutton_Admin();
		$driver->active = 0;
		$driver->name = $name;
		$driver->phone = $phone;
		if( $email && trim( $email ) != '' ){
			$driver->email = $email;
		}
		$driver->save();

		$driver = Crunchbutton_Admin::o( $driver->id_admin );

		// create an username
		$driver->login = $driver->createLogin();
		$driver->save();

		Log::debug( [ 'action' => 'new driver created', 'driver' => $driver->id_admin, 'name' => $name, 'phone' => $phone, 'email' => $email, 'type' => 'drivers-onboarding'] );

		// add the community
		$id_community = $this->request()[ 'id_community' ];
		if( $id_community ){
			$community = Crunchbutton_Community::o( $id_community );

			$driver->timezone = $community->timezone;
			$driver->save();

			if( $community->id_community ){
				$group = $community->groupOfDrivers();
				$adminGroup = new Crunchbutton_Admin_Group();
				$adminGroup->id_admin = $driver->id_admin;
				$adminGroup->id_group = $group->id_group;
				$adminGroup->save();
			}	
		}

		// Create phone notification
		if( $driver->phone ){
			$notification = new Crunchbutton_Admin_Notification();
			$notification->value = $driver->phone;
			$notification->type = Crunchbutton_Admin_Notification::TYPE_SMS;
			$notification->active = 1;
			$notification->id_admin = $driver->id_admin;
			$notification->save();
		}

		// Create email notification
		if( $driver->email ){
			$notification = new Crunchbutton_Admin_Notification();
			$notification->value = $driver->email;
			$notification->type = Crunchbutton_Admin_Notification::TYPE_EMAIL;
			$notification->active = 1;
			$notification->id_admin = $driver->id_admin;
			$notification->save();
		}

		// register the log
		$log = new Cockpit_Driver_Log();
		$log->id_admin = $driver->id_admin;
		$log->action = Cockpit_Driver_Log::ACTION_CREATED_DRIVER;
		$log->datetime = date('Y-m-d H:i:s');
		$log->save();

		Cockpit_Driver_Notify::send( $driver->id_admin, Cockpit_Driver_Notify::TYPE_WELCOME );

		echo json_encode( [ 'success' => $driver->exports() ] );
		return;
	}

	private function _error( $error = 'invalid request' ){
		echo json_encode( [ 'error' => $error ] );
		exit();
	}
}