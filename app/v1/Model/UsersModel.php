<?php

namespace App\v1\Model;
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
     * Get searchable fields
     *
     * @return array
     */
    public function getSearchableFields() : array {
        // TODO: Implement getSearchableFields() method.
    }

    public function getClaims() : array {
        return [];
    }

    /**
     * Try to login by username and password
     *
     * @param string $username - Username of the user to login
     * @param string $password - SHA512 encrypted password string of the user
     * @return UsersModel
     */
    public function login(string $username, string $password) : UsersModel {
        return new UsersModel();
    }
}