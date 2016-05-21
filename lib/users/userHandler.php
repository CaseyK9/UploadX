<?php

/* 

@author: Pips

@title: User Handler
@desc: class that handles user creation and permission checking.

 userHandler:
 
	__construct(): Build itself by loading everything from file into memory and into users.
	
	createUser(username): Create a user based off of the username given. Server defaults will be used.
	
	saveUser(user): Overrides a user. Either build a new user object and pass it, or use something that returns a user.
	
	save(): Saves all the files and rebuilds the constructor.
	
	generateKey(): Returns a random upload key. upper-alpha-numeric 6 characters.
	
	getUsers(): Returns an array of user objects.
	
	getUsersAsJson(): Returns the JSON data from file.
	
	deleteUser(username): Deletes user with the given username. 
	
	changeKey(username, key): Changes the key generated by generateKey() to whatever is given. Useful for resetting keys or changing them to work with old accounts.
	
	newKey(username): Re-generates the key for the given user.
	
	isValidKey(key): Returns true or false if the given key exists in the keylist.
	
	getUser(username): Returns the user from the given username.
	
	isUser(username): Returns true or false if the given username belongs to a user.
	
	getUserByKey(key): Returns the user who has the given key.
	
*/

class userHandler {
    
    protected $users;
    protected $SettingsHandler;
    protected $users_json;
    
    function __construct() {
        
        // create settings handler to check user creation agaist settings
        $this->settingsHandler = new settingsHandler();
        
        // users array
        $this->users = [];
        
        // users array as raw JSON from file
        $this->users_json = json_decode(file_get_contents(__DIR__ . '/../files/users.json'), true);
        
        // loop through each user and create it, then add it to the $users array
        foreach ($this->users_json as $username => $settings) {
            
            $access_key     = $settings['access_key'];
            $filesize_limit = $settings['filesize_limit'];
            $uploads        = $settings['uploads'];
            $enabled        = $settings['enabled'];
            
            
            $user = new user($username, $access_key, $filesize_limit, $uploads, $enabled);
            
            array_push($this->users, $user);
        }
        
    }
    
    // create user. Should add support to limit uploads. later.
    function createUser($username) {
        
        if (!$this->isUser($username)) {
            
            $access_key     = $this->generateKey();
            $filesize_limit = $this->settingsHandler->getSettings()['limits']['size'];
            $uploads        = 0;
            $enabled        = true;
            
            $user = new user($username, $access_key, $filesize_limit, $uploads, $enabled);
            
            array_push($this->users, $user);
            
            $this->users_json[$username]['access_key']     = $access_key;
            $this->users_json[$username]['filesize_limit'] = $filesize_limit;
            $this->users_json[$username]['uploads']        = $uploads;
            $this->users_json[$username]['enabled']        = $enabled;
            
            $this->save();
            
        } else {
            
            # throw user exists error
            
        }
        
        
    }
    
    // this is a way that we can update a user's settings; by recreating the uesr out-of-class, then passing it here.
    function saveUser($user) {
        
        $this->users_json[$user->username]['access_key']     = $user->access_key;
        $this->users_json[$user->username]['filesize_limit'] = $user->filesize_limit;
        $this->users_json[$user->username]['uploads']        = $user->uploads;
        $this->users_json[$user->username]['enabled']        = $user->enabled;
        
        $this->save();
        
    }
    
    // this just saves the json data to the fime.
    function save() {
        
        file_put_contents(__DIR__ . '/../files/users.json', json_encode($this->users_json, JSON_PRETTY_PRINT));
        
        $this->__construct();
        
        
        
    }
    
	// return a randomly generated key. upper-alpha-numeric
    private function generateKey() {
        
        $legnth = 6;
        $set    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        return substr(str_shuffle($set), 0, $legnth);
        
        
    }
    
    // return the array of users.
    function getUsers() {
        
        return $this->users;
        
    }
    
    // returns the json array of users. 
    function getUsersAsJson() {
        
        
        return $this->users_json;
        
    }
    
    // delete a user by his/her username.
    function deleteUser($username) {
        
        if ($this->isUser($username)) {
            
            unset($this->users_json[$username]);
            
            $this->save();
            
        }
        
    }
	
	// change key to given key for given user
    function changeKey($username, $newkey) {
        
        if ($this->isUser($username)) {
            
            $user = $this->getUser($username);
            
            $user->access_key = $newkey;
            
            $this->saveUser($user);
            
        } else {
            
            # not a user
            
        }
        
    }
	
	// generate a new key for the given user
	function newKey($username){
		
		if ($this->isUser($username)){
		
			$user = $this->getUser($username);

			$user-> access_key = $this->generateKey();

			$this-> saveUser($user);
		}

	}
    
    // check if the given key is a valid upload key.
    function isValidKey($key) {
        
        $valid = false;
        
        foreach ($this->users_json as $user) {
            
            if ($key == $user['access_key']) {
                
                $valid = true;
                
            }
            
        }
        
        return $valid;
        
    }
    
    // return the user associated to the username
    function getUser($username) {
        
        if ($this->isUser($username)) {
			
            foreach ($this->users as $u) {
                
                if ($username == $u->username) {
                    
                    return $u;
                    
                }
                
            }
			
			return null;
            
        }
        
    }
    
    // check if the given username is an actual user.
    function isUser($username) {
        
        $valid;
        
        foreach ($this->users as $user) {
            
            if ($username == $user->username) {
                
                return true;
                
            }
            
        }
        
        return false;
        
    }
    
    // return the user that has the given key.
    function getUserByKey($key) {
        
        if ($this->isValidKey($key)) {
            
            $user_from_key = null;
            
            foreach ($this->users as $user) {
                
                if ($key == $user->access_key) {
                    
                    $user_from_key = $user;
                    
                }
                
                
                
            }
            
            return $user_from_key;
            
        }
        
    }    
    
	function generateJson($username){
		
		$user = $this->users_json[$username];
		
		$json = json_decode(file_get_contents(__DIR__ . '/../files/import.json'), true);
		
		$json['Name'] = "$username - " . $GLOBALS['home'];
		
		$json['RequestURL'] = $GLOBALS['home'] . 'index.php';
		
		$json['Arguments']['key'] = $user['access_key'];
		
		$json = json_encode($json, JSON_PRETTY_PRINT);
		
		header("Content-type: text/json");
		header("Connection: Keep-Alive");
      	header("Cache-control: public");
      	header("Pragma: public");
      	header("Expires: Mon, 27 Mar 2038 13:33:37 GMT");
      	header('Content-Disposition: inline; filename="' . $username . '.json"' );
		echo $json;
		
		// how the fuck do I upload $file :C
		
		
		
				
	}
	
	function enableUser($username, $enabled){
		
		if($this->isUser($username)){
			
			$user = $this->getUser($username);
			$user->enabled = $enabled;
			$this->saveUser($user);
			
		}else{
			
			#not a user
			
		}
		
	}
}


?>