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
class MessagesModel extends Model {

    /**
     * Message from user ID
     *
     * @var int
     */
    protected $fromId;

    /**
     * Message to user ID
     *
     * @var
     */
    protected $toId;

    /**
     * Message body
     *
     * @var
     */
    protected $message;

    /**
     * Get searchable fields in model
     *
     * @return array
     */
    public function getSearchableFields() : array {
        return ['message'];
    }

    /**
     * From ID getter
     *
     * @return int|null
     */
    public function getFromId() {
        return $this->fromId;
    }

    /**
     * To ID getter
     *
     * @return int|null
     */
    public function getToId() {
        return $this->toId;
    }
}