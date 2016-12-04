<?php

namespace App\v1\Model;

use Resty\Model\Model;

/**
 * Flats Model
 *
 * Model for the flats
 *
 * @package    App
 * @subpackage v1\Model
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class FlatsModel extends Model {

    /**
     * Address of the flat
     *
     * @var string
     */
    protected $address;

    /**
     * Number of the max tenants
     *
     * @var int
     */
    protected $maxTenants;

    /**
     * Id of the owner
     *
     * @var int
     */
    protected $ownerId;

    /**
     * Get searchable fields in model
     *
     * @return array
     */
    public function getSearchableFields() : array {
        return ['address'];
    }

    /**
     * Owner id getter
     *
     * @return int
     */
    public function getOwnerId() : int {
        return $this->ownerId;
    }

    /**
     * Max teanats getter
     *
     * @return int
     */
    public function getMaxTenants() : int {
        return $this->maxTenants;
    }
}