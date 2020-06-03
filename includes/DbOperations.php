<?php  
    class DbOperations
    {
        private $con;

        /**
         * 
         */
        function __construct()
        {
            require_once dirname(__FILE__).'/DbConnect.php';
            $db=new DbConnect;
            $this->con=$db->connect();
        }

        /**
         * 
         */
        function createUser($email,$password,$name,$school)
        {
           if(!$this->isEmailAlreadyExist($email))
           {
            
            //prepare and bind
            $stmt= $this->con->prepare("INSERT INTO users (email,password,name,school) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss",$email,$password,$name,$school);

            /**
             * execute() returns boolean true if executed and false for otherwise
             */
            if($stmt->execute())
            {
                return USER_CREATED;
            }
            else{
                return USER_FAILURE;
            }
           }
           return USER_EXISTS;
        }

        /**
         * 
         */
        private function isEmailAlreadyExist($email)
        {
            $stmt=$this->con->prepare("SELECT id FROM users WHERE email=?");
            $stmt->bind_param("s",$email);
            $stmt->execute();
            $stmt->store_result();
            return $stmt->num_rows>0;
        }

        /**
         * 
         */
        public function userLogin($email,$password)
        {
            if($this->isEmailAlreadyExist($email))
            {
               $fetched_password = $this->getUsersPasswordByEmail($email);
                if($fetched_password==$password)
                {
                    return USER_AUTHENTICATED;
                }
                else{
                    return USER_AUTHENTICATION_FAILED;
                }
            }
            else{
                return USER_NOT_FOUND;
            }
        }

        /**
         * 
         */
        public function getUserByEmail($email)
        {
            $stmt=$this->con->prepare("SELECT id, email, name, school FROM users WHERE email=?");
            $stmt->bind_param("s",$email);
            $stmt->execute();
             /* bind variables (from database)to prepared statement */
            $stmt->bind_result($id,$email,$name,$school);
            $stmt->fetch();
            $user=array();
            $user['id']=$id;
            $user['email']=$email;
            $user['name']=$name;
            $user['school']=$school;
            return $user;
        }

        /**
         * 
         */
        public function getAllUsers()
        {
            $stmt=$this->con->prepare("SELECT id, email, name, school FROM users");
            $stmt->execute();
             /* bind variables (from database)to prepared statement */
            $stmt->bind_result($id,$email,$name,$school);
            $all_users=array();
            while ($stmt->fetch()) 
            {
                $user=array();
                $user['id']=$id;
                $user['email']=$email;
                $user['name']=$name;
                $user['school']=$school;
                array_push($all_users,$user);
            } 
            return $all_users;  
        }

        /**
         * 
         */
        private function getUsersPasswordByEmail($email)
        {
            $stmt=$this->con->prepare("SELECT password FROM users WHERE email=?");
            $stmt->bind_param("s",$email);
            $stmt->execute();
             /* bind variables (from database)to prepared statement */
            $stmt->bind_result($password);
            $stmt->fetch();
            return $password;
        }
        
        /**
         * 
         */
        public function updateUser($email,$name,$school,$id)
        {
            $stmt=$this->con->prepare("UPDATE users SET email = ?,name = ?,school = ? where id = ?");
            $stmt->bind_param("sssi",$email,$name,$school,$id);
            if($stmt->execute())
            return true;
            else
            return false;
        }

        /**
         * 
         */
        public function updatePassword($currentPassword,$newPassword,$email)
        {
            $fetched_password = $this->getUsersPasswordByEmail($email);
            if($fetched_password==$currentPassword)
                {
                    $stmt=$this->con->prepare("UPDATE users SET password = ? where email = ?");
                    $stmt->bind_param('ss',$newPassword,$email);

                    if($stmt->execute())
                    {
                        return PASSWORD_CHANGED;
                    }else
                    {
                        return PASSWORD_NOT_CHANGED;
                    }
                }
                else{
                    return PASSWORD_DO_NOT_MATCH;
                }
        }

        public function deleteUser($id)
        {
            $stmt=$this->con->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i",$id);
            if($stmt->execute())
            {
                return true;
            }
            return false;
        }
    }