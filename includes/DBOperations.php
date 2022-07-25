<?php

class DBOperations{
    private $connection;

     /**
     * Class constructor.
     */
    function __construct()
    {
        require_once dirname(__FILE__)."/DbConnect.php";
        $dbConnect=new DBConnect;
        $this->connection=$dbConnect->connect();
    }

    private function isEmailExist($email){
        $stmt=$this->connection->prepare("SELECT * FROM `users` WHERE `email`=?");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows>0;
    }

    public function createUser($email, $password, $name, $school){
        if (!$this->isEmailExist($email)) {
            $stmt=$this->connection->prepare("INSERT INTO `users`(email,password,name,school) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss",$email,$password,$name,$school);
            if ($stmt->execute()) {
                return USER_CREATED;
            }else{
                return USER_FAILURE;
            }
        }else{
            return USER_EXISTS;
        }
    }


}