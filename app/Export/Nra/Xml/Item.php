<?php
namespace Woo_BG\Export\Nra\Xml;

class Item {
    private string $name;

    private float $quantity;

    public float $subPrice;

    private float $price;

    private int $vatRate;

    private float $vatAmount;

    private bool $addVat;

    public function __construct(
        string $name,
        float $quantity,
        float $subPrice,
        float $price,
        float $vatAmount,
        int $vatRate = 20,
        bool $addVat = true
    ) {
        $this->name = $name;
        $this->quantity = $quantity;
        $this->subPrice = $subPrice;
        $this->price = $price;
        $this->vatRate = $vatRate;
        $this->vatAmount = $vatAmount;
        $this->addVat = $addVat;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return float
     */
    public function getSubPrice(): float
    {
        return $this->subPrice;
    }

    /**
     * @return int
     */
    public function getVatRate(): int
    {
        return $this->vatRate;
    }

    public function getVat(): float
    {
        return $this->vatAmount;
    }

    public function getSinglePrice(): float {
        $single_price = $this->price;

        if ( $this->addVat ) {
            $single_price = $this->price * (1+($this->vatRate / 100));
        }

        return $single_price;
    }

    public function getFinalPrice(): float
    {
        return $this->getSinglePrice() * $this->quantity;
    }

    public function getFinalSubPrice(): float
    {
        $single_price = $this->getSubPrice();

        if ( $this->addVat ) {
            $single_price = $this->getSubPrice() * (1+($this->vatRate / 100));
        }
        
        return $single_price * $this->quantity;
    }
}