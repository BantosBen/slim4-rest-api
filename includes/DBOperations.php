<?php

class DBOperations
{
    private $connection;

    /**
     * Class constructor.
     */
    function __construct()
    {
        require_once dirname(__FILE__) . "/DbConnect.php";
        $dbConnect = new DBConnect;
        $this->connection = $dbConnect->connect();
    }

    private function isEmailExist($email)
    {
        $stmt = $this->connection->prepare("SELECT * FROM `users` WHERE `email`=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function createUser($email, $password, $name, $school)
    {
        if (!$this->isEmailExist($email)) {
            $stmt = $this->connection->prepare("INSERT INTO `users`(email,password,name,school) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $email, $password, $name, $school);
            if ($stmt->execute()) {
                return USER_CREATED;
            } else {
                return USER_FAILURE;
            }
        } else {
            return USER_EXISTS;
        }
    }
    private function getUserPasswordByEmail($email)
    {
        $stmt = $this->connection->prepare("SELECT `password` FROM `users` WHERE `email`=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($password);
        $stmt->fetch();
        return $password;
    }
    public function userLogin($email, $password)
    {
        if ($this->isEmailExist($email)) {
            $hashed_password = $this->getUserPasswordByEmail($email);
            if (password_verify($password, $hashed_password)) {
                return USER_AUTHENTICATED;
            } else {
                return USER_PASSWORD_DO_NOT_MATCH;
            }
        } else {
            return USER_NOT_FOUND;
        }
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->connection->prepare("SELECT `id`, `email`, `name`, `school` FROM `users` WHERE `email`=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($id, $email, $name, $school);
        $stmt->fetch();
        $user = array();
        $user['name'] = $name;
        $user['email'] = $email;
        $user['school'] = $school;
        $user['id'] = $id;
        return $user;
    }

    public function getAllUsers()
    {
        $stmt = $this->connection->prepare("SELECT `id`, `email`, `name`, `school` FROM `users`");
        $stmt->execute();
        $stmt->bind_result($id, $email, $name, $school);
        $users = array();
        while ($stmt->fetch()) {
            $user = array();
            $user['name'] = $name;
            $user['email'] = $email;
            $user['school'] = $school;
            $user['id'] = $id;

            array_push($users, $user);
        }

        return $users;
    }

    public function updateUser($name, $email, $school, $id)
    {
        $stmt = $this->connection->prepare("UPDATE `users` SET `name`=?, `email`=?, `school`=? WHERE `id`=?");
        $stmt->bind_param("sssi", $name, $email, $school, $id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function updateUserPassword($newPassword, $currentPassword, $email)
    {
        $hashed_currentPassword = $this->getUserPasswordByEmail($email);

        if (password_verify($currentPassword, $hashed_currentPassword)) {
            $hashed_newPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $this->connection->prepare("UPDATE `users` SET `password`=? WHERE `email`=?");
            $stmt->bind_param("ss", $hashed_newPassword, $email);
            if ($stmt->execute()) {
                return PASSWORD_CHANGED;
            }
            return PASSWORD_NOT_CHANGED;
        }else{
            return PASSWORD_DO_NOT_MATCH;
        }
    }
}
