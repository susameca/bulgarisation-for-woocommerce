<?php
namespace Woo_BG\Export\Nra\Xml;

class Item {
    private string $name;

    private float $quantity;

    public float $subPrice;

    private float $price;

    private int $vatRate;

    public function __construct(
        string $name,
        float $quantity,
        float $subPrice,
        float $price,
        int $vatRate = 20
    ) {
        $this->name = $name;
        $this->quantity = $quantity;
        $this->subPrice = $subPrice;
        $this->price = $price;
        $this->vatRate = $vatRate;
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
        return ($this->vatRate * $this->price / 100) * $this->quantity;
    }

    public function getSubVat(): float
    {
        return ($this->vatRate * $this->subPrice / 100) * $this->quantity;
    }

    public function getFinalPrice(): float
    {
        return ($this->price * (1+($this->vatRate / 100))) * $this->quantity;
    }

    public function getFinalSubPrice(): float
    {
        return ($this->subPrice * (1+($this->vatRate / 100))) * $this->quantity;
    }
}