<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Bookable;

use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class AbstractBookable
 *
 * @package AmeliaBooking\Domain\Entity\Bookable
 */
class AbstractBookable
{
    /** @var Id */
    private $id;

    /** @var  Name */
    protected $name;

    /** @var Description */
    protected $description;

    /** @var  Color */
    protected $color;

    /** @var  Price */
    protected $price;

    /** @var  Status */
    protected $status;

    /** @var  Id */
    protected $categoryId;

    /** @var  Picture */
    protected $picture;

    /**
     * AbstractBookable constructor.
     *
     * @param Name        $name
     * @param Description $description
     * @param Color       $color
     * @param Price       $price
     * @param Status      $status
     * @param Id          $categoryId
     */
    public function __construct(
        Name $name,
        Description $description,
        Color $color,
        Price $price,
        Status $status,
        Id $categoryId
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->color = $color;
        $this->price = $price;
        $this->status = $status;
        $this->categoryId = $categoryId;
    }

    /**
     * @return Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Id $id
     */
    public function setId(Id $id)
    {
        $this->id = $id;
    }

    /**
     * @return Name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Name $name
     */
    public function setName(Name $name)
    {
        $this->name = $name;
    }

    /**
     * @return Description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param Description $description
     */
    public function setDescription(Description $description)
    {
        $this->description = $description;
    }

    /**
     * @return Color
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param Color $color
     */
    public function setColor(Color $color)
    {
        $this->color = $color;
    }

    /**
     * @return Price
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param Price $price
     */
    public function setPrice(Price $price)
    {
        $this->price = $price;
    }

    /**
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param Status $status
     */
    public function setStatus(Status $status)
    {
        $this->status = $status;
    }

    /**
     * @return Id
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param Id $categoryId
     */
    public function setCategoryId(Id $categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return Picture
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * @param Picture $picture
     */
    public function setPicture(Picture $picture)
    {
        $this->picture = $picture;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'               => null !== $this->getId() ? $this->getId()->getValue() : null,
            'name'             => $this->getName()->getValue(),
            'description'      => $this->getDescription()->getValue(),
            'color'            => $this->getColor()->getValue(),
            'price'            => $this->getPrice()->getValue(),
            'status'           => $this->getStatus()->getValue(),
            'categoryId'       => $this->getCategoryId()->getValue(),
            'pictureFullPath'  => null !== $this->getPicture() ? $this->getPicture()->getFullPath() : null,
            'pictureThumbPath' => null !== $this->getPicture() ? $this->getPicture()->getThumbPath() : null
        ];
    }
}
