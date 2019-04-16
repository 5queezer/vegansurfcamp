<?php

namespace AmeliaBooking\Domain\Entity\User;

use AmeliaBooking\Domain\ValueObjects\Gender;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Email;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Phone;

/**
 * Class Customer
 *
 * @package AmeliaBooking\Domain\Entity\User
 */
class Customer extends AbstractUser
{

    /** @var Description */
    private $note;

    /** @var Gender */
    private $gender;

    /**
     * @param Name        $firstName
     * @param Name        $lastName
     * @param Email       $email
     * @param Description $note
     * @param Phone       $phone
     * @param Gender      $gender
     */
    public function __construct(
        Name $firstName,
        Name $lastName,
        Email $email,
        Description $note,
        Phone $phone,
        Gender $gender
    ) {
        parent::__construct($firstName, $lastName, $email);
        $this->note = $note;
        $this->phone = $phone;
        $this->gender = $gender;
    }

    /**
     * @return Description
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param Description $note
     */
    public function setNote(Description $note)
    {
        $this->note = $note;
    }

    /**
     * @return Gender
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param Gender $gender
     */
    public function setGender(Gender $gender)
    {
        $this->gender = $gender;
    }

    /**
     * Get the user type in a string form
     */
    public function getType()
    {
        return self::USER_ROLE_CUSTOMER;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'note'   => $this->getNote()->getValue(),
                'phone'  => $this->getPhone()->getValue(),
                'gender' => $this->getGender()->getValue(),
            ]
        );
    }
}
