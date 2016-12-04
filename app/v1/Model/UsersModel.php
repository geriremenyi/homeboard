<?php

namespace App\v1\Model;
use Resty\Database\Database;
use Resty\Database\DatabaseCommand;
use Resty\Model\Model;

/**
 * User Model
 *
 * Model for the users
 *
 * @package    App
 * @subpackage v1\Model
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class UsersModel extends Model {

    /**
     * Username
     *
     * @var string
     */
    protected $username;

    /**
     * Password
     *
     * @var string
     */
    protected $password;

    /**
     * Random salt for password
     *
     * @var string
     */
    protected $salt;

    /**
     * Role of the user
     *
     * @var string
     */
    protected $role;

    /**
     * First name
     *
     * @var string
     */
    protected $firstName;

    /**
     * Middle name
     *
     * @var string|null
     */
    protected $middleName;

    /**
     * Last name
     *
     * @var string
     */
    protected $lastName;

    /**
     * Lives in flat ID
     *
     * @var int|null
     */
    protected $livesFlatId;

    /**
     * Fill new user model by incoming params
     *
     * @param string $username - Username of the new user
     * @param string $password - Password of the new user
     * @param string $salt - Random salt of the new user for two levels security check
     * @param string $role - Role of the new user
     * @param string $firstName - First name of the new user
     * @param $middleName - Middle name of the new user
     * @param string $lastName - Last name of the new user
     * @param $livesFlatId - The flat id of the new user which he/she lives in
     */
    public function create(string $username, string $password, string $salt, string $role, string $firstName, $middleName, string $lastName, $livesFlatId) {
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->role = $role;
        $this->firstName = $firstName;
        $this->middleName = $middleName;
        $this->lastName = $lastName;
        $this->livesFlatId = $livesFlatId;
    }

    /**
     * Get searchable fields
     *
     * @return array
     */
    public function getSearchableFields() : array {
        return ['username'];
    }

    public function getClaims() : array {
        return [
            'user_id' => $this->id,
            'user_role' => $this->role
        ];
    }

    /**
     * Try to login by username and password
     *
     * @param string $username - Username of the user to login
     * @param string $password - SHA512 encrypted password string of the user
     * @return UsersModel|bool
     */
    public function login(string $username, string $password) : bool {
        $command = new DatabaseCommand(Database::getConnection(), 'SELECT id, password, salt, role FROM users WHERE username=?');
        $command->execute([$username]);
        $userArray = $command->fetchAssoc();

        $saltedPassword = hash('sha512', $password . $userArray['salt']);

        if($saltedPassword == $userArray['password']) {
            $this->id = $userArray['id'];
            $this->role = $userArray['role'];
            return true;
        }

        return false;
    }

    /**
     * Lives flat id getter
     *
     * @return int|null
     */
    public function getLivesFlatId(){
        return $this->livesFlatId;
    }

    /**
     * Serialize class
     *
     * @return string
     */
    public function __toString() {
        $fields = [];

        $vars = get_object_vars($this);
        unset($vars['dal']); // Remove the all model dal variable
        unset($vars['password']); // Remove password
        unset($vars['salt']); // Remove salt
        unset($vars['includedFields']); // Remove included fields

        foreach($vars as $key => $var) {
            $fieldName = $this->convertToUnderscore($key);

            if(count($this->includedFields) == 0 || in_array($fieldName, $this->includedFields)) {
                $fields[$fieldName] = $var;
            }
        }

        return json_encode($fields);
    }
}